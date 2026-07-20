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

        $unread = DB::table('notifications')
            ->where('notifiable_id', $user->id)
            ->where('guard', 'library')
            ->where('status', 1)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->select('batch_id', 'guard', 'data', DB::raw('MIN(read_at) as read_at'))
            ->groupBy('batch_id', 'guard', 'data')
            ->havingRaw('MIN(read_at) IS NULL');

        return DB::query()->fromSub($unread, 'unread_notifications')->count();
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

        // Single pass over the filtered rows instead of 7 separate SUM(...) round trips
        // (collection/total_cr were literally duplicate queries before this merge).
        $totals = $baseQuery->selectRaw("
                SUM(CASE WHEN dr_cr = 'Cr' THEN amount ELSE 0 END) as total_cr,
                SUM(CASE WHEN dr_cr = 'Dr' THEN amount ELSE 0 END) as total_dr,
                SUM(CASE WHEN dr_cr = 'Cr' AND payment_type IN ('TOKEN MONEY', 'MISCELLANEOUS') THEN amount ELSE 0 END) as other_amt,
                SUM(CASE WHEN payment_type = 'EXPENSE' THEN amount ELSE 0 END) as expense_amt,
                SUM(CASE WHEN payment_type = 'PENDING' THEN amount ELSE 0 END) as pending_amt,
                SUM(CASE WHEN payment_type = 'REFUND' OR (payment_type = 'CHANGE PLAN' AND dr_cr = 'Dr') THEN amount ELSE 0 END) as refund_amt
            ")
            ->first();

        $totalCr = (float) ($totals->total_cr ?? 0);
        $totalDr = (float) ($totals->total_dr ?? 0);

        return [
            'type' => $type,
            'value' => (string) $resolvedValue,
            'collection' => (string) $totalCr,
            'other_income' => (string) ($totals->other_amt ?? 0),
            'expense' => (string) ($totals->expense_amt ?? 0),
            'refund' => (string) ($totals->refund_amt ?? 0),
            'pending_payment' => (string) ($totals->pending_amt ?? 0),
            'balance' => (string) ($totalCr - $totalDr)
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
        $bookings = Booking::where('bookings.branch_id', $branchId)
            ->where('bookings.type', 'qr_seat_book')
            ->leftJoin('plans', 'plans.id', '=', 'bookings.plan_id')
            ->leftJoin('plan_types', 'plan_types.id', '=', 'bookings.plan_type_id')
            ->select(
                'bookings.id', 'bookings.seat_no', 'bookings.name', 'bookings.mobile',
                'bookings.payment_screenshot', 'bookings.profile_picture',
                'bookings.plan_start_date', 'bookings.status', 'bookings.created_at',
                'plans.name as plan_name', 'plan_types.name as plan_type_name'
            )
            ->orderByDesc('bookings.created_at')
            ->limit(5)
            ->get();

        $list = $bookings->map(function ($booking) {

            $isPaid = !empty($booking->payment_screenshot);

            return [
                'booking_id' => $booking->id,
                'seat_no' => $this->dashboardSeatNo($booking->seat_no),
                'name' => $booking->name,
                'mobile' => $this->dashboardMobile($booking->mobile),
                'plan_name' => $booking->plan_name ?? '',
                'plan_type_name' => $booking->plan_type_name ?? '',
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
        $query = DB::table('learner_transactions')
            ->join('learners', 'learners.id', '=', 'learner_transactions.learner_id')
            ->where('learner_transactions.branch_id', $branchId)
            ->where('learners.branch_id', $branchId)
            ->whereNull('learners.deleted_at')
            ->where('learner_transactions.pending_amount', '>', 0)
            // learner_transactions always has deleted_at (LearnerTransaction uses SoftDeletes) —
            // no need for a Schema::hasColumn() introspection query on every call.
            ->whereNull('learner_transactions.deleted_at')
            ->select(
                'learners.id as learner_id',
                'learners.profile_picture',
                'learners.seat_no',
                'learners.name',
                'learners.mobile',
                'learners.sended_message_type',
                DB::raw('MIN(learner_transactions.learner_detail_id) as learner_detail_id'),
                DB::raw('SUM(learner_transactions.pending_amount) as pending_amount'),
                DB::raw('MIN(learner_transactions.due_date) as due_date')
            )
            ->groupBy(
                'learners.id',
                'learners.profile_picture',
                'learners.seat_no',
                'learners.name',
                'learners.mobile',
                'learners.sended_message_type'
            )
            ->orderByRaw('MIN(learner_transactions.due_date) IS NULL')
            ->orderByRaw('MIN(learner_transactions.due_date) ASC');

        $totalCount = DB::query()
            ->fromSub(clone $query, 'pending_learners')
            ->count();

        $data = clone $query;

        if ($limit !== null) {
            $data->limit($limit);
        }

        $data = $data->get()

            ->map(function ($item) {
                $pendingAmount = (float) $item->pending_amount;
                $dueDate = $item->due_date ? Carbon::parse($item->due_date) : null;
                if($item->sended_message_type=='whatsapp'){
                        $sended_message_type=1;
                }elseif($item->sended_message_type=='text'){
                        $sended_message_type=2;
                }elseif($item->sended_message_type=='both'){
                        $sended_message_type=3;
                }else{
                        $sended_message_type=0;
                }

                return [
                    'learner_id'=>$item->learner_id ?? '',
                    'learner_detail_id'=>$item->learner_detail_id ?? '',

                    'profile_picture' =>
                        $item->profile_picture
                        ? asset($item->profile_picture)
                        : '',

                    'seat_no' => $this->dashboardSeatNo($item->seat_no),

                    'name' => $item->name,
                    'mobile' => $this->dashboardMobile($item->mobile),

                    'pending_amount' =>(string)$pendingAmount,

                    'due_date' =>
                        $dueDate
                        ? $dueDate->format('d M')
                        : null,

                    'due_text' =>
                        $dueDate
                        ? 'Due ' . $pendingAmount . ' on ' . $dueDate->format('d M') . '.'
                        : 'Due ' . $pendingAmount . '.',
                    'sended_message_type' => $sended_message_type
                ];
            });

        return [
            'limit' => $limit,
            'count' => $totalCount,
            'list' => $data
        ];
    }

    private function duePayment(int $branchId)
    {
        return $this->duePaymentList($branchId, 5);
    }

    /**
     * Branch-wide pending-payment financial summary (see Api\V1\LearnerController::pendingPayment).
     */
    public function pendingPaymentSummary(int $branchId): array
    {
        $today = Carbon::today()->toDateString();

        $transactionBase = DB::table('learner_transactions as lt')
            ->join('learners', 'learners.id', '=', 'lt.learner_id')
            ->leftJoin('learner_detail as ld', 'ld.id', '=', 'lt.learner_detail_id')
            ->where('lt.branch_id', $branchId)
            ->where('learners.branch_id', $branchId)
            ->whereNull('learners.deleted_at')
            ->whereNull('lt.deleted_at');

        $activeDues = (clone $transactionBase)
            ->where('learners.status', 1)
            ->where('lt.pending_amount', '>', 0)
            ->selectRaw(
                "SUM(CASE WHEN COALESCE(ld.payment_mode, 0) = 3 THEN lt.pending_amount ELSE 0 END) as pay_later_amount,
                 SUM(CASE WHEN COALESCE(ld.payment_mode, 0) != 3 AND (lt.due_date IS NULL OR lt.due_date >= ?) THEN lt.pending_amount ELSE 0 END) as pending_payment_amount,
                 SUM(CASE WHEN COALESCE(ld.payment_mode, 0) != 3 AND lt.due_date IS NOT NULL AND lt.due_date < ? THEN lt.pending_amount ELSE 0 END) as non_expired_due_amount",
                [$today, $today]
            )
            ->first();

        $expiredLearnerDue = (clone $transactionBase)
            ->where('learners.status', 0)
            ->sum('lt.pending_amount');

        $adjustedPendingAmount = (clone $transactionBase)->sum('lt.sattle_amount');
        $extraPaidAmount = (clone $transactionBase)->sum('lt.refund');

        $receivedPendingAmount = DB::table('learner_transaction_activity')
            ->where('branch_id', $branchId)
            ->where('payment_type', 'PENDING')
            ->where('dr_cr', 'Cr')
            ->sum('amount');

        $pendingPaymentAmount = (float) ($activeDues->pending_payment_amount ?? 0);
        $payLaterAmount = (float) ($activeDues->pay_later_amount ?? 0);
        $nonExpiredDueAmount = (float) ($activeDues->non_expired_due_amount ?? 0);
        $totalPendingAmount = $pendingPaymentAmount + $payLaterAmount + $nonExpiredDueAmount;
        $expiredLearnerDueAmount = (float) $expiredLearnerDue;
        $adjustedPendingAmount = (float) $adjustedPendingAmount;
        $extraPaidAmount = (float) $extraPaidAmount;

        $finalDue = max(0, ($totalPendingAmount + $expiredLearnerDueAmount) - $adjustedPendingAmount - $extraPaidAmount);

        return [
            'total_pending_amount' => (string) $totalPendingAmount,
            'total_pending_breakdown' => [
                'pending_payment' => (string) $pendingPaymentAmount,
                'pay_later' => (string) $payLaterAmount,
                'non_expired_due' => (string) $nonExpiredDueAmount,
            ],
            'received_pending_amount' => (string) $receivedPendingAmount,
            'adjusted_pending_amount' => (string) $adjustedPendingAmount,
            'extra_paid_amount' => (string) $extraPaidAmount,
            'expired_learner_due_amount' => (string) $expiredLearnerDueAmount,
            'final_due' => (string) $finalDue,
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

        $query = Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
            ->where('learners.branch_id', $branchId)
            ->whereNull('learners.deleted_at')
            ->where('learners.no_expiry', 0)
            ->where('learner_detail.is_paid', 1)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('learner_operations_log as closed_op')
                    ->whereColumn('closed_op.learner_detail_id', 'learner_detail.id')
                    ->where('closed_op.operation', 'closeSeat');
            })

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
                    $q->where('learners.status', 1)
                    ->where('learner_detail.status', 1)
                    ->whereDate('learner_detail.plan_end_date', '>=', $today)
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

            ->orderBy('learner_detail.plan_end_date', 'asc');

        $totalCount = (clone $query)->count();

        $learners = $query
            ->limit(5)
            ->get([
                'learners.id',
                'learners.name',
                'learners.mobile',
                'learners.sended_message_type',
                'learner_detail.seat_no',
                'learner_detail.plan_end_date','learners.profile_picture'
            ]);

        $list = $learners->map(function ($learner) use ($today, $extendDay) {

            $planEndDate = Carbon::parse($learner->plan_end_date);
            $extensionEndDate = $planEndDate->copy()->addDays($extendDay);
            if($learner->sended_message_type=='whatsapp'){
                    $sended_message_type=1;
            }elseif($learner->sended_message_type=='text'){
                    $sended_message_type=2;
            }elseif($learner->sended_message_type=='both'){
                    $sended_message_type=3;
            }else{
                    $sended_message_type=0;
            }

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
                    'mobile' => $this->dashboardMobile($learner->mobile),
                    'sended_message_type'=>$sended_message_type,
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
                    'mobile' => $this->dashboardMobile($learner->mobile),
                    'sended_message_type'=>$sended_message_type,
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
                'mobile' => $this->dashboardMobile($learner->mobile),
                'sended_message_type'=>$sended_message_type,
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
            'count' => $totalCount,
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

    private function dashboardMobile($mobile): string
    {
        $mobile = trim((string) $mobile);

        if ($mobile === '') {
            return '';
        }

        $decrypted = decryptData($mobile);

        return !empty($decrypted) ? (string) $decrypted : $mobile;
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
            'dob' => '',
            'mobile' => '',
            'image_resource' =>"",
            'banner_link' =>"",
            'progress_percentage' =>0,
            'branch_id'=>getCurrentBranch()
        ]];

        $learners = Learner::query()
            ->where('branch_id', $branchId)
            ->whereMonth('dob', $today->month)
            ->whereDay('dob', $today->day)
            ->select('name', 'seat_no','dob','mobile')
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
                'dob' => (string) ($learner->dob ?? ''),
                'mobile' => $this->dashboardMobile($learner->mobile),
                'image_resource' =>"",
                'banner_link' =>"",
                'progress_percentage' =>0,
               'branch_id'=>getCurrentBranch()
            ];
        }

        // One information_schema round trip instead of two separate Schema::hasColumn() calls.
        $dobColumns = collect(DB::select(
            "SELECT TABLE_NAME as table_name FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND COLUMN_NAME = 'dob' AND TABLE_NAME IN ('libraries', 'library_users')"
        ))->pluck('table_name')->all();

        $librariesHasDob = in_array('libraries', $dobColumns, true);
        $libraryUsersHasDob = in_array('library_users', $dobColumns, true);

        // Single Library fetch reused for both the owner-birthday check and the subscription lookup below
        // (previously two separate queries against the same table).
        $library = Library::query()
            ->select(array_filter([
                'id', 'library_name', 'library_owner', 'library_type',
                $librariesHasDob ? 'dob' : null,
            ]))
            ->find($libraryId);

        if ($librariesHasDob && $library && !empty($library->dob)) {
            $ownerDob = Carbon::parse($library->dob);

            if ($ownerDob->month === $today->month && $ownerDob->day === $today->day) {
                $banners[] = [
                    'type' => 'birthday_wishes',
                    'tital' => 'Wish you happy birthay',
                    'description' => '',
                    'birthday_user' => (string) ($library->library_owner ?? $library->library_name ?? ''),
                    'seat_no' => '',
                    'subscription_type' => '',
                    'subscription_status' => '',
                    'days_in_left' => '',
                    'dob' => '',
                    'mobile' => '',
                    'image_resource' =>"",
                    'banner_link' =>"",
                     'progress_percentage' =>0,
                    'branch_id'=>getCurrentBranch()

                ];
            }
        }

        if ($libraryUsersHasDob) {
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
                    'dob' => '',
                    'mobile' => '',
                    'image_resource' =>"",
                    'banner_link' =>"",
                    'progress_percentage' =>0,
                   'branch_id'=>getCurrentBranch()
                ];
            }
        }

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
            'dob' => '',
            'mobile' => '',
            'image_resource' =>"",
            'banner_link' =>"",
            'progress_percentage' =>0,
            'branch_id'=>getCurrentBranch()
        ];
         $banners[] = [
            'type' => 'image',
            'tital' => 'image',
            'description' => '',
            'birthday_user' => '',
            'seat_no' => '',
            'subscription_type' => '',
            'subscription_status' => '',
            'days_in_left' => '',
            'dob' => '',
            'mobile' => '',
            'image_resource' =>asset('public/img/slider/topbanner.jpeg'),
            'banner_link' =>"",
            'progress_percentage' =>0,
            'branch_id'=>getCurrentBranch()
            
        ];
        if (!isLibraryProfileComplete()) {
            $banners[] = [
                'type' => 'profile',
                'tital' => 'profile',
                'description' => '',
                'birthday_user' => '',
                'seat_no' => '',
                'subscription_type' => '',
                'subscription_status' => '',
                'days_in_left' => '',
                'dob' => '',
                'mobile' => '',
                'image_resource' =>"",
                'banner_link' =>"",
                'progress_percentage' =>(int) round(getProfileCompletionPercentage()),
                'branch_id'=>getCurrentBranch()

            ];
        }

        return collect($banners)
            ->filter(fn ($banner) => !empty($banner['tital']))
            ->values()
            ->all();
    }

    private function lastBanner(): array
    {
        return [
            [
                'tital' => 'Welcome to Libraro',
                'description' => '',
                'image' => asset('public/img/slider/last_banner_1.jpeg'),
                'link' => '',
            ],
            [
                'tital' => 'Quick QR Seat Booking',
                'description' => '',
                'image' => asset('public/img/slider/last_banner_2.jpeg'),
                'link' => '',
            ],
            [
                'tital' => 'Quick QR Attendance',
                'description' => '',
                'image' => asset('public/img/slider/last_banner_3.jpeg'),
                'link' => '',
            ],
        ];
    }

        
    }
