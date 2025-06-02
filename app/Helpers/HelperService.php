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
            'operation_type' => '',
            'field' => '',
            'plan' => null,
            'plan_type' => null,
            'seat_no' => null,
            'old' => null,
            'new' => null,
        ];

        if (!$operation) {
            return $details;
        }

        switch ($operation->operation) {
            case 'renewSeat':
                $details['operation_type'] = 'Renew';
                $details['field'] = 'Plan';
                $details['old']=Plan::where('library_id',Auth::user()->id)->where('id',$operation->old_value)->value('name');
                $details['new']=Plan::where('id',$operation->new_value)->value('name');
                break;

            case 'learnerUpgrade':
                $details['operation_type'] = 'Upgrade';
                $details['field'] = 'Plan Type' ?? null;
                $details['old']=PlanType::where('id',$operation->old_value)->value('name');
                $details['new']=PlanType::where('id',$operation->new_value)->value('name');
                break;

            case 'reactive':
                $details['operation_type'] = 'Reactive';
                $details['field'] = 'Seat';
                $details['old']=Seat::where('id',$operation->old_value)->value('seat_no');
                $details['new']=Seat::where('id',$operation->new_value)->value('seat_no');
                break;

            case 'swapseat':
                $details['operation_type'] = 'Swap';
                $details['field'] = 'Seat';
                $details['old']=Seat::where('id',$operation->old_value)->value('seat_no');
                $details['new']=Seat::where('id',$operation->new_value)->value('seat_no');
                break;

            case 'closeSeat':
                $details['operation_type'] = 'Close';
                $details['field'] = 'Plan End';
                $details['old']=$operation->old_value;
                $details['new']=$operation->new_value;
                break;

            case 'deleteSeat':
                $details['operation_type'] = 'Delete';
                $details['field'] = 'Deleted';
                
                $details['new']=$operation->new_value;
                break;

            default:
                break;
        }

      

        return $details;
    }

    
   
}




