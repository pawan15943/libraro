<?php

namespace App\Services\Crm;

use App\Models\Crm\CrmActivity;
use App\Models\Crm\CrmCall;
use App\Models\Crm\CrmDisposition;
use App\Models\Crm\CrmImport;
use App\Models\Crm\CrmLead;
use App\Models\Crm\CrmReminder;
use App\Models\Crm\CrmStage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

/**
 * Everything that changes a lead: create, edit, stage / status, calls with
 * their disposition, follow-up reminders, messages, assignment and imports.
 * Every change writes a timeline entry (crm_activities).
 */
class CrmLeadService
{
    public static function can(?User $user, string $permission): bool
    {
        return $user && Gate::forUser($user)->allows('has-permission', [$permission]);
    }

    public static function seesAll(?User $user): bool
    {
        return self::can($user, 'crm-view-all-leads');
    }

    /** Leads the user may see: all for managers, own + unassigned for agents. */
    public function visibleTo(User $user): Builder
    {
        $q = CrmLead::query();
        if (! self::seesAll($user)) {
            $q->where(fn ($w) => $w->where('assigned_to', $user->id)->orWhereNull('assigned_to'));
        }

        return $q;
    }

    public function findVisible(User $user, int $id): CrmLead
    {
        return $this->visibleTo($user)->findOrFail($id);
    }

    /** People who can be given leads (have crm-view-leads), for assign pickers. */
    public function agents(): \Illuminate\Support\Collection
    {
        return User::orderBy('name')->get()->filter(fn ($u) => self::can($u, 'crm-view-leads'))->values();
    }

    public function create(array $data, ?User $by, string $source = 'manual', ?int $importId = null): CrmLead
    {
        $mobile = CrmLead::normaliseMobile($data['mobile'] ?? null);
        if (! $mobile) {
            throw ValidationException::withMessages(['mobile' => 'Enter a valid 10 digit Indian mobile number.']);
        }
        if (CrmLead::withTrashed()->where('mobile', $mobile)->exists()) {
            throw ValidationException::withMessages(['mobile' => 'A lead with this mobile number already exists.']);
        }

        [$stageId, $subId] = $this->defaultStage($source, $data['stage_id'] ?? null, $data['sub_stage_id'] ?? null);

        return DB::transaction(function () use ($data, $by, $source, $importId, $mobile, $stageId, $subId) {
            $lead = CrmLead::create([
                'contact_name' => $data['contact_name'] ?? null,
                'library_name' => $data['library_name'] ?? null,
                'mobile' => $mobile,
                'alt_mobile' => CrmLead::normaliseMobile($data['alt_mobile'] ?? null),
                'email' => $data['email'] ?? null,
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'address' => $data['address'] ?? null,
                'source' => $source,
                'library_id' => $data['library_id'] ?? null,
                'stage_id' => $stageId,
                'sub_stage_id' => $subId,
                'priority' => $data['priority'] ?? 'warm',
                'assigned_to' => $data['assigned_to'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $by?->id,
                'import_id' => $importId,
            ]);

            $this->log($lead, $by, $source === 'signup' ? 'sync' : ($importId ? 'import' : 'created'),
                $source === 'signup' ? 'Library registered, payment pending' : ($importId ? 'Uploaded' : 'Lead added'),
                $data['notes'] ?? null, ['source' => $source]);

            if (! empty($data['follow_up_at']) && $by) {
                $this->addReminder($lead, $by, Carbon::parse($data['follow_up_at']), 'First call');
            }

            return $lead;
        });
    }

    public function update(CrmLead $lead, array $data, User $by): CrmLead
    {
        if (array_key_exists('mobile', $data)) {
            $mobile = CrmLead::normaliseMobile($data['mobile']);
            if (! $mobile) {
                throw ValidationException::withMessages(['mobile' => 'Enter a valid 10 digit Indian mobile number.']);
            }
            if (CrmLead::withTrashed()->where('mobile', $mobile)->where('id', '!=', $lead->id)->exists()) {
                throw ValidationException::withMessages(['mobile' => 'Another lead has this mobile number.']);
            }
            $data['mobile'] = $mobile;
        }
        if (array_key_exists('alt_mobile', $data)) {
            $data['alt_mobile'] = CrmLead::normaliseMobile($data['alt_mobile']);
        }

        $fields = ['contact_name', 'library_name', 'mobile', 'alt_mobile', 'email', 'city', 'state', 'address', 'priority', 'notes', 'source'];
        $lead->fill(array_intersect_key($data, array_flip($fields)));
        $changed = array_keys($lead->getDirty());
        $lead->save();

        if ($changed) {
            $this->log($lead, $by, 'edit', 'Details updated', implode(', ', array_map(fn ($f) => str_replace('_', ' ', $f), $changed)));
        }

        return $lead;
    }

    /** Stage / status update (with an optional note). Closed stages end the follow-ups. */
    public function changeStage(CrmLead $lead, int $stageId, ?int $subId, ?User $by, ?string $note = null, bool $logIt = true): CrmLead
    {
        $stage = CrmStage::whereNull('parent_id')->findOrFail($stageId);
        if ($subId && ! CrmStage::where('id', $subId)->where('parent_id', $stage->id)->exists()) {
            $subId = null;
        }

        $from = $lead->stage;
        $fromSub = $lead->subStage;
        if ($lead->stage_id === $stage->id && (int) $lead->sub_stage_id === (int) $subId) {
            if ($note && $logIt) {
                $this->log($lead, $by, 'note', 'Note', $note);
            }

            return $lead;
        }

        $lead->stage_id = $stage->id;
        $lead->sub_stage_id = $subId;
        $lead->closed_at = $stage->is_closed ? now() : null;
        if ($stage->is_closed) {
            $lead->next_follow_up_at = null;
            $lead->reminders()->pending()->update(['status' => 'cancelled', 'updated_at' => now()]);
        }
        $lead->save();

        if ($logIt) {
            $sub = $subId ? CrmStage::find($subId)?->name : null;
            $this->log($lead, $by, 'stage', 'Stage: '.$stage->name.($sub ? ' › '.$sub : ''), $note, [
                'from' => trim(($from->name ?? '').($fromSub ? ' › '.$fromSub->name : '')),
                'to_stage_id' => $stage->id,
            ]);
        }

        // conversions are counted from these entries, credited to whoever closed it or the lead's agent;
        // a lead closed by the sync that nobody ever worked (owner had already paid) is not a conversion
        if ($stage->is_won && ! ($from->is_won ?? false) && ($by || $lead->calls_count > 0)) {
            $this->log($lead, $by ?? $lead->agent, 'enrolled', 'Enrolled', null);
        }

        return $lead;
    }

    /**
     * Log a call. The disposition moves the lead to its stage unless the agent picked one,
     * and a follow-up time creates a reminder.
     */
    public function logCall(CrmLead $lead, User $by, array $data): CrmCall
    {
        $disposition = CrmDisposition::findOrFail($data['disposition_id']);

        return DB::transaction(function () use ($lead, $by, $data, $disposition) {
            $stageId = (int) ($data['stage_id'] ?? 0) ?: $disposition->stage_id;
            $subId = (int) ($data['sub_stage_id'] ?? 0) ?: (empty($data['stage_id']) ? $disposition->sub_stage_id : null);
            $followUp = ! empty($data['follow_up_at']) ? Carbon::parse($data['follow_up_at']) : null;
            $stageFrom = $lead->stage_id;

            $call = CrmCall::create([
                'lead_id' => $lead->id,
                'user_id' => $by->id,
                'disposition_id' => $disposition->id,
                'is_connected' => $disposition->is_connected,
                'duration_seconds' => isset($data['duration_seconds']) && $data['duration_seconds'] !== '' ? max(0, (int) $data['duration_seconds']) : null,
                'notes' => $data['notes'] ?? null,
                'stage_from_id' => $stageFrom,
                'stage_to_id' => $stageId ?: $stageFrom,
                'sub_stage_to_id' => $subId ?: null,
                'follow_up_at' => $followUp,
                'called_at' => now(),
            ]);

            $lead->calls_count++;
            if ($disposition->is_connected) {
                $lead->connected_count++;
            }
            $lead->last_called_at = $call->called_at;
            $lead->last_disposition_id = $disposition->id;
            if (! $lead->assigned_to) {
                $lead->assigned_to = $by->id; // the first caller owns an unassigned lead
            }
            if (! empty($data['priority']) && isset(CrmLead::PRIORITIES[$data['priority']])) {
                $lead->priority = $data['priority'];
            }
            $lead->save();

            $this->log($lead, $by, 'call', 'Call · '.$disposition->name, $data['notes'] ?? null, [
                'call_id' => $call->id, 'connected' => $disposition->is_connected, 'duration' => $call->duration_seconds,
            ]);

            if ($stageId) {
                $this->changeStage($lead, $stageId, $subId ?: null, $by, null);
            }

            // the old follow-up is answered by this call
            $lead->reminders()->pending()->where('remind_at', '<=', now()->addMinutes(30))
                ->update(['status' => 'done', 'done_at' => now(), 'updated_at' => now()]);

            if ($followUp && ! $lead->refresh()->is_closed) {
                $this->addReminder($lead, $by, $followUp, $data['follow_up_note'] ?? ('After call: '.$disposition->name), $call->id);
            } elseif (! $lead->reminders()->pending()->exists()) {
                $lead->update(['next_follow_up_at' => null]);
            }

            return $call;
        });
    }

    /** One open follow-up per lead: a new one replaces the pending one. */
    public function addReminder(CrmLead $lead, User $by, Carbon $at, ?string $note = null, ?int $callId = null): CrmReminder
    {
        $lead->reminders()->pending()->update(['status' => 'cancelled', 'updated_at' => now()]);

        $reminder = CrmReminder::create([
            'lead_id' => $lead->id,
            'user_id' => $lead->assigned_to ?: $by->id,
            'call_id' => $callId,
            'remind_at' => $at,
            'note' => $note,
        ]);

        $lead->update(['next_follow_up_at' => $at]);
        $this->log($lead, $by, 'reminder', 'Follow-up set for '.$at->format('j M, g:i A'), $note);

        return $reminder;
    }

    public function completeReminder(CrmReminder $reminder, User $by, string $status = 'done'): void
    {
        $reminder->update(['status' => $status, 'done_at' => now()]);
        $lead = $reminder->lead;
        if ($lead && ! $lead->reminders()->pending()->exists()) {
            $lead->update(['next_follow_up_at' => null]);
        }
        if ($lead) {
            $this->log($lead, $by, 'reminder', $status === 'done' ? 'Follow-up done' : 'Follow-up cancelled', $reminder->note);
        }
    }

    public function logMessage(CrmLead $lead, User $by, string $channel, string $text, ?string $template = null): CrmActivity
    {
        return $this->log($lead, $by, $channel === 'sms' ? 'sms' : 'whatsapp',
            ($channel === 'sms' ? 'Text message' : 'WhatsApp').($template ? ' · '.$template : ''), $text);
    }

    public function addNote(CrmLead $lead, User $by, string $note): CrmActivity
    {
        return $this->log($lead, $by, 'note', 'Note', $note);
    }

    /** @param int[] $ids */
    public function assign(array $ids, ?int $agentId, User $by): int
    {
        $agent = $agentId ? User::findOrFail($agentId) : null;
        $n = 0;
        foreach (CrmLead::whereIn('id', $ids)->get() as $lead) {
            if ((int) $lead->assigned_to === (int) $agentId) {
                continue;
            }
            $lead->update(['assigned_to' => $agentId]);
            $lead->reminders()->pending()->update(['user_id' => $agentId ?: $by->id]);
            $this->log($lead, $by, 'assigned', $agent ? 'Assigned to '.$agent->name : 'Moved to the open pool');
            $n++;
        }

        return $n;
    }

    /**
     * @param  array<int, array<string, string|null>>  $rows  contact_name, library_name, mobile, email, city, state, notes, priority
     * @return array{import: CrmImport, created: int, duplicates: int, invalid: int, total: int}
     */
    public function import(array $rows, User $by, ?int $assignTo, string $method, ?string $fileName = null): array
    {
        $import = CrmImport::create(['user_id' => $by->id, 'file_name' => $fileName, 'method' => $method, 'assigned_to' => $assignTo]);
        $created = $duplicates = $invalid = 0;
        $seen = [];

        foreach ($rows as $row) {
            $mobile = CrmLead::normaliseMobile($row['mobile'] ?? null);
            if (! $mobile) {
                $invalid++;

                continue;
            }
            if (isset($seen[$mobile]) || CrmLead::withTrashed()->where('mobile', $mobile)->exists()) {
                $duplicates++;

                continue;
            }
            $seen[$mobile] = true;
            $row['mobile'] = $mobile;
            $row['assigned_to'] = $assignTo;
            $row['priority'] = isset(CrmLead::PRIORITIES[strtolower((string) ($row['priority'] ?? ''))]) ? strtolower($row['priority']) : 'warm';
            $this->create($row, $by, 'import', $import->id);
            $created++;
        }

        $import->update(['total' => count($rows), 'created' => $created, 'duplicates' => $duplicates, 'invalid' => $invalid]);

        return ['import' => $import, 'created' => $created, 'duplicates' => $duplicates, 'invalid' => $invalid, 'total' => count($rows)];
    }

    public function log(CrmLead $lead, ?User $by, string $type, string $title, ?string $body = null, array $meta = []): CrmActivity
    {
        return CrmActivity::create([
            'lead_id' => $lead->id,
            'user_id' => $by?->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'meta' => $meta ?: null,
        ]);
    }

    private function defaultStage(string $source, $stageId, $subId): array
    {
        if ($stageId) {
            return [(int) $stageId, $subId ? (int) $subId : null];
        }
        $slug = ['signup' => 'new-registered-not-paid', 'import' => 'new-uploaded', 'website' => 'new-website-demo-request', 'demo' => 'new-website-demo-request', 'inquiry' => 'new-website-demo-request'][$source] ?? 'new-fresh-lead';
        $sub = CrmStage::where('slug', $slug)->first();
        $new = CrmStage::where('slug', 'new')->whereNull('parent_id')->first() ?? CrmStage::main()->firstOrFail();

        return [$sub->parent_id ?? $new->id, $sub->id ?? null];
    }
}
