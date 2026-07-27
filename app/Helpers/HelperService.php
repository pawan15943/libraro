<?php

namespace App\Helpers;

use App\Models\Hour;
use App\Models\Library;
use App\Models\LearnerDetail;
use App\Models\LibraryTransaction;
use App\Models\PlanPrice;
use App\Models\PlanType;
use App\Models\Seat;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Request;
use Auth;
use Carbon\Carbon;

class HelperService
{
   
    protected static $titleMap = [
        'dashboard' => 'Dashboard',
        'student.index' => 'Student List',
        'student.create' => 'Create Student',
        'student.edit' => 'Edit Student',
        // Add more route-specific titles here
    ];

    public static function generateBreadcrumbs()
    {
        $breadcrumbs = collect();
        $currentRouteName = Route::currentRouteName();
        $routes = explode('.', $currentRouteName);

        foreach ($routes as $key => $route) {
            $routeName = implode('.', array_slice($routes, 0, $key + 1));
            if ($route === 'student') {
                $routeName = 'student.index'; // Map "student" to the "student.index" route
            }
            $url = '#'; // Default to not clickable for the last breadcrumb

            if (Route::has($routeName)) {
                try {
                    $parameters = Request::route()->parameters();
                    $url = count($parameters) > 0 ? route($routeName, $parameters) : route($routeName);
                } catch (\Exception $e) {
                    $url = '#';
                }
            }

            $breadcrumbs->push([
                'name' => ucfirst(str_replace('-', ' ', $route)),
                'url' => ($key === count($routes) - 1) ? '#' : $url
            ]);
        }

        return $breadcrumbs;
    }

    public static function generateTitle()
    {
        $breadcrumbs = self::generateBreadcrumbs();
        $routeName = Route::currentRouteName();

        if (array_key_exists($routeName, self::$titleMap)) {
            return self::$titleMap[$routeName];
        }

        $titleParts = $breadcrumbs->pluck('name')->toArray();
        return implode(' ', $titleParts);
    }

    public static function generateLicenseKey(string $macAddress): string
    {
        return hash('sha256', $macAddress . '125352-ABXG56-H7Y5F5-45IJNN');
    }



    public static function getOperationDetails($operation, array $planTypeNames = [], array $seatMap = [])
    {
        $details = [
            'message' => '',
            'operation_type'=>''
        ];

        if (!$operation) {
            return $details;
        }

        $userName = $operation->updated_by_name ?? 'System';

        // Convert updated_at to Carbon instance
        $updatedAt = Carbon::parse($operation->updated_at)->format('d-m-Y h:i:s A');

        switch ($operation->operation) {
            case 'renewSeat':
                $details['operation_type'] = 'Plan Renewed';
                $renewedEndDate = $operation->learner_detail_id
                    ? self::safeParseDate(
                        LearnerDetail::withTrashed()->where('id', $operation->learner_detail_id)->value('plan_end_date')
                    )
                    : null;

                $details['message'] = $renewedEndDate
                    ? 'Plan renewed until <strong>' . $renewedEndDate->format('d M Y') . '</strong>'
                    : 'Plan renewed successfully.';
                break;

            case 'learnerUpgrade':
                $details['operation_type'] = 'Plan Upgraded';
                // old_value/new_value are JSON-encoded {plan_id, plan_type_id} snapshots (see
                // LearnerOperationService::logOperation), not plain plan_type ids.
                $oldSnapshot = json_decode((string) $operation->old_value, true) ?: [];
                $newSnapshot = json_decode((string) $operation->new_value, true) ?: [];
                $oldTypeId = $oldSnapshot['plan_type_id'] ?? null;
                $newTypeId = $newSnapshot['plan_type_id'] ?? null;
                $oldPlanType = $planTypeNames[$oldTypeId] ?? ($oldTypeId ? PlanType::where('id', $oldTypeId)->value('name') : null);
                $newPlanType = $planTypeNames[$newTypeId] ?? ($newTypeId ? PlanType::where('id', $newTypeId)->value('name') : null);

                $details['message'] = "<strong>{$oldPlanType}</strong> → <strong>{$newPlanType}</strong> Plan Upgraded";
                break;

            case 'reactive':
                $details['operation_type'] = 'Seat Reactivated';
                $oldSeat = self::formatSeatDisplay($operation->old_value, $seatMap);
                $newSeat = self::formatSeatDisplay($operation->new_value, $seatMap);

                $details['message'] = "<strong>{$oldSeat}</strong> → <strong>{$newSeat}</strong> Reactivated";
                break;

            case 'swapseat':
                $details['operation_type'] = 'Seat Swapped';
                $oldSeat = self::formatSeatDisplay($operation->old_value, $seatMap);
                $newSeat = self::formatSeatDisplay($operation->new_value, $seatMap);

                $details['message'] = "<strong>{$oldSeat}</strong> → <strong>{$newSeat}</strong>";
                break;

            case 'closeSeat':
                $details['operation_type'] = 'Seat Closed';
                $details['message'] = 'Learner left the library, Seat is available now.';
                break;

            case 'deleteSeat':
                $details['operation_type'] = 'Seat Deleted';
                $details['message'] = 'Seat is soft deleted.';
                break;

            case 'changePlan':
                 $details['operation_type']='Change Plan';
                // old_value/new_value are JSON-encoded {plan_id, plan_type_id} snapshots (see
                // LearnerOperationService::logOperation), not plain plan_type ids.
                $oldSnapshot = json_decode((string) $operation->old_value, true) ?: [];
                $newSnapshot = json_decode((string) $operation->new_value, true) ?: [];
                $oldTypeId = $oldSnapshot['plan_type_id'] ?? null;
                $newTypeId = $newSnapshot['plan_type_id'] ?? null;
                $oldPlanType = $planTypeNames[$oldTypeId] ?? ($oldTypeId ? PlanType::where('id', $oldTypeId)->value('name') : null);
                $newPlanType = $planTypeNames[$newTypeId] ?? ($newTypeId ? PlanType::where('id', $newTypeId)->value('name') : null);

                $details['message'] = "Your plan has been updated from <strong>{$oldPlanType}</strong> to <strong>{$newPlanType}</strong> on {$updatedAt} by {$userName}.";
                break;
            case 'restoreSeat':
                $details['operation_type'] = 'Seat Restored';
                $details['message'] = 'This seat is restored.';
                break;

            case 'freezePlan':
                $details['operation_type'] = 'Plan Frozen';
                $frozenOn = self::safeParseDate($operation->new_value);
                $details['message'] = $frozenOn
                    ? 'Plan Frozen on <strong>' . $frozenOn->format('d M Y') . '</strong>'
                    : 'Plan Frozen.';
                break;

            case 'unfreezePlan':
                $details['operation_type'] = 'Plan Unfrozen';
                // old_value is the freeze start date; frozen days are derived from the gap
                // to this log row's own timestamp, since freeze_start_date is cleared by the time this renders.
                $frozenSince = self::safeParseDate($operation->old_value);
                $unfrozenAt = self::safeParseDate($operation->created_at ?? $operation->updated_at);
                $frozenDays = ($frozenSince && $unfrozenAt) ? $frozenSince->diffInDays($unfrozenAt) : null;
                $details['message'] = 'Plan Unfrozen on <strong>' . $updatedAt . '</strong>'
                    . ($frozenDays !== null ? " (<strong>{$frozenDays}</strong> frozen day(s) added)" : '');
                break;

            case 'giftDays':
                $details['operation_type'] = 'Added Gift Days';
                $added = (int) $operation->new_value - (int) $operation->old_value;
                $details['message'] = 'Added <strong>' . $added . ' gift days</strong>';
                break;

            case 'edit':
                self::fillEditOperationDetails($details, $operation);
                break;

            default:
             $details['operation_type']='';
                $details['message'] = "Operation performed successfully.";
                break;
        }

        // Prefix every activity message with a bold "Seat No. {seat} : {learner}"
        // line - callers set learner_name/learner_seat_no on the log row up front
        // (same pattern as updated_by_name above) since some callers (e.g.
        // LearnerController::buildLearnerActivityLog) query learner_operations_log
        // as a plain stdClass row with no ->learner relation to fall back on.
        $learnerName = $operation->learner_name ?? optional($operation->learner ?? null)->name ?? 'Learner';
        $seatNo = $operation->learner_seat_no ?? optional($operation->learner ?? null)->seat_no ?? null;

        // Uses <b>, not <strong> - bold like the rest of the message, but
        // messageHighlights() only extracts <strong> spans, so this seat/learner
        // prefix is excluded from message_highlights (unlike the real changed
        // values/dates in the message body, which should still surface there).
        $seatLine = $seatNo
            ? "<b>Seat No. {$seatNo} : {$learnerName}</b><br>"
            : "<b>{$learnerName}</b><br>";

        $details['message'] = $seatLine . $details['message'];

        return $details;
    }

    /**
     * The 'edit' log stores a full before/after snapshot of learner + plan-detail
     * fields together (see LearnerOperationService::logOperation). Diff the two to
     * tell "profile" edits (learner fields) apart from "plan detail" edits
     * (locker/plan/date fields) and build a short "which fields changed" summary.
     */
    private static function fillEditOperationDetails(
        array &$details,
        $operation
    ): void {
        // Short, lowercase names used to build the "X, Y, Z, N+ fields are updated."
        // summary sentence for a pure profile edit (see design: "Email, mobile no,
        // profile, 5+ fields are updated.").
        $shortLabels = [
            'name' => 'name', 'email' => 'email', 'mobile' => 'mobile no',
            'dob' => 'DOB', 'father_name' => 'father name', 'address' => 'address',
            'remark' => 'remark', 'alternate_mobile' => 'alternate mobile',
            'id_proof_name' => 'ID proof', 'id_proof_number' => 'ID proof number',
            'exam_id' => 'exam', 'no_expiry' => 'non-expiry', 'locker_no' => 'locker',
            'seat_no' => 'seat', 'profile_picture' => 'profile',
        ];

        // Title-case labels used for the "Plan Details Updated" line, e.g. "Locker
        // Added | Discount Removed | Plan Start Date Changes".
        $fieldLabels = [
            'plan_id' => 'Plan', 'plan_type_id' => 'Plan Type', 'seat_no' => 'Seat',
            'locker_no' => 'Locker', 'plan_price_id' => 'Plan Price',
        ];

        // plan_start_date/plan_end_date move together (changing the start date always
        // shifts the end date too - see edit-plan.blade.php's warning about this), so
        // they're excluded from the generic changed-fields list and reported below
        // as a single "Plan Start Date Changes" entry instead.
        $planDateFields = ['plan_start_date', 'plan_end_date'];

        $old = json_decode((string) $operation->old_value, true) ?: [];
        $new = json_decode((string) $operation->new_value, true) ?: [];

        $hasProfileChange = false;
        $profileFieldNames = [];

        foreach (($new['learner'] ?? []) as $field => $value) {
            $oldValue = $old['learner'][$field] ?? null;

            if ($oldValue == $value) {
                continue;
            }

            $hasProfileChange = true;
            $profileFieldNames[] = $shortLabels[$field] ?? strtolower(str_replace('_', ' ', $field));
        }

        $detailChangeLines = [];
        $planDatesChanged = false;
        foreach (($new['detail'] ?? []) as $field => $value) {
            $oldFieldValue = $old['detail'][$field] ?? null;

            if (in_array($field, $planDateFields, true)) {
                // The "before" snapshot is a raw DB string while the "after" snapshot
                // is a Carbon instance that got JSON-serialized in a different format
                // (see LearnerOperationService::updateDetail(), which always reassigns
                // plan_start_date/plan_end_date on every EDIT, even when unchanged) -
                // a raw string comparison here would flag a date change on every edit,
                // e.g. a locker-only update. Parse both sides before comparing.
                $oldDate = self::safeParseDate($oldFieldValue !== null ? (string) $oldFieldValue : null);
                $newDate = self::safeParseDate($value !== null ? (string) $value : null);
                if ($oldDate?->format('Y-m-d H:i:s') !== $newDate?->format('Y-m-d H:i:s')) {
                    $planDatesChanged = true;
                }

                continue;
            }

            if ($oldFieldValue == $value) {
                continue;
            }

            if (!isset($fieldLabels[$field])) {
                continue;
            }

            $detailChangeLines[] = self::formatShortChange($fieldLabels[$field], $oldFieldValue, $value);
        }

        // Locker/discount amounts live on the billing/transaction side, not on the
        // learner or plan-detail rows, so they're diffed separately here and folded
        // into the "plan detail" bucket — otherwise a locker/discount-only edit would
        // show no changed fields at all and fall back to a generic message.
        $billingLabels = ['locker_amount' => 'Locker', 'discount_amount' => 'Discount'];
        foreach ($billingLabels as $field => $label) {
            $oldAmount = (float) ($old['billing'][$field] ?? 0);
            $newAmount = (float) ($new['billing'][$field] ?? 0);

            if (abs($oldAmount - $newAmount) < 0.01) {
                continue;
            }

            $detailChangeLines[] = self::formatShortChange($label, $oldAmount > 0.009 ? $oldAmount : null, $newAmount > 0.009 ? $newAmount : null);
        }

        if ($planDatesChanged) {
            $detailChangeLines[] = 'Plan Start Date Changes';
        }

        $isPlanDetailsOnly = (!empty($detailChangeLines)) && !$hasProfileChange;
        $details['operation_type'] = $isPlanDetailsOnly ? 'Plan Details Updated' : 'Learner Profile Updated';

        if ($isPlanDetailsOnly) {
            $details['message'] = implode(' | ', $detailChangeLines);

            return;
        }

        if (!$hasProfileChange && empty($detailChangeLines)) {
            $details['message'] = 'Details updated successfully.';

            return;
        }

        $details['message'] = self::summarizeChangedFields($profileFieldNames);

        if (!empty($detailChangeLines)) {
            $details['message'] .= ' ' . implode(' | ', $detailChangeLines);
        }
    }

    /**
     * Builds the "X, Y, Z, N+ fields are updated." summary sentence used for the
     * "Learner Profile Updated" activity line - names the first 3 changed fields and
     * folds the rest into a "N+ fields" count rather than listing every field.
     */
    private static function summarizeChangedFields(array $fieldNames): string
    {
        $total = count($fieldNames);

        if ($total === 0) {
            return 'Details updated successfully.';
        }

        if ($total <= 3) {
            $sentence = implode(', ', $fieldNames) . ($total === 1 ? ' is updated.' : ' are updated.');

            return ucfirst($sentence);
        }

        $shown = array_slice($fieldNames, 0, 3);
        $remaining = $total - 3;
        $sentence = implode(', ', $shown) . ", {$remaining}+ fields are updated.";

        return ucfirst($sentence);
    }

    /**
     * Builds a short "{Label} Added/Removed/Changed" tag for a before/after field
     * diff - used by the "Plan Details Updated" activity line, which shows which
     * fields changed without the actual values (see design: "Locker Added | Discount
     * Removed | Plan Start Date Changes").
     */
    private static function formatShortChange(string $label, $oldValue, $newValue): string
    {
        $oldEmpty = $oldValue === null || $oldValue === '';
        $newEmpty = $newValue === null || $newValue === '';

        if ($oldEmpty && !$newEmpty) {
            return "{$label} Added";
        }

        if (!$oldEmpty && $newEmpty) {
            return "{$label} Removed";
        }

        return "{$label} Changed";
    }

    /**
     * Tolerates legacy activity-log rows whose date value got JSON-encoded with
     * literal wrapping quotes (a past bug in LearnerOperationLogService::stringValue()
     * for Carbon values) — strips them before parsing, and returns null instead of
     * throwing when the value still isn't a valid date.
     */
    private static function safeParseDate(?string $value): ?Carbon
    {
        if (empty($value)) {
            return null;
        }

        $value = trim($value, " \t\n\r\0\x0B\"");

        try {
            return Carbon::parse($value);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Formats a raw seat number as "{floor seat no} {Floor Name}" (e.g. "12 First
     * Floor") using the branch's seat map from generateSeatNumbers(), falling back
     * to the plain seat number when there's no floor to attach.
     */
    public static function formatSeatDisplay($seatNo, array $seatMap = []): string
    {
        if (empty($seatNo)) {
            return '';
        }

        $seat = collect($seatMap)->firstWhere('main', (int) $seatNo);

        if ($seat && !empty($seat['floor_name'])) {
            return $seat['floor'] . ' ' . $seat['floor_name'];
        }

        return (string) $seatNo;
    }

    /**
     * Shared by the mobile activity API (Api\V1\LearnerController::activity()) and
     * the web "All Activities" page so both render identical labels/colors for the
     * same operation.
     */
    public static function activityMeta(?string $operation): array
    {
        $map = [
            'renewSeat' => ['label' => 'Renew Seat', 'filter_key' => 'renew', 'color_code' => '#10B7D9'],
            'renewDelete' => ['label' => 'Renew Delete', 'filter_key' => 'renew', 'color_code' => '#10B7D9'],
            'learnerUpgrade' => ['label' => 'Plan Upgraded', 'filter_key' => 'modify', 'color_code' => '#E19A00'],
            'changePlan' => ['label' => 'Change Plan', 'filter_key' => 'modify', 'color_code' => '#E19A00'],
            'swapseat' => ['label' => 'Seat Swapped', 'filter_key' => 'swap', 'color_code' => '#D633E9'],
            'reactive' => ['label' => 'Reactive Seat', 'filter_key' => 'reactive', 'color_code' => '#22C55E'],
            'closeSeat' => ['label' => 'Seat Closed', 'filter_key' => 'close_plan', 'color_code' => '#F97316'],
            'deleteSeat' => ['label' => 'Delete Seat', 'filter_key' => 'delete', 'color_code' => '#DC2626'],
            'restoreSeat' => ['label' => 'Restore Seat', 'filter_key' => 'restore', 'color_code' => '#14B8A6'],
            'freezePlan' => ['label' => 'Plan Frozen', 'filter_key' => 'freeze_plan', 'color_code' => '#0EA5E9'],
            'unfreezePlan' => ['label' => 'Plan Unfrozen', 'filter_key' => 'freeze_plan', 'color_code' => '#0EA5E9'],
            'giftDays' => ['label' => 'Added Gift Days', 'filter_key' => 'gift_day', 'color_code' => '#14B8A6'],
            'edit' => ['label' => 'Edit', 'filter_key' => 'edit', 'color_code' => '#6366F1'],
        ];

        return $map[$operation] ?? [
            'label' => ucwords(str_replace(['_', '-'], ' ', (string) $operation)),
            'filter_key' => (string) $operation,
            'color_code' => '#6B7280',
        ];
    }

    /**
     * "Today" / "Yesterday" / "d M Y" section heading used to group activity feed
     * items by day - shared by the mobile activity API and the web "All Activities" page.
     */
    public static function activityDateLabel(Carbon $date): string
    {
        if ($date->isToday()) {
            return 'Today';
        }

        if ($date->isYesterday()) {
            return 'Yesterday';
        }

        return $date->format('d M Y');
    }

    /**
     * Pulls the <strong>...</strong> values out of an activity message_html (e.g.
     * seat numbers, changed field values, plan names) into a plain array, so the
     * mobile app / frontend can bold them without having to parse HTML themselves.
     */
    public static function messageHighlights(string $messageHtml): array
    {
        preg_match_all('/<strong>(.*?)<\/strong>/is', $messageHtml, $matches);

        return array_values(array_map(
            fn ($value) => html_entity_decode(trim(strip_tags($value))),
            $matches[1] ?? []
        ));
    }
}




