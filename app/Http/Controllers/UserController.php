<?php

namespace App\Http\Controllers;

use App\Models\CustomerDetail;

use App\Models\Plan;
use App\Models\PlanType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use DB;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use App\Services\LearnerService;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{   
    protected $learnerService;

    public function __construct(LearnerService $learnerService)
    {
        $this->learnerService = $learnerService;
    }
   

    public function fetchCustomerData($customerId = null, $isRenew = false, $status = 1, $detailStatus = 1)
    {
        $query = Customers::leftJoin('customer_detail', 'customer_detail.customer_id', '=', 'customers.id')
            ->leftJoin('seats', 'customers.seat_no', '=', 'seats.id')
            ->leftJoin('plans', 'customer_detail.plan_id', '=', 'plans.id')
            ->leftJoin('plan_types', 'customer_detail.plan_type_id', '=', 'plan_types.id')
            ->where('customers.status', $status)
            ->where('customer_detail.status', $detailStatus)
            ->select(
                'plan_types.name as plan_type_name',
                'plans.name as plan_name',
                'seats.seat_no',
                'customers.*',
                'plan_types.start_time',
                'plan_types.end_time',
                'customer_detail.customer_id','customer_detail.plan_start_date','customer_detail.plan_end_date','customer_detail.plan_type_id','customer_detail.plan_id','customer_detail.plan_price_id','customer_detail.status',
                'plan_types.image'
            );
    
        if ($customerId) {
            // Fetch a single customer record
            $query->where('customers.id', $customerId);
            
            if ($isRenew) {
                $query->selectRaw('customer_detail.customer_id, customer_detail.plan_start_date, customer_detail.join_date, customer_detail.plan_end_date, customer_detail.plan_type_id, customer_detail.plan_id, customer_detail.plan_price_id, customer_detail.status, 1 as is_renew');
            } else {
                $query->selectRaw('customer_detail.customer_id, customer_detail.plan_start_date, customer_detail.join_date, customer_detail.plan_end_date, customer_detail.plan_type_id, customer_detail.plan_id, customer_detail.plan_price_id, customer_detail.status, 0 as is_renew');
            }
    
            return $query->firstOrFail();
        } else {
            // Fetch multiple customer records
            return $query->get();
        }
    }
    
    public function changePassword(Request $request)
    {
        $this->validate($request, [
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        // Get the authenticated user based on the guard
        $user = Auth::guard('library')->user();

        // Check if the current password matches
        if (!Hash::check($request->current_password, $user->password)) {
            return redirect()->back()->with('error', 'Current password is incorrect');
            
        }

        // Update the password
        $user->password = Hash::make($request->new_password);
        $user->save();

        return redirect()->back()->with('success', 'Password updated successfully');
    }

    public function changePasswordView(){
        return view('auth.passwords.reset-password');
    }
   
}
