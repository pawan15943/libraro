<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Hour;
use App\Models\LearnerDetail;
use App\Models\Plan;
use App\Models\PlanType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class DemoUserController extends Controller
{   


    public function index()
    {
         $qrbookings=Booking::where('branch_id',getCurrentBranch())->with(['plan','planType'])->get();
        return view('library.demo-enquery',compact('qrbookings'));
        
    }

    public function create()
    {
         $branch = Branch::where('id', getCurrentBranch())->select('id', 'library_id', 'uuid')->firstOrFail();

            $totalSeats =  Hour::withoutGlobalScopes()->where('branch_id',$branch->id)->value('seats');
            $totalHour=Hour::withoutGlobalScopes()->where('branch_id',$branch->id)->value('hour');
         
            $usedSeats = LearnerDetail::withoutGlobalScopes()->select('seat_no', DB::raw('SUM(hour) as used_hours'))
                        ->where('branch_id',$branch->id)
                        ->whereNotNull('seat_no')
                        ->groupBy('seat_no')->where('status',1)
                        ->pluck('used_hours', 'seat_no'); // [seat_no => used_hours]

            $availableSeats = collect();

            $allSeats = collect(generateSeatNumbers());
            $newAvailableSeat = collect();

            for ($seatNo = 1; $seatNo <= $totalSeats; $seatNo++) {
                $usedHours = $usedSeats[$seatNo] ?? 0;

                if ($usedHours < $totalHour) {
                    $seatInfo = $allSeats->firstWhere('main', $seatNo);

                    if ($seatInfo) {
                        $newAvailableSeat->push($seatInfo);
                    } else {
                        $newAvailableSeat->push([
                            'main' => $seatNo,
                            'display' => $seatNo,
                        ]);
                    }
                }
            }
      
        $plans = Plan::withoutGlobalScopes()->where('library_id', $branch->library_id)->whereNull('deleted_at')->get();

        $planType = PlanType::withoutGlobalScopes()->where('branch_id', $branch->id)->whereNull('deleted_at')->get();

        return view('library.demo-enquery-create', compact('branch', 'plans', 'planType','availableSeats','newAvailableSeat'));
        
    }

     public function store(Request $request)
    {
       
        try {
          
            $branch = Branch::where('id', getCurrentBranch())->firstOrFail();
           
            // Build validation rules
            $rules = [
                'name'           => 'required|string|max:191',
                'mobile'         => 'required|integer|digits_between:8,15',
                'email'        => 'nullable',
                'dob'        => 'nullable',
                'profile_picture' => 'nullable',
                'general_seat' => 'nullable|in:yes,no',
    
                'seat_no' => [
                    'nullable',
                    'required_if:general_seat,no'
                ],
                'plan_id'        => 'required|integer|exists:plans,id',
                'plan_type_id'   => 'required|integer|exists:plan_types,id',
                'plan_price_id'  => 'required',
                'plan_start_date'=> 'required|date',
                'payment_mode'   => 'required|in:online,offline,paylater',
            ];
            $messages = [
                'name.required'            => 'Name is required.',
                'name.max'                 => 'Name must not exceed 191 characters.',
                'seat_no.required_if' => 'Seat number is required',
                'mobile.required'          => 'Mobile number is required.',
                'mobile.integer'           => 'Mobile number must contain only digits.',
                'mobile.digits_between'    => 'Mobile number must be between 8 and 15 digits.',

                'plan_id.required'         => 'Please select a plan.',
                'plan_id.exists'           => 'Selected plan is invalid.',

                'plan_type_id.required'    => 'Please select a plan type.',
                'plan_type_id.exists'      => 'Selected plan type is invalid.',

                'plan_price_id.required'   => 'Plan price is required.',

                'plan_start_date.required' => 'Plan start date is required.',
                'plan_start_date.date'     => 'Please enter a valid start date.',

                'payment_mode.required'    => 'Please select a payment mode.',
                'payment_mode.in'          => 'Invalid payment mode selected.',
            ];
            
            

            $validated = $request->validate($rules,$messages);
            $plan_id=$validated['plan_id'];
            $start_date = Carbon::parse($validated['plan_start_date'])->addDay();

            $endDate = getEndDate($plan_id, $start_date,$branch->id);

            $password     = Hash::make($validated['mobile']);
            $total_amount = $validated['plan_price_id'];
            $seat_type =  'demo-bookings';
          
          
            if ($request->hasFile('profile_picture')) {
               
                $this->validate($request, ['profile_picture' => 'mimes:webp,png,jpg,jpeg|max:200']);
                $profile_picture = $request->profile_picture;
                $profile_pictureNewName = "profile_picture" . time() . $profile_picture->getClientOriginalName();
                $profile_picture->move('public/uploade/', $profile_pictureNewName);
                $profile_picture = 'public/uploade/' . $profile_pictureNewName;
            } else {
                 
                $profile_picture = null;
            }

          
            $booking = Booking::create([
                'name'            => $validated['name'],
                'mobile' => encryptData($validated['mobile']),
               'email' => !empty($validated['email']) 
                ? encryptData($validated['email']) 
                : null,

              
                 'dob' => $validated['dob'],
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
                'transaction_id'  => null,
                'type'            => $seat_type,
                'profile_picture' => $profile_picture,
            ]);

            return redirect()
                    ->route('demo-users.index')
                    ->with('success', 'Booking successful! Please complete payment to confirm your seat.');

        } catch (\Illuminate\Validation\ValidationException $e) {
          
             Log::warning('VALIDATION EXCEPTION', [
                'method' => request()->method(),
                'url'    => request()->fullUrl(),
                'errors' => $e->errors(),
            ]);

            return redirect()
                ->route('demo-users.index')
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Exception $e) {
           
             Log::error('BOOKING STORE CRASH', [
                'method'  => request()->method(),
                'url'     => request()->fullUrl(),
                'message' => $e->getMessage(),
            ]);

            return redirect()
                ->route('demo-users.index')
                ->with('error', 'Something went wrong.')
                ->withInput();
        }
    }
}
