<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Hour;
use App\Models\Learner;
use App\Models\LearnerDetail;
use App\Models\LearnerTransaction;
use App\Models\LearnerTransactionActivity;
use App\Models\Library;
use App\Models\Plan;
use App\Models\PlanType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use DB;
use App\Http\Controllers\LearnerController;
use Barryvdh\DomPDF\Facade\Pdf;

class QrEntryController extends Controller
{
    public function downloadBranchQR($uuid)
    {
        
        $url = route('qr.branch', $uuid);

        // Generate QR as SVG       
     
        $qrCode = base64_encode(
        QrCode::format('png')
            ->size(550)
            ->margin(2)
            ->generate($url)
            );

        $pdf = PDF::loadView('library.branch-qr', compact('qrCode', 'uuid'))
                ->setPaper('a4', 'portrait');

        return $pdf->download("branch-qr-{$uuid}.pdf");
    }
   public function showOptions($uuid)
    {
        $branch = Branch::where('uuid', $uuid)->where('status', 1)->firstOrFail();

        return view('qrcode.options', compact('branch'));
    }

  
    public function bookSeat($branchUuid)
    {
            $branch = Branch::where('uuid', $branchUuid)->select('id', 'library_id', 'uuid')->firstOrFail();

            $totalSeats =  Hour::withoutGlobalScopes()->where('branch_id',$branch->id)->value('seats');
            $totalHour=Hour::withoutGlobalScopes()->where('branch_id',$branch->id)->value('hour');
         
            $usedSeats = LearnerDetail::withoutGlobalScopes()->select('seat_no', DB::raw('SUM(hour) as used_hours'))
                        ->where('branch_id',$branch->id)
                        ->whereNotNull('seat_no')
                        ->groupBy('seat_no')->where('status',1)
                        ->pluck('used_hours', 'seat_no'); // [seat_no => used_hours]

            $availableSeats = collect();

            // Step 2: Loop through all seat numbers and apply logic
            for ($seatNo = 1; $seatNo <= $totalSeats; $seatNo++) {
                $usedHours = $usedSeats[$seatNo] ?? 0;

                if ($usedHours < $totalHour) {
                    $availableSeats->push($seatNo);
                }
            }

      
        $plans = Plan::withoutGlobalScopes()->where('library_id', $branch->library_id)->get();

        $planType = PlanType::withoutGlobalScopes()->where('branch_id', $branch->id)->get();

        return view('qrcode.booking', compact('branch', 'plans', 'planType','availableSeats'));
    }
   public function getPlanPrice(Request $request)
    {
        // Validation
        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'plan_type_id' => 'required|exists:plan_types,id',
            'branch_id' => 'required|exists:branches,id',
        ]);

        // Assign variables from request
        $plan_id = $validated['plan_id'];
        $plan_type_id = $validated['plan_type_id'];
        $branch_id = $validated['branch_id'];

        // Call your helper function
        $price = getPlanPrice($plan_id, $plan_type_id, $branch_id);

        // Return JSON response
        return response()->json([
            'success' => true,
            'price'   => $price ?? 0,
        ]);
    }

    //  private function validateLearnerCustom($branch_id, $plan_type_id, $seat_no,$library_id)
    // {
            
    //     $total_hour= Hour::withoutGlobalScopes()->where('branch_id',$branch_id)->first()?->hour ?? 0;

    //     if ($total_hour === 0) {
    //         return ['error' => true, 'message' => 'Total available hours not set.'];
    //     }

    //     $hours = PlanType::where('id', $plan_type_id)->value('slot_hours') ?? 0;
     
    //     if (Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
    //                   ->where('learners.branch_id', $branch_id)->where('learners.seat_no', $seat_no)->where('plan_type_id', $plan_type_id)->where('learners.status', 1)->exists()) {
    //         return ['error' => true, 'message' => 'This Plan Type Seat already booked'];
    //     }
       

    //     if ((Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
    //                   ->where('learners.branch_id', $branch_id)->where('learners.seat_no', $seat_no)->where('learner_detail.status', 1)->sum('hours') + $hours) > $total_hour) {
    //         return ['error' => true, 'message' => 'This seat is already reserved for the full library hours on the selected day.'];
    //     }

    //     if (Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
    //                   ->where('learners.branch_id', $branch_id)->where('learners.seat_no', $seat_no)->where('learner_detail.plan_start_date', '>', Carbon::today())->exists()) {
    //         if ($this->checkPlanTypeSeatWise($seat_no, $plan_type_id,$branch_id,$library_id) == false) {
    //             return ['error' => true, 'message' => 'This plan conflicts with a future booking.'];
    //         }
    //     }
       

    //     // ✅ Always return structured response
    //     return ['error' => false];
    // }
    private function validateLearnerCustom(
    $branch_id,
    $plan_type_id,
    $seat_no,
    $library_id,$learnerId = null) {
    $total_hour = Hour::withoutGlobalScopes()
        ->where('branch_id', $branch_id)
        ->value('hour') ?? 0;

    if ($total_hour === 0) {
        return ['error' => true, 'message' => 'Total available hours not set.'];
    }

    $hours = PlanType::where('id', $plan_type_id)->value('slot_hours') ?? 0;

    // Same plan + seat conflict
    if (
        Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
            ->where('learners.branch_id', $branch_id)
            ->where('learners.seat_no', $seat_no)
            ->where('learner_detail.plan_type_id', $plan_type_id)
            ->where('learners.status', 1)
            ->when($learnerId, fn ($q) => $q->where('learners.id', '!=', $learnerId))
            ->exists()
    ) {
        return ['error' => true, 'message' => 'This plan type seat is already booked'];
    }

    // Hour overflow
    $usedHours =Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
            ->where('learners.branch_id', $branch_id)
            ->where('learners.seat_no', $seat_no)
            ->where('learner_detail.status', 1)
            ->when($learnerId, fn ($q) => $q->where('learners.id', '!=', $learnerId))
            ->sum('hours');

    if (($usedHours + $hours) > $total_hour) {
        return [
            'error' => true,
            'message' => 'This seat is already reserved for the full library hours.'
        ];
    }

    // Future booking conflict
    if (
        Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
            ->where('learners.branch_id', $branch_id)
            ->where('learners.seat_no', $seat_no)
            ->whereDate('learner_detail.plan_start_date', '>', Carbon::today())
            ->when($learnerId, fn ($q) => $q->where('learners.id', '!=', $learnerId))
            ->exists()
    ) {
        if (!$this->checkPlanTypeSeatWise($seat_no, $plan_type_id, $branch_id, $library_id)) {
            return ['error' => true, 'message' => 'This plan conflicts with a future booking.'];
        }
    }

    return ['error' => false];
    }

     public function checkPlanTypeSeatWise($seatNo,$requestPlanType,$branch_id,$library_id)
    {

            // Step 1: Retrieve all bookings for the given seat
            $bookings = $this->getLearnersByLibrary()
                ->join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')
                ->where('learner_detail.seat_no', $seatNo)
                ->where('learner_detail.plan_start_date','>',Carbon::today())
                ->where('learners.branch_id', $branch_id)
                ->where('learner_detail.branch_id', $branch_id)
                ->get(['learner_detail.plan_type_id', 'plan_types.start_time', 'plan_types.end_time', 'plan_types.slot_hours']);
       
            // Step 2: Retrieve all plan types
            $planTypes = PlanType::withoutGlobalScopes()->where('branch_id', $branch_id)->get();

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
                $planTypeId = PlanType::withoutGlobalScopes()->where('branch_id', $branch_id)->where('day_type_id', 8)->value('id') ?? 0;
            }

            if (!is_null($planTypeId)) {
                $planTypesRemovals[] = $planTypeId;
            }
            $nightseatBooked = LearnerDetail::withoutGlobalScopes()->join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')->where('learner_detail.branch_id', $branch_id)->where('plan_types.library_id', $library_id)->where('learner_detail.seat_no', $seatNo)->where('learner_detail.plan_start_date','>',Carbon::today())->where('plan_types.day_type_id', 9)->exists();

            if ($nightseatBooked) {
                $planTypeid = LearnerDetail::withoutGlobalScopes()->join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')->where('learner_detail.branch_id', $branch_id)->where('plan_types.library_id', $library_id)->where('learner_detail.seat_no', $seatNo)->where('learner_detail.plan_start_date','>',Carbon::today())->where('plan_types.day_type_id', 9)->value('plan_types.id') ?? 0;
                $planTypesRemovals[] = $planTypeid;
            }
            // Remove duplicate entries in planTypesRemovals
            $planTypesRemovals = array_unique($planTypesRemovals);

            // If total booked hours >= 16, all plan types should be removed
            $first_record = Hour::withoutGlobalScopes()->where('branch_id', $branch_id)->first();
            $total_hour = $first_record ? $first_record->hour : null;

            if ($totalBookedHours >= $total_hour) {
                $planTypesRemovals = $planTypes->pluck('id')->toArray();
            }
            // ✅ Remove day_type_id 8 and 9 if total allowed hours < 24
            if ($total_hour < 24) {
                $dayTypePlanIds = PlanType::withoutGlobalScopes()->where('branch_id', $branch_id)->whereIn('day_type_id', [8, 9])->pluck('id')->toArray();
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
    public function store(Request $request, $uuid)
    {
        try {
            Log::info('Heena Booking store started', ['uuid' => $uuid, 'request' => $request->all()]);
            $branch = Branch::where('uuid', $uuid)->firstOrFail();
           Log::info('STEP 2: Branch fetched', [
                'branch_id' => $branch->id ?? null,
            ]);


            // Build validation rules
            $rules = [
                'name'           => 'required|string|max:191',
                'mobile'         => 'required|integer|digits_between:8,15',
                'seat_no'        => 'nullable',
                'plan_id'        => 'required|integer|exists:plans,id',
                'plan_type_id'   => 'required|integer|exists:plan_types,id',
                'plan_price_id'  => 'required',
                'plan_start_date'=> 'required|date',
                'payment_mode'   => 'required|in:online,offline',
            ];
            
            Log::info('STEP 3: Before validation', [
                'request_data' => $request->except(['password', '_token']),
            ]);

            $validated = $request->validate($rules);
            
            $months   = Plan::withoutGlobalScopes()->where('id', $validated['plan_id'])->value('plan_id');
            $planData = Plan::withoutGlobalScopes()->where('id', $validated['plan_id'])
                ->select('plan_id', 'type', 'monthdays')
                ->first();
            Log::info('STEP 5: plan',['planData'=>$planData,'months'=>$months]);
            $duration  = $planData->plan_id ?? 0; 
            $type      = $planData->type;
            $monthdays = $planData->monthdays;

            $start_date = Carbon::parse($validated['plan_start_date'])->addDay();

            switch (strtoupper($type)) {
                case 'DAY':   $endDate = $start_date->copy()->addDays($duration); break;
                case 'WEEK':  $endDate = $start_date->copy()->addWeeks($duration); break;
                case 'MONTH':
                if (!empty($monthdays)) {
                    // Use exact number of days defined for this month plan
                    $endDate = $start_date->copy()->addDays($monthdays - 1);
                } else {
                    // Fallback to month-wise duration
                    $endDate = $start_date->copy()->addMonths($duration);
                }
                break;
                case 'YEAR':  $endDate = $start_date->copy()->addYears($duration); break;
                default:      $endDate = $start_date; break;
            }
            Log::info('STEP 6: endDate booking',['endDate'=>$endDate]);

            $transactions = LearnerTransaction::withoutGlobalScopes()
                ->where('id', $request->learner_transaction_id)
                ->first();
            Log::info('Transaction check', ['transaction' => $transactions]);
             $learnerId = $transactions?->learner_id ?? null;
              if($request->seat_no){
               
                    
                Log::info('STEP 5: Seat validation started learnerId',['learnerId'=>$learnerId]);
                $validated_custom = $this->validateLearnerCustom($branch->id, $request->plan_type_id, $request->seat_no,$branch->library_id,$learnerId);
                if ($validated_custom['error']) {
                    Log::warning('STEP 5 FAILED: Seat validation error', [
                        'message' => $validated_custom['message']
                    ]);
                  
                  return redirect()
                        ->route('renew.form', $uuid)
                        ->with('error', $validated_custom['message'])
                        ->withInput();

                }
                
            }

            if ($learnerId) {

                $today        = Carbon::today();
                $expiryLimit  = Carbon::today()->addDays(7);

                /* 1️⃣ Active plan (if any) */
                $activePlan = LearnerDetail::where('learner_id', $learnerId)
                    ->where('status', 1)
                    ->whereDate('plan_start_date', '<=', $today)
                    ->whereDate('plan_end_date', '>=', $today)
                    ->orderBy('plan_end_date', 'desc')
                    ->first();

                /* 2️⃣ Future plan check → HARD BLOCK */
                $futurePlanExists = LearnerDetail::where('learner_id', $learnerId)
                    ->whereDate('plan_start_date', '>', $today)
                    ->exists();

                if ($futurePlanExists) {
                    return redirect()->route('renew.form', $uuid)
                        ->with('error', 'Renewal already exists. Multiple renewals are not allowed.')
                        ->withInput();
                }

                /* 3️⃣ If ACTIVE plan exists → check buffer window */
                if ($activePlan) {

                    $planEndDate = Carbon::parse($activePlan->plan_end_date);

                    // ❌ Active & NOT in buffer days
                    if ($planEndDate->gt($expiryLimit)) {
                        return redirect()->route('renew.form', $uuid)
                            ->with('error', 'Current plan is active and not eligible for renewal yet.')
                            ->withInput();
                    }

                }

            }

            if (!is_null($transactions)) {

                $learnerId = $transactions->learner_id;

                $password = Learner::where('id', $learnerId)->value('password')
                            ?? Hash::make($validated['mobile']);

                $total_amount = $transactions->total_amount ?? $validated['plan_price_id'];

            } else {

                $password     = Hash::make($validated['mobile']);
                $total_amount = $validated['plan_price_id'];
            }

            Log::info('Password & Total amount set', ['total_amount' => $total_amount,'password'=>$password]);

            $seat_type = $request->has('renewal') ? 'qr_renew' : 'qr_seat_book';
Log::info('seat type', ['seat_type' => $seat_type]);
            $booking = Booking::create([
                'name'            => $validated['name'],
                // 'email'           => $validated['email'] ?? null,
                'mobile'          => $validated['mobile'],
                'password'        => $password,
                'seat_no'         => $request->seat_no ?? null,
                'branch_id'       => $branch->id,
                'plan_id'         => $validated['plan_id'],
                'plan_type_id'    => $validated['plan_type_id'],
                'plan_price_id'   => $validated['plan_price_id'],
                'plan_start_date' => $validated['plan_start_date'],
                'plan_end_date'   => $endDate,
                'payment_mode'    => $validated['payment_mode'],
                'status'          => 'pending',
                'total_amount'    => $total_amount,
                'transaction_id'  => $transactions ? $transactions->id : null,
                'type'            => $seat_type,
            ]);

            Log::info('Booking created successfully', ['booking_id' => $booking->id]);

            if ($validated['payment_mode'] === 'online') {
                return redirect()
                    ->route('booking.payment.qr', $booking->id)
                    ->with('success', 'Booking created! Please complete your payment.');
            } else {
                return redirect()
                    ->route('booking.offline.details', $booking->id)
                    ->with('success', 'Booking created! Please visit the branch to pay.');
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
          
             Log::warning('VALIDATION EXCEPTION', [
                'method' => request()->method(),
                'url'    => request()->fullUrl(),
                'errors' => $e->errors(),
            ]);

            return redirect()
                ->route('booking.form', $uuid)
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Exception $e) {
           
             Log::error('BOOKING STORE CRASH', [
                'method'  => request()->method(),
                'url'     => request()->fullUrl(),
                'message' => $e->getMessage(),
            ]);

            return redirect()
                ->route('booking.form', $uuid)
                ->with('error', 'Something went wrong.')
                ->withInput();
        }
    }



    public function showPaymentQR($bookingId)
    {
        $booking = Booking::with('branch')->findOrFail($bookingId);

        $branch=Branch::where('id',$booking->branch_id)->first();
        $upiId   = $branch->upi_id ?? 'heenamehandi94145';  // fallback UPI if branch has none
        $payee   = $branch->name ?? 'Library';           // dynamic payee name
        $amount  = $booking->total_amount;                        // dynamic amount
        $currency = 'INR';
        $note     = 'Book Seat';                      // you can extend this as needed


        $upiLink = "upi://pay?pa={$upiId}&pn=".urlencode($payee)."&am={$amount}&cu={$currency}&tn=".urlencode($note);
      

        

        $qrCode = QrCode::size(300)->generate($upiLink);
        // Assume branch has a payment_qr field
        return view('qrcode.payment_qr', compact('booking','qrCode','upiLink'));
    }

    public function showOfflineDetails($bookingId)
    {
        $booking = Booking::with('branch')->findOrFail($bookingId);

        return view('qrcode.offline_details', compact('booking'));
    }

    public function uploadScreenshot(Request $request, $bookingId)
    {
        $request->validate([
            'payment_screenshot' => 'required|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $booking = Booking::findOrFail($bookingId);
          if ($request->hasFile('payment_screenshot')) {
            $this->validate($request, ['payment_screenshot' => 'mimes:webp,png,jpg,jpeg|max:2048']);
            $payment_screenshot = $request->payment_screenshot;
            $payment_screenshotNewName = "payment" . time() . $payment_screenshot->getClientOriginalName();
            $payment_screenshot->move('public/uploade/', $payment_screenshotNewName);
            $payment_screenshot = 'public/uploade/' . $payment_screenshotNewName;
        } else {
            $payment_screenshot = null;
        }
        $update= $booking->update([
            'payment_screenshot' => $payment_screenshot,
            'status' => 'pending'
        ]);
        
      
       // 🔔 Send notification to library owner
        if ($update) {
            $branch = Branch::where('id', $booking->branch_id)->first(['id', 'email', 'library_id']);
            $library = Library::where('id', $branch->library_id)->first(['id', 'email']);

            // Decide which email to use
            $email = $branch->email ?? $library->email;

            if ($email) {
                try {
                    Mail::send('email.notify-email', ['booking' => $booking], function ($message) use ($booking, $email) {
                        $message->to($email)
                            ->subject('New Registration Payment Request');
                    });
                } catch (\Exception $e) {
                    // Log error but don't break redirect
                    \Log::error('Mail sending failed: ' . $e->getMessage());
                }
            }
        }
         return redirect()
                ->route('booking.offline.details', $booking->id)
                ->with('success', 'Payment screenshot uploaded. Please wait for confirmation.');

        
    }
    public function showBookingDetails($id)
    {
        
        $customer = Booking::with(['branch', 'plan', 'planType']) // eager load relations
            ->findOrFail($id);
        $plans = Plan::where('library_id', getLibraryId())->get();

        $planType = PlanType::withoutGlobalScopes()->where('branch_id', getCurrentBranch())->get();
        if($customer->transaction_id){
             $transaction=LearnerTransaction::withoutGlobalScopes()->where('id',$customer->transaction_id)->first();
             $learner=Learner::withoutGlobalScopes()->where('id',$transaction->learner_id)->first();
        }else{
            $transaction=null;
             $learner=null;
        }
       

        return view('qrcode.verify_request', compact('customer','planType','plans','transaction','learner'));
    }

    public function requestApproveEdit(Request $request)
    {
       
        if(!$request->direct_validate && !isset($request->direct_validate)){

        $rules = [
            'booking_id' => 'required',
            'branch_id' => 'required',
            'seat_no' => 'nullable',
            'plan_id' => 'required',
            'plan_type_id' => 'required',
            'plan_price_id' => 'required',
            'plan_start_date' => 'required',
            'paid_amount' => 'nullable',
           
            'payment_mode' => 'required',
            'discount_type' => 'nullable',
           
            'discount_amount' => [
                'nullable',
                function ($attribute, $value, $fail) use ($request) {
                    if (!in_array($request->discount_type, ['amount', 'percentage']) && $value) {
                        $fail('Discount type must be selected when providing a discount amount.');
                    }
                    if (in_array($request->discount_type, ['amount', 'percentage']) && !$value) {
                        $fail('Discount amount is required when a discount type is selected.');
                    }
                }
            ],
            'locker_no' => [
                'nullable',
                'required_if:locker,yes',
                'numeric'
            ],
            'locker_amount' => [
                'nullable',
                'required_if:locker,yes'
            ],
            'name'              => 'nullable|string|max:255',
            'mobile'            => 'nullable|digits:10',
            'email'             => 'nullable|email',
            'dob'               => 'nullable|date',
            'alternate_mobile'  => 'nullable|digits:10',
            'exam_id'           => 'nullable|integer',
            'address'           => 'nullable|string',
            'remark'            => 'nullable|string',
            'id_proof_name'     => 'nullable',

        ];
       
        
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        // if (!Auth::user()->can('has-permission', 'Renew Seat')) {
        //     return redirect()->back()->with('error', 'You do not have permission to renew the seat.');
        // }

        }
     
        DB::beginTransaction();

        try {
            $learnerController = app(\App\Http\Controllers\LearnerController::class);

         
            $bookingurl=Booking::find($request->booking_id);
          
            
            if ($request->seat_no ) {
                $seat_no = $request->input('seat_no');
            } else {
                $seat_no = $bookingurl->seat_no;
            }
            if($request->direct_validate){
                $planPrice= $bookingurl->plan_price_id;
                $start_date = Carbon::parse($bookingurl->plan_start_date);
                $plan_id = $bookingurl->plan_id;
                $plan_type_id = $bookingurl->plan_type_id;
                $locker_no=null;
                $total_amt=$bookingurl->plan_price_id;
                $paid_amount=$bookingurl->plan_price_id;
                $pending_amount=0;
                $locker=0;
                $discount=0;
                $pending_refund=0;
            }else{

            
                $planPrice = (float) $request->input('plan_price_id', 0);
                $locker = (float) $request->input('locker_amount', 0);
                if ($request->discount_type == 'amount') {
                    $discount = $request->discount_amount;
                } elseif ($request->discount_type == 'percentage') {
                    $total = $planPrice + $locker;
                    $discount = ($total * $request->discount_amount) / 100;
                } else {
                    $discount = 0;
                }
                $total_amt=$planPrice+$locker-$discount;
            
                $paid_amount = (float) $request->input('paid_amount', 0);
                $pending_amount = ($total_amt-$paid_amount) ?? 0;
               
               
                $refund = 0;
                $pending_refund = 0;
                $dr_cr='Cr';
        
            
                if($pending_amount > 0 && !$request->due_date){
                    return redirect()->back()->with('error', 'Due date is required')->withInput();
                }

                $start_date = Carbon::parse($request->input('plan_start_date'));
                $plan_id = $request->input('plan_id');
                $plan_type_id = $request->input('plan_type_id');
                $locker_no=$request->input('locker_no');

            }
           
            $planType = PlanType::withoutGlobalScopes()->find($plan_type_id);
          
           
            $hours = $planType->slot_hours;
        
            $endDate=getEndDate($plan_id, $start_date);
            $extendDay = getExtendDays();

            $inextendDate = Carbon::parse($endDate)->addDays($extendDay);
          
           $alreadyActive = false;

            if (!empty($request->learner_id)) {
                $alreadyActive = LearnerDetail::where('learner_id', $request->learner_id)->where('status', 1)->exists();
            }

            // Default status
            $detailStatus = 0;

            // Date-based activation
            if ($inextendDate->greaterThan(Carbon::today()) && $start_date->lessThanOrEqualTo(Carbon::today())) {
                $detailStatus = 1;
            }

            // If any active plan exists → force inactive
            if ($alreadyActive) {
                $detailStatus = 0;
            }

            if (($inextendDate > Carbon::today() && $start_date <= Carbon::today()) || $detailStatus == 1) {
                $status = 1;
            } else {
                $status = 0;
            }

            $is_paid = 1;
            if($request->payment_mode=='online'){
                $payment_mode = 1;
            }else{
                $payment_mode = 0;
            }
            
           Log::info('FordetailStatus', ['status' => $status,'detailStatus'=>$detailStatus]);

            $learnerId=$request->learner_id;
           
            if ($learnerId) {

                $today        = Carbon::today();
                $expiryLimit  = Carbon::today()->addDays(7);

                /* 1️⃣ Active plan (if any) */
                $activePlan = LearnerDetail::where('learner_id', $learnerId)
                    ->where('status', 1)
                    ->whereDate('plan_start_date', '<=', $today)
                    ->whereDate('plan_end_date', '>=', $today)
                    ->orderBy('plan_end_date', 'desc')
                    ->first();

                /* 2️⃣ Future plan check → HARD BLOCK */
                $futurePlanExists = LearnerDetail::where('learner_id', $learnerId)
                    ->whereDate('plan_start_date', '>', $today)
                    ->exists();

                if ($futurePlanExists) {
                    return redirect()->back()
                        ->with('error', 'Renewal already exists. Multiple renewals are not allowed.')
                        ->withInput();
                }

                /* 3️⃣ If ACTIVE plan exists → check buffer window */
                if ($activePlan) {

                    $planEndDate = Carbon::parse($activePlan->plan_end_date);

                    // ❌ Active & NOT in buffer days
                    if ($planEndDate->gt($expiryLimit)) {
                        return redirect()->back()
                            ->with('error', 'Current plan is active and not eligible for renewal yet.')
                            ->withInput();
                    }

                }

            }


            if($seat_no){
                $result = checkSeatAvailability($seat_no,$learnerId ?? null,$plan_type_id,$start_date,$endDate);
                 if ($result['error']) {
                    return redirect()->back()->with('error', $result['message'])->withInput();
                
                }
            }
            
            

           

            if (($paid_amount > $total_amt) || ($paid_amount == 0)) {
                return redirect()->back()->with('error', 'Paid amount is not valid')->withInput();
               
            }
            if (($pending_amount > 0) && (!$request->due_date)) {
                return redirect()->back()->with('error', 'Paid amount is not valid')->withInput();
              
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
               
            if($request->learner_id){
                $customer=Learner::find($request->learner_id);
                $customer->seat_no=$seat_no;
                $customer->hours=$hours;
                $customer->status=$status;
                $customer->locker_no=$locker_no;
                if ($request->filled('email')) {
                    $customer->email = encryptData($request->email);
                }
                if ($request->filled('dob')) {
                    $customer->dob = $request->dob;
                }
                $customer->save();
            }else{
               
                $customer = Learner::create([
                 'seat_no' => $seat_no,
                'name' => $request->input('name') ?? $bookingurl->name,
                'mobile' => encryptData($request->input('mobile')) ?? encryptData($bookingurl->mobile),
                'email' => $request->input('email') ? encryptData($request->input('email')) : null,
                'dob' => $request->input('dob'),
                'id_proof_name' => $request->input('id_proof_name'),
                'id_proof_file' => $id_proof_file,
                'hours' => $hours,
                'status' => $status,
                'library_id' => getLibraryId(),
                'password' =>$bookingurl->password,
                'branch_id' => getCurrentBranch(),
                'learner_no'=>$this->generateLearnerCode(),
                'father_name' => $request->input('father_name'),
                'alternate_mobile' => $request->input('alternate_mobile'),
                'remark' => $request->input('remark'),
                'profile_picture'=>$profile_picture,
                'address' => $request->input('address'),
                'locker_no'=>$locker_no ?? null ,
                'sended_message_type'=>$request->input('sended_message_type') ?? 'no'
                ]);
            }

          
            $learner_detail = LearnerDetail::create([
                'library_id' => getLibraryId(),
                'branch_id' => getCurrentBranch(),
                'learner_id' => $customer->id,
                'plan_id' => $plan_id,
                'plan_type_id' => $plan_type_id,
                'plan_price_id' => $planPrice,
                'plan_start_date' => $start_date->format('Y-m-d'),
                'plan_end_date' => $endDate->format('Y-m-d'),
                'join_date' => $start_date->format('Y-m-d'),
                'hour' => $hours,
                'seat_no' => $seat_no,
                'payment_mode' => $payment_mode,
                'is_paid' =>1,
                'status' => $detailStatus,
            ]);

            if ($bookingurl->created_at) {
                $transaction_date = $bookingurl->created_at;
            } else {
                $transaction_date =null;
            }
                   

             $learnerTransaction = LearnerTransaction::create([
                'learner_id' => $customer->id,
                'library_id'        => getLibraryId(),
                'branch_id'         => getCurrentBranch(),
                'learner_detail_id' => $learner_detail->id,
                'total_amount'      => $total_amt,
                'paid_amount'       => $paid_amount,
                'pending_amount'    => $pending_amount,
                'locker_amount'     => $locker ?? 0,
                'discount_amount'   => $discount ?? 0,
                'paid_date'         => $transaction_date,
                'is_paid'           => $is_paid ?? 0,
                'due_date'        => $request->due_date ?? null,
                'refund'        => $pending_refund,
                 'transaction_id' => transaction_id(),
            ]);
           
            //learner Activity
            $data=[];
            $data['learner_id']=$customer->id;
            $data['particular']='Paid By Trans';
            $data['payment_type']='SEAT ASSIGNMENT';
            $data['payment_mode']=1;
            $data['amount']=$paid_amount;
            $data['dr_cr']='Cr';
          
            $learnerController->learnerTransactionActivity($data);

                if ($status == 1) {

                    $learnerController->dataUpdate();
                }
                $bookingurl->delete();

            DB::commit();

            return redirect()->route('learners')->with('success', 'Learner updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }
    

   
    public function renewSeat($uuid)
    {
        $branch = Branch::where('uuid', $uuid)->firstOrFail();

        return view('qrcode.renew_form', compact('branch'));
    }

    public function findCustomer(Request $request, $uuid)
    {
        $request->validate([
            'mobile' => 'required|digits:10',
            'learner_no'=>'required'
        ]);
         try {
            $dob = Carbon::parse($request->learner_no)->format('Y-m-d');
        } catch (\Exception $e) {
            $dob = null;
        }

        $branch = Branch::where('uuid', $uuid)->firstOrFail();
        // Learner::withoutGlobalScopes()->where('branch_id', $branch->id)
        //     ->where('mobile', encryptData($request->input('mobile')))->where('learner_no',$request->input('learner_no'))
        //     ->first();
        $customer = Learner::withoutGlobalScopes()->where('branch_id', $branch->id)->where(function ($query) use ($request,$dob) {
                    $query->where('learner_no', $request->input('learner_no'));
                        if ($dob) {
                            \Log::info('dob part hit',['dob'=>$dob]);
                            $query->orWhere('dob', $dob);
                        }
                       // Email (only if valid)
                        if (filter_var($request->input('learner_no'), FILTER_VALIDATE_EMAIL)) {
                            $query->orWhere('email', encryptData($request->input('learner_no')));
                        }
                })
                ->where('mobile', encryptData($request->mobile))
                ->first();

        if (!$customer) {
            return redirect()->back()->with('error', 'No customer found with this mobile.');
        }

        $customer_detail = LearnerDetail::withoutGlobalScopes()->where('learner_id', $customer->id)
            ->with('plan', 'planType')
            ->latest() // gets the latest detail record
            ->first();
       
        $transaction = LearnerTransaction::withoutGlobalScopes()->where('learner_detail_id', $customer_detail->id)->first();
        if (!$transaction) {
            return redirect()->back()->with('error', 'No customer transaction found with this mobile.');

        }
    

        return view('qrcode.renew_show_form', compact('branch', 'customer', 'customer_detail','transaction'));
    }
    public function destroy($id)
    {
        $booking = Booking::findOrFail($id);
        $booking->delete();

        return response()->json(['success' => true]);
    }

     public function getPlanTypeSeatWise(Request $request)
    {
        Log::info('Branchrequest', ['request' => $request]);
        $seatNo = $request->seatNo;
        if($request->branchId){
            $branch_id=$request->branchId;
        }else{
            $branch_id=getCurrentBranch();
        }
       
        $branchData=Branch::where('id',$branch_id)->select('library_id')->first();
         Log::info('branchData', ['branchData' => $branchData]);
         // Step 1: Retrieve all bookings for the given seat
            $bookings = Learner::withoutGlobalScopes()->leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')   
                ->join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')
                ->where('learner_detail.seat_no', $seatNo)
                ->where('learners.status', 1)
                ->where('learner_detail.status', 1)
                ->where('learners.branch_id', $branch_id)
                ->where('learner_detail.branch_id', $branch_id)
                ->get(['learner_detail.plan_type_id', 'plan_types.start_time', 'plan_types.end_time', 'plan_types.slot_hours']);
        if ($seatNo && $bookings) {

            // Step 2: Retrieve all plan types
            $planTypes = PlanType::withoutGlobalScopes()->where('branch_id', $branch_id)->get();
            Log::info('Plan types fetched', [
                'count' => $planTypes->count(),
                'plan_type_ids' => $planTypes->pluck('id')
            ]);

            // Step 3: Initialize an array to store the plan_type_ids to be removed
            $planTypesRemovals = [];

            // Step 4: Calculate total booked hours for the seat
            $totalBookedHours = $bookings->sum('slot_hours');

            $nightseatBooked = LearnerDetail::withoutGlobalScopes()->join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')->where('learner_detail.seat_no', $seatNo)->where('learner_detail.branch_id', $branch_id)->where('plan_types.library_id', $branchData->library_id)->where('learner_detail.status', 1)->where('plan_types.day_type_id', 9)->exists();

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
                $planTypeId = PlanType::withoutGlobalScopes()->where('branch_id', $branch_id)->where('day_type_id', 8)->value('id') ?? 0;
            }

            if (!is_null($planTypeId)) {
                $planTypesRemovals[] = $planTypeId;
            }
            if ($nightseatBooked) {
                $planTypeid = LearnerDetail::join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')->where('learner_detail.seat_no', $seatNo)->where('learner_detail.branch_id', $branch_id)->where('plan_types.library_id', $branchData->library_id)->where('learner_detail.status', 1)->where('plan_types.day_type_id', 9)->value('plan_types.id') ?? 0;
                $planTypesRemovals[] = $planTypeid;
            }
            // Remove duplicate entries in planTypesRemovals
            $planTypesRemovals = array_unique($planTypesRemovals);

            // If total booked hours >= 16, all plan types should be removed
            $first_record = Hour::withoutGlobalScopes()->where('branch_id', $branch_id)->first();
            $total_hour = $first_record ? $first_record->hour : null;

            if ($totalBookedHours >= $total_hour) {
                $planTypesRemovals = $planTypes->pluck('id')->toArray();
            }
            // ✅ Remove day_type_id 8 and 9 if total allowed hours < 24
            if ($total_hour < 24) {
                $dayTypePlanIds = PlanType::withoutGlobalScopes()->where('branch_id', $branch_id)->whereIn('day_type_id', [8, 9])->pluck('id')->toArray();
                $planTypesRemovals = array_merge($planTypesRemovals, $dayTypePlanIds);
            }
            // Step 6: Filter out the plan_types that match the retrieved plan_type_ids
            $filteredPlanTypes = $planTypes->filter(function ($planType) use ($planTypesRemovals) {
                return !in_array($planType->id, $planTypesRemovals);
            })->map(function ($planType) {
                return ['id' => $planType->id, 'name' => $planType->name];
            })->values(); // Ensure the keys are reset to a continuous numerical index
        } else {

            $first_record = Hour::withoutGlobalScopes()->where('branch_id', $branch_id)->first();
            $total_hour = $first_record ? $first_record->hour : null;

            if ($total_hour < 24) {
                $filteredPlanTypes = PlanType::withoutGlobalScopes()->where('branch_id', $branch_id)->whereNotIn('day_type_id', [8, 9])
                    ->select('id', 'name')
                    ->get();
            } else {
                $filteredPlanTypes = PlanType::withoutGlobalScopes()->where('branch_id', $branch_id)->select('id', 'name')->get();
            }

        }

        // Return the filtered plan types as JSON
        return response()->json($filteredPlanTypes);
    }

     public function getPlanTypeForRenew(Request $request)
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
        if ($request->filled('planType')) {

            $selectedPlan = PlanType::where('id', $request->planType)
            ->select('id', 'name')
            ->first();

            if ($selectedPlan) {
                $exists = collect($filteredPlanTypes)->contains('id', $selectedPlan->id);

                if (!$exists) {
                    $filteredPlanTypes = collect($filteredPlanTypes)
                        ->push([
                            'id'   => $selectedPlan->id,
                            'name' => $selectedPlan->name
                        ])
                        ->values();
                }
            }
        }
        // Return the filtered plan types as JSON
        return response()->json($filteredPlanTypes);
    }
}
