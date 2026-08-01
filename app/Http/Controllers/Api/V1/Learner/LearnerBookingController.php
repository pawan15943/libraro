<?php

namespace App\Http\Controllers\Api\V1\Learner;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Services\QrBookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LearnerBookingController extends Controller
{
    /**
     * Public seat-booking request against a branch's QR (uuid) — same
     * field set as the web QR booking form (QrEntryController::store()).
     * Creates a pending Booking; staff approves it via qr-bookings/verify.
     */
    public function store(Request $request, string $uuid)
    {
        $branch = Branch::where('uuid', $uuid)->first();

        if (! $branch) {
            return response()->json([
                'status' => false,
                'message' => 'Branch not found',
            ], 404);
        }

        $rules = [
            'name'            => 'required|string|max:191',
            'mobile'          => 'required|integer|digits_between:8,15',
            'email'           => 'nullable|email',
            'dob'             => 'nullable|date',
            'general_seat'    => 'nullable|in:yes,no',
            'seat_no'         => ['nullable', 'required_if:general_seat,no'],
            'plan_id'         => 'required|integer|exists:plans,id',
            'plan_type_id'    => 'required|integer|exists:plan_types,id',
            'plan_price_id'   => 'required',
            'plan_start_date' => 'required|date',
            'payment_mode'    => 'required|in:online,offline',
            'id_proof_name'   => 'nullable|in:1,2,3',
            'id_proof_file'   => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf|max:2048',
            'id_proof_number' => 'nullable|string|max:150',
            'address'         => 'nullable|string|max:500',
            'profile_picture' => 'nullable|file|mimes:webp,png,jpg,jpeg|max:200',
            'learner_transaction_id' => 'nullable|integer|exists:learner_transactions,id',
        ];

        $messages = [
            'seat_no.required_if' => 'Seat number is required',
        ];

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();

        try {
            $booking = app(QrBookingService::class)->createBooking($validated, $branch, [
                'seat_no' => $request->seat_no ?: null,
                'learner_transaction_id' => $request->learner_transaction_id,
                'renewal' => $request->boolean('renewal'),
                'profile_picture_file' => $request->file('profile_picture'),
                'id_proof_file' => $request->file('id_proof_file'),
            ]);

            return response()->json([
                'status' => true,
                'message' => $validated['payment_mode'] === 'online'
                    ? 'Booking created! Please complete your payment.'
                    : 'Booking created! Please visit the branch to pay.',
                'data' => [
                    'booking_id' => $booking->id,
                    'payment_mode' => $booking->payment_mode,
                    'total_amount' => $booking->total_amount,
                ],
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Learner app booking store error', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ]);

            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
