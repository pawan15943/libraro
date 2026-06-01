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
                'seat_no' => optional($item->learner)->seat_no ?? '',
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
           
           
        ];
    }

    public function getDashboardRevenue(int $branchId, string $type, ?string $value = null): array
    {
        return $this->collectionSummary($branchId, $type, $value);
    }

    /*
    |--------------------------------------------------------------------------
    | Collection Summary
    |--------------------------------------------------------------------------
    */

    private function collectionSummary(int $branchId, string $type, ?string $value = null): array
    {
        $query = LearnerTransactionActivity::where('branch_id', $branchId);
        $resolvedValue = $value;
        if ($type === 'date') {
            $resolvedValue = $value ?: Carbon::today()->format('Y-m-d');
            $query->whereDate('date', Carbon::parse($resolvedValue)->toDateString());
        } elseif ($type === 'monthly') {
            $resolvedValue = $value ?: Carbon::now()->format('Y-m');
            $monthDate = Carbon::parse($resolvedValue . '-01');
            $query->whereMonth('date', $monthDate->month)->whereYear('date', $monthDate->year);
        } else {
            $resolvedValue = $value ?: Carbon::now()->format('Y');
            $query->whereYear('date', (int) $resolvedValue);
        }

        $collection = $query ->where(function($q) {
                $q->whereIn('payment_type', ['SEAT ASSIGNMENT', 'RENEW', 'REACTIVE','UPGRADE'])
                ->orWhere(function($sub) {
                    $sub->where('payment_type', 'CHANGE PLAN')
                        ->where('dr_cr', 'Cr');
                });
            })->sum('amount');

        $today_other_amt=$query->whereIn('payment_type',['TOKEN MONEY','MISCELLANEOUS'])->where('dr_cr','Cr')->sum('amount');

        $todayExpense =$query->where('payment_type','EXPENSE')->sum('amount');
        $today_pending=$query->where('payment_type','PENDING')->sum('amount');
        $today_refund = $query->where(function($q) {
            $q->where('payment_type', 'REFUND')
            ->orWhere(function($sub) {
                $sub->where('payment_type', 'CHANGE PLAN')
                    ->where('dr_cr', 'Dr');
            });
        })
        ->sum('amount');

        $total_cr=$query->where('dr_cr','Cr')->sum('amount');
        $total_dr=$query->where('dr_cr','Dr')->sum('amount');
           
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
        $attendance = Learner::leftJoin('attendances', function ($join) use ($branchId) {

            $join->on('learners.id', '=', 'attendances.learner_id')
                ->where('attendances.branch_id', $branchId)
                ->whereDate('attendances.date', today());
        })

        ->leftJoin('learner_detail', function ($join) {

            $join->on('learners.id', '=', 'learner_detail.learner_id')
                ->where('learner_detail.id', function ($query) {

                    $query->select('id')
                        ->from('learner_detail as ld')
                        ->whereColumn('ld.learner_id', 'learner_detail.learner_id')
                        ->where('ld.status', 1)
                        ->latest()
                        ->limit(1);
                });
        })

        ->leftJoin('plan_types', 'learner_detail.plan_type_id', '=', 'plan_types.id')

        ->where('learners.status', 1)

        ->where('learners.branch_id', $branchId)

        ->select(
            'learners.id as learner_id',
            'learners.name',
            'learners.mobile',
            'learners.seat_no',
            'plan_types.name as plan_type_name',
            'learner_detail.plan_end_date',
            'attendances.in_time',
            'attendances.out_time',
            'attendances.attendance',
            'attendances.date'
        )

        ->get();

        $totalStudents   = $attendance->count();

        $presentStudents = $attendance
                        ->where('attendance', 1)
                        ->where('attendances.date', date('Y-m-d'))
                        ->count();

       $absentStudents = $totalStudents - $presentStudents;

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
            ->select('id','seat_no','name','mobile','plan_id','plan_type_id','payment_screenshot','profile_picture','plan_start_date','status')
            ->latest()
            ->limit(5)
            ->get();

        $list = $bookings->map(function ($booking) {

            $isPaid = !empty($booking->payment_screenshot);

            return [
                'booking_id' => $booking->id,
                'seat_no' => (string) ($booking->seat_no ?? '0'),
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
    private function duePayment(int $branchId)
    {
        $latestDetail = LearnerDetail::selectRaw('MAX(id) as id')
            ->groupBy('learner_id');

        $data = LearnerDetail::query()

            ->joinSub($latestDetail, 'latest', function ($join) {

                $join->on('learner_detail.id', '=', 'latest.id');
            })

            ->join('learners', 'learners.id', '=', 'learner_detail.learner_id')

            ->leftJoin('plans', 'plans.id', '=', 'learner_detail.plan_id')

            ->leftJoin('plan_types', 'plan_types.id', '=', 'learner_detail.plan_type_id')

            ->leftJoin('learner_transactions', function ($join) {

                $join->on(
                    'learner_transactions.learner_id',
                    '=',
                    'learners.id'
                )

                ->where('learner_transactions.pending_amount', '>', 0);
            })

            ->where('learner_detail.branch_id', $branchId)

            ->select(
                'learners.id as learner_id',
                'learners.profile_picture',

                'learners.seat_no',

                'learners.name',

                'learner_transactions.pending_amount',

                'learner_transactions.due_date',
                'learner_detail.id as learner_detail_id'
            )

            ->whereNotNull('learner_transactions.id')

            ->limit(7)
            ->get()

            ->map(function ($item) {

                return [
                    'learner_id'=>$item->learner_id ?? '',
                    'learner_detail_id'=>$item->learner_detail_id ?? '',

                    'profile_picture' =>
                        $item->profile_picture
                        ? asset($item->profile_picture)
                        : '',

                    'seat_no' => $item->seat_no,

                    'name' => $item->name,

                    'pending_amount' =>
                        $item->pending_amount,

                    'due_date' =>
                        $item->due_date
                        ? Carbon::parse(
                            $item->due_date
                        )->format('d M')
                        : null,

                    'due_text' =>
                        'Due '
                        . $item->pending_amount
                        . ' on '
                        . Carbon::parse(
                            $item->due_date
                        )->format('d M')
                        . '.',
                    'send_message' => 'pending_waba'
                ];
            });

    return [
        'limit' => 5,
        'count' => $data->count(),
        'list' => $data
    ];
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
                    'seat_no' => $learner->seat_no,
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
                    'seat_no' => $learner->seat_no,
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
                'seat_no' => $learner->seat_no,
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
            ->where(function ($q) {

                $q->whereIn('payment_type', [
                    'SEAT ASSIGNMENT',
                    'RENEW',
                    'REACTIVE',
                    'UPGRADE'
                ])

                ->orWhere(function ($sub) {

                    $sub->where('payment_type', 'CHANGE PLAN')
                        ->where('dr_cr', 'Cr');
                });
            })
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
            $paymentTypes = is_array($request->payment_type)
                ? $request->payment_type
                : [$request->payment_type];

            $query->whereIn('payment_type', $paymentTypes);
        }

        switch ($request->type) {

            case 'today_collection':

                $query->where(function ($q) {

                    $q->whereIn('payment_type', [
                        'SEAT ASSIGNMENT',
                        'RENEW',
                        'REACTIVE',
                        'UPGRADE'
                    ])

                    ->orWhere(function ($sub) {

                        $sub->where('payment_type', 'CHANGE PLAN')
                            ->where('dr_cr', 'Cr');
                    });
                });

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
                'amount' => $item->amount ?? 0,
                'transaction_id' => $item->transaction_id ?? '',
                'created_by' => $item->created_by_name ?? 'System User',
                'learner_name' => optional($item->learner)->name ?? '',
                'seat_no' => optional($item->learner)->seat_no ?? '',
            ];
        })->values();

        return [

            'today_booking_amt' => $today_booking_amt,

            'today_other_amt' => $today_other_amt,

            'today_expense' => $today_expense,

            'today_pending' => $today_pending,

            'today_refund' => $today_refund,

            'total_revenue' => $total_revenue,

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

                $q->whereIn('payment_type', [
                    'SEAT ASSIGNMENT',
                    'RENEW',
                    'REACTIVE',
                    'UPGRADE'
                ])

                ->orWhere(function ($sub) {

                    $sub->where('payment_type', 'CHANGE PLAN')
                        ->where('dr_cr', 'Cr');
                });
            })->sum('amount');

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

        $monthlyBalance = (clone $baseQuery)->selectRaw("
                DATE(date) as tx_date,

                SUM(
                    CASE
                        WHEN payment_type IN
                        ('SEAT ASSIGNMENT','RENEW','REACTIVE','UPGRADE')
                        THEN amount

                        WHEN payment_type = 'CHANGE PLAN'
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

        /*
        |--------------------------------------------------------------------------
        | FILTER CLICK LOGIC
        |--------------------------------------------------------------------------
        */

        if ($request->filled('payment_type')) {
            $paymentTypes = is_array($request->payment_type)
                ? $request->payment_type
                : [$request->payment_type];

            $query->whereIn('payment_type', $paymentTypes);
        }

        switch ($request->type) {

            case 'monthly_collection':

                $query->where(function ($q) {

                    $q->whereIn('payment_type', [
                        'SEAT ASSIGNMENT',
                        'RENEW',
                        'REACTIVE',
                        'UPGRADE'
                    ])

                    ->orWhere(function ($sub) {

                        $sub->where('payment_type', 'CHANGE PLAN')
                            ->where('dr_cr', 'Cr');
                    });
                });

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
                'seat_no' => optional($item->learner)->seat_no ?? '',
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

        $mappedType = match ($type) {
            'daily' => 'today',
            'monthly' => 'monthly',
            'yearly' => 'yearly',
            'custom' => 'custom',
            default => 'today',
        };

        $financialType = match ($listFor) {
            'collection' => $listFor === 'balance' ? '' : $mappedType . '_collection',
            'other_collection' => $mappedType . '_other_collection',
            'expense' => $mappedType . '_expense',
            'refund' => $mappedType . '_refund',
            'pending' => $mappedType . '_pending',
            'balance' => $mappedType . '_balance',
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

        if ($type === 'yearly') {
            $payload['from_date'] = $request->year . '-01-01';
            $payload['to_date'] = $request->year . '-12-31';
        } elseif ($type === 'monthly') {
            $payload['from_date'] = null;
            $payload['to_date'] = null;
        } elseif ($type === 'daily') {
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
                    'booking_income' => $data['monthly_income'],
                    'other_income' => $data['other_total_income'],
                    'expense' => $data['monthly_expense'],
                    'refund' => $data['monthly_refund'],
                    'pending' => $data['monthly_pending'],
                    'total_revenue' => $data['monthlyBalance'],
                ],
                'list' => $paginatedList['items'],
                'transactions' => $paginatedTransactions['items'],
                'pagination' => [
                    'list' => $paginatedList['pagination'],
                    'transactions' => $paginatedTransactions['pagination'],
                ],
            ];
        }

        $data = $this->todayFinancialData($innerRequest);
        $paginatedTransactions = $this->paginateArrayItems(
            $data['collection'],
            (int) $request->input('page', 1),
            (int) $request->input('per_page', 20)
        );

        return [
            'summary' => [
                'booking_income' => $data['today_booking_amt'],
                'other_income' => $data['today_other_amt'],
                'expense' => $data['today_expense'],
                'refund' => $data['today_refund'],
                'pending' => $data['today_pending'],
                'total_revenue' => $data['total_revenue'],
            ],
            'transactions' => $paginatedTransactions['items'],
            'pagination' => $paginatedTransactions['pagination'],
        ];
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
            'type' => 'other_wishes',
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
                'seat_no' => !empty($learner->seat_no) ? ('Seat ' . $learner->seat_no) : '',
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
            'tital' => '',
            'description' => '',
            'birthday_user' => '',
            'seat_no' => '',
            'subscription_type' => $subscriptionName,
            'subscription_status' => $subscriptionStatus,
            'days_in_left' => $daysLeft,
        ];

        return $banners;
    }

    private function lastBanner(): array
    {
        return [
            [
                'tital' => 'Libraro Track Everithing',
                'description' => '',
                'image' => '',
                'link' => '',
            ],
            [
                'tital' => 'Libraro Track Everithing',
                'description' => '',
                'image' => '',
                'link' => '',
            ],
            [
                'tital' => 'Libraro Track Everithing',
                'description' => '',
                'image' => '',
                'link' => '',
            ],
        ];
    }

        
    }
