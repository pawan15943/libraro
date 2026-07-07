<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Branch;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class BookingEnquiryService
{
    public function __construct(private FileUploadService $fileUploadService)
    {
    }

    private const PAYMENT_MODE_MAP = [
        '1' => 'online',
        '2' => 'offline',
        '3' => 'paylater',
        1 => 'online',
        2 => 'offline',
        3 => 'paylater',
    ];

    public function validationRules(): array
    {
        return [
            'name' => 'required|string|max:191',
            'mobile' => 'required|digits_between:8,15',
            'email' => 'nullable|email',
            'dob' => 'nullable|date',
            'profile_picture' => 'nullable',
            'general_seat' => 'nullable|in:yes,no',
            'seat_no' => 'required_if:general_seat,no',
            'plan_id' => 'required|integer|exists:plans,id',
            'plan_type_id' => 'required|integer|exists:plan_types,id',
            'plan_price_id' => 'required',
            'plan_start_date' => 'required|date',
            'payment_mode' => 'required',
            'id_proof_name' => 'nullable|in:1,2,3,4,5',
            'id_proof_file' => 'nullable',
            'id_proof_number' => 'nullable|string|max:150',
            'address' => 'nullable|string|max:500',
            'remark' => 'nullable|string|max:500',
            'discountType' => 'nullable|in:amount,percentage',
            'discount_amount' => 'nullable|numeric|min:0',
            'locker_no' => 'nullable',
            'locker_amount' => 'nullable|numeric|min:0',
            'paid_amount' => 'nullable|numeric|min:0',
            'due_date' => 'nullable|date',
            'father_name' => 'nullable|string|max:191',
            'alternate_mobile' => 'nullable|digits_between:8,15',
            'exam_id' => 'nullable|integer',
            'no_expiry' => 'nullable|in:0,1',
            'sended_message_type' => 'nullable|in:whatsapp,text,both,no',
        ];
    }

    public function validationMessages(): array
    {
        return [
            'name.required' => 'Name is required.',
            'name.max' => 'Name must not exceed 191 characters.',
            'seat_no.required_if' => 'Seat number is required',
            'mobile.required' => 'Mobile number is required.',
            'mobile.integer' => 'Mobile number must contain only digits.',
            'mobile.digits_between' => 'Mobile number must be between 8 and 15 digits.',
            'plan_id.required' => 'Please select a plan.',
            'plan_id.exists' => 'Selected plan is invalid.',
            'plan_type_id.required' => 'Please select a plan type.',
            'plan_type_id.exists' => 'Selected plan type is invalid.',
            'plan_price_id.required' => 'Plan price is required.',
            'plan_start_date.required' => 'Plan start date is required.',
            'plan_start_date.date' => 'Please enter a valid start date.',
            'payment_mode.required' => 'Please select a payment mode.',
            'payment_mode.in' => 'Invalid payment mode selected.',
        ];
    }

    public function createDemoBooking(Request $request, int $branchId): Booking
    {
        $validated = $request->validate($this->validationRules(), $this->validationMessages());
        $paymentMode = $this->normalizePaymentMode($validated['payment_mode']);

        $branch = Branch::findOrFail($branchId);
        $startDate = Carbon::parse($validated['plan_start_date'])->addDay();
        $endDate = getEndDate($validated['plan_id'], $startDate, $branch->id);
        $encryptedMobile = encryptData($validated['mobile']);
        $encryptedEmail = !empty($validated['email']) ? encryptData($validated['email']) : null;

        $this->assertNoDuplicateBooking($encryptedMobile, $encryptedEmail);

        $profilePicturePath = null;
        if ($request->hasFile('profile_picture')) {
            $profilePicturePath = $this->fileUploadService->moveTempFileToPublic(
                $request->file('profile_picture'),
                'profile_picture',
                'upload/profile_picture'
            );
        } elseif (is_string($request->input('profile_picture')) && filter_var($request->input('profile_picture'), FILTER_VALIDATE_URL)) {
            $profilePicturePath = $this->fileUploadService->moveTempFileToPublic(
                $request->input('profile_picture'),
                'profile_picture',
                'upload/profile_picture'
            );
        }

        $idProofFilePath = null;
        if ($request->hasFile('id_proof_file')) {
            $idProofFilePath = $this->fileUploadService->moveTempFileToPublic(
                $request->file('id_proof_file'),
                'id_proof_file',
                'upload/id_proof_file'
            );
        } elseif (is_string($request->input('id_proof_file')) && filter_var($request->input('id_proof_file'), FILTER_VALIDATE_URL)) {
            $idProofFilePath = $this->fileUploadService->moveTempFileToPublic(
                $request->input('id_proof_file'),
                'id_proof_file',
                'upload/id_proof_file'
            );
        }

        return Booking::create([
            'name' => $validated['name'],
            'mobile' => $encryptedMobile,
            'email' => $encryptedEmail,
            'dob' => $validated['dob'] ?? null,
            'password' => Hash::make($validated['mobile']),
            'seat_no' => ((string) $request->input('seat_no')) === '0' ? null : $request->input('seat_no'),
            'branch_id' => $branch->id,
            'plan_id' => $validated['plan_id'],
            'plan_type_id' => $validated['plan_type_id'],
            'plan_price_id' => $validated['plan_price_id'],
            'plan_start_date' => $validated['plan_start_date'],
            'plan_end_date' => $endDate,
            'payment_mode' => $paymentMode,
            'status' => 'pending',
            'total_amount' => $validated['plan_price_id'],
            'transaction_id' => null,
            'type' => 'demo-bookings',
            'profile_picture' => $profilePicturePath,
            'id_proof_name' => $request->input('id_proof_name'),
            'id_proof_file' => $idProofFilePath,
            'id_proof_number' => $request->input('id_proof_number'),
            'address' => $request->input('address'),
        ]);
    }

    private function normalizePaymentMode($paymentMode): string
    {
        $normalized = self::PAYMENT_MODE_MAP[$paymentMode] ?? $paymentMode;

        if (!in_array($normalized, ['online', 'offline', 'paylater'], true)) {
            abort(422, 'Invalid payment mode selected.');
        }

        return $normalized;
    }

    private function assertNoDuplicateBooking(string $encryptedMobile, ?string $encryptedEmail): void
    {
        if (Booking::where('mobile', $encryptedMobile)->exists()) {
            throw ValidationException::withMessages([
                'mobile' => 'This mobile already has a booking enquiry.',
            ]);
        }

        if ($encryptedEmail && Booking::where('email', $encryptedEmail)->exists()) {
            throw ValidationException::withMessages([
                'email' => 'This email already has a booking enquiry.',
            ]);
        }
    }
}
