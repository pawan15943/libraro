<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Learner;
use App\Models\LearnerDetail;
use App\Models\LearnerTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

/**
 * "Pay to library" links sent inside the renewal / pending-payment reminder messages
 * (docs/QR_PAY_LINK.md). The learner opens the signed link (no login), pays the branch
 * UPI ID and uploads the screenshot. That creates a pending `bookings` row the library
 * verifies in Dashboard → Online / QR bookings:
 *   - renew → type `qr_renew` (approved like the QR renew form: a new plan, old dues first)
 *   - due   → type `qr_due_payment` (approved as a pending-payment collection, no new plan)
 *
 * The page is public: every query here skips the global library / branch scopes.
 */
class LearnerPayLinkService
{
    public const PURPOSES = ['renew', 'due'];

    /** Link lifetime: a reminder sent today still works for this many days. */
    public const VALID_DAYS = 15;

    /** Renewal opens this many days before the plan ends (same window the approval allows). */
    public const RENEW_WINDOW_DAYS = 7;

    public const TYPE_DUE = 'qr_due_payment';

    public const TYPE_RENEW = 'qr_renew';

    /**
     * Signed link for a reminder message, or null when the branch has no UPI ID
     * (then there is nothing to pay to and the message stays as it was).
     */
    public function url(int $learnerId, string $purpose): ?string
    {
        if (! in_array($purpose, self::PURPOSES, true) || $learnerId <= 0) {
            return null;
        }

        static $upi = [];
        $branchId = (int) Learner::withoutGlobalScopes()->where('id', $learnerId)->value('branch_id');
        if (! $branchId) {
            return null;
        }
        if (! array_key_exists($branchId, $upi)) {
            $upi[$branchId] = self::validUpi(Branch::withoutGlobalScopes()->where('id', $branchId)->value('upi_id'));
        }
        if (! $upi[$branchId]) {
            return null;
        }

        return URL::temporarySignedRoute(
            'learner.paylink',
            now()->addDays(self::VALID_DAYS)->endOfDay(),
            ['learner' => $learnerId, 'for' => $purpose]
        );
    }

    /** A UPI ID looks like name@bank; placeholders such as "#" do not count. */
    public static function validUpi($upi): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9._\-]{2,}@[A-Za-z0-9.\-]{2,}$/', trim((string) $upi));
    }

    /**
     * Everything the pay page needs. `state` says what the page shows:
     *   pay      → amount + UPI QR + upload
     *   waiting  → a screenshot is already with the library for verification
     *   nothing  → no dues / renewal already done / not eligible (see `message`)
     *   no_upi   → branch has no UPI ID: pay at the desk
     */
    public function context(Learner $learner, string $purpose): array
    {
        $branch = Branch::withoutGlobalScopes()->with(['library', 'city', 'state', 'hour'])->find($learner->branch_id);
        $detail = $this->currentDetail($learner->id);
        $pendingRows = $this->pendingRows($learner);
        $pending = round((float) $pendingRows->sum('pending_amount'), 2);

        $ctx = [
            'purpose' => $purpose,
            'learner' => $learner,
            'branch' => $branch,
            'detail' => $detail,
            'seat' => $this->seatLabel($learner),
            'pending' => $pending,
            'plan_amount' => 0.0,
            'plan_price' => 0.0,
            'renew_start' => null,
            'renew_end' => null,
            'amount' => 0.0,
            'state' => 'pay',
            'message' => null,
            'waiting' => null,
        ];

        if (! $branch) {
            return array_merge($ctx, ['state' => 'nothing', 'message' => 'This library branch is not available any more.']);
        }

        $waiting = $this->waitingBooking($learner, $purpose);
        if ($waiting) {
            return array_merge($ctx, ['state' => 'waiting', 'waiting' => $waiting, 'amount' => (float) $waiting->total_amount]);
        }

        if ($purpose === 'due') {
            if ($pending <= 0) {
                return array_merge($ctx, ['state' => 'nothing', 'message' => 'You have no pending amount. Thank you!']);
            }
            $ctx['amount'] = $pending;
        } else {
            $block = $this->renewBlockReason($learner, $detail);
            if ($block) {
                return array_merge($ctx, ['state' => 'nothing', 'message' => $block]);
            }
            $start = Carbon::parse($detail->plan_end_date)->addDay();
            $price = $this->renewPrice($detail, $start, (int) $branch->id);
            $planAmount = app(GlobalDiscountService::class)->payable($price, (int) $branch->id);

            $ctx['plan_price'] = $price;
            $ctx['plan_amount'] = $planAmount;
            $ctx['renew_start'] = $start;
            $ctx['renew_end'] = Carbon::parse(getEndDate($detail->plan_id, $start->copy(), $branch->id));
            $ctx['amount'] = round($planAmount + $pending, 2);
        }

        if (! self::validUpi($branch->upi_id)) {
            $ctx['state'] = 'no_upi';
        }

        return $ctx;
    }

    /**
     * Saves the learner's payment proof as a pending booking for the library to verify.
     * Re-checks everything on the server (the page may be old).
     *
     * @throws \RuntimeException with a learner-facing message
     */
    public function submit(Learner $learner, string $purpose, string $screenshotPath): Booking
    {
        return DB::transaction(function () use ($learner, $purpose, $screenshotPath) {
            // one learner at a time: a double tap must not create two requests
            Learner::withoutGlobalScopes()->where('id', $learner->id)->lockForUpdate()->first();

            $ctx = $this->context($learner, $purpose);
            if ($ctx['state'] === 'waiting') {
                throw new \RuntimeException('Your payment is already with the library for verification.');
            }
            if ($ctx['state'] !== 'pay') {
                throw new \RuntimeException($ctx['message'] ?: 'Online payment is not available. Please pay at the library desk.');
            }

            $detail = $ctx['detail'];
            if (! $detail) {
                throw new \RuntimeException('We could not find your plan. Please contact the library.');
            }

            $base = [
                'name' => $learner->name,
                // the Booking model encrypts these once (it skips already encrypted values)
                'mobile' => $learner->getRawOriginal('mobile'),
                'email' => $learner->getRawOriginal('email') ?: null,
                'dob' => $learner->dob ?: null,
                'password' => $learner->password ?: \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(16)),
                'branch_id' => $ctx['branch']->id,
                'plan_id' => $detail->plan_id,
                'plan_type_id' => $detail->plan_type_id,
                'payment_mode' => 'online',
                'payment_screenshot' => $screenshotPath,
                'status' => 'pending',
                'total_amount' => $ctx['amount'],
            ];

            if ($purpose === 'due') {
                $first = $this->pendingRows($learner)->first();

                return Booking::create($base + [
                    // no seat: a due payment never holds or blocks a seat (QR booking clash check)
                    'seat_no' => null,
                    'plan_price_id' => (int) round((float) $detail->plan_price_id),
                    'plan_start_date' => $detail->plan_start_date,
                    'plan_end_date' => $detail->plan_end_date,
                    'transaction_id' => $first?->id,
                    'type' => self::TYPE_DUE,
                ]);
            }

            $lastTransaction = LearnerTransaction::withoutGlobalScopes()
                ->where('learner_detail_id', $detail->id)
                ->orderByDesc('id')
                ->first()
                ?? LearnerTransaction::withoutGlobalScopes()->where('learner_id', $learner->id)->orderByDesc('id')->first();
            if (! $lastTransaction) {
                throw new \RuntimeException('We could not find your last payment. Please renew at the library desk.');
            }

            return Booking::create($base + [
                'seat_no' => $learner->seat_no ?: null,
                'plan_price_id' => (int) round($ctx['plan_price']),
                'plan_start_date' => $ctx['renew_start']->toDateString(),
                'plan_end_date' => $ctx['renew_end']->toDateString(),
                'transaction_id' => $lastTransaction->id,
                'type' => self::TYPE_RENEW,
            ]);
        });
    }

    /** The learner's latest plan (the one a renewal continues). */
    public function currentDetail(int $learnerId): ?LearnerDetail
    {
        return LearnerDetail::withoutGlobalScopes()
            ->where('learner_id', $learnerId)
            ->whereNull('deleted_at')
            ->with(['plan' => fn ($q) => $q->withoutGlobalScopes(), 'planType' => fn ($q) => $q->withoutGlobalScopes()])
            ->orderByDesc('plan_end_date')
            ->orderByDesc('id')
            ->first();
    }

    /** Open dues, oldest first (same rows the library's "Collect pending" uses). */
    public function pendingRows(Learner $learner)
    {
        return LearnerTransaction::withoutGlobalScopes()
            ->where('learner_id', $learner->id)
            ->where('branch_id', $learner->branch_id)
            ->whereNull('deleted_at')
            ->where('pending_amount', '>', 0)
            ->orderBy('id')
            ->get();
    }

    /** A screenshot of this learner for the same purpose still waiting in the library's list. */
    public function waitingBooking(Learner $learner, string $purpose): ?Booking
    {
        $type = $purpose === 'due' ? self::TYPE_DUE : self::TYPE_RENEW;
        $txIds = LearnerTransaction::withoutGlobalScopes()->where('learner_id', $learner->id)->pluck('id')
            ->map(fn ($id) => (string) $id)->all();
        if (! $txIds) {
            return null;
        }

        return Booking::where('branch_id', $learner->branch_id)
            ->where('type', $type)
            ->where('status', 'pending')
            ->whereIn('transaction_id', $txIds)
            ->latest('id')
            ->first();
    }

    /** Why this learner cannot renew online right now (same rules as the QR renew form), or null. */
    private function renewBlockReason(Learner $learner, ?LearnerDetail $detail): ?string
    {
        if (! $detail) {
            return 'We could not find your plan. Please contact the library.';
        }

        $lastWithTrashed = LearnerDetail::withoutGlobalScopes()->withTrashed()
            ->where('learner_id', $learner->id)->orderByDesc('plan_end_date')->first();
        if ($lastWithTrashed) {
            $operation = DB::table('learner_operations_log')->where('learner_detail_id', $lastWithTrashed->id)->value('operation');
            if (in_array($operation, ['deleteSeat', 'closeSeat'], true)) {
                return 'Your plan has been closed. Please contact the library to start it again.';
            }
        }

        $today = Carbon::today();
        if (LearnerDetail::withoutGlobalScopes()->where('learner_id', $learner->id)->whereNull('deleted_at')
            ->whereDate('plan_start_date', '>', $today)->exists()) {
            return 'Your renewal is already done. Thank you!';
        }

        if ((int) $detail->status !== 1) {
            return 'Your plan has expired. Please contact the library to start it again.';
        }

        $end = Carbon::parse($detail->plan_end_date);
        if ($end->gt($today->copy()->addDays(self::RENEW_WINDOW_DAYS))) {
            return 'Your plan is active till ' . $end->format('d M Y') . '. You can renew online from '
                . $end->copy()->subDays(self::RENEW_WINDOW_DAYS)->format('d M Y') . '.';
        }

        return null;
    }

    /** Today's price of the learner's plan + shift (same lookup as the QR booking form). */
    private function renewPrice(LearnerDetail $detail, Carbon $start, int $branchId): float
    {
        try {
            $fixed = Branch::withoutGlobalScopes()->where('id', $branchId)->value('fixed_billing_date');
            $price = $fixed
                ? getBillingCyclePrice($detail->plan_id, $detail->plan_type_id, $start->copy(), $branchId)
                : getPlanPrice($detail->plan_id, $detail->plan_type_id, $branchId);
        } catch (\Throwable $e) {
            $price = 0;
        }

        return (float) ($price ?: $detail->plan_price_id ?: 0);
    }

    private function seatLabel(Learner $learner): string
    {
        if (empty($learner->seat_no)) {
            return 'General';
        }
        try {
            $label = app(\App\Http\Controllers\QrEntryController::class)->getSeatDisplayByMainNo($learner->seat_no, $learner->branch_id);
        } catch (\Throwable $e) {
            $label = null;
        }

        return (string) ($label ?: $learner->seat_no);
    }
}
