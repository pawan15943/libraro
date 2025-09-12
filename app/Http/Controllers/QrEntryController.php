<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Library;
use App\Models\Plan;
use App\Models\PlanType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

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

      
        $plans = Plan::where('library_id', $branch->library_id)->get();

        $planType = PlanType::where('library_id', $branch->library_id)->get();

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

        // ✅ Validation rules
        $validated = $request->validate([
            'name'           => 'required|string|max:191',
            'email'          => 'nullable|email|max:191|unique:bookings,email',
            'mobile'         => 'required|digits_between:8,15',
            'password'       => 'required|min:6',
            'dob'            => 'nullable|date',
            'plan_id'        => 'required|integer|exists:plans,id',
            'plan_type_id'   => 'required|integer|exists:plan_types,id',
            'plan_price_id'  => 'required',
            'plan_start_date'=> 'required|date',
            'payment_mode'   => 'required|in:online,offline',
        ]);
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

        // ✅ Save booking
       $booking = Booking::create([
            'name'            => $validated['name'],
            'email'           => $validated['email'] ?? null,
            'mobile'          => $validated['mobile'],
            'password'        => Hash::make($validated['password']),
            'dob'             => $validated['dob'] ?? null,

            'branch_id'       => $branch->id,
            'plan_id'         => $validated['plan_id'],
            'plan_type_id'    => $validated['plan_type_id'],
            'plan_price_id'   => $validated['plan_price_id'],

            'plan_start_date' => $validated['plan_start_date'],
            'plan_end_date'   => $endDate,

            'payment_mode'    => $validated['payment_mode'],
            'status'          => 'pending',
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

        $planType = PlanType::where('library_id', getLibraryId())->get();

        return view('qrcode.verify_request', compact('customer','planType','plans'));
    }



}
