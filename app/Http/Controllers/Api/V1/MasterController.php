<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\City;
use App\Models\Exam;
use App\Models\Floor;
use App\Models\Hour;
use App\Models\Learner;
use App\Models\LearnerDetail;
use App\Models\LibraryUser;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\PlanType;
use App\Models\Scopes\LibraryScope;
use App\Models\State;
use App\Models\Subscription;
use App\Services\PlanService;
use App\Services\SeatAvailabilityService;
use Illuminate\Http\Request;
use DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\Validator;
use App\Services\LibraryConfigurationService;
use Illuminate\Support\Facades\File;

class MasterController extends Controller
{
    // ✅ Get all states
    public function getStates()
    {
        $states = State::select('id', 'state_name')
            ->orderBy('state_name', 'asc')
            ->get();

        if ($states->isEmpty()) {
            return response()->json([
                'status' => false,
                'message' => 'No states found',
                'data' => []
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => 'States fetched successfully',
            'data' => $states
        ]);
    }

    // ✅ Get cities by state_id
    public function getCities(Request $request)
    {
        // ✅ Validate state_id exists
            $validator = Validator::make($request->all(), [
                'id' => 'required|integer|exists:states,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first(),
                    'errors' => $validator->errors()
                ]);
            }
            $state_id=$request->id;
        if (!is_numeric($state_id) || $state_id <= 0) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid state id'
            ], 400);
        }

        $stateExists = State::where('id', $state_id)->exists();

        if (!$stateExists) {
            return response()->json([
                'status' => false,
                'message' => 'State not found'
            ], 404);
        }

        $cities = City::where('state_id', $state_id)
            ->select('id', 'city_name')
            ->orderBy('city_name', 'asc')
            ->get();

        return response()->json([
            'status' => true,
            'message' => 'Cities fetched successfully',
            'data' => $cities
        ]);
    }
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
            ['value' => '30', 'label' => '30 Days'],
            ['value' => '28', 'label' => '28 Days'],
            ['value' => '', 'label' => 'Calendar wise'],
        ];
         $features = DB::table('features')->whereNull('deleted_at')->select('id','name','image')->get() 
         ->map(function ($item) {
                $item->image = $item->image 
                    ? url('public/'.$item->image) 
                    : null;
                return $item;
            });
         $exams=DB::table('exams')->whereNull('deleted_at')->select('id','name',)->get();
         $expenses=DB::table('expenses')->whereNull('deleted_at')->select('id','name',)->get();
         $libraryUserRoles=Role::where('guard_name','library_user')->select('name','guard_name')->get();

        return response()->json([
            'status' => true,
            'message'=> 'Static master data fetched successfully',
            'data'   => [
                'plan_duration' => $planDurations,
                'plan_types'     => $planTypes,
                'monthly_options'=> $monthlyOptions,
                'features'=> $features,
                'expenses'=> $expenses,
                'exams'=> $exams,
                'libraryUserRoles'=> $libraryUserRoles,
            ]
        ]);
    }

    public function features()
    {
        try {
            $features = DB::table('feedback_features')->whereNull('deleted_at')->select('id', 'name', 'image')->get()
                ->map(function ($item) {
                    $item->image = $item->image
                        ? url('public/' . $item->image)
                        : null;
                    return $item;
                });

            return response()->json([
                'status' => true,
                'message' => 'Features fetched successfully',
                'data' => $features,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function paymentTypeList()
    {
        $paymentTypes = [
            ['key' => 'SEAT ASSIGNMENT', 'value' => 'SEAT ASSIGNMENT'],
            ['key' => 'CHANGE PLAN', 'value' => 'CHANGE PLAN'],
            ['key' => 'RENEW', 'value' => 'RENEW'],
            ['key' => 'REFUND', 'value' => 'REFUND'],
            ['key' => 'REACTIVE', 'value' => 'REACTIVE'],
            ['key' => 'UPGRADE', 'value' => 'UPGRADE'],
            ['key' => 'TOKEN MONEY', 'value' => 'TOKEN MONEY'],
            ['key' => 'MISCELLANEOUS', 'value' => 'MISCELLANEOUS'],
            ['key' => 'EXPENSE', 'value' => 'EXPENSE'],
            ['key' => 'PENDING', 'value' => 'PENDING'],
            ['key' => 'LOCKER', 'value' => 'LOCKER'],
            ['key' => 'RESTORE', 'value' => 'RESTORE'],
            ['key' => 'EDIT', 'value' => 'EDIT'],
            ['key' => 'VIP', 'value' => 'VIP'],
            ['key' => 'RESERVED', 'value' => 'RESERVED'],
            ['key' => 'SEAT ASSIGNMENT(NON-EXPIRED)', 'value' => 'NON-EXPIRED'],
            ['key' => 'SETTLED', 'value' => 'SETTLED'],
        ];

        return response()->json([
            'status' => true,
            'message' => 'Payment types fetched successfully',
            'data' => $paymentTypes,
        ]);
    }
    public function plans(Request $request)
    {
        $validated = $request->validate([
            'library_id'       => 'required|exists:libraries,id',
           
        ]);
      
        $libraryId = $request->library_id;
       
        $plans = Plan::where('library_id',$libraryId)
            ->get(['id', 'name']);

        return response()->json([
            'status' => true,
            'message' => 'Plans fetched successfully',
            'data' => $plans
        ]);
    }
    public function getPlanTypeSeatWiseApi(Request $request, PlanService $service)
    {
       
        $validated = $request->validate([
            'branch_id'       => 'required|exists:branches,id',
            'seat_no' => 'nullable',
        ]);
       
      
        $data = $service->getAvailablePlanTypes(
            $validated['seat_no'] ?? null,
            $validated['branch_id']
        );

        return response()->json([
            'status' => true,
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
            'message' => 'Chargeable days calculated successfully',
            'data'    => $daysInfo
        ]);
    }

    public function getPriceApi(Request $request, PlanService $priceService)
    {
        if ($request->filled('discount_type') && ($request->discount_value === null || $request->discount_value === '')) {
            $request->merge(['discount_value' => 0]);
        }

        
        $validated = $request->validate([
            'plan_id'        => 'required|exists:plans,id',
            'plan_type_id' => [
                'required',
                Rule::exists('plan_types', 'id')->where(function ($query) use ($request) {
                    return $query->where('branch_id', $request->branch_id);
                }),
            ],
            'branch_id'      => 'required|exists:branches,id',
            'plan_start_date'=> 'nullable|date',

            'locker_amount'  => 'nullable|numeric',

            'discount_type' => [
                'nullable',
                'in:percentage,amount',
                Rule::requiredIf(function () use ($request) {
                    return $request->discount_value > 0;
                }),
            ],

            'discount_value' => [
                'nullable',
                'numeric',
                'required_with:discount_type',
                function ($attribute, $value, $fail) use ($request) {

                    // Percentage validation
                    if ($request->discount_type === 'percentage') {
                        if ($value > 100) {
                            $fail('Percentage cannot be more than 100.');
                        }
                        if ($value < 0) {
                            $fail('Percentage cannot be negative.');
                        }
                    }

                    // Amount validation
                    if ($request->discount_type === 'amount') {
                        if ($value < 0) {
                            $fail('Discount amount cannot be negative.');
                        }
                    }
                },
            ],
            'paid_amount'    => 'nullable|numeric'
        ]);
        

       $result = $priceService->calculatePrice(
            $validated['plan_id'],
            $validated['plan_type_id'],
            $validated['plan_start_date'] ?? null,
            $validated['branch_id'],
            $validated['locker_amount'] ?? 0,
            $validated['discount_type'] ?? null,
            $validated['discount_value'] ?? 0,
            $validated['paid_amount'] ?? 0
        );

        return response()->json([
            'status' => true,
            'data'   => $result
        ]);
    }

    public function getFilterPlantypeWithself(Request $request)
    {
         $validated = $request->validate([
            'learner_id'       => 'required|exists:learners,id',
           
        ]);

        try {
            $learner = Learner::find($request->learner_id);

            if (!$learner) {
                return response()->json([
                    'status' => false,
                    'message' => 'Learner not found'
                ], 404);
            }

            if ($learner->seat_no) {
                $filteredPlanTypes = filterPlantypeFromseat($learner->seat_no, $learner->id);
            } else {
                $filteredPlanTypes = PlanType::select('id', 'name','start_time','end_time')->get();
            }

            return response()->json([
                'status' => true,
                'message' => 'Plan types fetched successfully',
                'data' => $filteredPlanTypes
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function floorStore(Request $request)
    {
        if ($denied = $this->denyWithoutAppPermission(['Add Floor Master', 'add-floor-master'])) {
            return $denied;
        }

        $validated = $request->validate([
            'id' => [
                'nullable',
                Rule::exists('floors', 'id')->where(function ($query) use ($request) {
                    return $query->where('branch_id', $request->branch_id);
                })
            ],
            'branch_id' => 'required|exists:branches,id',
            'floor_no'  => [
                'required',
                'integer',
                'min:0',
                'max:99',
                Rule::unique('floors', 'floor_no')
                    ->where(fn ($query) => $query
                        ->where('branch_id', $request->branch_id)
                        ->whereNull('deleted_at'))
                    ->ignore($request->id),
            ],
            'name'      => 'required|string|max:255',
            'from_seat' => 'required|integer|min:1',
            'to_seat'   => 'required|integer|gte:from_seat',
        ]);

        DB::beginTransaction();

        try {

            /* ==============================
            GET BRANCH RECORD
            ============================== */

            $branchRecord = Branch::find($request->branch_id);

            if(!$branchRecord){
                return response()->json([
                    'status' => false,
                    'message' => 'Branch not found'
                ],404);
            }

            /* ==============================
            CALCULATE SEATS
            ============================== */

            $fromSeat = (int)$request->from_seat;
            $toSeat   = (int)$request->to_seat;

            $requested_total_seats = $toSeat - $fromSeat + 1;

            /* ==============================
            EXISTING SEATS IN THIS BRANCH
            ============================== */

            $existing_total = Floor::where('branch_id',$request->branch_id)
                ->sum('total_seats');
                
            $seats=Hour::withoutGlobalScopes()->select('id','seats')
                ->where('branch_id',$request->branch_id)
                ->first();
             
            if(!$seats){
                return response()->json([
                    'status' => false,
                    'message' => 'Seats not found'
                ],404);
            }
            $grand_branch_total =$seats->seats;

            /* ==============================
            IF EDITING FLOOR
            ============================== */

            if($request->id){

                $oldSeats = Floor::where('id',$request->id)
                    ->value('total_seats') ?? 0;

                $existing_total -= $oldSeats;
            }

            /* ==============================
            VALIDATE BRANCH SEAT LIMIT
            ============================== */

            if(($existing_total + $requested_total_seats) > $grand_branch_total){

                return response()->json([
                    'status' => false,
                    'message' => 'Adding or updating this floor exceeds the total allowed seats for this branch. Maximum allowed: '.$grand_branch_total
                ],200);
            }

            /* ==============================
            PREPARE DATA (NO total_seats)
            ============================== */

            $data = [
                'branch_id' => $request->branch_id,
                'floor_no'  => $request->floor_no,
                'name'      => $request->name,
                'from_seat' => $request->from_seat,
                'to_seat'   => $request->to_seat
            ];

            /* ==============================
            CREATE OR UPDATE
            ============================== */

            if($request->id){

                $floor = Floor::findOrFail($request->id);
                $floor->update($data);

                $message = "Floor updated successfully";

            }else{

                $floor = Floor::create($data);

                $message = "Floor created successfully";
            }

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => $message,
                // 'data'    => $floor
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ],500);
        }
    }

    public function floorlist(Request $request){
         $validated = $request->validate([
           
            'branch_id' => 'required|exists:branches,id',
           
        ]);

        $floors=Floor::where('branch_id',$request->branch_id)->select('id','name','floor_no','from_seat','to_seat','total_seats')->get();
        
        $totalSeats =  Hour::withoutGlobalScopes()->where('branch_id',$request->branch_id)->value('seats') ?? 0;
        
        foreach ($floors as $floor) {
            $floor->can_delete = true; // always true
            $floor->status     = $floor->deleted_at ? 'Inactive' : 'Active';
        }
        return response()->json([
            'status'  => true,
            'message' => "Floor fetch successfully",
            'data'    => [
                'total_seats' => $totalSeats,
                'floors'      => $floors
            ]
        ]);
    }
    public function floorDetail(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:floors,id',
        ]);

        $floor = Floor::where('id', $request->id)
            ->select('id','name','floor_no','from_seat','to_seat','total_seats','deleted_at')
            ->first();

        if (!$floor) {
            return response()->json([
                'status' => false,
                'message' => 'Floor not found'
            ], 200);
        }
        $floor->can_delete = true;
        $floor->status     = $floor->deleted_at ? 'Inactive' : 'Active';

        return response()->json([
            'status'  => true,
            'message' => 'Floor detail fetched successfully',
            'data'    => $floor
        ]);
    }
    public function deleteFloor(Request $request)
    {
        if ($denied = $this->denyWithoutAppPermission(['Add Floor Master', 'add-floor-master'])) {
            return $denied;
        }

        $request->validate([
            'id' => 'required|exists:floors,id',
        ]);

        $deleted = Floor::where('id', $request->id)
            ->delete();

        if (!$deleted) {
            return response()->json([
                'status' => false,
                'message' => 'Floor not found'
            ]);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Floor deleted successfully'
        ]);
    }
    public function floorStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:floors,id',
        ]);

        $floor = Floor::withTrashed()
            ->where('id', $request->id)
            ->first();

        if (!$floor) {
            return response()->json([
                'status' => false,
                'message' => 'Floor not found'
            ], 200);
        }

        // ✅ Toggle
        if (!$floor->trashed()) {
            $floor->delete();
            $message = 'Floor deactivated successfully';
        } else {
            $floor->restore();
            $message = 'Floor activated successfully';
        }

        return response()->json([
            'status'  => true,
            'message' => $message
        ]);
    }

    public function planEdit(Request $request){
        $libraryId = auth('library_api')->id();
        
        $validated = $request->validate([
            'id' => [
                'required',
                Rule::exists('plans','id')->where(function($q) use ($libraryId){
                    $q->where('library_id',$libraryId);
                })
            ],
           
        ]);

        $plan=Plan::withoutGlobalScopes()->where('id',$validated['id'])->select('id','name','monthdays','type','plan_id as no_monthdays')->first();

            if (!$plan) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Plan not found',
                    'data'    => []
                ], 200); // or 404 if you prefer
            }
        
       
        $plan->monthdays = $plan->monthdays ?? "";
          
         $hasUser = LearnerDetail::where('plan_id', $plan->id)->exists();

        $plan->can_delete = !$hasUser;
        $plan->status     = $plan->deleted_at ? 'Inactive' : 'Active';
         return response()->json([
                'status'  => true,
                'message' =>"Plan fetch successfully",
                'data'    => $plan
            ]);

    }

    public function planStore(Request $request)
    {
        if ($denied = $this->denyWithoutAppPermission(['Add Plan Master', 'add-plan-master', 'Add Plan', 'add-plan'])) {
            return $denied;
        }

        $libraryId = auth('library_api')->id();

        $validated = $request->validate([
            'id' => [
                'nullable',
                Rule::exists('plans','id')->where(function($q) use ($libraryId){
                    $q->where('library_id',$libraryId);
                })
            ],

            'type' => 'required|in:MONTH,YEAR,DAY,WEEK',
            'no_monthdays' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('plans', 'plan_id') // ✅ FIX HERE
                    ->where(function ($query) use ($libraryId, $request) {
                        return $query->where('library_id', $libraryId)
                                    ->where('type', $request->type);
                    })
                    ->ignore($request->id)
            ],

            'monthdays' => 'nullable|in:28,30'
        ]);

        DB::beginTransaction();

        try{

            /* =========================
            PLAN NAME
            ========================= */
            $plan_id= $validated['no_monthdays'] ;
            $name = $plan_id.' '.$validated['type'];

            $data = [
                'library_id' => $libraryId,
                'plan_id'    => $plan_id,
                'type'       => $validated['type'],
                'name'       => $name,
                'monthdays'  => $validated['type'] == 'MONTH'
                                ? ($validated['monthdays'] ?? null)
                                : null
            ];

            /* =========================
            CREATE / UPDATE
            ========================= */
            

            if($validated['id']){

                $plan = Plan::findOrFail($validated['id']);
                $plan->update($data);

                $message = "Plan updated successfully";

            }else{

                $plan = Plan::create($data);

                $message = "Plan created successfully";
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message'=> $message,
            ]);

        }catch(\Exception $e){

            DB::rollBack();

            return response()->json([
                'status' => false,
                'message'=> $e->getMessage()
            ],500);
        }
    }

    public function planlist(Request $request)
    {
        $libraryId = auth('library_api')->id();

        // ✅ Query 1: Fetch plans (optimized with COALESCE)
        $plans = Plan::withoutGlobalScopes()
            ->where('library_id', $libraryId)
            ->select(
                'id',
                'name',
                DB::raw('COALESCE(monthdays, "") as monthdays'),
                'type',
                'plan_id as no_monthdays',
                'deleted_at'
            )
            ->get();

        // ✅ Query 2: Get used plan_ids (avoid N+1)
        $planIds = $plans->pluck('id')->toArray();

        $usedPlans = LearnerDetail::whereIn('plan_id', $planIds)
            ->pluck('plan_id')
            ->flip(); // ⚡ O(1) lookup

        // ✅ Fast loop (better than map)
        foreach ($plans as $plan) {

            $plan->can_delete = !isset($usedPlans[$plan->id]); // ⚡ fast
            $plan->status     = $plan->deleted_at ? 'Inactive' : 'Active';
        }

        return response()->json([
            'status'  => true,
            'message' => "Plan fetch successfully",
            'data'    => $plans
        ]);
    }

    public function deletePlan(Request $request)
    {
        if ($denied = $this->denyWithoutAppPermission(['Add Plan Master', 'add-plan-master', 'Add Plan', 'add-plan'])) {
            return $denied;
        }

        $libraryId = auth('library_api')->id();

        // ✅ Validation
        $validated = $request->validate([
            'id' => 'required|exists:plans,id',
        ]);

        // ✅ Check ownership
        $plan = Plan::withoutGlobalScopes()
            ->where('id', $validated['id'])
            ->where('library_id', $libraryId)
            ->first();

        if (!$plan) {
            return response()->json([
                'status'  => false,
                'message' => 'Plan not found or not authorized'
            ], 200);
        }

        try {

            // ✅ Check learner usage (FAST)
            $hasLearner = LearnerDetail::where('plan_id', $plan->id)->exists();

            if ($hasLearner) {
            
                $plan->delete();

                $message = 'Plan in use, moved to inactive (soft deleted)';
            } else {
            
                $plan->forceDelete();

                $message = 'Plan permanently deleted';
            }

            return response()->json([
                'status'  => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function planStatus(Request $request)
    {
        $libraryId = auth('library_api')->id();
        $request->validate([
            'id'     => 'required|exists:plans,id',
            // 'status' => 'required|in:active,inactive'
        ]);

        $plan = Plan::withoutGlobalScopes()->withTrashed()
            ->where('id', $request->id)
            ->where('library_id', $libraryId)
            ->first();

        if (!$plan) {
            return response()->json([
                'status' => false,
                'message' => 'Plan not found'
            ],200);
        }
        if (!$plan->trashed()) {
             $plan->delete();
           
        }else{
            $plan->restore();
        }

        return response()->json([
            'status' => true,
            'message' => 'Status updated successfully'
        ]);
    }

  
    public function planTypeEdit(Request $request){
        $libraryId = auth('library_api')->id();
        
        $validated = $request->validate([
            'id' => [
                'required',
                Rule::exists('plan_types','id')->where(function($q) use ($libraryId){
                    $q->where('library_id',$libraryId);
                })
            ],
           
        ]);

        $plan=PlanType::withoutGlobalScopes()->where('id',$validated['id'])->select('id','name','start_time','end_time','slot_hours','day_type_id')->first();
          if (!$plan) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Plan Type not found',
                    'data'    => []
                ], 200); // or 404 if you prefer
            }
            // ✅ Single fast query (no N+1 issue here)
        $hasUser = LearnerDetail::where('plan_type_id', $plan->id)->exists();

        // ✅ Add fields
        $plan->can_delete = !$hasUser;
        $plan->status     = $plan->deleted_at ? 'Inactive' : 'Active';
        return response()->json([
                'status'  => true,
                'message' =>"Plan Type fetch successfully",
                'data'    => $plan 
            ]);

    }

    public function plantypeStore(Request $request)
    {
        if ($denied = $this->denyWithoutAppPermission(['Add Plan Type Master', 'add-plan-type-master', 'Add Master Plan Type', 'add-master-plan-type'])) {
            return $denied;
        }

        $libraryId = auth('library_api')->id();

        $validated = $request->validate([
            'id'            => 'nullable|exists:plan_types,id',
            'branch_id'     => 'required|exists:branches,id',
            'day_type_id'   => 'required|integer',
             'custom_plan_type' => 'required_if:day_type_id,0|string|max:255',
            'start_time'    => 'required|date_format:H:i',
            'end_time'      => 'required|date_format:H:i',
            // 'slot_hours'    => 'required|integer|min:1'
        ]);

        DB::beginTransaction();

        try {

            $branchId = $validated['branch_id'];
            $existsbrnach=Branch::where('id',$branchId)->where('library_id',$libraryId)->exists();
            if(!$existsbrnach){
                throw new \Exception('Branch Not exists');
            }

            $branchRecord = Hour::withoutGlobalScopes()->where('branch_id',$branchId)->first();

            if(!$branchRecord){
                throw new \Exception('Library hours not configured.');
            }

            /* ================= SLOT HOURS VALIDATION ================= */

            $start = Carbon::parse($validated['start_time']);
            $end   = Carbon::parse($validated['end_time']);

            if ($end->lessThanOrEqualTo($start)) {
                $end->addDay();
            }

            $actualHours = $start->diffInHours($end);

            $start = Carbon::parse($request->start_time);
            $end   = Carbon::parse($request->end_time);

            /* Handle overnight shift (example 22:00 → 06:00) */
            if ($end->lessThanOrEqualTo($start)) {
                $end->addDay();
            }

            /* Calculate hours */
            $slot_hours = $start->diffInHours($end);

            if ($actualHours != $slot_hours ) {
                throw new \Exception(
                    "Slot hours must match shift time ({$actualHours} hours)."
                );
            }

            /* ================= TOTAL COVERAGE VALIDATION ================= */

            $minTime = PlanType::where('branch_id', $branchId)
                ->where('id','!=',$request->id)
                ->min('start_time');

            $maxTime = PlanType::where('branch_id', $branchId)
                ->where('id','!=',$request->id)
                ->max('end_time');

            if ($minTime && $maxTime) {

                $globalStart = Carbon::parse($minTime);
                $globalEnd   = Carbon::parse($maxTime);

                if ($globalEnd->lessThanOrEqualTo($globalStart)) {
                    $globalEnd->addDay();
                }

                $newStart = Carbon::parse($validated['start_time']);
                $newEnd   = Carbon::parse($validated['end_time']);

                if ($newEnd->lessThanOrEqualTo($newStart)) {
                    $newEnd->addDay();
                }

                $finalStart = $newStart->lessThan($globalStart) ? $newStart : $globalStart;
                $finalEnd   = $newEnd->greaterThan($globalEnd) ? $newEnd : $globalEnd;

                $totalHours = $finalStart->diffInHours($finalEnd);

                if ($totalHours > $branchRecord->hour) {

                    throw new \Exception(
                        'You can’t add shift timings outside the library’s hours.'
                    );
                }
            }

            /* ================= PREVENT UPDATE IF LEARNER USING SHIFT ================= */

            if ($request->id) {

                $exists = LearnerDetail::where('plan_type_id', $request->id)
                    ->where('status',1)
                    ->exists();

                if ($exists) {
                    throw new \Exception(
                        'You can not update this shift because learners are already using it.'
                    );
                }
            }

            /* VIP / RESERVED VALIDATION */

            if (in_array($request->day_type_id, [10, 11])) {

                if ($request->slot_hours != $branchRecord->hour) {

                    $shiftName = $request->day_type_id == 11 ? 'VIP' : 'Reserved';

                    return response()->json([
                        'error' => true,
                        'message' => "{$shiftName} shift timing must match library hours ({$branchRecord->hour} hours)."
                    ]);
                }
            }

            /* ================= SHIFT NAME ================= */

            $planTypeName = match ((int)$validated['day_type_id']) {
                1 => 'Full Day',
                2 => 'First Half',
                3 => 'Second Half',
                8 => 'All Day',
                9 => 'Full Night',
                10 => 'Reserved',
                11 => 'VIP',
                0 => $validated['custom_plan_type'],
                default => 'Custom'
            };

            $data = [
                'library_id'  => $libraryId,
                'branch_id'   => $branchId,
                'day_type_id' => $validated['day_type_id'],
                'name'        => $planTypeName,
                'start_time'  => $validated['start_time'],
                'end_time'    => $validated['end_time'],
                'slot_hours'  => $slot_hours ,
                'image'       => 'public/img/booked.png'
            ];

            /* ================= CREATE OR UPDATE ================= */

            if ($request->id) {

                $planType = PlanType::withoutGlobalScopes()->where('id',$request->id)
                    ->where('branch_id',$branchId)
                    ->first();
                if (!$planType) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Plan Type not found for this branch'
                    ], 200);
                }
                $planType->update($data);

                $message = "Shift updated successfully";

            } else {

                $planType = PlanType::create($data);

                $message = "Shift created successfully";
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message'=> $message,
                // 'data'   => $planType
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status'=> false,
                'message'=> $e->getMessage()
            ],500);
        }
    }

    public function planTypelist(Request $request)
    {
        $libraryId = auth('library_api')->id();

        $validated = $request->validate([
            'branch_id' => [
                'required',
                Rule::exists('branches', 'id')->where(function ($q) use ($libraryId) {
                    $q->where('library_id', $libraryId);
                })
            ],
        ]);

        $branchId = $validated['branch_id'];

        // ✅ Query 1: Fetch plan types
        $types = PlanType::withoutGlobalScopes()
            ->where('branch_id', $branchId)
            ->select(
                'id',
                'name',
                'start_time',
                'end_time',
                'slot_hours',
                'day_type_id',
                'deleted_at'
            )
            ->get();

        // ✅ Query 2: Get used plan_type_ids
        $planTypeIds = $types->pluck('id')->toArray();

        $usedPlanTypes = LearnerDetail::whereIn('plan_type_id', $planTypeIds)
            ->pluck('plan_type_id')
            ->flip(); // 🔥 O(1) lookup

        // ✅ Fast loop (no map, no extra memory)
        foreach ($types as $type) {

            $type->can_delete = !isset($usedPlanTypes[$type->id]); // ⚡ fast lookup
            $type->status     = $type->deleted_at ? 'Inactive' : 'Active';
        }

        return response()->json([
            'status'  => true,
            'message' => "Plan Type fetch successfully",
            'data'    => [
                'operatingHour' => operatingHour($branchId),
                'planTypes'     => $types
            ]
        ]);
    }
    public function planTypeStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:plan_types,id',
        ]);

        // ✅ Get PlanType (including soft deleted)
        $planType = PlanType::withoutGlobalScopes()->withTrashed()
            ->where('id', $request->id)
            ->where('library_id', authLibraryId())
            ->first();

        if (!$planType) {
            return response()->json([
                'status' => false,
                'message' => 'Plan Type not found'
            ], 200);
        }

        // ✅ Toggle Logic
        if (!$planType->trashed()) {
            // Deactivate (Soft Delete)
            $planType->delete();
            $message = 'Plan Type deactivated successfully';
        } else {
            // Activate (Restore)
            $planType->restore();
            $message = 'Plan Type activated successfully';
        }

        return response()->json([
            'status'  => true,
            'message' => $message
        ]);
    }
    public function deletePlanType(Request $request)
    {
        if ($denied = $this->denyWithoutAppPermission(['Add Plan Type Master', 'add-plan-type-master', 'Add Master Plan Type', 'add-master-plan-type'])) {
            return $denied;
        }

        try {

            $libraryId = authLibraryId();

            // ✅ Validation
            $request->validate([
                'id' => 'required|exists:plan_types,id'
            ]);

            // ✅ Fetch PlanType
            $planType = PlanType::withoutGlobalScopes()
                ->where('id', $request->id)
                ->where('library_id', $libraryId)
                ->first();

            if (!$planType) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Plan Type not found'
                ], 200);
            }

            // ✅ Check usage (FAST)
            $hasLearner = \App\Models\LearnerDetail::where('plan_type_id', $planType->id)->exists();

            if ($hasLearner) {
                // ❌ In use → Soft delete
                $planType->delete();
                $message = 'Plan Type in use, moved to inactive (soft deleted)';
            } else {
                // ✅ Not used → Permanent delete
                $planType->forceDelete();
                $message = 'Plan Type permanently deleted';
            }

            return response()->json([
                'status'  => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'status'  => false,
                'message' => 'Something went wrong',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function planPriceEdit(Request $request)
    {
        $libraryId = auth('library_api')->id();
        
        $validated = $request->validate([
            'id' => [
                'required',
                Rule::exists('plan_prices','id')->where(function($q) use ($libraryId){
                    $q->where('library_id',$libraryId);
                })
            ],
        ]);

        // ✅ Single query (optimized)
        $price = PlanPrice::withoutGlobalScopes()
            ->join('plans', 'plans.id', '=', 'plan_prices.plan_id')
            ->join('plan_types', 'plan_types.id', '=', 'plan_prices.plan_type_id')
            ->where('plan_prices.id', $validated['id'])
            ->select(
                'plan_prices.id',
                'plan_prices.branch_id',
                'plan_prices.price',
                'plan_prices.deleted_at', // ✅ required
                'plans.id as plan_id',
                'plans.name as plan_name',
                'plan_types.id as plan_type_id',
                'plan_types.name as plan_type_name'
            )
            ->first();

        if (!$price) {
            return response()->json([
                'status'  => false,
                'message' => 'Price not found',
                'data'    => []
            ], 200);
        }

        // ✅ Add fields (no extra query)
        $price->can_delete = ! $this->isPlanPriceUsed(
            $libraryId,
            (int) $price->branch_id,
            (int) $price->plan_id,
            (int) $price->plan_type_id
        );
        $price->status     = $price->deleted_at ? 'Inactive' : 'Active';

        return response()->json([
            'status'  => true,
            'message' => "Price fetch successfully",
            'data'    => $price
        ]);
    }

    public function priceStore(Request $request)
    {
        if ($denied = $this->denyWithoutAppPermission(['Add Plan Price Master', 'add-plan-price-master', 'Add Master Plan Price', 'add-master-plan-price'])) {
            return $denied;
        }

        $libraryId = auth('library_api')->id();
        $branchId  = $request->branch_id;

        $validated = $request->validate([
            'id' => 'nullable|exists:plan_prices,id',

            'branch_id' => 'required|exists:branches,id',

            'plan_id' => [
                'required',
                Rule::exists('plans','id')->where(function ($q) use ($libraryId) {
                    $q->where('library_id',$libraryId);
                })
            ],

            'plan_type_id' => [
                'required',
                Rule::exists('plan_types','id')->where(function ($q) use ($branchId) {
                    $q->where('branch_id',$branchId);
                })
            ],

            'price' => 'required|numeric|min:0'
        ]);


        /* -------------------------
        DUPLICATE CHECK
        --------------------------*/

        $duplicate = PlanPrice::where('library_id',$libraryId)
            ->where('branch_id',$branchId)
            ->where('plan_id',$validated['plan_id'])
            ->where('plan_type_id',$validated['plan_type_id'])
            ->when($validated['id'] ?? null,function($q) use ($validated){
                $q->where('id','!=',$validated['id']);
            })
            ->exists();

        if($duplicate){
            return response()->json([
                'status'=>false,
                'message'=>'Plan price already exists for this plan and shift.'
            ]);
        }


        /* -------------------------
        CREATE OR UPDATE
        --------------------------*/

        $data = [
            'library_id'=>$libraryId,
            'branch_id'=>$branchId,
            'plan_id'=>$validated['plan_id'],
            'plan_type_id'=>$validated['plan_type_id'],
            'price'=>$validated['price']
        ];

        if(isset($validated['id'])){

            $planPrice = PlanPrice::findOrFail($validated['id']);
            $planPrice->update($data);

            return response()->json([
                'status'=>true,
                'message'=>'Plan price updated successfully.'
            ]);

        }else{

            PlanPrice::create($data);

            return response()->json([
                'status'=>true,
                'message'=>'Plan price created successfully.'
            ]);
        }
    }
    public function pricelist(Request $request)
    {
        $libraryId = auth('library_api')->id();

        $validated = $request->validate([
            'branch_id' => [
                'required',
                Rule::exists('branches', 'id')->where(function ($q) use ($libraryId) {
                    $q->where('library_id', $libraryId);
                })
            ],
        ]);

        $branchId = $validated['branch_id'];

       

        // ✅ Fetch plan prices (include deleted_at)
        $price = PlanPrice::withoutGlobalScopes()
            ->join('plans', 'plans.id', '=', 'plan_prices.plan_id')
            ->join('plan_types', 'plan_types.id', '=', 'plan_prices.plan_type_id')
            ->where('plan_prices.branch_id', $branchId)
            ->select(
                'plan_prices.id',
                'plan_prices.price',
                'plan_prices.deleted_at',
                'plans.id as plan_id',
                'plans.name as plan_name',
                'plan_types.id as plan_type_id',
                'plan_types.name as plan_type_name'
            )
            ->orderBy('plans.id')
            ->get();

        // ✅ Fast loop (no extra queries)
        $usedPriceKeys = LearnerDetail::withoutGlobalScopes()
            ->where('library_id', $libraryId)
            ->where('branch_id', $branchId)
            ->whereIn('plan_id', $price->pluck('plan_id')->unique()->values()->all())
            ->whereIn('plan_type_id', $price->pluck('plan_type_id')->unique()->values()->all())
            ->select('plan_id', 'plan_type_id')
            ->distinct()
            ->get()
            ->mapWithKeys(fn ($item) => [$item->plan_id.':'.$item->plan_type_id => true]);

        foreach ($price as $row) {
            $row->can_delete = !isset($usedPriceKeys[$row->plan_id.':'.$row->plan_type_id]);
            $row->status     = $row->deleted_at ? 'Inactive' : 'Active';
        }

        return response()->json([
            'status'  => true,
            'message' => "Price fetch successfully",
            'data'    => $price
        ]);
    }
    public function deletePlanPrice(Request $request)
    {
        if ($denied = $this->denyWithoutAppPermission(['Add Plan Price Master', 'add-plan-price-master', 'Add Master Plan Price', 'add-master-plan-price'])) {
            return $denied;
        }

         $libraryId = auth('library_api')->id();
        $request->validate([
            'id' => 'required|exists:plan_prices,id',
        ]);

        $planPrice = PlanPrice::withoutGlobalScopes()
            ->where('id', $request->id)
            ->where('library_id', $libraryId)
            ->first();

        if (!$planPrice) {
            return response()->json([
                'status' => false,
                'message' => 'Plan Price not found'
            ]);
        }

        if ($this->isPlanPriceUsed(
            $libraryId,
            (int) $planPrice->branch_id,
            (int) $planPrice->plan_id,
            (int) $planPrice->plan_type_id
        )) {
            return response()->json([
                'status' => false,
                'message' => 'Plan Price is used by learners and cannot be deleted'
            ]);
        }

        $planPrice->forceDelete();

        return response()->json([
            'status'  => true,
            'message' => 'Plan Price deleted successfully'
        ]);
    }

    public function planPriceStatus(Request $request)
    {
         $libraryId = auth('library_api')->id();
        $request->validate([
            'id' => 'required|exists:plan_prices,id',
        ]);

        $planPrice = PlanPrice::withTrashed()
            ->where('id', $request->id)
            ->where('library_id', $libraryId)
            ->first();

        if (!$planPrice) {
            return response()->json([
                'status' => false,
                'message' => 'Plan Price not found'
            ], 200);
        }

        // ✅ Toggle
        if (!$planPrice->trashed()) {
            $planPrice->delete();
            $message = 'Plan Price deactivated successfully';
        } else {
            $planPrice->restore();
            $message = 'Plan Price activated successfully';
        }

        return response()->json([
            'status'  => true,
            'message' => $message
        ]);
    }

    public function saveLibraryUser(Request $request)
    {
        if ($denied = $this->denyWithoutAppPermission(['Add User Master', 'add-user-master'])) {
            return $denied;
        }

        $libraryId = authLibraryId();

        $validated = $request->validate([
            'id' => [
                'nullable',
                Rule::exists('library_users', 'id')->where(fn($q) => 
                    $q->where('library_id', $libraryId)
                )
            ],
            'name' => 'required|string|max:255',
            'email' => [
                'required','email',
                Rule::unique('library_users','email')->ignore($request->id)
            ],
            'password' => $request->id ? 'nullable|min:6' : 'required|min:6',
            'mobile' => 'required|digits:10',

            'branch' => 'required|array|min:1',
            'branch.*' => [
                'integer',
                Rule::exists('branches','id')->where(fn($q) => 
                    $q->where('library_id',$libraryId)
                )
            ],

            'role_id' => 'required|int|exists:roles,id',
            'library_user_image' => 'nullable|string',
        ],[
            'branch.required' => 'Please select at least one branch.',
            'branch.min' => 'Please select at least one branch.',
        ]);

        // ✅ Find user if update
        $user = null;
        if (!empty($validated['id'])) {
            $user = LibraryUser::where('id', $validated['id'])
                ->where('library_id', $libraryId)
                ->first();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid user for this library'
                ], 404);
            }
        }

        DB::beginTransaction();

        try {

            /* ======================
            BASIC DATA
            ====================== */
            $data = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'mobile' => $validated['mobile'],
                'library_id' => $libraryId,
                'branch_id' => array_map('strval', $validated['branch']),
            ];

            $selectedBranches = array_map('intval', $validated['branch']);
            $currentBranch = (int) ($user->current_branch ?? 0);

            if (!$user || $currentBranch <= 0 || !in_array($currentBranch, $selectedBranches, true)) {
                $data['current_branch'] = $selectedBranches[0] ?? null;
            }

            /* ======================
            PASSWORD
            ====================== */
            if (!empty($validated['password'])) {
                $data['password'] = bcrypt($validated['password']);
            }

           /* ======================
            IMAGE HANDLING (DIRECT DEBUG VERSION)
            ====================== */
            $profilePath = null;

            // if ($validated['library_user_image'] === '' || is_null($validated['library_user_image'])) {

            // } else
            if (!empty($validated['library_user_image'])) {

                $service = new LibraryConfigurationService();

                $profilePath = $service->moveTempFileToPublic(
                    $validated['library_user_image'],
                    'user_profile_picture',
                    'upload/user_profile_picture'
                );

                if ($profilePath !== null) {
                    $data['profile_picture'] = $profilePath;
                }
            }else{
                $data['profile_picture'] = null;
            }

          
            if ($user) {
                $user->update($data);
            } else {
                $user = LibraryUser::create($data);
            }

            if (!$user) {
                throw new \Exception('User creation failed');
            }

            /* ======================
            ASSIGN ROLE
            ====================== */
            $role = Role::where('id', $validated['role_id'])
                ->where('guard_name', 'library_user')
                ->firstOrFail();

            $user->syncRoles([$role->name]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Library user saved successfully.',
                // 'data' => [
                //     'id' => $user->id,
                //     'name' => $user->name,
                //     'email' => $user->email,
                //     'mobile' => $user->mobile,
                //     'profile_picture' => $user->profile_picture
                // ]
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function editLibraryUser(Request $request)
    {
        
        $libraryId = auth('library_api')->id();
        $request->validate([
            'library_user_id' => 'required|exists:library_users,id'
        ]);


        $user = LibraryUser::where('id', $request->library_user_id)
            ->where('library_id', $libraryId)
            ->select( 'id','name','email','mobile','branch_id','profile_picture')
            ->first();
          
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ],200);
        }
        $branchIds = $user->branch_id ?? [];

         $branches = Branch::whereIn('id', $branchIds)
        ->pluck('name','id')
        ->map(function ($name, $id) {
            return [
                'id' => $id,
                'name' => $name
            ];
        })
        ->values();


        // User role
        $role = $user->roles()->first();
          

        return response()->json([
            'status' => true,
            'message' => 'User detail fetched successfully',
            'data' => [
                 'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'mobile' => $user->mobile,
                 'role' => $role->name ?? '',
                'role_id' => $role->id ?? '',
                 'branches' => $branches,
                'can_delete' =>true,
                'status' => $user->status ? 'Active' : 'Inactive',
                'library_user_image' =>  !empty($user->profile_picture) ? asset('public/'.$user->profile_picture) : '',
            ]
        ]);
    }

    public function libraryUserList()
    {
        $libraryId = auth('library_api')->id();

        $users = LibraryUser::where('library_id', $libraryId)
            ->with('roles:id,name')
            ->select('id',
                'name','email','mobile','branch_id','status','profile_picture'
            )
            ->latest()
            ->get();

        $users->transform(function ($user) {

            // Branch names
            $branchIds = $user->branch_id ?? [];

             $branches = Branch::whereIn('id', $branchIds)
                ->pluck('name', 'id')
                ->map(function ($name, $id) {
                    return [
                        'id' => $id,
                        'name' => $name
                    ];
                })
                ->values();
            $role = $user->roles->first();

            return [
                'id' => $user->id,
                'name' => $user->name,
                'mobile' => $user->mobile ?? '',
                'email' => $user->email ?? '',
                'role' => $role->name ?? '',
                'role_id' => $role->id ?? '',
                'branches' => $branches,
                'status' => $user->status ? 'Active' : 'Inactive',
                'can_delete'=>true,
                'library_user_image' =>  !empty($user->profile_picture) ? asset('public/'.$user->profile_picture) : '',
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'User list fetched successfully',
            'data' => $users
        ]);
    }

     public function libraryPermissions(Request $request)
    {
        $library = auth('library_api')->user();

        $subscription = Subscription::find($library->library_type);

        $result = [];

        // ✅ get user permissions (if user_id passed)
        $userPermissionIds = [];

        if (!empty($request->library_user_id)) {

            $user = LibraryUser::find($request->library_user_id);

            if ($user) {
                // assuming spatie
                $userPermissionIds = $user->getAllPermissions()->pluck('id')->toArray();
            }
        }

        if ($subscription) {

            $permissions = $subscription->permissions()
                ->leftJoin('permission_categories', 'permission_categories.id', '=', 'permissions.permission_category_id')
                ->select(
                    'permissions.id',
                    'permissions.name',
                    'permission_categories.name as category_name'
                )
                ->get();

            $grouped = $permissions->groupBy('category_name');

            $result = $grouped->map(function ($items, $category) use ($userPermissionIds) {

                return [
                    'category' => $category,
                    'display_name' => ucfirst($category) . ' Permissions',
                    'permissions' => $items->map(function ($permission) use ($userPermissionIds) {

                        return [
                            'id' => $permission->id,
                            'name' => $permission->name,

                            // ✅ MAIN LOGIC
                            'is_selected' => in_array($permission->id, $userPermissionIds)
                        ];
                    })->values()
                ];

            })->values();
        }

        return response()->json([
            'status'  => true,
            'message' => 'Permissions fetched successfully',
            'data'    => $result
        ]);
    }

    public function assignPermissions(Request $request)
    {
        if ($denied = $this->denyWithoutAppPermission(['Add User Master', 'add-user-master'])) {
            return $denied;
        }

        $libraryId = auth('library_api')->id();

        $validated = $request->validate([
            'user_id' => [
                'required',
                Rule::exists('library_users','id')->where(function($q) use ($libraryId){
                    $q->where('library_id',$libraryId);
                })
            ],

            'permissions' => 'required|array',

            'permissions.*' => 'exists:permissions,id'
        ]);
        

        $user = LibraryUser::findOrFail($validated['user_id']);

        /* Sync permissions */
        $user->permissions()->sync($validated['permissions']); 
     
       
        return response()->json([
            'status'=>true,
            'message'=>'Permissions applied successfully',
            
           
        ]);
    }

    public function deleteLibraryUser(Request $request)
    {
        if ($denied = $this->denyWithoutAppPermission(['Add User Master', 'add-user-master'])) {
            return $denied;
        }

        $libraryId = auth('library_api')->id();

        // ✅ Validate input
        $validated = $request->validate([
            'id' => [
                'required',
                Rule::exists('library_users', 'id')->where(fn($q) =>
                    $q->where('library_id', $libraryId)
                )
            ]
        ]);

        // ✅ Fetch user
        $user = LibraryUser::where('id', $validated['id'])
            ->where('library_id', $libraryId)
            ->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ], 404);
        }

        /* ======================
        DELETE PROFILE IMAGE (OPTIONAL 🔥)
        ====================== */
        if (!empty($user->profile_picture)) {

            $imagePath = public_path($user->profile_picture);

            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }

        // ✅ Delete user
        $user->delete();

        return response()->json([
            'status' => true,
            'message' => 'User deleted successfully'
        ]);
    }

    public function libraryUserStatus(Request $request)
    {
        if ($denied = $this->denyWithoutAppPermission(['Add User Master', 'add-user-master'])) {
            return $denied;
        }

        $libraryId = auth('library_api')->id();

        // ✅ Validation
        $validated = $request->validate([
            'id' => [
                'required',
                Rule::exists('library_users', 'id')->where(fn($q) =>
                    $q->where('library_id', $libraryId)
                )
            ]
        ]);

        // ✅ Get User
        $user = LibraryUser::where('id', $validated['id'])
            ->where('library_id', $libraryId)
            ->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ], 404);
        }

        // ✅ Toggle Status (assuming 1 = active, 0 = inactive)
        $user->status = $user->status == 1 ? 0 : 1;
        $user->save();

        return response()->json([
            'status'  => true,
            'message' => $user->status ? 'User activated successfully' : 'User deactivated successfully',
            'data'    => [
                'id'     => $user->id,
                'status' => $user->status
            ]
        ]);
    }
    

    public function branchStatus(Request $request)
    {
        if ($denied = $this->denyWithoutAppPermission(['Edit Branch', 'edit-branch', 'Add Branch Master', 'add-branch-master'])) {
            return $denied;
        }

        $request->validate([
            'branch_id' => 'required|exists:branches,id'
        ]);

        $branch = Branch::findOrFail($request->branch_id);

        $branch->status = !$branch->status;
        $branch->save();

        return response()->json([
            'status' => true,
            'message' => 'Status updated successfully'
        ]);
    }

    public function branchDestroy(Request $request)
    {
        if ($denied = $this->denyWithoutAppPermission(['Edit Branch', 'edit-branch', 'Add Branch Master', 'add-branch-master'])) {
            return $denied;
        }

       
        $id = $request->id;

        $branch = Branch::find($id);
        if (!$branch) {
            return response()->json([
                'status' => false,
                'message' => 'Branch not found'
            ]);
        }

        // ✅ Check learners exist
        $hasLearners = LearnerDetail::withoutGlobalScopes()->where('branch_id', $id)->exists();

        if ($hasLearners) {
            return response()->json([
                'status' => false,
                'message' => 'Cannot delete branch. Learners are assigned.'
            ]);
        }

        // ✅ Check shifts / plan types
        $hasShifts = PlanType::withoutGlobalScopes()->where('branch_id', $id)->exists();

        if ($hasShifts && $hasLearners) {
            return response()->json([
                'status' => false,
                'message' => 'Cannot delete branch. Shifts are configured.'
            ]);
        }

        // ✅ Check hours
        $hasHours =Hour::withoutGlobalScopes()->where('branch_id', $id)->exists();

        if ($hasHours && $hasLearners) {
            return response()->json([
                'status' => false,
                'message' => 'Cannot delete branch. Hours data exists.'
            ]);
        }

        // ✅ OPTIONAL: delete images from storage
        if (!empty($branch->library_images)) {

            $images = is_array($branch->library_images)
                ? $branch->library_images
                : json_decode($branch->library_images, true);

            foreach ($images as $img) {
                Storage::disk('public')->delete($img);
            }
        }

        // ✅ delete logo
        if (!empty($branch->library_logo) && !str_contains($branch->library_logo, 'uploads/')) {
            Storage::disk('public')->delete($branch->library_logo);
        }

        // ✅ delete hour record(s) for this branch
        Hour::withoutGlobalScopes()->where('branch_id', $id)->delete();

        // ✅ delete shifts (plan types) for this branch, but only the ones with no
        // learner attached (active or historical) so learner history keeps its shift name.
        $shifts = PlanType::withoutGlobalScopes()->where('branch_id', $id)->get();
        foreach ($shifts as $shift) {
            $hasShiftLearners = LearnerDetail::withoutGlobalScopes()
                ->withTrashed()
                ->where('plan_type_id', $shift->id)
                ->exists();

            if (!$hasShiftLearners) {
                $shift->delete();
            }
        }

        // ✅ finally delete branch
        $branch->delete();

        return response()->json([
            'status' => true,
            'message' => 'Deleted successfully'
        ]);
    }

    public function rolesList(Request $request)
    {
        try {

            // ✅ Execute query
            $roles = DB::table('roles')->where('guard_name','library_user')
                ->select('id', 'name', 'guard_name')
                ->orderBy('id', 'DESC')
                ->get();

            // ✅ Format response
            $data = $roles->map(function ($role) {
                return [
                    'id'         => $role->id,
                    'name'       => $role->name,
                    'guard_name' => $role->guard_name,
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Roles fetched successfully',
                'data' => [
                    'roles' => $data
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

      public function examlist(Request $request)
    {
        $libraryId = auth('library_api')->id();

        $exams = Exam::select('id','name','deleted_at')
            ->get()
            ->map(function ($exam) {

                // ✅ Check if used in learners table
                $isUsed =LearnerDetail::where('exam_id', $exam->id)->exists();

                return [
                    'id'         => $exam->id,
                    'name'       => $exam->name,

                    'status'     => is_null($exam->deleted_at) ? 'Active' : 'Inactive',

                    'can_delete' => !$isUsed
                ];
            });

        return response()->json([
            'status'  => true,
            'message' => "Exam fetch successfully",
            'data'    => $exams
        ]);
    }
    public function examStore(Request $request)
    {
        $libraryId = auth('library_api')->id(); // keep if needed, else remove

        $validated = $request->validate([

            'id' => [
                'nullable',
                Rule::exists('exams', 'id') // remove where if no library_id
            ],

            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('exams', 'name')
                    ->where(function ($query) {
                        return $query->whereNull('deleted_at'); // ✅ soft delete safe
                    })
                    ->ignore($request->id)
            ],
        ]);

        DB::beginTransaction();

        try {

            /* =========================
            DATA PREPARE
            ========================= */
            $data = [
                'name' => $validated['name'],
            ];

            /* =========================
            CREATE / UPDATE
            ========================= */

            if (!empty($validated['id'])) {

                $exam = Exam::findOrFail($validated['id']);
                $exam->update($data);

                $message = "Exam updated successfully";

            } else {

                // 🔥 Optional: restore if already soft deleted
                $existing = Exam::withTrashed()
                    ->where('name', $validated['name'])
                    ->first();

                if ($existing && $existing->trashed()) {
                    $existing->restore();
                    DB::commit();

                    return response()->json([
                        'status'  => true,
                        'message' => 'Exam restored successfully',
                        'data'    => $existing
                    ]);
                }

                $exam = Exam::create($data);
                $message = "Exam created successfully";
            }

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => $message,
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function examedit(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:exams,id'
        ]);

        $exam = Exam::select('id','name')
            ->findOrFail($request->id);

        return response()->json([
            'status'  => true,
            'message' => 'Exam fetched successfully',
            'data'    => $exam
        ]);
    }

   public function examdelete(Request $request)
    {
        $libraryId = auth('library_api')->id(); // keep if needed

        $validated = $request->validate([
            'id' => [
                'required',
                Rule::exists('exams', 'id')
                // 👉 add this only if exams are library आधारित
                // ->where(fn($q) => $q->where('library_id', $libraryId))
            ]
        ]);

        DB::beginTransaction();

        try {

            $exam = Exam::findOrFail($validated['id']);

            /* =========================
            CHECK DEPENDENCY (Learners)
            ========================= */
            $isUsed = LearnerDetail::where('exam_id', $exam->id)->exists();

            if ($isUsed) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Exam cannot be deleted, already assigned to learners'
                ]);
            }

            /* =========================
            SOFT DELETE
            ========================= */
            $exam->delete();

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Exam deleted successfully'
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function expenseList(Request $request)
    {
        $data = DB::table('expenses as e')
            ->leftJoin('monthly_expense as me', 'me.expense_id', '=', 'e.id')
            ->select(
                'e.id',
                'e.name',
                'e.deleted_at',
                DB::raw('COUNT(me.id) as used_count')
            )
            ->groupBy('e.id', 'e.name', 'e.deleted_at')
            ->get()
            ->map(function ($item) {

                return [
                    'id'         => $item->id,
                    'name'       => $item->name,
                    'status'     => is_null($item->deleted_at) ? 'Active' : 'Inactive',
                    'can_delete' => $item->used_count == 0
                ];
            });

        return response()->json([
            'status'  => true,
            'message' => 'Expense list fetched successfully',
            'data'    => $data
        ]);
    }
    

    public function expenseStore(Request $request)
    {
        if ($denied = $this->denyWithoutAppPermission(['Add Expense Master', 'add-expense-master', 'Add Expense', 'add-expense'])) {
            return $denied;
        }

        $validated = $request->validate([

            'id' => [
                'nullable',
                Rule::exists('expenses', 'id') // ⚠️ confirm table
            ],

            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('expenses', 'name')
                    ->where(fn($q) => $q->whereNull('deleted_at'))
                    ->ignore($request->id)
            ],
        ]);

        DB::beginTransaction();

        try {

            $data = [
                'name' => trim($validated['name'])
            ];

            /* =========================
            CREATE / UPDATE
            ========================= */

            if (!empty($validated['id'])) {

                $item = DB::table('expenses')
                    ->where('id', $validated['id'])
                    ->update($data);

                $message = "Expense updated successfully";

            } else {

                // 🔥 restore if soft deleted
                $existing = DB::table('expenses')
                    ->where('name', $validated['name'])
                    ->whereNotNull('deleted_at')
                    ->first();

                if ($existing) {

                    DB::table('expenses')
                        ->where('id', $existing->id)
                        ->update(['deleted_at' => null]);

                    DB::commit();

                    return response()->json([
                        'status'  => true,
                        'message' => 'Expense restored successfully'
                    ]);
                }

                DB::table('expenses')->insert($data);

                $message = "Expense created successfully";
            }

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function expenseDetail(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:expenses,id'
        ]);

        $data = DB::table('expenses')
            ->select('id','name')
            ->where('id', $request->id)
            ->first();

        return response()->json([
            'status'  => true,
            'message' => 'Expense detail fetched successfully',
            'data'    => $data
        ]);
    }

    public function expenseDelete(Request $request)
    {
        if ($denied = $this->denyWithoutAppPermission(['Add Expense Master', 'add-expense-master', 'Add Expense', 'add-expense'])) {
            return $denied;
        }

        $request->validate([
            'id' => 'required|exists:expenses,id'
        ]);

        DB::beginTransaction();

        try {

            // ✅ check usage
            $isUsed = DB::table('monthly_expense')
                ->where('expense_id', $request->id)
                ->exists();

            if ($isUsed) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Expense cannot be deleted, already used'
                ]);
            }

            // ✅ soft delete
            DB::table('expenses')
                ->where('id', $request->id)
                ->update(['deleted_at' => now()]);

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Expense deleted successfully'
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function getSeat(Request $request, SeatAvailabilityService $seatAvailabilityService)
    {
        // $validated = $request->validate([
        //     'branch_id' => 'required|exists:branches,id',
        // ]);

        $branch_id = getCurrentBranch();

        $totalSeats = Hour::withoutGlobalScopes()
            ->where('branch_id', $branch_id)
            ->value('seats');

        $availablePlanTypesBySeat = $seatAvailabilityService->getAvailablePlanTypesMap((int) $branch_id, (int) $totalSeats);

        $futureSeats = LearnerDetail::withoutGlobalScopes()
            ->where('branch_id', $branch_id)
            ->whereNotNull('seat_no')
            ->whereDate('plan_start_date', '>', now()->toDateString())
            ->whereNull('deleted_at')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('learner_detail as previous_detail')
                    ->whereColumn('previous_detail.learner_id', 'learner_detail.learner_id')
                    ->whereColumn('previous_detail.id', '!=', 'learner_detail.id')
                    ->whereNull('previous_detail.deleted_at');
            })
            ->pluck('seat_no')
            ->map(fn ($seatNo) => (int) $seatNo)
            ->unique()
            ->flip();

        $allSeats = collect(generateSeatNumbers());

        $newAvailableSeat = collect();

        for ($seatNo = 1; $seatNo <= $totalSeats; $seatNo++) {
            $availablePlanTypes = $availablePlanTypesBySeat[$seatNo] ?? collect();

            if ($availablePlanTypes->isNotEmpty()) {
                $seatInfo = $allSeats->firstWhere('main', $seatNo);
                $isFuture = $futureSeats->has((int) $seatNo);

                $seatInfo = $seatInfo ?? [
                    'original_seat' => $seatNo,
                    'display' => (string) $seatNo,
                ];

                $displaySeatNo = $seatInfo['floor'] ?? $seatNo;
                $seatInfo['display'] = 'Seat No - '.$displaySeatNo;
                $seatInfo['is_future'] = $isFuture;
                $seatInfo['is_future_text'] = $isFuture ? 'future booked' : '';

                $newAvailableSeat->push($seatInfo);
            }
        }

        $search = trim((string) $request->input('search', ''));

        if ($search !== '') {
            $newAvailableSeat = $newAvailableSeat->filter(function ($seat) use ($search) {
                if (is_numeric($search)) {
                    $seatNo = (int) $search;

                    return (int) ($seat['main'] ?? 0) === $seatNo
                        || (int) ($seat['floor'] ?? 0) === $seatNo
                        || (int) ($seat['original_seat'] ?? 0) === $seatNo;
                }

                $needle = strtolower($search);

                return str_contains(strtolower((string) ($seat['display'] ?? '')), $needle)
                    || str_contains(strtolower((string) ($seat['floor_name'] ?? '')), $needle);
            });
        }
        

        return response()->json([
            'status' => true,
            'message' => 'Available seats fetched successfully',
            'data' => $newAvailableSeat->values(),

        ]);
    }

    // public function getSeat(Request $request)
    // {
    //     // $validated = $request->validate([
    //     //     'branch_id' => 'required|exists:branches,id',
    //     // ]);

    //     $branch_id =getCurrentBranch();

    //     $totalSeats = Hour::withoutGlobalScopes()
    //         ->where('branch_id', $branch_id)
    //         ->value('seats');

    //     $totalHour = Hour::withoutGlobalScopes()
    //         ->where('branch_id', $branch_id)
    //         ->value('hour');

    //     $usedSeats = LearnerDetail::withoutGlobalScopes()
    //         ->select('seat_no', DB::raw('SUM(hour) as used_hours'))
    //         ->where('branch_id', $branch_id)
    //         ->whereNotNull('seat_no')
    //         ->where('status', 1)
    //         ->groupBy('seat_no')
    //         ->pluck('used_hours', 'seat_no');

    //     $futureSeats = LearnerDetail::withoutGlobalScopes()
    //         ->where('branch_id', $branch_id)
    //         ->whereNotNull('seat_no')
    //         ->whereDate('plan_start_date', '>', now()->toDateString())
    //         ->whereNull('deleted_at')
    //         ->whereNotExists(function ($query) {
    //             $query->select(DB::raw(1))
    //                 ->from('learner_detail as previous_detail')
    //                 ->whereColumn('previous_detail.learner_id', 'learner_detail.learner_id')
    //                 ->whereColumn('previous_detail.id', '!=', 'learner_detail.id')
    //                 ->whereNull('previous_detail.deleted_at');
    //         })
    //         ->pluck('seat_no')
    //         ->map(fn ($seatNo) => (int) $seatNo)
    //         ->unique()
    //         ->flip();

    //     $allSeats = collect(generateSeatNumbers());
    
    //     $newAvailableSeat = collect();

    //     for ($seatNo = 1; $seatNo <= $totalSeats; $seatNo++) {
    //         $usedHours = $usedSeats[$seatNo] ?? 0;

    //         if ($usedHours < $totalHour) {
    //             $seatInfo = $allSeats->firstWhere('main', $seatNo);
    //             $isFuture = $futureSeats->has((int) $seatNo);

    //             $seatInfo = $seatInfo ?? [
    //                 'original_seat' => $seatNo,
    //                 'display' => (string) $seatNo,
    //             ];

    //             $displaySeatNo = $seatInfo['floor'] ?? $seatNo;
    //             $seatInfo['display'] = 'Seat No - ' . $displaySeatNo;
    //             $seatInfo['is_future'] = $isFuture;
    //             $seatInfo['is_future_text'] = $isFuture ? 'future booked' : '';

    //             $newAvailableSeat->push($seatInfo);
    //         }
    //     }

    //     $search = trim((string) $request->input('search', ''));

    //     if ($search !== '') {
    //         $newAvailableSeat = $newAvailableSeat->filter(function ($seat) use ($search) {
    //             if (is_numeric($search)) {
    //                 $seatNo = (int) $search;

    //                 return (int) ($seat['main'] ?? 0) === $seatNo
    //                     || (int) ($seat['floor'] ?? 0) === $seatNo
    //                     || (int) ($seat['original_seat'] ?? 0) === $seatNo;
    //             }

    //             $needle = strtolower($search);

    //             return str_contains(strtolower((string) ($seat['display'] ?? '')), $needle)
    //                 || str_contains(strtolower((string) ($seat['floor_name'] ?? '')), $needle);
    //         });
    //     }
        

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Available seats fetched successfully',
    //         'data' => $newAvailableSeat->values(),
            
    //     ]);
    // }

    private function isPlanPriceUsed(int $libraryId, ?int $branchId, int $planId, int $planTypeId): bool
    {
        return LearnerDetail::withoutGlobalScopes()
            ->where('library_id', $libraryId)
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->where('plan_id', $planId)
            ->where('plan_type_id', $planTypeId)
            ->exists();
    }

    // ✅ Show toggle-feature list (current hide/show state)
    public function toggleFeatureList(Request $request)
    {
        if ($denied = $this->denyWithoutAppPermission('Manage Feature Toggles')) {
            return $denied;
        }

        return response()->json([
            'status' => true,
            'message' => 'Toggle features fetched successfully',
            'data' => $this->buildToggleFeatureData(),
        ]);
    }

    // ✅ Update toggle-feature hide/show state (bulk save, same as web toggle switches)
    public function updateToggleFeature(Request $request)
    {
        if ($denied = $this->denyWithoutAppPermission('Manage Feature Toggles')) {
            return $denied;
        }

        $validated = $request->validate([
            'hidden_ids' => 'nullable|array',
            'hidden_ids.*' => 'integer|exists:toggle_features,id',
        ]);

        $branch = Branch::where('id', getCurrentBranch())->first();

        if (!$branch) {
            return response()->json([
                'status' => false,
                'message' => 'Branch not found',
            ], 404);
        }

        $branch->hide_field = !empty($validated['hidden_ids']) ? json_encode(array_values($validated['hidden_ids'])) : null;
        $branch->save();

        return response()->json([
            'status' => true,
            'message' => 'Updated successfully',
            'data' => $this->buildToggleFeatureData(),
        ]);
    }

    // Common builder reused by both the list (show) and update APIs above.
    private function buildToggleFeatureData(): array
    {
        $hiddenIds = toggleHideField() ?? [];

        $features = DB::table('toggle_features')->orderBy('category')->orderBy('id')->get();

        $categories = $features->groupBy('category')->values()->map(function ($items, $index) use ($hiddenIds) {
            return [
                'id' => $index + 1,
                'title' => $items->first()->category,
                'display_order' => $index + 1,
                'features' => $items->map(function ($item) use ($hiddenIds) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'description' => $item->description,
                        'is_hidden' => in_array($item->id, $hiddenIds),
                    ];
                })->values(),
            ];
        })->values();

        return [
            'screen_title' => 'Show / Hide Features',
            'search_placeholder' => 'Search Feature',
            'hidden_ids' => array_values($hiddenIds),
            'categories' => $categories,
        ];
    }
}
