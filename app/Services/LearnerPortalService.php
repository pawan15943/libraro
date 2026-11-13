<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Learner;
use App\Models\LearnerDetail;
use App\Models\LearnerTransaction;
use App\Models\PlanType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * One summary of the signed-in learner for the learner portal (web): plan,
 * seat, shift time, plan state, dues, today's attendance and library contact.
 * Every learner page reads the same numbers from here, so they always agree.
 */
class LearnerPortalService
{
    /** days before the end date when a plan counts as "ending soon" */
    public const SOON_DAYS = 5;

    private array $cache = [];

    public function summary(Learner $learner): array
    {
        return $this->cache[$learner->id] ??= $this->build($learner);
    }

    private function build(Learner $learner): array
    {
        $branch = Branch::withoutGlobalScopes()->find($learner->branch_id);
        $library = DB::table('libraries')->where('id', $learner->library_id)->first();

        $detail = LearnerDetail::withoutGlobalScopes()
            ->where('learner_id', $learner->id)
            ->where('status', 1)
            ->whereNull('deleted_at')
            ->with(['plan' => fn ($q) => $q->withoutGlobalScopes(), 'planType' => fn ($q) => $q->withoutGlobalScopes()->withTrashed()])
            ->orderByDesc('plan_end_date')
            ->first();

        $last = $detail ?: LearnerDetail::withoutGlobalScopes()
            ->where('learner_id', $learner->id)->whereNull('deleted_at')
            ->with(['plan' => fn ($q) => $q->withoutGlobalScopes(), 'planType' => fn ($q) => $q->withoutGlobalScopes()->withTrashed()])
            ->orderByDesc('plan_end_date')->first();

        $upcoming = LearnerDetail::withoutGlobalScopes()
            ->where('learner_id', $learner->id)->where('status', 0)->whereNull('deleted_at')
            ->whereDate('plan_start_date', '>', Carbon::today())
            ->orderBy('plan_start_date')->first();

        $extendDays = (int) ($branch->extend_days ?? 0); // same source as getExtendDays()

        return [
            'name' => $learner->name,
            'first_name' => trim(strtok((string) $learner->name, ' ')) ?: $learner->name,
            'initials' => $this->initials($learner->name),
            'learner_no' => $learner->learner_no,
            'mobile' => $learner->mobile,
            'email' => $learner->email,
            'library' => [
                'name' => $branch?->display_name ?: ($library->library_name ?? $branch?->name ?? 'Library'),
                'library_no' => $library->library_no ?? null,
                'phone' => $branch?->mobile ?: ($library->library_mobile ?? null),
                'phone2' => $library->library_owner_contact ?? null,
                'email' => $branch?->email ?: ($library->email ?? null),
                'address' => $branch?->library_address ?: ($library->library_address ?? null),
                'map' => $branch?->google_map,
            ],
            'plan' => $last ? $this->plan($last, $extendDays, (bool) $detail, (int) $learner->frozen_status === 1) : null,
            'upcoming' => $upcoming ? Carbon::parse($upcoming->plan_start_date) : null,
            'dues' => $this->dues($learner, $last),
            'chat_unread' => $this->chatUnread($learner),
            'today' => $this->today($learner),
            'id_card_url' => $detail ? \Illuminate\Support\Facades\URL::signedRoute('idCard.signed.pdf', ['id' => $detail->id]) : null,
        ];
    }

    private function plan(LearnerDetail $d, int $extendDays, bool $isActive, bool $frozen = false): array
    {
        $today = Carbon::today();
        $start = Carbon::parse($d->plan_start_date)->startOfDay();
        $end = Carbon::parse($d->plan_end_date)->startOfDay();
        $daysLeft = $today->diffInDays($end, false);
        $graceEnd = $end->copy()->addDays($extendDays);
        $graceLeft = $today->diffInDays($graceEnd, false);
        $total = max(1, $start->diffInDays($end) + 1);
        $used = min($total, max(0, $start->diffInDays($today, false) + 1));

        if (!$isActive || ($daysLeft < 0 && $graceLeft < 0)) {
            $state = 'expired';
        } elseif ($frozen) {
            $state = 'frozen';
        } elseif ($daysLeft < 0) {
            $state = 'grace';
        } elseif ($daysLeft <= self::SOON_DAYS) {
            $state = 'soon';
        } else {
            $state = 'active';
        }

        $type = $d->planType;
        $seat = $d->seat_no ? (getSeatDisplayShortFloorName($d->seat_no) ?: (string) $d->seat_no) : null;

        return [
            'detail_id' => $d->id,
            'name' => $d->plan->name ?? 'Plan',
            'type' => $type->name ?? null,
            'time' => $type instanceof PlanType ? $type->timeLabel() : '',
            'hours' => $d->hour ?: ($type->slot_hours ?? null),
            'seat' => $seat,
            'start' => $start,
            'end' => $end,
            'days_left' => $daysLeft,
            'grace_left' => $graceLeft,
            'extend_days' => $extendDays,
            'progress' => (int) round($used / $total * 100),
            'state' => $state,
            'frozen' => $frozen,
            'frozen_since' => $frozen && $d->freeze_start_date ? Carbon::parse($d->freeze_start_date) : null,
            'price' => $d->plan_price_id,
        ];
    }

    private function dues(Learner $learner, ?LearnerDetail $detail): array
    {
        $txn = LearnerTransaction::withoutGlobalScopes()
            ->where('learner_id', $learner->id)
            ->when($detail, fn ($q) => $q->where('learner_detail_id', $detail->id))
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->first();

        $pending = (float) ($txn->pending_amount ?? 0);
        $dueDate = $txn && $txn->due_date ? Carbon::parse($txn->due_date) : null;

        return [
            'amount' => $pending,
            'due_date' => $dueDate,
            'overdue' => $pending > 0 && $dueDate && Carbon::now()->gt($dueDate),
        ];
    }

    private function today(Learner $learner): array
    {
        $row = Attendance::withoutGlobalScopes()
            ->where('learner_id', $learner->id)
            ->whereDate('date', Carbon::today())
            ->orderByDesc('id')
            ->first();

        $in = $row && $row->in_time ? Carbon::parse($row->in_time) : null;
        $out = $row && $row->out_time ? Carbon::parse($row->out_time) : null;

        return [
            'state' => $in ? ($out ? 'done' : 'in') : 'none',
            'in' => $in,
            'out' => $out,
        ];
    }

    /** Unread chat messages for the header dot. Never breaks a page (table may not exist yet). */
    private function chatUnread(Learner $learner): int
    {
        try {
            return app(ChatService::class)->unreadForLearner($learner->id);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function initials(?string $name): string
    {
        $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
        $first = mb_substr($parts[0] ?? '', 0, 1);
        $last = count($parts) > 1 ? mb_substr(end($parts), 0, 1) : '';

        return mb_strtoupper($first . $last) ?: 'L';
    }

    /** Label and tone for a plan state, shared by every page. */
    public static function stateLabel(?array $plan): array
    {
        if (!$plan) {
            return ['No active plan', 'mut'];
        }

        return match ($plan['state']) {
            'active' => [$plan['days_left'] === 0 ? 'Ends today' : $plan['days_left'] . ' ' . ($plan['days_left'] == 1 ? 'day' : 'days') . ' left', 'ok'],
            'soon' => [$plan['days_left'] === 0 ? 'Ends today' : 'Ends in ' . $plan['days_left'] . ' ' . ($plan['days_left'] == 1 ? 'day' : 'days'), 'warn'],
            'grace' => ['Extra days: ' . max(0, $plan['grace_left']) . ' left', 'warn'],
            'frozen' => ['Plan on hold', 'acc'],
            default => ['Plan ended', 'bad'],
        };
    }
}
