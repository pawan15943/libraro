<?php

namespace App\Http\Controllers\api\V1;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\LearnerService;
use App\Services\QrBookingService;
use Illuminate\Http\Request;

class QrBookingController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'tab' => 'nullable|in:qr_online_booking,daily_demo_inquiry',
            'date' => 'nullable|date',
            'search' => 'nullable|string',
        ]);

        $branchId = auth()->user()->current_branch;
        $perPage = 10;
        $tab = $request->input('tab', 'qr_online_booking');

        $applyCommonFilters = function ($query) use ($request) {
            if ($request->filled('date')) {
                $query->whereDate('created_at', $request->date);
            }
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('mobile', 'LIKE', "%{$search}%");
                });
            }
        };

        $query = Booking::where('branch_id', $branchId)->with('planType:id,name');
        if ($tab === 'daily_demo_inquiry') {
            $query->where('type', 'demo-bookings');
        } else {
            $query->where(function ($q) {
                $q->whereNull('type')->orWhere('type', '!=', 'demo-bookings');
            });
        }
        $applyCommonFilters($query);
        $bookings = $query->latest()->paginate($perPage);

        $transform = function ($booking) {
            return [
                'booking_id' => $booking->id,
                'name' => $booking->name,
                'plan_type_name' => optional($booking->planType)->name,
                'seat_no' => (string) ($booking->seat_no ?? ''),
                'payment_status' => $booking->payment_screenshot ? 'Paid' : 'Unpaid',
                'plan_start_date' => $booking->plan_start_date,
                'profile_picture' => $booking->profile_picture ? asset($booking->profile_picture) : '',
            ];
        };
        $bookings->getCollection()->transform($transform);

        return response()->json([
            'status' => 'success',
            'data'   => [
                'tab' => $tab,
                $tab => $bookings,
            ]
        ]);
    }

    public function show(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|integer|exists:bookings,id',
        ]);

        $branchId = auth()->user()->current_branch;

            $booking = Booking::where('branch_id', $branchId)
            ->where('id', $request->booking_id)
            ->with('planType:id,name')
            ->first();

            if (!$booking) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Booking not found'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data'   => $booking
            ]);
    }

    /** Direct verify and without direct verify in seat book and renew-
     * if in qr-boking check first time booking or not.
     * if first time then check that shift in start to end date have any other learner(active and future) or not
     * no first time renew then check already have que any plan or not
     * no first time then check renew/qr booking ana if not renew then error message show
     * if renew then check that learner in our shift in start to end date have any other learner(active and future)(minus self)
        
    * */

    public function verify(Request $request, QrBookingService $qrService, LearnerService $service)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
        ]);

        try {

            $result = $qrService->verifyBooking($request, $service);

            return response()->json([
                'status' => 'true',
                'message' => $result['message'],
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|integer|exists:bookings,id',
        ]);

        $branchId = auth()->user()->current_branch;

        $booking = Booking::where('branch_id', $branchId)
            ->where('id', $request->booking_id)
            ->first();

        if (!$booking) {
            return response()->json([
                'status' => 'error',
                'message' => 'Booking not found'
            ], 404);
        }

        $booking->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Booking deleted successfully'
        ]);
    }
}
