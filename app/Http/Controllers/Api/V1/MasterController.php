<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\PlanService;
use Illuminate\Http\Request;

class MasterController extends Controller
{
    public function getStaticMasters()
    {
        /*
        |--------------------------------------------------------------------------
        | Plan Duration
        |--------------------------------------------------------------------------
        */
$planDurations = collect()

        // Months
        ->merge(collect(range(1, 12))->map(function ($i) {
            return [
                'value' => $i . ' MONTH',
                'label' => $i . ' MONTH',
            ];
        }))

        // Weeks
        ->merge(collect(range(1, 4))->map(function ($i) {
            return [
                'value' => $i . ' WEEK',
                'label' => $i . ' WEEK',
            ];
        }))

        // Days
        ->merge(collect(range(1, 31))->map(function ($i) {
            return [
                'value' => $i . ' DAY',
                'label' => $i . ' DAY',
            ];
        }))

        ->values();

        /*
        |--------------------------------------------------------------------------
        | Plan Types
        |--------------------------------------------------------------------------
        */

        $planTypes = [
            ['id' => 1,  'name' => 'Full Day'],
            ['id' => 2,  'name' => 'First Half'],
            ['id' => 3,  'name' => 'Second Half'],
            ['id' => 8,  'name' => 'All Day'],
            ['id' => 9,  'name' => 'Full Night'],
            ['id' => 0,  'name' => 'Custom'],
            ['id' => 10, 'name' => 'Reserved'],
            ['id' => 11, 'name' => 'VIP'],
        ];

        /*
        |--------------------------------------------------------------------------
        | Monthly Plan Type
        |--------------------------------------------------------------------------
        */

        $monthlyOptions = [
            ['value' => 30, 'label' => '30 Days'],
            ['value' => 28, 'label' => '28 Days'],
            ['value' => null, 'label' => 'According to Months'],
        ];

        return response()->json([
            'status' => true,
            'code'   => 200,
            'message'=> 'Static master data fetched successfully',
            'data'   => [
                'plan_duration' => $planDurations,
                'plan_types'     => $planTypes,
                'monthly_options'=> $monthlyOptions,
            ]
        ]);
    }
    public function plans(Request $request)
    {
       
        $request->validate([
            'library_id' => 'required|exists:libraries,id'
        ]);
        $libraryId=$request->library_id;
    

        $plans = Plan::where('library_id',$libraryId)
            ->get(['id', 'name']);

        return response()->json([
            'status' => true,
            'code' => 200,
            'message' => 'Plans fetched successfully',
            'data' => $plans
        ]);
    }
    public function getPlanTypeSeatWiseApi(Request $request, PlanService $service)
    {
         
 
        $validated = $request->validate([
            'seat_no' => 'nullable',
            'branch_id' => 'required|exists:branches,id'
        ]);
       

        $branchId = $request->branch_id; 
        

        $data = $service->getAvailablePlanTypes(
            $validated['seat_no'] ?? null,
            $branchId
        );

        return response()->json([
            'status' => true,
            'code' => 200,
            'message' => 'Plan types fetched successfully',
            'data' => $data
        ]);
    }
    public function getChargeableDaysApi(Request $request)
    {
        $validated = $request->validate([
            'plan_id'         => 'required|exists:plans,id',
            'plan_start_date' => 'required|date',
            'branch_id'       => 'required|exists:branches,id',
        ]);

        $daysInfo = getChargeableDays(
            $validated['plan_id'],
            $validated['plan_start_date'],
            $validated['branch_id']
        );

        return response()->json([
            'status'  => true,
            'code'    => 200,
            'message' => 'Chargeable days calculated successfully',
            'data'    => $daysInfo
        ]);
    }

    public function getPriceApi(Request $request, PlanService $priceService)
    {
        $validated = $request->validate([
            'plan_id'        => 'required|exists:plans,id',
            'plan_type_id'   => 'required|exists:plan_types,id',
            'plan_start_date'=> 'nullable|date',
            'branch_id'      => 'required|exists:branches,id',
        ]);

        $result = $priceService->calculatePrice(
            $validated['plan_id'],
            $validated['plan_type_id'],
            $validated['plan_start_date'] ?? null,
            $validated['branch_id']
        );

        return response()->json([
            'status' => true,
            'code'   => 200,
            'data'   => $result
        ]);
    }
}
