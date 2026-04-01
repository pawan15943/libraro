<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Floor;
use App\Models\Hour;
use App\Models\Library;
use App\Models\LibraryTransaction;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\PlanType;
use App\Models\Subscription;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LibraryController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | library detail API
    |--------------------------------------------------------------------------
    */

   public function getLibraryDetail()
   {
      $libraryId = authLibraryId();

      // Library detail
      $library = Library::select( 'id as library_id','library_name','email as library_email','library_mobile', 'current_branch')->findOrFail($libraryId);

      // Branches
      $branches = Branch::where('library_id', $libraryId)
         ->where('status', 1)
         ->select('id', 'name')
         ->get();
       $getPaymentUpi=  Branch::where('library_id', $libraryId)
         ->where('status', 1)->where('upi_id','!=',null)
         ->select('upi_id')
         ->first();
     // ✅ Selected Branch Detail
        $selectedBranch = Branch::where('id', $library->current_branch)
            ->select('id', 'name', 'library_logo')
            ->first();

      // Active plan with subscription name
      $activePlan = LibraryTransaction::where('library_transactions.library_id', $libraryId)
         ->where('library_transactions.status', 1)
         ->leftJoin('subscriptions', 'subscriptions.id', '=', 'library_transactions.subscription')
         ->select(
               'library_transactions.subscription as plan_id',
               'subscriptions.name as plan_name',
               'library_transactions.month',
               'library_transactions.start_date',
               'library_transactions.end_date',
               'library_transactions.status',
               'library_transactions.paid_amount'
         )
         ->latest('library_transactions.id')
         ->first();

      $planData = null;

      if ($activePlan) {

         $planTypes = [
               1  => 'monthly',
               3  => 'three_monthly',
               6  => 'six_monthly',
               12 => 'yearly',
               24 => 'two_yearly',
         ];

         $planType = $planTypes[$activePlan->month] ?? $activePlan->month . '_months';

         $planData = [
               'plan_id'    => $activePlan->plan_id,
               'plan_name'  => $activePlan->plan_name ?? '',
               'plan_type'  => $planType,
               'price'      => (string) ($activePlan->paid_amount ?? ''),
               'start_date' => $activePlan->start_date,
               'end_date'   => $activePlan->end_date,
               'status'     => $activePlan->status ? 'active' : 'inactive',
         ];
      }

      return response()->json([
         'status'  => true,
         'message' => 'Library data fetched successfully',
         'data'    => [
               'library_id'     => $library->library_id,
               'library_name'   => $library->library_name,
               'library_email'  => $library->library_email,
               'library_mobile' => $library->library_mobile,
               'pyment_upi'     => $getPaymentUpi->upi_id ?? '',
               'branches'       => $branches,
               'active_plan'    => $planData,
                 // ✅ Image (FROM BRANCH)
                'library_image' => !empty($selectedBranch->library_logo)
                   ? asset('public/'.$selectedBranch->library_logo)
                : asset('public/img/user.png'),

                // ✅ Selected Branch
                'selected_branch' => [
                    'id'   => $selectedBranch->id ?? null,
                    'name' => $selectedBranch->name ?? ''
                ],
              
         ]
      ]);
   }

   /*
    |--------------------------------------------------------------------------
    | Current Branch API
    |--------------------------------------------------------------------------
    */

//   public function getCurrentBranchDetail()
//    {
//       $branchId  = getCurrentBranch();
//       $libraryId = authLibraryId();

//       // Branch details
//       $branch = Branch::select(
//          'name as branch_name',
//          'founder_day as founded_date',
//          'email',
//          'mobile as contact_number',
//          'upi_id',
//          'extend_days',
//          'locker_amount'
//       )->where('id', $branchId)->first();

//       // Branch master
//       $branchMaster = Hour::where('branch_id', $branchId)
//          ->select(
//                'seats as total_seats',
//                'hour as operating_hours'
//          )->first();

//       // Plans
//       $plans = Plan::where('library_id', $libraryId)
//          ->get();

//       // Floors
//       $floors = Floor::where('branch_id', $branchId)
//          ->select(
//                'floor_no',
//                'name as floor_name',
//                'from_seat',
//                'to_seat',
//                'total_seats'
//          )->get();

//       // Shifts
//       $shifts = collect();

//       if ($branchId) {
//             // PlanPrice::leftJoin('plan_prices', 'plan_prices.plan_type_id', '=', 'plan_types.id')
//          $shifts = PlanType::withoutGlobalScopes()
//                ->where('branch_id', $branchId)
//                ->select(
//                   'name',
//                   'day_type_id as type',
//                   'name as custom_name',
//                   'start_time',
//                   'end_time',
//                   'slot_hours as duration_hours',
//                )
//                ->get();
//       }

//       return response()->json([
//          'status'  => true,
//          'message' => 'Branch data fetched successfully',
//          'data'    => [
//                'branch_details' => $branch ?? [],

//                'branch_master' => [
//                   'total_seats'      => $branchMaster->total_seats ?? 0,
//                   'operating_hours'  => $branchMaster->operating_hours ?? 0,
//                   'extend_days'      => $branch->extend_days ?? 0,
//                   'locker_amount'    => $branch->locker_amount ?? 0,
//                ],

//                'plan'   => $plans,
//                'floors' => $floors,
//                'shifts' => $shifts
//          ]
//       ]);
//    }



    /*
    |--------------------------------------------------------------------------
    | Dashboard API
    |--------------------------------------------------------------------------
    */

    public function dashboard(Request $request, DashboardService $service)
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'type'      => 'nullable|in:daily,monthly'
        ]);

        $libraryId = authLibraryId();

      //   $library = auth('library_api')->user();

      //   $branches = Branch::where('library_id', $library->id)
      //    ->where('status', 1)
      //    ->select('id', 'name')
      //    ->get();

        // 🔐 Security: ensure branch belongs to library
      //   $branch =  $branches->pluck('id')->contains($library->current_branch)? $library->current_branch
      //          : $branches->first()->id;

        $type = $validated['type'] ?? 'daily';

        $data = $service->getDashboardData(getCurrentBranch(), $type);

        return response()->json([
            'status' => true,
            'code' => 200,
            'data' => $data
        ]);
    }

    public function uploadTempImages(Request $request)
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'image|mimes:jpg,jpeg,png,webp|max:2048'
        ]);

        $uploadedFiles = [];

        foreach ($request->file('files') as $file) {

            // Unique name generate
            $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();

            // Store in temp folder
            $path = $file->storeAs('temp', $fileName, 'public');

            $uploadedFiles[] = [
                'temp_path' => $path, // use this in next API
                'url' => asset('storage/' . $path)
            ];
        }

        return response()->json([
            'status' => true,
            'message' => 'Files uploaded successfully',
            'files' => $uploadedFiles
        ]);
    }

    public function switchBranch(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|integer|min:1|exists:branches,id'
        ]);

        DB::beginTransaction();

        try {

            $branchId = $request->branch_id;

            // ✅ Get logged-in user (multi-guard safe)
            if (Auth::guard('library_api')->check()) {

                $user = Auth::guard('library_api')->user();

                // ✅ Optional: check branch belongs to library
                $isValid = Branch::where('id', $branchId)
                    ->where('library_id', $user->id)
                    ->exists();

                if (!$isValid) {
                    return response()->json([
                        'status'  => false,
                        'message' => 'Invalid branch for this library'
                    ], 403);
                }

              Library::where('id', $user->id)
                    ->update(['current_branch' => $branchId]);

            } 
            // elseif (Auth::guard('library_user_api')->check()) {

            //     $user = Auth::guard('library_user_api')->user();

            //     // ✅ Optional: validate assigned branches
            //     $isValid = DB::table('branch_user')
            //         ->where('user_id', $user->id)
            //         ->where('branch_id', $branchId)
            //         ->exists();

            //     if (!$isValid) {
            //         return response()->json([
            //             'status'  => false,
            //             'message' => 'You are not assigned to this branch'
            //         ], 403);
            //     }

            //     DB::table('library_users')
            //         ->where('id', $user->id)
            //         ->update(['current_branch' => $branchId]);

            // } 
            else {

                return response()->json([
                    'status'  => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Branch switched successfully',
                'data'    => [
                    'branch_id' => $branchId
                ]
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'status'  => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
       
}
