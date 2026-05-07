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
        $branchId = auth()->user()->current_branch;

        $query = Booking::where('branch_id', $branchId)
            ->with('planType:id,name');

        // ✅ Date Filter
        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        // ✅ Booking Status Filter
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // ✅ Payment Status Filter
        if ($request->filled('payment_status')) {

            if ($request->payment_status == 'Paid') {
                $query->whereNotNull('payment_screenshot');
            }

            if ($request->payment_status == 'Unpaid') {
                $query->whereNull('payment_screenshot');
            }
        }

        // ✅ Search
        if ($request->filled('search')) {

            $search = $request->search;

            $query->where(function ($q) use ($search) {

                $q->where('name', 'LIKE', "%{$search}%")
                ->orWhere('mobile', 'LIKE', "%{$search}%");
            });
        }

        $bookings = $query->latest()->paginate(10);

        // ✅ Only Selected Data
        $bookings->getCollection()->transform(function ($booking) {

            return [
                'name' => $booking->name,

                'plan_type_name' => optional($booking->planType)->name,

                'seat_no' => $booking->seat_no,

                'payment_status' => $booking->payment_screenshot
                    ? 'Paid'
                    : 'Unpaid',
            ];
        });

        return response()->json([
            'status' => 'success',
            'data'   => $bookings
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
                'status' => 'success',
                'message' => $result['message'],
                'data' => $result
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
