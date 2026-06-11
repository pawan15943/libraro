<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Expense;
use App\Models\Floor;
use App\Models\Hour;
use App\Models\LearnerTransactionActivity;
use App\Models\Library;
use App\Models\LibraryTransaction;
use App\Models\LibraryUser;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\PlanType;
use App\Models\Subscription;
use App\Services\DashboardService;
use App\Services\LibraryLifecycleService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

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
      $library = Library::select( 'id as library_id','library_name','email as library_email','library_mobile', 'current_branch','referral_code', 'extend_days')->findOrFail($libraryId);

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
            ->select('id', 'name', 'uuid', 'library_logo','library_address')
            ->first();
      $branchQrValue = $selectedBranch?->uuid
         ? route('qr.branch', $selectedBranch->uuid)
         : '';
      $branchQrSvg = $branchQrValue
         ? (string) QrCode::size(650)->generate($branchQrValue)
         : '';

      // Active plan with subscription name
      $activePlan = LibraryTransaction::where('library_transactions.library_id', $libraryId)
         ->where('library_transactions.status', 1)
         ->leftJoin('subscriptions', 'subscriptions.id', '=', 'library_transactions.subscription')
         ->select(
               'library_transactions.subscription as plan_id',
               'subscriptions.name as plan_name',
               'subscriptions.id as plan_type_id',
               'library_transactions.month',
               'library_transactions.start_date',
               'library_transactions.end_date',
               'library_transactions.status',
               'library_transactions.paid_amount'
         )
         ->latest('library_transactions.id')
         ->first();

      $planData = null;
      $isActive = false;
      $isNotification = false;
      $today = Carbon::today();

      if ($activePlan) {

         $planTypes = [
               1  => 'monthly',
               3  => 'three_monthly',
               6  => 'six_monthly',
               12 => 'yearly',
               24 => 'two_yearly',
         ];

         $planType = $planTypes[$activePlan->month] ?? $activePlan->month . '_months';

         $modeIds = [
               'monthly' => 1,
               'yearly' => 2,
               'three_monthly' => 3,
               'six_monthly' => 4,
               'two_yearly' => 5,
         ];

         $planData = [
               'plan_id'    => $activePlan->plan_id,
               'plan_name'  => $activePlan->plan_name ?? '',
               'plan_type_id' => $modeIds[$planType] ?? '',
               'plan_type'  => $planType,
               'price'      => (string) ($activePlan->paid_amount ?? ''),
               'start_date' => $activePlan->start_date,
               'end_date'   => $activePlan->end_date,
               'status'     => $activePlan->status ? 'active' : 'inactive',
         ];

         if (!empty($activePlan->end_date)) {
            $endDate = Carbon::parse($activePlan->end_date)->startOfDay();
            $extensionDays = (int) ($library->extend_days ?? 0);
            $extensionEndDate = $endDate->copy()->addDays($extensionDays);
            $notificationStartDate = $endDate->copy()->subDays(5);

            $isActive = $today->lte($extensionEndDate);
            $isNotification = $today->gte($notificationStartDate) && $today->lte($extensionEndDate);
         }
      }
    $referralCode = (string) ($library->referral_code ?? '');
    $referralLink = url('/library/register?ref=' . $referralCode);

      return response()->json([
         'status'  => true,
         'message' => 'Library data fetched successfully',
         'data'    => [
               'library_id'     => $library->library_id,
               'library_name'   => $library->library_name,
               'library_email'  => $library->library_email,
               'library_mobile' => $library->library_mobile,
               'library_no'=> $library->library_no ?? '',
               'pyment_upi'     => $getPaymentUpi->upi_id ?? '',
               'branches'       => $branches,
               'active_plan'    => $planData,
               'subscription_active'      => $isActive,
               'subscription_notification'=> $isNotification,
               'referral_code'    => $library->referral_code,
                 // ✅ Image (FROM BRANCH)
                'library_image' => !empty($selectedBranch->library_logo)
                   ? asset('public/'.$selectedBranch->library_logo)
                : asset('public/img/user.png'),
                'referral_link'=>$referralLink,

                // ✅ Selected Branch
                'selected_branch' => [
                    'id'   => $selectedBranch->id ?? null,
                    'name' => $selectedBranch->name ?? '',
                    'address' => $selectedBranch->library_address ?? '',
                    'uuid' => $selectedBranch->uuid ?? '',
                    'branch_qr_value' => $branchQrValue,
                    'branch_qr_svg' => $branchQrSvg,
                ],
              
         ]
      ]);
   }

    public function subscriptions(Request $request)
    {
        $libraryId = authLibraryId();
        $library = Library::select('id', 'extend_days')->findOrFail($libraryId);
        $extensionDays = (int) ($library->extend_days ?? 0);
        $today = Carbon::today();

        $transactions = LibraryTransaction::withoutGlobalScopes()
            ->where('library_id', $libraryId)
            ->where('is_paid', 1)
            ->leftJoin('subscriptions', 'subscriptions.id', '=', 'library_transactions.subscription')
            ->select(
                'library_transactions.id',
                'library_transactions.subscription',
                'library_transactions.month',
                'library_transactions.start_date',
                'library_transactions.end_date',
                'library_transactions.paid_amount',
                'library_transactions.transaction_date',
                'library_transactions.status',
                'subscriptions.name as subscription_name'
            )
            ->orderByDesc('library_transactions.start_date')
            ->orderByDesc('library_transactions.id')
            ->get()
            ->map(function ($transaction) use ($today, $extensionDays) {
                $startDate = $transaction->start_date ? Carbon::parse($transaction->start_date)->startOfDay() : null;
                $endDate = $transaction->end_date ? Carbon::parse($transaction->end_date)->startOfDay() : null;
                $extensionEndDate = $endDate ? $endDate->copy()->addDays($extensionDays) : null;
                $isExtensionPeriod = $endDate
                    && $today->gt($endDate)
                    && $extensionEndDate
                    && $today->lte($extensionEndDate);

                if ($startDate && $startDate->gt($today)) {
                    $subscriptionStatus = 'upcoming';
                } elseif ($extensionEndDate && $today->lte($extensionEndDate)) {
                    $subscriptionStatus = 'active';
                } else {
                    $subscriptionStatus = 'expired';
                }

                $totalDays = max(((int) $transaction->month * 30), 0);
                $usageDate = $today;

                if ($extensionEndDate && $extensionEndDate->lt($today)) {
                    $usageDate = $endDate;
                }

                $usedDays = ($startDate && $today->gte($startDate))
                    ? min($startDate->diffInDays($usageDate) + 1, $totalDays)
                    : 0;

                return [
                    'transaction_id' => $transaction->id,
                    'plan_id' => $transaction->subscription,
                    'plan_name' => (string) ($transaction->subscription_name ?? ''),
                    'plan_type' => $this->mapSubscriptionDuration((int) $transaction->month),
                    'plan_type_id' => $this->mapSubscriptionModeId((int) $transaction->month),
                    'plan_name_color' => $this->mapSubscriptionColor((int) $transaction->subscription),
                    'start_date' => optional($startDate)->format('Y-m-d'),
                    'end_date' => optional($endDate)->format('Y-m-d'),
                  
                    'extension_end_date' => optional($extensionEndDate)->format('Y-m-d'),
                    'amount_paid' => (string) ($transaction->paid_amount ?? '0'),
                    'status' => $subscriptionStatus,
                    'status_label' => ucfirst($subscriptionStatus),
                    'is_extension_period' => $isExtensionPeriod,
                    'days_used_text' => $subscriptionStatus === 'upcoming'
                        ? 'Not started yet'
                        : $usedDays . ' of ' . $totalDays . ' days used',
                    'totaldays'=>$totalDays,
                    'used_days'=>$usedDays,
                    'can_renew' => $subscriptionStatus === 'expired',
                    'download_receipt' => false,
                    'transaction_date' => $transaction->transaction_date,
                ];
            });

        $groupedSubscriptions = [
            'all' => $transactions->values(),
            'active' => $transactions->where('status', 'active')->values(),
            'upcoming' => $transactions->where('status', 'upcoming')->values(),
            'expired' => $transactions->where('status', 'expired')->values(),
        ];

        $hasAnyActiveSubscription = $groupedSubscriptions['active']->count() > 0
            || $groupedSubscriptions['upcoming']->count() > 0;

        return response()->json([
            'status' => true,
            'message' => 'Library subscriptions fetched successfully',
            'data' => [
                'subscription_active' => $hasAnyActiveSubscription,
                'subscriptions' => $groupedSubscriptions,
            ],
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Dashboard API
    |--------------------------------------------------------------------------
    */

    public function dashboard(Request $request, DashboardService $service)
    {
       $branch=getCurrentBranch();
        $data = $service->getDashboardData($branch);

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    public function dashboardRevenue(Request $request, DashboardService $service)
    {
        $validated = $request->validate([
            'type'  => 'nullable|in:date,monthly,yearly',
            'value' => 'nullable|string'
        ]);

        $type = $validated['type'] ?? 'date';
        $value = $validated['value'] ?? null;
        $data = $service->getDashboardRevenue(getCurrentBranch(), $type, $value);

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    public function notifications(Request $request)
    {
        $perPage = (int) $request->input('per_page', 20);
        $perPage = $perPage > 0 && $perPage <= 100 ? $perPage : 20;
        $today = Carbon::today()->toDateString();
        $user = Auth::guard('library_api')->user();

        $query = DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->where('guard', 'library')
            ->where('status', 1)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->select(
                DB::raw('MIN(id) as id'),
                'batch_id',
                'guard',
                'data',
                DB::raw('MIN(read_at) as read_at'),
                DB::raw('MIN(start_date) as start_date'),
                DB::raw('MAX(end_date) as end_date'),
                DB::raw('MAX(created_at) as created_at')
            )
            ->groupBy('batch_id', 'guard', 'data')
            ->orderByDesc(DB::raw('MAX(created_at)'));

        $unreadCount = (clone $query)
            ->havingRaw('MIN(read_at) IS NULL')
            ->get()
            ->count();

        $notifications = $query->paginate($perPage);

        $items = $notifications->getCollection()->map(function ($notification) {
            $data = json_decode($notification->data, true) ?: [];

            return [
                'id' => $notification->id,
                'batch_id' => $notification->batch_id,
                'title' => $data['title'] ?? '',
                'description' => $data['description'] ?? '',
                'link' => $data['link'] ?? '',
                'image' => $data['image'] ?? '',
                'guard' => $notification->guard,
                'is_read' => !empty($notification->read_at),
                'start_date' => $notification->start_date,
                'end_date' => $notification->end_date,
                'created_at' => $notification->created_at,
            ];
        })->values();

        return response()->json([
            'status' => true,
            'message' => 'Notifications fetched successfully',
            'data' => [
                'unread_count' => $unreadCount,
                'notifications' => $items,
                'pagination' => [
                    'current_page' => $notifications->currentPage(),
                    'per_page' => $notifications->perPage(),
                    'total' => $notifications->total(),
                    'last_page' => $notifications->lastPage(),
                    'has_more' => $notifications->hasMorePages(),
                ],
            ],
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
        $request->validate([
            'type' => 'nullable|string',
            'date' => 'nullable|date',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'payment_type' => 'nullable',
            'payment_type.*' => 'nullable|string'
        ]);

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
        $request->validate([
            'type' => 'nullable|string',
            'month' => 'nullable|integer|min:1|max:12',
            'year' => 'nullable|integer|min:2000|max:2100',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'payment_type' => 'nullable',
            'payment_type.*' => 'nullable|string'
        ]);

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

    public function dashboardFinancial(Request $request, DashboardService $service)
    {
        $request->validate([
            'type' => 'required|in:daily,monthly,yearly,custom',
            'list_for' => 'required|in:collection,other_collection,expense,refund,pending,balance',
            'date' => 'nullable|date',
            'month' => 'nullable|integer|min:1|max:12',
            'year' => 'nullable|integer|min:2000|max:2100',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
            'payment_type' => 'nullable',
            'payment_type.*' => 'nullable|string',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        $type = $request->input('type');

        if ($type === 'daily') {
            $request->validate(['date' => 'required|date']);
        } elseif ($type === 'monthly') {
            $request->validate([
                'month' => 'required|integer|min:1|max:12',
                'year' => 'required|integer|min:2000|max:2100',
            ]);
        } elseif ($type === 'yearly') {
            $request->validate(['year' => 'required|integer|min:2000|max:2100']);
        } elseif ($type === 'custom') {
            $request->validate([
                'from_date' => 'required|date',
                'to_date' => 'required|date|after_or_equal:from_date',
            ]);
        }

        $data = $service->dashboardFinancialData($request);

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

      public function expenseList(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $request->validate([

            /*
            |--------------------------------------------------------------------------
            | Month Format Example:
            | 2026-05
            |--------------------------------------------------------------------------
            */

            'month' => 'nullable|date_format:Y-m',

            /*
            |--------------------------------------------------------------------------
            | Expense ID
            |--------------------------------------------------------------------------
            */

            'expense_id' => 'nullable|exists:expenses,id',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Main Query
        |--------------------------------------------------------------------------
        */

        $query = LearnerTransactionActivity::query()

            ->where('payment_type', 'EXPENSE')

            ->where('branch_id', getCurrentBranch());

        /*
        |--------------------------------------------------------------------------
        | Month Filter
        |--------------------------------------------------------------------------
        |
        | Example:
        | 2026-05
        |
        */

        if ($request->filled('month')) {

            $month = Carbon::parse($request->month . '-01');

            $query->whereYear('date', $month->year)
                ->whereMonth('date', $month->month);
        }

        /*
        |--------------------------------------------------------------------------
        | Expense Filter
        |--------------------------------------------------------------------------
        */
        if ($request->filled('expense_id')) {

            /*
            |--------------------------------------------------------------------------
            | Get Expense Names
            |--------------------------------------------------------------------------
            */

            $expenseNames = Expense::whereIn(
                    'id',
                    $request->expense_id
                )
                ->pluck('name')
                ->toArray();

            /*
            |--------------------------------------------------------------------------
            | Apply Filter
            |--------------------------------------------------------------------------
            */

            $query->whereIn('particular', $expenseNames);
        }

        /*
        |--------------------------------------------------------------------------
        | Expense List
        |--------------------------------------------------------------------------
        */

        $expenses = $query

            ->latest('date')

            ->latest('id')

            ->get()

            ->map(function ($item) {

                return [

                    'id' => $item->id,

                    'date' => Carbon::parse($item->date)
                        ->format('Y-m-d'),

                    'expense_name' => $item->particular,

                    'amount' => (string) $item->amount,

                    'payment_mode' => $item->payment_mode,

                    'remark' => $item->remark,

                    'transaction_id' => $item->transaction_id,
                ];
            });

        /*
        |--------------------------------------------------------------------------
        | Total Expense
        |--------------------------------------------------------------------------
        */

        $totalExpense = $query->sum('amount');

        /*
        |--------------------------------------------------------------------------
        | Final Response
        |--------------------------------------------------------------------------
        */

        return response()->json([

            'status' => true,

            'message' => 'Expense list fetched successfully',

            'data' => [

                'total_expense' => (float) $totalExpense,

                'filters' => [

                    'month' => $request->month,

                    'expense_id' => $request->expense_id,
                ],

                'expenses' => $expenses,
            ]
        ]);
    }

    public function expenseSave(Request $request, LibraryLifecycleService $service)
    {
        $validator = Validator::make($request->all(), [

            'id'           => 'nullable|exists:learner_transaction_activity,id',

            'expense_id'   => 'required|integer|exists:expenses,id',

            'amount'       => 'required|numeric|min:1',

            'date'         => 'required|date',

            'payment_mode' => 'required',

            'remark'       => 'nullable|string',
        ]);

         if ($validator->fails()) {

            return response()->json([

                'status' => false,

                'message' => $validator->errors()->first(),
            ]);
        }

        try {

            $expense = $service->storeExpense($request);

            return response()->json([

                'status' => true,

                'message' => $expense['message'],
            ]);

        } catch (\Throwable $e) {

            return response()->json([

                'status' => false,

                'message' => $e->getMessage(),
               

            ], 500);
        }
    }

    public function expenseDelete(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $validator = Validator::make($request->all(), [

            'id' => 'required|integer|exists:learner_transaction_activity,id',
        ]);

         if ($validator->fails()) {

            return response()->json([

                'status' => false,

                'message' => $validator->errors()->first(),
            ]);
        }

        try {

            $expense = LearnerTransactionActivity::where('id', $request->id)

                ->where('payment_type', 'EXPENSE')

                ->where('branch_id', getCurrentBranch())

                ->first();

            /*
            |--------------------------------------------------------------------------
            | Expense Not Found
            |--------------------------------------------------------------------------
            */

            if (!$expense) {

                return response()->json([

                    'status' => false,

                    'message' => 'Expense not found',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Delete Expense
            |--------------------------------------------------------------------------
            */

            $expense->delete();

        

            return response()->json([

                'status' => true,

                'message' => 'Expense deleted successfully',
            ]);

        } catch (\Throwable $e) {


            /*
            |--------------------------------------------------------------------------
            | Error Response
            |--------------------------------------------------------------------------
            */

            return response()->json([

                'status' => false,

                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function about()
    {
        return response()->json([
            'status' => true,
            'message' => 'About Libraro fetched successfully',
            'data' => [
                'title' => 'About Libraro',
                'description' => 'At Libraro, we are dedicated to transforming how libraries operate by providing an intuitive, all-in-one solution for library management. Our goal is to make library operations efficient and effortless for everyone, helping them save time and resources while focusing on what truly matters - their learners.',
                'stats' => [
                    ['label' => 'Library Registered', 'value' => '270+'],
                    ['label' => 'Learners Enrolled', 'value' => '4000+'],
                    ['label' => 'City Covered', 'value' => '50+'],
                ],
                'website' => 'https://www.libraro.in',
            ],
        ]);
    }

    public function support()
    {
        return response()->json([
            'status' => true,
            'message' => 'Support details fetched successfully',
            'data' => [
                'title' => 'How can we help you?',
                'subtitle' => 'Our support team is here to assist you. Connect with us.',
                'channels' => [
                    [
                        'type' => 'call',
                        'title' => 'Call Support',
                        'value' => '+91-8149476982, +91-9428640636',
                    ],
                    [
                        'type' => 'email',
                        'title' => 'Mail Support',
                        'value' => 'support@libraro.in',
                    ],
                    [
                        'type' => 'whatsapp',
                        'title' => 'WhatsApp Support',
                        'value' => 'support@libraro.in',
                    ],
                ],
                'note' => 'We value your time and trust we usually respond in 15-30 min during working hours.',
            ],
        ]);
    }

    public function howToUse(Request $request)
    {
        $validated = $request->validate([
            'language' => 'nullable|in:english,hindi,all',
        ]);

        $language = strtolower($validated['language'] ?? 'all');

        $rows = DB::table('how-to-use')
            ->select('operation_name', 'usage_english', 'usage_hindi')
            ->orderBy('id', 'asc')
            ->get();

        $english = $rows->map(function ($row) {
            return [
                'title' => (string) ($row->operation_name ?? ''),
                'content' => (string) ($row->usage_english ?? ''),
            ];
        })->values();

        $hindi = $rows->map(function ($row) {
            return [
                'title' => (string) ($row->operation_name ?? ''),
                'content' => (string) ($row->usage_hindi ?? ''),
            ];
        })->values();

        $data = [
            'hindi' => $hindi,
            'english' => $english,
        ];

        if ($language === 'english') {
            $data = ['english' => $english];
        } elseif ($language === 'hindi') {
            $data = ['hindi' => $hindi];
        }

        return response()->json([
            'status' => true,
            'message' => 'How to use fetched successfully',
            'data' => $data,
        ]);
    }

    public function videoTutorial()
    {
        return response()->json([
            'status' => true,
            'message' => 'Video tutorials fetched successfully',
            'data' => [
                'title' => 'Video Tutorial',
                'description' => 'Libraro video tutorials help you understand and use every feature of the platform with ease.',
                'sections' => [
                    [
                        'title' => 'Registration Process',
                        'videos' => [
                            ['title' => 'Registration Process', 'url' => 'https://www.youtube.com/watch?v=demo_registration'],
                            ['title' => 'Create Branch', 'url' => 'https://www.youtube.com/watch?v=demo_branch'],
                            ['title' => 'Create Shifts', 'url' => 'https://www.youtube.com/watch?v=demo_shift'],
                        ],
                    ],
                    [
                        'title' => 'How to Use',
                        'videos' => [
                            ['title' => 'Seat Booking', 'url' => 'https://www.youtube.com/watch?v=demo_seat_booking'],
                            ['title' => 'Reporting', 'url' => 'https://www.youtube.com/watch?v=demo_reporting'],
                        ],
                    ],
                ],
            ],
        ]);
    }

    public function faq()
    {
        return response()->json([
            'status' => true,
            'message' => 'FAQ fetched successfully',
            'data' => [
                'title' => 'Frequently Asked Questions',
                'items' => [
                    [
                        'question' => 'How do I book a seat?',
                        'answer' => 'Go to learner section, choose plan and seat, then complete booking.',
                    ],
                    [
                        'question' => 'How can I renew a learner?',
                        'answer' => 'Open learner details and select renew operation with plan and payment details.',
                    ],
                    [
                        'question' => 'How to change branch?',
                        'answer' => 'Use library branch switch API from profile/dashboard section.',
                    ],
                    [
                        'question' => 'How to contact support?',
                        'answer' => 'Use call, email or WhatsApp options in support section.',
                    ],
                ],
            ],
        ]);
    }

    private function mapSubscriptionDuration(int $month): string
    {
        return match ($month) {
            1 => 'Monthly',
            3 => 'Quarterly',
            6 => 'Half Yearly',
            12 => 'Yearly',
            24 => 'Two Yearly',
            default => $month . ' Months',
        };
    }

    private function mapSubscriptionModeId(int $month): int|string
    {
        return match ($month) {
            1 => 1,
            12 => 2,
            3 => 3,
            6 => 4,
            24 => 5,
            default => '',
        };
    }

    private function mapSubscriptionColor(int $subscriptionId): string
    {
        return match ($subscriptionId) {
            1 => '#004AAD',
            2 => '#2E9E3F',
            3 => '#F2A900',
            default => '#000000',
        };
    }
}
