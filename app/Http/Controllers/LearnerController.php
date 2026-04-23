<?php

namespace App\Http\Controllers;

use App\DTO\LearnerOperationDTO;
use App\Models\Attendance;
use App\Models\Hour;
use App\Models\Learner;
use App\Models\LearnerDetail;
use App\Models\LearnerTransaction;
use App\Models\Plan;
use App\Models\Library;
use App\Models\Blog;
use App\Models\Branch;
use App\Models\PlanPrice;
use App\Models\PlanType;
use App\Models\Seat;
use App\Models\Suggestion;
use App\Models\LearnerFeedback;
use App\Models\Complaint;
use App\Models\Floor;
use App\Models\LearnerTransactionActivity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use DB;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use App\Services\LearnerService;
use Exception;
use App\Traits\LearnerQueryTrait;
use Illuminate\Support\Facades\Auth;
use Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;
use App\Http\Controllers\NotificationSentController;
use App\Http\Requests\LearnerOperationRequest;
use App\Http\Requests\StoreLearnerRequest;
use App\Models\Category;
use App\Services\LearnerOperationService;
use App\Services\LibraryService;
use App\Services\PlanService;
use App\Services\LearnerGiftDaysService;
use App\Services\LearnerLifecycleService;
use App\Services\LearnerSeatSwapService;
use App\Services\SeatAvailabilityService;


class LearnerController extends Controller
{
    use LearnerQueryTrait;
    protected $learnerService;

    public function __construct(
        LearnerService $learnerService,
        protected LearnerLifecycleService $learnerLifecycleService,
        protected LearnerGiftDaysService $learnerGiftDaysService
    ) {
        $this->learnerService = $learnerService;
    }

    protected function validateCustomer(Request $request, array $additionalRules = [])
    {

        $baseRules = [

            'email' => [
                'nullable',
                'email',
                Rule::unique('learners')->where(function ($query) use ($request) {
                    return $query->where('branch_id', getCurrentBranch());
                }),
            ],
            'name' => 'required',
            'id_proof_file' => 'nullable|file|mimes:jpg,png,jpeg,webp|max:200',
            'profile_picture' => 'nullable|file|mimes:jpg,png,jpeg,webp|max:200',
            'mobile' => 'required|digits:10',
            'dob' => 'nullable|date',
            'father_name' => 'nullable|string|max:255',
            'alternate_mobile' => 'nullable|digits:10',
            'address' => 'nullable|string|max:500',
            'remark' => 'nullable|string|max:1000',
            'exam_id' => 'nullable|exists:exams,id',
            'plan_start_date' => 'nullable|date',
            'plan_id' => 'required',
            'plan_type_id' => 'required',
            'plan_price_id' => 'required|numeric|min:0',

            'discount_amount' => 'nullable|numeric|min:0',
            'locker_amount' => [
                'nullable',
                'required_if:toggleFieldCheckbox,yes',
                'numeric'
            ],

            'locker_no' => [
                'nullable',
                'required_if:toggleFieldCheckbox,yes',
                'numeric'
            ],
            'due_date' => [
                'nullable',
                'date',
                function ($attribute, $value, $fail) use ($request) {
                    $planPrice = (float) $request->input('plan_price_id', 0);
                    $paid = (float) $request->input('paid_amount', 0);
                    $locker = (float) $request->input('locker_amount', 0);
                    $discount = (float) $request->input('discount_amount', 0);

                    $effectivePaid = $planPrice + $locker - $discount;
                    $pending =  $effectivePaid - $paid;

                    if ($pending > 0 && empty($value)) {
                        $fail('Due date is required if there is any pending amount.');
                    }
                }
            ],


        ];


        $rules = array_merge($baseRules, $additionalRules);

        return Validator::make($request->all(), $rules);
    }

    public function dataUpdate()
    {
        $today = Carbon::today();
        $futureCheckDate = $today->copy()->addDays(5);
        $extend_day = getExtendDays();
        // ---- Case 1: Renewed Learners ----
        $renewedLearners = LearnerDetail::select('learner_id')
            ->groupBy('learner_id')
            ->havingRaw('
                SUM(CASE WHEN plan_end_date <= ? THEN 1 ELSE 0 END) > 0 
                AND 
                SUM(CASE WHEN plan_end_date > ? AND status = 0 THEN 1 ELSE 0 END) > 0
            ', [$today->copy()->addDays(5), $today->copy()->addDays(5)])
            ->pluck('learner_id');

        // ---- Case 2: Expired Learners ----
        $expiredLearners = LearnerDetail::whereDate(
            DB::raw("DATE_ADD(plan_end_date, INTERVAL $extend_day DAY)"),
            '<=',
            $today
        )
            ->pluck('learner_id');

        // ---- Case 3: Active Future Booked Learners ----
        $futureLearners = LearnerDetail::where('status', 0)
            ->where('plan_start_date', '<=', $today)
            ->where('plan_end_date', '>', $today)
            ->pluck('learner_id');

        // ---- Merge All Unique Learners ----
        $learnerIds = $renewedLearners
            ->merge($expiredLearners)
            ->merge($futureLearners)
            ->unique();


        $customerdatas = LearnerDetail::whereIn('learner_id', $learnerIds)->get();

        foreach ($customerdatas as $customerdata) {
            $branchId = $customerdata->branch_id;
            $branch = $branchId ? Branch::find($branchId) : null;
            $extend_day = $branch ? $branch->extend_days : 0;

            $planEndDateWithExtension = Carbon::parse($customerdata->plan_end_date)->addDays($extend_day);

            $hasFuturePlan = LearnerDetail::where('learner_id', $customerdata->learner_id)
                ->where('plan_end_date', '>', $futureCheckDate)
                ->where('status', 0)
                ->exists();

            $hasPastPlan = LearnerDetail::where('learner_id', $customerdata->learner_id)
                ->where('plan_end_date', '<', $futureCheckDate)
                ->exists();

            $isRenewed = $hasFuturePlan && $hasPastPlan;

            if ($isRenewed) {
                LearnerDetail::where('learner_id', $customerdata->learner_id)
                    ->where('plan_start_date', '<=', $today)
                    ->where('plan_end_date', '>', $futureCheckDate)
                    ->update(['status' => 1]);

                LearnerDetail::where('learner_id', $customerdata->learner_id)
                    ->where('plan_end_date', '<', $today)
                    ->update(['status' => 0]);
            }
            elseif ($planEndDateWithExtension->lt($today)) {
                Learner::where('id', $customerdata->learner_id)
                    ->where('status', '!=', 0)
                    ->update(['status' => 0]);

                $customerdata->update(['status' => 0]);
            }  else {
                Learner::where('id', $customerdata->learner_id)
                    ->where('status', '!=', 1)
                    ->update(['status' => 1]);

                LearnerDetail::where('learner_id', $customerdata->learner_id)
                    ->where('status', 0)
                    ->where('plan_start_date', '<=', $today)
                    ->where('plan_end_date', '>', $today)
                    ->update(['status' => 1]);
            }
        }
    }

    public function learnerStatusUpdate($learnerIds){
         $customerdatas = LearnerDetail::where('learner_id', $learnerIds)->get();

        foreach ($customerdatas as $customerdata) {
           $today = Carbon::today();
            $futureCheckDate = $today->copy()->addDays(5);
            $extend_day = getExtendDays();

            $planEndDateWithExtension = Carbon::parse($customerdata->plan_end_date)->addDays($extend_day);

            $hasFuturePlan = LearnerDetail::where('learner_id', $customerdata->learner_id)
                ->where('plan_end_date', '>', $futureCheckDate)
                ->where('status', 0)
                ->exists();

            $hasPastPlan = LearnerDetail::where('learner_id', $customerdata->learner_id)
                ->where('plan_end_date', '<', $futureCheckDate)
                ->exists();

            $isRenewed = $hasFuturePlan && $hasPastPlan;

            if ($planEndDateWithExtension->lte($today)) {
                Learner::where('id', $customerdata->learner_id)
                    ->where('status', '!=', 0)
                    ->update(['status' => 0]);

                $customerdata->update(['status' => 0]);
            } elseif ($isRenewed) {
                LearnerDetail::where('learner_id', $customerdata->learner_id)
                    ->where('plan_start_date', '<=', $today)
                    ->where('plan_end_date', '>', $futureCheckDate)
                    ->update(['status' => 1]);

                LearnerDetail::where('learner_id', $customerdata->learner_id)
                    ->where('plan_end_date', '<', $today)
                    ->update(['status' => 0]);
            } else {
                Learner::where('id', $customerdata->learner_id)
                    ->where('status', '!=', 1)
                    ->update(['status' => 1]);

                LearnerDetail::where('learner_id', $customerdata->learner_id)
                    ->where('status', 0)
                    ->where('plan_start_date', '<=', $today)
                    ->where('plan_end_date', '>', $today)
                    ->update(['status' => 1]);
            }
        }
    }

    public function index()
    {
        // $this->dataUpdate();
        $users = $this->getLearnersByLibrary()->where('learners.status', 1)->where('learner_detail.library_id', getLibraryId());

        $count_fullday = $this->getLearnersByLibrary()->leftJoin('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')->where('plan_types.day_type_id', 1)->where('learners.status', 1)->count();
        $count_firstH = $this->getLearnersByLibrary()->leftJoin('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')->where('plan_types.day_type_id', 2)->where('learners.status', 1)->count();
        $count_secondH = $this->getLearnersByLibrary()->leftJoin('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')->where('plan_types.day_type_id', 3)->where('learners.status', 1)->count();
        $count_hourly = $this->getLearnersByLibrary()->leftJoin('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')->whereIn('plan_types.day_type_id', [4, 5, 6, 7])->where('learners.status', 1)->count();

        $not_available = getUnavailableSeatCount();
        $available = getAvailableSeatCount();
        $availableseats = $this->learnerService->getAvailableSeats();
        $floors = Floor::where('branch_id', getCurrentBranch())->orderBy('floor_no')->get();


        return view('learner.seat', compact('availableseats', 'users',  'count_fullday', 'count_firstH', 'count_secondH', 'available', 'not_available', 'count_hourly', 'floors'));
    }
    //learner store seat and without seat
    public function learnerStore(StoreLearnerRequest $request, LearnerService $service)
    {

        try {

            $processData = $request->prepareData();
          
            $result = $service->processLearnerStore($processData);

            if ($request->expectsJson()) {
                return response()->json($result);
            }

            if ($result['success']) {
                return redirect()->route('learners')
                    ->with('success',$result['message']);
            }

            return redirect()->back()
                ->with('error',$result['message']);

        } catch (\Exception $e) {

            \Log::error("Learner Create Error: ".$e->getMessage(),[
                'line'=>$e->getLine(),
                'file'=>$e->getFile()
            ]);

            return response()->json([
                'success'=>false,
                'message'=>'Something went wrong while creating learner!'
            ],500);
        }
    }
    //learner  change plan 
    public function changePlanUpdate(LearnerOperationRequest $request, LearnerOperationService $service)
    {
        if (!Gate::allows('has-permission', 'Change Plan')) {
            return redirect()->back()->with('error', 'You do not have permission to renew the seat.');
        }

     

        $dto = LearnerOperationDTO::fromRequest($request);
       
       
        $result = $service->process($dto);
      

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        if ($result['success']) {
            return redirect()->route('learners')
                ->with('success', $result['message']);
        }

        return redirect()->back()
            ->with('error', $result['message']);
       
    }

     //upgrade form view
    public function getLearner(Request $request, $id = null)
    {
        $routeName = $request->route()->getName();
        $today = Carbon::today()->format('Y-m-d');
        $customerId = $request->id ?? $id;
        $hasFuturePlan = LearnerDetail::where('learner_id', $customerId)
            ->where('plan_end_date', '>', $today)
            ->exists();
        $hasPastPlan = LearnerDetail::where('learner_id', $customerId)
            ->where('plan_end_date', '<=', $today)
            ->exists();
        $isalreadyRenew = LearnerDetail::where('learner_id', $customerId)
            ->where('plan_end_date', '>', $today)
            ->where('status', 0)
            ->exists();

        $is_renew = $hasFuturePlan && $hasPastPlan; // 3rd learner detail condition fail


        $available_seat = $this->learnerService->getAvailableSeats();

        $customer = $this->fetchCustomerData($customerId, $is_renew, $status = 1, $detailStatus = 1, $perPage = 10, $paginate = false);
        $customer_detail = LearnerDetail::where('learner_id', $customerId)->orderBy('id', 'Desc')->first();

        $oneWeekLater = Carbon::parse($customer->plan_start_date)->addWeek();
        $showButton = Carbon::now()->greaterThanOrEqualTo($oneWeekLater);
        $plantype = PlanType::get();
        $otherPlantype = LearnerDetail::where('learner_id', '!=', $customerId)
            ->where('seat_no', $customer_detail->seat_no)
            ->where('status', 1)
            ->pluck('plan_type_id');

        if ($customer_detail->seat_no) {
            $filteredPlanTypes = filterPlantypeFromseat($customer_detail->seat_no, $customerId);
        } else {
            $filteredPlanTypes = PlanType::select('id', 'name')->get();
        }

        $hasLocker = currentTransaction($customer->learner_detail_id)->locker_amount > 0 ? 'yes' : 'no';
        $discountAmount = currentTransaction($customer->learner_detail_id)->discount_amount ?? null;
        $selectedDiscountType = $discountAmount ? 'amount' : '';
        $today = \Carbon\Carbon::now();
        if($hasLocker){
            $locker_amt=currentTransaction($customer->learner_detail_id)->locker_amount;
        }else{
            $locker_amt=0;
        }

        return view('learner.changePlanUpgrade', compact('customer',  'available_seat', 'showButton', 'is_renew', 'filteredPlanTypes', 'isalreadyRenew','hasLocker','discountAmount','selectedDiscountType','today','locker_amt','oneWeekLater'));
    }
   
    //renew and learner  Upgrade
     public function learnerUpgradeRenew(LearnerOperationRequest $request, LearnerOperationService $service)
    {
        
        
        $dto = LearnerOperationDTO::fromRequest($request);
       
       
        $result = $service->process($dto);

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        if ($result['success']) {
            return redirect()->route('learners')
                ->with('success', $result['message']);
        }

        return redirect()->back()
            ->with('error', $result['message']);



    }
    // reactive learner get
    public function reactiveUser(Request $request, $id = null)
    {

        $customerId = $request->id ?? $id;

        $is_renew = $this->learnerService->getRenewalStatus($customerId);
        $available_seat = $this->learnerService->getAvailableSeats();

        $customer = $this->fetchCustomerData($customerId, false, $status = 0, $detailStatus = 0, $perPage = 10, $paginate = false);

        $customer_detail = LearnerDetail::withTrashed()->where('learner_id', $customerId)->orderBy('id', 'Desc')->first();
        
        $hasLocker = currentTransaction($customer->learner_detail_id)->locker_amount > 0 ? 'yes' : 'no';
        $discountAmount = currentTransaction($customer->learner_detail_id)->discount_amount ?? null;
        $selectedDiscountType = $discountAmount ? 'amount' : '';
        $oneWeekLater = \Carbon\Carbon::parse($customer->plan_start_date)->addWeek();
        $today = \Carbon\Carbon::now();
        if($hasLocker){
            $locker_amt=currentTransaction($customer->learner_detail_id)->locker_amount;
        }else{
            $locker_amt=0;
        }

        if ($request->expectsJson() || $request->has('id')) {

            return response()->json($customer);
        } else {

            return view('learner.reactive', compact('customer', 'available_seat','hasLocker','discountAmount','selectedDiscountType','oneWeekLater','today','locker_amt'));
        }
    }

      public function reactiveLearner(LearnerOperationRequest $request, LearnerOperationService $service)
    {
       
  
        
        $dto = LearnerOperationDTO::fromRequest($request);
       
        $result = $service->process($dto);

        if ($request->expectsJson()) {
            return response()->json($result);
        }

        if ($result['success']) {
            return redirect()->route('learners')
                ->with('success', $result['message']);
        }

        return redirect()->back()
            ->with('error', $result['message']);



    }

    public function learnerUpdate(Request $request, $id = null)
    {


        $validator = $this->validateCustomer($request);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $user_id = $id ?: $request->input('user_id');

        $learner = Learner::findOrFail($user_id);

        /*
        |--------------------------------------------------------------------------
        | File Upload
        |--------------------------------------------------------------------------
        */

        if ($request->hasFile('profile_picture')) {

            $request->validate([
                'profile_picture' => 'mimes:webp,png,jpg,jpeg|max:200'
            ]);

            $file = $request->file('profile_picture');

            $name = "profile_picture_" . time() . $file->getClientOriginalName();

            $file->move(public_path('uploade'), $name);

            $learner->profile_picture = 'public/uploade/'.$name;
        }

        if ($request->hasFile('id_proof_file')) {

            $file = $request->file('id_proof_file');

            $name = "id_proof_" . time() . $file->getClientOriginalName();

            $file->move(public_path('uploads'), $name);

            $learner->id_proof_file = 'public/uploads/'.$name;
        }

        /*
        |--------------------------------------------------------------------------
        | Profile Update
        |--------------------------------------------------------------------------
        */

        $learner->name = $request->input('name',$learner->name);
        $learner->email = encryptData($request->input('email',$learner->email));
        $learner->mobile = encryptData($request->input('mobile',$learner->mobile));
        $learner->dob = $request->input('dob',$learner->dob);
        $learner->father_name = $request->input('father_name',$learner->father_name);
        $learner->alternate_mobile = $request->input('alternate_mobile',$learner->alternate_mobile);
        $learner->address = $request->input('address',$learner->address);
        $learner->remark = $request->input('remark',$learner->remark);
        $learner->id_proof_name = $request->input('id_proof_name',$learner->id_proof_name);
        $learner->id_proof_number = $request->input('id_proof_number',$learner->id_proof_number);

        $learner->save();

        /*
        |--------------------------------------------------------------------------
        | Prepare DTO for EDIT operation
        |--------------------------------------------------------------------------
        */

        $dto = LearnerOperationDTO::fromRequest($request);

        $dto->operation = 'EDIT';

        /*
        |--------------------------------------------------------------------------
        | Call Global Operation Service
        |--------------------------------------------------------------------------
        */

        $result = app(LearnerOperationService::class)->process($dto);

        if(!$result['success']){
            return redirect()->back()->with('error',$result['message']);
        }

        if(!empty($result['start_date_blocked'])){
            return redirect()
                ->route('learners')
                ->with('success','Learner updated successfully. Start date could not be updated due to seat availability.');
        }

        return redirect()
            ->route('learners')
            ->with('success','Learner updated successfully');
    }

     //learner  update
    // public function learnerUpdate(Request $request, $id = null)
    // {

    //     $validator = $this->validateCustomer($request);
    //     if ($validator->fails()) {

    //         if ($request->expectsJson()) {
    //             return response()->json([
    //                 'success' => false,
    //                 'errors' => $validator->errors()
    //             ], 422);
    //         } else {
    //             return redirect()->back()->withErrors($validator)->withInput();
    //         }
    //     }
        
       
    //     // Determine user_id based on $id or request input
    //     $user_id = $id ?: $request->input('user_id');


    //     $customer = Learner::findOrFail($user_id);

    //     // Handle the profile picture upload
    //     if ($request->hasFile('profile_picture')) {
    //         $this->validate($request, ['profile_picture' => 'mimes:webp,png,jpg,jpeg|max:200']);
    //         $profile_picture = $request->profile_picture;
    //         $profile_pictureNewName = "profile_picture" . time() . $profile_picture->getClientOriginalName();
    //         $profile_picture->move('public/uploade/', $profile_pictureNewName);
    //         $profile_picturePath = 'public/uploade/' . $profile_pictureNewName;
    //         $customer->profile_picture = $profile_picturePath;
    //     }

    //     // Handle the id proof file upload
    //     if ($request->hasFile('id_proof_file')) {
    //         $id_proof_file = $request->file('id_proof_file');
    //         $id_proof_fileNewName = "id_proof_file_" . time() . "_" . $id_proof_file->getClientOriginalName();

    //         // Store the file in the 'public/uploads' directory
    //         $id_proof_file->move(public_path('uploads'), $id_proof_fileNewName);
    //         $id_proof_filePath = 'public/uploads/' . $id_proof_fileNewName;

    //         // Set the path in the customer model
    //         $customer->id_proof_file = $id_proof_filePath;
    //     }

        
    //     // Update customer details

    //     $customer->name = $request->input('name', $customer->name);
    //     $customer->email = encryptData($request->input('email', $customer->email));
    //     $customer->mobile = encryptData($request->input('mobile', $customer->mobile));
    //     $customer->dob = $request->input('dob', $customer->dob);
    //     $customer->father_name = $request->input('father_name', $customer->father_name);
    //     $customer->alternate_mobile = $request->input('alternate_mobile', $customer->alternate_mobile);
    //     $customer->address = $request->input('address', $customer->address);
    //     $customer->remark = $request->input('remark', $customer->remark);
    //     $customer->id_proof_name = $request->input('id_proof_name', $customer->id_proof_name);

       
    //     // Update exam_id in learner_detail table if provided
    //     $learnerDetail = LearnerDetail::where('learner_id', $customer->id)
    //         ->latest()
    //         ->first();
    //     $branchId = getCurrentBranch();
    //     $startDateBlocked = false;

    //     if ($learnerDetail) {
    //         $updated = false;

    //         // Update exam_id
    //         if ($request->has('exam_id')) {
    //             $learnerDetail->exam_id = $request->input('exam_id');
    //             $updated = true;
    //         }
    //         $alreadyStartDate = LearnerDetail::where('learner_id', $customer->id)
    //             ->whereDate('plan_start_date', $request->plan_start_date)
    //             ->exists();

           

    //         // Start date update attempt
    //         if ($request->filled('plan_start_date') && $request->filled('plan_id') && !$alreadyStartDate) {

          
    //              // Determine hours based on plan_type_id
      
    //             $seat_no = $request->input('seat_no');
    //             $plan_type_id = $request->input('plan_type_id');
    //             $plan_id = $request->plan_id;

    //             $startDate = Carbon::parse($request->input('plan_start_date'));
    //             $endDate   = getEndDate($learnerDetail->plan_id, $startDate);
    //             if ($seat_no) {
    //                 $result = checkSeatAvailability($seat_no, $customer->id, $plan_type_id, $startDate, $endDate);

    //                 if ($result['error']) {
    //                     return redirect()->back()->with('error', $result['message'])->withInput();
    //                 } 
    //             }

    //             $check = checkAvailability(
    //                 $branchId,
    //                 $learnerDetail->seat_no,      // nullable allowed
    //                 $customer->id,
    //                 $learnerDetail->plan_type_id,
    //                 $learnerDetail->plan_id,
    //                 $startDate
    //             );

    //             $extendDay   = getExtendDays();
    //             $inextendDate = Carbon::parse($endDate)->addDays($extendDay);
    //             $currentDate  = date('Y-m-d');

    //             if ($learnerDetail && $learnerDetail->plan_end_date < $currentDate && $endDate->gt($currentDate)) {
    //                 $status = 1;
    //             } elseif ($inextendDate >= Carbon::today() && $startDate <= Carbon::today()) {
    //                 $status = 1;
    //             } else {
    //                 $status = 0;
    //             }
               
    //             if ($check['error'] === false || $result['error'] === false) {
    //                 // ✅ Allowed
    //                 $learnerDetail->plan_start_date = $startDate;
    //                 $learnerDetail->plan_end_date   = $endDate;
    //                 $learnerDetail->status=$status;
    //                 $customer->status=$status;

    //                 $updated = true;
    //             } else {
    //                 $startDateBlocked = true;
    //             }
    //         }
       
    //         if ($updated) {
    //             $learnerDetail->save();
    //         }
    //     }

    //     // Save the customer details
    //     $customer->save();

    //     $learnerTransaction = LearnerTransaction::where('learner_detail_id', $learnerDetail->id)->first();
    //     $planPrice = (float) $request->input('plan_price_id', 0);
    //     $locker = (float) $request->input('locker_amount', 0);
    //     if ($request->discountType == 'amount') {
    //         $discount = $request->discount_amount;
    //     } elseif ($request->discountType == 'percentage') {
    //         $total = $planPrice + $locker;
    //         $discount = ($total * $request->discount_amount) / 100;
    //     } else {
    //         $discount = 0;
    //     }

    //     $effectivePaid = $planPrice + $locker - $discount; 
    //     $old_price      = $learnerTransaction->paid_amount ?? 0;
    //     $pending_amount = $request->input('pending_amount');
    //     $diff_amount    = $request->input('diffrence_amount');
    //     $paid_amount = $old_price + $diff_amount;
    //     $payment_mode=$learnerDetail->payment_mode;
        
    //     if ($payment_mode == 3) {
    //         $pending_amount = $effectivePaid;
    //         $paid_amount    = 0;
    //     }
      
    //     $refund = 0;
    //     $pending_refund = 0;
    //     // Handle difference amount (refund vs pending)
    //     if ($diff_amount < 0) {

    //         // refund case
    //         $refund = abs($diff_amount);
    //         $pending_refund = abs($pending_amount);
    //         $pending_amount = 0;
    //         $dr_cr = 'Dr';
    //     } else {

    //         // extra payment (pending dues)
    //         $pending_amount = $pending_amount ?? 0;
    //         $refund = $diff_amount;
    //         $pending_refund = 0;
    //         $dr_cr = 'Cr';
    //     }
    //     $due_date = $request->due_date ?? null;
        
    //     if (($paid_amount > $effectivePaid) || ($paid_amount == 0 && $payment_mode != 3)) {
    //         return redirect()->back()->with('error', 'Paid amount is not valid');
    //     }

    //     if (($pending_amount > 0) && (empty($due_date)) && $payment_mode != 3) {
    //         return redirect()->back()->with('error', 'Due date is required');
    //     }
        
    //     if ($learnerTransaction) {
    //         if ($request->locker == 'yes') {
    //             $learnerTransaction->locker_amount = $locker;
    //         }

    //         $learnerTransaction->total_amount   = $effectivePaid ?? $paid_amount;
    //         $learnerTransaction->paid_amount    = $paid_amount;
    //         $learnerTransaction->pending_amount = $pending_amount;
    //         $learnerTransaction->refund         = $pending_refund;   // keep refund only if negative diff
    //         $learnerTransaction->due_date       = $due_date ?? null;
    //         $learnerTransaction->discount_amount = $discount;

    //         $learnerTransaction->save();

    //         //learner Activity
    //         if($refund && $refund!=0){
    //             $data = [];
    //             $data['learner_id'] = $customer->id;
    //             $data['particular'] = 'Paid By WEBSITE';
    //             $data['payment_type'] = 'EDIT';
    //             $data['payment_mode'] = 1;
    //             $data['amount'] = $refund ?? 0;
    //             $data['dr_cr'] = $dr_cr;
    //             $this->learnerTransactionActivity($data);
    //         }
    //     }

    //     if ($startDateBlocked) {
    //         return redirect()
    //             ->route('learners')
    //             ->with('success', 'Learner updated successfully. Start date could not be updated due to seat availability.');
    //     }
    //     return redirect()->route('learners')->with('success', 'Learner updated successfully.');
    // }


      // reactive learner store
    // public function reactiveLearner(Request $request, $id, LearnerService $service)
    // {
    //     $rules = [
    //         'plan_id' => 'required',
    //         'seat_no' => 'nullable',
    //         'plan_type_id' => 'required',
    //         'plan_price_id' => 'required',
    //         'plan_start_date' => 'required',
    //         'user_id' => 'required',
    //         'learner_detail' => 'required',
    //         'payment_mode' => 'required',
    //     ];

    //     $validator = Validator::make($request->all(), $rules);

    //     if ($validator->fails()) {
    //         return redirect()->back()->withErrors($validator)->withInput();
    //     }

    //     if (!Gate::allows('has-permission', 'Reactive Seat')) {
    //         return redirect()->back()->with('error', 'You do not have permission.');
    //     }

    //     try {

    //         $old_value = LearnerDetail::withTrashed()
    //             ->where('id', $request->learner_detail)
    //             ->first();

    //         $customer = Learner::withTrashed()
    //             ->findOrFail($request->user_id);

    //         if (LearnerDetail::where('learner_id', $customer->id)
    //             ->where('status', 1)->exists()) {
    //             return redirect()->back()
    //                 ->with('error', 'Your Plan Already Active')
    //                 ->withInput();
    //         }
           

    //         $result = $service->processPlan([
    //             'learner_id' => $customer->id,
    //             'plan_id' => $request->plan_id,
    //             'plan_type_id' => $request->plan_type_id,
    //             'plan_price' => (float) $request->plan_price_id,
    //             'payment_mode' => $request->payment_mode,
    //             'discount_type' => $request->discountType,
    //             'discount_amount' => $request->discount_amount,
    //             'paid_amount' => (float) $request->paid_amount,
    //             'locker_amount' => $request->locker_amount,
    //             'locker_no' => $request->locker_no,
    //             'payment_type' => 'REACTIVE',
    //             'paid_date' => $request->paid_date,
    //             'due_date' => $request->due_date,
    //             'particular' => 'Paid by website',
    //             'branchId' => getCurrentBranch(),
    //             'library_id' => getLibraryId(),
    //             'start_date' => $request->plan_start_date,
    //             'seat_no' => $request->seat_no,
    //             'old_value' => $old_value->seat_no ?? null,
    //         ]);

    //         if ($result['success']) {
    //             return redirect()->route('learners')
    //                 ->with('success', $result['message']);
    //         }

    //         return redirect()->back()
    //             ->with('error', $result['message']);

    //     } catch (\Exception $e) {
    //         return redirect()->back()
    //             ->with('error', 'An error occurred: ' . $e->getMessage());
    //     }
    // }
   
     //  public function changePlanUpdate(Request $request, $id = null)
    // {

    //     $rules = [
    //         'plan_type_id' => 'required|exists:plan_types,id',
    //         'plan_price_id' => 'required',
    //         'paid_amount' => 'required',
    //         'diffrence_amount' => 'required',
    //         'previous_amount' => 'required',
    //         'payment_mode' => 'required',
    //         'user_id' => 'required|exists:learners,id',
    //         'learner_detail' => 'required|exists:learner_detail,id',
    //         'discountType' => 'nullable|in:amount,percentage',
    //         'discount_amount' => [
    //             'required_if:discountType,amount,percentage',

    //         ],
    //         'locker_no' => [
    //             'nullable',
    //             'required_if:locker,yes',
    //             'numeric'
    //         ],
    //         'locker_amount' => [
    //             'nullable',
    //             'required_if:locker,yes'
    //         ],
    //     ];

    //     $validator = Validator::make($request->all(), $rules);
    //     if ($validator->fails()) {
    //         return redirect()->back()->withErrors($validator)->withInput();
    //     }

    //     $customer = Learner::findOrFail($request->user_id);
    //     if (!$customer) {
    //         return redirect()->back()->with('error', 'Total available hours not set.');
    //     }


    //     if (!Gate::allows('has-permission', 'Change Plan')) {
    //         return redirect()->back()->with('error', 'You do not have permission to renew the seat.');
    //     }
    //     DB::beginTransaction();

    //     try {

    //         $LearnerDetail = LearnerDetail::where('id', $request->learner_detail)->first();

    //         //check plan type hours based on plan_type_id
    //         $seat_no = $customer->seat_no;
    //         $plan_type_id = $request->input('plan_type_id');
    //         $planType = PlanType::find($plan_type_id);
    //         $startTime = $planType->start_time;
    //         $endTime = $planType->end_time;
    //         $hours = $planType->slot_hours;
    //         $plan_id = $request->plan_id;

    //         $start_date = Carbon::parse($LearnerDetail->plan_start_date);
    //         if ($plan_id) {
    //             $planData = Plan::where('id', $plan_id)
    //                 ->select('plan_id', 'type', 'monthdays')
    //                 ->first();

    //             $duration  = $planData->plan_id ?? 0;
    //             $type      = $planData->type;
    //             $monthdays = $planData->monthdays;
    //             switch (strtoupper($type)) {
    //                 case 'DAY':
    //                     $endDate = $start_date->copy()->addDays($duration);
    //                     break;
    //                 case 'WEEK':
    //                     $endDate = $start_date->copy()->addWeeks($duration);
    //                     break;
    //                 case 'MONTH':
    //                     if (!empty($monthdays)) {
    //                         // Use exact number of days defined for this month plan
    //                         $endDate = $start_date->copy()->addDays($monthdays - 1);
    //                     } else {
    //                         // Fallback to month-wise duration
    //                         $endDate = $start_date->copy()->addMonths($duration);
    //                     }
    //                     break;
    //                 case 'YEAR':
    //                     $endDate = $start_date->copy()->addYears($duration);
    //                     break;
    //                 default:
    //                     $endDate = $start_date;
    //                     break;
    //             }
    //         } else {
    //             $endDate = $LearnerDetail->plan_end_date;
    //         }


    //         // Calculate end date

    //         if ($seat_no) {
    //             // Fetch existing bookings for the same seat
    //             $existingBookings = $this->getLearnersByLibrary()->where('learner_detail.seat_no', $seat_no)
    //                 ->where('learners.id', '!=', $customer->id) // Exclude the current booking
    //                 ->where('learner_detail.status', 1)
    //                 ->get();
    //             // Check for overlaps with existing bookings
    //             foreach ($existingBookings as $booking) {
    //                 $bookingPlanType = PlanType::find($booking->plan_type_id);

    //                 if ($bookingPlanType) {
    //                     $bookingStartTime = $bookingPlanType->start_time;
    //                     $bookingEndTime = $bookingPlanType->end_time;

    //                     if (
    //                         ($startTime < $bookingEndTime && $endTime > $bookingStartTime) ||
    //                         ($endTime > $bookingStartTime && $startTime < $bookingEndTime)
    //                     ) {
    //                         return redirect()->back()->with('error', 'The selected plan type overlaps with an existing booking.');
    //                     }
    //                 }
    //             }

    //             $first_record = Hour::first();
    //             $total_hour = $first_record ? $first_record->hour : 0;

    //             if ($total_hour === 0) {
    //                 return redirect()->back()->with('error', 'Total available hours not set.');
    //             }

    //             $total_cust_hour = Learner::where('seat_no', $seat_no)->where('status', 1)->sum('hours');
    //             // Check if the selected plan type exceeds available hours
    //             if ($hours > ($total_hour - ($total_cust_hour - $customer->hours))) {
    //                 return redirect()->back()->with('error', 'You cannot select this plan type as it exceeds the available hours.');
    //             }
    //             if ($this->getLearnersByLibrary()->where('learners.seat_no', $seat_no)->where('learner_detail.plan_start_date', '>', Carbon::today())->exists()) {
    //                 if ($this->checkPlanTypeSeatWise($seat_no, $plan_type_id) == false) {
    //                     return ['error' => true, 'message' => 'This plan conflicts with a future booking.'];
    //                 }
    //             }
    //         }



    //         $customer->hours = $hours;
    //         $customer->save();
    //         if ($request->payment_type) {
    //             $payment_type = $request->payment_type;
    //         } else {
    //             $payment_type = 'UPGRADE';
    //         }
    //         $planPrice = (float) $request->input('plan_price_id', 0);
    //         $locker = $request->input('locker_amount');

    //         if ($request->discountType == 'amount') {
    //             $discount = $request->discount_amount;
    //         } elseif ($request->discountType == 'percentage') {
    //             $total = $request->input('plan_price_id') + $locker;
    //             $discount = ($total * $request->discount_amount) / 100;
    //         } else {
    //             $discount = 0;
    //         }


    //         $learnerTransaction = LearnerTransaction::where('learner_detail_id', $request->learner_detail)->first();
    //         $paid_amount = (float) $request->input('paid_amount', 0);
    //         $effectivePaid = $planPrice + $locker - $discount; //new price
    //         $old_price      = $learnerTransaction->paid_amount ?? 0;
    //         $pending_amount = $request->input('pending_amount');
    //         $diff_amount    = $request->input('diffrence_amount');
    //         $payment_mode = $request->payment_mode;
    //         if ($payment_mode == 3) {
    //             $pending_amount = $paid_amount;
    //             $paid_amount    = 0;
    //         }
    //         $refund = 0;
    //         $pending_refund = 0;

    //         // Handle difference amount (refund vs pending)
    //         if ($diff_amount < 0) {

    //             // refund case
    //             $refund = abs($diff_amount);
    //             $pending_refund = abs($pending_amount);
    //             $pending_amount = 0;
    //             $dr_cr = 'Dr';
    //         } else {

    //             // extra payment (pending dues)
    //             $pending_amount = $pending_amount ?? 0;
    //             $refund = $diff_amount;
    //             $pending_refund = 0;
    //             $dr_cr = 'Cr';
    //         }

    //         $due_date = $request->due_date ?? ($learnerTransaction->due_date ?? null);

    //         if (($pending_amount > 0 || $pending_refund != 0) && empty($due_date) && !$payment_mode) {
    //             return redirect()->back()->with('error', 'Due date is required');
    //         }
    //         if ($pending_amount < 0) {

    //             return redirect()->back()->with('error', 'Paid amount is not valid');
    //         }

    //         if ($learnerTransaction) {
    //             if ($request->locker == 'yes') {
    //                 $learnerTransaction->locker_amount = $locker;
    //             }

    //             $learnerTransaction->total_amount   = $effectivePaid ?? $paid_amount;
    //             $learnerTransaction->paid_amount    = $paid_amount;
    //             $learnerTransaction->pending_amount = $pending_amount;
    //             $learnerTransaction->refund         = $pending_refund;   // keep refund only if negative diff
    //             $learnerTransaction->due_date       = $due_date ?? null;
    //             $learnerTransaction->discount_amount = $discount;

    //             $learnerTransaction->save();

    //             //learner Activity
    //             $data = [];
    //             $data['learner_id'] = $customer->id;
    //             $data['particular'] = 'Paid By Trans';
    //             $data['payment_type'] = 'CHANGE PLAN';
    //             $data['payment_mode'] = 1;
    //             $data['amount'] = $refund ?? 0;
    //             $data['dr_cr'] = $dr_cr;
    //             $this->learnerTransactionActivity($data);
    //         }


    //         if ($LearnerDetail) {
    //             if ($request->input('plan_type_id')) {
    //                 $LearnerDetail->plan_id = $plan_id;
    //             }
    //             $LearnerDetail->plan_end_date = $endDate;
    //             $LearnerDetail->plan_type_id = $plan_type_id;
    //             $LearnerDetail->plan_price_id = $planPrice;
    //             $LearnerDetail->payment_mode = $payment_mode;
    //             $LearnerDetail->hour = $hours;
    //             $LearnerDetail->save();
    //         }
    //         if ($data['payment_type'] == 'CHANGE PLAN') {

    //             try {

    //                 $noti = new NotificationSentController;

    //                 // WABA Notification
    //                 if (autowabaNotificationActive()) {
    //                     \Log::info('autowabaNotificationActive');
    //                     $noti->autoMessage($data['learner_id'], 'waba', 'change-plan-waba');
    //                 }

    //                 // TEXT Notification
    //                 if (autotextNotificationActive()) {
    //                     \Log::info('autotextNotificationActive');
    //                     $noti->autoMessage($data['learner_id'], 'text', 'change-plan-sms');
    //                 }
    //             } catch (\Throwable $e) {

    //                 // Log the error (won't break your main code)
    //                 \Log::error('Notification sending failed: ' . $e->getMessage(), [

    //                     'exception' => $e
    //                 ]);
    //             }
    //         }

    //         if ($data['payment_type'] == 'UPGRADE') {

    //             try {

    //                 $noti = new NotificationSentController;

    //                 // WABA Notification
    //                 if (autowabaNotificationActive()) {
    //                     \Log::info('autowabaNotificationActive');
    //                     $noti->autoMessage($data['learner_id'], 'waba', 'upgrade-waba');
    //                 }

    //                 // TEXT Notification
    //                 if (autotextNotificationActive()) {
    //                     \Log::info('autotextNotificationActive');
    //                     $noti->autoMessage($data['learner_id'], 'text', 'upgrade-sms');
    //                 }
    //             } catch (\Throwable $e) {

    //                 // Log the error (won't break your main code)
    //                 \Log::error('Notification sending failed: ' . $e->getMessage(), [

    //                     'exception' => $e
    //                 ]);
    //             }
    //         }


    //         $this->dataUpdate();
    //         DB::commit();
    //         if ($request->expectsJson()) {
    //             return response()->json([
    //                 'success' => true,
    //                 'message' => 'Learner updated successfully!',
    //             ], 200);
    //         } else {
    //             return redirect()->route('learners')->with('success', 'Learner updated successfully.');
    //         }
    //     } catch (\Exception $e) {
    //         DB::rollBack(); // Something went wrong, rollback

    //         return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
    //     }
    // }
    // 
    // payment store
    public function paymentStore(Request $request)
    {
        $this->validate($request, [
            'learner_id'   => 'required|exists:learners,id',
            'paid_amount'  => 'required|numeric|min:1',
            'payment_mode' => 'required',
            'learner_transaction_id' => 'required',
        ]);

        $tranDetail = LearnerTransaction::find($request->learner_transaction_id);

        if (!$tranDetail) {
            return redirect()->route('learners')->with('error', 'Transaction not found.');
        }
        $due_date = null;

        // ✅ Call reusable function
        $activityData = [
            'learner_id' => $tranDetail->learner_id,
            'learner_transaction_id' => $tranDetail->id,
            'particular'   => 'Pay later',
            'payment_type' => 'SEAT ASSIGNMENT',
            'payment_mode' => $request->payment_mode,
            'amount'       => $request->paid_amount,
            'dr_cr'        => 'Cr',
        ];
        $tranDetail->update([

            'is_paid'        => 1,
            'paid_date'      => now()->format('Y-m-d'),

        ]);
        $this->learnerTransactionActivity($activityData);


        return redirect()->route('learners')->with('success', 'Payment successfully recorded.');
    }
   
    // custome validation
    private function validateLearnerCustom($plan_id, $plan_type_id, $start_date, $planPrice, $paid_amount, $locker, $discount, $seat_no, $due_date, $payment_mode, $learner_detail_id)
    {
        $planData = Plan::where('id', $plan_id)->select('plan_id', 'type', 'monthdays')->first();
        $duration = $planData->plan_id;
        $type     = $planData->type;
        $monthdays = $planData->monthdays;

        $effectivePaid   = $planPrice + $locker - $discount;
        $pending_amount  = $effectivePaid - $paid_amount;
        $total_hour      = Hour::first()?->hour ?? 0;

        if ($total_hour === 0) {
            return ['error' => true, 'message' => 'Total available hours not set.'];
        }

        $hours = PlanType::where('id', $plan_type_id)->value('slot_hours') ?? 0;
        $planType = PlanType::find($plan_type_id);
        $startTime = $planType->start_time ?? null;
        $endTime   = $planType->end_time ?? null;

        $learner_detail = $learner_detail_id
            ? LearnerDetail::find($learner_detail_id)
            : null;
        $endDate =getEndDate($plan_id, $start_date);
       

        $branchId=getCurrentBranch();
        // 🔹 Seat overlap checks
        if (!empty($seat_no)) {
            $learnerId = !empty($learner_detail->learner_id)
                ? (int) $learner_detail->learner_id
                : null;
            $check = checkAvailability($branchId,$seat_no,$learnerId,
                $plan_type_id,$plan_id,$start_date
            );

            // 🔴 STOP immediately if seat is not available
            if ($check['error'] === true) {
                return [
                    'error'   => true,
                    'message' => $check['message'] ?? 'Seat is not available'
                ];
            }
        }

        // 🔹 Payment validations
        if (($paid_amount > $effectivePaid) || ($paid_amount == 0)) {
            return ['error' => true, 'message' => 'Paid amount is not valid'];
        }

        if (($pending_amount > 0) && (!$due_date)) {
            return ['error' => true, 'message' => 'Due date is required'];
        }

        $is_paid = in_array($payment_mode, [1, 2]) ? 1 : 0;
        if ($payment_mode == 3) {
            $pending_amount = $paid_amount;
            $paid_amount    = 0;
        }

        $extendDay   = getExtendDays();
        $inextendDate = Carbon::parse($endDate)->addDays($extendDay);
        $currentDate  = date('Y-m-d');

        if ($learner_detail && $learner_detail->plan_end_date < $currentDate && $endDate->gt($currentDate) && $is_paid == 1) {
            $status = 1;
        } elseif ($inextendDate >= Carbon::today() && $start_date <= Carbon::today()) {
            $status = 1;
        } else {
            $status = 0;
        }

        // ✅ Always return structured response
        return [
            'error'       => false,
            'end_date'    => $endDate,
            'hours'       => $hours,
            'effective'   => $effectivePaid,
            'pending'     => $pending_amount,
            'paid_amount' => $paid_amount,
            'status'      => $status,
            'is_paid'     => $is_paid,
        ];
    }

    
    public function learnerRenew(Request $request)
    {

        $rules = [

            'plan_id' => 'required',
            'plan_type_id' => 'required',
            'plan_price_id' => 'required',
            'user_id' => 'required',
            // 'payment_mode' => 'required',

        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        $currentDate = date('Y-m-d');
        // Find the customer by user_id
        $customer = Learner::findOrFail($request->user_id);
        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Learner not found.'
            ], 404);
        }

        $months = Plan::where('id', $request->plan_id)->value('plan_id');
        $duration = $months ?? 0;
        $learner_detail = LearnerDetail::where('learner_id', $customer->id)->where('status', 1)->first();


        if (!$learner_detail) {
            return response()->json([
                'success' => false,
                'message' => 'Learner detail not found or inactive.'
            ], 404);
        }
        $start_date = Carbon::parse($learner_detail->plan_end_date)->addDay();

        $endDate = $start_date->copy()->addMonths($duration);
        if ($request->payment_mode == 1 || $request->payment_mode == 2) {
            $is_paid = 1;
            $payment_mode = $request->payment_mode;
        } else {
            $is_paid = 0;
            $payment_mode = 3;
        }

        if ($learner_detail->plan_end_date < $currentDate && $endDate->format('Y-m-d') > $currentDate  && $is_paid == 1) {

            $status = 1;
        } else {

            $status = 0;
        }


        $learner_detail = LearnerDetail::create([
            'library_id' => $customer->library_id,
            'learner_id' => $customer->id,
            'plan_id' => $request->input('plan_id'),
            'plan_type_id' => $request->input('plan_type_id'),
            'plan_price_id' => $request->input('plan_price_id'),
            'plan_start_date' => $start_date->format('Y-m-d'),
            'plan_end_date' => $endDate->format('Y-m-d'),
            'join_date' => $learner_detail->join_date,
            'hour' => $learner_detail->hour,
            'seat_no' => $learner_detail->seat_no,
            'status' => $status,
            'is_paid' => $is_paid,
            'payment_mode' => $payment_mode,
        ]);

        LearnerTransaction::create([
            'learner_id' => $customer->id,
            'library_id' => getLibraryId(),
            'learner_detail_id' => $learner_detail->id,
            'total_amount' => $request->input('plan_price_id'),
            'paid_amount' => $request->input('plan_price_id'),
            'pending_amount' => 0,
            'paid_date' => $start_date->format('Y-m-d') ?? date('Y-m-d'),
            'is_paid' => 1
        ]);

        $learnerStatus = LearnerDetail::where('learner_id', $customer->id)
            ->where('plan_end_date', '<', $currentDate) // Corrected comparison syntax
            ->where('status', 1)
            ->get();

        if (!$learnerStatus->isEmpty()) {

            foreach ($learnerStatus as $data) {
                if ($data->plan_end_date < $currentDate) {

                    $data->status = 0;  // Expired
                } elseif ($data->plan_end_date > $currentDate) {

                    $data->status = 1;  // Active
                }

                $data->save();
            }
        }


        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Learner updated successfully!',
            ], 200);
        } else {
            return redirect()->back()->with('success', 'Learner updated successfully!');
        }
    }

    public function checkPlanTypeSeatWise($seatNo, $requestPlanType)
    {

        // Step 1: Retrieve all bookings for the given seat
        $bookings = $this->getLearnersByLibrary()
            ->join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')
            ->where('learner_detail.seat_no', $seatNo)
            ->where('learner_detail.plan_start_date', '>', Carbon::today())
            ->where('learners.branch_id', getCurrentBranch())
            ->where('learner_detail.branch_id', getCurrentBranch())
            ->get(['learner_detail.plan_type_id', 'plan_types.start_time', 'plan_types.end_time', 'plan_types.slot_hours']);

        // Step 2: Retrieve all plan types
        $planTypes = PlanType::get();

        // Step 3: Initialize an array to store the plan_type_ids to be removed
        $planTypesRemovals = [];

        // Step 4: Calculate total booked hours for the seat
        $totalBookedHours = $bookings->sum('slot_hours');


        // Step 5: Determine conflicts based on plan_type_id and hours
        $planTypeId = null;
        if ($totalBookedHours < 24) {

            foreach ($bookings as $booking) {
                foreach ($planTypes as $planType) {
                    if ($booking->start_time < $planType->end_time && $booking->end_time > $planType->start_time) {
                        $planTypesRemovals[] = $planType->id;
                    }
                }
            }
        }
        if ($totalBookedHours > 1) {
            $planTypeId = PlanType::where('day_type_id', 8)->value('id') ?? 0;
        }

        if (!is_null($planTypeId)) {
            $planTypesRemovals[] = $planTypeId;
        }
        $nightseatBooked = LearnerDetail::join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')->where('learner_detail.seat_no', $seatNo)->where('learner_detail.plan_start_date', '>', Carbon::today())->where('plan_types.day_type_id', 9)->exists();

        if ($nightseatBooked) {
            $planTypeid = LearnerDetail::join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')->where('learner_detail.seat_no', $seatNo)->where('learner_detail.plan_start_date', '>', Carbon::today())->where('plan_types.day_type_id', 9)->value('plan_types.id') ?? 0;
            $planTypesRemovals[] = $planTypeid;
        }
        // Remove duplicate entries in planTypesRemovals
        $planTypesRemovals = array_unique($planTypesRemovals);

        // If total booked hours >= 16, all plan types should be removed
        $first_record = Hour::where('branch_id', getCurrentBranch())->first();
        $total_hour = $first_record ? $first_record->hour : null;

        if ($totalBookedHours >= $total_hour) {
            $planTypesRemovals = $planTypes->pluck('id')->toArray();
        }
        // ✅ Remove day_type_id 8 and 9 if total allowed hours < 24
        if ($total_hour < 24) {
            $dayTypePlanIds = PlanType::whereIn('day_type_id', [8, 9])->pluck('id')->toArray();
            $planTypesRemovals = array_merge($planTypesRemovals, $dayTypePlanIds);
        }
        // Step 6: Filter out the plan_types that match the retrieved plan_type_ids
        $filteredPlanTypes = $planTypes->filter(function ($planType) use ($planTypesRemovals) {
            return !in_array($planType->id, $planTypesRemovals);
        })->map(function ($planType) {
            return ['id' => $planType->id, 'name' => $planType->name];
        })->values(); // Ensure the keys are reset to a continuous numerical index

        $exists = $filteredPlanTypes->contains('id', $requestPlanType);

        return $exists; // true if available, false if not

    }
    public function getChargeableDaysAjax(Request $request)
    {
        
        if (!$request->plan_id || !$request->plan_start_date) {
            return response()->json(null);
        }

        if($request->branch_id){
            $branch=$request->branch_id;
        }else{
            $branch=getCurrentBranch();
        }

        $daysInfo = getChargeableDays(
            $request->plan_id,
            $request->plan_start_date,
            $branch
        );

        return response()->json($daysInfo);
    }


    public function getPlanType(Request $request)
    {

        $seatNo = $request->seat_no;


        if ($request->learner_detail_id) {
            $customer_plan = LearnerDetail::where('id', $request->learner_detail_id)
                ->pluck('plan_type_id');
            $selectedPlan = LearnerDetail::where('id', $request->learner_detail_id)
                ->pluck('plan_id');
        } else {
            $customer_plan = LearnerDetail::where('seat_no', $seatNo)->where('learner_id', $request->user_id)
                ->pluck('plan_type_id');
            $selectedPlan = $this->getLearnersByLibrary()->where('learner_detail.seat_no', $seatNo)->where('learners.id', $request->user_id)
                ->pluck('plan_id');
        }


        // Step 1: Retrieve the plan_type_ids from learners for the given seat
        $filteredPlanTypes = PlanType::where('id', $customer_plan)->pluck('name', 'id');

        $planTypesRemovals = $this->getLearnersByLibrary()->where('learner_detail.seat_no', $seatNo)
            ->pluck('plan_type_id')
            ->toArray();


        // Step 2: Retrieve all plan_types as an associative array
        $planTypes = PlanType::pluck('name', 'id');



        // Step 3: Filter out the plan_types that match the retrieved plan_type_ids
        if (!$planTypesRemovals) {
            $filteredPlanTypes = $planTypes->reject(function ($name, $id) use ($planTypesRemovals) {
                return in_array($id, $planTypesRemovals);
            });
        }


        $selectedPlanName = Plan::where('id', $selectedPlan)->pluck('name', 'id');

        // Return the filtered plan types as JSON
        $selectedbothId = LearnerDetail::where('id', $request->learner_detail_id)->select('learner_id', 'plan_id', 'plan_type_id', 'plan_price_id','plan_end_date')->first();
        $transaction = LearnerTransaction::where('learner_detail_id', $request->learner_detail_id)->select('total_amount', 'locker_amount', 'discount_amount', 'paid_amount')->first();
        $learner = Learner::where('id', $selectedbothId->learner_id)->select('locker_no')->first();

        $branch = Branch::select('fixed_billing_date')
        ->where('id', getCurrentBranch())
        ->first();

        $hasFixedBilling = Branch::where('id', getCurrentBranch())
            ->whereNotNull('fixed_billing_date')
            ->exists();

        $fixedBillingDate = $branch?->fixed_billing_date;
        $start_date = \Carbon\Carbon::parse($selectedbothId->plan_end_date)->addDay()->format('Y-m-d');
       
        if ($hasFixedBilling ) {

            $PlanpPrice = getBillingCyclePrice($selectedbothId->plan_id,$selectedbothId->plan_type_id,$start_date);

        }else {

            $PlanpPrice = getPlanPrice($selectedbothId->plan_id,$selectedbothId->plan_type_id);

        }
        $days=getChargeableDays($selectedbothId->plan_id, $start_date, getCurrentBranch());
        $previous_pending = LearnerTransaction::where(
        'learner_id',
        optional($selectedbothId)->learner_id
        )->sum('pending_amount') ?? 0;

        return response()->json([$filteredPlanTypes, $selectedPlanName, $selectedbothId, $transaction, $learner,$PlanpPrice,$days,$previous_pending]);
    }
    public function getPrice(Request $request, PlanService $priceService)
    {
        if (!$request->plan_type_id || !$request->plan_id) {
            return response()->json(0);
        }

        $branchId = getCurrentBranch();

        $result = $priceService->calculatePrice(
            $request->plan_id,
            $request->plan_type_id,
            $request->plan_start_date,
            $branchId
        );

        return response()->json($result['price']);
    }
    

    
    public function getPlanTypeSeatWise(Request $request, PlanService $service)
    {

        $seatNo = $request->seatNo;
        $branchId = getCurrentBranch();

        $data = $service->getAvailablePlanTypes($seatNo, $branchId);

        return response()->json($data);
    }


    public function fetchCustomerData($customerId = null, $isRenew = false, $status, $detailStatus, $filters = [], $perPage = 10, $paginate = true)
    {


        $query = Learner::withTrashed()->with([
                'latestTransaction:id,pending_amount,total_amount,due_date,locker_amount'
            ])->leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
            ->leftJoin('plans', 'learner_detail.plan_id', '=', 'plans.id')
            ->leftJoin('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id');

        if (getCurrentBranch() == 0) {
            $query->where('learners.library_id', getLibraryId())
                ->where('learner_detail.library_id', getLibraryId());
        } else {
            $query->where('learners.branch_id', getCurrentBranch())
                ->where('learner_detail.branch_id', getCurrentBranch())
                ->where('learners.library_id', getLibraryId())
                ->where('learner_detail.library_id', getLibraryId());
        }
       

        $query->select(
            'plan_types.name as plan_type_name',
            'plans.name as plan_name',
            'learner_detail.seat_no',
            'learners.*',
            'plan_types.start_time',
            'plan_types.end_time',
            'learner_detail.plan_start_date',
            'learner_detail.plan_end_date',
            'learner_detail.plan_type_id',
            'learner_detail.plan_id',
            'learner_detail.plan_price_id',
            'learner_detail.status as learner_detail_status',
            'plan_types.image',
            'learner_detail.is_paid',
            'learner_detail.payment_mode',
            'learner_detail.id as learner_detail_id',
            'learner_detail.exam_id'
        );



        //  Apply dynamic filters if provided
        if (!empty($filters)) {

            // Filter by Plan ID
            if (!empty($filters['plan_id'])) {
                $query->where('learner_detail.plan_id', $filters['plan_id']);
            }

            // Filter by Payment Status

            if (isset($filters['is_paid'])) {
                $query->where('learner_detail.is_paid', $filters['is_paid']);
            }

            // If a status filter is provided, apply it and skip the default status conditions
            if (isset($filters['status'])) {

                if ($filters['status'] === 'active') {
                    // Only select active learners and details
                    $query->where('learners.status', 1)
                        ->where('learner_detail.status', 1);
                } elseif ($filters['status'] === 'expired') {
                    // Only select expired learners or details
                    $query->where('learners.status', 0)
                        ->where('learner_detail.status', 0);
                }
            } else {

                // Apply default status conditions if no status filter is provided
                if ($status == 1 && $detailStatus == 1) {
                    $query->whereIn('learners.status', [1, 2])->whereIn('learner_detail.status', [1, 2]);
                } else {
                    $query->where('learners.status', $status)->where('learner_detail.status', $detailStatus)->orderByDesc('learner_detail.id');
                }
            }

            if (!empty($filters['seat_no'])) {

                $query->where('learner_detail.seat_no', $filters['seat_no']);
            }

            // Search by Name, Mobile, or Email
            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $encryptdata = encryptData($search);
                $query->where(function ($q) use ($search, $encryptdata) {
                    $q->where('learners.name', 'LIKE', "%{$search}%")
                        ->orWhere('learners.mobile', 'LIKE', "%{$encryptdata}%")
                        ->orWhere('learners.seat_no', 'LIKE', "%{$search}%")
                        ->orWhere('learners.email', $encryptdata); // 🔍 Exact match for encrypted email
                });
            }

            if (!empty($filters['future_booking'])) {
                $query->whereDate('learner_detail.plan_start_date', '>', today())->whereNull('learners.deleted_at');
            }
        } else {

            // Apply default status conditions if no filters are provided
            $query->where('learners.status', $status)
                ->where('learner_detail.status', $detailStatus);
        }


        // If fetching a specific customer
        if ($customerId) {


            $query->where('learners.id', $customerId);

            // Handle renew cases
            if ($isRenew) {
                $query->selectRaw('learner_detail.learner_id, learner_detail.plan_start_date, learner_detail.join_date, learner_detail.plan_end_date, learner_detail.plan_type_id, learner_detail.plan_id, learner_detail.plan_price_id, learner_detail.status, 1 as is_renew ,learner_detail.exam_id');
            } else {
                $query->selectRaw('learner_detail.learner_id, learner_detail.plan_start_date, learner_detail.join_date, learner_detail.plan_end_date, learner_detail.plan_type_id, learner_detail.plan_id, learner_detail.plan_price_id, learner_detail.status, 0 as is_renew ,learner_detail.exam_id');
            }

            $customer = $query->firstOrFail();

            if ($customer) {
                // Format start and end time
                $customer->start_time = Carbon::parse($customer->start_time)->format('g:i A');
                $customer->end_time = Carbon::parse($customer->end_time)->format('g:i A');
            }

            return $customer;
        }
        // 🔹 Get sorting values from request (default = seat_no asc)
        $sortBy = request()->get('sort_by', 'seat_no');
        $sortOrder = request()->get('sort_order', 'asc');

        // Map sort_by to real columns
        $sortableColumns = [
            'seat_no' => 'learner_detail.seat_no',
        ];

        if (array_key_exists($sortBy, $sortableColumns)) {
            if ($sortBy === 'seat_no') {
                // NULL values go last
                $query->orderByRaw('learner_detail.seat_no IS NULL')
                    ->orderByRaw('CAST(learner_detail.seat_no AS UNSIGNED) ' . $sortOrder);
            } else {
                $query->orderBy($sortableColumns[$sortBy], $sortOrder);
            }
        } else {
            // Default fallback with NULL last
            $query->orderByRaw('learner_detail.seat_no IS NULL')
                ->orderByRaw('CAST(learner_detail.seat_no AS UNSIGNED) ASC');
        }

        return $paginate
            ? $query->paginate($perPage)
            : $query->get();
    }

    public function fetchLearnerData($customerId = null, $isRenew = false, $status, $detailStatus, $filters = [], $perPage = 5, $paginate = true)
    {

        $query = Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
            ->leftJoin('plans', 'learner_detail.plan_id', '=', 'plans.id')
            ->leftJoin('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id');

        if (getCurrentBranch() == 0) {
            $query->where('learners.library_id', getLibraryId())
                ->where('learner_detail.library_id', getLibraryId());
        } else {
            $query->where('learners.branch_id', getCurrentBranch())
                ->where('learner_detail.branch_id', getCurrentBranch())
                ->where('learners.library_id', getLibraryId())
                ->where('learner_detail.library_id', getLibraryId());
        }

        $query->select(
            'plan_types.name as plan_type_name',
            'plans.name as plan_name',
            'learner_detail.seat_no',
            'learners.*',
            'plan_types.start_time',
            'plan_types.end_time',
            'learner_detail.plan_start_date',
            'learner_detail.plan_end_date',
            'learner_detail.plan_type_id',
            'learner_detail.plan_id',
            'learner_detail.plan_price_id',
            'learner_detail.status as learner_detail_status',
            'plan_types.image',
            'learner_detail.is_paid',
            'learner_detail.payment_mode',
            'learner_detail.id as learner_detail_id'
        )->orderBy('learner_detail_status', 'DESC');


        $filters = array_filter($filters ?? []);

        if (!empty($filters)) {

            // Filter by Plan ID
            if (!empty($filters['plan_id'])) {

                $query->where('learner_detail.plan_id', $filters['plan_id']);
            }

            // Filter by Payment Status

            if (isset($filters['is_paid'])) {

                $query->where('learner_detail.is_paid', $filters['is_paid']);
            }


            if (!empty($filters['seat_no'])) {

                $query->where('learner_detail.seat_no', $filters['seat_no']);
            }
            // Search by Name, Mobile, or Email
            if (!empty($filters['search'])) {

                $search = $filters['search'];
                $encryptdata = encryptData($search);
                $query->where(function ($q) use ($search, $encryptdata) {
                    $q->where('learners.name', 'LIKE', "%{$search}%")
                        ->orWhere('learners.mobile', 'LIKE', "%{$encryptdata}%")
                        ->orWhere('learners.seat_no', 'LIKE', "%{$search}%")
                        ->orWhere('learners.email', $encryptdata);
                });
            }


            return $paginate
                ? $query->paginate($perPage)
                : $query->get();
        }
    }

     public function userUpdate(Request $request, $id = null)
    {

        $learner = Learner::find($id);

        $validator = $this->validateCustomer($request);

        $validator = Validator::make($request->all(), array_merge($validator->getRules(), [
            'email' => [
                'required',
                'email',
                Rule::unique('learners')->where(function ($query) use ($request) {
                    return $query->where('library_id', getLibraryId());
                })->ignore($learner->id),
            ],

        ]));

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors()
                ], 422);
            } else {
                return redirect()->back()->withErrors($validator)->withInput();
            }
        }

        $user_id = $id ?: $request->input('user_id');
        $customer = Learner::findOrFail($user_id);
        if ($customer->seat_no) {
            // Fetch existing bookings for the same seat
            $existingBookings = $this->getLearnersByLibrary()->where('seat_no', $customer->seat_no)
                ->where('learners.id', '!=', $customer->id) // Exclude the current booking
                ->where('learner_detail.status', 1)
                ->get();

            // Determine hours based on plan_type_id

            $planType = PlanType::find($request->plan_type_id);
            $startTime = $planType->start_time;
            $endTime = $planType->end_time;
            $hours = $planType->slot_hours;

            // Check for overlaps with existing bookings
            foreach ($existingBookings as $booking) {
                $bookingPlanType = PlanType::find($booking->plan_type_id);

                if ($bookingPlanType) {
                    $bookingStartTime = $bookingPlanType->start_time;
                    $bookingEndTime = $bookingPlanType->end_time;


                    if (
                        ($startTime < $bookingEndTime && $endTime > $bookingStartTime) ||
                        ($endTime > $bookingStartTime && $startTime < $bookingEndTime)
                    ) {
                        return redirect()->back()->with('error', 'The selected plan type overlaps with an existing booking.');
                    }
                }
            }


            $first_record = Hour::first();
            $total_hour = $first_record ? $first_record->hour : 0;

            if ($total_hour === 0) {
                return redirect()->back()->with('error', 'Total available hours not set.');
            }

            // Calculate total hours booked on this seat
            $total_cust_hour = Learner::where('library_id', getLibraryId())->where('seat_no', $customer->seat_no)->where('status', 1)->sum('hours');

            // Check if the selected plan type exceeds available hours
            if ($hours > ($total_hour - ($total_cust_hour - $customer->hours))) {
                return redirect()->back()->with('error', 'You cannot select this plan type as it exceeds the available hours.');
            } else {
                $plan_type = $request->plan_type_id;
            }
        }
        // Calculate new plan_end_date by adding duration to the current plan_end_date
        $months = Plan::where('id', $request->plan_id)->value('plan_id');
        $duration = $months ?? 0;
        $currentEndDate = Carbon::parse($customer->plan_end_date);
        $start_date = Carbon::parse($request->input('plan_start_date'));
        if ($request->input('plan_end_date')) {
            $newEndDate = Carbon::parse($request->input('plan_end_date'));
        } elseif ($request->input('plan_start_date')) {
            $start_date = Carbon::parse($request->input('plan_start_date'));
            $newEndDate = $start_date->copy()->addMonths($duration);
        } else {

            $newEndDate = $currentEndDate->addMonths($duration);
        }
        // Handle the file upload
        if ($request->hasFile('id_proof_file')) {
            $id_proof_file = $request->file('id_proof_file');
            $id_proof_fileNewName = "id_proof_file_" . time() . "_" . $id_proof_file->getClientOriginalName();

            $id_proof_file->move(public_path('uploads'), $id_proof_fileNewName);
            $id_proof_filePath = 'uploads/' . $id_proof_fileNewName;

            $customer->id_proof_file = $id_proof_filePath;
        }

        // Update customer details only if the field is provided
        $customer->name = $request->input('name', $customer->name);
        $customer->mobile = $request->input('mobile', $customer->mobile);
        $customer->email = $request->input('email', $customer->email);
        $customer->dob = $request->input('dob', $customer->dob);

        $customer->id_proof_name = $request->input('id_proof_name', $customer->id_proof_name);
        $customer->hours = $hours;
        $customer->save();

        $LearnerDetail = LearnerDetail::where('learner_id', $customer->id)->first();
        if ($LearnerDetail) {
            if ($request->input('plan_start_date')) {
                $LearnerDetail->plan_start_date = $start_date;
            }
            $LearnerDetail->plan_id = $request->input('plan_id');
            $LearnerDetail->plan_type_id = $plan_type;
            $LearnerDetail->plan_price_id = $request->input('plan_price_id');
            $LearnerDetail->plan_end_date = $newEndDate->toDateString();
            $LearnerDetail->payment_mode = $request->input('payment_mode');
            $LearnerDetail->save();
        }
        $learnerTransaction = LearnerTransaction::where('learner_detail_id', $request->learner_detail_id)->first();
        if ($learnerTransaction) {
            $learnerTransaction->total_amount = $request->input('plan_price_id');
            $learnerTransaction->paid_amount = $request->input('plan_price_id');
            $learnerTransaction->pending_amount = 0;
        }

        $this->learnerStatusUpdate($customer->id);
        // $this->dataUpdate();
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Learner updated successfully!',
            ], 200);
        } else {
            return redirect()->route('learners')->with('success', 'Learner updated successfully.');
        }
    }
    public function learnerList(Request $request)
    {
        

        $filters = [
            'plan_id' => $request->get('plan_id'),
            'is_paid' => $request->get('is_paid'),
            'status'  => $request->get('status'),
            'search'  => $request->get('search'),
            'seat_no'  => $request->get('seat_no'),
        ];

        $learners = $this->fetchCustomerData(null, false, 1, 1, $filters, $perPage = 15, $paginate = true);

        return view('learner.learner', compact('learners'));
    }
    public function learnerSearch(Request $request)
    {

        $filters = [
            'search'  => $request->get('search'),
        ];

        if ($filters) {
            $paginate = true;
        } else {
            $paginate = false;
        }

        $learners = $this->fetchLearnerData(null, false, 1, 1, $filters, $perPage = 10, $paginate);

        return view('learner.learner-search', compact('learners'));
    }

    public function learnerHistory(Request $request)
    {
        $perPage = 10;
        $filters = [
            'plan_id' => $request->get('plan_id'),
            'is_paid' => $request->get('is_paid'),
            'status'  => $request->get('status'),
            'search'  => $request->get('search'),
        ];

       $latestDetail = LearnerDetail::selectRaw('MAX(id) as id, learner_id')
            ->groupBy('learner_id');

        $query = Learner::withTrashed()
           ->leftJoinSub($latestDetail, 'latest', function ($join) {
                    $join->on('learners.id', '=', 'latest.learner_id');
                })
            ->leftJoin('learner_detail', 'learner_detail.id', '=', 'latest.id')
            ->leftJoin('plans', 'learner_detail.plan_id', '=', 'plans.id')
            ->leftJoin('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')->where('learner_detail.plan_start_date', '<=', date('Y-m-d'));

        if (getCurrentBranch() == 0) {
            $query->where('learners.library_id', getLibraryId())
                ->where('learner_detail.library_id', getLibraryId());
        } else {
            $query->where('learners.branch_id', getCurrentBranch())
                ->where('learner_detail.branch_id', getCurrentBranch())
                ->where('learners.library_id', getLibraryId())
                ->where('learner_detail.library_id', getLibraryId());
        }

        $query->select(
            'plan_types.name as plan_type_name',
            'plans.name as plan_name',
            'learner_detail.seat_no',
            'learners.*',
            'plan_types.start_time',
            'plan_types.end_time',
            'learner_detail.plan_start_date',
            'learner_detail.plan_end_date',
            'learner_detail.plan_type_id',
            'learner_detail.plan_id',
            'learner_detail.plan_price_id',
            'learner_detail.status as learner_detail_status',
            'plan_types.image',
            'learner_detail.is_paid',
            'learner_detail.payment_mode',
            'learner_detail.id as learner_detail_id'
        );


        //  Apply dynamic filters if provided
        if (!empty($filters)) {

            // Filter by Plan ID
            if (!empty($filters['plan_id'])) {
                $query->where('learner_detail.plan_id', $filters['plan_id']);
            }

            // Filter by Payment Status

            if (isset($filters['is_paid'])) {
                $query->where('learner_detail.is_paid', $filters['is_paid']);
            }

            // If a status filter is provided, apply it and skip the default status conditions
            if (isset($filters['status'])) {
                if ($filters['status'] === 'active') {
                    // Only select active learners and details
                    $query->where('learners.status', 1)
                        ->where('learner_detail.status', 1);
                } elseif ($filters['status'] === 'expired') {
                    // Only select expired learners or details
                    $query->where(function ($q) {
                        $q->where('learner_detail.status', 0)->where('learners.status', 0);
                    });
                }
            } else {
                // Apply default status conditions if no status filter is provided
                $query->where('learners.status', 0)
                    ->where('learner_detail.status', 0);
            }
            if (!empty($filters['seat_no'])) {

                $query->where('learner_detail.seat_no', $filters['seat_no']);
            }
            // Search by Name, Mobile, or Email
            if (!empty($filters['search'])) {
                $search = $filters['search'];
                $encryptdata = encryptData($search);
                $query->where(function ($q) use ($search, $encryptdata) {
                    $q->where('learners.name', 'LIKE', "%{$search}%")
                        ->orWhere('learners.mobile', 'LIKE', "%{$encryptdata}%")
                        ->orWhere('learners.seat_no', 'LIKE', "%{$search}%")
                        ->orWhere('learners.email', $encryptdata); // 🔍 Exact match for encrypted email
                });
            }
        } else {
            // Apply default status conditions if no filters are provided
            $query->where('learners.status', 0)
                ->where('learner_detail.status', 0);
        }

        $learnerHistory =   $query->paginate($perPage);



        return view('learner.learnerHistory', compact('learnerHistory'));
    }


    public function getUser(Request $request, $id = null)
    {

        $customerId = $request->id ?? $id;

        $is_renew = $this->learnerService->getRenewalStatus($customerId);

        $status = DB::table('learners')->where('id', $customerId)->value('status') ?? 1;
        $detail = DB::table('learner_detail')->where('learner_id', $customerId)->orderBy('id', 'DESC')->first();
        $detailStatus = $detail?->status ?? 1;
        $available_seat = $this->learnerService->getAvailableSeats();

        $customer = $this->fetchCustomerData($customerId, $is_renew, $status, $detailStatus, $perPage = 10, $paginate = false);
        
        $hasLocker = currentTransaction($customer->learner_detail_id)->locker_amount > 0 ? 'yes' : 'no';
        $discountAmount = currentTransaction($customer->learner_detail_id)->discount_amount ?? null;
        $selectedDiscountType = $discountAmount ? 'amount' : '';
        $oneWeekLater = \Carbon\Carbon::parse($customer->plan_start_date)->addWeek();
        $today = \Carbon\Carbon::now();
        if($hasLocker){
            $locker_amt=currentTransaction($customer->learner_detail_id)->locker_amount;
        }else{
            $locker_amt=0;
        }

        if ($request->expectsJson() || $request->has('id')) {
            return response()->json($customer);
        } else {
            return view('learner.learnerEdit', compact('customer', 'available_seat','hasLocker','discountAmount','selectedDiscountType','oneWeekLater','today','locker_amt'));
        }
    }
    public function showLearner(Request $request, $id = null)
    {

        $customerId = $request->id ?? $id;
        $is_renew = $this->learnerService->getRenewalStatus($customerId);

        $today = Carbon::today();
        $hasFuturePlan = LearnerDetail::where('learner_id', $customerId)
            ->where('plan_end_date', '>', $today->copy()->addDays(5))->where('status', 0)
            ->exists();
        $hasPastPlan = LearnerDetail::where('learner_id', $customerId)
            ->where('plan_end_date', '<=', $today->copy()->addDays(5))
            ->exists();

        $is_renew_update = $hasFuturePlan && $hasPastPlan;



        $available_seat = $this->learnerService->getAvailableSeats();
        $customer_status = Learner::where('id', $customerId)->first();

        $status = $customer_status->status ?? 0;
        $detailStatus = $customer_status->status ?? 0;
        $customer = $this->fetchCustomerData($customerId, $is_renew, $status, $detailStatus);

        //renew History
        $renew_detail = LearnerDetail::where('learner_detail.learner_id', $customerId)
            ->with(['plan', 'planType'])
            ->get();

        //seat history

        if ($customer->seat_no) {
            $seat_history = $this->getAllLearnersByLibrary()
                ->where('seat_no', $customer->seat_no)
                ->where('id', '!=', $customerId)
                ->get();
        } else {
            $seat_history = null;
        }


        $transaction = LearnerTransaction::where('learner_id', $customerId)->where('learner_detail_id', $customer->learner_detail_id)
            ->orderBy('id', 'DESC')
            ->first();



        $all_transactions = LearnerTransaction::where('learner_id', $customerId)->where('is_paid', 1)->get();


        $customer['renew_update'] = $is_renew_update;
        if (isset($transaction) && $transaction->pending_amount > 0 &&  overdue($customerId, $transaction->pending_amount)) {
            $customer['pending'] = $transaction->pending_amount;
            $customer['overdue'] = "Overdue";
        } elseif (isset($transaction) && $transaction->pending_amount > 0) {
            $customer['pending'] = $transaction->pending_amount;
            $customer['overdue'] = '';
        } else {
            $customer['pending'] = '';
            $customer['overdue'] = '';
        }
        $customer['floor_seat_no'] = getSeatDisplayByMainNo($customer->seat_no);
        $customer['seat_status'] = getUserStatusWithSpan($customer->plan_end_date, $customer->learner_id);

        $learner_request = DB::table('learner_request')->where('learner_id', $customerId)->get();

        $learnerlog = DB::table('learner_operations_log')
            ->select('learner_id', 'created_at', DB::raw('MAX(operation) as operation'))
            ->where('learner_id', $customerId)
            ->groupBy('learner_id', 'created_at')
            ->get();

        if ($request->expectsJson() || $request->has('id')) {
            return response()->json($customer);
        } else {
            return view('learner.learnershow', compact('customer', 'available_seat', 'renew_detail', 'seat_history', 'transaction', 'all_transactions', 'learner_request', 'learnerlog'));
        }
    }
   

    public function getSwapUser($id)
    {

        $customerId = $id;
        $firstRecord = Hour::first();
        $totalHour = $firstRecord ? $firstRecord->hour : null;

        $customer = $this->fetchCustomerData($customerId, false, $status = 1, $detailStatus = 1, $perPage = 10, $paginate = false);

        return view('learner.swap', compact('customer'));
    }

    public function seatHistory()
    {

        $today = Carbon::today();
        $first_record = Hour::where('branch_id', getCurrentBranch())->first();
        $total_seats = $first_record ? $first_record->seats : 0;

        $seats = [];

        for ($seatNo = 1; $seatNo <= $total_seats; $seatNo++) {
            // Fetch learners for this seat including trashed details
            $learners = Learner::join('learner_detail', function ($join) {
                        $join->on('learner_detail.learner_id', '=', 'learners.id')
                            ->where('learner_detail.status', 1);
                    })
                ->where('learners.branch_id', getCurrentBranch())
                ->where('learner_detail.seat_no', $seatNo)
                ->select(
                    'learners.*',
                    'learner_detail.seat_no',
                    'learner_detail.plan_start_date',
                    'learner_detail.plan_end_date',
                    'learner_detail.plan_type_id',
                    'learner_detail.plan_id',
                    'learner_detail.join_date',
                    'learner_detail.plan_price_id',
                    'learner_detail.status as learner_detail_status',
                    'learner_detail.is_paid',
                    'learner_detail.learner_id',
                    'learner_detail.payment_mode',
                    'learner_detail.id as learner_detail_id',
                )
                ->get();

            $activeLearners = $learners->where('status', 1)->where('learner_detail_status', 1);
            // $expiredLearners = $learners->where('status', 0);

            $seat = new \stdClass();
            $seat->seat_no = $seatNo;
            $seat->learners = $activeLearners;

            if ($activeLearners->isNotEmpty()) {
                $seat->status = 'booked';
            } else {
                $seat->status = 'available';
            }

            $seats[] = $seat;
        }


        $generalLearners = Learner::join('learner_detail', function ($join) {
                            $join->on('learner_detail.learner_id', '=', 'learners.id')
                                ->where('learner_detail.status', 1);
                        })
                        ->where('learners.branch_id', getCurrentBranch())
                        ->whereNull('learner_detail.seat_no')
                    ->select(
                            'learners.*',
                            'learner_detail.seat_no',
                            'learner_detail.plan_start_date',
                            'learner_detail.plan_end_date',
                            'learner_detail.plan_type_id',
                            'learner_detail.plan_id',
                            'learner_detail.plan_price_id',
                            'learner_detail.status as learner_detail_status',
                            'learner_detail.is_paid',
                            'learner_detail.payment_mode',
                            'learner_detail.join_date',
                            'learner_detail.learner_id',
                            'learner_detail.id as learner_detail_id',
                        )
                    ->get();

        $activeGeneral = $generalLearners->where('status', 1);
        
        $finalGeneralLearners = $activeGeneral;


        return view('learner.seatHistory', ['seats' => $seats, 'finalGeneralLearners' => $finalGeneralLearners]);
    }

    public function history($id)
    {
        $learners = LearnerDetail::withTrashed()->where('branch_id', getCurrentBranch())->where('seat_no', $id)->where('learner_detail.status', 0)->with(['plan', 'planType'])
            ->paginate(5);

        return view('learner.seatHistoryView', compact('learners'));
    }
    public function generalSeathistory()
    {
        // Get the learners with their details, plans, and seat information
        // $learners = Learner::withTrashed()->where('branch_id', getCurrentBranch())->where('learners.status', 0)
        // ->whereNull('learners.seat_no')
        //     ->with([
        //         'learnerDetails' => function ($query) {
        //             $query->with(['plan', 'planType']);
        //         }
        //     ])
        //     ->whereHas('learnerDetails', function ($query) {
        //         $query->whereNull('seat_no')->where('learner_detail.status', 0);
        //     })

        //     ->get();
        $learners = LearnerDetail::withTrashed()->where('branch_id', getCurrentBranch())->whereNull('seat_no')->where('learner_detail.status', 0)->with(['plan', 'planType'])
            ->paginate(10);

        return view('learner.genaralSeatHistoryView', compact('learners'));
    }
    

    public function swapSeat(Request $request, LearnerSeatSwapService $swapService)
    {
        try {
            $swapService->swap($request->learner_id, $request->seat_id);

            return redirect()->route('learners')->with('success', 'Seat swapped successfully!');
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Seat swap failed: '.$e->getMessage());
        }
    }

   
    public function getSeatStatus(Request $request, SeatAvailabilityService $seatAvailability)
    {
        $code = $seatAvailability->getSwapSeatStatusCode(
            $request->new_seat_id,
            $request->user_id,
            $request->plan_type_id
        );

        return response()->json($code);
    }

    public function destroy(Request $request, $id)
    {

        try {
            DB::transaction(function () use ($request, $id) {

                $customer = Learner::withTrashed()->findOrFail($id);

                if ($request->permanent == '1') {
                    $deleteAll = in_array($request->input('deleteAll'), [1, '1', true], true);
                    $result = $this->learnerLifecycleService->permanentDelete((int) $id, $deleteAll);
                    if (! $result['ok']) {
                        throw new Exception($result['message']);
                    }
                } else {

                    $lastLearnerDetail = LearnerDetail::where('learner_id', $customer->id)->orderBy('id', 'DESC')->first();
                    if (!$lastLearnerDetail) {
                        throw new Exception("No LearnerDetail found for learner ID: {$customer->id}");
                    }
                    if ($lastLearnerDetail) {
                        if ($request->isRefund && $request->refundAmount > 0) {
                            LearnerTransaction::where('learner_detail_id', $lastLearnerDetail->id)->update([
                                'refund' => $request->pendingRefund
                            ]);
                        }
                        if ($request->remark) {
                            $customer->remark =  $request->remark;
                        }
                        // Delete associated LearnerTransaction records
                        // LearnerTransaction::where('learner_detail_id', $lastLearnerDetail->id)->delete();
                        $lastLearnerDetail->status = 0;
                        $lastLearnerDetail->save();
                        LearnerDetail::where('learner_id', $customer->id)->delete();
                        $customer->status = 0;

                        $customer->save();
                        $customer->delete();
                        if ($request->isRefund && $request->refundAmount > 0) {

                            $data = [];
                            $data['learner_id'] = $id;
                            $data['particular'] = 'Delete Seat';
                            $data['payment_type'] = 'REFUND';
                            $data['payment_mode'] = 1;
                            $data['amount'] = $request->refundAmount ?? 0;
                            $data['dr_cr'] = 'Dr';
                            $this->learnerTransactionActivity($data);
                        }

                        try {
                            $noti = new NotificationSentController;
                            if (autowabaNotificationActive()) {
                                if ($request->isRefund) {
                                    $template_code = 'refund-waba';
                                } else {
                                    $template_code = 'delete-waba';
                                }
                                \Log::info('autowabaNotificationActive');
                                $noti->autoMessage($customer->id, 'waba', $template_code);
                            }
                            if (autotextNotificationActive()) {
                                if ($request->isRefund) {
                                    $template_code_sms = 'refund-sms';
                                } else {
                                    $template_code_sms = 'delete-sms';
                                }
                                \Log::info('autotextNotificationActive');
                                $noti->autoMessage($customer->id, 'text', $template_code_sms);
                            }
                        } catch (\Throwable $e) {
                            // Log the error (won't break your main code)
                            \Log::error('Notification sending failed: ' . $e->getMessage(), [

                                'exception' => $e
                            ]);
                        }
                    }
                }
            });


            return response()->json(['success' => 'Learner and related details deleted successfully.']);
        } catch (\Exception $e) {

            return response()->json(['error' => 'An error occurred while deleting the customer: ' . $e->getMessage()], 500);
        }

        return response()->json(['success' => 'Learner deleted successfully.']);
    }

    public function userclose(Request $request)
    {


        try {

            DB::transaction(function () use ($request) {
                $today = date('Y-m-d');
                $customer = Learner::findOrFail($request->learner_id);


                $lastLearnerDetail = LearnerDetail::where('id', $request->learnerDetail)->exists();
                if (!$lastLearnerDetail) {
                    throw new Exception("No LearnerDetail found for learner ID: {$customer->id}");
                }
                if ($lastLearnerDetail) {
                    if ($request->isRefund && $request->refundAmount > 0) {
                        LearnerTransaction::where('learner_detail_id', $request->learnerDetail)->update([
                            'refund' => $request->pendingRefund
                        ]);
                    }
                    if ($request->remark) {
                        $customer->remark =  $request->remark;
                    }
                    // Delete associated LearnerTransaction records
                    $exists = LearnerDetail::where('id', $request->learnerDetail)->where('plan_start_date', '>', date('Y-m-d'))->exists();

                    if ($exists) {
                        LearnerDetail::where('id', $request->learnerDetail)->update([
                            'plan_start_date' => $today,
                            'plan_end_date'   => $today,
                            'status'          => 0
                        ]);
                    } else {
                        LearnerDetail::where('id', $request->learnerDetail)->update([
                            'plan_end_date' => $today,
                            'status'        => 0
                        ]);
                    }

                    $customer->status = 0;
                    $customer->save();
                    if ($request->isRefund && $request->refundAmount > 0) {
                        $data = [];
                        $data['learner_id'] = $request->learner_id;
                        $data['particular'] = 'Close Seat';
                        $data['payment_type'] = 'REFUND';
                        $data['payment_mode'] = 1;
                        $data['amount'] = $request->refundAmount ?? 0;
                        $data['dr_cr'] = 'Dr';
                        $this->learnerTransactionActivity($data);
                    }
                    try {
                        $noti = new NotificationSentController;

                        if (autowabaNotificationActive()) {
                            \Log::info('autowabaNotificationActive');
                            $noti->autoMessage($customer->id, 'waba', 'close-waba');
                        }
                        if (autotextNotificationActive()) {
                            \Log::info('autotextNotificationActive');
                            $noti->autoMessage($customer->id, 'text', 'close-sms');
                        }
                    } catch (\Throwable $e) {
                        // Log the error (won't break your main code)
                        \Log::error('Notification sending failed: ' . $e->getMessage(), [

                            'exception' => $e
                        ]);
                    }
                }
            });

            return response()->json(['success' => 'Learner closed successfully.']);
        } catch (\Exception $e) {

            return response()->json(['error' => 'An error occurred while close the customer: ' . $e->getMessage()], 500);
        }
    }

    public function makePayment(Request $request)
    {

        $customer_detail_id = $request->id;
        $customer_detail = LearnerDetail::where('id', $customer_detail_id)->first();
        $customerId = $customer_detail->learner_id;
        if ($customer_detail->status == 0 && $customer_detail->is_paid == 0 && $customer_detail->plan_end_date >= date('Y-m-d')) {
            $isRenew = true;
        } else {
            $isRenew = false;
        }

        $learner = Learner::where('id', $customerId)->first();
        $status = $learner->status;
        $detailStatus = $customer_detail->status;

        $customer = LearnerDetail::leftJoin('learner_transactions', 'learner_detail.id', '=', 'learner_transactions.learner_detail_id')->where('learner_detail.id', $customer_detail_id)->with('learner', 'plan', 'plantype')->first();
        $is_payment_pending = LearnerTransaction::where('learner_detail_id', $customer_detail_id)
            ->where('pending_amount', '!=', 0)
            ->exists();
        $pending_payment = LearnerTransaction::where('learner_detail_id', $customer_detail_id)
            ->where('pending_amount', '!=', 0)->select('pending_amount', 'id')->first();
        if ($customer_detail->seat_no) {
            $filteredPlanTypes = filterPlantypeFromseat($customer_detail->seat_no, $customerId);
        } else {
            $filteredPlanTypes = PlanType::select('id', 'name')->get();
        }

        return view('learner.payment', compact('customer',  'isRenew', 'is_payment_pending', 'pending_payment', 'filteredPlanTypes'));
    }

    public function learnerExpire(Request $request, $id = null)
    {

        $customerId = $request->id ?? $id;
        $is_renew = $this->learnerService->getRenewalStatus($customerId);

        $available_seat = $this->learnerService->getAvailableSeats();

        $customer = $this->fetchCustomerData($customerId, $is_renew, $status = 1, $detailStatus = 1, $perPage = 10, $paginate = true);

        return view('learner.expire', compact('customer',  'available_seat'));
    }
    public function editLearnerExpire(Request $request)
    {
        $user_id = $request->input('user_id');

        $customer = Learner::findOrFail($user_id);
        $start_date = Carbon::parse($request->input('plan_start_date'));
        if ($request->input('plan_end_date')) {
            $newEndDate = Carbon::parse($request->input('plan_end_date'));
        }

        $LearnerDetail = LearnerDetail::where('learner_id', $customer->id)->first();
        if ($request->input('plan_start_date')) {
            $LearnerDetail->plan_start_date = $start_date->toDateString();
            $LearnerDetail->join_date = $start_date->toDateString();
        }

        $LearnerDetail->plan_end_date = $newEndDate->toDateString();
        $LearnerDetail->save();
        $LearnerDetail = LearnerDetail::where('learner_id', $customer->id)->first();
        $LearnerDetail->save();

        $this->learnerStatusUpdate($customer->id);
        // $this->dataUpdate();
        return redirect()->route('learners')->with('success', 'Learner updated successfully.');
    }

    public function learnerLog(Request $request)
    {

        try {
            $validatedData = $request->validate([
                'learner_id' => 'required|integer',
                'field_updated' => 'required',
                'old_value' => 'nullable',
                'new_value' => 'nullable',
                'updated_by' => 'required|integer',
                'operation' => 'required',
            ]);



            $updated_user = $validatedData['updated_by'] ?? getLibraryId();
            $old_value = $validatedData['old_value'] ? $validatedData['old_value'] : $validatedData['operation'];
            if ($validatedData['operation'] == 'renewSeat' || $validatedData['operation'] == 'reactive' || $validatedData['operation'] == 'learnerUpgrade' || $validatedData['operation'] == 'swapseat' || $validatedData['operation'] == 'changePlan') {
                Log::info('Learner Deatail First');
                $learner_detail_id = LearnerDetail::where('learner_id', $validatedData['learner_id'])
                    ->orderBy('id', 'DESC')
                    ->value('id');
            } elseif ($validatedData['operation'] == 'closeSeat' || $validatedData['operation'] == 'deleteSeat') {
                Log::info('Learner Deatail Delete');
                $learner_detail_id = LearnerDetail::withTrashed()->where('learner_id', $validatedData['learner_id'])
                    ->orderBy('id', 'DESC')
                    ->value('id');
            } else {
                Log::info('Learner Deatail Null');
                $learner_detail_id = null;
            }

            /*ALTER TABLE learner_operations_log
            ADD UNIQUE INDEX unique_learner_operation_created_at (learner_id, operation, created_at);
            */
            if ($old_value == 'swapseat') {
                $old_value = "General";
            }

            Log::info('Learner Field Update Info', [
                'learner_id'        => $validatedData['learner_id'],
                'learner_detail_id' => $learner_detail_id,
                'library_id'        => getLibraryId(),
                'field_updated'     => $validatedData['field_updated'],
                'old_value'         => $old_value,
                'new_value'         => $validatedData['new_value'],
                'updated_by'        => $updated_user,
                'operation'         => $validatedData['operation'],
                'branch_id'         => getCurrentBranch(),
                'created_at'        => now(),
            ]);


            DB::table('learner_operations_log')->insert([
                'learner_id' => $validatedData['learner_id'],
                'learner_detail_id' => $learner_detail_id,
                'library_id' => getLibraryId(),
                'field_updated' => $validatedData['field_updated'],
                'old_value' => $old_value,
                'new_value' => $validatedData['new_value'],
                'updated_by' => $updated_user,
                'operation' => $validatedData['operation'],
                'branch_id' =>  getCurrentBranch(),
                'created_at' => now(),
            ]);

            Log::info('Data inserted successfully');
            return response()->json(['success' => true, 'message' => 'Change logged successfully']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation Error:', $e->errors());
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $e->errors()]);
        } catch (\Exception $e) {
            Log::error('Insertion Error:', ['message' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Database insertion error']);
        }
    }


    public function incrementMessageCount(Request $request)
    {

        $id = $request->input('id');
        $type = $request->input('type');
        $message = $request->input('message');

        // Find the learner record
        $learner = Learner::find($id);

        if ($learner) {

            $detailCount = DB::table('email_message')->where('learner_id', $learner->id)->first();

            if ($detailCount) {
                if ($type === 'whatsapp') {

                    DB::table('email_message')->where('learner_id', $learner->id)->create([
                        'learner_message' => $message,
                        'created_at' => now(),
                    ]);
                } elseif ($type === 'email') {

                    DB::table('email_message')->where('learner_id', $learner->id)->create([
                        'learner_email' => $message,
                        'created_at' => now(),
                    ]);
                }

                return response()->json(['success' => true]);
            }
        }

        return response()->json(['success' => false], 404);
    }


    public function generateIdCard(Request $request)
    {

        $learner_detail = LearnerDetail::where('id', $request->detail_id)->with(['plan', 'planType', 'learner'])->first();
        $learner = Learner::where('id', $request->learner_id)->first();
        // Generate the ID Card PDF
        $pdf = PDF::loadView('learner.id_card_template', compact('learner_detail', 'learner'));
        $filePath = storage_path('app/public/id_cards/' . $learner_detail->id . '_id_card.pdf');
        $pdf->save($filePath);

        // Send via WhatsApp
        // $whatsappService = new WhatsAppService();
        // $whatsappService->sendMessageWithAttachment($user->phone, $filePath);
        return redirect()->back()->with('success', 'Learner Id Card Generate successfully!');
    }


    public function learnerProfile()
    {

        // $learner = LearnerDetail::withoutGlobalScopes()->where('learner_id', getLibraryId())->where('learner_detail.status', 1)->leftJoin('plans', 'learner_detail.plan_id', '=', 'plans.id')->leftJoin('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')->select('learner_detail.*', 'plan_types.name as plan_type_name', 'plans.name as plan_name', 'plan_types.start_time', 'plan_types.end_time')->first();
        $customerId = getAuthenticatedUser()->id;
        $is_renew = $this->learnerService->getRenewalStatus($customerId);

        $today = Carbon::today();
        $hasFuturePlan = LearnerDetail::where('learner_id', $customerId)
            ->where('plan_end_date', '>', $today->copy()->addDays(5))->where('status', 0)
            ->exists();
        $hasPastPlan = LearnerDetail::where('learner_id', $customerId)
            ->where('plan_end_date', '<=', $today->copy()->addDays(5))
            ->exists();

        $is_renew_update = $hasFuturePlan && $hasPastPlan;

        $available_seat = $this->learnerService->getAvailableSeats();
        $customer_status = getAuthenticatedUser();

        $status = $customer_status->status ?? 0;
        $detailStatus = $customer_status->status ?? 0;
        $customer = $this->fetchCustomerData($customerId, $is_renew, $status, $detailStatus);

        //renew History
        $renew_detail = LearnerDetail::where('learner_detail.learner_id', $customerId)
            ->with(['plan', 'planType'])
            ->get();

        //seat history

        if ($customer->seat_no) {
            $seat_history = $this->getAllLearnersByLibrary()
                ->where('seat_no', $customer->seat_no)
                ->where('id', '!=', $customerId)
                ->get();
        } else {
            $seat_history = null;
        }


        $transaction = LearnerTransaction::where('learner_id', $customerId)->where('learner_detail_id', $customer->learner_detail_id)
            ->orderBy('id', 'DESC')
            ->first();



        $all_transactions = LearnerTransaction::where('learner_id', $customerId)->where('is_paid', 1)->get();


        $customer['renew_update'] = $is_renew_update;
        if (isset($transaction) && $transaction->pending_amount > 0 &&  overdue($customerId, $transaction->pending_amount)) {
            $customer['pending'] = $transaction->pending_amount;
            $customer['overdue'] = "Overdue";
        } elseif (isset($transaction) && $transaction->pending_amount > 0) {
            $customer['pending'] = $transaction->pending_amount;
            $customer['overdue'] = '';
        } else {
            $customer['pending'] = '';
            $customer['overdue'] = '';
        }
        $customer['floor_seat_no'] = getSeatDisplayByMainNo($customer->seat_no);

        $learner_request = DB::table('learner_request')->where('learner_id', $customerId)->get();

        $learnerlog = DB::table('learner_operations_log')
            ->select('learner_id', 'created_at', DB::raw('MAX(operation) as operation'))
            ->where('learner_id', $customerId)
            ->groupBy('learner_id', 'created_at')
            ->get();

        return view('learner.profile', compact('customer', 'available_seat', 'renew_detail', 'seat_history', 'transaction', 'all_transactions', 'learner_request', 'learnerlog'));
    }

    public function learnerRequest()
    {

        $learner_request = DB::table('learner_request')->where('learner_id', getAuthenticatedUser()->id)->get();

        return view('learner.request', compact('learner_request'));
    }

    public function learnerRequestCreate(Request $request)
    {
        $request->validate([
            'request_name' => 'required|string|max:255',
            'description' => 'required',
        ]);

        DB::table('learner_request')->insert([
            'learner_id' => Auth::id(),
            'request_name' => $request->request_name,
            'description' => $request->description,
            'request_date' => Carbon::now()->toDateString(),

            'created_at' => now(),

        ]);

        return redirect('learner/request')->with('success', 'Request submitted successfully.');
    }

    public function learnerAttendence(Request $request)
    {
        if ($request->has('date')) {
            $learners =  Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
                ->where('learners.library_id', getLibraryId())
                ->where('learners.branch_id', getCurrentBranch())
                ->whereNull('learner_detail.deleted_at')
                ->leftJoin('attendances', function ($join) use ($request) {
                    $join->on('learners.id', '=', 'attendances.learner_id')
                        ->whereDate('attendances.date', '=', $request->date);
                })
                ->where('learners.status', 1)
                ->where('learner_detail.status', 1)
                ->with(['planType'])
                ->select('learners.*', 'learner_detail.*', DB::raw('COALESCE(attendances.attendance, 2) as attendance'), 'attendances.in_time', 'attendances.out_time')
                ->get();
        } else {
            $learners = collect();
        }


        return view('learner.attendance', compact('learners'));
    }

    public function updateAttendance(Request $request)
    {

        $request->validate([
            'learner_id' => 'required|integer',
            'attendance' => 'required|integer',
            'date' => 'required|date',
            'time' => 'required|in:in,out',
        ]);

        // Extract variables from the request
        $learnerId = $request->learner_id;
        $attendance = $request->attendance;
        $date = $request->date;
        $currentTime = now();

        $existingAttendance = Attendance::where('learner_id', $learnerId)
            ->where('date', $date)
            ->first();

        if ($existingAttendance) {

            if ($request->time == 'in') {
                $existingAttendance->in_time = $currentTime;
            } elseif ($request->time == 'out') {
                $existingAttendance->out_time = $currentTime;
            }

            // Update attendance status
            $existingAttendance->attendance = $attendance;
            $existingAttendance->save();
        } else {

            Attendance::create([
                'learner_id' => $learnerId,
                'attendance' => $attendance,
                'date' => $date,
                'in_time' => $request->time == 'in' ? $currentTime : null,
                'out_time' => $request->time == 'out' ? $currentTime : null,
                'library_id' => getLibraryId(),
                'branch_id' => getCurrentBranch(),
            ]);
        }
        $learner = Learner::where('id', $learnerId)->select('name')->first();
        DB::table('learner_attendance_logs')->insert([
            'learner_id'     => $learnerId,
            'branch_id'      => getCurrentBranch(),
            'punch_datetime' => $currentTime,
            'source'         => 'MANUAL',
            'created_at'     => now(),
            
        ]);

        if ($attendance == 1) {
            $message = 'Attendance of ' . $learner->name . ' has been marked Present!';
            return response()->json(['present' => true, 'message' => $message]);
        } else {
            $message = '';
            $message = 'Attendance of ' . $learner->name . ' has been marked Absent!';
            return response()->json(['absent' => true, 'message' => $message]);
        }
    }

    public function getLearnerAttendence(Request $request)
    {
        // Dropdown data
    $data = Learner::where('branch_id', getCurrentBranch())
        ->where('status', 1)
        ->pluck('name', 'id');

    // Base Query
    $learners = Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
        ->leftJoin('plans', 'learner_detail.plan_id', '=', 'plans.id')
        ->leftJoin('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')
        ->leftJoin('attendances', function ($join) use ($request) {
            $join->on('learners.id', '=', 'attendances.learner_id');

            // Apply date filter inside join (important)
            if ($request->filled('date')) {
                $join->whereDate('attendances.date', '=', $request->date);
            }
        })
        ->where('learners.library_id', getLibraryId())
        ->where('learners.branch_id', getCurrentBranch())
        ->where('learners.status', 1)
        ->where('learner_detail.status', 1);

    // Filter by learner
    if ($request->filled('learner_id')) {
        $learners->where('learners.id', $request->learner_id);
    }

    // Select required columns
    $learners = $learners->select(
        'learners.id as learner_id',
        'learners.name as name',
        'learners.email as email',
        'learners.dob as dob',
        'learners.mobile',
        'learners.seat_no',
        'learner_detail.plan_start_date',
        'learner_detail.plan_end_date',
        'learners.library_id',
        'learners.status',
        'plans.name as plan_name',
        'plan_types.name as plan_type_name',
        'attendances.in_time',
        'attendances.out_time',
        'attendances.attendance',
        'attendances.date'
    )->get();

    /* =========================
       COUNTS
    ========================= */

    $totalStudents = $learners->unique('learner_id')->count();

    $presentStudents = $learners
        ->where('attendance', 1)
        ->unique('learner_id')
        ->count();

    $absentStudents = $learners
        ->filter(function ($row) {
            return $row->attendance == 0 || $row->attendance === null;
        })
        ->unique('learner_id')
        ->count();


        return view('library.learner-attendance', compact(
            'learners',
            'data',
            'totalStudents',
            'presentStudents',
            'absentStudents'
        ));
    }


    /** Learner Guard and in front learner related function**/

    public function IdCard(Request $request)
    {

        $data = LearnerDetail::withoutGlobalScopes()->where('learner_id', Auth::user()->id)->where('learner_detail.status', 1)->leftJoin('plans', 'learner_detail.plan_id', '=', 'plans.id')->leftJoin('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')->select('learner_detail.*', 'plan_types.name as plan_type_name', 'plans.name as plan_name', 'plan_types.start_time', 'plan_types.end_time')->first();

        $library_name = Branch::where('id', Auth::user()->branch_id)->select('name as library_name', 'features', 'library_id')->first();
        $library_no = Library::where('id', $library_name->library_id)->select("library_no")->first();

        return view('learner.idCard', compact('library_name', 'data', 'library_no'));
    }
    public function support()
    {
        $library_name = Library::where('id', Auth::user()->library_id)->first();

        return view('learner.support', compact('library_name'));
    }
    public function blog()
    {
        $data = Blog::get();
        return view('learner.blog', compact('data'));
    }
    public function feadback()
    {
        $is_feedback = LearnerFeedback::where('learner_id', Auth::user()->id)->exists();
        return view('learner.feadback', compact('is_feedback'));
    }
    public function suggestions()
    {
        $data = Suggestion::where('learner_id', Auth::user()->id)->get();
        return view('learner.suggestions', compact('data'));
    }
    public function attendance(Request $request)
    {
        $dates = LearnerDetail::withoutGlobalScopes()->where('learner_id', Auth::user()->id)->select('plan_start_date', 'plan_end_date')->get();
        $data = LearnerDetail::withoutGlobalScopes()->where('learner_id', Auth::user()->id)->where('learner_detail.status', 1)->leftJoin('plans', 'learner_detail.plan_id', '=', 'plans.id')->leftJoin('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')->select('learner_detail.*', 'plan_types.name as plan_type_name', 'plans.name as plan_name', 'plan_types.start_time', 'plan_types.end_time')->first();
        $my_attandance = Attendance::where('learner_id', Auth::user()->id)->get();

        if ($request->has('request_name') && !empty($request->request_name)) {
            $year = Carbon::parse($request->request_name)->year;
            $month = Carbon::parse($request->request_name)->month;

            $my_attandance = Attendance::where('learner_id', Auth::user()->id)
                ->whereYear('date', $year)
                ->whereMonth('date', $month)
                ->get();
        }

        $months = [];

        foreach ($dates as $date) {
            $start = Carbon::parse($date->plan_start_date)->startOfMonth();
            $end = Carbon::parse($date->plan_end_date)->startOfMonth();

            // Loop through the months within the start and end date range
            while ($start <= $end) {
                $year = $start->year;
                $monthNumber = $start->month;
                $monthName = $start->format('F');
                $year_month = $start->format('Y-m');

                $months[] = [
                    'year' => $year,
                    'month' => $monthNumber,
                    'month_name' => $monthName,
                    'year_month' => $year_month
                ];

                $start->addMonth();
            }
        }
        return view('learner.my-attendance', compact('months', 'data', 'my_attandance'));
    }
    public function complaints()
    {
        $data = Complaint::where('learner_id', Auth::user()->id)->get();
        return view('learner.complaints', compact('data'));
    }
    public function transactions()
    {
        $transaction = LearnerTransaction::withoutGlobalScopes()->where('learner_transactions.learner_id', Auth::user()->id)->leftJoin('learner_detail', 'learner_transactions.learner_detail_id', '=', 'learner_detail.id')->select('learner_transactions.*', 'learner_detail.plan_type_id', 'learner_detail.plan_id')->get();

        return view('learner.transactions', compact('transaction'));
    }
    public function booksLibrary()
    {
        return view('learner.booksLibrary');
    }

    public function suggestionsStore(Request $request)
    {

        $data = $request->validate([
            'title' => 'required',
            'description' => 'required',

        ]);

        if ($request->hasFile('attachment')) {
            $this->validate($request, ['attachment' => 'mimes:webp,png,jpg,jpeg|max:200']);
            $attachment = $request->attachment;
            $id_proof_fileNewName = "suggestion" . time() . $attachment->getClientOriginalName();
            $attachment->move('public/uploade/', $id_proof_fileNewName);
            $attachment = 'public/uploade/' . $id_proof_fileNewName;
        } else {
            $attachment = null;
        }
        $data['attachment'] = $attachment;
        $data['learner_id'] = Auth::user()->id;
        $data['library_id'] = Auth::user()->library_id;
        Suggestion::create($data);

        return redirect()->route('learner.suggestions')->with('success', 'Data created Successfully');
    }

    public function complaintsStore(Request $request)
    {

        $data = $request->validate([
            'title' => 'required',
            'description' => 'required',

        ]);

        if ($request->hasFile('attachment')) {
            $this->validate($request, ['attachment' => 'mimes:webp,png,jpg,jpeg|max:200']);
            $attachment = $request->attachment;
            $id_proof_fileNewName = "suggestion" . time() . $attachment->getClientOriginalName();
            $attachment->move('public/uploade/', $id_proof_fileNewName);
            $attachment = 'public/uploade/' . $id_proof_fileNewName;
        } else {
            $attachment = null;
        }
        $data['attachment'] = $attachment;
        $data['learner_id'] = Auth::user()->id;
        $data['library_id'] = Auth::user()->library_id;
        Complaint::create($data);

        return redirect()->route('complaints')->with('success', 'Data created Successfully');
    }


    public function feadbackStore(Request $request)
    {
        $data = $request->validate([
            'frequency' => 'required|integer|in:1,2,3,4', // Must be 1, 2, 3, or 4
            'purpose' => 'required|string|max:255', // Required, max 255 chars
            'resources' => 'required|integer|in:1,2', // Must be 1 (Yes) or 2 (No) [change if needed]
            'resource_suggestions' => 'nullable|string|max:500', // Optional, max 500 chars
            'rating' => 'required|integer|min:1|max:5', // Rating between 1-5
            'staff' => 'required|integer|in:1,2', // Must be 1 (Yes) or 2 (No) [change if needed]
            'comments' => 'nullable|string|max:500', // Optional, max 500 chars
        ]);


        $data['learner_id'] = Auth::user()->id;
        $data['library_id'] = Auth::user()->library_id;
        if (LearnerFeedback::where('learner_id', Auth::user()->id)->exists()) {
            return redirect()->route('learner.feadback')->with('error', ' Your feedback already uploaded');
        }
        LearnerFeedback::create($data);
        return redirect()->route('learner.feadback')->with('success', 'Thank you for your feedback!');
    }


    public function blogDetailShow($slug)
    {

        $data = Blog::where('page_slug', $slug)->first();
        $data->tags = json_decode($data->tags, true);
        $categoryIds = json_decode($data->categories_id, true) ?? [];
        $categories = Category::whereIn('id', $categoryIds)->get();
        return view('learner.blog-details', compact('data', 'categories'));
    }

    public function pendingPayment(Request $request)
    {

        $id = $request->id;
        $tran = LearnerTransaction::where('id', $id)->first();

        if (!$tran) {
            abort(404);
        }

        $pendingPayment = LearnerTransaction::where('learner_id', $tran->learner_id)
            ->whereNull('deleted_at')
            ->sum('pending_amount');

        $customer = LearnerDetail::where('id', $tran->learner_detail_id)
            ->with('learner', 'plan', 'planType')
            ->orderBy('id', 'DESC')
            ->first();

        return view('learner.pending-payment', compact('customer', 'pendingPayment', 'tran'));
    }

    public function getTransactionDetail(Request $request)
    {
        $transaction_id = $request->transaction_id;
        $transaction = LearnerTransaction::find($transaction_id);

        if (!$transaction) {
            return response()->json(['error' => 'Transaction not found'], 404);
        }

        $learnerDetail = $transaction->learner_detail_id;
        $data = LearnerDetail::where('id', $learnerDetail)
            ->with('learner', 'plan', 'plantype')
            ->first();

        return response()->json($data);
    }

    /**
     * JSON for learner list modal: transaction summary rows + ledger activity.
     */
    public function learnerTransactionsModalData($learnerId)
    {
        $learnerId = (int) $learnerId;

        $learnerExists = Learner::query()
            ->where('id', $learnerId)
            ->where('library_id', getLibraryId())
            ->when(getCurrentBranch(), function ($q) {
                $q->where('branch_id', getCurrentBranch());
            })
            ->exists();

        if (!$learnerExists) {
            return response()->json(['status' => false, 'message' => 'Learner not found.'], 404);
        }

        $allTransactions = LearnerTransaction::with(['learnerDetail:id,payment_mode'])
            ->where('learner_id', $learnerId)
            ->orderByDesc('id')
            ->get();

        $pendingTransactions = $allTransactions->filter(function ($t) {
            return (float) $t->pending_amount > 0;
        })->values();

        $hasPending = $pendingTransactions->isNotEmpty();
        $transactions = $hasPending ? $pendingTransactions : $allTransactions->take(1)->values();

        $txIds = $transactions->pluck('id')->all();
        $latestActivityByTransactionId = collect();
        if (!empty($txIds)) {
            $latestActivityByTransactionId = LearnerTransactionActivity::where('learner_id', $learnerId)
                ->whereIn('learner_transaction_id', $txIds)
                ->orderByDesc('id')
                ->get()
                ->groupBy('learner_transaction_id')
                ->map(fn ($group) => $group->first());
        }

        $activities = LearnerTransactionActivity::where('learner_id', $learnerId)
            ->orderByDesc('id')
            ->limit(200)
            ->get();

        $rows = $transactions->map(function ($t) use ($latestActivityByTransactionId) {
            $total = (float) ($t->total_amount ?? 0);
            $locker = (float) ($t->locker_amount ?? 0);
            $token = (float) ($t->token_money ?? 0);
            $misc = (float) ($t->miscellaneous ?? 0);
            $discount = (float) ($t->discount_amount ?? 0);
            $planPrice = max(0, $total - $locker - $token - $misc + $discount);

            // Raw column order matches UI: locker_amount | miscellaneous | token_money
            $fmt = static function ($v): string {
                return number_format((float) ($v ?? 0), 2, '.', '');
            };
            $addonLabel = $fmt($t->locker_amount ?? 0) . ' | ' . $fmt($t->miscellaneous ?? 0) . ' | ' . $fmt($t->token_money ?? 0);

            $paid = (float) ($t->paid_amount ?? 0);
            $pending = (float) ($t->pending_amount ?? 0);
            $paidDate = $t->paid_date ? Carbon::parse($t->paid_date)->format('j M Y') : '—';

            $act = $latestActivityByTransactionId->get($t->id);
            $modeSource = null;
            if ($act && $act->payment_mode !== null && trim((string) $act->payment_mode) !== '') {
                $modeSource = $act->payment_mode;
            } elseif ($t->learnerDetail
                && $t->learnerDetail->payment_mode !== null
                && trim((string) $t->learnerDetail->payment_mode) !== '') {
                $modeSource = $t->learnerDetail->payment_mode;
            }
            $paymentModeRaw = $this->displayPaymentModeLabel($modeSource);

            $canReceipt = (int) ($t->is_paid ?? 0) === 1;

            return [
                'id' => $t->id,
                'plan_price' => number_format($planPrice, 2, '.', ''),
                'other_addon_label' => $addonLabel,
                'discount_amount' => number_format($discount, 2, '.', ''),
                'paid_amount' => number_format($paid, 2, '.', ''),
                'pending_amount' => number_format($pending, 2, '.', ''),
                'paid_date' => $paidDate,
                'payment_mode' => $paymentModeRaw,
                'receipt_url' => $canReceipt ? route('receipt.view', ['transactionId' => $t->id]) : null,
            ];
        })->values();

        $activityRows = $activities->map(function ($a) {
            $raw = $a->amount !== null ? (float) $a->amount : 0.0;
            $formatted = number_format($raw, 2, '.', '');
            $signed = ($a->dr_cr ?? '') === 'Cr' ? $formatted : '-' . $formatted;
            $dateLabel = $a->date ? Carbon::parse($a->date)->format('j M Y') : '—';
            $particular = trim((string) ($a->particular ?? ''));

            return [
                'particular' => $particular !== '' ? $particular : '—',
                'payment_type' => ($a->payment_type !== null && $a->payment_type !== '') ? (string) $a->payment_type : '—',
                'payment_mode' => $this->displayPaymentModeLabel($a->payment_mode),
                'amount_display' => $signed,
                'payment_date' => $dateLabel,
                'dr_cr' => ($a->dr_cr !== null && $a->dr_cr !== '') ? (string) $a->dr_cr : '—',
            ];
        })->values();

        return response()->json([
            'status' => true,
            'has_pending' => $hasPending,
            'transactions' => $rows,
            'activities' => $activityRows,
        ]);
    }

    /**
     * learner_detail / legacy codes: 1 => ONLINE, 2 => OFFLINE, 3 => PAYLATER.
     * Non-numeric values (e.g. activity enums) are returned as stored.
     */
    private function displayPaymentModeLabel($value): string
    {
        if ($value === null || trim((string) $value) === '') {
            return '—';
        }

        $s = trim((string) $value);
        if (is_numeric($s)) {
            return match ((int) $s) {
                1 => 'ONLINE',
                2 => 'OFFLINE',
                3 => 'PAYLATER',
                default => $s,
            };
        }

        return $s;
    }

    public function pendingPaymentStore(Request $request)
    {
       
        $this->validate($request, [
            // 'transaction_image' => 'nullable|mimes:webp,png,jpg,jpeg|max:200',
            'learner_id' => 'required|exists:learners,id',
            'total_pending' => 'nullable',
            'amount_to_pay' => 'required',
            'payment_mode' => 'required',
            'due_date'=>'nullable'

        ]);



        if ($request->total_pending != $request->amount_to_pay && !$request->due_date) {

            return redirect()->back()->with('error', ' Due date is required');
        }
        if ($request->due_date) {
            $due_dat = $request->due_date;
        } else {
            $due_dat = null;
        }


        try {

            $this->updateLearnerTransactionPayment($request->learner_id,$request->amount_to_pay, $request->total_pending, $request->payment_mode, $due_dat);

            return redirect()->route('learners')->with('success', 'Payment successfully recorded.');
        } catch (\Exception $e) {
            \Log::error('Payment Error: ' . $e->getMessage());
            return redirect()->route('learners')->withErrors(['error' => 'An error occurred while processing the payment.']);
        }
    }
   

    public function makeOtherPayment(Request $request)
    {

        $customer_detail_id = $request->id;
        $customer = LearnerDetail::withTrashed()->where('learner_detail.id', $customer_detail_id)->leftJoin('learner_transactions', 'learner_transactions.learner_detail_id', '=', 'learner_detail.id')->with('learner', 'plan', 'plantype')->select('learner_detail.*', 'learner_transactions.token_money', 'learner_transactions.refund as pending_refund')->first();

        $tokenMoney = token_money();

        return view('learner.other_payment', compact('customer', 'tokenMoney'));
    }

    public function otherPaymentStore(Request $request)
    {

        $request->validate([
            'learner_id'    => 'required|exists:learner_transactions,learner_id',
            'payment_type'  => 'required|in:token_money,miscellaneous,pending_refund',
            'fees'          => 'required|numeric|min:1',
        ]);

        try {
            // Step 2: Find transaction record
            $transaction = LearnerTransaction::withTrashed()->where('learner_id', $request->learner_id)->first();

            if (!$transaction) {
                return redirect()->back()->with('error', 'Learner transaction record not found.');
            }

            // Step 3: Update based on payment type
            if ($request->payment_type === 'token_money') {
                $transaction->token_money = $request->fees;
                $payment_type = 'TOKEN MONEY';
                $dr_cr = 'Cr';
            } elseif ($request->payment_type === 'miscellaneous') {
                $transaction->miscellaneous = ($transaction->miscellaneous ?? 0) + $request->fees;
                $payment_type = 'MISCELLANEOUS';
                $dr_cr = 'Cr';
            } elseif ($request->payment_type === 'pending_refund') {
                $transaction->refund = ($transaction->refund ?? 0) - $request->fees;
                $payment_type = 'REFUND';
                $dr_cr = 'Dr';
            }

            $transaction->save();
            $data = [];
            $data['learner_id'] = $request->learner_id;
            $data['particular'] = 'Paid By Trans';
            $data['payment_type'] = $payment_type;
            $data['payment_mode'] = $request->payment_mode ?? 1;
            $data['amount'] = $request->fees;
            $data['dr_cr'] = $dr_cr;
            $this->learnerTransactionActivity($data);
            if ($payment_type == 'REFUND') {
                try {
                    $noti = new NotificationSentController;

                    if (autowabaNotificationActive()) {
                        \Log::info('autowabaNotificationActive');
                        $noti->autoMessage($data['learner_id'], 'waba', 'refund-waba');
                    }
                    if (autotextNotificationActive()) {
                        \Log::info('autotextNotificationActive');
                        $noti->autoMessage($data['learner_id'], 'text', 'refund-sms');
                    }
                } catch (\Throwable $e) {
                    // Log the error (won't break your main code)
                    \Log::error('Notification sending failed: ' . $e->getMessage(), [

                        'exception' => $e
                    ]);
                }
            }

            if ($payment_type == 'REFUND') {
                return redirect('library/learners/history/list')->with('success', 'Refund Processed Successfully.');
            } else {
                return redirect('library/learners/list')->with('success', 'Payment successfully recorded.');
            }
        } catch (\Exception $e) {

            // Log the error if needed: Log::error($e->getMessage());
            return redirect()->back()->with('error', 'An error occurred while processing payment.');
        }
    }

    public function learnerIdCard($id)
    {
        $learner_detail = LearnerDetail::where('id', $id)->with(['learner','planType'])->first();
        $branch = Branch::where('id', $learner_detail->branch_id)->with('city', 'state')->first();
        // $filename = 'qr_' . $learner_detail->learner_id . '.png';
        // Storage::put("public/qr/$filename", QrCode::format('png')->size(300)->generate($learner_detail->learner->learner_no));
        return view('learner.id_card_template', compact('learner_detail', 'branch'));
    }
    public function printBulkIdCard(Request $request)
    {
       
        $learnerIds = $request->input('learner_ids', []);

        if (empty($learnerIds)) {
            return back()->with('error', 'Please select at least one learner.');
        }

        $learner_details = LearnerDetail::whereIn('learner_id', $learnerIds)->where('status', 1)->with(['learner'])->get();

        $branch = Branch::where('id', getCurrentBranch())->with('city', 'state')->first();
        $print_type=$request->print_type;
        return view('learner.bulk-idcards', compact('learner_details', 'branch','print_type'));
    }

   public function learnerChecklist()
    {
        $learners = Learner::leftJoin('learner_detail as ld', function ($join) {
                $join->on('learners.id', '=', 'ld.learner_id')
                    ->whereRaw('ld.id = (
                        SELECT MAX(id) 
                        FROM learner_detail 
                        WHERE learner_id = learners.id
                    )');
            })
            ->where('learners.status', 1)
            ->where('learners.branch_id', getCurrentBranch())
            ->select(
                'learners.name',
                'learners.learner_no',
                'learners.mobile',
                'learners.father_name',
                'ld.is_paid',
                'ld.plan_end_date',
                'learners.id',
                'learners.profile_picture'
            )
            ->get();

        return view('learner.checklist', compact('learners'));
    }


    public function learnerTransactionActivity($data)
    {
         if($data['payment_mode'] == 1){
            $paymentmode='ONLINE';
        }elseif($data['payment_mode'] == 2){
            $paymentmode='OFFLINE';
        }else{
            $paymentmode='PAYLATER';
        }

        $payload = [
            'branch_id'      => getCurrentBranch(),
            'learner_id'     => $data['learner_id'],
            'learner_transaction_id' => $data['learner_transaction_id'] ?? null,
            'date'           => now()->format('Y-m-d'),
            'transaction_id' => transaction_id(),
            'particular'     => $data['particular'],
            'payment_type'   => $data['payment_type'],
            'payment_mode'   => $paymentmode,
            'amount'         => $data['amount'] ?? 0,
            'dr_cr'          => $data['dr_cr'],
        ];

        if (!empty($data['learner_transaction_id'])) {
            $payload['learner_transaction_id'] = $data['learner_transaction_id'];
        }

        LearnerTransactionActivity::create($payload);
    }

   

    // public function learnerTransactionAddUpdate($data)
    // {
    //     // 1. Save LearnerTransaction
    //     $effectivePaid = $data['planPrice'] + $data['locker'] - $data['discount'];
    //     $pending_amount =  $effectivePaid - $data['paid_amount'];

    //     if ($data['paid_date']) {
    //         $transaction_date = $data['paid_date']->format('Y-m-d');
    //     } elseif ($data['start_date']->format('Y-m-d')) {
    //         $transaction_date = $data['start_date']->format('Y-m-d');
    //     } else {
    //         $transaction_date = date('Y-m-d');
    //     }

    //     $learnerPendingTran=LearnerTransaction::where('learner_id',$data['learner_id'])->where('pending_amount','>',0)->get();
    //     $oldPendingTotal=LearnerTransaction::where('learner_id',$data['learner_id'])->sum('pending_amount');

    //     if($learnerPendingTran){
    //         foreach($learnerPendingTran as $data){
    //             $new_paid=$data['paid_amount'];
    //             if($new_paid >= $data->pending_amount){
    //                     $paid_new_amount=$data->pending_amount;
    //                     $newPending=0;
    //                     $remaining=$new_paid-$data->pending_amount;
    //             }else{
    //                 $paid_new_amount=$new_paid;
    //                 $newPending=$data->pending_amount-$new_paid;
    //                 $remaining=0;
    //             }

    //             $learnerTransaction = LearnerTransaction::where('id',$data->id)->update([
                   
                   
    //                 'paid_amount'       => $paid_new_amount,
    //                 'pending_amount'    => $newPending,
    //                 'paid_date'         => $transaction_date,
    //                 'due_date'        => $data['due_date'],
                   

    //             ]);

    //             $new_paid=$remaining;

    //         }
    //         $learnerTransaction = LearnerTransaction::create([
    //                 'learner_id'        => $data['learner_id'],
    //                 'library_id'        => getLibraryId(),
    //                 'learner_detail_id' => $data['learner_detail_id'],
    //                 'total_amount'      => $effectivePaid,
    //                 'paid_amount'       => $paid_new_amount,
    //                 'pending_amount'    => $newPending,
    //                 'locker_amount'     => $data['locker'] ?? 0,
    //                 'discount_amount'   => $data['discount'] ?? 0,
    //                 'paid_date'         => $transaction_date,
    //                 'is_paid'           => $data['is_paid'] ?? 0,
    //                 'branch_id'         => getCurrentBranch(),
    //                 'due_date'        => $data['due_date'],
    //                 'transaction_id' => transaction_id(),

    //             ]);
    //          // 2. Add to LearnerTransactionActivity
    //          if( $data['paid_amount'] >=  $oldPendingTotal){
    //                 $activityData1 = [
    //                     'learner_id'   => $data['learner_id'],
    //                     'particular'   => $data['particular'] ? $data['particular'] :'Paid By Trans',
    //                     'payment_type' => 'PENDING',
    //                     'payment_mode' => $data['payment_mode'],
    //                     'amount'       => $oldPendingTotal,
    //                     'dr_cr'        => 'Cr',
    //                 ];
    //                 $this->learnerTransactionActivity($activityData1);
    //                 $activityData2 = [
    //                     'learner_id'   => $data['learner_id'],
    //                     'particular'   => $data['particular'] ? $data['particular'] :'Paid By Trans',
    //                     'payment_type' => $data['payment_type'],
    //                     'payment_mode' => $data['payment_mode'],
    //                     'amount'       => $data['paid_amount']-$oldPendingTotal,
    //                     'dr_cr'        => 'Cr',
    //                 ];
    //                 $this->learnerTransactionActivity($activityData2);
    //          }else{
    //             $activityData1 = [
    //                 'learner_id'   => $data['learner_id'],
    //                 'particular'   => $data['particular'] ? $data['particular'] :'Paid By Trans',
    //                 'payment_type' => 'PENDING',
    //                 'payment_mode' => $data['payment_mode'],
    //                 'amount'       => $data['paid_amount'],
    //                 'dr_cr'        => 'Cr',
    //             ];
    //             $this->learnerTransactionActivity($activityData1);
    //          }
            

    //     }else{
    //         $learnerTransaction = LearnerTransaction::create([
    //             'learner_id'        => $data['learner_id'],
    //             'library_id'        => getLibraryId(),
    //             'learner_detail_id' => $data['learner_detail_id'],
    //             'total_amount'      => $effectivePaid,
    //             'paid_amount'       => $data['paid_amount'],
    //             'pending_amount'    => $pending_amount,
    //             'locker_amount'     => $data['locker'] ?? 0,
    //             'discount_amount'   => $data['discount'] ?? 0,
    //             'paid_date'         => $transaction_date,
    //             'is_paid'           => $data['is_paid'] ?? 0,
    //             'branch_id'         => getCurrentBranch(),
    //             'due_date'        => $data['due_date'],
    //             'transaction_id' => transaction_id(),

    //         ]);
    //          // 2. Add to LearnerTransactionActivity
    //         $activityData = [
    //             'learner_id'   => $data['learner_id'],
    //             'particular'   => $data['particular'] ? $data['particular'] :'Paid By Trans',
    //             'payment_type' => $data['payment_type'],
    //             'payment_mode' => $data['payment_mode'],
    //             'amount'       => $data['paid_amount'],
    //             'dr_cr'        => 'Cr',
    //         ];
    //     }

       
    //     $this->learnerTransactionActivity($activityData);


    //     return $learnerTransaction;
    // }

    public function learnerTransactionAddUpdate($data)
    {
        // 1. Calculate new plan total
        $effectivePaid = $data['planPrice'] + $data['locker'] - $data['discount'];

        if ($data['paid_date']) {
            $transaction_date = $data['paid_date']->format('Y-m-d');
        } elseif ($data['start_date']) {
            $transaction_date = $data['start_date']->format('Y-m-d');
        } else {
            $transaction_date = date('Y-m-d');
        }

        // 2. Get old pending transactions
        $pendingTransactions = LearnerTransaction::where('learner_id', $data['learner_id'])
            ->where('pending_amount', '>', 0)
            ->orderBy('id', 'asc')
            ->get();

        $oldPendingTotal = $pendingTransactions->sum('pending_amount');

        $paidAmount = $data['paid_amount'];

        // 3. Apply payment to old pending first
        $pendingPaid = min($paidAmount, $oldPendingTotal);
        $remainingForNewPlan = $paidAmount - $pendingPaid;

        $remainingPendingPayment = $pendingPaid;

        foreach ($pendingTransactions as $tran) {
            if ($remainingPendingPayment <= 0) {
                break;
            }

            $tranPending = $tran->pending_amount;

            if ($remainingPendingPayment >= $tranPending) {
                // Fully clear this transaction
                $paidNow = $tranPending;
                $newPending = 0;
            } else {
                // Partially clear
                $paidNow = $remainingPendingPayment;
                $newPending = $tranPending - $paidNow;
            }

            $tran->update([
                'paid_amount'    => $tran->paid_amount + $paidNow,
                'pending_amount' => $newPending,
                'paid_date'      => $transaction_date,
            ]);

            $remainingPendingPayment -= $paidNow;
        }

        // 4. New plan payment
        $newPlanPaid = max(0, $remainingForNewPlan);
        $newPlanPending = $effectivePaid - $newPlanPaid;

        if ($newPlanPending < 0) {
            $newPlanPending = 0;
        }

        // 5. Create new plan transaction
        $learnerTransaction = LearnerTransaction::create([
            'learner_id'        => $data['learner_id'],
            'library_id'        => getLibraryId(),
            'learner_detail_id' => $data['learner_detail_id'],
            'total_amount'      => $effectivePaid,
            'paid_amount'       => $newPlanPaid,
            'pending_amount'    => $newPlanPending,
            'locker_amount'     => $data['locker'] ?? 0,
            'discount_amount'   => $data['discount'] ?? 0,
            'paid_date'         => $transaction_date,
            'is_paid'           => $data['is_paid'] ?? 0,
            'branch_id'         => getCurrentBranch(),
            'due_date'          => $data['due_date'],
            'transaction_id'    => transaction_id(),
        ]);

        // // 2. Add to LearnerTransactionActivity
        // $activityData = [
        //     'learner_id'   => $data['learner_id'],
        //     'learner_transaction_id' => $learnerTransaction->id,
        //     'particular'   => $data['particular'] ? $data['particular'] :'Paid By Trans',
        //     'payment_type' => $data['payment_type'],
        //     'payment_mode' => $data['payment_mode'],
        //     'amount'       => $data['paid_amount'],
        //     'dr_cr'        => 'Cr',
        // ];
        // $this->learnerTransactionActivity($activityData);

        // 6. Activity entries

        // Pending payment activity
        if ($pendingPaid > 0) {
            $activityData1 = [
                'learner_id'   => $data['learner_id'],
                'learner_transaction_id' => optional($pendingTransactions->first())->id,
                'particular'   => $data['particular'] ?? 'Paid By Trans',
                'payment_type' => 'PENDING',
                'payment_mode' => $data['payment_mode'],
                'amount'       => $pendingPaid,
                'dr_cr'        => 'Cr',
            ];
            $this->learnerTransactionActivity($activityData1);
        }

        // New plan payment activity
        if ($newPlanPaid >= 0) {
            $activityData2 = [
                'learner_id'   => $data['learner_id'],
                'learner_transaction_id' => $learnerTransaction->id,
                'particular'   => $data['particular'] ?? 'Paid By Trans',
                'payment_type' => $data['payment_type'],
                'payment_mode' => $data['payment_mode'],
                'amount'       => $newPlanPaid,
                'dr_cr'        => 'Cr',
            ];
            $this->learnerTransactionActivity($activityData2);
        }

        return $learnerTransaction;
    }


    public function updateLearnerTransactionPayment($learnerId, $paidAmount,$pending_amount,$payment_mode, $due_date)
    {
            $transaction_date = date('Y-m-d');

                // Get old pending transactions
            $pendingTransactions = LearnerTransaction::where('learner_id', $learnerId)
                ->where('pending_amount', '>', 0)
                ->orderBy('id', 'asc')
                ->get();

            $oldPendingTotal = $pendingTransactions->sum('pending_amount');

            // Apply payment to old pending first
            $pendingPaid = min($paidAmount, $oldPendingTotal);
            $remainingForNewPlan = $paidAmount - $pendingPaid;

            $remainingPendingPayment = $pendingPaid;

            foreach ($pendingTransactions as $tran) {
                if ($remainingPendingPayment <= 0) {
                    break;
                }

                $tranPending = $tran->pending_amount;

                if ($remainingPendingPayment >= $tranPending) {
                    // Fully clear this transaction
                    $paidNow = $tranPending;
                    $newPending = 0;
                } else {
                    // Partially clear
                    $paidNow = $remainingPendingPayment;
                    $newPending = $tranPending - $paidNow;
                }

                $updateData = [
                    'paid_amount'    => $tran->paid_amount + $paidNow,
                    'pending_amount' => $newPending,
                    'paid_date'      => $transaction_date,
                    'is_paid'        => $newPending == 0 ? 1 : 0,
                ];

                // Update due date only if still pending
                if ($newPending > 0 && $due_date) {
                    $updateData['due_date'] = $due_date;
                }

                $tran->update($updateData);

                $remainingPendingPayment -= $paidNow;
            }

            $type  = 'PENDING';
            $parti = 'REMAINING PAYMENT';

            // Pending payment activity
            if ($pendingPaid > 0) {
                $activityData1 = [
                    'branchId'     => getCurrentBranch(),
                    'learner_id'   => $learnerId,
                    'learner_transaction_id' => optional($pendingTransactions->first())->id,
                    'particular'   => $parti ?? 'Paid By Trans',
                    'payment_type' => $type,
                    'payment_mode' => $payment_mode,
                    'amount'       => $pendingPaid,
                    'dr_cr'        => 'Cr',
                ];
                $this->learnerTransactionActivity($activityData1);
            }


    }


    public function learnerFuture(Request $request)
    {

        $filters = [
            'plan_id' => $request->get('plan_id'),
            'is_paid' => $request->get('is_paid'),
            'status'  => $request->get('status'),
            'search'  => $request->get('search'),
            'seat_no'  => $request->get('seat_no'),
            'future_booking' => true,
        ];

        $learners = $this->fetchCustomerData(null, false, 0, 0, $filters, $perPage = 15, $paginate = true);

        return view('learner.future-bookings', compact('learners'));
    }

    public function restore(Request $request)
    {
        if (! $request->filled('learner_detail_id') && ! $request->filled('learner_id')) {
            return response()->json([
                'success' => false,
                'message' => 'learner_id or learner_detail_id is required.',
            ], 422);
        }

        $detailId = $request->learner_detail_id ? (int) $request->learner_detail_id : null;
        $learnerId = $request->learner_id ? (int) $request->learner_id : 0;

        if ($detailId) {
            $ld = LearnerDetail::withTrashed()->find($detailId);
            if (! $ld) {
                return response()->json([
                    'success' => false,
                    'message' => 'Learner detail not found.',
                ], 404);
            }
            if ($learnerId && (int) $ld->learner_id !== $learnerId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Learner detail does not match learner_id.',
                ], 422);
            }
            $learnerId = (int) $ld->learner_id;
        }

        if (! $learnerId) {
            return response()->json([
                'success' => false,
                'message' => 'learner_id is required when learner_detail_id is omitted.',
            ], 422);
        }

        $result = $this->learnerLifecycleService->restore($learnerId, $detailId);

        if (! $result['ok']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
        ]);
    }

    //assign gift days
    public function giftDaysAssign(Request $request)
    {
        $request->validate([
            'learner_id' => 'required|integer',
            'gift_days' => 'required|integer',
        ]);

        $result = $this->learnerGiftDaysService->assign(
            (int) $request->learner_id,
            (int) $request->gift_days
        );

        if (! $result['ok']) {
            return response()->json([
                'status' => false,
                'message' => $result['message'],
            ], $result['status_code'] ?? 422);
        }

        return response()->json([
            'status' => true,
            'message' => $result['message'],
        ]);
    }

    public function getGiftDays(Request $request)
    {
        $total = $this->learnerGiftDaysService->getTotalGiftDays((int) $request->learner_id);

        return response()->json([
            'total_gift_days' => $total,
        ]);
    }

    public function freezeUnfreeze(Request $request)
    {
        if (! $request->has('status') || ! in_array((int) $request->status, [0, 1], true)) {
            return response()->json(['status' => false, 'message' => 'status 0 (freeze) or 1 (unfreeze) is required.']);
        }

        $learnerId = (int) ($request->learner_id ?? 0);
        $detailId = $request->learnerDetail ?? $request->learner_detail_id;
        if ($detailId) {
            $detailId = (int) $detailId;
        } else {
            $detailId = null;
        }

        if (! $learnerId && ! $detailId) {
            return response()->json(['status' => false, 'message' => 'learner_id or learnerDetail is required.'], 422);
        }

        if (! $learnerId && $detailId) {
            $d = LearnerDetail::find($detailId);
            if (! $d) {
                return response()->json(['status' => false, 'message' => 'Learner detail not found.'], 404);
            }
            $learnerId = (int) $d->learner_id;
        }

        $freeze = (int) $request->status === 0;
        $result = $this->learnerLifecycleService->freezeOrUnfreeze($learnerId, $freeze, $detailId);

        if (! $result['ok']) {
            return response()->json(['status' => false, 'message' => $result['message']]);
        }

        $payload = [
            'status' => true,
            'message' => $result['message'],
        ];
        if (array_key_exists('frozen_days', $result)) {
            $payload['frozen_days'] = $result['frozen_days'];
        }

        return response()->json($payload);
    }

    public function viewReceipt($transactionId)
    {
        $transaction = LearnerTransaction::withoutGlobalScopes()
            ->where('id', $transactionId)
            ->where('is_paid', 1)
            ->firstOrFail();

        return $this->generateReceiptPdf($transaction);
    }

    private function generateReceiptPdf(LearnerTransaction $transaction)
    {
        \Log::info('Generating receipt', ['transaction_id' => $transaction->id]);

        // ✅ Safety check
        if (!$transaction || !$transaction->is_paid) {
            abort(404, 'Receipt not found');
        }

        $user = Learner::find($transaction->learner_id);

        if (!$user) {
            abort(404, 'Learner not found');
        }

        $learnerDetail = LearnerDetail::withoutGlobalScopes()
            ->with(['plan', 'planType'])
            ->find($transaction->learner_detail_id);

        if (!$learnerDetail) {
            abort(404, 'Learner detail not found');
        }

        $library = Library::leftJoin('branches', 'libraries.id', '=', 'branches.library_id')
            ->where('libraries.id', $learnerDetail->library_id)
            ->select(
                'libraries.library_name',
                'libraries.email',
                'libraries.library_mobile',
                'branches.library_address'
            )
            ->first();

        $branch_logo = Branch::where('id', $transaction->branch_id)->value('library_logo');
        $branch_slug = Branch::where('id', $transaction->branch_id)->value('slug');

        $tran = LearnerTransactionActivity::where('learner_id', $transaction->learner_id)
            ->value('transaction_id');

        $start = date('h:i A', strtotime($learnerDetail->planType->start_time));
        $end   = date('h:i A', strtotime($learnerDetail->planType->end_time));

        $shift_timing = $start . ' to ' . $end;

        $send_data = [
            'branch_logo'      => $branch_logo ?? '',
            'subscription'     => $learnerDetail->planType->name ?? 'NA',
            'name'             => $user->name ?? 'NA',
            'email'            => $user->email ?? 'NA',
            'transactiondate'  => $transaction->paid_date ?? 'NA',
            'paid_amount'      => $transaction->paid_amount ?? 'NA',
            'payment_mode'     => $learnerDetail->payment_mode ?? 'NA',
            'invoice_ref_no'   => $tran ?? 'NA',
            'total_amount'     => $transaction->total_amount ?? 'NA',
            'start_date'       => $learnerDetail->plan_start_date ?? 'NA',
            'end_date'         => $learnerDetail->plan_end_date ?? 'NA',
            'monthly_amount'   => $transaction->total_amount ?? 'NA',
            'month'            => $learnerDetail->plan->plan_id ?? 'NA',
            'currency'         => 'Rs.',
            'library_name'     => $library->library_name ?? '',
            'library_email'    => $library->email ?? '',
            'library_mobile'   => $library->library_mobile ?? '',
            'library_address'  => $library->library_address ?? '',
            'branch_slug'      => $branch_slug ?? '',
            'shift_timing'=>$shift_timing
        ];

        

        $pdf = PDF::loadView('recieptPdf', $send_data);

        return $pdf->download(now()->timestamp . '_receipt.pdf');
    }
}
