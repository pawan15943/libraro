<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Floor;
use App\Models\Hour;
use App\Models\Library;
use App\Models\LibraryTransaction;
use App\Models\LibraryUser;
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
use Illuminate\Support\Facades\Auth;

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
    | Dashboard API
    |--------------------------------------------------------------------------
    */

    public function dashboard(Request $request, DashboardService $service)
    {
        $validated = $request->validate([
            // 'branch_id' => 'nullable|exists:branches,id',
            'type'      => 'nullable|in:daily,monthly'
        ]);

        $libraryId = authLibraryId();


        $type = $validated['type'] ?? 'daily';

        $data = $service->getDashboardData(getCurrentBranch(), $type);

        return response()->json([
            'status' => true,
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
            $user = Auth::guard('library_api')->user();

            // ✅ Check branch belongs to same library
            $libraryId = $user instanceof \App\Models\Library 
                ? $user->id 
                : $user->library_id;

            $branch = Branch::where('id', $branchId)
                ->where('library_id', $libraryId)
                ->first();

            if (!$branch) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Unauthorized branch access'
                ], 403);
            }

            // ✅ Update current branch
            if ($user instanceof \App\Models\Library) {
                $user->update(['current_branch' => $branchId]);
            } else {
                $user->update(['current_branch' => $branchId]);
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
                'message' => 'Something went wrong',
                'error'   => $e->getMessage() // optional (remove in production)
            ], 500);
        }
    }

    public function expenseList(Request $request)
    {
        $libraryId = authLibraryId();
        $branchId  = getCurrentBranch();

        $query = DB::table('monthly_expense')->where('library_id', $libraryId)
            ->where('branch_id', $branchId);

        // 🔎 Filter by date
        if ($request->filled('date')) {
            $month = date('m', strtotime($request->date));
            $year  = date('Y', strtotime($request->date));

            $query->where('month', $month)
                ->where('year', $year);
        } else {
            // default current month
            $query->where('month', date('m'))
                ->where('year', date('Y'));
        }

        $expenses = $query->orderBy('id', 'desc')->get();

        return response()->json([
            'status' => true,
            'data'   => $expenses
        ]);
    }

    

    public function expenseSave(Request $request)
    {
        
        $validator =  $request->validate([
            'id'         => 'nullable|exists:monthly_expense,id',
            'expense_id' => 'required|integer|exists:expenses,id',
            'amount'     => 'required|numeric|min:1',
            'date'       => 'required|date',
        ]);

        

        try {
            $libraryId = authLibraryId();
            $branchId  = getCurrentBranch();

            $month = date('m', strtotime($request->date));
            $year  = date('Y', strtotime($request->date));

            if ($request->id) {
                // 🔴 SECURITY CHECK
                $exists = DB::table('monthly_expense')
                    ->where('id', $request->id)
                    ->where('library_id', $libraryId)
                    ->exists();

                if (!$exists) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Expense does not belong to this library'
                    ], );
                }
                // 🔵 UPDATE
                DB::table('monthly_expense')
                    ->where('id', $request->id)
                    ->update([
                        'expense_id' => $request->expense_id,
                        'amount'     => $request->amount,
                        'month'      => $month,
                        'year'       => $year,
                        'updated_at' => now(),
                    ]);

                $message = 'Expense updated';

            } else {
                // 🟢 INSERT
                $id = DB::table('monthly_expense')->insertGetId([
                    'library_id' => $libraryId,
                    'branch_id'  => $branchId,
                    'expense_id' => $request->expense_id,
                    'amount'     => $request->amount,
                    'month'      => $month,
                    'year'       => $year,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $message = 'Expense added';
            }

            return response()->json([
                'status'  => true,
                'message' => $message
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Something went wrong'
            ], 500);
        }
    }

    public function templateList(Request $request)
    {
        try {
            $libraryId = authLibraryId();
            $templateMap = $this->libraryMessageTemplateMap();

            $globalTemplates = DB::table('notification_templates')
                ->where('is_active', 1)
                ->where(function ($query) use ($templateMap) {
                    foreach ($templateMap as $template) {
                        $query->orWhere(function ($q) use ($template) {
                            $q->where('operation_id', $template['operation_id'])
                                ->where('type', $template['type']);
                        });
                    }
                })
                ->select('operation_id', 'type', 'template_message')
                ->limit(count($templateMap))
                ->get()
                ->keyBy(function ($item) {
                    return $item->operation_id . '_' . $item->type;
                });

            $customTemplates = DB::table('custom_notification_templates')
                ->where('library_id', $libraryId)
                ->where(function ($query) use ($templateMap) {
                    foreach ($templateMap as $template) {
                        $query->orWhere(function ($q) use ($template) {
                            $q->where('operation_id', $template['operation_id'])
                                ->where('type', $template['type']);
                        });
                    }
                })
                ->select('operation_id', 'type', 'template_message')
                ->limit(count($templateMap))
                ->get()
                ->keyBy(function ($item) {
                    return $item->operation_id . '_' . $item->type;
                });

            $final = [];

            foreach ($templateMap as $responseKey => $template) {

                $key = $template['operation_id'] . '_' . $template['type'];

                $message = isset($customTemplates[$key])
                    ? $customTemplates[$key]->template_message
                    : ($globalTemplates[$key]->template_message ?? '');

                $final[$responseKey] = $message;
            }

            return response()->json([
                'status' => true,
                'data'   => $final
            ]);
        } catch (\Throwable $e) {
            \Log::error('Template List Error: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong'
            ], 500);
        }
    }

    public function templateUpdate(Request $request)
    {
        $templateMap = $this->libraryMessageTemplateMap();
        $templates = [];

        if ($request->has('templates')) {
            $request->validate([
                'templates' => 'required|array|min:1',
                'templates.*.operation_id' => 'required|integer|exists:operations,id',
                'templates.*.type' => 'required|in:text,waba',
                'templates.*.template_message' => 'required|string',
            ]);

            $templates = $request->templates;
        } else {
            $validator = Validator::make($request->all(), [
                'waba_reminder' => 'nullable|string',
                'waba_pending_payment' => 'nullable|string',
                'waba_birthday' => 'nullable|string',
                'text_reminder' => 'nullable|string',
                'text_pending_payment' => 'nullable|string',
                'text_birthday' => 'nullable|string',
            ]);

            $validator->after(function ($validator) use ($request, $templateMap) {
                $hasTemplate = false;

                foreach (array_keys($templateMap) as $key) {
                    if ($request->has($key)) {
                        $hasTemplate = true;
                        break;
                    }
                }

                if (! $hasTemplate) {
                    $validator->errors()->add('templates', 'At least one template message is required.');
                }
            });

            $validator->validate();

            foreach ($templateMap as $key => $template) {
                if (! $request->has($key)) {
                    continue;
                }

                $templates[] = [
                    'operation_id' => $template['operation_id'],
                    'type' => $template['type'],
                    'template_message' => $request->input($key),
                ];
            }
        }

        try {
            $libraryId = authLibraryId();

            DB::beginTransaction();

            foreach ($templates as $item) {

                DB::table('custom_notification_templates')->updateOrInsert(
                    [
                        'library_id'   => $libraryId,
                        'operation_id' => $item['operation_id'],
                        'type'         => $item['type'],
                    ],
                    [
                        'template_message' => $item['template_message'],
                        'is_active'        => 1,
                        'is_custom'        => 1,
                        'updated_at'       => now(),
                        'created_at'       => now(),
                    ]
                );
            }

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => 'Templates updated successfully'
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            \Log::error('Template Bulk Update Error: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong'
            ], 500);
        }
    } 

    private function libraryMessageTemplateMap()
    {
        return [
            'waba_reminder' => [
                'operation_id' => 11,
                'type' => 'waba',
            ],
            'waba_pending_payment' => [
                'operation_id' => 18,
                'type' => 'waba',
            ],
            'waba_birthday' => [
                'operation_id' => 19,
                'type' => 'waba',
            ],
            'text_reminder' => [
                'operation_id' => 11,
                'type' => 'text',
            ],
            'text_pending_payment' => [
                'operation_id' => 18,
                'type' => 'text',
            ],
            'text_birthday' => [
                'operation_id' => 19,
                'type' => 'text',
            ],
        ];
    }
    
    public function todayFinancial(Request $request,DashboardService $service) {

        $data = $service->todayFinancialData($request);

        return response()->json([

            'status' => true,

            'summary' => [

                'today_booking_income' =>
                    $data['today_booking_amt'],

                'today_other_income' =>
                    $data['today_other_amt'],

                'today_expense' =>
                    $data['today_expense'],

                'today_refund' =>
                    $data['today_refund'],

                'today_pending' =>
                    $data['today_pending'],

                'today_total_revenue' =>
                    $data['total_revenue'],
            ],

            'transactions' => $data['collection']
        ]);
    }

    public function monthlyFinancial(Request $request,DashboardService $service) {

    $data = $service->monthlyFinancialData($request);

    return response()->json([

        'status' => true,

        'summary' => [

            'monthly_booking_income' =>
                $data['monthly_income'],

            'monthly_other_income' =>
                $data['other_total_income'],

            'monthly_expense' =>
                $data['monthly_expense'],

            'monthly_refund' =>
                $data['monthly_refund'],

            'monthly_pending' =>
                $data['monthly_pending'],

            'monthly_total_revenue' =>
                $data['monthlyBalance'],
        ],

        // ✅ monthly cards list UI
        'list' => $data['monthly_balance'],

        // ✅ if filter clicked
        'transactions' => $data['collection']
    ]);
}
}
