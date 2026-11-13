<?php

namespace App\Services\Ai;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Limo Support — the data checks behind each problem. Every check returns a list of
 * findings ['tone' => good|bad|warn|info, 'text' => ...] in the user's language.
 *
 * The rules mirror the panel's learner action menu
 * (resources/views/learner/partials/learner-actions.blade.php) and its helpers:
 *  - days_left   = today → plan_end_date (negative after it);
 *  - ext_left    = today → plan_end_date + branch extend_days;
 *  - most actions (Renew, Upgrade, Change plan, Swap, ID card, Close, reminders) hide once ext_left < 0;
 *  - Renew / Upgrade: days_left ≤ 5, not frozen, Renew also needs no queued plan;
 *  - Change plan: today < plan_start_date + 7 days;  Edit start date: ≤ plan_start_date + 3 days;
 *  - Freeze: frozen or days_left ≥ 0;  Reactivate: learners.status = 0;  Restore refused if the plan ended;
 *  - an option can also be hidden by the branch's "Hide … option" toggles, a missing permission in
 *    the library's Libraro plan, or (staff) a missing staff permission.
 */
trait SupportChecks
{
    /** Branch toggles that hide an option (toggle_features ids in branches.hide_field). */
    private const TOGGLES = [
        13 => ['Hide Upgrade Option', 'Upgrade छुपाएं'],
        14 => ['Hide Change Plan Option', 'Change Plan छुपाएं'],
        15 => ['Hide ID Card Generate Option', 'ID कार्ड छुपाएं'],
        16 => ['Hide Close Plan Option', 'Close Plan छुपाएं'],
        17 => ['Hide Edit Seat Plan Option', 'Edit Seat Plan छुपाएं'],
        33 => ['Hide Gift Days Option', 'Gift Days छुपाएं'],
        34 => ['Hide Freeze Days Option', 'Freeze छुपाएं'],
    ];

    private ?array $hiddenToggles = null;

    private function toggleHidden(int $id): bool
    {
        if ($this->hiddenToggles === null) {
            $raw = DB::table('branches')->where('id', $this->branchId)->value('hide_field');
            $this->hiddenToggles = array_map('intval', (array) (json_decode((string) $raw, true) ?: []));
        }

        return in_array($id, $this->hiddenToggles, true);
    }

    /** Is the permission part of the library's Libraro plan? */
    private function planAllows(string $permission): bool
    {
        $type = DB::table('libraries')->where('id', $this->libraryId)->value('library_type');
        if (! $type) {
            return true;
        }

        return DB::table('subscription_permission as sp')
            ->join('permissions as p', 'p.id', '=', 'sp.permission_id')
            ->where('sp.subscription_id', $type)->whereNull('sp.deleted_at')
            ->where('p.name', $permission)
            ->exists();
    }

    /** Why an option may be hidden for this user: Libraro plan, staff permission, branch toggle. */
    private function permissionChecks(?string $permission, ?int $toggle): array
    {
        $out = [];
        if ($permission && ! $this->planAllows($permission)) {
            $out[] = $this->finding('bad',
                'Your Libraro plan does not include “'.$permission.'”, so this option is hidden. Upgrade the Libraro plan to get it.',
                'आपके Libraro प्लान में “'.$permission.'” शामिल नहीं है, इसलिए यह ऑप्शन छुपा है। इसे पाने के लिए Libraro प्लान अपग्रेड करें।');
        }
        if ($permission && $this->staffId !== null && ! $this->can($permission)) {
            $out[] = $this->finding('bad',
                'Your staff account does not have the “'.$permission.'” permission. Ask the library owner to allow it in Staff → Access.',
                'आपके स्टाफ अकाउंट में “'.$permission.'” की अनुमति नहीं है। लाइब्रेरी मालिक से Staff → Access में यह अनुमति दिलवाएं।');
        }
        if ($toggle && $this->toggleHidden($toggle)) {
            $name = self::TOGGLES[$toggle] ?? ['this option', 'यह ऑप्शन'];
            $out[] = $this->finding('bad',
                'This option is switched off for your branch (“'.$name[0].'”). Turn it back on in Custom Management → feature toggles.',
                'आपकी ब्रांच में यह ऑप्शन बंद है (“'.$name[1].'”)। इसे Custom Management → feature toggles से फिर चालू करें।');
        }

        return $out;
    }

    private function finding(string $tone, string $en, string $hi): array
    {
        return ['tone' => $tone, 'text' => $this->t($en, $hi)];
    }

    /** Common blockers for any action on a learner: deleted, frozen, past the extension days. */
    private function learnerBlockers(array $L, bool $needsExtension = true, bool $frozenBlocks = true): array
    {
        $out = [];
        if ($L['deleted']) {
            $out[] = $this->finding('bad', $L['name'].' is deleted. Restore them from the Deleted list first.', $L['name'].' डिलीट हैं। पहले Deleted लिस्ट से Restore करें।');
        }
        if ($frozenBlocks && $L['frozen']) {
            $out[] = $this->finding('bad', $L['name'].'\'s plan is frozen — most options hide while frozen. Unfreeze first.', $L['name'].' का प्लान फ्रीज़ है — फ्रीज़ के दौरान ज़्यादातर ऑप्शन छुप जाते हैं। पहले Unfreeze करें।');
        }
        if ($needsExtension && $L['ext_left'] !== null && $L['ext_left'] < 0 && ! $L['no_expiry']) {
            $out[] = $this->finding('bad',
                'The plan ended on '.$this->d($L['end']).' and the '.$L['extend_days'].' extension day(s) are also over, so Renew, Upgrade, Change plan, Swap and ID card are hidden. Use Reactivate.',
                'प्लान '.$this->d($L['end']).' को खत्म हुआ और '.$L['extend_days'].' extension दिन भी बीत गए, इसलिए Renew, Upgrade, Change plan, Swap और ID कार्ड छुप गए हैं। Reactivate करें।');
        }

        return $out;
    }

    private function planLine(array $L): array
    {
        if (! $L['has_plan']) {
            return $this->finding('warn', $L['name'].' has no plan on record.', $L['name'].' का कोई प्लान दर्ज नहीं है।');
        }
        if ($L['no_expiry']) {
            return $this->finding('info', $L['name'].' has a non-expiring seat ('.($L['plan'] ?? '—').').', $L['name'].' की सीट कभी खत्म नहीं होती ('.($L['plan'] ?? '—').')।');
        }
        $state = $L['days_left'] > 5 ? $this->t('active', 'चालू')
            : ($L['days_left'] >= 0 ? $this->t('about to expire', 'खत्म होने वाला')
                : ($L['ext_left'] >= 0 ? $this->t('in extension days', 'extension दिनों में') : $this->t('expired', 'एक्सपायर')));

        return $this->finding('info',
            'Plan: '.($L['plan'] ?? '—').($L['shift'] ? ' · '.$L['shift'] : '').' · '.$this->d($L['start']).' → '.$this->d($L['end']).' ('.$state.').',
            'प्लान: '.($L['plan'] ?? '—').($L['shift'] ? ' · '.$L['shift'] : '').' · '.$this->d($L['start']).' → '.$this->d($L['end']).' ('.$state.')।');
    }

    /*
    |--------------------------------------------------------------------------
    | Checks per problem
    |--------------------------------------------------------------------------
    */

    private function checkRenew(?array $L): array
    {
        if (! $L) {
            return [];
        }
        $out = [$this->planLine($L)];
        $block = $this->learnerBlockers($L);
        if ($block) {
            return array_merge($out, $block);
        }
        if ($L['queued']) {
            $out[] = $this->finding('good',
                'Already renewed: the next plan ('.($L['queued']['plan'] ?? '—').') starts on '.$this->d($L['queued']['start']).' — that is why Renew is hidden (“Plan in Queue”). Nothing to do.',
                'पहले ही रिन्यू हो चुका है: अगला प्लान ('.($L['queued']['plan'] ?? '—').') '.$this->d($L['queued']['start']).' से शुरू होगा — इसी वजह से Renew छुपा है (“Plan in Queue”)। कुछ करने की ज़रूरत नहीं।');
        } elseif ($L['days_left'] !== null && $L['days_left'] > 5) {
            $from = $L['end']->copy()->subDays(5);
            $out[] = $this->finding('warn',
                'Renew is not open yet. It shows from '.$this->d($from).' (5 days before the plan ends on '.$this->d($L['end']).').',
                'Renew अभी नहीं खुला। यह '.$this->d($from).' से दिखेगा (प्लान '.$this->d($L['end']).' को खत्म होने से 5 दिन पहले)।');
        } else {
            $out[] = $this->finding('good',
                'Renew should be visible now'.($L['ext_left'] !== null ? ' — until '.$this->d($L['end']->copy()->addDays($L['extend_days'])).' (end + extension days).' : '.'),
                'Renew अभी दिखना चाहिए'.($L['ext_left'] !== null ? ' — '.$this->d($L['end']->copy()->addDays($L['extend_days'])).' तक (खत्म + extension दिन)।' : '।'));
        }

        return $out;
    }

    private function checkQueue(?array $L): array
    {
        if (! $L) {
            return [];
        }

        return [$L['queued']
            ? $this->finding('good', 'Next plan ('.($L['queued']['plan'] ?? '—').') is queued and starts on '.$this->d($L['queued']['start']).'.', 'अगला प्लान ('.($L['queued']['plan'] ?? '—').') क्यू में है और '.$this->d($L['queued']['start']).' से शुरू होगा।')
            : $this->finding('info', 'There is no queued plan for '.$L['name'].'.', $L['name'].' का कोई क्यू वाला प्लान नहीं है।')];
    }

    private function checkUpgrade(?array $L): array
    {
        if (! $L) {
            return [];
        }
        $out = array_merge([$this->planLine($L)], $this->learnerBlockers($L));
        if (count($out) > 1) {
            return $out;
        }
        if ($L['days_left'] !== null && $L['days_left'] > 5) {
            $out[] = $this->finding('warn', 'Upgrade opens on '.$this->d($L['end']->copy()->subDays(5)).' (5 days before the plan ends).', 'Upgrade '.$this->d($L['end']->copy()->subDays(5)).' से खुलेगा (प्लान खत्म होने से 5 दिन पहले)।');
        } else {
            $out[] = $this->finding('good', 'Upgrade should be visible now.', 'Upgrade अभी दिखना चाहिए।');
        }

        return $out;
    }

    private function checkChangePlan(?array $L): array
    {
        if (! $L) {
            return [];
        }
        $out = array_merge([$this->planLine($L)], $this->learnerBlockers($L));
        if (count($out) > 1 || ! $L['start']) {
            return $out;
        }
        $until = $L['start']->copy()->addDays(7);
        $out[] = Carbon::today()->lt($until)
            ? $this->finding('good', 'Change Plan is open until '.$this->d($until->copy()->subDay()).' (7 days from the plan start).', 'Change Plan '.$this->d($until->copy()->subDay()).' तक खुला है (प्लान शुरू होने से 7 दिन)।')
            : $this->finding('bad', 'Change Plan closed on '.$this->d($until).' — it is open only for 7 days from the plan start ('.$this->d($L['start']).'). Use Upgrade / Renew in the renewal window.', 'Change Plan '.$this->d($until).' को बंद हो गया — यह प्लान शुरू ('.$this->d($L['start']).') से सिर्फ 7 दिन खुला रहता है। रिन्यूअल के समय Upgrade / Renew करें।');

        return $out;
    }

    private function checkEditProfile(?array $L): array
    {
        if (! $L) {
            return [];
        }
        $out = array_merge([$this->planLine($L)], $this->learnerBlockers($L, false));
        if ($L['start'] && ! $L['frozen'] && ! $L['deleted']) {
            $until = $L['start']->copy()->addDays(3);
            $out[] = Carbon::today()->lte($until)
                ? $this->finding('good', 'The plan start date can still be edited until '.$this->d($until).'.', 'प्लान की शुरू की तारीख '.$this->d($until).' तक बदली जा सकती है।')
                : $this->finding('info', 'The plan start date can no longer be edited (only within 3 days of the start). Name, mobile and other details stay editable from Edit Seat Booking Details.', 'प्लान की शुरू की तारीख अब नहीं बदल सकती (सिर्फ शुरू होने के 3 दिन तक)। नाम, मोबाइल और बाकी जानकारी Edit Seat Booking Details से बदल सकते हैं।');
        }

        return $out;
    }

    private function checkEndDate(?array $L): array
    {
        if (! $L) {
            return [];
        }
        $out = [$this->planLine($L)];
        if ($L['frozen']) {
            $out[] = $this->finding('info', 'The plan is frozen — on Unfreeze, the frozen days are added to the end date.', 'प्लान फ्रीज़ है — Unfreeze करने पर फ्रीज़ के दिन खत्म की तारीख में जुड़ जाते हैं।');
        }
        if ($L['start']) {
            $until = $L['start']->copy()->addDays(3);
            $out[] = Carbon::today()->lte($until)
                ? $this->finding('good', 'The end date follows the start date and plan length. You can still fix the start date until '.$this->d($until).' (Edit plan).', 'खत्म की तारीख शुरू की तारीख और प्लान की अवधि से बनती है। शुरू की तारीख '.$this->d($until).' तक ठीक कर सकते हैं (Edit plan)।')
                : $this->finding('warn', 'The start date can no longer be edited. To add days, use Gift Days; for a wrong booking, send it to Libraro support.', 'शुरू की तारीख अब नहीं बदल सकती। दिन जोड़ने के लिए Gift Days दें; गलत बुकिंग हो तो Libraro सपोर्ट को भेजें।');
        }

        return $out;
    }

    private function checkFreeze(?array $L): array
    {
        if (! $L) {
            return [];
        }
        $out = [$this->planLine($L)];
        if ($L['deleted']) {
            return array_merge($out, $this->learnerBlockers($L, false, false));
        }
        if ($L['frozen']) {
            $out[] = $this->finding('info', 'The plan is already frozen — use Unfreeze to continue it.', 'प्लान पहले से फ्रीज़ है — चालू करने के लिए Unfreeze करें।');
        } elseif ($L['detail_status'] === 0 || ($L['days_left'] !== null && $L['days_left'] < 0)) {
            $out[] = $this->finding('bad', 'Freeze works only before the plan end date — it is not allowed in the extension days or after expiry.', 'Freeze सिर्फ प्लान खत्म होने की तारीख से पहले होता है — extension दिनों में या एक्सपायरी के बाद नहीं।');
        } else {
            $out[] = $this->finding('good', 'The plan is active, so Freeze should be available.', 'प्लान चालू है, इसलिए Freeze उपलब्ध होना चाहिए।');
        }

        return $out;
    }

    /** Gift days and swap: an active, unfrozen plan. */
    private function checkActivePlan(?array $L): array
    {
        if (! $L) {
            return [];
        }
        $out = array_merge([$this->planLine($L)], $this->learnerBlockers($L));
        if (count($out) === 1) {
            $out[] = $this->finding('good', 'The plan is active — this option should be available.', 'प्लान चालू है — यह ऑप्शन उपलब्ध होना चाहिए।');
        }

        return $out;
    }

    private function checkDeleted(?array $L): array
    {
        if (! $L) {
            return [];
        }
        if (! $L['deleted']) {
            return [$this->finding('good', $L['name'].' is not deleted — they are in the learner list'.($L['learner_status'] ? ' (active).' : ' (expired / closed filter).'), $L['name'].' डिलीट नहीं हैं — वे learner list में हैं'.($L['learner_status'] ? ' (चालू)।' : ' (Expired / Closed फ़िल्टर में)।'))];
        }
        if ($L['end'] && $L['end']->lt(Carbon::today())) {
            return [$this->finding('warn', $L['name'].' is in the Deleted list, but the plan ended on '.$this->d($L['end']).', so Restore will refuse. Use Reactivate to give a new plan.', $L['name'].' Deleted लिस्ट में हैं, पर प्लान '.$this->d($L['end']).' को खत्म हो गया, इसलिए Restore नहीं होगा। नया प्लान देने के लिए Reactivate करें।')];
        }

        return [$this->finding('good', $L['name'].' is in the Deleted list and the plan is still running — Restore will bring them back as they were.', $L['name'].' Deleted लिस्ट में हैं और प्लान अभी चालू है — Restore करने पर वे पहले जैसे वापस आ जाएंगे।')];
    }

    private function checkReactivate(?array $L): array
    {
        if (! $L) {
            return [];
        }
        $out = [$this->planLine($L)];
        if ($L['frozen']) {
            $out[] = $this->finding('bad', 'The plan is frozen — Reactivate hides while frozen. Unfreeze first.', 'प्लान फ्रीज़ है — फ्रीज़ के दौरान Reactivate छुपा रहता है। पहले Unfreeze करें।');
        } elseif ($L['learner_status'] === 1 && ! $L['deleted']) {
            $out[] = $this->finding('info', $L['name'].' is still active (including extension days) — Reactivate is only for expired, closed or deleted learners. Use Renew.', $L['name'].' अभी चालू हैं (extension दिन मिलाकर) — Reactivate सिर्फ expired, closed या deleted छात्रों के लिए है। Renew करें।');
        } else {
            $out[] = $this->finding('good', 'Reactivate should be available. The new start date must be on or after '.$this->d($L['end']).'.', 'Reactivate उपलब्ध होना चाहिए। नई शुरू की तारीख '.$this->d($L['end']).' या उसके बाद की होनी चाहिए।');
        }

        return $out;
    }

    private function checkLearnerFound(?array $L): array
    {
        if (! $L) {
            return [];
        }
        $where = $L['deleted'] ? $this->t('Deleted list', 'Deleted लिस्ट') : ($L['closed'] ? $this->t('Closed list', 'Closed लिस्ट') : ($L['learner_status'] ? $this->t('Active learners', 'चालू छात्र') : $this->t('Expired list', 'Expired लिस्ट')));

        return [
            $this->finding('good', 'Found '.$L['name'].' in this branch — look in: '.$where.'.', $L['name'].' इसी ब्रांच में मिले — यहां देखें: '.$where.'।'),
            $this->planLine($L),
        ];
    }

    private function checkDues(?array $L): array
    {
        if (! $L) {
            $d = $this->duesTotals();

            return [$this->finding('info', 'Branch total pending: '.$this->inr($d['amount']).' from '.$d['learners'].' learners ('.$this->inr($d['overdue']).' overdue).', 'ब्रांच का कुल बकाया: '.$this->inr($d['amount']).', '.$d['learners'].' छात्रों से ('.$this->inr($d['overdue']).' की तारीख निकल गई)।')];
        }
        $rows = DB::table('learner_transactions')->where('learner_id', $L['id'])->whereNull('deleted_at')
            ->orderByDesc('id')->limit(5)
            ->get(['total_amount', 'paid_amount', 'pending_amount', 'discount_amount', 'due_date', 'paid_date']);
        $out = [$this->finding($L['due'] > 0 ? 'warn' : 'good', $L['name'].' — total pending: '.$this->inr($L['due']).'.', $L['name'].' — कुल बकाया: '.$this->inr($L['due']).'।')];
        foreach ($rows as $r) {
            $out[] = $this->finding('info',
                'Bill '.$this->inr((float) $r->total_amount).' · paid '.$this->inr((float) $r->paid_amount).((float) $r->discount_amount > 0 ? ' · discount '.$this->inr((float) $r->discount_amount) : '').' · pending '.$this->inr((float) $r->pending_amount).($r->due_date ? ' · due '.$this->date($r->due_date) : ''),
                'बिल '.$this->inr((float) $r->total_amount).' · जमा '.$this->inr((float) $r->paid_amount).((float) $r->discount_amount > 0 ? ' · छूट '.$this->inr((float) $r->discount_amount) : '').' · बकाया '.$this->inr((float) $r->pending_amount).($r->due_date ? ' · देय '.$this->date($r->due_date) : ''));
        }

        return $out;
    }

    private function checkCollection(?array $L): array
    {
        $today = Carbon::today()->toDateString();
        $c = $this->collectionTotal($today, $today);
        $payLater = DB::table('learner_transaction_activity')->where('branch_id', $this->branchId)->whereNull('deleted_at')
            ->where('dr_cr', 'Cr')->where('payment_mode', 'PAYLATER')->whereDate('date', $today)->count();
        $modes = DB::table('learner_transaction_activity')->where('branch_id', $this->branchId)->whereNull('deleted_at')
            ->where('dr_cr', 'Cr')->where('amount', '>', 0)->whereNotIn('payment_mode', ['PAYLATER'])->whereDate('date', $today)
            ->selectRaw('UPPER(payment_mode) as m, SUM(amount) as t')->groupBy(DB::raw('UPPER(payment_mode)'))->pluck('t', 'm');

        $out = [$this->finding('info', "Today's collection: ".$this->inr($c['total']).' from '.$c['count'].' payments.', 'आज का कलेक्शन: '.$this->inr($c['total']).', '.$c['count'].' भुगतान।')];
        foreach ($modes as $m => $t) {
            $out[] = $this->finding('info', ucwords(strtolower((string) $m)).': '.$this->inr((float) $t), ucwords(strtolower((string) $m)).': '.$this->inr((float) $t));
        }
        if ($payLater) {
            $out[] = $this->finding('warn', $payLater.' pay-later booking(s) today are not in the collection until the money is collected.', 'आज की '.$payLater.' pay-later बुकिंग पैसे मिलने तक कलेक्शन में नहीं हैं।');
        }

        return $out;
    }

    private function checkAttendance(?array $L): array
    {
        if (! $L) {
            return [];
        }
        $out = [$this->planLine($L)];
        if (! $L['learner_status'] || $L['deleted']) {
            $out[] = $this->finding('bad', $L['name'].' is not active, so the QR will not mark attendance.', $L['name'].' चालू नहीं हैं, इसलिए QR से हाजिरी नहीं लगेगी।');
        }
        if (! $L['mobile_ok']) {
            $out[] = $this->finding('bad', 'The mobile number on '.$L['name'].'\'s profile is not a valid 10-digit number — verification needs it.', $L['name'].' की प्रोफाइल में मोबाइल नंबर सही 10 अंकों का नहीं है — वेरिफिकेशन के लिए यह ज़रूरी है।');
        }
        $today = Carbon::today()->toDateString();
        $punches = DB::table('learner_attendance_logs')->where('learner_id', $L['id'])->whereDate('punch_datetime', $today)->count();
        $out[] = $punches
            ? $this->finding('good', $punches.' scan(s) recorded today for '.$L['name'].'.', 'आज '.$L['name'].' के '.$punches.' स्कैन दर्ज हैं।')
            : $this->finding('info', 'No scan recorded today for '.$L['name'].'.', 'आज '.$L['name'].' का कोई स्कैन दर्ज नहीं है।');

        return $out;
    }

    private function checkReminder(?array $L): array
    {
        $out = $this->permissionChecks('WhatsApp Notification', null);
        $b = DB::table('branches')->where('id', $this->branchId)->first(['is_send_waba_sms', 'is_send_txt_sms']);
        if ($b && ! (int) $b->is_send_waba_sms && ! (int) $b->is_send_txt_sms) {
            $out[] = $this->finding('info', 'Automatic WhatsApp / SMS are off for this branch — reminders open free WhatsApp (wa.me) instead, where you press send.', 'इस ब्रांच में अपने आप जाने वाले WhatsApp / SMS बंद हैं — रिमाइंडर फ्री WhatsApp (wa.me) में खुलता है, जहां आपको भेजना दबाना होता है।');
        }
        if (! $L) {
            return $out;
        }
        $out[] = $this->planLine($L);
        if (! $L['mobile_ok']) {
            $out[] = $this->finding('bad', $L['name'].'\'s mobile number is not a valid 10-digit number — the message cannot open.', $L['name'].' का मोबाइल नंबर सही 10 अंकों का नहीं है — मैसेज नहीं खुल सकता।');
        }
        if ($L['ext_left'] !== null && $L['ext_left'] < 0) {
            $out[] = $this->finding('bad', 'The plan and its extension days are over, so the reminder buttons are hidden. Reactivate or contact the learner directly.', 'प्लान और extension दिन खत्म हो गए, इसलिए रिमाइंडर बटन छुप गए। Reactivate करें या छात्र से सीधे बात करें।');
        } elseif ($L['days_left'] !== null && $L['days_left'] > 5) {
            $out[] = $this->finding('warn', 'The plan reminder opens on '.$this->d($L['end']->copy()->subDays(5)).' (5 days before expiry). “Always on WhatsApp” works any time.', 'प्लान रिमाइंडर '.$this->d($L['end']->copy()->subDays(5)).' से खुलेगा (एक्सपायरी से 5 दिन पहले)। “Always on WhatsApp” कभी भी काम करता है।');
        } else {
            $out[] = $this->finding('good', 'The plan reminder is open for '.$L['name'].' now.', $L['name'].' के लिए प्लान रिमाइंडर अभी खुला है।');
        }
        if ($L['overdue']) {
            $out[] = $this->finding('info', 'There is an overdue payment, so the “Overdue payment” WhatsApp reminder is also shown.', 'भुगतान की तारीख निकल गई है, इसलिए “Overdue payment” WhatsApp रिमाइंडर भी दिखता है।');
        }

        return $out;
    }

    private function checkIdCard(?array $L): array
    {
        $out = $this->permissionChecks('Genrate ID Card', 15);
        if ($L) {
            $out[] = $this->planLine($L);
            if ($L['ext_left'] !== null && $L['ext_left'] < 0) {
                $out[] = $this->finding('bad', 'The plan and extension days are over — ID cards are made only for active learners.', 'प्लान और extension दिन खत्म हो गए — ID कार्ड सिर्फ चालू छात्रों के बनते हैं।');
            }
        } else {
            $out[] = $this->finding('info', 'Bulk print includes only learners with an active plan.', 'एक साथ प्रिंट में सिर्फ चालू प्लान वाले छात्र आते हैं।');
        }

        return $out;
    }

    private function checkSeatBooking(?array $L): array
    {
        $out = [];
        $hour = DB::table('hour')->where('branch_id', $this->branchId)->whereNull('deleted_at')->first(['hour', 'seats']);
        if (! $hour || (int) $hour->hour <= 0) {
            $out[] = $this->finding('bad', 'Library hours are not set for this branch (“Library hours not configured”) — set opening hours and seats in Branch settings.', 'इस ब्रांच के लाइब्रेरी घंटे सेट नहीं हैं (“Library hours not configured”) — Branch सेटिंग में खुलने के घंटे और सीटें सेट करें।');
        } else {
            $out[] = $this->finding('info', 'The branch is open '.(int) $hour->hour.' hours a day with '.(int) $hour->seats.' seats. A seat can hold bookings whose shift hours add up to at most '.(int) $hour->hour.' hours.', 'ब्रांच दिन में '.(int) $hour->hour.' घंटे खुलती है और '.(int) $hour->seats.' सीटें हैं। एक सीट पर कुल '.(int) $hour->hour.' घंटे तक की शिफ्ट बुक हो सकती हैं।');
        }

        if ($seat = $this->seatNumber()) {
            if ($hour && $seat > (int) $hour->seats) {
                $out[] = $this->finding('bad', 'Seat '.$seat.' does not exist — this branch has '.(int) $hour->seats.' seats.', 'सीट '.$seat.' मौजूद नहीं — इस ब्रांच में '.(int) $hour->seats.' सीटें हैं।');
            } else {
                $today = Carbon::today()->toDateString();
                $holders = DB::table('learner_detail as ld')->join('learners as l', 'l.id', '=', 'ld.learner_id')
                    ->leftJoin('plan_types as pt', 'pt.id', '=', 'ld.plan_type_id')
                    ->where('ld.branch_id', $this->branchId)->where('ld.seat_no', $seat)->whereNull('ld.deleted_at')->whereNull('l.deleted_at')
                    ->where('ld.plan_end_date', '>=', $today)
                    ->get(['l.name', 'pt.name as shift', 'pt.start_time', 'pt.end_time', 'pt.slot_hours', 'ld.plan_start_date', 'ld.plan_end_date']);
                if ($holders->isEmpty()) {
                    $out[] = $this->finding('good', 'Seat '.$seat.' is free in every shift.', 'सीट '.$seat.' हर शिफ्ट में खाली है।');
                }
                foreach ($holders as $h) {
                    $out[] = $this->finding('warn',
                        'Seat '.$seat.' is held by '.$h->name.' — '.($h->shift ?? '—').' '.$this->timeRange($h->start_time, $h->end_time).' until '.$this->date($h->plan_end_date).($h->plan_start_date > $today ? ' (future booking)' : '').'. An overlapping shift on this seat cannot be booked.',
                        'सीट '.$seat.' पर '.$h->name.' हैं — '.($h->shift ?? '—').' '.$this->timeRange($h->start_time, $h->end_time).', '.$this->date($h->plan_end_date).' तक'.($h->plan_start_date > $today ? ' (आगे की बुकिंग)' : '').'। इस सीट पर overlap वाली शिफ्ट बुक नहीं हो सकती।');
                }
            }
        } else {
            $s = $this->seatStats();
            $out[] = $this->finding($s['vacant'] ? 'info' : 'warn', $s['vacant'].' of '.$s['total'].' seats are free in all shifts right now. Tell me the seat number to see who holds it.', 'अभी '.$s['total'].' में से '.$s['vacant'].' सीटें हर शिफ्ट में खाली हैं। सीट नंबर बताइए, मैं बताऊंगा उस पर कौन है।');
        }
        $out = array_merge($out, $this->checkPrices(null), $this->subscriptionLock());

        return $out;
    }

    private function checkVacantSeats(?array $L): array
    {
        $s = $this->seatStats();
        $ext = DB::table('learner_detail')->where('branch_id', $this->branchId)->whereNull('deleted_at')->where('status', 1)
            ->where('plan_end_date', '<', Carbon::today()->toDateString())->where('seat_no', '>', 0)->distinct()->count('seat_no');

        $out = [$this->finding($s['vacant'] ? 'good' : 'warn', $s['vacant'].' of '.$s['total'].' seats are free in all shifts; '.$s['general'].' learner(s) are on General seats.', $s['total'].' में से '.$s['vacant'].' सीटें हर शिफ्ट में खाली हैं; '.$s['general'].' छात्र General सीट पर हैं।')];
        if ($ext) {
            $out[] = $this->finding('info', $ext.' seat(s) are held by learners in their extension days — renew or close them to free those seats.', $ext.' सीटें extension दिनों वाले छात्रों के पास हैं — उन्हें रिन्यू या क्लोज़ करके सीटें खाली करें।');
        }

        return $out;
    }

    private function checkPrices(?array $L): array
    {
        $shifts = DB::table('plan_types')->where('branch_id', $this->branchId)->whereNull('deleted_at')->get(['id', 'name']);
        if ($shifts->isEmpty()) {
            return [$this->finding('bad', 'No shift (Plan Type) is set up for this branch yet.', 'इस ब्रांच में अभी कोई शिफ्ट (Plan Type) सेट नहीं है।')];
        }
        $priced = DB::table('plan_prices')->where('branch_id', $this->branchId)->whereNull('deleted_at')->where('price', '>', 0)->pluck('plan_type_id')->unique()->all();
        $missing = $shifts->filter(fn ($s) => ! in_array($s->id, $priced))->pluck('name')->all();

        return $missing
            ? [$this->finding('bad', 'These shifts have no price, so they do not show in booking: '.implode(', ', $missing).'.', 'इन शिफ्ट की कीमत सेट नहीं है, इसलिए ये बुकिंग में नहीं दिखतीं: '.implode(', ', $missing).'।')]
            : [$this->finding('good', 'All '.$shifts->count().' shifts have a price.', 'सभी '.$shifts->count().' शिफ्ट की कीमत सेट है।')];
    }

    private function checkSeatSetup(?array $L): array
    {
        $hour = DB::table('hour')->where('branch_id', $this->branchId)->whereNull('deleted_at')->value('seats');
        $floors = DB::table('floors')->where('branch_id', $this->branchId)->whereNull('deleted_at')->orderBy('floor_no')->get(['name', 'from_seat', 'to_seat']);
        $out = [$this->finding('info', 'Branch seat setting: '.(int) $hour.' seats.', 'ब्रांच की सीट सेटिंग: '.(int) $hour.' सीटें।')];
        if ($floors->isNotEmpty()) {
            $sum = $floors->sum(fn ($f) => max(0, (int) $f->to_seat - (int) $f->from_seat + 1));
            $out[] = $this->finding($sum === (int) $hour ? 'good' : 'bad',
                'Floors cover '.$sum.' seats ('.$floors->map(fn ($f) => $f->name.' '.$f->from_seat.'–'.$f->to_seat)->implode(', ').')'.($sum === (int) $hour ? '.' : ' — this does not match the '.(int) $hour.' seats.'),
                'फ्लोर '.$sum.' सीटें कवर करते हैं ('.$floors->map(fn ($f) => $f->name.' '.$f->from_seat.'–'.$f->to_seat)->implode(', ').')'.($sum === (int) $hour ? '।' : ' — यह '.(int) $hour.' सीटों से मेल नहीं खाता।'));
        }

        return $out;
    }

    private function checkStaffAccess(?array $L): array
    {
        if ($this->staffId !== null) {
            $u = DB::table('library_users')->where('id', $this->staffId)->first(['name', 'status']);
            $n = DB::table('library_user_permissions')->where('library_user_id', $this->staffId)->count();

            return [
                $this->finding('info', 'You are signed in as staff ('.($u->name ?? '—').') with '.$n.' permission(s). Only the library owner can add more.', 'आप स्टाफ ('.($u->name ?? '—').') के रूप में लॉगिन हैं, '.$n.' अनुमतियों के साथ। और अनुमति सिर्फ लाइब्रेरी मालिक दे सकता है।'),
            ];
        }
        $staff = DB::table('library_users')->where('library_id', $this->libraryId)->whereNull('deleted_at')->get(['id', 'name', 'status']);
        $out = [$this->finding('info', 'You have '.$staff->count().' staff account(s).', 'आपके '.$staff->count().' स्टाफ अकाउंट हैं।')];
        foreach ($staff as $s) {
            $n = DB::table('library_user_permissions')->where('library_user_id', $s->id)->count();
            $out[] = $this->finding((int) $s->status === 1 ? ($n ? 'info' : 'warn') : 'bad',
                $s->name.': '.((int) $s->status === 1 ? 'active' : 'inactive — cannot log in').', '.$n.' permission(s).',
                $s->name.': '.((int) $s->status === 1 ? 'चालू' : 'बंद — लॉगिन नहीं कर सकते').', '.$n.' अनुमतियां।');
        }

        return $out;
    }

    private function checkOnlineBookings(?array $L): array
    {
        $pending = DB::table('bookings')->where('branch_id', $this->branchId)->where('status', 'pending')->count();
        $upi = DB::table('branches')->where('id', $this->branchId)->value('upi_id');

        return array_merge([
            $this->finding($pending ? 'warn' : 'info', $pending.' online booking request(s) are waiting for your approval.', $pending.' ऑनलाइन बुकिंग रिक्वेस्ट आपकी मंज़ूरी का इंतज़ार कर रही हैं।'),
            $upi ? $this->finding('good', 'UPI ID is set for online payment.', 'ऑनलाइन भुगतान के लिए UPI ID सेट है।')
                : $this->finding('warn', 'No UPI ID is set for this branch — add it in Branch settings so learners can pay online.', 'इस ब्रांच की UPI ID सेट नहीं है — Branch सेटिंग में डालें, ताकि छात्र ऑनलाइन भुगतान कर सकें।'),
        ], $this->checkPrices(null));
    }

    private function checkBranch(?array $L): array
    {
        $branches = DB::table('branches')->where('library_id', $this->libraryId)->whereNull('deleted_at')->count();
        $learners = DB::table('learners')->where('branch_id', $this->branchId)->whereNull('deleted_at')->count();

        return [
            $this->finding('info', 'You are viewing branch “'.$this->branchName().'” ('.$learners.' learners). You have '.$branches.' branch(es).', 'आप “'.$this->branchName().'” ब्रांच देख रहे हैं ('.$learners.' छात्र)। आपकी '.$branches.' ब्रांच हैं।'),
        ];
    }

    private function checkSubscription(?array $L): array
    {
        return $this->subscriptionLock(true);
    }

    /** The library's own Libraro plan (same logic as the panel's LoadMenus). */
    private function subscriptionLock(bool $always = false): array
    {
        $lib = DB::table('libraries')->where('id', $this->libraryId)->first(['status', 'is_paid', 'extend_days', 'library_type']);
        $last = DB::table('library_transactions')->where('library_id', $this->libraryId)->where('is_paid', 1)->whereNull('deleted_at')->orderByDesc('id')->first(['end_date', 'status']);
        $plan = $lib && $lib->library_type ? DB::table('subscriptions')->where('id', $lib->library_type)->value('name') : null;
        if (! $last) {
            return $always ? [$this->finding('warn', 'No paid Libraro plan was found for your library.', 'आपकी लाइब्रेरी का कोई paid Libraro प्लान नहीं मिला।')] : [];
        }
        $end = Carbon::parse($last->end_date);
        $left = Carbon::today()->diffInDays($end, false);
        $grace = (int) ($lib->extend_days ?? 0);

        if ($left < 0 && $left + $grace < 0) {
            return [$this->finding('bad', 'Your Libraro plan'.($plan ? ' ('.$plan.')' : '').' ended on '.$this->d($end).' — the panel is locked to the Dashboard until you renew.', 'आपका Libraro प्लान'.($plan ? ' ('.$plan.')' : '').' '.$this->d($end).' को खत्म हो गया — रिन्यू करने तक पैनल सिर्फ डैशबोर्ड तक सीमित है।')];
        }
        if ($left < 0) {
            return [$this->finding('warn', 'Your Libraro plan ended on '.$this->d($end).'; you are in the '.$grace.'-day grace period. Renew now to avoid the lock on '.$this->d($end->copy()->addDays($grace + 1)).'.', 'आपका Libraro प्लान '.$this->d($end).' को खत्म हुआ; आप '.$grace.' दिन के grace में हैं। '.$this->d($end->copy()->addDays($grace + 1)).' को पैनल बंद होने से बचने के लिए अभी रिन्यू करें।')];
        }
        if ($lib && ((int) $lib->status !== 1 || (int) $lib->is_paid !== 1)) {
            return [$this->finding('bad', 'Your library account is not active / paid — contact Libraro support.', 'आपका लाइब्रेरी अकाउंट चालू / paid नहीं है — Libraro सपोर्ट से संपर्क करें।')];
        }

        return $always || $left <= 5
            ? [$this->finding($left <= 5 ? 'warn' : 'good', 'Your Libraro plan'.($plan ? ' ('.$plan.')' : '').' is active until '.$this->d($end).' ('.$left.' days left).', 'आपका Libraro प्लान'.($plan ? ' ('.$plan.')' : '').' '.$this->d($end).' तक चालू है ('.$left.' दिन बाकी)।')]
            : [];
    }
}
