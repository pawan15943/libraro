<?php

namespace App\Http\Controllers\api\V1;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\LearnerService;
use App\Services\QrBookingService;
use Illuminate\Http\Request;

class QrBookingController extends Controller
{
    private function decryptedBookingValue(Booking $booking, string $key): string
    {
        $rawValue = $booking->getRawOriginal($key);

        if ($rawValue === null || $rawValue === '') {
            return '';
        }

        $decryptedValue = decryptData($rawValue);

        return (string) ($decryptedValue === false ? $booking->{$key} : $decryptedValue);
    }

    public function index(Request $request)
    {
        $request->validate([
            'date' => 'nullable|date',
            'search' => 'nullable|string',
        ]);

        $branchId = auth()->user()->current_branch;

        $applyCommonFilters = function ($query) use ($request) {
            if ($request->filled('date')) {
                $query->whereDate('created_at', $request->date);
            }
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $encryptedSearch = encryptData($search);

                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('mobile', $encryptedSearch);
                });
            }
        };

        $dailyDemoQuery = Booking::where('branch_id', $branchId)
            ->with('planType:id,name')
            ->where('type', 'demo-bookings');
        $applyCommonFilters($dailyDemoQuery);
        $dailyDemoInquiry = $dailyDemoQuery->latest()->get();

        $qrOnlineQuery = Booking::where('branch_id', $branchId)
            ->with('planType:id,name')
            ->where(function ($q) {
                $q->whereNull('type')->orWhere('type', '!=', 'demo-bookings');
            });
        $applyCommonFilters($qrOnlineQuery);
        $qrOnlineBooking = $qrOnlineQuery->latest()->get();

        $transform = function ($booking) {
            $profilePicture = ltrim((string) $booking->profile_picture, '/');
            if ($profilePicture !== '' && ! str_starts_with($profilePicture, 'public/')) {
                $profilePicture = 'public/'.$profilePicture;
            }

            return [
                'booking_id' => $booking->id,
                'name' => $booking->name,
                'mobile' => $this->decryptedBookingValue($booking, 'mobile'),
                'plan_type_name' => optional($booking->planType)->name,
                'seat_no' => (string) ($booking->seat_no ?? ''),
                'payment_status' => $booking->payment_screenshot ? 'Paid' : 'Unpaid',
                'plan_start_date' => $booking->plan_start_date,
                'profile_picture' => $profilePicture !== '' ? asset($profilePicture) : '',
                'created_at' => optional($booking->created_at)->format('d-m-Y')
            ];
        };
        $dailyDemoInquiry = $dailyDemoInquiry->map($transform)->values();
        $qrOnlineBooking = $qrOnlineBooking->map($transform)->values();

        return response()->json([
            'status' => true,
            'data'   => [
                'daily_demo_inquiry' => $dailyDemoInquiry,
                'qr_online_booking' => $qrOnlineBooking,
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
            ->select('bookings.*')
            ->selectRaw('bookings.total_amount as paid_amount')
            ->with('planType:id,name')
            ->first();

            if (!$booking) {
                return response()->json([
                    'status' => false,
                    'message' => 'Booking not found'
                ], 404);
            }

            $booking->plan_price_id = (string) ($booking->plan_price_id ?? '');
            $booking->total_amount = (string) ($booking->total_amount ?? '');
            $booking->paid_amount = (string) ($booking->paid_amount ?? '');
            $profilePicture = ltrim((string) $booking->profile_picture, '/');
            if ($profilePicture !== '' && ! str_starts_with($profilePicture, 'public/')) {
                $profilePicture = 'public/'.$profilePicture;
            }
            $booking->profile_picture = $profilePicture !== '' ? asset($profilePicture) : '';

            return response()->json([
                'status' => true,
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
        if ($denied = $this->denyWithoutAppPermission(['QR Seat Booking', 'qr-seat-booking', 'General Seat Booking', 'general-seat-booking'])) {
            return $denied;
        }

        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'direct_validate' => 'nullable|boolean',
            'due_date' => 'nullable|date',
            'id_proof_name' => 'nullable',
            'id_proof_number' => 'nullable|string',
            'exam_id' => 'nullable|integer',
            'no_expiry' => 'nullable|in:0,1',
            'sended_message_type' => 'nullable|in:whatsapp,sms,both,no',
        ]);

        try {

            $result = $qrService->verifyBooking($request, $service);

            return response()->json([
                'status' => 'true',
                'message' => $result['message'],
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request)
    {
        if ($denied = $this->denyWithoutAppPermission(['QR Seat Booking', 'qr-seat-booking', 'General Seat Booking', 'general-seat-booking'])) {
            return $denied;
        }

        $request->validate([
            'booking_id' => 'required|integer|exists:bookings,id',
        ]);

        $branchId = auth()->user()->current_branch;

        $booking = Booking::where('branch_id', $branchId)
            ->where('id', $request->booking_id)
            ->first();

        if (!$booking) {
            return response()->json([
                'status' => false,
                'message' => 'Booking not found'
            ], 404);
        }

        $booking->delete();

        return response()->json([
            'status' => true,
            'message' => 'Booking deleted successfully'
        ]);
    }
}
