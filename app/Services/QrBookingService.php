<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Learner;
use App\Models\LearnerDetail;
use App\Models\LearnerTransaction;
use App\Models\PlanType;
use App\Http\Controllers\LearnerController;
use Carbon\Carbon;
use DB;
use App\Services\LearnerService;
use App\Services\LearnerOperationService;
use Illuminate\Support\Facades\Log;

class QrBookingService
{
   
    protected $learnerOperationService;

    public function __construct(LearnerOperationService $learnerOperationService) {
        $this->learnerOperationService = $learnerOperationService;
    }
    public function verifyBooking($request, $service)
    {
        DB::beginTransaction();

        try {

    
            $booking = Booking::find($request->booking_id);

            if (!$booking) {
                throw new \Exception('Booking not found');
            }

            // ✅ Seat
            $requestedSeat = $request->input('seat_no');

            if ($requestedSeat === 0 || $requestedSeat === '0') {
                $seat_no = null; // explicit clear
            } elseif ($requestedSeat === null || $requestedSeat === '') {
                $seat_no = $booking->seat_no; // fallback only when truly missing
            } else {
                $seat_no = $requestedSeat;
            }

            /*
            |--------------------------------------------------------------------------
            | DIRECT VERIFY
            |--------------------------------------------------------------------------
            */

            if ($request->direct_validate) {

                $planPrice     = $booking->plan_price_id;
                $start_date    = Carbon::parse($booking->plan_start_date);
                $plan_id       = $booking->plan_id;
                $plan_type_id  = $booking->plan_type_id;

                $locker_no      = null;
                $locker         = 0;
                $discount       = 0;

                $total_amt      = $planPrice;
                $paid_amount    = $planPrice;
                $pending_amount = 0;
            }

            /*
            |--------------------------------------------------------------------------
            | MANUAL VERIFY / AFTER CHECK
            |--------------------------------------------------------------------------
            */

            else {

                $planPrice = (float) $request->plan_price_id;
                $locker    = (float) ($request->locker_amount ?? 0);

                $paid_amount = (float) $request->paid_amount;

                if (BillingAmountService::discountExceedsPayable($planPrice, $locker, $request->discount_type, (float) ($request->discount_amount ?? 0))) {
                    throw new \Exception('Discount cannot be more than the total payable amount.');
                }

                $billing = BillingAmountService::calculate(
                    $planPrice,
                    $locker,
                    $request->discount_type,
                    (float) ($request->discount_amount ?? 0),
                    $paid_amount
                );

                $discount = $billing['discount_amount'];
                $total_amt = $billing['total_amount'];
                $pending_amount = $billing['pending_amount'];

                if ($pending_amount > 0 && !$request->due_date) {
                    throw new \Exception('Due date is required');
                }

                $start_date = $request->plan_start_date
                    ? Carbon::parse($request->plan_start_date)
                    : Carbon::parse($booking->plan_start_date);

                $plan_id       = $request->plan_id;
                $plan_type_id  = $request->plan_type_id;

                $locker_no = $request->locker_no;
            }

            /*
            |--------------------------------------------------------------------------
            | PLAN TYPE
            |--------------------------------------------------------------------------
            */

            $planType = PlanType::withoutGlobalScopes()->find($plan_type_id);

            if (!$planType) {
                throw new \Exception('Invalid plan type selected');
            }

            $hours = $planType->slot_hours;

            $endDate = getEndDate($plan_id, $start_date);

            $extendDay = getExtendDays();

            $inextendDate = Carbon::parse($endDate)->addDays($extendDay);

            /*
            |--------------------------------------------------------------------------
            | LEARNER CHECK
            |--------------------------------------------------------------------------
            */

            $learnerId = $request->learner_id;
             $alreadyActive = false;

            if ($tra = LearnerTransaction::find($booking->transaction_id)) {
                $learnerId = $tra->learner_id;
            }

            if (!empty($learnerId)) {
                $alreadyActive = LearnerDetail::where('learner_id', $learnerId)->where('status', 1)->exists();
            }

            $customerfind = Learner::find($learnerId);

            $detailStatus = 0;

            if (
                $inextendDate->greaterThan(Carbon::today())
                &&
                $start_date->lessThanOrEqualTo(Carbon::today())
            ) {
                $detailStatus = 1;
            }
             // If any active plan exists → force inactive
            if ($alreadyActive) {
                $detailStatus = 0;
            }
            $is_paid = 1;
            if($request->payment_mode){
                $payment_mode = $request->payment_mode;
            }

            if (($inextendDate > Carbon::today() && $start_date <= Carbon::today()) || $detailStatus == 1) {
                $status = 1;
            } elseif($customerfind) {
                $status = $customerfind->status;
            }else{
                $status =0;
            } 

          


            /*
            |--------------------------------------------------------------------------
            | SEAT AVAILABILITY
            |--------------------------------------------------------------------------
            */
            if ($learnerId && $customerfind) {

                $today        = Carbon::today();
                $expiryLimit  = Carbon::today()->addDays(7);

                /* 1️⃣ Active plan (if any) */
                $activePlan = LearnerDetail::where('learner_id', $learnerId)
                    ->where('status', 1)
                    ->whereDate('plan_start_date', '<=', $today)
                    ->whereDate('plan_end_date', '>=', $today)
                    ->orderBy('plan_end_date', 'desc')
                    ->first();

                /* 2️⃣ Future plan check → HARD BLOCK */
                $futurePlanExists = LearnerDetail::where('learner_id', $learnerId)
                    ->whereDate('plan_start_date', '>', $today)
                    ->exists();

                if ($futurePlanExists) {
                    return redirect()->back()
                        ->with('error', 'Renewal already exists. Multiple renewals are not allowed.')
                        ->withInput();
                }

                /* 3️⃣ If ACTIVE plan exists → check buffer window */
                if ($activePlan) {

                    $planEndDate = Carbon::parse($activePlan->plan_end_date);

                    // ❌ Active & NOT in buffer days
                    if ($planEndDate->gt($expiryLimit)) {
                        return redirect()->back()
                            ->with('error', 'Current plan is active and not eligible for renewal yet.')
                            ->withInput();
                    }

                }

            }

            if (!empty($seat_no) && $seat_no !=0) {

                $noExpiry = (int) ($request->input('no_expiry') ?? 0) === 1;

                if (seatHeldByFuture(getCurrentBranch(), $seat_no, $plan_type_id, $start_date, $endDate, $learnerId ?? null, $noExpiry)) {
                    throw new \Exception($noExpiry
                        ? 'This seat is already assigned to a future booking and cannot be given a non-expiring plan.'
                        : 'This seat is already booked for the selected date and time.');
                }

                $check = checkAvailability(
                    getCurrentBranch(),
                    $seat_no,
                    $learnerId ?? null,
                    $plan_type_id,
                    $plan_id,
                    $start_date
                );

                if ($check['error'] === true) {
                    throw new \Exception($check['message']);
                }
            }

            if($request->previous_pending){
                $reprevious_pending=$request->previous_pending;
            }else{
                $reprevious_pending=0;
            }
            
            
            if (
                $paid_amount > (($planPrice + $locker) + $reprevious_pending)
                || ((float) $total_amt > 0 && $paid_amount == 0)
            ) {

                throw new \Exception('Paid amount is not valid');
            }

            if (($pending_amount > 0) && (!$request->due_date)) {

                throw new \Exception('Due Date is required');
            }

            /*
            |--------------------------------------------------------------------------
            | CREATE / UPDATE LEARNER
            |--------------------------------------------------------------------------

            */
            $relPathidproof = null;

            $relPath_profile = null;
             if (! empty($request->profile_picture)) {
                $relPath_profile = $this->learnerOperationService->moveTempFileToPublic(
                    $request->profile_picture,
                    'profile_picture',
                    'upload/profile_picture'
                );
               
            }

            if (! empty($request->id_proof_file)) {
                $relPathidproof = $this->learnerOperationService->moveTempFileToPublic(
                    $request->id_proof_file,
                    'id_proof_file',
                    'upload/id_proof_file'
                );
                
            }

            if ($learnerId && $customerfind) {

                $customer = Learner::find($learnerId);

                $customer->seat_no  = $seat_no;
                $customer->hours    = $hours;
                $customer->locker_no = $locker_no;
                $customer->status=$status;
                if (!empty($request->id_proof_name)) {
                    $customer->id_proof_name = $request->id_proof_name;
                }
                if (!empty($request->id_proof_number)) {
                    $customer->id_proof_number = $request->id_proof_number;
                }
                if (!empty($request->sended_message_type)) {
                    $customer->sended_message_type = $request->sended_message_type;
                }
                $customer->save();

            } else {

                $customer = Learner::create([
                    'seat_no' => $seat_no,
                    'name' => $request->input('name') ?? $booking->name,
                    'mobile' => encryptData($request->input('mobile')) ?? encryptData($booking->mobile),
                    'email' => $request->input('email') ? encryptData($request->input('email')) : null,
                    'dob' => $request->input('dob'),
                    'id_proof_name' => $request->input('id_proof_name'),
                    'id_proof_file' => $relPathidproof,
                    'id_proof_number'=>$request->input('id_proof_number') ?? $booking->id_proof_number,
                   
                    'no_expiry' => $request->input('no_expiry') ?? 0,
                    'hours' => $hours,
                    'status' => $status,
                    'library_id' => getLibraryId(),
                    'password' =>$booking->password,
                    'branch_id' => getCurrentBranch(),
                    'learner_no'=>generateLearnerCode(),
                    'father_name' => $request->input('father_name'),
                    'alternate_mobile' => $request->input('alternate_mobile'),
                    'remark' => $request->input('remark'),
                    'profile_picture'=>$relPath_profile,
                    'address' => $request->input('address'),
                    'locker_no'=>$locker_no ?? null ,
                    'sended_message_type'=>$request->input('sended_message_type') ?? 'no'
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | LEARNER DETAIL
            |--------------------------------------------------------------------------
            */

            $learner_detail = LearnerDetail::create([

                'library_id' => getLibraryId(),
                'branch_id' => getCurrentBranch(),
                'learner_id' => $customer->id,
                'plan_id' => $plan_id,
                'plan_type_id' => $plan_type_id,
                'plan_price_id' => $planPrice,
                'plan_start_date' => $start_date->format('Y-m-d'),
                'plan_end_date' => $endDate->format('Y-m-d'),
                'join_date' => $start_date->format('Y-m-d'),
                'hour' => $hours,
                'seat_no' => $seat_no,
                'payment_mode' => $payment_mode,
                'is_paid' =>1,
                'status' => $detailStatus,
                'exam_id' => $request->input('exam_id'),
            ]);

            /*
            |--------------------------------------------------------------------------
            | TRANSACTION
            |--------------------------------------------------------------------------
            */

            $tran = [

                'planPrice' => $planPrice,

                'paid_amount' => $paid_amount,

                'locker' => $locker,

                'discount' => $discount,

                'start_date' => $start_date->format('Y-m-d'),
                'paid_date' => $booking->created_at->format('Y-m-d') ?? null,
                'is_paid' => $is_paid,
                'learner_detail_id' => $learner_detail->id,

                'learner_id' => $customer->id,

                'payment_type' =>$booking->type == 'qr_renew' ? 'RENEW': 'SEAT ASSIGNMENT',
                'payment_mode' => $payment_mode,
                'due_date' => $request->due_date ?? null,
                'particular' => 'Paid By Trans',
                'library_id' => getLibraryId(),
                'branchId' => getCurrentBranch(),
                'transaction_date'=>$booking->created_at->format('Y-m-d')
            ];

            

            // learnerTransactionAddUpdate() creates the corresponding activity entry.
            $service->learnerTransactionAddUpdate($tran);

             $previousLearnerDetail = LearnerDetail::where('learner_id', $learnerId)
            ->where('id', '!=', $learner_detail->id) // important
            ->orderByDesc('plan_end_date')
            ->first();
            if($detailStatus==1 && $booking->type=='qr_renew' && $previousLearnerDetail){
                $previousLearnerDetail->status=0;
                $previousLearnerDetail->save();
            }


            /*
            |--------------------------------------------------------------------------
            | DELETE BOOKING
            |--------------------------------------------------------------------------
            */

            $booking->delete();

            DB::commit();

            return [

                'status' => true,

                'message' => 'Booking verified successfully',

                'learner_id' => $customer->id,
            ];

        } catch (\Throwable $e) {

            DB::rollBack();

            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Create a pending self-service seat-booking request against a branch's
     * public QR (same field set as the web QR booking form, see
     * QrEntryController::store()). Staff later approves it via verifyBooking().
     * Used by the learner-app "book seat" API; throws \Exception with a
     * user-facing message on validation/availability failure.
     */
    public function createBooking(array $validated, \App\Models\Branch $branch, array $extra = []): \App\Models\Booking
    {
        $planId = $validated['plan_id'];
        $seatNo = $extra['seat_no'] ?? null;
        $learnerTransactionId = $extra['learner_transaction_id'] ?? null;

        $startDate = Carbon::parse($validated['plan_start_date'])->addDay();
        $endDate = getEndDate($planId, $startDate, $branch->id);

        $transaction = $learnerTransactionId
            ? LearnerTransaction::withoutGlobalScopes()->find($learnerTransactionId)
            : null;
        $learnerId = $transaction?->learner_id ?? null;

        if ($seatNo) {
            $seatCheck = $this->validateSeatForBooking($branch->id, $validated['plan_type_id'], $seatNo, $learnerId);

            if ($seatCheck['error']) {
                throw new \Exception($seatCheck['message']);
            }
        }

        if ($learnerId) {
            $today = Carbon::today();
            $expiryLimit = Carbon::today()->addDays(7);

            $activePlan = LearnerDetail::where('learner_id', $learnerId)
                ->where('status', 1)
                ->whereDate('plan_start_date', '<=', $today)
                ->whereDate('plan_end_date', '>=', $today)
                ->orderBy('plan_end_date', 'desc')
                ->first();

            $futurePlanExists = LearnerDetail::where('learner_id', $learnerId)
                ->whereDate('plan_start_date', '>', $today)
                ->exists();

            if ($futurePlanExists) {
                throw new \Exception('Renewal already exists. Multiple renewals are not allowed.');
            }

            if ($activePlan && Carbon::parse($activePlan->plan_end_date)->gt($expiryLimit)) {
                throw new \Exception('Current plan is active and not eligible for renewal yet.');
            }
        }

        if ($transaction) {
            $password = Learner::where('id', $learnerId)->value('password') ?? Hash::make($validated['mobile']);
            $totalAmount = $extra['paid_amount'] ?? $validated['plan_price_id'];
        } else {
            $password = Hash::make($validated['mobile']);
            $totalAmount = $validated['plan_price_id'];
        }

        $profilePicturePath = null;
        if (!empty($extra['profile_picture_file'])) {
            $profilePicturePath = $this->moveUploadedBookingFile(
                $extra['profile_picture_file'],
                'profile_picture',
                'upload/profile_picture'
            );
        }

        $idProofFilePath = null;
        if (!empty($extra['id_proof_file'])) {
            $idProofFilePath = $this->moveUploadedBookingFile(
                $extra['id_proof_file'],
                'id_proof_',
                'upload/id_proof_file'
            );
        }

        return Booking::create([
            'name'            => $validated['name'],
            'mobile'          => encryptData($validated['mobile']),
            'email'           => !empty($validated['email']) ? encryptData($validated['email']) : null,
            'dob'             => $validated['dob'] ?? null,
            'password'        => $password,
            'seat_no'         => $seatNo,
            'branch_id'       => $branch->id,
            'plan_id'         => $validated['plan_id'],
            'plan_type_id'    => $validated['plan_type_id'],
            'plan_price_id'   => $validated['plan_price_id'],
            'plan_start_date' => $validated['plan_start_date'],
            'plan_end_date'   => $endDate,
            'payment_mode'    => $validated['payment_mode'],
            'status'          => 'pending',
            'total_amount'    => $totalAmount,
            'transaction_id'  => $transaction?->id,
            // 'learner_book' marks bookings submitted via the learner app
            // (createBooking() is only ever called from there — the web QR
            // form still writes 'qr_seat_book' via its own inline logic in
            // QrEntryController::store()), so staff can tell app vs
            // physical-QR-poster origin apart. Renewal type is left as
            // 'qr_renew' unchanged — it's checked by exact string in
            // verifyBooking() and QrEntryController's approval flow.
            'type'            => !empty($extra['renewal']) ? 'qr_renew' : 'learner_book',
            'profile_picture' => $profilePicturePath,
            'id_proof_name'   => $validated['id_proof_name'] ?? null,
            'id_proof_file'   => $idProofFilePath,
            'id_proof_number' => $validated['id_proof_number'] ?? null,
            'address'         => $validated['address'] ?? null,
        ]);
    }

    private function validateSeatForBooking($branchId, $planTypeId, $seatNo, $learnerId = null): array
    {
        $totalHour = \App\Models\Hour::withoutGlobalScopes()->where('branch_id', $branchId)->value('hour') ?? 0;

        if ($totalHour === 0) {
            return ['error' => true, 'message' => 'Total available hours not set.'];
        }

        $hours = PlanType::where('id', $planTypeId)->value('slot_hours') ?? 0;

        $conflict = Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
            ->where('learners.branch_id', $branchId)
            ->where('learners.seat_no', $seatNo)
            ->where('learner_detail.plan_type_id', $planTypeId)
            ->where('learners.status', 1)
            ->when($learnerId, fn ($q) => $q->where('learners.id', '!=', $learnerId))
            ->exists();

        if ($conflict) {
            return ['error' => true, 'message' => 'This plan type seat is already booked'];
        }

        $usedHours = Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
            ->where('learners.branch_id', $branchId)
            ->where('learners.seat_no', $seatNo)
            ->where('learner_detail.status', 1)
            ->when($learnerId, fn ($q) => $q->where('learners.id', '!=', $learnerId))
            ->sum('hours');

        if (($usedHours + $hours) > $totalHour) {
            return ['error' => true, 'message' => 'This seat is already reserved for the full library hours.'];
        }

        $futureConflict = Learner::leftJoin('learner_detail', 'learner_detail.learner_id', '=', 'learners.id')
            ->where('learners.branch_id', $branchId)
            ->where('learners.seat_no', $seatNo)
            ->where('learner_detail.plan_start_date', '>', Carbon::today())
            ->when($learnerId, fn ($q) => $q->where('learners.id', '!=', $learnerId))
            ->exists();

        if ($futureConflict) {
            return ['error' => true, 'message' => 'This plan conflicts with a future booking.'];
        }

        return ['error' => false];
    }

    private function moveUploadedBookingFile($file, string $prefix, string $folder): string
    {
        $fileName = $prefix . time() . '.' . $file->getClientOriginalExtension();
        $destinationFolder = public_path($folder);

        if (!\Illuminate\Support\Facades\File::exists($destinationFolder)) {
            \Illuminate\Support\Facades\File::makeDirectory($destinationFolder, 0777, true);
        }

        $file->move($destinationFolder, $fileName);

        return 'public/' . $folder . '/' . $fileName;
    }
}
