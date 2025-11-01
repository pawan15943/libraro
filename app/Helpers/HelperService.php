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



    public static function getOperationDetails($operation)
    {
        $details = [
            'message' => '',
        ];

        if (!$operation) {
            return $details;
        }

        $userName = $operation->updated_by_name ?? 'System'; 

        // Convert updated_at to Carbon instance
        $updatedAt = Carbon::parse($operation->updated_at)->format('d-m-Y h:i:s A');

        switch ($operation->operation) {
            case 'renewSeat':
                $oldPlan = Plan::where('library_id', getLibraryId())->where('id', $operation->old_value)->value('name');
                $newPlan = Plan::where('id', $operation->new_value)->value('name');

                $details['message'] = "Plan renewed successfully. <br>
                Your plan validity has been updated from <strong>{$oldPlan}</strong> to <strong>{$newPlan}</strong> on {$updatedAt} by {$userName}.";
                break;

            case 'learnerUpgrade':
                $oldPlanType = PlanType::where('id', $operation->old_value)->value('name');
                $newPlanType = PlanType::where('id', $operation->new_value)->value('name');

                $details['message'] = "Plan upgraded successfully. <br>
                Your plan type has been updated from <strong>{$oldPlanType}</strong> to <strong>{$newPlanType}</strong> on {$updatedAt} by {$userName}.";
                break;

            case 'reactive':
                $details['message'] = "Seat reactivated successfully. <br>
                Seat number has been updated from <strong>{$operation->old_value}</strong> to <strong>{$operation->new_value}</strong> on {$updatedAt} by {$userName}.";
                break;

            case 'swapseat':
                $details['message'] = "Seat swapped successfully. <br>
                Seat number has been changed from <strong>{$operation->old_value}</strong> to <strong>{$operation->new_value}</strong> on {$updatedAt} by {$userName}.";
                break;

            case 'closeSeat':
                $details['message'] = "Seat closed successfully. <br>
                Plan end has been updated from <strong>{$operation->old_value}</strong> to <strong>{$operation->new_value}</strong> on {$updatedAt} by {$userName}.";
                break;

            case 'deleteSeat':
                $details['message'] = "Seat deleted successfully. <br>
                Seat number <strong>{$operation->new_value}</strong> has been deleted on {$updatedAt} by {$userName}.";
                break;

            case 'changePlan':
                $oldPlanType = PlanType::where('id', $operation->old_value)->value('name');
                $newPlanType = PlanType::where('id', $operation->new_value)->value('name');

                $details['message'] = "Plan type changed successfully. <br>
                Your plan type has been updated from <strong>{$oldPlanType}</strong> to <strong>{$newPlanType}</strong> on {$updatedAt} by {$userName}.";
                break;

            default:
                $details['message'] = "Operation performed successfully.";
                break;
        }

        return $details;
    }



    
   
}




