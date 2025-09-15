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

class QrEntryController extends Controller
{
   public function showOptions($uuid)
    {
        $branch = Branch::where('uuid', $uuid)->where('status', 1)->firstOrFail();

        return view('qrcode.options', compact('branch'));
    }

  
    public function bookSeat($branchUuid)
    {
        $branch = Branch::where('uuid', $branchUuid)->firstOrFail();

      
        $plans = Plan::withoutGlobalScopes()->where('library_id', $branch->library_id)->get();

        $planType = PlanType::withoutGlobalScopes()->where('library_id', $branch->library_id)->get();

        return view('qrcode.booking', compact('branch', 'plans', 'planType'));
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

    public function store(Request $request, $uuid)
    {
       
        $branch = Branch::where('uuid', $uuid)->firstOrFail();

     // Build validation rules
        $rules = [
            'name'           => 'required|string|max:191',
            'email'          => 'nullable|email|max:191|unique:bookings,email',
            'mobile'         => 'required|integer|digits_between:8,15',
            'dob'            => 'nullable|date',
            'plan_id'        => 'required|integer|exists:plans,id',
            'plan_type_id'   => 'required|integer|exists:plan_types,id',
            'plan_price_id'  => 'required',
            'plan_start_date'=> 'required|date',
            'payment_mode'   => 'required|in:online,offline',
        ];

        // Only add password rule if not renewal
        if (!$request->has('renewal')) {
            $rules['password'] = 'required|min:6';
        }

        $messages = [
            'name.required'            => 'Please enter your full name.',
            'name.max'                 => 'Name should not exceed 191 characters.',
            'email.email'              => 'Please provide a valid email address.',
            'email.unique'             => 'This email is already registered.',
            'email.max'                => 'Email should not exceed 191 characters.',
            'mobile.required'          => 'Mobile number is required.',
            'mobile.digits_between'    => 'Mobile number must be between 8 to 15 digits.',
            'password.required'        => 'Password is required.',
            'password.min'             => 'Password must be at least 6 characters.',
            'dob.date'                 => 'Please enter a valid date of birth.',
            'plan_id.required'         => 'Please select a plan.',
            'plan_id.exists'           => 'Selected plan does not exist.',
            'plan_type_id.required'    => 'Please select a plan type.',
            'plan_type_id.exists'      => 'Selected plan type does not exist.',
            'plan_price_id.required'   => 'Please select a plan price.',
            'plan_start_date.required' => 'Please choose a start date for the plan.',
            'plan_start_date.date'     => 'Plan start date must be a valid date.',
            'payment_mode.required'    => 'Please select a payment mode.',
            'payment_mode.in'          => 'Payment mode must be either online or offline.',
        ];

        // Run validation
        $validated = $request->validate($rules, $messages);


            $months = Plan::where('id', $validated['plan_id'])->value('plan_id');
            $duration = $months ?? 0;
            $type     = Plan::where('id', $validated['plan_id'])->value('type'); 

            
            $start_date = Carbon::parse($validated['plan_start_date'])->addDay();

             // Calculate end date
            switch (strtoupper($type)) {
                case 'DAY':   $endDate = $start_date->copy()->addDays($duration); break;
                case 'WEEK':  $endDate = $start_date->copy()->addWeeks($duration); break;
                case 'MONTH': $endDate = $start_date->copy()->addMonths($duration); break;
                case 'YEAR':  $endDate = $start_date->copy()->addYears($duration); break;
                default:      $endDate = $start_date; break;
            }
        $transactions = LearnerTransaction::withoutGlobalScopes()->where('id', $request->learner_transaction_id)->first();
        if($transactions){
            $password=Learner::where('id',$transactions->learner_id)->value('password');
            $total_amount=$transactions->total_amount;
           
        }else{
            $password=Hash::make($validated['password']);
            $total_amount=$validated['plan_price_id'];
        }
        if ($request->has('renewal')){
            $seat_type='qr_renew';
        }else{
            $seat_type='qr_seat_book';
        }


        // ✅ Save booking
       $booking = Booking::create([
            'name'            => $validated['name'],
            'email'           => $validated['email'] ?? null,
            'mobile'          => $validated['mobile'],
            'password'        => $password,
            'dob'             => $validated['dob'] ?? null,

            'branch_id'       => $branch->id,
            'plan_id'         => $validated['plan_id'],
            'plan_type_id'    => $validated['plan_type_id'],
            'plan_price_id'   => $validated['plan_price_id'],

            'plan_start_date' => $validated['plan_start_date'],
            'plan_end_date'   => $endDate,

            'payment_mode'    => $validated['payment_mode'],
            'status'          => 'pending',
            'total_amount' =>$total_amount,
            'transaction_id' =>$transactions ? $transactions->id : null,
            'type'=>$seat_type,
        ]);
         if ($validated['payment_mode'] === 'online') {
            return redirect()
                ->route('booking.payment.qr', $booking->id)
                ->with('success', 'Booking created! Please complete your payment.');
        } else {
            return redirect()
                ->route('booking.offline.details', $booking->id)
                ->with('success', 'Booking created! Please visit the branch to pay.');
        }

        return redirect()->back()->with('success', 'Booking request submitted. Awaiting confirmation.');
    }

    public function showPaymentQR($bookingId)
    {
        $booking = Booking::with('branch')->findOrFail($bookingId);
        $upiLink = "upi://pay?pa=heenamehandi94145@ybl&pn=Test+Library&am=10&cu=INR&tn=Seat+Booking+Test";

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
            $this->validate($request, ['payment_screenshot' => 'mimes:webp,png,jpg,jpeg|max:200']);
            $payment_screenshot = $request->payment_screenshot;
            $payment_screenshotNewName = "payment" . time() . $payment_screenshot->getClientOriginalName();
            $payment_screenshot->move('public/uploade', $payment_screenshotNewName);
            $payment_screenshot = 'public/uploade' . $payment_screenshotNewName;
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

        return view('qrcode.verify_request', compact('customer','planType','plans'));
    }

      public function requestApproveEdit(Request $request)
    {
        
      
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


        DB::beginTransaction();

        try {
            $learnerController = app(\App\Http\Controllers\LearnerController::class);

         
            $booking=Booking::find($request->booking_id);
            
            if ($request->seat_no && $request->seat_no!='gen') {
                $seat_no = $request->input('seat_no');
            } else {
                $seat_no = null;
            }
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
            $already_paid  =$booking->plan_price_id;
            
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
            $months = Plan::where('id', $plan_id)->value('plan_id'); 
            $duration = $months ?? 0;
            $type = Plan::where('id', $plan_id)->value('type'); 
          
            $plan_type_id = $request->input('plan_type_id');
            
            $planType = PlanType::withoutGlobalScopes()->find($plan_type_id);
          
            $startTime = $planType->start_time;
            $endTime = $planType->end_time;
            $hours = $planType->slot_hours;
        
            $first_record = Hour::first();
            $total_hour = $first_record ? $first_record->hour : 0;

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
            $is_paid = 1;
            $payment_mode = 1;
         
           
            if ($seat_no) {

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


            if (($new_paid > $total_amt) || ($new_paid == 0)) {
                return redirect()->back()->with('error', 'Paid amount is not valid');
               
            }
            if (($pending_amount > 0) && (!$request->due_date)) {
                return redirect()->back()->with('error', 'Paid amount is not valid');
              
            }
            
           
            $customerEmail = $booking->email 
        ? encryptData($booking->email) 
        : ($request->filled('email') ? encryptData($request->input('email')) : null);

           $customer = Learner::create([
            'seat_no' => $seat_no,
            'name' => $booking->name,
            'mobile' =>$booking->mobile,
            'email' => $customerEmail,
            'dob' => $booking->dob,
            'hours' => $hours,
            'status' => $status,
            'library_id' => getLibraryId(),
            'branch_id' => getCurrentBranch(),
            'password' => $booking->password,
            'learner_no'=>$learnerController->generateLearnerCode(),
            'locker_no'=>$request->input('locker_no') ?? null ,
        ]);
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
                'due_date'        => $request->due_date,
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
public function renewStore(Request $request, $uuid)
{
    $branch = Branch::where('uuid', $uuid)->firstOrFail();

    $request->validate([
        'mobile' => 'required|digits:10',
        'learner_detail_id'=>'required',
        'learner_transaction_id'=>'required',
    ]);

    $customer = Learner::where('branch_id', $branch->id)
        ->where('mobile', $request->mobile)
        ->firstOrFail();

    // Get latest detail for reference
    $lastDetail = LearnerDetail::find($request->learner_detail_id);

    if (!$lastDetail) {
        return back()->withErrors(['mobile' => 'No existing plan found for this customer.']);
    }

   $start_date = Carbon::parse($lastDetail->plan_end_date)->addDay();
    $duration  = Plan::withoutGlobalScopes()->where('id', $lastDetail->plan_id)->value('plan_id'); 
        $type  = Plan::withoutGlobalScopes()->where('id', $lastDetail->plan_id)->value('type'); 
   
    // Calculate end date
    switch (strtoupper($type)) {
        case 'DAY':   $endDate = $start_date->copy()->addDays($duration); break;
        case 'WEEK':  $endDate = $start_date->copy()->addWeeks($duration); break;
        case 'MONTH': $endDate = $start_date->copy()->addMonths($duration); break;
        case 'YEAR':  $endDate = $start_date->copy()->addYears($duration); break;
        default:      $endDate = $start_date; break;
    }
    $transactions = LearnerTransaction::withoutGlobalScopes()->where('id', $request->learner_transaction_id)->first();

    // Store new renewal record
    $learnerController = app(\App\Http\Controllers\LearnerController::class);
    $learner_detail = LearnerDetail::create([
        'library_id' => $branch->library_id,
        'branch_id' => $branch->id,
        'learner_id' => $lastDetail->learner_id,
        'plan_id'         => $lastDetail->plan_id,
        'plan_type_id'    => $lastDetail->plan_type_id,
        'plan_price_id' =>  $lastDetail->plan_price_id,
        'plan_start_date' => $start_date->format('Y-m-d'),
        'plan_end_date' => $endDate->format('Y-m-d'),
        'join_date' =>$lastDetail->join_date,
        'hour' => $lastDetail->hour,
        'seat_no' => $lastDetail->seat_no,
        'payment_mode' => 1,
        'is_paid' =>0,
        'status' => 0,
    ]);
    LearnerTransaction::create([
                'learner_id' => $lastDetail->learner_id,
                'library_id' => $branch->library_id,
                'branch_id' => $branch->id,
                'learner_detail_id' => $learner_detail->id,
                'total_amount'      => $transactions->total_amount,
                'paid_amount'       => $transactions->paid_amount,
                'pending_amount'    => $transactions->pending_amount,
                'locker_amount'     =>$transactions->locker_amount,
                'discount_amount'   => $transactions->discount_amount,
                'paid_date'         =>date('Y-m-d'),
                'is_paid'           =>0,
                'due_date'        => $transactions->due_date,
                'refund'        => $transactions->refund,
            ]);
           
            //learner Activity
            $data=[];
            $data['learner_id']=$customer->id;
            $data['particular']='Paid By Trans';
            $data['payment_type']='RENEW';
            $data['payment_mode']=1;
            $data['amount']=$transactions->paid_amount;
            $data['dr_cr']='Cr';
          
            $learnerController->learnerTransactionActivity($data);

    return redirect()
        ->route('renew.form', $branch->uuid)
        ->with('success', 'Plan renewed successfully!');
}




}
