<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Floor;
use App\Models\Hour;
use App\Models\LearnerDetail;
use App\Models\LibraryUser;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\PlanType;
use App\Models\Scopes\LibraryScope;
use App\Models\Subscription;
use App\Services\PlanService;
use Illuminate\Http\Request;
use DB;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;

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
            ['value' => '30', 'label' => '30 Days'],
            ['value' => '28', 'label' => '28 Days'],
            ['value' => '', 'label' => 'Caleder wise'],
        ];
         $features = DB::table('features')->whereNull('deleted_at')->select('id','name','image')->get();
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
        $validated = $request->validate([
            'plan_id'        => 'required|exists:plans,id',
            'plan_type_id'   => 'required|exists:plan_types,id',
            'plan_start_date'=> 'nullable|date',
            'branch_id'      => 'required|exists:branches,id',

            'locker_amount'  => 'nullable|numeric',
            'discount_type'  => 'nullable|in:percentage,amount',
            'discount_value' => 'nullable|numeric',
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

    public function floorStore(Request $request)
    {
        $validated = $request->validate([
            'id' => [
                'nullable',
                Rule::exists('floors', 'id')->where(function ($query) use ($request) {
                    return $query->where('branch_id', $request->branch_id);
                })
            ],
            'branch_id' => 'required|exists:branches,id',
            'floor_no'  => 'required|integer|min:1',
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
                ],422);
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
                'data'    => $floor
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

        $floor=Floor::where('branch_id',$request->branch_id)->select('name','floor_no','from_seat','to_seat','total_seats')->get();
         return response()->json([
                'status'  => true,
                'message' =>"Floor fetch successfully",
                'data'    => $floor
            ]);
    }

    public function planEdit(Request $request){
        $libraryId = auth('library_api')->id();
        
        $validated = $request->validate([
            'plan_id' => [
                'required',
                Rule::exists('plans','id')->where(function($q) use ($libraryId){
                    $q->where('library_id',$libraryId);
                })
            ],
           
        ]);

        $plan=Plan::where('id',$validated['plan_id'])->select('id','name','monthdays','type')->first();
         return response()->json([
                'status'  => true,
                'message' =>"Plan fetch successfully",
                'data'    => $plan
            ]);

    }

    public function planStore(Request $request)
    {
        $libraryId = auth('library_api')->id();

        $validated = $request->validate([
            'id' => [
                'nullable',
                Rule::exists('plans','id')->where(function($q) use ($libraryId){
                    $q->where('library_id',$libraryId);
                })
            ],

            'type' => 'required|in:MONTH,YEAR,DAY,WEEK',

            'plan_id' => [
                'required',
                'integer',
                'min:1',
                Rule::unique('plans')
                    ->where('library_id',$libraryId)
                    ->where('type',$request->type)
                    ->ignore($request->id)
            ],

            'monthdays' => 'nullable|in:28,30'
        ]);

        DB::beginTransaction();

        try{

            /* =========================
            PLAN NAME
            ========================= */

            $name = $validated['plan_id'].' '.$validated['type'];

            $data = [
                'library_id' => $libraryId,
                'plan_id'    => $validated['plan_id'],
                'type'       => $validated['type'],
                'name'       => $name,
                'monthdays'  => $validated['type'] == 'MONTH'
                                ? ($validated['monthdays'] ?? null)
                                : null
            ];

            /* =========================
            CREATE / UPDATE
            ========================= */

            if($request->id){

                $plan = Plan::findOrFail($request->id);
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
                'data'   => $plan
            ]);

        }catch(\Exception $e){

            DB::rollBack();

            return response()->json([
                'status' => false,
                'message'=> $e->getMessage()
            ],500);
        }
    }

    public function planlist(Request $request){
        $libraryId = auth('library_api')->id();
         

        $plan=Plan::where('library_id',$request->library_id)->select('id','name','monthdays','type')->get();
         return response()->json([
                'status'  => true,
                'message' =>"Plan fetch successfully",
                'data'    => $plan
            ]);
    }

    public function planTypeEdit(Request $request){
        $libraryId = auth('library_api')->id();
        
        $validated = $request->validate([
            'plantype_id' => [
                'required',
                Rule::exists('plan_types','id')->where(function($q) use ($libraryId){
                    $q->where('library_id',$libraryId);
                })
            ],
           
        ]);

        $plan=PlanType::where('id',$validated['plantype_id'])->select('id','name','start_time','end_time','slot_hours','day_type_id','image')->first();
         return response()->json([
                'status'  => true,
                'message' =>"Plan Type fetch successfully",
                'data'    => $plan
            ]);

    }

    public function plantypeStore(Request $request)
    {
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

                $planType = PlanType::where('id',$request->id)
                    ->where('branch_id',$branchId)
                    ->firstOrFail();

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
                'data'   => $planType
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status'=> false,
                'message'=> $e->getMessage()
            ],500);
        }
    }

    public function planTypelist(Request $request){
         $validated = $request->validate([
           
            'branch_id' => 'required|exists:branches,id',
           
        ]);
        $libraryId = auth('library_api')->id();
        $branchId = $validated['branch_id'];
        $existsbrnach=Branch::where('id',$branchId)->where('library_id',$libraryId)->exists();
        if(!$existsbrnach){
            throw new \Exception('Branch Not exists');
        }

        $types=PlanType::where('branch_id',$request->branch_id)->select('id','name','start_time','end_time','slot_hours','day_type_id','image')->get();
         return response()->json([
                'status'  => true,
                'message' =>"Plan Type fetch successfully",
                'data'    => $types
            ]);
    }

    public function planPriceEdit(Request $request){
        $libraryId = auth('library_api')->id();
        
        $validated = $request->validate([
            'planprice_id' => [
                'required',
                Rule::exists('plan_prices','id')->where(function($q) use ($libraryId){
                    $q->where('library_id',$libraryId);
                })
            ],
           
        ]);

        $price=PlanPrice::withoutGlobalScopes()->join('plans', 'plans.id', '=', 'plan_prices.plan_id')
            ->join('plan_types', 'plan_types.id', '=', 'plan_prices.plan_type_id')
            ->where('plan_prices.id',$validated['planprice_id'])
            ->select(
                'plan_prices.id',
                'plan_prices.price',
                'plans.id as plan_id',
                'plans.name as plan_name',
                'plan_types.id as plan_type_id',
                'plan_types.name as plan_type_name'
            )
            ->orderBy('plans.id')->first();
            return response()->json([
                'status'  => true,
                  'message' => "Price fetch successfully",
                'data'    => $price
            ]);

    }

    public function priceStore(Request $request)
    {
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
        $validated = $request->validate([
            'branch_id' => 'required|exists:branches,id',
        ]);

        $libraryId = auth('library_api')->id();
        $branchId  = $validated['branch_id'];

        /* Check branch belongs to library */

        $existsBranch = Branch::where('id', $branchId)
            ->where('library_id', $libraryId)
            ->exists();

        if (!$existsBranch) {
            throw new \Exception('Branch not exists');
        }

        /* Fetch plan prices */
        $price = PlanPrice::withoutGlobalScopes()
            ->join('plans', 'plans.id', '=', 'plan_prices.plan_id')
            ->join('plan_types', 'plan_types.id', '=', 'plan_prices.plan_type_id')
            ->where('plan_prices.branch_id', $branchId)
            ->select(
                'plan_prices.id',
                'plan_prices.price',
                'plans.id as plan_id',
                'plans.name as plan_name',
                'plan_types.id as plan_type_id',
                'plan_types.name as plan_type_name'
            )
            ->orderBy('plans.id')
            ->get();

        return response()->json([
            'status'  => true,
            'message' => "Price fetch successfully",
            'data'    => $price
        ]);
    }

    public function libraryPermissions(Request $request)
    {
        $library = auth('library_api')->user();

        $subscription = Subscription::find($library->library_type);

        $result = [];

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

            $result = $grouped->map(function ($items, $category) {

                return [
                    'category' => $category,
                    'display_name' => ucfirst($category) . ' Permissions',
                    'permissions' => $items->map(function ($permission) {
                        return [
                            'id' => $permission->id,
                            'name' => $permission->name,
                            'is_selected' => false // 👈 default OR dynamic
                        ];
                    })->values()
                ];

            })->values(); // महत्वपूर्ण (important)
        }

        return response()->json([
            'status'  => true,
            'message' => 'Permissions fetched successfully',
            'data'    => $result
        ]);
    }

    public function saveLibraryUser(Request $request)
    {
        $libraryId = auth('library_api')->id();
    

        $validated = $request->validate([
            'id' => 'nullable|exists:library_users,id',

            'name' => 'required|string|max:255',

            'email' => [
                'required',
                'email',
                Rule::unique('library_users','email')->ignore($request->id)
            ],

            'password' => $request->id
                ? 'nullable|min:6|confirmed'
                : 'required|min:6|confirmed',

            'mobile' => 'required|digits:10',

            'branch' => 'required|array|min:1',

            'branch.*' => [
                'integer',
                Rule::exists('branches','id')->where(function($q) use ($libraryId){
                    $q->where('library_id',$libraryId);
                })
            ],

            'role_id' => 'required|int|exists:roles,id',

            'profile_picture' => 'nullable|image|mimes:jpg,jpeg,png|max:1024',
        ],[
            'branch.required' => 'Please select at least one branch.',
            'branch.min' => 'Please select at least one branch.',
        ]);

        if($request->id){

            $userCheck = LibraryUser::where('id',$request->id)
                ->where('library_id',$libraryId)
                ->exists();

            if(!$userCheck){
                throw new \Exception('User not found');
            }

        }

       
        DB::beginTransaction();

        try {

            $data = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'mobile' => $validated['mobile'],
                'library_id' => $libraryId,
                'branch_id' => $validated['branch']
            ];

            if ($request->filled('branch')) {

                // convert values to string so DB stores ["32","33"]
                $data['branch_id'] = array_map('strval', $request->branch);

            }

            /* Password */

            if(!empty($validated['password'])){
                $data['password'] = bcrypt($validated['password']);
                $data['original_password'] = $validated['password'];
            }

            /* Profile picture */

            if ($request->hasFile('profile_picture')) {

                $data['profile_picture'] = $request
                    ->file('profile_picture')
                    ->store('user_profile_picture','public');
            }

          

            /* Create or Update */

            $user = LibraryUser::updateOrCreate(
                ['id'=>$validated['id'] ?? null],
                $data
            );

            if(!$user){
                throw new \Exception('User creation failed');
            }

            /* Assign Role */
            $role = Role::where('id', $request->role_id)
                ->where('guard_name', 'library_user')
                ->firstOrFail();
              

            $user->syncRoles([$role->name]);


            DB::commit();

            return response()->json([
                'status'=>true,
                'message'=>'Library user saved successfully.',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'mobile' => $user->mobile,
                ]
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status'=>false,
                'message'=>$e->getMessage()
            ],500);
        }
    }

    public function editLibraryUser($id)
    {
        
        $libraryId = auth('library_api')->id();

        $user = LibraryUser::where('id', $id)
            ->where('library_id', $libraryId)
            ->select( 'id','name','email','mobile','branch_id')
            ->first();
          
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ],200);
        }

        // Branch ids (stored as json)
       $branch = Branch::whereIn('id',$user->branch_id)->select('id','name')->get();

        // User role
        $role = $user->roles()->select('id','name')->first();


        return response()->json([
            'status' => true,
            'message' => 'User detail fetched successfully',
            'data' => [
                 'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'mobile' => $user->mobile,
                'role_id' => $role->id ?? null,
                'branch_ids' => $user->branch_id,
                //optional
                'role' => $role,
                'branch'=>$branch
            ]
        ]);
    }

    public function libraryUserList()
    {
        $libraryId = auth('library_api')->id();

        $users = LibraryUser::where('library_id', $libraryId)
            ->with('roles:id,name')
            ->select('id',
                'name','email','mobile','branch_id','status'
            )
            ->latest()
            ->get();

        $users->transform(function ($user) {

            // Branch names
            $branchIds = $user->branch_id ?? [];

            $branches = Branch::whereIn('id', $branchIds)
                ->pluck('name');

            return [
                'id' => $user->id,
                'name' => $user->name,
                'mobile' => $user->mobile,
                'email' => $user->email,
                'role' => optional($user->roles->first())->name,
                'branches' => $branches,
                'status' => $user->status ? 'Active' : 'Inactive',
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'User list fetched successfully',
            'data' => $users
        ]);
    }

    public function assignPermissions(Request $request)
    {
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

    public function deleteLibraryUser($id)
    {
        $libraryId = auth('library_api')->id();

        $user = LibraryUser::where('id',$id)
            ->where('library_id',$libraryId)
            ->first();

        if(!$user){
            return response()->json([
                'status'=>false,
                'message'=>'User not found'
            ],404);
        }

        $user->delete();

        return response()->json([
            'status'=>true,
            'message'=>'User deleted successfully'
        ]);
    }

         /*
    |--------------------------------------------------------------------------
    | Branch List API
    |--------------------------------------------------------------------------
    */

   public function branches()
   {
      $library = auth('library_api')->user();
       $libraryId = authLibraryId();
      $branches = Branch::where('library_id', authLibraryId())->withCount('learners')->with(['state','city'])
        ->select('id', 'name','mobile','email', 'library_address','library_zip', 'status','state_id','city_id')
        ->get() 
        ->map(function ($branch) {

            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'mobile' => $branch->mobile,
                'email' => $branch->email,
                'address' => $branch->library_address ?? '',
                'state' => $branch->state->state_name ?? '',
                'city' => $branch->city->city_name ?? '',
                'zip_code' => $branch->library_zip ?? '',
                'status' => $branch->status == 1 ? 'Active' : 'Deactive',

                // 🔥 main logic
                'can_delete' => $branch->learners_count == 0 ? true : false
            ];
        });


        return response()->json([
            'status' => true,
            'data' => $branches
        ]);
   }
    
}
