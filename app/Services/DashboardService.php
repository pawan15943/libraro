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
    public function getDashboardData(int $branchId, string $type): array
    {
        return [
            'collection' => $this->collectionSummary($branchId, $type),
            'seat_summary' => $this->seatSummary($branchId),
            'online_bookings' => $this->onlineBookings($branchId),
            'expired_members' => $this->expiredMembers($branchId),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Collection Summary
    |--------------------------------------------------------------------------
    */

    private function collectionSummary(int $branchId, string $type): array
    {
        

        $query = LearnerTransactionActivity::where('branch_id', $branchId);

        if ($type === 'daily') {
            $query->whereDate('date', Carbon::today());
        } else {
            $query->whereMonth('date', Carbon::now()->month)
                  ->whereYear('date', Carbon::now()->year);
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
            'collection' => (int) $collection,
            'other_income' => $today_other_amt ?? 0,
            'expense' => $todayExpense ?? 0,
            'refund' =>$today_refund ?? 0,
            'pending_payment' => $today_pending ?? 0,
            'balance' => (int) $todayBalance
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
            ->select('id','seat_no','name','mobile','plan_id','plan_type_id','payment_screenshot')
            ->latest()
            ->get();

        return $bookings->map(function ($booking) {

            $isPaid = !empty($booking->payment_screenshot);

            return [
                'seat_no' => $booking->seat_no,
                'name' => $booking->name,
                'mobile' => $booking->mobile,
                'plan_name' => $booking->plan?->name ?? '',
                'plan_type_name' => $booking->planType?->name ?? '',
                
                'payment_status' => $isPaid ? 'paid' : 'unpaid',
                'is_paid' => $isPaid,

                'payment_screenshot' => $isPaid
                    ? url('storage/' . $booking->payment_screenshot)
                    : ''
            ];
        })->toArray();
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
            'learner_detail.plan_end_date'
        ]);

    return $learners->map(function ($learner) use ($today, $extendDay) {

        $planEndDate = Carbon::parse($learner->plan_end_date);
        $extensionEndDate = $planEndDate->copy()->addDays($extendDay);

        // 🟡 About to expire (future date)
        if ($planEndDate->gte($today)) {

            $daysLeft = $today->diffInDays($planEndDate);

            return [
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
            'seat_no' => $learner->seat_no,
            'name' => $learner->name,
            'plan_end_date' => $learner->plan_end_date,
            'days_remaining' => $expiredDays,
            'status' => 'expired',
            'label' => "Expired {$expiredDays} days ago"
        ];

    })->toArray();
}

        
    }