<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Branch;
use App\Models\Hour;
use App\Models\LearnerDetail;
use App\Models\Plan;
use App\Models\PlanType;
use App\Services\BookingEnquiryService;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DemoUserController extends Controller
{
    public function index()
    {
        $qrbookings = Booking::where('branch_id', getCurrentBranch())->with(['plan', 'planType'])->where('type', 'demo-bookings')->get();
        return view('library.demo-enquery', compact('qrbookings'));
    }

    public function create()
    {
        $branch = Branch::where('id', getCurrentBranch())->select('id', 'library_id', 'uuid')->firstOrFail();

        $totalSeats = Hour::withoutGlobalScopes()->where('branch_id', $branch->id)->value('seats');
        $totalHour = Hour::withoutGlobalScopes()->where('branch_id', $branch->id)->value('hour');

        $usedSeats = LearnerDetail::withoutGlobalScopes()->select('seat_no', DB::raw('SUM(hour) as used_hours'))
            ->where('branch_id', $branch->id)
            ->whereNotNull('seat_no')
            ->groupBy('seat_no')->where('status', 1)
            ->pluck('used_hours', 'seat_no');

        $availableSeats = collect();

        $allSeats = collect(generateSeatNumbers());
        $newAvailableSeat = collect();

        for ($seatNo = 1; $seatNo <= $totalSeats; $seatNo++) {
            $usedHours = $usedSeats[$seatNo] ?? 0;

            if ($usedHours < $totalHour) {
                $seatInfo = $allSeats->firstWhere('main', $seatNo);

                if ($seatInfo) {
                    $newAvailableSeat->push($seatInfo);
                } else {
                    $newAvailableSeat->push([
                        'main' => $seatNo,
                        'display' => $seatNo,
                    ]);
                }
            }
        }

        $plans = Plan::withoutGlobalScopes()->where('library_id', $branch->library_id)->whereNull('deleted_at')->get();

        $planType = PlanType::withoutGlobalScopes()->where('branch_id', $branch->id)->whereNull('deleted_at')->get();

        return view('library.demo-enquery-create', compact('branch', 'plans', 'planType', 'availableSeats', 'newAvailableSeat'));
    }

    public function store(Request $request, BookingEnquiryService $bookingEnquiryService)
    {
        try {
            $bookingEnquiryService->createDemoBooking($request, (int) getCurrentBranch());

            return redirect()
                ->route('demo-users.index')
                ->with('success', 'Booking successful! Please complete payment to confirm your seat.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return redirect()
                ->back()
                ->withErrors($e->validator)
                ->withInput();
        } catch (\Throwable $e) {
            Log::error('BOOKING STORE CRASH', [
                'method' => request()->method(),
                'url' => request()->fullUrl(),
                'message' => $e->getMessage(),
            ]);

            return redirect()
                ->route('demo-users.index')
                ->with('error', $e->getMessage() ?: 'Something went wrong.')
                ->withInput();
        }
    }
}
