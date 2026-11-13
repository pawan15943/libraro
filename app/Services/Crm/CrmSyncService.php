<?php

namespace App\Services\Crm;

use App\Models\Crm\CrmLead;
use App\Models\Crm\CrmStage;
use App\Models\Library;
use Illuminate\Support\Facades\Cache;

/**
 * Keeps the CRM in step with library sign-ups:
 *  - a library that registered but has not paid becomes a lead (source "signup");
 *  - a lead whose library has paid moves to Enrolled, so it leaves the call queue.
 * Runs from the scheduler (crm:sync-libraries), the "Sync now" button and, at most
 * every 5 minutes, when someone opens a CRM page.
 */
class CrmSyncService
{
    public function __construct(private CrmLeadService $leads) {}

    public function maybeSync(): void
    {
        try {
            if (Cache::add('crm:libraries-synced', 1, 300)) {
                $this->sync();
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /** @return array{added: int, enrolled: int} */
    public function sync(): array
    {
        $added = 0;
        $known = CrmLead::withTrashed()->pluck('mobile')->flip();
        $linked = CrmLead::withTrashed()->whereNotNull('library_id')->pluck('library_id')->flip();

        Library::query()
            ->where('is_paid', 0)
            ->whereNotNull('library_mobile')
            ->orderBy('id')
            ->select(['id', 'library_name', 'library_owner', 'library_mobile', 'email', 'created_at'])
            ->chunk(200, function ($libraries) use (&$added, $known, $linked) {
                foreach ($libraries as $library) {
                    $mobile = CrmLead::normaliseMobile($library->library_mobile);
                    if (! $mobile || isset($known[$mobile]) || isset($linked[$library->id])) {
                        continue;
                    }
                    $this->leads->create([
                        'library_name' => $library->library_name,
                        'contact_name' => $library->library_owner,
                        'mobile' => $mobile,
                        'email' => $library->email,
                        'library_id' => $library->id,
                        'priority' => $library->created_at && $library->created_at->gt(now()->subDays(3)) ? 'hot' : 'warm',
                    ], null, 'signup');
                    $known[$mobile] = true;
                    $added++;
                }
            });

        return ['added' => $added, 'enrolled' => $this->markPaidAsEnrolled()];
    }

    /** Open leads whose library (linked, or with the same mobile) is paid → Enrolled. */
    private function markPaidAsEnrolled(): int
    {
        $won = CrmStage::whereNull('parent_id')->where('is_won', true)->first();
        if (! $won) {
            return 0;
        }
        $sub = CrmStage::where('parent_id', $won->id)->orderBy('sort')->first();

        $paidIds = Library::where('is_paid', 1)->pluck('id');
        $paidMobiles = Library::where('is_paid', 1)->pluck('library_mobile')
            ->map(fn ($m) => CrmLead::normaliseMobile($m))->filter()->values();

        $n = 0;
        CrmLead::open()
            ->where(fn ($q) => $q->whereIn('library_id', $paidIds)->orWhereIn('mobile', $paidMobiles))
            ->each(function (CrmLead $lead) use ($won, $sub, &$n) {
                $this->leads->changeStage($lead, $won->id, $sub?->id, null, 'The library paid for a plan (found automatically).');
                $n++;
            });

        return $n;
    }
}
