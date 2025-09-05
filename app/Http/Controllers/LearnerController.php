<?php

namespace App\Http\Controllers;

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


class LearnerController extends Controller
{
    use LearnerQueryTrait;
    protected $learnerService;

    public function __construct(LearnerService $learnerService)
    {
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

            'plan_id' => 'required',
            'plan_type_id' => 'required',
            'plan_price_id' => 'required|numeric|min:0',

            'discount_amount' => 'nullable|numeric|min:0',
            'locker_amount' => [
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
    protected function dataUpdate()
    {
        Log::info('Starting dataUpdate function');

        $userUpdates = Learner::where('status', 1)->get();

        foreach ($userUpdates as $userUpdate) {
            $today = date('Y-m-d');
            $customerdatas = LearnerDetail::where('learner_id', $userUpdate->id)->where('status', 1)->get();

            $extend_day = getExtendDays();
            foreach ($customerdatas as $customerdata) {
                $planEndDateWithExtension = Carbon::parse($customerdata->plan_end_date)->addDays($extend_day);
               
                $current_date = Carbon::today();
                $hasFuturePlan = LearnerDetail::where('learner_id', $userUpdate->id)
                    ->where('plan_end_date', '>', $current_date->copy()->addDays(5))->where('status', 0)
                    ->exists();
                $hasPastPlan = LearnerDetail::where('learner_id', $userUpdate->id)
                    ->where('plan_end_date', '<=', $current_date->copy()->addDays(5))
                    ->exists();

                $isRenewed = $hasFuturePlan && $hasPastPlan;

                if ($planEndDateWithExtension->lte($today)) {
                    $userUpdate->update(['status' => 0]);
                    $customerdata->update(['status' => 0]);
                    \Log::info("Updated Learner ID {$userUpdate->id} and Customer Data ID {$customerdata->id} to status 0.");
                } elseif ($isRenewed) {
                    LearnerDetail::where('learner_id', $userUpdate->id)->where('plan_start_date', '<=', $today)->where('plan_end_date', '>', $current_date->copy()->addDays(5))->update(['status' => 1]);
                    LearnerDetail::where('learner_id', $userUpdate->id)->where('plan_end_date', '<', $today)->update(['status' => 0]);
                } else {
                    // $userUpdate->update(['status' => 1]);
                     Learner::where('id', $customerdata->learner_id)
                    ->where('status', '!=', 1)
                    ->update(['status' => 1]);
                    LearnerDetail::where('learner_id', $userUpdate->learner_id)
                        ->where('status', 0)
                        ->where('plan_start_date', '<=', $today)
                        ->where('plan_end_date', '>', $today)
                        ->update(['status' => 1]);
                    \Log::info("Updated Learner ID {$userUpdate->id} to status 1.");
                }
            }
        }
    }

    public function index()
    {
        $this->dataUpdate();
        $users = $this->getLearnersByLibrary()->where('learners.status', 1)->where('learner_detail.library_id', getLibraryId());

        $count_fullday = $this->getLearnersByLibrary()->leftJoin('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')->where('learner_detail.library_id', getLibraryId())->where('plan_types.day_type_id', 1)->where('learners.status', 1)->count();
        $count_firstH = $this->getLearnersByLibrary()->leftJoin('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')->where('learner_detail.library_id', getLibraryId())->where('plan_types.day_type_id', 2)->where('learners.status', 1)->count();
        $count_secondH = $this->getLearnersByLibrary()->leftJoin('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')->where('learner_detail.library_id', getLibraryId())->where('plan_types.day_type_id', 3)->where('learners.status', 1)->count();
        $count_hourly = $this->getLearnersByLibrary()->leftJoin('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')->where('learner_detail.library_id', getLibraryId())->whereIn('plan_types.day_type_id', [4, 5, 6, 7])->where('learners.status', 1)->count();

        $not_available = getUnavailableSeatCount();
        $available = getAvailableSeatCount();
        $availableseats = $this->learnerService->getAvailableSeats();

        return view('learner.seat', compact('availableseats', 'users',  'count_fullday', 'count_firstH', 'count_secondH', 'available', 'not_available', 'count_hourly'));
    }
    //learner store seat and without seat
    public function learnerStore(Request $request)
    {
        
        $additionalRules = [
            'payment_mode'     => 'required',
            'plan_start_date'  => 'required|date',
            'paid_amount'      => 'required',

        ];

        if ($request->general_seat != 'yes') {
            $additionalRules['seat_no'] = 'required|integer';
        }

        $validator = $this->validateCustomer($request, $additionalRules);
        if ($validator->fails()) {

            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
            die;
        }

        $exists = Learner::where('branch_id', getCurrentBranch())
            ->get()
            ->filter(function ($learner) use ($request) {
                return $learner->email === strtolower(trim($request->input('email')));
            })
            ->isNotEmpty();

        if ($exists) {
            return response()->json([
                'error' => true,
                'message' => 'The email has already been taken.'
            ], 422);
            die;
        }



        $plan_id = $request->input('plan_id');
        $duration = Plan::where('id', $plan_id)->value('plan_id'); 
        $type = Plan::where('id', $plan_id)->value('type');        // This is the unit type: DAY, MONTH, YEAR, WEEK

        $start_date = Carbon::parse($request->input('plan_start_date'));
        $planPrice = (float) $request->input('plan_price_id', 0);
        $paid_amount = (float) $request->input('paid_amount', 0);
        $locker = (float) $request->input('locker_amount', 0);
        $discount = (float) $request->input('discount_amount', 0);
       
        $effectivePaid = $planPrice + $locker - $discount;
        $pending_amount =  $effectivePaid - $paid_amount;

        $first_record = Hour::first();

        $total_hour = $first_record ? $first_record->hour : null;

        if (PlanType::where('id', $request->plan_type_id)->count() > 0) {

            $hours = PlanType::where('id', $request->plan_type_id)->value('slot_hours');
        }

         switch (strtoupper($type)) {
            case 'DAY':
                $endDate = $start_date->copy()->addDays($duration);
                break;
            case 'WEEK':
                $endDate = $start_date->copy()->addWeeks($duration);
                break;
            case 'MONTH':
                $endDate = $start_date->copy()->addMonths($duration);
                break;
            case 'YEAR':
                $endDate = $start_date->copy()->addYears($duration);
                break;
            default:
                $endDate = $start_date; // fallback to start date if type is unknown
                break;
        }

        if ($request->seat_no) {

            if ($this->getLearnersByLibrary()->where('learners.seat_no', $request->seat_no)->where('plan_type_id', $request->plan_type_id)->where('learners.status', 1)->count() > 0) {

                return response()->json([
                    'error' => true,
                    'message' => 'This Plan Type Seat already booked'
                ], 422);
                die;
            }

             if (($this->getLearnersByLibrary()->where('learners.seat_no', $request->seat_no)->where('learner_detail.status', 1)->sum('hours') + $hours) > $total_hour) {

                return response()->json([
                    'error' => true,
                    'message' => 'You cannot select this plan because it conflicts with an existing booking. The seat is already reserved for the full library hours on the selected day, so we are unable to process this booking.'
                ], 422);
                die;
            }

            if(($this->getLearnersByLibrary()->where('learners.seat_no', $request->seat_no)->where('learner_detail.plan_start_date','>',Carbon::today())->exists())){
                if($this->checkPlanTypeSeatWise($request->seat_no,$request->plan_type_id)==false){
                     return response()->json([
                    'error' => true,
                    'message' => 'You cannot select this plan because it conflicts with an existing future booking. '
                ], 422);
                die;
                }
            }
        }
   

        if (($paid_amount > $effectivePaid) || ($paid_amount == 0)) {
            return response()->json([
                'error' => true,
                'message' => 'Paid amount is not valid',
            ], 422);
            die;
        }
        if (($pending_amount > 0) && (!$request->due_date)) {
            return response()->json([
                'error' => true,
                'message' => 'Due date is required',
            ], 422);
            die;
        }
      
        if ($request->payment_mode == 1 || $request->payment_mode == 2) {
            $is_paid = 1;
        } else {
            $is_paid = 0;
        }
         if ($request->input('payment_mode') == 3) {

            $pending_amount = $paid_amount;
            $paid_amount = 0;
        }

        $extendDay = getExtendDays();

        $inextendDate = Carbon::parse($endDate)->addDays($extendDay);
       if ($inextendDate > Carbon::today() && $start_date <= Carbon::today()) {
            $status = 1;
        } else {
            $status = 0;
        }


        if ($request->seat_no) {
            $seat_no = $request->input('seat_no');
        } else {
            $seat_no = null;
        }
       

        if ($request->hasFile('id_proof_file')) {
            $this->validate($request, ['id_proof_file' => 'mimes:webp,png,jpg,jpeg|max:200']);
            $id_proof_file = $request->id_proof_file;
            $id_proof_fileNewName = "id_proof_file" . time() . $id_proof_file->getClientOriginalName();
            $id_proof_file->move('public/uploade/', $id_proof_fileNewName);
            $id_proof_file = 'public/uploade/' . $id_proof_fileNewName;
        } else {
            $id_proof_file = null;
        }
        if ($request->hasFile('profile_picture')) {
            $this->validate($request, ['profile_picture' => 'mimes:webp,png,jpg,jpeg|max:200']);
            $profile_picture = $request->profile_picture;
            $profile_pictureNewName = "profile_picture" . time() . $profile_picture->getClientOriginalName();
            $profile_picture->move('public/uploade/', $profile_pictureNewName);
            $profile_picture = 'public/uploade/' . $profile_pictureNewName;
        } else {
            $profile_picture = null;
        }
        

        $customer = Learner::create([
            'seat_no' => $seat_no,
            'name' => $request->input('name'),
            'mobile' => encryptData($request->input('mobile')),
            'email' => $request->input('email') ? encryptData($request->input('email')) : null,
            'dob' => $request->input('dob'),
            'id_proof_name' => $request->input('id_proof_name'),
            'id_proof_file' => $id_proof_file,
            'hours' => $hours,
            'status' => $status,
            'library_id' => getLibraryId(),
            'password' => bcrypt($request->mobile),
            'branch_id' => getCurrentBranch(),
            'learner_no'=>$this->generateLearnerCode(),
            'father_name' => $request->input('father_name'),
            'alternate_mobile' => $request->input('alternate_mobile'),
            'remark' => $request->input('remark'),
            'profile_picture'=>$profile_picture,
            'address' => $request->input('address'),
            'locker_no'=>$request->input('locker_no') ?? null ,
        ]);

        $learner_detail = LearnerDetail::create([
            'learner_id' => $customer->id,
            'plan_id' => $plan_id,
            'plan_type_id' => $request->input('plan_type_id'),
            'plan_price_id' => $request->input('plan_price_id'),
            'plan_start_date' => $start_date->format('Y-m-d'),
            'plan_end_date' => $endDate->format('Y-m-d'),
            'join_date' =>  $start_date->format('Y-m-d'),
            'hour' => $hours,
            'library_id' => getLibraryId(),
            'is_paid' => 1,
            'status' => $status,
            'payment_mode' => $request->input('payment_mode'),
            'seat_no' => $seat_no,
            'branch_id' => getCurrentBranch(),
            'exam_id' => $request->input('exam_id') ?? null,
        ]);
        if ($request->paid_date) {
            $transaction_date = $request->paid_date;
        } else {
            $transaction_date =null;
        }
        $data=[];
        $data['planPrice']=$planPrice ;
        $data['paid_amount']=$paid_amount ;
        $data['locker']=$locker ;
        $data['discount']=$discount ;
        $data['start_date']=$start_date ;
        $data['paid_date']=$transaction_date ;
        $data['is_paid']=$is_paid ;
        $data['learner_detail_id']=$learner_detail->id ;
        $data['learner_id']=$customer->id ;
        $data['payment_type']='SEAT ASSIGNMENT' ;
        $data['payment_mode']=$request->input('payment_mode');
        $data['due_date']=$request->due_date ?? null;
       $this->learnerTransactionAddUpdate($data);

        if ($status == 1) {

            $this->dataUpdate();
        }

        return response()->json([
            'success' => true,
            'message' => 'Learner created successfully!',
        ], 201);
    }

    public function checkPlanTypeSeatWise($seatNo,$requestPlanType)
    {

            // Step 1: Retrieve all bookings for the given seat
            $bookings = $this->getLearnersByLibrary()
                ->join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')
                ->where('learner_detail.seat_no', $seatNo)
                ->where('learner_detail.plan_start_date','>',Carbon::today())
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
            $nightseatBooked = LearnerDetail::join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')->where('learner_detail.seat_no', $seatNo)->where('learner_detail.plan_start_date','>',Carbon::today())->where('plan_types.day_type_id', 9)->exists();

            if ($nightseatBooked) {
                $planTypeid = LearnerDetail::join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')->where('learner_detail.seat_no', $seatNo)->where('learner_detail.plan_start_date','>',Carbon::today())->where('plan_types.day_type_id', 9)->value('plan_types.id') ?? 0;
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
        $selectedbothId = LearnerDetail::where('id', $request->learner_detail_id)->select('learner_id','plan_id', 'plan_type_id', 'plan_price_id')->first();
        $transaction = LearnerTransaction::where('learner_detail_id', $request->learner_detail_id)->select('total_amount', 'locker_amount', 'discount_amount', 'paid_amount')->first();
        $learner=Learner::where('id',$selectedbothId->learner_id)->select('locker_no')->first();
        return response()->json([$filteredPlanTypes, $selectedPlanName, $selectedbothId, $transaction,$learner]);
    }
    public function getPrice(Request $request)
    {

        $plan_type_id = $request->plan_type_id;
        $plan_id = $request->plan_id;
        if ($request->plan_type_id && $request->plan_id) {
            $PlanpPrice = getPlanPrice($plan_id, $plan_type_id);
           
            return response()->json($PlanpPrice);
        }
    }
    //learner  change plan 
    public function changePlanUpdate(Request $request, $id = null)
    {
       
        $request->validate([
            'plan_type_id' => 'required|exists:plan_types,id',
            'plan_price_id' => 'required',
            'paid_amount' => 'required',
            'diffrence_amount' => 'required',
            'previous_amount' => 'required',
            'payment_mode' => 'required',
            'user_id' => 'required|exists:learners,id',
            'learner_detail' => 'required|exists:learner_detail,id',
             'discountType' => 'nullable',
            'discount_amount' => [
                'nullable',
                function ($attribute, $value, $fail) use ($request) {
                    if (!in_array($request->discountType, ['amount', 'percentage']) && $value) {
                        $fail('Discount type must be selected when providing a discount amount.');
                    }
                    if (in_array($request->discountType, ['amount', 'percentage']) && !$value) {
                        $fail('Discount amount is required when a discount type is selected.');
                    }
                }
            ],
            'locker_no' => [
                'nullable',
                 'required_if:locker,yes',
                'numeric'
            ],
        ]);

        $LearnerDetail = LearnerDetail::where('id', $request->learner_detail)->first();
        $customer = Learner::findOrFail($request->user_id);

         if (!Auth::user()->can('has-permission', 'Change Plan')) {
            return redirect()->back()->with('error', 'You do not have permission to renew the seat.');
        }

        //check plan type hours based on plan_type_id

        $planType = PlanType::find($request->plan_type_id);
        $startTime = $planType->start_time;
        $endTime = $planType->end_time;
        $hours = $planType->slot_hours;
        if ($customer->seat_no) {
            // Fetch existing bookings for the same seat
            $existingBookings = $this->getLearnersByLibrary()->where('learner_detail.seat_no', $customer->seat_no)
                ->where('learners.id', '!=', $customer->id) // Exclude the current booking
                ->where('learner_detail.status', 1)
                ->get();
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

            $total_cust_hour = Learner::where('seat_no', $customer->seat_no)->where('status', 1)->sum('hours');
            // Check if the selected plan type exceeds available hours
            if ($hours > ($total_hour - ($total_cust_hour - $customer->hours))) {
                return redirect()->back()->with('error', 'You cannot select this plan type as it exceeds the available hours.');
            }
        }

     

        $customer->hours = $hours;
        $customer->save();


        $locker=$request->input('locker_amount');
        if($request->payment_type){
            $payment_type=$request->payment_type;
        }else{
            $payment_type='UPGRADE';
        }
         if ($request->discountType == 'amount') {
            $discount = $request->discount_amount;
        } elseif ($request->discountType == 'percentage') {
            $total = $request->input('plan_price_id') + $locker;
            $discount = ($total * $request->discount_amount) / 100;
        } else {
            $discount = 0;
        }


        $learnerTransaction = LearnerTransaction::where('learner_detail_id', $request->learner_detail)->first();

        $effectivePaid = $request->input('plan_price_id') + $locker - $discount;  // new price
        $old_price      = $learnerTransaction->paid_amount ?? 0;
        $paid_amount    = $request->input('paid_amount'); 

        $pending_amount = $request->input('pending_amount');
        $diff_amount    = $request->input('diffrence_amount');

        
        $refund = 0;
        $pending_refund = 0;

        // Handle difference amount (refund vs pending)
        if ($diff_amount < 0) {
         
            // refund case
            $refund = abs($diff_amount);
            $pending_refund = abs($pending_amount);
            $pending_amount = 0;
            $dr_cr='Dr';
        } else {
           
            // extra payment (pending dues)
            $pending_amount = $pending_amount ?? 0;
            $refund = $diff_amount;
            $pending_refund = 0;
            $dr_cr='Cr';
        }
        if($pending_amount > 0){
            return redirect()->back()->with('error', 'Due date is required');
        }
        
        if ($learnerTransaction) {
            if ($request->locker == 'yes') {
                $learnerTransaction->locker_amount = $locker;
            }

            $learnerTransaction->total_amount   = $effectivePaid ?? $paid_amount;
            $learnerTransaction->paid_amount    = $paid_amount;
            $learnerTransaction->pending_amount = $pending_amount;
            $learnerTransaction->refund         = $pending_refund;   // keep refund only if negative diff
            $learnerTransaction->due_date       = $request->due_date ?? null;
            $learnerTransaction->discount_amount       =$discount; 
           
            $learnerTransaction->save();

            //learner Activity
            $data=[];
            $data['learner_id']=$customer->id;
            $data['particular']='Paid By Trans';
            $data['payment_type']='CHANGE PLAN';
            $data['payment_mode']=1;
            $data['amount']=$refund ?? 0;
            $data['dr_cr']=$dr_cr;
            $this->learnerTransactionActivity($data);

           
        }


        if ($LearnerDetail) {
            $LearnerDetail->plan_type_id = $request->input('plan_type_id');
            $LearnerDetail->plan_price_id = $request->input('plan_price_id');
            $LearnerDetail->payment_mode = $request->input('payment_mode');
            $LearnerDetail->hour = $hours;
            $LearnerDetail->save();
        }
           


        $this->dataUpdate();
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Learner updated successfully!',
            ], 200);
        } else {
            return redirect()->route('learners')->with('success', 'Learner updated successfully.');
        }
    }
 

    public function getPlanTypeSeatWise(Request $request)
    {

        $seatNo = $request->seatNo;
        if ($seatNo) {


            // Step 1: Retrieve all bookings for the given seat
            $bookings = $this->getLearnersByLibrary()
                ->join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')
                ->where('learner_detail.seat_no', $seatNo)
                ->where('learners.status', 1)
                ->where('learner_detail.status', 1)
                ->where('learners.branch_id', getCurrentBranch())
                ->where('learner_detail.branch_id', getCurrentBranch())
                ->get(['learner_detail.plan_type_id', 'plan_types.start_time', 'plan_types.end_time', 'plan_types.slot_hours']);

            // Step 2: Retrieve all plan types
            $planTypes = PlanType::get();

            // Step 3: Initialize an array to store the plan_type_ids to be removed
            $planTypesRemovals = [];

            // Step 4: Calculate total booked hours for the seat
            $totalBookedHours = $bookings->sum('slot_hours');

            $nightseatBooked = LearnerDetail::join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')->where('learner_detail.seat_no', $seatNo)->where('learner_detail.status', 1)->where('plan_types.day_type_id', 9)->exists();

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
            if ($nightseatBooked) {
                $planTypeid = LearnerDetail::join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')->where('learner_detail.seat_no', $seatNo)->where('learner_detail.status', 1)->where('plan_types.day_type_id', 9)->value('plan_types.id') ?? 0;
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
        } else {

            $first_record = Hour::where('branch_id', getCurrentBranch())->first();
            $total_hour = $first_record ? $first_record->hour : null;

            if ($total_hour < 24) {
                $filteredPlanTypes = PlanType::whereNotIn('day_type_id', [8, 9])
                    ->select('id', 'name')
                    ->get();
            } else {
                $filteredPlanTypes = PlanType::select('id', 'name')->get();
            }

        }

        // Return the filtered plan types as JSON
        return response()->json($filteredPlanTypes);
    }


    public function fetchCustomerData($customerId = null, $isRenew = false, $status, $detailStatus, $filters = [],$perPage = 10,$paginate = true)
    {

        $query = Learner::withTrashed()->leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
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
                $query->where('learners.status', $status)
                    ->where('learner_detail.status', $detailStatus);
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
        
        return $paginate
        ? $query->paginate($perPage)
        : $query->get();


    }

    public function fetchLearnerData($customerId = null, $isRenew = false, $status, $detailStatus, $filters = [],$perPage = 5,$paginate = true)
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
        );


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
    public function learnerList(Request $request)
    {

        $filters = [
            'plan_id' => $request->get('plan_id'),
            'is_paid' => $request->get('is_paid'),
            'status'  => $request->get('status'),
            'search'  => $request->get('search'),
            'seat_no'  => $request->get('seat_no'),
        ];

        $learners = $this->fetchCustomerData(null, false, 1, 1, $filters,$perPage = 15,$paginate = true);

        return view('learner.learner', compact('learners'));
    }
    public function learnerSearch(Request $request)
    {

        $filters = [
            'search'  => $request->get('search'),
        ];
        
        if($filters){
            $paginate = true;
        }else{
            $paginate = false;
        }

        $learners = $this->fetchLearnerData(null, false, 1, 1, $filters,$perPage = 5,$paginate);

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

        $query = Learner::withTrashed()->leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
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
                    ->where('learner_detail.status',0);
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
                ->where('learner_detail.status',0);
        }
      
        $learnerHistory =   $query->paginate($perPage);


       
        return view('learner.learnerHistory', compact('learnerHistory'));
    }

    //learner  Upgrade 
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


        $this->dataUpdate();
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Learner updated successfully!',
            ], 200);
        } else {
            return redirect()->route('learners')->with('success', 'Learner updated successfully.');
        }
    }
    //learner  Upgrade and renew store
    public function learnerUpgradeRenew(Request $request)
    {
      
        $rules = [

            'plan_id' => 'required',
            'plan_type_id' => 'required|exists:plan_types,id',
            'plan_price_id' => 'required',
            'payment_mode' => 'required',
            'user_id' => 'required',
            'discountType' => 'nullable',
            'discount_amount' => [
                'nullable',
                function ($attribute, $value, $fail) use ($request) {
                    if (!in_array($request->discountType, ['amount', 'percentage']) && $value) {
                        $fail('Discount type must be selected when providing a discount amount.');
                    }
                    if (in_array($request->discountType, ['amount', 'percentage']) && !$value) {
                        $fail('Discount amount is required when a discount type is selected.');
                    }
                }
            ],
            'locker_no' => [
                'nullable',
                 'required_if:locker,yes',
                'numeric'
            ],

        ];
         
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
         if ($request->ajax() && $validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        if($request->payment_type=='RENEW'){
             if (!Auth::user()->can('has-permission', 'Renew Seat')) {
                return redirect()->back()->with('error', 'You do not have permission to renew the seat.');
            }
        }
       

        $currentDate = date('Y-m-d');
        DB::beginTransaction();

        try {
            $customer = Learner::findOrFail($request->user_id);
            if (!$customer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Learner not found.'
                ], 404);
            }

            $months = Plan::where('id', $request->plan_id)->value('plan_id');
            $duration = $months ?? 0;
            if ($request->learner_detail) {
                $learner_detail = LearnerDetail::where('id', $request->learner_detail)->first();
            } else {
                $learner_detail = LearnerDetail::where('learner_id', $request->user_id)->orderBy('id', 'DESC')->first();
            }
            // Determine hours based on plan_type_id

            $planType = PlanType::find($request->plan_type_id);
            $startTime = $planType->start_time;
            $endTime = $planType->end_time;
            $hours = $planType->slot_hours;
            if ($customer->seat_no) {
                // Fetch existing bookings for the same seat
                $existingBookings = $this->getLearnersByLibrary()->where('learner_detail.seat_no', $customer->seat_no)
                    ->where('learners.id', '!=', $customer->id) // Exclude the current booking
                    ->where('learner_detail.status', 1)
                    ->get();
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

                $total_cust_hour = Learner::where('seat_no', $customer->seat_no)->where('status', 1)->sum('hours');
                // Check if the selected plan type exceeds available hours
                if ($hours > ($total_hour - ($total_cust_hour - $customer->hours))) {
                    return redirect()->back()->with('error', 'You cannot select this plan type as it exceeds the available hours.');
                }
            }


            $plan_type = $request->plan_type_id;
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

            if ($request->paid_date) {
                $transaction_date = $request->paid_date;
            } else {
                $transaction_date =null;
            }
           
            $locker = (float) $request->input('locker_amount', 0);
            if ($request->discountType == 'amount') {
                $discount = $request->discount_amount;
            } elseif ($request->discountType == 'percentage') {
                $total = $request->input('plan_price_id') + $locker;
                $discount = ($total * $request->discount_amount) / 100;
            } else {
                $discount = 0;
            }
            $planPrice = (float) $request->input('plan_price_id', 0);
            $paid_amount = (float) $request->input('paid_amount', 0);
            $effectivePaid = $planPrice + $locker - $discount;
            $pending_amount =  $effectivePaid - $paid_amount;
            
            if (($paid_amount > $effectivePaid) || ($paid_amount == 0) && $request->expectsJson()) {
                return response()->json([
                    'error' => true,
                    'message' => 'Paid amount is not valid',
                ], 422);
                die;
            }
            if (($pending_amount > 0) && (!$request->due_date)  && $request->expectsJson()) {
                return response()->json([
                    'error' => true,
                    'message' => 'Due date is required',
                ], 422);
                die;
            }
             if (($paid_amount > $effectivePaid) || ($paid_amount == 0)){
                return redirect()->back()->with('error', 'Paid amount is not valid');
             }
            if (($pending_amount > 0) && (!$request->due_date)) {
               return redirect()->back()->with('error', 'Due date is required');
            }
            
            $learner_detail = LearnerDetail::create([
                'library_id' => $customer->library_id,
                'branch_id' => getCurrentBranch(),
                'learner_id' => $customer->id,
                'plan_id' => $request->input('plan_id'),
                'plan_type_id' => $request->input('plan_type_id'),
                'plan_price_id' => $request->input('plan_price_id'),
                'plan_start_date' => $start_date->format('Y-m-d'),
                'plan_end_date' => $endDate->format('Y-m-d'),
                'join_date' => $learner_detail->join_date,
                'hour' => $hours,
                'seat_no' => $learner_detail->seat_no,
                'status' => $status,
                'is_paid' => $is_paid,
                'payment_mode' => $payment_mode,
            ]);

            if($request->payment_type){
                $payment_type=$request->payment_type;
            }elseif ($request->expectsJson()){
                $payment_type='RENEW';
            }else{
                $payment_type='UPGRADE';
            }

            $data=[];
            $data['planPrice']=$planPrice ;
            $data['paid_amount']=$paid_amount ;
            $data['locker']=$locker ;
            $data['discount']=$discount ;
            $data['start_date']=$start_date ;
            $data['paid_date']=$transaction_date ;
            $data['is_paid']=$is_paid ;
            $data['learner_detail_id']=$learner_detail->id ;
            $data['learner_id']=$customer->id ;
            $data['payment_type']=$payment_type ;
            $data['payment_mode']=$payment_mode;
            $data['due_date']=$request->due_date ?? null ;
            $this->learnerTransactionAddUpdate($data);

            if ($status == 1) {
                $customer->hours = $hours;
               LearnerDetail::where('learner_id', $customer->id)
                ->where('id', '!=', $learner_detail->id)
                ->update(['status' => 0]);
            }
            $customer->save();
            DB::commit();
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Learner Renew successfully!',
                ], 200);
            } else {
                return redirect()->route('learners')->with('success', 'Learner updated successfully!');
            }
        } catch (\Exception $e) {
            DB::rollBack(); // Something went wrong, rollback

            return redirect()->back()->with('error', 'Something went wrong: ' . $e->getMessage());
        }
    }
    public function getUser(Request $request, $id = null)
    {

        $customerId = $request->id ?? $id;
        $is_renew = $this->learnerService->getRenewalStatus($customerId);


        $available_seat = $this->learnerService->getAvailableSeats();

        $customer = $this->fetchCustomerData($customerId, $is_renew, $status = 1, $detailStatus = 1,$perPage = 10,$paginate = false);

        if ($request->expectsJson() || $request->has('id')) {
            return response()->json($customer);
        } else {
            return view('learner.learnerEdit', compact('customer', 'available_seat'));
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
        $customer_status = learner::where('id', $customerId)->first();
     
        $status = $customer_status->status;
        $detailStatus = $customer_status->status;
        $customer = $this->fetchCustomerData($customerId, $is_renew, $status, $detailStatus);

        //renew History
        $renew_detail = LearnerDetail::where('learner_detail.learner_id', $customerId)
            ->with(['plan', 'planType'])
            ->get();
            
        //seat history

        if($customer->seat_no){
            $seat_history = $this->getAllLearnersByLibrary()
            ->where('seat_no', $customer->seat_no)
            ->where('id', '!=', $customerId)
            ->get();
        }else{
            $seat_history=null;
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

        $is_renew = $hasFuturePlan && $hasPastPlan;


        $available_seat = $this->learnerService->getAvailableSeats();

        $customer = $this->fetchCustomerData($customerId, $is_renew, $status = 1, $detailStatus = 1,$perPage = 10,$paginate = false);
        $customer_detail = LearnerDetail::where('learner_id', $customerId)->orderBy('id', 'Desc')->first();

        $oneWeekLater = Carbon::parse($customer->plan_start_date)->addWeek();
        $showButton = Carbon::now()->greaterThanOrEqualTo($oneWeekLater);
        $plantype = PlanType::get();
        $otherPlantype = LearnerDetail::where('learner_id', '!=', $customerId)
            ->where('seat_no', $customer_detail->seat_no)
            ->where('status', 1)
            ->pluck('plan_type_id');

        if ($customer_detail->seat_no) {
           $filteredPlanTypes =filterPlantypeFromseat($customer_detail->seat_no,$customerId);
        } else {
            $filteredPlanTypes = PlanType::select('id', 'name')->get();
        }

        

        return view('learner.renewUpgrade', compact('customer',  'available_seat', 'showButton', 'is_renew', 'filteredPlanTypes', 'isalreadyRenew'));
       
    }
    public function getSwapUser($id)
    {

        $customerId = $id;
        $firstRecord = Hour::first();
        $totalHour = $firstRecord ? $firstRecord->hour : null;

        $customer = $this->fetchCustomerData($customerId, false, $status = 1, $detailStatus = 1,$perPage = 10,$paginate = false);

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
            $learners = Learner::withTrashed()
                ->leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
                ->where('learners.branch_id', getCurrentBranch())
                ->where('learner_detail.seat_no', $seatNo)
                ->select('learners.*', 'learner_detail.*')
                ->get();

            $activeLearners = $learners->where('status', 1);
            $expiredLearners = $learners->where('status', 0);

            $seat = new \stdClass();
            $seat->seat_no = $seatNo;
            $seat->learners = $activeLearners->isNotEmpty() ? $activeLearners : ($expiredLearners->isNotEmpty() ? $expiredLearners : collect());

            if ($activeLearners->isNotEmpty()) {
                $seat->status = 'booked';
            } elseif ($expiredLearners->isNotEmpty()) {
                $seat->status = 'expired';
            } else {
                $seat->status = 'available';
            }

            $seats[] = $seat;
        }


        $generalLearners = Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
            ->where('learners.branch_id', getCurrentBranch())
            ->whereNull('learner_detail.seat_no')
            ->select('learners.*', 'learner_detail.*')
            ->get();

        $activeGeneral = $generalLearners->where('status', 1);
        $expiredGeneral = $generalLearners->where('status', 0)->take(1); 
        $finalGeneralLearners = $activeGeneral->isNotEmpty() ? $activeGeneral : $expiredGeneral;
       
         
        return view('learner.seatHistory', [ 'seats' => $seats,'finalGeneralLearners'=>$finalGeneralLearners]);
    }
    // public function history($id)
    // {
    //     // Get the learners with their details, plans, and seat information
    //     $learners = Learner::where('library_id', getLibraryId())
    //         ->with([
    //             'learnerDetails' => function ($query) {
    //                 $query->with(['plan', 'planType']);
    //             }
    //         ])
    //         ->whereHas('learnerDetails', function ($query) use ($id) {
    //             $query->where('learner_id', $id)->where('learner_detail.status', 0);
    //         })

    //         ->get();

    //     return view('learner.seatHistoryView', compact('learners'));
    // }
    public function history($id)
    {
        $learners = LearnerDetail::where('branch_id', getCurrentBranch())->where('seat_no', $id)->where('learner_detail.status', 0)->with(['plan', 'planType'])
        // ->get();
             
           ->paginate(5);
            
        return view('learner.seatHistoryView', compact('learners'));
    }
    public function generalSeathistory()
    {
        // Get the learners with their details, plans, and seat information
        $learners = Learner::where('library_id', getLibraryId())->where('learners.status', 0)
        ->whereNull('learners.seat_no')
            ->with([
                'learnerDetails' => function ($query) {
                    $query->with(['plan', 'planType']);
                }
            ])
            ->whereHas('learnerDetails', function ($query) {
                $query->whereNull('seat_no')->where('learner_detail.status', 0);
            })
            
            ->get();
            
        return view('learner.genaralSeatHistoryView', compact('learners'));
    }
    public function reactiveUser(Request $request, $id = null)
    {
        
        $customerId = $request->id ?? $id;
        
        $is_renew = $this->learnerService->getRenewalStatus($customerId);
        $available_seat = $this->learnerService->getAvailableSeats();
      
        $customer = $this->fetchCustomerData($customerId, false, $status = 0, $detailStatus = 0,$perPage = 10,$paginate = false);
        
        $customer_detail = LearnerDetail::withTrashed()->where('learner_id', $customerId)->orderBy('id', 'Desc')->first();
     
        if ($request->expectsJson() || $request->has('id')) {

            return response()->json($customer);
        } else {

            return view('learner.learnerEdit', compact('customer', 'available_seat'));
        }
    }

    public function swapSeat(Request $request)
    {
        try {
            DB::transaction(function () use ($request) {

                $customer = $this->getLearnersByLibrary()->where('learners.id', $request->learner_id)->select('learners.id as id', 'learners.*', 'learner_detail.plan_type_id', 'learner_detail.seat_no')->first();

                $newSeatId = $request->seat_id;

                $first_record = Hour::first();
                $total_hour = $first_record ? $first_record->hour : null;

                $newSeatNo = $request->seat_id;

                $total_cust_hour = Learner::where('library_id', getLibraryId())->where('seat_no', $newSeatNo)->sum('hours');
                $new_seat_remainig = $total_hour - $total_cust_hour;

                if ($request->seat_id && ($customer->hours > $new_seat_remainig)) {
                    throw new Exception('Not available according to your hours.');
                } elseif (
                    $this->getLearnersByLibrary()->where('learner_detail.seat_no', $newSeatNo)
                    ->where('plan_type_id', $customer->plan_type_id)
                    ->where('learners.status', 1)
                    ->where('learner_detail.status', 1)
                    ->count() > 0 && $request->seat_id
                ) {
                    throw new Exception('The new seat is not available for your plan type.');
                } else {

                    // Update the learner's seat_id and seat_no
                    $data = Learner::findOrFail($request->learner_id);
                    $data->seat_no = $newSeatNo;
                    $data->save();
                    $learner_detail = LearnerDetail::where('learner_id', $request->learner_id)->update([
                        'seat_no' => $newSeatId,
                    ]);
                }
            });


            return redirect()->route('learners')->with('success', 'Seat swapped successfully!');
        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Seat swap failed: ' . $e->getMessage());
        }
    }
    public function reactiveLearner(Request $request, $id)
    {
        

         $rules = [

            'plan_id' => 'required',
            'seat_no' => 'nullable',
            'plan_type_id' => 'required',
            'plan_price_id' => 'required',
            'plan_start_date' => 'required',
            'user_id' => 'required',
            'learner_detail' => 'required',
            'payment_mode' => 'required',
            'discountType' => 'nullable',
            'discount_amount' => [
                'nullable',
                function ($attribute, $value, $fail) use ($request) {
                    if (!in_array($request->discountType, ['amount', 'percentage']) && $value) {
                        $fail('Discount type must be selected when providing a discount amount.');
                    }
                    if (in_array($request->discountType, ['amount', 'percentage']) && !$value) {
                        $fail('Discount amount is required when a discount type is selected.');
                    }
                }
            ],
             'locker_no' => [
                'nullable',
                 'required_if:locker,yes',
                'numeric'
            ],

        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        if (!Auth::user()->can('has-permission', 'Renew Seat')) {
            return redirect()->back()->with('error', 'You do not have permission to renew the seat.');
        }


        DB::beginTransaction();

        try {
            // for log value

            $old_value = LearnerDetail::withTrashed()
                ->where('id', $request->learner_detail)
                ->first();

            // for log value
           $customer = Learner::withTrashed()
                ->findOrFail($request->user_id);

            $start_date = Carbon::parse($request->input('plan_start_date'));
            $plan_id = $request->input('plan_id');
            $duration = Plan::where('id', $plan_id)->value('plan_id'); 
            $type = Plan::where('id', $plan_id)->value('type'); 
            $paid_amount = (float) $request->input('paid_amount', 0);
            $locker = (float) $request->input('locker_amount', 0);
            $discount = (float) $request->input('discount_amount', 0);
            $planPrice = (float) $request->input('plan_price_id', 0);
            $effectivePaid = $planPrice + $locker - $discount;
            $pending_amount =  $effectivePaid - $paid_amount;
            $planType = PlanType::find($request->plan_type_id);
            $startTime = $planType->start_time;
            $endTime = $planType->end_time;
            $hours = $planType->slot_hours;
           
            $first_record = Hour::first();
            $total_hour = $first_record ? $first_record->hour : 0;

            $months = Plan::where('id', $request->plan_id)->value('plan_id');
            $duration = $months ?? 0;
            $start_date = Carbon::parse($request->input('plan_start_date'));
            $endDate = $start_date->copy()->addMonths($duration);
            switch (strtoupper($type)) {
                case 'DAY':
                    $endDate = $start_date->copy()->addDays($duration);
                    break;
                case 'WEEK':
                    $endDate = $start_date->copy()->addWeeks($duration);
                    break;
                case 'MONTH':
                    $endDate = $start_date->copy()->addMonths($duration);
                    break;
                case 'YEAR':
                    $endDate = $start_date->copy()->addYears($duration);
                    break;
                default:
                    $endDate = $start_date; 
                    break;
            }
            
            $existingBookings = $this->getLearnersByLibrary()
            ->where('learner_detail.seat_no', '=', $request->seat_no)
            ->where('learner_detail.plan_type_id', '=', $request->plan_type_id)
            ->where('learners.status', 1)
            ->where('learner_detail.status', 1)
            ->where(function ($q) use ($start_date, $endDate) {
                $q->where('learner_detail.plan_start_date', '<=', $endDate)
                ->where('learner_detail.plan_end_date', '>=', $start_date);
            })
          
            ->exists();

     
           if($existingBookings){
            return redirect()->back()->with('error', 'You can not select this plan type it is already booked for this Seat.');
           }
           

            if ($total_hour === 0) {
                return redirect()->back()->with('error', 'Total available hours not set.');
            }

            $total_cust_hour = Learner::where('seat_no', $request->seat_no)->where('status', 1)->sum('hours');
           

            if ($hours > ($total_hour - $total_cust_hour)) {

                return redirect()->back()->with('error', 'You cannot select this plan type as it exceeds the available hours.');
            }

          

            $extendDay = getExtendDays();

            $inextendDate = Carbon::parse($endDate)->addDays($extendDay);
            if ($inextendDate > Carbon::today() && $start_date <= Carbon::today()) {
                $status = 1;
            } else {
                $status = 0;
            }
            if ($request->payment_mode == 1 || $request->payment_mode == 2) {
                $is_paid = 1;
                $payment_mode = $request->payment_mode;
            } else {
                $is_paid = 0;
                $payment_mode = 3;
            }
            if ($request->input('payment_mode') == 3) {

                $pending_amount = $paid_amount;
                $paid_amount = 0;
            }
            if ($request->seat_no) {
                $seat_no = $request->input('seat_no');
            } else {
                $seat_no = null;
            }

          

            if ($request->seat_no) {

                if ($this->getLearnersByLibrary()->where('learners.seat_no', $request->seat_no)->where('plan_type_id', $request->plan_type_id)->where('learners.status', 1)->count() > 0) {

                    return response()->json([
                        'error' => true,
                        'message' => 'This Plan Type Seat already booked'
                    ], 422);
                    die;
                }

                if (($this->getLearnersByLibrary()->where('learners.seat_no', $request->seat_no)->where('learner_detail.status', 1)->sum('hours') + $hours) > $total_hour) {

                    return response()->json([
                        'error' => true,
                        'message' => 'You cannot select this plan because it conflicts with an existing booking. The seat is already reserved for the full library hours on the selected day, so we are unable to process this booking.'
                    ], 422);
                    die;
                }

                if(($this->getLearnersByLibrary()->where('learners.seat_no', $request->seat_no)->where('learner_detail.plan_start_date','>',Carbon::today())->exists())){
                    if($this->checkPlanTypeSeatWise($request->seat_no,$request->plan_type_id)==false){
                        return response()->json([
                        'error' => true,
                        'message' => 'You cannot select this plan because it conflicts with an existing future booking. '
                    ], 422);
                    die;
                    }
                }
            }
   

            if (($paid_amount > $effectivePaid) || ($paid_amount == 0)) {
                return response()->json([
                    'error' => true,
                    'message' => 'Paid amount is not valid',
                ], 422);
                die;
            }
            if (($pending_amount > 0) && (!$request->due_date)) {
                return response()->json([
                    'error' => true,
                    'message' => 'Due date is required',
                ], 422);
                die;
            }

            $customer->seat_no = $seat_no;
            $customer->hours = $hours;
            $customer->status = $status;
            if ($customer->trashed()) {
                $customer->restore();
            }
            if (!$customer->save()) {
                throw new \Exception('Failed to update customer');
            }
           
            $learner_detail = LearnerDetail::create([
                'library_id' => getLibraryId(),
                'branch_id' => getCurrentBranch(),
                'learner_id' => $customer->id,
                'plan_id' => $plan_id,
                'plan_type_id' => $request->input('plan_type_id'),
                'plan_price_id' => $request->input('plan_price_id'),
                'plan_start_date' => $start_date->format('Y-m-d'),
                'plan_end_date' => $endDate->format('Y-m-d'),
                'join_date' => $start_date->format('Y-m-d'),
                'hour' => $hours,
                'seat_no' => $seat_no,
                'payment_mode' => $payment_mode,
                'is_paid' =>1,
                'status' => $status,
            ]);
            
            // learner log table update
            DB::table('learner_operations_log')->insert([
                'learner_id' => $customer->id,
                'learner_detail_id' => $learner_detail->id,
                'library_id' => $customer->library_id,
                'field_updated' => 'seat_no',
                'old_value' => $old_value->seat_no,
                'new_value' => $request->seat_no,
                'updated_by' => getLibraryId(),
                'branch_id' =>  getCurrentBranch(),
                'operation' => 'reactive',
                'created_at' => now(),
            ]);

                if ($request->paid_date) {
                    $transaction_date = $request->paid_date;
                } else {
                    $transaction_date =null;
                }
                $data=[];
                $data['planPrice']=$planPrice ;
                $data['paid_amount']=$paid_amount ;
                $data['locker']=$locker ;
                $data['discount']=$discount ;
                $data['start_date']=$start_date ;
                $data['paid_date']=$transaction_date ;
                $data['is_paid']=$is_paid ;
                $data['learner_detail_id']=$learner_detail->id ;
                $data['learner_id']=$customer->id ;
                $data['payment_type']='REACTIVE' ;
                $data['payment_mode']=$request->input('payment_mode');
                $data['due_date']=$request->due_date ?? null;
            $this->learnerTransactionAddUpdate($data);
                if ($status == 1) {

                    $this->dataUpdate();
                }


            DB::commit();

            return redirect()->route('learnerHistory')->with('success', 'Learner updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'An error occurred: ' . $e->getMessage());
        }
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
            ->where('is_paid', 1)
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
    public function getSeatStatus(Request $request)
    {

        $count = $this->getLearnersByLibrary()
            ->where('learner_detail.seat_no', $request->new_seat_id)
            ->where('learners.status', 1)
            ->where('learner_detail.status', 1)
            ->where('learner_detail.plan_type_id', $request->plan_type_id)
            ->count();

        $customer = Learner::where('id', $request->user_id)
            ->where('status', 1)
            ->first();

        $first_record = Hour::first();
        $total_hour = $first_record ? $first_record->hour : null;

        $total_cust_hour = Learner::where('library_id', getLibraryId())->where('seat_no', $request->new_seat_id)->sum('hours');
        $new_seat_remaining = $total_hour - $total_cust_hour;


        $bookings = $this->getLearnersByLibrary()
            ->join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')
            ->where('learner_detail.seat_no', $request->new_seat_id)
            ->where('learners.status', 1)
            ->where('learner_detail.status', 1)
            ->get(['learner_detail.plan_type_id', 'plan_types.start_time', 'plan_types.end_time', 'plan_types.slot_hours']);

        $planType = PlanType::where('id', $request->plan_type_id)->first();

        $status_array = [];
       
        foreach ($bookings as $booking) {

            if ($booking->start_time < $planType->end_time && $booking->end_time > $planType->start_time) {
                $status_array[] = 0;
            } else {
                $status_array[] = 1;
            }
        }

        if ($customer->hours > $new_seat_remaining) {
            $status = 0;
        } elseif ($count == 1) {
            $status = 0;
        } elseif (in_array(0, $status_array)) {
            $status = 0;
        } elseif ($count == 0) {
            $status = 1;
        } else {
            $status = 1;
        }

        return response()->json($status);
    }

   public function destroy(Request $request, $id)
    {
       
        try {
             DB::transaction(function () use ($request, $id) {

                $customer = Learner::findOrFail($id);


                $lastLearnerDetail = LearnerDetail::where('learner_id', $customer->id)->where('id',$request->learnerDetail)->first();
                if (!$lastLearnerDetail) {
                    throw new Exception("No LearnerDetail found for learner ID: {$customer->id}");
                }
                if ($lastLearnerDetail) {
                     if ($request->isRefund && $request->refundAmount > 0) {
                         LearnerTransaction::where('learner_detail_id', $lastLearnerDetail->id)->update([
                            'refund'=> $request->pendingRefund
                         ]);
                            
                        }
                        if($request->remark){
                            $customer->remark =  $request->remark;
                        }
                    // Delete associated LearnerTransaction records
                    LearnerTransaction::where('learner_detail_id', $lastLearnerDetail->id)->delete();
                    $lastLearnerDetail->status=0;
                    $lastLearnerDetail->save();
                    $lastLearnerDetail->delete();
                    $customer->status = 0;
                    
                    $customer->save();
                    $customer->delete();
                    if ($request->isRefund && $request->refundAmount > 0){
                        $data=[];
                        $data['learner_id']=$id;
                        $data['particular']='Delete Plan';
                        $data['payment_type']='REFUND';
                        $data['payment_mode']=1;
                        $data['amount']=$request->refundAmount ?? 0;
                        $data['dr_cr']='Dr';
                        $this->learnerTransactionActivity($data);
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


                $lastLearnerDetail = LearnerDetail::where('id',$request->learnerDetail)->exists();
                if (!$lastLearnerDetail) {
                    throw new Exception("No LearnerDetail found for learner ID: {$customer->id}");
                }
                if ($lastLearnerDetail) {
                     if ($request->isRefund && $request->refundAmount > 0) {
                         LearnerTransaction::where('learner_detail_id', $request->learnerDetail)->update([
                            'refund'=> $request->pendingRefund
                         ]);
                            
                        }
                        if($request->remark){
                            $customer->remark =  $request->remark;
                        }
                    // Delete associated LearnerTransaction records
                    LearnerDetail::where('id', $request->learnerDetail)->update([
                        'plan_end_date' => $today, 'status' => 0
                    ]);

                    $customer->status = 0;
                    $customer->save();
                    if ($request->isRefund && $request->refundAmount > 0){
                        $data=[];
                        $data['learner_id']=$request->learner_id;
                        $data['particular']='Close Plan';
                        $data['payment_type']='REFUND';
                        $data['payment_mode']=1;
                        $data['amount']=$request->refundAmount ?? 0;
                        $data['dr_cr']='Dr';
                        $this->learnerTransactionActivity($data);
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

        $customer = LearnerDetail::where('id', $customer_detail_id)->with('learner', 'plan', 'plantype')->first();
        $is_payment_pending = LearnerTransaction::where('learner_detail_id', $customer_detail_id)
            ->where('pending_amount', '!=', 0)
            ->exists();
        $pending_payment = LearnerTransaction::where('learner_detail_id', $customer_detail_id)
            ->where('pending_amount', '!=', 0)->select('pending_amount', 'id')->first();

        return view('learner.payment', compact('customer',  'isRenew', 'is_payment_pending', 'pending_payment'));
    }

   public function paymentStore(Request $request)
    {
        $this->validate($request, [
            'learner_id'   => 'required|exists:learners,id',
            'paid_amount'  => 'required|numeric|min:1',
            'payment_mode' => 'required'
        ]);

        $tranDetail = LearnerTransaction::find($request->learner_transaction_id);

        if (!$tranDetail) {
            return redirect()->route('learners')->with('error', 'Transaction not found.');
        }
        $due_date=null;

        // ✅ Call reusable function
        $this->updateLearnerTransactionPayment($tranDetail, $request->paid_amount, $request->payment_mode,$due_date);

        return redirect()->route('learners')->with('success', 'Payment successfully recorded.');
    }


    public function learnerExpire(Request $request, $id = null)
    {

        $customerId = $request->id ?? $id;
        $is_renew = $this->learnerService->getRenewalStatus($customerId);

        $available_seat = $this->learnerService->getAvailableSeats();

        $customer = $this->fetchCustomerData($customerId, $is_renew, $status = 1, $detailStatus = 1,$perPage = 10,$paginate = true);

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
     

        $this->dataUpdate();
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
    //learner  update
    public function learnerUpdate(Request $request, $id = null)
    {

        $learner = Learner::find($id);

        // Call validateCustomer method to apply default validation
        $validator = $this->validateCustomer($request);

        $validator = Validator::make($request->all(), array_merge($validator->getRules(), [
            'email' => [
                'required',
                'email',
                Rule::unique('learners')->where(function ($query) use ($request) {
                    return $query->where('library_id', getLibraryId());
                })->ignore($learner->id), // Ignore current learner's email
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

        // Determine user_id based on $id or request input
        $user_id = $id ?: $request->input('user_id');


        $customer = Learner::findOrFail($user_id);
        
        // Handle the profile picture upload
        if ($request->hasFile('profile_picture')) {
            $this->validate($request, ['profile_picture' => 'mimes:webp,png,jpg,jpeg|max:200']);
            $profile_picture = $request->profile_picture;
            $profile_pictureNewName = "profile_picture" . time() . $profile_picture->getClientOriginalName();
            $profile_picture->move('public/uploade/', $profile_pictureNewName);
            $profile_picturePath = 'public/uploade/' . $profile_pictureNewName;
            $customer->profile_picture = $profile_picturePath;
        }
        
        // Handle the id proof file upload
        if ($request->hasFile('id_proof_file')) {
            $id_proof_file = $request->file('id_proof_file');
            $id_proof_fileNewName = "id_proof_file_" . time() . "_" . $id_proof_file->getClientOriginalName();

            // Store the file in the 'public/uploads' directory
            $id_proof_file->move(public_path('uploads'), $id_proof_fileNewName);
            $id_proof_filePath = 'uploads/' . $id_proof_fileNewName;

            // Set the path in the customer model
            $customer->id_proof_file = $id_proof_filePath;
        }

        // Update customer details
        $customer->name = $request->input('name', $customer->name);
        $customer->email = encryptData($request->input('email', $customer->email));
        $customer->mobile = encryptData($request->input('mobile', $customer->mobile));
        $customer->dob = $request->input('dob', $customer->dob);
        $customer->father_name = $request->input('father_name', $customer->father_name);
        $customer->alternate_mobile = $request->input('alternate_mobile', $customer->alternate_mobile);
        $customer->address = $request->input('address', $customer->address);
        $customer->remark = $request->input('remark', $customer->remark);
        $customer->id_proof_name = $request->input('id_proof_name', $customer->id_proof_name);

        // Save the customer details
        $customer->save();
        
        // Update exam_id in learner_detail table if provided
        if ($request->has('exam_id')) {
            $learnerDetail = LearnerDetail::where('learner_id', $customer->id)
                ->where('status', 1)
                ->first();
            
            if ($learnerDetail) {
                $learnerDetail->exam_id = $request->input('exam_id');
                $learnerDetail->save();
            }
        }
        return redirect()->route('learners')->with('success', 'Learner updated successfully.');
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
        $learner = LearnerDetail::withoutGlobalScopes()->where('learner_id', getLibraryId())->where('learner_detail.status', 1)->leftJoin('plans', 'learner_detail.plan_id', '=', 'plans.id')->leftJoin('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')->select('learner_detail.*', 'plan_types.name as plan_type_name', 'plans.name as plan_name', 'plan_types.start_time', 'plan_types.end_time')->first();

        return view('learner.profile', compact('learner'));
    }

    public function learnerRequest()
    {
        $learner_request = DB::table('learner_request')->where('learner_id', getLibraryId())->get();
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
                ->leftJoin('attendances', function ($join) use ($request) {
                    $join->on('learners.id', '=', 'attendances.learner_id')
                        ->whereDate('attendances.date', '=', $request->date);
                })
                ->where('learners.status', 1)
                ->select('learners.*', 'learner_detail.*', DB::raw('COALESCE(attendances.attendance, 2) as attendance'))
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
        $data = Learner::where('library_id', getLibraryId())
            ->where('status', 1)
            ->pluck('name', 'id');

        // Base Query
        $learners = Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
            ->leftJoin('attendances', 'learners.id', '=', 'attendances.learner_id')
            ->leftJoin('plans', 'learner_detail.plan_id', '=', 'plans.id')
            ->leftJoin('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')
            ->where('learners.library_id', getLibraryId())
            ->where('learners.status', 1);

        // Apply Filters Dynamically
        if ($request->has('date')) {

            $learners->whereDate('attendances.date', '=', $request->date);
        }

        if ($request->has('learner_id')) {

            $learners->where('learners.id', '=', $request->learner_id);
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

        return view('library.learner-attendance', compact('learners', 'data'));
    }


    /** Learner Guard and in front learner related function**/

    public function IdCard(Request $request)
    {
        
        $data = LearnerDetail::withoutGlobalScopes()->where('learner_id', Auth::user()->id)->where('learner_detail.status', 1)->leftJoin('plans', 'learner_detail.plan_id', '=', 'plans.id')->leftJoin('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')->select('learner_detail.*', 'plan_types.name as plan_type_name', 'plans.name as plan_name', 'plan_types.start_time', 'plan_types.end_time')->first();

        $library_name = Branch::where('id', Auth::user()->branch_id)->select('name as library_name', 'features')->first();

        return view('learner.idCard', compact('library_name', 'data'));
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
        return view('site.blog-details', compact('data'));
    }

    public function pendingPayment(Request $request)
    {
       
        $id = $request->id;
        $pendingPayment =LearnerTransaction::where('id',$id)->first();
        $customer = LearnerDetail::where('id', $pendingPayment->learner_detail_id)
            ->with('learner', 'plan', 'plantype')
            ->orderBy('id', 'DESC')
            ->first();
       
       
        return view('learner.pending-payment', compact('customer', 'pendingPayment'));
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


    public function pendingPaymentStore(Request $request)
    {

        $this->validate($request, [
            // 'transaction_image' => 'nullable|mimes:webp,png,jpg,jpeg|max:200',
            'transaction_id' => 'required|exists:learner_transactions,id',
            'pending_amount' => 'required',
            'payment_mode' => 'required',

        ]);
        
        
        $transaction = LearnerTransaction::where('id',$request->transaction_id)->first();

        if (!$transaction) {
            return redirect()->route('learners')->withErrors(['error' => 'Transaction not found.']);
        }
        
        if(($transaction->pending_amount - $request->pending_amount) > 0 && !$request->due_date){
          
            return redirect()->back() ->with('error', ' Due date is required');
        }
        if($request->due_date){
            $due_dat=$request->due_date;
        }else{
             $due_dat=null;
        }

       
        try {

           $this->updateLearnerTransactionPayment($transaction, $request->pending_amount, $request->payment_mode,$due_dat);

            return redirect()->route('learners')->with('success', 'Payment successfully recorded.');
        } catch (\Exception $e) {
            \Log::error('Payment Error: ' . $e->getMessage());
            return redirect()->route('learners')->withErrors(['error' => 'An error occurred while processing the payment.']);
        }
        
    }
    function generateLearnerCode() {
        $prefix = "LN";
      $lastlearner = Learner::withoutGlobalScopes()
                      ->whereNotNull('learner_no')
                      ->orderBy('id', 'DESC')
                      ->first();
                              
        if ($lastlearner) {
            
            $lastNumber = intval(substr($lastlearner->learner_no, 2)); 
            $newNumber = $lastNumber + 1;
            $randomNumber = str_pad($newNumber, 6, '0', STR_PAD_LEFT); 
        } else {
            $randomNumber = '000001';
        }
    
        return $prefix . $randomNumber;
    }

   public function makeOtherPayment(Request $request)
    {

        $customer_detail_id = $request->id;
        $customer = LearnerDetail::withTrashed()->where('learner_detail.id', $customer_detail_id)->leftJoin('learner_transactions','learner_transactions.learner_detail_id','=','learner_detail.id')->with('learner', 'plan', 'plantype')->select('learner_detail.*','learner_transactions.token_money','learner_transactions.refund as pending_refund')->first();
        
        $tokenMoney = token_money();
        
        return view('learner.other_payment', compact('customer','tokenMoney'));
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
                $payment_type='TOKEN MONEY';
                $dr_cr='Cr';
            } elseif ($request->payment_type === 'miscellaneous') {
                $transaction->miscellaneous = ($transaction->miscellaneous ?? 0) + $request->fees;
                $payment_type='MISCELLANEOUS';
                 $dr_cr='Cr';
            } elseif ($request->payment_type === 'pending_refund'){
                $transaction->refund=($transaction->refund ?? 0) - $request->fees;
                $payment_type='REFUND';
                 $dr_cr='Dr';
            }
           
            $transaction->save(); 
                $data=[];
                $data['learner_id']=$request->learner_id;
                $data['particular']='Paid By Trans';
                $data['payment_type']=$payment_type;
                $data['payment_mode']=$request->payment_mode ?? 1;
                $data['amount']=$request->fees;
                $data['dr_cr']=$dr_cr;
                $this->learnerTransactionActivity($data);

            return redirect('library/learners/list')->with('success', 'Payment successfully recorded.');

        } catch (\Exception $e) {
              
            // Log the error if needed: Log::error($e->getMessage());
            return redirect()->back()->with('error', 'An error occurred while processing payment.');
        }
    }

    

    public function learnerIdCard($id){
        $learner_detail = LearnerDetail::where('id', $id)->with([ 'learner'])->first();
        $branch=Branch::where('id',$learner_detail->branch_id)->with('city','state')->first();
        // $filename = 'qr_' . $learner_detail->learner_id . '.png';
        // Storage::put("public/qr/$filename", QrCode::format('png')->size(300)->generate($learner_detail->learner->learner_no));
        return view('learner.id_card_template', compact('learner_detail','branch'));
    }
    public function printBulkIdCard(Request $request)
    {
        $learnerIds = $request->input('learner_ids', []);
        
        if (empty($learnerIds)) {
            return back()->with('error', 'Please select at least one learner.');
        }

        $learner_details = LearnerDetail::whereIn('learner_id', $learnerIds)->where('status',1)->with(['learner'])->get();

        $branch=Branch::where('id',getCurrentBranch())->with('city','state')->first();
        return view('learner.bulk-idcards', compact('learner_details','branch'));
    }

    public function learnerChecklist(){
        $learners=Learner::leftJoin('learner_detail','learners.id','=','learner_detail.learner_id')->where('learners.status',1)->where('learners.branch_id',getCurrentBranch())->select('learners.name','learners.mobile','learners.father_name','learner_detail.is_paid','learner_detail.plan_end_date','learners.id')->get();
        return view('learner.checklist', compact('learners'));

    }

    public function learnerTransactionActivity($data)
    {
        // Fixed year
        $year = "2000";

        // Get last transaction (only for 2025 IDs)
        $last = LearnerTransactionActivity::where('transaction_id', 'like', $year . '000%')
            ->orderBy('id', 'desc')
            ->first();

        if ($last && !empty($last->transaction_id)) {
            // extract last sequence (last 4 digits)
            $lastSeq = (int)substr($last->transaction_id, -4);
            $newSeq = str_pad($lastSeq + 1, 4, '0', STR_PAD_LEFT);
        } else {
            // first transaction
            $newSeq = "0001";
        }

        // Build transaction ID
        $transactionId = $year . "0000" . $newSeq;

        LearnerTransactionActivity::create([
            'branch_id'      => getCurrentBranch(),
            'learner_id'     => $data['learner_id'],
            'date'           => now()->format('Y-m-d'),
            'transaction_id' => $transactionId,
            'particular'     => $data['particular'],
            'payment_type'   => $data['payment_type'],
            'payment_mode'   => $data['payment_mode'] == 1 ? 'CASH' : 'OTHER',
            'amount'         => $data['amount'] ?? 0,
            'dr_cr'          => $data['dr_cr'],
            'created_by'     => auth()->user()->name ?? 'System'
        ]);
    }

   public function learnerTransactionAddUpdate($data)
    {
        // 1. Save LearnerTransaction
         $effectivePaid = $data['planPrice'] + $data['locker'] -$data['discount'];
        $pending_amount =  $effectivePaid - $data['paid_amount'];
        if ( $data['paid_date']) {
            $transaction_date = $data['paid_date']->format('Y-m-d');
        } elseif ($data['start_date']->format('Y-m-d')) {
            $transaction_date = $data['start_date']->format('Y-m-d');
        } else {
            $transaction_date = date('Y-m-d');
        }
        $learnerTransaction = LearnerTransaction::create([
            'learner_id'        => $data['learner_id'],
            'library_id'        => getLibraryId(),
            'learner_detail_id' => $data['learner_detail_id'],
            'total_amount'      => $effectivePaid,
            'paid_amount'       => $data['paid_amount'],
            'pending_amount'    => $pending_amount,
            'locker_amount'     => $data['locker'] ?? 0,
            'discount_amount'   => $data['discount'] ?? 0,
            'paid_date'         => $transaction_date,
            'is_paid'           => $data['is_paid'] ?? 0,
            'branch_id'         => getCurrentBranch(),
             'due_date'        => $data['due_date'],
        ]);

        // 2. Add to LearnerTransactionActivity
        $activityData = [
            'learner_id'   => $data['learner_id'],
            'particular'   => 'Paid By Trans',
            'payment_type' => $data['payment_type'],
            'payment_mode' => $data['payment_mode'],
            'amount'       => $data['paid_amount'],
            'dr_cr'        => 'Cr',
        ];
        $this->learnerTransactionActivity($activityData);

     
        return $learnerTransaction;
    }

    public function updateLearnerTransactionPayment($transaction, $paid_amount, $payment_mode ,$due_date)
    {
        // 1. Update amounts
        $newPending = $transaction->pending_amount - $paid_amount;
        $newPaid    = $transaction->paid_amount + $paid_amount;
        $isPaid     = $newPending <= 0 ? 1 : 0;
        $due_date = $due_date ?? ($newPending > 0 ? date("Y-m-d") : null);


        $transaction->update([
            'pending_amount' => $newPending,
            'paid_amount'    => $newPaid,
            'is_paid'        => $isPaid,
            'paid_date'      => now()->format('Y-m-d'),
            'due_date'      => $due_date 
        ]);

        // 3. Insert into LearnerTransactionActivity
        
            $activityData = [
                'learner_id'   => $transaction->learner_id,
                'particular'   => 'Additional Payment',
                'payment_type' => 'PENDING',
                'payment_mode' => $payment_mode,
                'amount'       => $paid_amount,
                'dr_cr'        => 'Cr',
            ];
            $this->learnerTransactionActivity($activityData);
    }




   
}
