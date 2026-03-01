<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\PlanService;
use Illuminate\Http\Request;

class MasterController extends Controller
{
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
            'branch_id' => 'required'
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
