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

class DashboardService
{
    public function getDashboardData(int $branchId, string $type, ?string $value = null): array
    {
        $branchName = DB::table('branches')->where('id', $branchId)->value('name');
        $authUser = auth('library_api')->user();
        $userType = 'library user';
        if ($authUser && get_class($authUser) === \App\Models\Library::class) {
            $userType = 'library owner';
        }

        return [
            'user' => [
                'name' => $authUser->name ?? $authUser->library_name ?? '',
                'avatar' => !empty($authUser->profile_picture) ? asset($authUser->profile_picture) : '',
                'current_branch_name' => $branchName ?? '',
                'type' => $userType,
            ],
            'collection' => $this->collectionSummary($branchId, $type, $value),
            'seat_summary' => $this->seatSummary($branchId),
            'library_occupancy' => $this->libraryOccupancy($branchId),
            'online_bookings' => $this->onlineBookings($branchId),
            'expired_members' => $this->expiredMembers($branchId),
            'due_pending' => $this->duePayment($branchId),
            'banner' => [
                'title' => 'Libraoo Track Everything',
                'image_url' => '',
                'cta_label' => 'View Details',
                'cta_action' => '/promo/libraoo-track',
                'colour' => '#22c55e',
            ],
            'stats_footer' => [
                'total_learners_count' => (int) Learner::where('branch_id', $branchId)->count(),
                'label' => 'Learner Added',
            ],
        ];
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
            ->select('id','seat_no','name','mobile','plan_id','plan_type_id','payment_screenshot','profile_picture')
            ->latest()
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
                
                'payment_status' => $isPaid ? 'paid' : 'unpaid',
                'is_paid' => $isPaid,

                'payment_screenshot' => $isPaid
                    ? asset($booking->payment_screenshot)
                    : '',
                
                 'profile_picture'=>$booking->profile_picture 
                                ? asset($booking->profile_picture) 
                                : '',
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

            'learners.profile_picture',

            'learners.seat_no',

            'learners.name',

            'learner_transactions.pending_amount',

            'learner_transactions.due_date'
        )

        ->whereNotNull('learner_transactions.id')

        ->get()

        ->map(function ($item) {

            return [

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

        })->toArray();

        return [
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
        $query = LearnerTransactionActivity::with('learner')
            ->where('branch_id', getCurrentBranch());

        /*
        |--------------------------------------------------------------------------
        | TODAY SUMMARY
        |--------------------------------------------------------------------------
        */

        $today_booking_amt =
            LearnerTransactionActivity::where(
                'branch_id',
                getCurrentBranch()
            )
            ->whereDate('date', now()->toDateString())
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

        $today_other_amt =
            LearnerTransactionActivity::where(
                'branch_id',
                getCurrentBranch()
            )
            ->whereDate('date', now()->toDateString())
            ->whereIn('payment_type', [
                'TOKEN MONEY',
                'MISCELLANEOUS'
            ])
            ->where('dr_cr', 'Cr')
            ->sum('amount');

        $today_expense =
            LearnerTransactionActivity::where(
                'branch_id',
                getCurrentBranch()
            )
            ->whereDate('date', now()->toDateString())
            ->where('payment_type', 'EXPENSE')
            ->sum('amount');

        $today_pending =
            LearnerTransactionActivity::where(
                'branch_id',
                getCurrentBranch()
            )
            ->whereDate('date', now()->toDateString())
            ->where('payment_type', 'PENDING')
            ->sum('amount');

        $today_refund =
            LearnerTransactionActivity::where(
                'branch_id',
                getCurrentBranch()
            )
            ->whereDate('date', now()->toDateString())
            ->where(function ($q) {

                $q->where('payment_type', 'REFUND')

                ->orWhere(function ($sub) {

                    $sub->where('payment_type', 'CHANGE PLAN')
                        ->where('dr_cr', 'Dr');
                });
            })
            ->sum('amount');

        $total_cr =
            LearnerTransactionActivity::where(
                'branch_id',
                getCurrentBranch()
            )
            ->whereDate('date', now()->toDateString())
            ->where('dr_cr', 'Cr')
            ->sum('amount');

        $total_dr =
            LearnerTransactionActivity::where(
                'branch_id',
                getCurrentBranch()
            )
            ->whereDate('date', now()->toDateString())
            ->where('dr_cr', 'Dr')
            ->sum('amount');

        $total_revenue = $total_cr - $total_dr;

        /*
        |--------------------------------------------------------------------------
        | FILTER CLICK LOGIC
        |--------------------------------------------------------------------------
        */

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

                $query->whereDate('date', now()->toDateString());

            break;
        }

        $query->whereDate('date', now()->toDateString());

        return [

            'today_booking_amt' => $today_booking_amt,

            'today_other_amt' => $today_other_amt,

            'today_expense' => $today_expense,

            'today_pending' => $today_pending,

            'today_refund' => $today_refund,

            'total_revenue' => $total_revenue,

            'collection' => $query->latest()->paginate(10)
        ];
    }

        /*
    |--------------------------------------------------------------------------
    | MONTHLY FINANCIAL
    |--------------------------------------------------------------------------
    */

    public function monthlyFinancialData($request)
    {
        $query = LearnerTransactionActivity::with('learner')
            ->where('branch_id', getCurrentBranch());

        /*
        |--------------------------------------------------------------------------
        | MONTH / YEAR
        |--------------------------------------------------------------------------
        */

        $year = $request->year ?? date('Y');

        $month = $request->month ?? date('m');

        /*
        |--------------------------------------------------------------------------
        | MONTHLY SUMMARY
        |--------------------------------------------------------------------------
        */

        $monthlyIncome =
            LearnerTransactionActivity::where(
                'branch_id',
                getCurrentBranch()
            )
            ->whereYear('date', $year)
            ->whereMonth('date', $month)

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

        $other_total_income =
            LearnerTransactionActivity::where(
                'branch_id',
                getCurrentBranch()
            )
            ->whereYear('date', $year)
            ->whereMonth('date', $month)

            ->whereIn('payment_type', [
                'TOKEN MONEY',
                'MISCELLANEOUS'
            ])

            ->where('dr_cr', 'Cr')

            ->sum('amount');

        $monthlyExpense =
            LearnerTransactionActivity::where(
                'branch_id',
                getCurrentBranch()
            )
            ->whereYear('date', $year)
            ->whereMonth('date', $month)

            ->where('payment_type', 'EXPENSE')

            ->sum('amount');

        $monthly_refund =
            LearnerTransactionActivity::where(
                'branch_id',
                getCurrentBranch()
            )
            ->whereYear('date', $year)
            ->whereMonth('date', $month)

            ->where(function ($q) {

                $q->where('payment_type', 'REFUND')

                ->orWhere(function ($sub) {

                    $sub->where('payment_type', 'CHANGE PLAN')
                        ->where('dr_cr', 'Dr');
                });
            })

            ->sum('amount');

        $monthly_pending =
            LearnerTransactionActivity::where(
                'branch_id',
                getCurrentBranch()
            )
            ->whereYear('date', $year)
            ->whereMonth('date', $month)

            ->where('payment_type', 'PENDING')

            ->sum('amount');

        $totals_monthly =
            LearnerTransactionActivity::selectRaw("
                SUM(CASE WHEN dr_cr = 'Cr' THEN amount ELSE 0 END) as total_cr,
                SUM(CASE WHEN dr_cr = 'Dr' THEN amount ELSE 0 END) as total_dr
            ")
            ->where('branch_id', getCurrentBranch())
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->first();

        /*
        |--------------------------------------------------------------------------
        | MONTHLY BALANCE LIST
        |--------------------------------------------------------------------------
        */

        $monthlyBalance =
            LearnerTransactionActivity::selectRaw("
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

            ->where('branch_id', getCurrentBranch())

            ->whereYear('date', $year)

            ->whereMonth('date', $month)

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

                'collection' => $row->collection,

                'other_collection' => $row->other_collection,

                'expense' => $row->expense,

                'refund' => $row->refund,

                'pending' => $row->pending,

                'net' => $net,

                'final_balance' => $runningBalance
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER CLICK LOGIC
        |--------------------------------------------------------------------------
        */

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

                $query->whereYear('date', $year)
                    ->whereMonth('date', $month);

            break;
        }

        $query->whereYear('date', $year)
            ->whereMonth('date', $month);

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

            'collection' => $query->latest()->paginate(10)
        ];
    }

        
    }
