<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Hour;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Seat;
use App\Models\Learner;
use App\Models\LearnerDetail;
use App\Models\LearnerTransactionActivity;
use App\Models\LibraryTransaction;
use App\Models\Library;
use App\Models\LibraryUser;
use App\Models\Subscription;
use Illuminate\Support\Facades\Schema;

class DashboardService
{
    public function getDashboardData(int $branchId)
    {
        $branchName = DB::table('branches')->where('id', $branchId)->value('name');
        $authUser = auth('library_api')->user();
        $userType = 'library user';
        if ($authUser && get_class($authUser) === Library::class) {
            $userType = 'library owner';
        }

        $query = LearnerTransactionActivity::query()
            ->where('branch_id', $branchId)
            ->with('learner');

        $transactions = $query->latest()->get()->map(function ($item) {
            return [
                'payment_type' => $item->payment_type ?? '',
                'dr_cr' => $item->dr_cr ?? '',
                'particular' => $item->particular ?? '',
                'payment_mode' => $item->payment_mode ?? '',
                'amount' => $item->amount ?? 0,
                'transaction_id' => $item->transaction_id ?? '',
                'created_by' => $item->created_by_name ?? 'System User',
                'learner_name' => optional($item->learner)->name ?? '',
                'seat_no' => $this->dashboardSeatNo(optional($item->learner)->seat_no),
            ];
        })->values();

        return [
            'user' => [
                'name' => $authUser->name ?? $authUser->library_name ?? '',
                'avatar' => !empty($authUser->profile_picture) ? asset($authUser->profile_picture) : '',
                'current_branch_name' => $branchName ?? '',
                'type' => $userType,
            ],
            'seat_summary' => $this->seatSummary($branchId),
            'library_occupancy' => $this->libraryOccupancy($branchId),
            'online_bookings' => $this->onlineBookings($branchId),
            'expired_members' => $this->expiredMembers($branchId),
            'due_pending' => $this->duePayment($branchId),
            'top_banner'=>$this->topBanner(),
            'last_banner'=>$this->lastBanner(),
            'unread_notification_count' => $this->unreadNotificationCount($authUser),
            'qr_marque'=>"New updates are live. You may face temporary issues, but essential services are running normally. Everything will be stable shortly—no need to worry.",
           
           
        ];
    }

    public function getDashboardRevenue(int $branchId, string $type, ?string $value = null): array
    {
        return $this->collectionSummary($branchId, $type, $value);
    }

    private function unreadNotificationCount($user): int
    {
        if (!$user) {
            return 0;
        }

        $today = Carbon::today()->toDateString();

        return DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->where('guard', 'library')
            ->where('status', 1)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->select(
                'batch_id',
                'guard',
                'data',
                DB::raw('MIN(read_at) as read_at')
            )
            ->groupBy('batch_id', 'guard', 'data')
            ->havingRaw('MIN(read_at) IS NULL')
            ->get()
            ->count();
    }

    /*
    |--------------------------------------------------------------------------
    | Collection Summary
    |--------------------------------------------------------------------------
    */

    private function collectionSummary(int $branchId, string $type, ?string $value = null): array
    {
        $baseQuery = LearnerTransactionActivity::where('branch_id', $branchId);
        $resolvedValue = $value;
        if ($type === 'date') {
            $resolvedValue = $value ?: Carbon::today()->format('Y-m-d');
            $baseQuery->whereDate('date', Carbon::parse($resolvedValue)->toDateString());
        } elseif ($type === 'monthly') {
            $resolvedValue = $value ?: Carbon::now()->format('Y-m');
            $monthDate = Carbon::parse($resolvedValue . '-01');
            $baseQuery->whereMonth('date', $monthDate->month)->whereYear('date', $monthDate->year);
        } else {
            $resolvedValue = $value ?: Carbon::now()->format('Y');
            $baseQuery->whereYear('date', (int) $resolvedValue);
        }

        $collection = (clone $baseQuery)->where('dr_cr', 'Cr')->sum('amount');

        // $collection = $query ->where(function($q) {
        //         $q->whereIn('payment_type', ['SEAT ASSIGNMENT', 'RENEW', 'REACTIVE','UPGRADE'])
        //         ->orWhere(function($sub) {
        //             $sub->where('payment_type', 'CHANGE PLAN')
        //                 ->where('dr_cr', 'Cr');
        //         });
        //     })->sum('amount');

        $today_other_amt = (clone $baseQuery)
            ->whereIn('payment_type', ['TOKEN MONEY', 'MISCELLANEOUS'])
            ->where('dr_cr', 'Cr')
            ->sum('amount');

        $todayExpense = (clone $baseQuery)->where('payment_type', 'EXPENSE')->sum('amount');
        $today_pending = (clone $baseQuery)->where('payment_type', 'PENDING')->sum('amount');
        $today_refund = (clone $baseQuery)->where(function($q) {
            $q->where('payment_type', 'REFUND')
            ->orWhere(function($sub) {
                $sub->where('payment_type', 'CHANGE PLAN')
                    ->where('dr_cr', 'Dr');
            });
        })
        ->sum('amount');

        $total_cr = (clone $baseQuery)->where('dr_cr', 'Cr')->sum('amount');
        $total_dr = (clone $baseQuery)->where('dr_cr', 'Dr')->sum('amount');
           
        $todayBalance = $total_cr-$total_dr;

        return [
            'type' => $type,
            'value' => (string) $resolvedValue,
            'collection' => (string) $collection,
            'other_income' => (string)$today_other_amt ?? 0,
            'expense' => (string)$todayExpense ?? 0,
            'refund' =>(string)$today_refund ?? 0,
            'pending_payment' => (string)$today_pending ?? 0,
            'balance' => (string) $todayBalance
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Seat Summary
    |--------------------------------------------------------------------------
    */

    private function seatSummary(int $branchId): array
    {
       
        $totalSeats =  Hour::where('branch_id', $branchId)->value('seats');

         $extend_day = getExtendDays();
        
       
        $booked_seats=LearnerDetail::whereNull('deleted_at')->distinct('seat_no')->where('status', 1)->whereNotNull('seat_no')->count('seat_no');
      
        // available slot
        if($totalSeats!=0){
            $availble_seats=$totalSeats-$booked_seats; 
        }else{
            $availble_seats=0;
        }


        return [
            'total_seats' => $totalSeats,
            'booked_seats' => $booked_seats,
            'available_seats' => $availble_seats
        ];
    }


     /*
    |--------------------------------------------------------------------------
    | Librray Occupancy
    |--------------------------------------------------------------------------
    */

    private function libraryOccupancy(int $branchId)
    {
        $today = Carbon::today()->toDateString();

        $totalStudents = Learner::where('status', 1)
            ->where('branch_id', $branchId)
            ->count();

        $presentStudents = DB::table('attendances')
            ->join('learners', 'learners.id', '=', 'attendances.learner_id')
            ->where('learners.status', 1)
            ->where('learners.branch_id', $branchId)
            ->where('attendances.branch_id', $branchId)
            ->whereDate('attendances.date', $today)
            ->where('attendances.attendance', 1)
            ->distinct('attendances.learner_id')
            ->count('attendances.learner_id');

       $absentStudents = max($totalStudents - $presentStudents, 0);

         return [
            'total_seats' => $totalStudents,
            'present' => $presentStudents,
            'absent' => $absentStudents
        ];

    }

    /*
    |--------------------------------------------------------------------------
    | Online / QR Bookings
    |--------------------------------------------------------------------------
    */

    private function onlineBookings(int $branchId): array
    {
        $bookings = Booking::where('branch_id', $branchId)
            ->with([
                'plan:id,name',
                'planType:id,name'
            ])
            ->where('type','qr_seat_book')
            ->select('id','seat_no','name','mobile','plan_id','plan_type_id','payment_screenshot','profile_picture','plan_start_date','status')
            ->latest()
            ->limit(5)
            ->get();

        $list = $bookings->map(function ($booking) {

            $isPaid = !empty($booking->payment_screenshot);

            return [
                'booking_id' => $booking->id,
                'seat_no' => $this->dashboardSeatNo($booking->seat_no),
                'name' => $booking->name,
                'mobile' => $booking->mobile,
                'plan_name' => $booking->plan?->name ?? '',
                'plan_type_name' => $booking->planType?->name ?? '',
                'plan_start_date' => $booking->plan_start_date ?? '',
                
                'payment_status' => $isPaid ? 'paid' : 'unpaid',
                'is_paid' => $isPaid,
                'status'=>$booking->status?? '',

                'payment_screenshot' => $isPaid
                    ? asset($booking->payment_screenshot)
                    : '',
                
                 'profile_picture'=>$booking->profile_picture 
                                ? asset($booking->profile_picture) 
                                : '',
                'created_at' => optional($booking->created_at)->format('d-m-Y')
            ];
        })->toArray();

        return [
            'total_count' => count($list),
            'booking' => $list
        ];
    }

    /**
     * Due payment
     **/
    // private function duePayment(int $branchId)
    // {
    //     $latestDetail = LearnerDetail::selectRaw('MAX(id) as id')
    //         ->groupBy('learner_id');

    //     $data = LearnerDetail::query()

    //         ->joinSub($latestDetail, 'latest', function ($join) {

    //             $join->on('learner_detail.id', '=', 'latest.id');
    //         })

    //         ->join('learners', 'learners.id', '=', 'learner_detail.learner_id')

    //         ->leftJoin('plans', 'plans.id', '=', 'learner_detail.plan_id')

    //         ->leftJoin('plan_types', 'plan_types.id', '=', 'learner_detail.plan_type_id')

    //         ->leftJoin('learner_transactions', function ($join) {

    //             $join->on(
    //                 'learner_transactions.learner_id',
    //                 '=',
    //                 'learners.id'
    //             )

    //             ->where('learner_transactions.pending_amount', '>', 0);
    //         })

    //         ->where('learner_detail.branch_id', $branchId)

    //         ->select(
    //             'learners.id as learner_id',
    //             'learners.profile_picture',

    //             'learners.seat_no',

    //             'learners.name',

    //             'learner_transactions.pending_amount',

    //             'learner_transactions.due_date',
    //             'learner_detail.id as learner_detail_id'
    //         )

    //         ->whereNotNull('learner_transactions.id')

    //         ->limit(7)
    //         ->get()

    //         ->map(function ($item) {

    //             return [
    //                 'learner_id'=>$item->learner_id ?? '',
    //                 'learner_detail_id'=>$item->learner_detail_id ?? '',

    //                 'profile_picture' =>
    //                     $item->profile_picture
    //                     ? asset($item->profile_picture)
    //                     : '',

    //                 'seat_no' => $item->seat_no,

    //                 'name' => $item->name,

    //                 'pending_amount' =>
    //                     $item->pending_amount,

    //                 'due_date' =>
    //                     $item->due_date
    //                     ? Carbon::parse(
    //                         $item->due_date
    //                     )->format('d M')
    //                     : null,

    //                 'due_text' =>
    //                     'Due '
    //                     . $item->pending_amount
    //                     . ' on '
    //                     . Carbon::parse(
    //                         $item->due_date
    //                     )->format('d M')
    //                     . '.',
    //                 'send_message' => 'pending_waba'
    //             ];
    //         });

    //     return [
    //         'limit' => 5,
    //         'count' => $data->count(),
    //         'list' => $data
    //     ];
    // }

      public function duePaymentList(int $branchId, ?int $limit = 5): array
    {
        $data = DB::table('learner_transactions')
            ->join('learners', 'learners.id', '=', 'learner_transactions.learner_id')
            ->where('learner_transactions.branch_id', $branchId)
            ->where('learner_transactions.pending_amount', '>', 0)
            ->when(Schema::hasColumn('learner_transactions', 'deleted_at'), function ($query) {
                $query->whereNull('learner_transactions.deleted_at');
            })
            ->select(
                'learners.id as learner_id',
                'learners.profile_picture',
                'learners.seat_no',
                'learners.name',
                DB::raw('MIN(learner_transactions.learner_detail_id) as learner_detail_id'),
                DB::raw('SUM(learner_transactions.pending_amount) as pending_amount'),
                DB::raw('MIN(learner_transactions.due_date) as due_date')
            )
            ->groupBy(
                'learners.id',
                'learners.profile_picture',
                'learners.seat_no',
                'learners.name'
            )
            ->orderByRaw('MIN(learner_transactions.due_date) IS NULL')
            ->orderByRaw('MIN(learner_transactions.due_date) ASC');

        if ($limit !== null) {
            $data->limit($limit);
        }

        $data = $data->get()

            ->map(function ($item) {
                $pendingAmount = (float) $item->pending_amount;
                $dueDate = $item->due_date ? Carbon::parse($item->due_date) : null;

                return [
                    'learner_id'=>$item->learner_id ?? '',
                    'learner_detail_id'=>$item->learner_detail_id ?? '',

                    'profile_picture' =>
                        $item->profile_picture
                        ? asset($item->profile_picture)
                        : '',

                    'seat_no' => $this->dashboardSeatNo($item->seat_no),

                    'name' => $item->name,

                    'pending_amount' =>(string)$pendingAmount,

                    'due_date' =>
                        $dueDate
                        ? $dueDate->format('d M')
                        : null,

                    'due_text' =>
                        $dueDate
                        ? 'Due ' . $pendingAmount . ' on ' . $dueDate->format('d M') . '.'
                        : 'Due ' . $pendingAmount . '.',
                    'send_message' => 'pending_waba'
                ];
            });

        return [
            'limit' => $limit,
            'count' => $data->count(),
            'list' => $data
        ];
    }

    private function duePayment(int $branchId)
    {
        return $this->duePaymentList($branchId, 5);
    }

    /*
    |--------------------------------------------------------------------------
    | Expired Members- in extension period and in about to expire period
    |--------------------------------------------------------------------------
    */

    private function expiredMembers(int $branchId): array
    {
        $today = Carbon::today();
        $extendDay = getExtendDays($branchId); // extension window
        $aboutToExpireDays = 5;

        $learners = Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
            ->where('learners.branch_id', $branchId)
            ->where('learners.status', 1)
            ->where('learner_detail.status', 1)
            ->where('learner_detail.is_paid', 1)

            ->where(function ($query) use ($today, $extendDay, $aboutToExpireDays) {

                // 🔴 Extension: expired but inside extension window
                $query->where(function ($q) use ($today, $extendDay) {
                    $q->whereDate('learner_detail.plan_end_date', '<', $today)
                    ->whereRaw(
                        "DATE_ADD(learner_detail.plan_end_date, INTERVAL ? DAY) >= ?",
                        [$extendDay, $today]
                    );
                })

                // 🟡 About to expire: today to next X days
                ->orWhere(function ($q) use ($today, $aboutToExpireDays) {
                    $q->whereDate('learner_detail.plan_end_date', '>=', $today)
                    ->whereDate(
                        'learner_detail.plan_end_date',
                        '<=',
                        $today->copy()->addDays($aboutToExpireDays)
                    );
                });
            })

            // Only latest plan record per learner
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('learner_detail as ld')
                    ->whereColumn('ld.learner_id', 'learner_detail.learner_id')
                    ->whereColumn('ld.plan_end_date', '>', 'learner_detail.plan_end_date');
            })

            ->orderBy('learner_detail.plan_end_date', 'asc')
            ->limit(5)

            ->get([
                'learners.id',
                'learners.name',
                'learner_detail.seat_no',
                'learner_detail.plan_end_date','learners.profile_picture'
            ]);

        $list = $learners->map(function ($learner) use ($today, $extendDay) {

            $planEndDate = Carbon::parse($learner->plan_end_date);
            $extensionEndDate = $planEndDate->copy()->addDays($extendDay);

            // 🟡 About to expire (future date)
            if ($planEndDate->gte($today)) {

                $daysLeft = $today->diffInDays($planEndDate);

                return [
                    'learner_id' => $learner->id,
                    'profile_picture'=>$learner->profile_picture 
                                ? asset($learner->profile_picture) 
                                : '',
                    'seat_no' => $this->dashboardSeatNo($learner->seat_no),
                    'name' => $learner->name,
                    'plan_end_date' => $learner->plan_end_date,
                    'days_remaining' => $daysLeft,
                    'status' => $daysLeft === 0 ? 'expires_today' : 'about_to_expire',
                    'label' => $daysLeft === 0
                        ? 'Expires today'
                        : "Expires in {$daysLeft} days"
                ];
            }

            // 🔴 Extension active
            if ($planEndDate->lt($today) && $extensionEndDate->gte($today)) {

                $daysLeft = $today->diffInDays($extensionEndDate);

                return [
                    'learner_id' => $learner->id,
                    'profile_picture'=>$learner->profile_picture 
                                ? asset($learner->profile_picture) 
                                : '',
                    'seat_no' => $this->dashboardSeatNo($learner->seat_no),
                    'name' => $learner->name,
                    'plan_end_date' => $learner->plan_end_date,
                    'days_remaining' => $daysLeft,
                    'status' => $daysLeft === 0 ? 'extension_last_day' : 'extension',
                    'label' => $daysLeft === 0
                        ? 'Extension expires today'
                        : "Extension active! {$daysLeft} days left"
                ];
            }

            // ⚫ Fully expired (should normally not appear due to SQL filter)
            $expiredDays = $planEndDate->diffInDays($today);

            return [
                 'learner_id' => $learner->id,
                 'profile_picture'=>$learner->profile_picture 
                                ? asset($learner->profile_picture) 
                                : '',
                'seat_no' => $this->dashboardSeatNo($learner->seat_no),
                'name' => $learner->name,
                'plan_end_date' => $learner->plan_end_date,
                'days_remaining' => $expiredDays,
                'status' => 'expired',
                'label' => "Expired {$expiredDays} days ago"
            ];

        })->take(5)->values()->toArray();

        $list = collect($list)
            ->whereIn('status', ['about_to_expire', 'expires_today', 'extension', 'extension_last_day'])
            ->take(7)
            ->values()
            ->toArray();

        return [
            'limit' => 5,
            'count' => count($list),
            'list' => $list
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | TODAY FINANCIAL
    |--------------------------------------------------------------------------
    */

    public function todayFinancialData($request)
    {
        $baseQuery = LearnerTransactionActivity::query()
            ->where('branch_id', getCurrentBranch());

        if ($request->filled('from_date') && $request->filled('to_date')) {
            $baseQuery->whereBetween('date', [$request->from_date, $request->to_date]);
        } elseif ($request->filled('date')) {
            $baseQuery->whereDate('date', $request->date);
        } else {
            $baseQuery->whereDate('date', now()->toDateString());
        }

        /*
        |--------------------------------------------------------------------------
        | TODAY SUMMARY
        |--------------------------------------------------------------------------
        */

        $today_booking_amt = (clone $baseQuery)
            ->whereNotIn('payment_type', [
                    'TOKEN MONEY',
                    'MISCELLANEOUS',
                    'PENDING',
                ])
             ->where('dr_cr', 'Cr')
            ->sum('amount');

        $today_other_amt = (clone $baseQuery)
            ->whereIn('payment_type', [
                'TOKEN MONEY',
                'MISCELLANEOUS'
            ])
            ->where('dr_cr', 'Cr')
            ->sum('amount');

        $today_expense = (clone $baseQuery)
            ->where('payment_type', 'EXPENSE')
            ->sum('amount');

        $today_pending = (clone $baseQuery)
            ->where('payment_type', 'PENDING')
            ->sum('amount');

        $today_refund = (clone $baseQuery)
            ->where(function ($q) {

                $q->where('payment_type', 'REFUND')

                ->orWhere(function ($sub) {

                    $sub->where('payment_type', 'CHANGE PLAN')
                        ->where('dr_cr', 'Dr');
                });
            })
            ->sum('amount');

        $total_cr = (clone $baseQuery)
            ->where('dr_cr', 'Cr')
            ->sum('amount');

        $total_dr = (clone $baseQuery)
            ->where('dr_cr', 'Dr')
            ->sum('amount');

        $total_revenue = $total_cr - $total_dr;

        /*
        |--------------------------------------------------------------------------
        | FILTER CLICK LOGIC
        |--------------------------------------------------------------------------
        */

        $query = (clone $baseQuery)->with('learner');

        if ($request->filled('payment_type')) {
            $paymentTypes = $this->normalizeFinancialPaymentTypes($request->payment_type);

            if (!empty($paymentTypes)) {
                $query->whereIn('payment_type', $paymentTypes);
            }
        }

        switch ($request->type) {

            case 'today_collection':

                $query->whereNotIn('payment_type', [
                    'TOKEN MONEY',
                    'MISCELLANEOUS',
                    'PENDING',
                ])
             ->where('dr_cr', 'Cr');
            break;

            case 'today_other_collection':

                $query->whereIn('payment_type', [
                    'TOKEN MONEY',
                    'MISCELLANEOUS'
                ])
                ->where('dr_cr', 'Cr');

            break;

            case 'today_expense':

                $query->where('payment_type', 'EXPENSE');

            break;

            case 'today_refund':

                $query->where(function ($q) {

                    $q->where('payment_type', 'REFUND')

                    ->orWhere(function ($sub) {

                        $sub->where('payment_type', 'CHANGE PLAN')
                            ->where('dr_cr', 'Dr');
                    });
                });

            break;

            case 'today_pending':

                $query->where('payment_type', 'PENDING');

            break;

            case 'today_balance':
                break;
        }

        $transactions = $query->latest()->get()->map(function ($item) {
            return [
                'payment_type' => $item->payment_type ?? '',
                'dr_cr' => $item->dr_cr ?? '',
                'date' => $item->date ?? '',
                'particular' => $item->particular ?? '',
                'payment_mode' => $item->payment_mode ?? '',
                'amount' => (string) $item->amount ?? 0,
                'transaction_id' => $item->transaction_id ?? '',
                'created_by' => $item->created_by_name ?? 'System User',
                'learner_name' => optional($item->learner)->name ?? '',
                'seat_no' => $this->dashboardSeatNo(optional($item->learner)->seat_no),
            ];
        })->values();

        return [

            'today_booking_amt' => (string)$today_booking_amt,

            'today_other_amt' =>  (string)$today_other_amt,

            'today_expense' => (string) $today_expense,

            'today_pending' => (string) $today_pending,

            'today_refund' => (string) $today_refund,

            'total_revenue' => (string) $total_revenue,

            'collection' => $transactions
        ];
    }

        /*
    |--------------------------------------------------------------------------
    | MONTHLY FINANCIAL
    |--------------------------------------------------------------------------
    */

    public function monthlyFinancialData($request)
    {
        $year = $request->year ?? date('Y');
        $month = $request->month ?? date('m');
        $groupByMonth = $request->input('group_by') === 'month';

        $baseQuery = LearnerTransactionActivity::query()
            ->where('branch_id', getCurrentBranch());

        if ($request->filled('from_date') && $request->filled('to_date')) {
            $baseQuery->whereBetween('date', [$request->from_date, $request->to_date]);
        } else {
            $baseQuery->whereYear('date', $year)
                ->whereMonth('date', $month);
        }

        $query = (clone $baseQuery)->with('learner');

        /*
        |--------------------------------------------------------------------------
        | MONTHLY SUMMARY
        |--------------------------------------------------------------------------
        */

        $monthlyIncome = (clone $baseQuery)->where(function ($q) {

                $q->whereNotIn('payment_type', [
                    'TOKEN MONEY',
                    'MISCELLANEOUS',
                    'PENDING',
                ]);
            })
             ->where('dr_cr', 'Cr')
             ->sum('amount');

        $other_total_income = (clone $baseQuery)->whereIn('payment_type', [
                'TOKEN MONEY',
                'MISCELLANEOUS'
            ])
            ->where('dr_cr', 'Cr')
            ->sum('amount');

        $monthlyExpense = (clone $baseQuery)
            ->where('payment_type', 'EXPENSE')
            ->sum('amount');

        $monthly_refund = (clone $baseQuery)->where(function ($q) {

                $q->where('payment_type', 'REFUND')

                ->orWhere(function ($sub) {

                    $sub->where('payment_type', 'CHANGE PLAN')
                        ->where('dr_cr', 'Dr');
                });
            })->sum('amount');

        $monthly_pending = (clone $baseQuery)
            ->where('payment_type', 'PENDING')
            ->sum('amount');

        $totals_monthly = (clone $baseQuery)->selectRaw("
                SUM(CASE WHEN dr_cr = 'Cr' THEN amount ELSE 0 END) as total_cr,
                SUM(CASE WHEN dr_cr = 'Dr' THEN amount ELSE 0 END) as total_dr
            ")
            ->first();

        /*
        |--------------------------------------------------------------------------
        | MONTHLY BALANCE LIST
        |--------------------------------------------------------------------------
        */

        $dateBucket = $groupByMonth
            ? "DATE_FORMAT(date, '%Y-%m-01')"
            : 'DATE(date)';

        $monthlyBalance = (clone $baseQuery)->selectRaw("
                {$dateBucket} as tx_date,

                SUM(
                    CASE
                        WHEN payment_type NOT IN
                        ('TOKEN MONEY','MISCELLANEOUS','PENDING')
                        AND dr_cr = 'Cr'
                        THEN amount

                        ELSE 0
                    END
                ) as collection,

                SUM(
                    CASE
                        WHEN payment_type IN
                        ('TOKEN MONEY','MISCELLANEOUS')
                        AND dr_cr='Cr'
                        THEN amount

                        ELSE 0
                    END
                ) as other_collection,

                SUM(
                    CASE
                        WHEN payment_type = 'EXPENSE'
                        THEN amount

                        ELSE 0
                    END
                ) as expense,

                SUM(
                    CASE
                        WHEN payment_type = 'REFUND'
                        THEN amount

                        WHEN payment_type='CHANGE PLAN'
                        AND dr_cr='Dr'
                        THEN amount

                        ELSE 0
                    END
                ) as refund,

                SUM(
                    CASE
                        WHEN payment_type = 'PENDING'
                        THEN amount

                        ELSE 0
                    END
                ) as pending
            ")

            ->groupBy('tx_date')

            ->orderBy('tx_date')

            ->get();

        $finalData = [];

        $runningBalance = 0;

        if ($groupByMonth) {
            $monthlyBalance = $monthlyBalance->keyBy('tx_date');
            $startMonth = Carbon::createFromDate((int) $year, 1, 1)->startOfMonth();

            for ($index = 0; $index < 12; $index++) {
                $date = $startMonth->copy()->addMonths($index)->toDateString();
                $row = $monthlyBalance->get($date);

                $collection = (float) ($row->collection ?? 0);
                $otherCollection = (float) ($row->other_collection ?? 0);
                $expense = (float) ($row->expense ?? 0);
                $refund = (float) ($row->refund ?? 0);
                $pending = (float) ($row->pending ?? 0);
                $net = ($collection + $otherCollection) - ($expense + $refund) + $pending;

                $runningBalance += $net;

                $finalData[] = [

                    'date' => $date,

                    'collection' => (string)$collection,

                    'other_collection' => (string)$otherCollection,

                    'expense' => (string)$expense,

                    'refund' => (string)$refund,

                    'pending' => (string)$pending,

                    'net' => (string)$net,

                    'final_balance' => (string)$runningBalance
                ];
            }
        } else {
            foreach ($monthlyBalance as $row) {

                $net =
                    ($row->collection + $row->other_collection)
                    -
                    ($row->expense + $row->refund)
                    +
                    $row->pending;

                $runningBalance += $net;

                $finalData[] = [

                    'date' => $row->tx_date,

                    'collection' =>(string)$row->collection,

                    'other_collection' => (string)$row->other_collection,

                    'expense' => (string)$row->expense,

                    'refund' =>(string) $row->refund,

                    'pending' =>(string) $row->pending,

                    'net' => (string)$net,

                    'final_balance' => (string)$runningBalance
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER CLICK LOGIC
        |--------------------------------------------------------------------------
        */

        if ($request->filled('payment_type')) {
            $paymentTypes = $this->normalizeFinancialPaymentTypes($request->payment_type);

            if (!empty($paymentTypes)) {
                $query->whereIn('payment_type', $paymentTypes);
            }
        }

        switch ($request->type) {

            case 'monthly_collection':

                $query->whereNotIn('payment_type', [
                    'TOKEN MONEY',
                    'MISCELLANEOUS',
                    'PENDING',
                ])
                ->where('dr_cr', 'Cr');

            break;

            case 'monthly_other_collection':

                $query->whereIn('payment_type', [
                    'TOKEN MONEY',
                    'MISCELLANEOUS'
                ])
                ->where('dr_cr', 'Cr');

            break;

            case 'monthly_expense':

                $query->where('payment_type', 'EXPENSE');

            break;

            case 'monthly_refund':

                $query->where(function ($q) {

                    $q->where('payment_type', 'REFUND')

                    ->orWhere(function ($sub) {

                        $sub->where('payment_type', 'CHANGE PLAN')
                            ->where('dr_cr', 'Dr');
                    });
                });

            break;

            case 'monthly_pending':

                $query->where('payment_type', 'PENDING');

            break;
            case 'monthly_balance':
            break;
        }
        
        $transactions = $query->latest()->get()->map(function ($item) {
            return [
                'payment_type' => $item->payment_type ?? '',
                'dr_cr' => $item->dr_cr ?? '',
                'particular' => $item->particular ?? '',
                'payment_mode' => $item->payment_mode ?? '',
                'amount' => $item->amount ?? 0,
                'transaction_id' => $item->transaction_id ?? '',
                'created_by' => $item->created_by_name ?? 'System User',
                'learner_name' => optional($item->learner)->name ?? '',
                'seat_no' => $this->dashboardSeatNo(optional($item->learner)->seat_no),
            ];
        })->values();

        return [

            'monthly_income' => $monthlyIncome,

            'other_total_income' => $other_total_income,

            'monthly_expense' => $monthlyExpense,

            'monthly_refund' => $monthly_refund,

            'monthly_pending' => $monthly_pending,

            'monthlyBalance' =>
                $totals_monthly->total_cr
                -
                $totals_monthly->total_dr,

            'monthly_balance' => $finalData,

            'collection' => $transactions
        ];
    }

    public function dashboardFinancialData($request): array
    {
        $type = (string) $request->input('type');
        $listFor = (string) $request->input('list_for');
        $useMonthlyEngine = $type !== 'daily' || $listFor === 'balance';
        $filterPrefix = $useMonthlyEngine ? 'monthly' : 'today';

        $financialType = match ($listFor) {
            'collection' => $filterPrefix . '_collection',
            'other_collection' => $filterPrefix . '_other_collection',
            'expense' => $filterPrefix . '_expense',
            'refund' => $filterPrefix . '_refund',
            'pending' => $filterPrefix . '_pending',
            'balance' => 'monthly_balance',
            default => null,
        };

        $payload = [
            'type' => $financialType,
            'payment_type' => $request->input('payment_type'),
            'date' => $request->input('date'),
            'month' => $request->input('month'),
            'year' => $request->input('year'),
            'from_date' => $request->input('from_date'),
            'to_date' => $request->input('to_date'),
        ];

        if ($type === 'daily' && $useMonthlyEngine) {
            $date = $request->input('date') ?: now()->toDateString();
            $payload['from_date'] = $date;
            $payload['to_date'] = $date;
        } elseif ($type === 'daily') {
            $payload['from_date'] = null;
            $payload['to_date'] = null;
        } elseif ($type === 'yearly') {
            $payload['from_date'] = $request->year . '-01-01';
            $payload['to_date'] = $request->year . '-12-31';
            $payload['group_by'] = 'month';
        } elseif ($type === 'monthly') {
            $payload['from_date'] = null;
            $payload['to_date'] = null;
        }

        $innerRequest = new \Illuminate\Http\Request($payload);

        if ($listFor === 'balance') {
            $data = $this->monthlyFinancialData($innerRequest);
            $paginatedList = $this->paginateArrayItems(
                $data['monthly_balance'],
                (int) $request->input('page', 1),
                (int) $request->input('per_page', 20)
            );
            $paginatedTransactions = $this->paginateArrayItems(
                $data['collection'],
                (int) $request->input('page', 1),
                (int) $request->input('per_page', 20)
            );

            return [
                'summary' => [
                    'booking_income' => (string)$data['monthly_income'],
                    'other_income' =>(string) $data['other_total_income'],
                    'expense' => (string)$data['monthly_expense'],
                    'refund' => (string)$data['monthly_refund'],
                    'pending' =>(string) $data['monthly_pending'],
                    'total_revenue' =>(string) $data['monthlyBalance'],
                ],
                'list' => $paginatedList['items'],
                'transactions' => $paginatedTransactions['items'],
                'pagination' => [
                    'list' => $paginatedList['pagination'],
                    'transactions' => $paginatedTransactions['pagination'],
                ],
            ];
        }

        $data = $useMonthlyEngine
            ? $this->monthlyFinancialData($innerRequest)
            : $this->todayFinancialData($innerRequest);

        $summaryKeys = $useMonthlyEngine
            ? [
                'booking_income' => 'monthly_income',
                'other_income' => 'other_total_income',
                'expense' => 'monthly_expense',
                'refund' => 'monthly_refund',
                'pending' => 'monthly_pending',
                'total_revenue' => 'monthlyBalance',
            ]
            : [
                'booking_income' => 'today_booking_amt',
                'other_income' => 'today_other_amt',
                'expense' => 'today_expense',
                'refund' => 'today_refund',
                'pending' => 'today_pending',
                'total_revenue' => 'total_revenue',
            ];

        $paginatedList = $this->paginateArrayItems(
            [],
            (int) $request->input('page', 1),
            (int) $request->input('per_page', 20)
        );
        $paginatedTransactions = $this->paginateArrayItems(
            $data['collection'],
            (int) $request->input('page', 1),
            (int) $request->input('per_page', 20)
        );

        $response = [
            'summary' => [
                'booking_income' => (string) $data[$summaryKeys['booking_income']],
                'other_income' => (string) $data[$summaryKeys['other_income']],
                'expense' => (string) $data[$summaryKeys['expense']],
                'refund' => (string) $data[$summaryKeys['refund']],
                'pending' => (string) $data[$summaryKeys['pending']],
                'total_revenue' => (string) $data[$summaryKeys['total_revenue']],
            ],
            'transactions' => $paginatedTransactions['items'],
            'pagination' => [
                'list' => $paginatedList['pagination'],
                'transactions' => $paginatedTransactions['pagination'],
            ],
        ];

        if ($paginatedList['items']->isNotEmpty()) {
            $response['list'] = $paginatedList['items'];
        }

        return $response;
    }

    private function normalizeFinancialPaymentTypes($paymentType): array
    {
        $paymentTypes = is_array($paymentType) ? $paymentType : [$paymentType];
        $paymentTypes = array_values(array_filter(array_map(function ($type) {
            return strtoupper(trim((string) $type));
        }, $paymentTypes)));

        if (in_array('SEAT ASSIGNMENT', $paymentTypes, true)) {
            $paymentTypes[] = 'NON-EXPIRED';
        }

        return array_values(array_unique($paymentTypes));
    }

    private function paginateArrayItems($items, int $page = 1, int $perPage = 20): array
    {
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $collection = collect($items)->values();
        $total = $collection->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min($page, $lastPage);
        $offset = ($page - 1) * $perPage;

        return [
            'items' => $collection->slice($offset, $perPage)->values(),
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => $lastPage,
                'has_more' => $page < $lastPage,
            ],
        ];
    }

    private function dashboardSeatNo($seatNo): string
    {
        $seatNo = trim((string) $seatNo);

        return !empty($seatNo) ? (string) getSeatDisplayShortFloorName($seatNo) : 'GEN';
    }

    private function topBanner(): array
    {
        $today = Carbon::today();
        $branchId = getCurrentBranch();
        $libraryId = (int) getLibraryId();
        $festival = DB::table('india_festivals')
            ->whereDate('festival_date', $today->toDateString())
            ->select('festival_name', 'description')
            ->first();

        $banners = [[
            'type' => $festival ? 'other_wishes' : '',
            'tital' => $festival ? ('Wish you happy ' . $festival->festival_name) : '',
            'description' => $festival->description ?? '',
            'birthday_user' => '',
            'seat_no' => '',
            'subscription_type' => '',
            'subscription_status' => '',
            'days_in_left' => '',
        ]];

        $learners = Learner::query()
            ->where('branch_id', $branchId)
            ->whereMonth('dob', $today->month)
            ->whereDay('dob', $today->day)
            ->select('name', 'seat_no')
            ->get();

        foreach ($learners as $learner) {
            $banners[] = [
                'type' => 'birthday_wishes',
                'tital' => 'Wish you happy birthay',
                'description' => '',
                'birthday_user' => (string) ($learner->name ?? ''),
                'seat_no' => $this->dashboardSeatNo($learner->seat_no),
                'subscription_type' => '',
                'subscription_status' => '',
                'days_in_left' => '',
            ];
        }

        if (Schema::hasColumn('libraries', 'dob')) {
            $owner = Library::query()
                ->where('id', $libraryId)
                ->whereMonth('dob', $today->month)
                ->whereDay('dob', $today->day)
                ->select('name', 'library_name')
                ->first();

            if ($owner) {
                $banners[] = [
                    'type' => 'birthday_wishes',
                    'tital' => 'Wish you happy birthay',
                    'description' => '',
                    'birthday_user' => (string) ($owner->name ?? $owner->library_name ?? ''),
                    'seat_no' => '',
                    'subscription_type' => '',
                    'subscription_status' => '',
                    'days_in_left' => '',
                ];
            }
        }

        if (Schema::hasColumn('library_users', 'dob')) {
            $users = LibraryUser::query()
                ->where('library_id', $libraryId)
                ->whereMonth('dob', $today->month)
                ->whereDay('dob', $today->day)
                ->select('name')
                ->get();

            foreach ($users as $user) {
                $banners[] = [
                    'type' => 'birthday_wishes',
                    'tital' => 'Wish you happy birthay',
                    'description' => '',
                    'birthday_user' => (string) ($user->name ?? ''),
                    'seat_no' => '',
                    'subscription_type' => '',
                    'subscription_status' => '',
                    'days_in_left' => '',
                ];
            }
        }

        $library = Library::query()->select('library_type')->find($libraryId);
        $subscriptionName = '';
        if ($library && !empty($library->library_type)) {
            $subscriptionName = (string) (Subscription::where('id', $library->library_type)->value('name') ?? '');
        }

        $latestPlan = LibraryTransaction::query()
            ->where('library_id', $libraryId)
            ->latest('id')
            ->first(['status', 'end_date']);

        $subscriptionStatus = ($latestPlan && (int) $latestPlan->status === 1) ? 'Active' : 'Inactive';
        $daysLeft = '';
        if ($latestPlan && !empty($latestPlan->end_date)) {
            $daysLeft = (string) max(0, Carbon::today()->diffInDays(Carbon::parse($latestPlan->end_date), false));
        }

        $banners[] = [
            'type' => 'subscription',
            'tital' => 'subscription',
            'description' => '',
            'birthday_user' => '',
            'seat_no' => '',
            'subscription_type' => $subscriptionName,
            'subscription_status' => $subscriptionStatus,
            'days_in_left' => $daysLeft,
        ];

        return collect($banners)
            ->filter(fn ($banner) => !empty($banner['tital']))
            ->values()
            ->all();
    }

    private function lastBanner(): array
    {
        return [
            [
                'tital' => 'Libraro Track Everithing',
                'description' => '',
                'image' => asset('public/img/slider/last_banner_1.jpeg'),
                'link' => '',
            ],
            [
                'tital' => 'Libraro Track Everithing',
                'description' => '',
                'image' => asset('public/img/slider/last_banner_2.jpeg'),
                'link' => '',
            ],
            [
                'tital' => 'Libraro Track Everithing',
                'description' => '',
                'image' => asset('public/img/slider/last_banner_3.jpeg'),
                'link' => '',
            ],
        ];
    }

        
    }
