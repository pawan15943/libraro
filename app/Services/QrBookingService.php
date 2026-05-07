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

class QrBookingService
{
   

    public function verifyBooking($request, $service)
    {
        DB::beginTransaction();

        try {

           

            $booking = Booking::find($request->booking_id);

            if (!$booking) {
                throw new \Exception('Booking not found');
            }

            // ✅ Seat
            $seat_no = $request->seat_no ?? $booking->seat_no;

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

                if ($request->discount_type == 'amount') {

                    $discount = $request->discount_amount;

                } elseif ($request->discount_type == 'percentage') {

                    $total = $planPrice + $locker;

                    $discount = ($total * $request->discount_amount) / 100;

                } else {

                    $discount = 0;
                }

                $total_amt = $planPrice + $locker - $discount;

                $paid_amount = (float) $request->paid_amount;

                $pending_amount = $total_amt - $paid_amount;

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
            if($request->payment_mode=='online'){
                $payment_mode = 1;
            }else{
                $payment_mode = 2;
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

            if (!empty($seat_no)) {

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
            
            
            if (($paid_amount > ($total_amt +$reprevious_pending)) || ($paid_amount == 0)) {
                return redirect()->back()->with('error', 'Paid amount is not valid')->withInput();
               
            }
            if (($pending_amount > 0) && (!$request->due_date)) {
                return redirect()->back()->with('error', 'Due Date is required')->withInput();
              
            }

            /*
            |--------------------------------------------------------------------------
            | CREATE / UPDATE LEARNER
            |--------------------------------------------------------------------------
            */

            if ($learnerId && $customerfind) {

                $customer = Learner::find($learnerId);

                $customer->seat_no  = $seat_no;
                $customer->hours    = $hours;
                $customer->locker_no = $locker_no;
                $customer->status=$status;
                $customer->save();

            } else {

                $customer = Learner::create([
                    'seat_no' => $seat_no,
                    'name' => $request->input('name') ?? $booking->name,
                    'mobile' => encryptData($request->input('mobile')) ?? encryptData($booking->mobile),
                    'email' => $request->input('email') ? encryptData($request->input('email')) : null,
                    'dob' => $request->input('dob'),
                    'id_proof_name' => $request->input('id_proof_name'),
                    'id_proof_file' => $id_proof_file,
                    'id_proof_number'=>$request->input('id_proof_number') ?? $booking->id_proof_number,
                    'hours' => $hours,
                    'status' => $status,
                    'library_id' => getLibraryId(),
                    'password' =>$booking->password,
                    'branch_id' => getCurrentBranch(),
                    'learner_no'=>generateLearnerCode(),
                    'father_name' => $request->input('father_name'),
                    'alternate_mobile' => $request->input('alternate_mobile'),
                    'remark' => $request->input('remark'),
                    'profile_picture'=>$profile_picture,
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
                'particular' => $data['particular'] ?? 'System',
                'library_id' => getLibraryId(),

                'branchId' => getCurrentBranch(),
                'transaction_date'=>$booking->created_at->format('Y-m-d')
            ];
            $service->learnerTransactionAddUpdate($tran);

          

            /*
            |--------------------------------------------------------------------------
            | ACTIVITY
            |--------------------------------------------------------------------------
            */

            $data = [];

            $data['learner_id'] = $customer->id;

            $data['particular'] = 'Paid By Trans';

            $data['payment_mode'] = 1;

            $data['amount'] = $paid_amount;

            $data['dr_cr'] = 'Cr';

            $data['payment_type'] =$booking->type == 'qr_renew' ? 'RENEW' : 'SEAT ASSIGNMENT';

           
            $service->learnerTransactionActivity($data);

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
}