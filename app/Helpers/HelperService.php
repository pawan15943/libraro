<?php

namespace App\Helpers;

use App\Models\Hour;
use App\Models\Library;
use App\Models\LibraryTransaction;
use App\Models\Plan;
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



    public static function getOperationDetails($operation, array $planNames = [], array $planTypeNames = [], array $seatMap = [])
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

                $details['operation_type']='Renew Seat';
                $oldPlan = $planNames[$operation->old_value] ?? Plan::where('library_id', getLibraryId())->where('id', $operation->old_value)->value('name');
                $newPlan = $planNames[$operation->new_value] ?? Plan::where('id', $operation->new_value)->value('name');

                $details['message'] = "Plan renewed successfully. <br>
                Your plan validity has been updated from <strong>{$oldPlan}</strong> to <strong>{$newPlan}</strong> on {$updatedAt} by {$userName}.";
                break;

            case 'learnerUpgrade':
                $details['operation_type'] = 'Plan Upgraded';
                $oldPlanType = $planTypeNames[$operation->old_value] ?? PlanType::where('id', $operation->old_value)->value('name');
                $newPlanType = $planTypeNames[$operation->new_value] ?? PlanType::where('id', $operation->new_value)->value('name');

                $details['message'] = "{$oldPlanType} → {$newPlanType}";
                break;

            case 'reactive':
                 $details['operation_type']='Reactive Seat';
                $details['message'] = "Seat reactivated successfully. <br>
                Seat number has been updated from <strong>{$operation->old_value}</strong> to <strong>{$operation->new_value}</strong> on {$updatedAt} by {$userName}.";
                break;

            case 'swapseat':
                $details['operation_type'] = 'Seat Swapped';
                $oldSeat = self::formatSeatDisplay($operation->old_value, $seatMap);
                $newSeat = self::formatSeatDisplay($operation->new_value, $seatMap);

                $details['message'] = "Seat {$oldSeat} → {$newSeat}";
                break;

            case 'closeSeat':
                $details['operation_type'] = 'Seat Closed';
                $details['message'] = 'Learner left the library, Seat is available now.';
                break;

            case 'deleteSeat':
                 $details['operation_type']='Delete Seat';
                $details['message'] = "Seat deleted successfully. <br>
                Seat number <strong>{$operation->new_value}</strong> has been deleted on {$updatedAt} by {$userName}.";
                break;

            case 'changePlan':
                 $details['operation_type']='Change Plan';
                $oldPlanType = $planTypeNames[$operation->old_value] ?? PlanType::where('id', $operation->old_value)->value('name');
                $newPlanType = $planTypeNames[$operation->new_value] ?? PlanType::where('id', $operation->new_value)->value('name');

                $details['message'] = "Plan type changed successfully. <br>
                Your plan type has been updated from <strong>{$oldPlanType}</strong> to <strong>{$newPlanType}</strong> on {$updatedAt} by {$userName}.";
                break;
            case 'restoreSeat':
                 $details['operation_type']='Restore Seat';
             $details['message'] = "Seat restored successfully. <br>
                Seat number <strong>{$operation->new_value}</strong> has been restored on {$updatedAt}.";
                break;

            case 'freezePlan':
                $details['operation_type'] = 'Plan Frozen';
                $details['message'] = 'Plan Frozen on ' . Carbon::parse($operation->new_value)->format('d M Y');
                break;

            case 'giftDays':
                $details['operation_type'] = 'Added Gift Days';
                $added = (int) $operation->new_value - (int) $operation->old_value;
                $details['message'] = 'Added ' . $added . ' gift days';
                break;

            case 'edit':
                self::fillEditOperationDetails($details, $operation);
                break;

            default:
             $details['operation_type']='';
                $details['message'] = "Operation performed successfully.";
                break;
        }

        return $details;
    }

    /**
     * The 'edit' log stores a full before/after snapshot of learner + plan-detail
     * fields together (see LearnerOperationService::logOperation). Diff the two to
     * tell "profile" edits (learner fields) apart from "plan detail" edits
     * (locker/plan/date fields) and build a short "which fields changed" summary.
     */
    private static function fillEditOperationDetails(array &$details, $operation): void
    {
        $fieldLabels = [
            'name' => 'Name', 'email' => 'Email', 'mobile' => 'Mobile No',
            'dob' => 'DOB', 'father_name' => 'Father Name', 'address' => 'Address',
            'remark' => 'Remark', 'alternate_mobile' => 'Alternate Mobile',
            'id_proof_name' => 'ID Proof', 'id_proof_number' => 'ID Proof Number',
            'exam_id' => 'Exam', 'no_expiry' => 'Non-Expiry', 'locker_no' => 'Locker',
            'seat_no' => 'Seat No', 'plan_id' => 'Plan', 'plan_type_id' => 'Plan Type',
            'plan_price_id' => 'Plan Price', 'plan_start_date' => 'Plan Start Date',
            'plan_end_date' => 'Plan End Date',
        ];

        $old = json_decode((string) $operation->old_value, true) ?: [];
        $new = json_decode((string) $operation->new_value, true) ?: [];

        $changedProfileFields = [];
        foreach (($new['learner'] ?? []) as $field => $value) {
            if (($old['learner'][$field] ?? null) != $value) {
                $changedProfileFields[] = $fieldLabels[$field] ?? ucwords(str_replace('_', ' ', $field));
            }
        }

        $changedDetailFields = [];
        foreach (($new['detail'] ?? []) as $field => $value) {
            if (($old['detail'][$field] ?? null) != $value) {
                $changedDetailFields[] = $fieldLabels[$field] ?? ucwords(str_replace('_', ' ', $field));
            }
        }

        $isPlanDetailsOnly = !empty($changedDetailFields) && empty($changedProfileFields);
        $details['operation_type'] = $isPlanDetailsOnly ? 'Plan Details Updated' : 'Learner Profile Updated';

        $changedFields = array_values(array_unique($isPlanDetailsOnly
            ? $changedDetailFields
            : array_merge($changedProfileFields, $changedDetailFields)));

        if (empty($changedFields)) {
            $details['message'] = 'Details updated successfully.';
        } elseif (count($changedFields) > 3) {
            $shown = array_slice($changedFields, 0, 2);
            $details['message'] = implode(', ', $shown) . ', ' . (count($changedFields) - 2) . '+ fields are updated.';
        } else {
            $details['message'] = implode(', ', $changedFields) . ' updated.';
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



    
   
}




