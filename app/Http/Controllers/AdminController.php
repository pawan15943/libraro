<?php

namespace App\Http\Controllers;

use App\Models\DemoRequest;
use App\Models\Inquiry;
use App\Models\Library;
use App\Models\LibraryTransaction;
use App\Models\Subscription;
use Illuminate\Http\Request;
use DB;
use Carbon\Carbon;

class AdminController extends Controller
{
    public function selectLibrary($id, Request $request)
    {
        \Log::info("library_id " . $id);
        // Validate and store selected library ID in session
        if (Library::find($id)) {
            $request->session()->put('selected_library_id', $id);
        }

        return redirect()->back()->with('success', 'Library selected successfully!');
    }
    public function contactInqueryGet(){
        $data=Inquiry::get();
        return view('administrator.inquery-list',compact('data'));
    }
    public function demoRequestGet(){
        $data=DemoRequest::get();
        return view('administrator.demo-request-list',compact('data'));
    }

    public function libraryPayment($library_id){
            
            $data = Library::where('id', $library_id)
            ->with('subscription.permissions')  
            ->first();
            $plans=Subscription::pluck('name','id');
         
            return view('administrator.library-payment',compact('plans','library_id'));
    }

    public function libraryPaymentStore(Request $request)
    {
        $library_id = (int) trim($request->library_id);

        $subscription = $request->library_type;
        $library_tra =LibraryTransaction::withoutGlobalScopes()->where('library_id', $request->library_id)
            ->orderBy('id', 'DESC')
            ->first();
    
        $status = 1; // Default status
        $month = null;
        $start_date = Carbon::now()->format('Y-m-d'); // Default start date
    
        if ($request->payment == 'new') {
            if ($library_tra &&  Library::withoutGlobalScopes()->where('id', $request->library_id)->where('status',1)->exists()) {
                return redirect()->back()->with('error', 'You can not pay new transaction');
            }
            if ($request->month == 'monthly') {
                $end_date = Carbon::now()->addMonth()->format('Y-m-d');
                $month = 1;
            } elseif ($request->month == 'yearly') {
                $end_date = Carbon::now()->addYear()->format('Y-m-d');
                $month = 12;
            } else {
                $end_date = null;
            }
        }
    
        if ($request->payment == 'renew') {
            if (!$library_tra || !$library_tra->end_date) {
                return redirect()->back()->with('error', 'Please select the right payment.');
            }
            $start_date = Carbon::parse($library_tra->end_date)->addDay()->format('Y-m-d');
    
            if ($request->month == 'monthly') {
                $end_date = Carbon::parse($start_date)->addMonth()->format('Y-m-d');
                $month = 1;
            } elseif ($request->month == 'yearly') {
                $end_date = Carbon::parse($start_date)->addYear()->format('Y-m-d');
                $month = 12;
            }
    
            // Set status based on start date
            $status = ($start_date > date('Y-m-d')) ? 0 : 1;
        }
    
        if ($request->payment == 'pre') {
            if (!$library_tra || !$library_tra->end_date) {
                return redirect()->back()->with('error', 'Previous transaction not found.');
            }
            $start_date = Carbon::parse($library_tra->end_date)->addDay()->format('Y-m-d');
    
            if ($request->month == 'monthly') {
                $end_date = Carbon::parse($start_date)->addMonth()->format('Y-m-d');
                $month = 1;
            } elseif ($request->month == 'yearly') {
                $end_date = Carbon::parse($start_date)->addYear()->format('Y-m-d');
                $month = 12;
            }
    
            $status = 0;
        }
    
        if ($request->payment == 'pending') {
            if (!$library_tra) {
                return redirect()->back()->with('error', 'No previous transaction found.');
            }
            $start_date = $library_tra->start_date;
            $end_date = $library_tra->end_date;
            $month = $library_tra->month;
            $status = $library_tra->status;
        }
    
        $data = [
            'library_id' => $request->library_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'month' => $month,
            'subscription' => $subscription,
            'transaction_id' => $request->transaction_id,
            'payment_mode' => 2,
            'amount' => $request->amount, // Amount should be passed correctly
            'paid_amount' => $request->paid_amount, // Correct paid amount
            'transaction_date' => $request->transaction_date,
            'is_paid' => 1,
            'status' => $status,
        ];
       
        if ($library_tra && in_array($request->payment, ['pending', 'new'])) {
           
            $library_tra->update($data);
        } else {
           
            LibraryTransaction::create($data);
        }
    
        if ($request->payment == 'new') {
            Library::where('id', $request->library_id)->update([
                'library_type' => $subscription,
                'is_paid' => 1,
            ]);
        }
    
        return redirect()->back()->with('success', 'Library payment updated successfully.');
    }


    public function getSubscriptionFees(Request $request){
       
        if(isset($request->library_type) && isset($request->month) && !empty($request->library_type) && !empty($request->month)){
        
            $data=Subscription::where('id',$request->library_type)->first();
            if($request->month=='monthly'){
                $amount=$data->monthly_fees;
            }elseif($request->month=='yearly'){
                $amount=$data->yearly_fees;
            }

            $gst_discount = DB::table('gst_discount')->first(); 

            if ($gst_discount) {
                $gst = $gst_discount->gst ?? 0;       
                $discount = $gst_discount->discount ?? 0; 
            } else {
                $gst = 0;
                $discount = 0;
            }
           
            //First Apply Discount, Then GST
            $discount_amount=$amount*($discount/100);
            $price_after_discount=$amount-$discount_amount;
            $gst_amount=$price_after_discount*($gst/100);
            $final_price=$price_after_discount+$gst_amount;

            return response()->json([
                'status' => 'success',
                'fees' => $amount,
                'gst' => $gst,
                'discount' => $discount,
                'paid_amount' => $final_price,
            ]);
        }

        
    }

    public function libraryUpgrade($id, Request $request){
   
        if (Library::find($id)) {
            $request->session()->put('selected_library_id', $id);
        }
        $plans=Subscription::get();
        return view('library.library-upgrade',compact('plans'));
    }

    public function libraryVerify(Request $request)
    {
       
        // Validate the input
        $request->validate([
            'email' => 'required|email',
            'email_otp' => 'required',
        ]);

        // Find the library by email
        $library = Library::where('email', $request->email)->first();
     

        if (!$library) {
            return redirect()->back()->withErrors(['email' => 'Library not found']);
        }
        
        // Check if the OTP matches
        if ($library->email_otp == $request->email_otp) {
            // Mark email as verified
            $library->email_verified_at = now();
            $library->save();
           
            if ($library && !$library->hasRole('admin', 'library')) {
                // Assign the 'admin' role to the user under the 'library' guard
                $library->assignRole('admin');
            }

            // Redirect to dashboard or wherever you want
            return redirect()->route('library')->with('success', 'Library Created successfully processed.');
        } else {
            return redirect()->back()->withErrors(['email_otp' => 'Invalid OTP. Please try again.']);
        }
    }
}
