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
        $branch = Branch::where('uuid', $branchUuid)->firstOrFail();

            $totalSeats =  Hour::where('branch_id',$branch->id)->value('seats');
            $totalHour=Hour::where('branch_id',$branch->id)->value('hour');
            $usedSeats = LearnerDetail::select('seat_no', DB::raw('SUM(hour) as used_hours'))
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

        $planType = PlanType::withoutGlobalScopes()->where('library_id', $branch->library_id)->get();

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

     private function validateLearnerCustom($branch_id, $plan_type_id, $seat_no,$library_id)
    {
      
        $total_hour= Hour::where('branch_id',$branch_id)->first()?->hour ?? 0;

        if ($total_hour === 0) {
            return ['error' => true, 'message' => 'Total available hours not set.'];
        }

        $hours = PlanType::where('id', $plan_type_id)->value('slot_hours') ?? 0;
     
        if (Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
                      ->where('learners.branch_id', $branch_id)->where('learners.seat_no', $seat_no)->where('plan_type_id', $plan_type_id)->where('learners.status', 1)->exists()) {
            return ['error' => true, 'message' => 'This Plan Type Seat already booked'];
        }
       

        if ((Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
                      ->where('learners.branch_id', $branch_id)->where('learners.seat_no', $seat_no)->where('learner_detail.status', 1)->sum('hours') + $hours) > $total_hour) {
            return ['error' => true, 'message' => 'This seat is already reserved for the full library hours on the selected day.'];
        }

        if (Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
                      ->where('learners.branch_id', $branch_id)->where('learners.seat_no', $seat_no)->where('learner_detail.plan_start_date', '>', Carbon::today())->exists()) {
            if ($this->checkPlanTypeSeatWise($seat_no, $plan_type_id,$branch_id,$library_id) == false) {
                return ['error' => true, 'message' => 'This plan conflicts with a future booking.'];
            }
        }
       

        // ✅ Always return structured response
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
            $planTypes = PlanType::withoutGlobalScopes()->where('library_id', $library_id)->get();

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
                $planTypeId = PlanType::withoutGlobalScopes()->where('library_id', $library_id)->where('day_type_id', 8)->value('id') ?? 0;
            }

            if (!is_null($planTypeId)) {
                $planTypesRemovals[] = $planTypeId;
            }
            $nightseatBooked = LearnerDetail::join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')->where('learner_detail.branch_id', $branch_id)->where('plan_types.library_id', $library_id)->where('learner_detail.seat_no', $seatNo)->where('learner_detail.plan_start_date','>',Carbon::today())->where('plan_types.day_type_id', 9)->exists();

            if ($nightseatBooked) {
                $planTypeid = LearnerDetail::join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')->where('learner_detail.branch_id', $branch_id)->where('plan_types.library_id', $library_id)->where('learner_detail.seat_no', $seatNo)->where('learner_detail.plan_start_date','>',Carbon::today())->where('plan_types.day_type_id', 9)->value('plan_types.id') ?? 0;
                $planTypesRemovals[] = $planTypeid;
            }
            // Remove duplicate entries in planTypesRemovals
            $planTypesRemovals = array_unique($planTypesRemovals);

            // If total booked hours >= 16, all plan types should be removed
            $first_record = Hour::where('branch_id', $branch_id)->first();
            $total_hour = $first_record ? $first_record->hour : null;

            if ($totalBookedHours >= $total_hour) {
                $planTypesRemovals = $planTypes->pluck('id')->toArray();
            }
            // ✅ Remove day_type_id 8 and 9 if total allowed hours < 24
            if ($total_hour < 24) {
                $dayTypePlanIds = PlanType::withoutGlobalScopes()->where('library_id', $library_id)->whereIn('day_type_id', [8, 9])->pluck('id')->toArray();
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
            // Log::info('Booking store started', ['uuid' => $uuid, 'request' => $request->all()]);

            $branch = Branch::where('uuid', $uuid)->firstOrFail();
            // Log::info('Branch found', ['branch_id' => $branch->id]);

            // Build validation rules
            $rules = [
                'name'           => 'required|string|max:191',
                // 'email'          => 'nullable|email|max:191|unique:bookings,email',
                'mobile'         => 'required|integer|digits_between:8,15',
                'seat_no'        => 'nullable',
                'plan_id'        => 'required|integer|exists:plans,id',
                'plan_type_id'   => 'required|integer|exists:plan_types,id',
                'plan_price_id'  => 'required',
                'plan_start_date'=> 'required|date',
                'payment_mode'   => 'required|in:online,offline',
            ];
            

            if (!$request->has('renewal')) {
                $rules['password'] = 'required|min:6';
            }

            $messages = [
                'password.required' => 'Password is required.',
                'password.min'      => 'Password must be at least 6 characters.',
            ];

            $validated = $request->validate($rules, $messages);
            // Log::info('Validation passed', ['validated' => $validated]);
            if($request->seat_no){
                
                $validated_custom = $this->validateLearnerCustom($branch->id, $request->plan_type_id, $request->seat_no,$branch->library_id);
                if ($validated_custom['error']) {
                  
                   return redirect()->back()->with('error',$validated_custom['message']);
                }
                
            }
            
           
            $months   = Plan::where('id', $validated['plan_id'])->value('plan_id');
            $duration = $months ?? 0;
            $type     = Plan::where('id', $validated['plan_id'])->value('type');

            $start_date = Carbon::parse($validated['plan_start_date'])->addDay();

            switch (strtoupper($type)) {
                case 'DAY':   $endDate = $start_date->copy()->addDays($duration); break;
                case 'WEEK':  $endDate = $start_date->copy()->addWeeks($duration); break;
                case 'MONTH': $endDate = $start_date->copy()->addMonths($duration); break;
                case 'YEAR':  $endDate = $start_date->copy()->addYears($duration); break;
                default:      $endDate = $start_date; break;
            }
            Log::info('Plan dates calculated', [
                'start_date' => $start_date,
                'end_date'   => $endDate,
                'type'       => $type,
                'duration'   => $duration
            ]);

            $transactions = LearnerTransaction::withoutGlobalScopes()
                ->where('id', $request->learner_transaction_id)
                ->first();
            Log::info('Transaction check', ['transaction' => $transactions]);

            if ($transactions) {
                $password     = Learner::where('id', $transactions->learner_id)->value('password');
                $total_amount = $transactions->total_amount;
            } else {
                $password     = Hash::make($validated['password']);
                $total_amount = $validated['plan_price_id'];
            }
            Log::info('Password & Total amount set', ['total_amount' => $total_amount]);

            $seat_type = $request->has('renewal') ? 'qr_renew' : 'qr_seat_book';

            $booking = Booking::create([
                'name'            => $validated['name'],
                // 'email'           => $validated['email'] ?? null,
                'mobile'          => $validated['mobile'],
                'password'        => $password,
                'seat_no'         => $validated['seat_no'] ?? null,
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
            Log::warning('Validation failed', ['errors' => $e->errors()]);
            return redirect()->back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            Log::error('Booking store error: '.$e->getMessage(), [
                'request' => $request->all(),
                'trace'   => $e->getTraceAsString(),
            ]);
            return redirect()->back()
                ->with('error', 'Something went wrong while processing your booking. Please try again.')
                ->withInput();
        }
    }



    public function showPaymentQR($bookingId)
    {
        $booking = Booking::with('branch')->findOrFail($bookingId);

        $branch=Branch::where('id',$booking->branch_id)->first();
        $upiId   = $branch->upi_id ?? 'heenamehandi94145';  // fallback UPI if branch has none
        $payee   = $branch->name ?? 'Library';           // dynamic payee name
        $amount  = $amount ?? 10;                        // dynamic amount
        $currency = 'INR';
        $note     = 'Seat Booking';                      // you can extend this as needed


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

        $planType = PlanType::withoutGlobalScopes()->where('library_id', getLibraryId())->get();
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
            'previous_amount' => 'required',
            'payment_mode' => 'required',
            'discount_type' => 'nullable',
            'diffrence_amount' => 'nullable',
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

         
            $booking=Booking::find($request->booking_id);
            
            if ($request->seat_no && $request->seat_no!='gen') {
                $seat_no = $request->input('seat_no');
            } else {
                $seat_no = $booking->seat_no;
            }
            if($request->direct_validate){
                $planPrice= $booking->plan_price_id;
                $start_date = Carbon::parse($booking->plan_start_date);
                $plan_id = $booking->plan_id;
                $plan_type_id = $booking->plan_type_id;
                $locker_no=null;
                $total_amt=$booking->plan_price_id;
                $new_paid=$booking->plan_price_id;
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
                $pending_amount = $request->input('pending_amount');
                $diff_amount    = $request->input('diffrence_amount');
                $already_paid  =$booking->total_amount;
                
                $refund = 0;
                $pending_refund = 0;

                // Handle difference amount (refund vs pending)
                if ($diff_amount < 0) {
                
                    // refund case
                    $new_paid=$already_paid-$paid_amount;
                    $pending_refund = $new_paid-$total_amt;
                    $refund = abs($paid_amount);
                    
                    $pending_amount = 0;
                    $dr_cr='Dr';
                    
                } else {
                    $new_paid=$already_paid+$paid_amount;
                    // extra payment (pending dues)
                    $pending_amount = $total_amt-$new_paid ;
                    $refund = 0;
                    $pending_refund = 0;
                    $dr_cr='Cr';
                
                }
        
            
                if($pending_amount > 0){
                    return redirect()->back()->with('error', 'Due date is required');
                }

                $start_date = Carbon::parse($request->input('plan_start_date'));
                $plan_id = $request->input('plan_id');
                $plan_type_id = $request->input('plan_type_id');
                $locker_no=$request->input('locker_no');

            }
           
            $months = Plan::where('id', $plan_id)->value('plan_id'); 
            $duration = $months ?? 0;
            
          
            
            
            $planType = PlanType::withoutGlobalScopes()->find($plan_type_id);
          
            $startTime = $planType->start_time;
            $endTime = $planType->end_time;
            $hours = $planType->slot_hours;
        
            $first_record = Hour::first();
            $total_hour = $first_record ? $first_record->hour : 0;
            $type = Plan::where('id', $plan_id)->value('type'); 
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
            
            $extendDay = getExtendDays();

            $inextendDate = Carbon::parse($endDate)->addDays($extendDay);
            if ($inextendDate > Carbon::today() && $start_date <= Carbon::today()) {
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
            
              if ($total_hour === 0) {
                return redirect()->back()->with('error', 'Total available hours not set.');
            }

           

            if ($seat_no && !$request->learner_id) {
                 $existingBookings = $this->getLearnersByLibrary()
                ->where('learner_detail.seat_no', '=', $seat_no)
                ->where('learner_detail.plan_type_id', '=', $plan_type_id)
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

                if ($this->getLearnersByLibrary()->where('learners.seat_no', $seat_no)->where('plan_type_id', $plan_type_id)->where('learners.status', 1)->count() > 0) {
                    return redirect()->back()->with('error', 'This Plan Type Seat already booked');
                }

                if (($this->getLearnersByLibrary()->where('learners.seat_no', $seat_no)->where('learner_detail.status', 1)->sum('hours') + $hours) > $total_hour) {
                     return redirect()->back()->with('error', 'You cannot select this plan because it conflicts with an existing booking. The seat is already reserved for the full library hours on the selected day, so we are unable to process this booking.');
                  
                }

                if(($this->getLearnersByLibrary()->where('learners.seat_no', $seat_no)->where('learner_detail.plan_start_date','>',Carbon::today())->exists())){
                    if($learnerController->checkPlanTypeSeatWise($seat_no,$plan_type_id)==false){
                         return redirect()->back()->with('error', 'You cannot select this plan because it conflicts with an existing future booking. ');
                       
                    }
                }
            }

            $total_cust_hour = Learner::where('seat_no', $seat_no)->where('status', 1)->sum('hours');
        

            if ($hours > ($total_hour - $total_cust_hour)) {
                
                return redirect()->back()->with('error', 'You cannot select this plan type as it exceeds the available hours.');
            }

        

            if (($new_paid > $total_amt) || ($new_paid == 0)) {
                return redirect()->back()->with('error', 'Paid amount is not valid');
               
            }
            if (($pending_amount > 0) && (!$request->due_date)) {
                return redirect()->back()->with('error', 'Paid amount is not valid');
              
            }
            
                
                //     $customerEmail = $booking->email 
                // ? encryptData($booking->email) 
                // : ($request->filled('email') ? encryptData($request->input('email')) : null);
            if($request->learner_id){
                $customer=Learner::find($request->learner_id);
                $customer->seat_no=$seat_no;
                $customer->hours=$hours;
                $customer->save();
            }else{
                $customer = Learner::create([
                'seat_no' => $seat_no,
                'name' => $booking->name,
                'mobile' =>encryptData($booking->mobile),
                // 'email' => $customerEmail,
                // 'dob' => $booking->dob,
                'hours' => $hours,
                'status' => $status,
                'library_id' => getLibraryId(),
                'branch_id' => getCurrentBranch(),
                'password' => $booking->password,
                'learner_no'=>$learnerController->generateLearnerCode(),
                'locker_no'=>$locker_no ?? null ,
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
                'status' => $status,
            ]);

            if ($booking->created_at) {
                $transaction_date = $booking->created_at;
            } else {
                $transaction_date =null;
            }
                   

             $learnerTransaction = LearnerTransaction::create([
                'learner_id' => $customer->id,
                'library_id'        => getLibraryId(),
                'branch_id'         => getCurrentBranch(),
                'learner_detail_id' => $learner_detail->id,
                'total_amount'      => $total_amt,
                'paid_amount'       => $new_paid,
                'pending_amount'    => $pending_amount,
                'locker_amount'     => $locker ?? 0,
                'discount_amount'   => $discount ?? 0,
                'paid_date'         => $transaction_date,
                'is_paid'           => $is_paid ?? 0,
                'due_date'        => $request->due_date ?? null,
                'refund'        => $pending_refund,
            ]);
           
            //learner Activity
            $data=[];
            $data['learner_id']=$customer->id;
            $data['particular']='Paid By Trans';
            $data['payment_type']='SEAT ASSIGNMENT';
            $data['payment_mode']=1;
            $data['amount']=$new_paid;
            $data['dr_cr']='Cr';
          
            $learnerController->learnerTransactionActivity($data);

                if ($status == 1) {

                    $learnerController->dataUpdate();
                }
                $booking->delete();

            DB::commit();

            return redirect()->route('learners')->with('success', 'Learner updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'An error occurred: ' . $e->getMessage());
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
        ]);

        $branch = Branch::where('uuid', $uuid)->firstOrFail();
        
        $customer = Learner::withoutGlobalScopes()->where('branch_id', $branch->id)
            ->where('mobile', encryptData($request->input('mobile')))
            ->first();

        if (!$customer) {
            return back()->withErrors(['mobile' => 'No customer found with this mobile.']);
        }

        $customer_detail = LearnerDetail::withoutGlobalScopes()->where('learner_id', $customer->id)
            ->with('plan', 'planType')
            ->latest() // gets the latest detail record
            ->first();
       
        $transaction = LearnerTransaction::withoutGlobalScopes()->where('learner_detail_id', $customer_detail->id)->first();
        if (!$transaction) {
            return back()->withErrors(['mobile' => 'No customer transaction found with this mobile.']);
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
        if ($seatNo) {

          
            // Step 1: Retrieve all bookings for the given seat
            $bookings = Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')   
                ->join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')
                ->where('learner_detail.seat_no', $seatNo)
                ->where('learners.status', 1)
                ->where('learner_detail.status', 1)
                ->where('learners.branch_id', $branch_id)
                ->where('learner_detail.branch_id', $branch_id)
                ->get(['learner_detail.plan_type_id', 'plan_types.start_time', 'plan_types.end_time', 'plan_types.slot_hours']);
          Log::info('Branch Library ID:', ['library_id' => $branchData->library_id]);
            // Step 2: Retrieve all plan types
            $planTypes = PlanType::withoutGlobalScopes()->where('library_id', $branchData->library_id)->get();
            Log::info('Plan types fetched', [
    'count' => $planTypes->count(),
    'plan_type_ids' => $planTypes->pluck('id')
]);

            // Step 3: Initialize an array to store the plan_type_ids to be removed
            $planTypesRemovals = [];

            // Step 4: Calculate total booked hours for the seat
            $totalBookedHours = $bookings->sum('slot_hours');

            $nightseatBooked = LearnerDetail::join('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')->where('learner_detail.seat_no', $seatNo)->where('learner_detail.branch_id', $branch_id)->where('plan_types.library_id', $branchData->library_id)->where('learner_detail.status', 1)->where('plan_types.day_type_id', 9)->exists();

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
                $planTypeId = PlanType::withoutGlobalScopes()->where('library_id', $branchData->library_id)->where('day_type_id', 8)->value('id') ?? 0;
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
            $first_record = Hour::where('branch_id', $branch_id)->first();
            $total_hour = $first_record ? $first_record->hour : null;

            if ($totalBookedHours >= $total_hour) {
                $planTypesRemovals = $planTypes->pluck('id')->toArray();
            }
            // ✅ Remove day_type_id 8 and 9 if total allowed hours < 24
            if ($total_hour < 24) {
                $dayTypePlanIds = PlanType::withoutGlobalScopes()->where('library_id', $branchData->library_id)->whereIn('day_type_id', [8, 9])->pluck('id')->toArray();
                $planTypesRemovals = array_merge($planTypesRemovals, $dayTypePlanIds);
            }
            // Step 6: Filter out the plan_types that match the retrieved plan_type_ids
            $filteredPlanTypes = $planTypes->filter(function ($planType) use ($planTypesRemovals) {
                return !in_array($planType->id, $planTypesRemovals);
            })->map(function ($planType) {
                return ['id' => $planType->id, 'name' => $planType->name];
            })->values(); // Ensure the keys are reset to a continuous numerical index
        } else {

            $first_record = Hour::where('branch_id', $branch_id)->first();
            $total_hour = $first_record ? $first_record->hour : null;

            if ($total_hour < 24) {
                $filteredPlanTypes = PlanType::withoutGlobalScopes()->where('library_id', $branchData->library_id)->whereNotIn('day_type_id', [8, 9])
                    ->select('id', 'name')
                    ->get();
            } else {
                $filteredPlanTypes = PlanType::withoutGlobalScopes()->where('library_id', $branchData->library_id)->select('id', 'name')->get();
            }

        }

        // Return the filtered plan types as JSON
        return response()->json($filteredPlanTypes);
    }



}
