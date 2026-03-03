<?php

namespace App\Services;

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
        $totalSeats = Seat::where('branch_id', $branchId)->count();

        $bookedSeats = LearnerDetail::where('branch_id', $branchId)
            ->where('status', 1)
            ->distinct('seat_no')
            ->count('seat_no');

        return [
            'total_seats' => $totalSeats,
            'booked_seats' => $bookedSeats,
            'available_seats' => $totalSeats - $bookedSeats
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Online / QR Bookings
    |--------------------------------------------------------------------------
    */

    private function onlineBookings(int $branchId): array
    {
        return LearnerDetail::join('learners', 'learners.id', '=', 'learner_detail.learner_id')
            ->join('plan_types', 'plan_types.id', '=', 'learner_detail.plan_type_id')
            ->where('learner_detail.branch_id', $branchId)
            ->where('learner_detail.status', 1)
            ->latest('learner_detail.id')
            ->limit(5)
            ->get([
                'learner_detail.seat_no',
                'learners.name',
                'plan_types.name as shift',
                'learner_detail.payment_status'
            ])
            ->map(function ($row) {
                return [
                    'seat_no' => $row->seat_no,
                    'name' => $row->name,
                    'shift' => $row->shift,
                    'status' => $row->payment_status == 1 ? 'paid' : 'unpaid'
                ];
            })
            ->toArray();
    }

    /*
    |--------------------------------------------------------------------------
    | Expired Members
    |--------------------------------------------------------------------------
    */

    private function expiredMembers(int $branchId): array
    {
        return Learner::where('branch_id', $branchId)
            ->where('status', 1)
            ->whereNotNull('plan_end_date')
            ->orderBy('plan_end_date', 'asc')
            ->limit(5)
            ->get()
            ->map(function ($learner) {

                $days = Carbon::now()->diffInDays($learner->plan_end_date, false);

                return [
                    'seat_no' => $learner->seat_no,
                    'name' => $learner->name,
                    'days_remaining' => $days,
                    'label' => $days >= 0
                        ? "Expires in {$days} days"
                        : "Expired " . abs($days) . " days ago"
                ];
            })
            ->toArray();
    }
}