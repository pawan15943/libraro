<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Learner;
use App\Models\PlanType;
use App\Services\FileUploadService;
use App\Services\ShiftTimingService;
use Carbon\Carbon;

class StoreLearnerRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {

        $rules = [

            'email' => [
                'nullable',
                'email',
                Rule::unique('learners')->where(function ($query) {
                    return $query->where('branch_id', getCurrentBranch());
                }),
            ],

            'name' => 'required',
            'mobile' => 'required|digits:10',
            'dob' => 'nullable|date_format:Y-m-d',
            'father_name' => 'nullable|string|max:255',
            'alternate_mobile' => 'nullable|digits:10',
            'address' => 'nullable|string|max:500',
            'remark' => 'nullable|string|max:1000',


            "id_proof_name"=>'nullable',
            'id_proof' => 'nullable|file|mimes:jpg,png,jpeg,webp,pdf|max:5120',
            'id_proof_file' => 'nullable|string',
            "id_proof_number"=>'nullable',
            'profile_picture_image' => 'nullable|file|mimes:jpg,png,jpeg,webp|max:3072',
            'profile_picture' => 'nullable|string',

            

            'exam_id' => 'nullable|exists:exams,id',
            'exam_date' => 'nullable|date',
            'syllabus' => 'nullable|string|max:5000',

            'plan_id' => [
                'required',
                Rule::exists('plans', 'id')->where(function ($query) {
                    $query->where('library_id', getLibraryId());
                }),
            ],
            'plan_type_id' => [
                'required',
                Rule::exists('plan_types', 'id')->where(function ($query) {
                    $query->where('branch_id', getCurrentBranch());
                }),
            ],
            'plan_price_id' => 'required|numeric|min:0',

            'plan_start_date' => 'required|date',
            

            'paid_amount' => 'required|numeric|min:0',
            'payment_mode' => 'required|in:1,2,3',
            // paid more than the bill: an advance (default) or a higher price for this plan (docs/LEARNER_ADVANCE.md)
            'extra_as' => 'nullable|in:advance,price',
            'discountType' => 'nullable|in:amount,percentage',
            'discount_amount' => 'nullable|numeric|min:0',

            'locker_amount' => [
                'nullable',
                'required_if:toggleFieldCheckbox,yes',
                'numeric'
            ],

            'locker_no' => [
                'nullable',
                'required_if:toggleFieldCheckbox,yes',
                'numeric'
            ],
            'sended_message_type'=>'nullable|in:whatsapp,text,both,no',

            'no_expiry'=>'required|in:0,1',
            'due_date' => [
                'nullable',
                'date',
                Rule::requiredIf(function () {
                    if ((int) $this->input('payment_mode') === 3) {
                        return true;
                    }

                    $planPrice = (float) $this->input('plan_price_id', 0);
                    $paid = (float) $this->input('paid_amount', 0);
                    $locker = (float) $this->input('locker_amount', 0);
                    $discount = (float) $this->input('discount_amount', 0);
                    // a percentage discount is a share of the plan price, not rupees
                    if ($this->input('discountType') === 'percentage') {
                        $discount = round($planPrice * $discount / 100, 2);
                    }

                    $total = $planPrice + $locker - $discount;

                    return ($total - $paid) > 0;
                }),
            ],

        ];

        if ($this->general_seat != 'yes') {
            $rules['seat_no'] = 'required|integer';
        }

        // Timing for duration/free shift templates (checked in withValidator)
        $rules['shift_start_time'] = 'nullable|date_format:H:i';
        $rules['shift_end_time'] = 'nullable|date_format:H:i';

        // More shifts in the same booking (docs/ADVANCED_SHIFTS.md); plan_price_id is the total
        $rules['extra_shifts'] = 'nullable|array';
        $rules['extra_shifts.*.seat_no'] = 'required|integer|min:1';
        $rules['extra_shifts.*.plan_type_id'] = [
            'required',
            Rule::exists('plan_types', 'id')->where(fn ($q) => $q->where('branch_id', getCurrentBranch())),
        ];
        $rules['extra_shifts.*.shift_start_time'] = 'nullable|date_format:H:i';
        $rules['extra_shifts.*.shift_end_time'] = 'nullable|date_format:H:i';
        $rules['extra_shifts.*.price'] = 'required|numeric|min:0';

        return $rules;
    }

    public function messages()
    {
        return [
            'due_date.required' => 'Due date is required when payment mode is Pay Later or there is a pending amount.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $branchId = getCurrentBranch();
            if (!$branchId || (int) $branchId === 0 || !\App\Models\Branch::where('id', $branchId)->where('library_id', getLibraryId())->where('status', 1)->exists()) {
                $validator->errors()->add('branch_id', 'No active branch selected or branch is inactive. Please create/select an active branch first.');
            }

            // The same person booked twice (same name + same mobile, not deleted) in this branch:
            // they should be renewed / reactivated instead. Family members sharing one number (other
            // names) can still be booked. Web booking form only; the app API is left as it is.
            $mobile = preg_replace('/\D/', '', (string) $this->input('mobile'));
            $name = trim((string) $this->input('name'));
            if (! $this->is('api/*') && $branchId && strlen($mobile) === 10 && $name !== '') {
                $existing = \App\Models\Learner::withoutGlobalScopes()
                    ->where('branch_id', $branchId)
                    ->whereNull('deleted_at')
                    ->whereIn('mobile', [encryptData($mobile), $mobile])
                    ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
                    ->first(['id', 'name', 'learner_no', 'status']);
                if ($existing) {
                    $validator->errors()->add('mobile', "{$existing->name} (UID {$existing->learner_no}) is already a member with this mobile number. "
                        . ((int) $existing->status === 1 ? 'Renew their plan' : 'Reactivate them') . ' instead of booking again.');
                }
            }

            if ($this->email) {

                $exists = Learner::where('branch_id', getCurrentBranch())
                    ->whereNotNull('email')
                    ->where('email', '!=', '')
                    ->whereRaw('LOWER(email) = ?', [strtolower(trim($this->email))])
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('email', 'The email has already been taken.');
                }
            }

            $this->validateShiftMode($validator);
            $this->validateExtraShifts($validator);

            // discount: a percent can't pass 100, a flat amount can't pass the plan price
            $discount = (float) $this->input('discount_amount', 0);
            $planPrice = (float) $this->input('plan_price_id', 0);
            if ($this->discountType === 'percentage' && $discount > 100) {
                $validator->errors()->add('discount_amount', 'A percent discount can be at most 100%.');
            } elseif ($this->discountType === 'amount' && $discount > $planPrice) {
                $validator->errors()->add('discount_amount', 'Discount can\'t be more than the plan price (₹' . rtrim(rtrim(number_format($planPrice, 2, '.', ''), '0'), '.') . ').');
            }

            $lockerNo = $this->input('locker_no');

            if ($this->toggleFieldCheckbox === 'yes' && $lockerNo !== null && $lockerNo !== '') {

                $exists = Learner::where('branch_id', getCurrentBranch())
                    ->where('status', 1)
                    ->whereNull('deleted_at')
                    ->where('locker_no', $lockerNo)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('locker_no', 'This locker number is already assigned to another active learner in this branch.');
                }
            }

        });
    }

    // Duration/free templates are for numbered seats only and need a valid timing;
    // flexible shifts are for general seats only
    private function validateShiftMode($validator): void
    {
        if ($validator->errors()->has('plan_type_id')) {
            return;
        }

        $planType = PlanType::where('id', $this->plan_type_id)->first();
        if (!$planType) {
            return;
        }

        $mode = $planType->booking_mode ?: PlanType::MODE_FIXED;
        if ($mode === PlanType::MODE_FIXED && $planType->parent_id === null) {
            return;
        }

        $seatNo = (int) $this->input('seat_no');

        if ($planType->isFlexible()) {
            if ($seatNo > 0) {
                $validator->errors()->add('plan_type_id', 'A flexible shift can only be booked on a general seat.');
            }
            return;
        }

        if (!$planType->isTimedTemplate()) {
            $validator->errors()->add('plan_type_id', 'This shift cannot be booked from this form.');
            return;
        }

        if ($this->general_seat == 'yes' || $seatNo <= 0) {
            $validator->errors()->add('plan_type_id', 'This shift can only be booked on a numbered seat.');
            return;
        }

        $timing = app(ShiftTimingService::class)->validateTiming(
            $planType,
            $this->input('shift_start_time'),
            $this->input('shift_end_time')
        );

        if ($timing['error']) {
            $validator->errors()->add('shift_start_time', $timing['message']);
            return;
        }

        // the same time already taken on this seat for these dates
        $clash = $this->seatClash($seatNo, $timing['start'], $timing['minutes']);
        if ($clash) {
            $validator->errors()->add('shift_start_time', ShiftTimingService::clashMessage($clash));
        }
    }

    private function seatClash(int $seatNo, string $start, int $minutes): ?array
    {
        $branchId = (int) getCurrentBranch();
        if (!$branchId || $seatNo <= 0 || !$this->filled('plan_id')) {
            return null;
        }
        try {
            $startDate = Carbon::parse($this->input('plan_start_date') ?: today());
            $endDate = Carbon::parse(getEndDate($this->input('plan_id'), $startDate, $branchId));
        } catch (\Throwable $e) {
            return null;
        }

        return app(ShiftTimingService::class)->clashOnSeat($branchId, $seatNo, $startDate, $endDate, $start, $minutes);
    }

    // Swaps a duration/free template for the hidden child row carrying the chosen timing
    private function resolveShiftTiming(): void
    {
        $planType = PlanType::where('id', $this->plan_type_id)->first();
        if (!$planType || !$planType->isTimedTemplate()) {
            return;
        }

        $child = app(ShiftTimingService::class)->resolveChild(
            $planType,
            $this->input('shift_start_time'),
            $this->input('shift_end_time'),
            (int) $this->plan_id,
            $this->mainShiftPrice()
        );

        $this->merge(['plan_type_id' => $child->id]);
    }

    // Extra shifts: numbered seats only, a seat shift (fixed or timed template, not flexible),
    // valid timing for templates, and their prices within the booking total
    private function validateExtraShifts($validator): void
    {
        $extras = $this->input('extra_shifts');
        if (!is_array($extras) || empty($extras) || $validator->errors()->isNotEmpty()) {
            return;
        }

        if ($this->general_seat == 'yes' || (int) $this->input('seat_no') <= 0) {
            $validator->errors()->add('extra_shifts', 'More shifts can only be added to a booking on a numbered seat.');
            return;
        }

        $timing = app(ShiftTimingService::class);
        $n = 1;
        foreach ($extras as $key => $extra) {
            $n++;
            $planType = PlanType::where('id', $extra['plan_type_id'] ?? null)->first();
            $mode = $planType ? ($planType->booking_mode ?: PlanType::MODE_FIXED) : null;

            if (!$planType || $planType->parent_id !== null || !in_array($mode, [PlanType::MODE_FIXED, PlanType::MODE_DURATION, PlanType::MODE_FREE], true)) {
                $validator->errors()->add("extra_shifts.$key.plan_type_id", "Shift $n: this shift cannot be booked here.");
                continue;
            }

            if ($planType->isTimedTemplate()) {
                $check = $timing->validateTiming($planType, $extra['shift_start_time'] ?? null, $extra['shift_end_time'] ?? null);
                if ($check['error']) {
                    $validator->errors()->add("extra_shifts.$key.shift_start_time", "Shift $n: " . $check['message']);
                }
            }
        }

        if ($this->mainShiftPrice() < 0) {
            $validator->errors()->add('plan_price_id', 'The shift prices add up to more than the booking total.');
        }
    }

    // plan_price_id carries the booking total; the main shift's share is what the extras leave
    private function mainShiftPrice(): float
    {
        $extras = collect($this->input('extra_shifts') ?: []);

        return round((float) $this->plan_price_id - (float) $extras->sum(fn ($e) => (float) ($e['price'] ?? 0)), 2);
    }

    // Extra shifts ready to store: duration/free templates swapped for their timing rows
    private function resolveExtraShifts(): array
    {
        $timing = app(ShiftTimingService::class);

        return collect($this->input('extra_shifts') ?: [])->map(function ($extra) use ($timing) {
            $planType = PlanType::where('id', $extra['plan_type_id'])->first();
            if ($planType->isTimedTemplate()) {
                $planType = $timing->resolveChild(
                    $planType,
                    $extra['shift_start_time'] ?? null,
                    $extra['shift_end_time'] ?? null,
                    (int) $this->plan_id,
                    (float) $extra['price']
                );
            }

            return [
                'seat_no' => (string) (int) $extra['seat_no'],
                'plan_type_id' => (int) $planType->id,
                'price' => round((float) $extra['price'], 2),
            ];
        })->values()->all();
    }

    public function prepareData()
    {
        $this->resolveShiftTiming();
        $extraShifts = $this->resolveExtraShifts();

        $day_type = PlanType::where('id',$this->plan_type_id)
            ->select('day_type_id','slot_hours')
            ->first();
           

        $locker = $day_type->day_type_id == 11
            ? 0
            : (float)$this->input('locker_amount',0);

        $no_expiry = ($this->no_expiry == 1 || $day_type->day_type_id == 11) ? 1 : 0;

        if ($day_type->day_type_id == 11) {
            $payment_type = 'VIP';
        } elseif ($day_type->day_type_id == 10) {
            $payment_type = 'RESERVED';
        } elseif ($no_expiry == 1) {
            $payment_type = 'NON-EXPIRED';
        } else {
            $payment_type = 'SEAT ASSIGNMENT';
        }

        $id_proof_file = null;

   

        if ($this->hasFile('id_proof')) {
  
            $id_proof_file = $this->moveTempFileToPublic(
                $this->file('id_proof'),
                'id_proof_file',
                'upload/id_proof_file'
            );

        } elseif (!empty($this->id_proof_file) && is_string($this->id_proof_file)) {
            
            $id_proof_file = $this->moveTempFileToPublic(
                $this->id_proof_file ?: $this->id_proof,
                'id_proof_file',
                'upload/id_proof_file'
            );
        }
        

        $profile_picture = null;

      
         if ($this->hasFile('profile_picture_image')) {
  
            $profile_picture = $this->moveTempFileToPublic(
                $this->file('profile_picture_image'),
                'profile_picture',
                'upload/profile_picture'
            );

        } elseif (!empty($this->profile_picture) && is_string($this->profile_picture)) {
            
            $profile_picture = $this->moveTempFileToPublic(
                $this->profile_picture,
                'profile_picture',
                'upload/profile_picture'
            );
        }

        $seat_no = ($this->seat_no == 0) ? null : $this->seat_no;

        return [

            'learner_data'=>[
                'seat_no'=>$seat_no,
                'name'=>$this->name,
                'mobile'=>encryptData($this->mobile),
                'email'=>$this->email ? encryptData($this->email) : null,
                'dob'=>$this->dob,
                'library_id'=>getLibraryId(),
                'branch_id'=>getCurrentBranch(),
                'password'=>bcrypt($this->mobile),
                'learner_no'=>generateLearnerCode(),
                'hours'=>$day_type->slot_hours,
                'status'=>0,
                'locker_no'=>$this->locker_no,
                'no_expiry'=>$no_expiry,
                'id_proof_name'=>$this->id_proof_name,
                'id_proof_number'=>$this->id_proof_number,
                'id_proof_file'=>$id_proof_file,
                'father_name'=>$this->father_name,
                'alternate_mobile'=>$this->alternate_mobile,
                'remark'=>$this->remark,
                'profile_picture'=>$profile_picture,
                'address'=>$this->address,
                'sended_message_type'=>$this->sended_message_type ?? 'no',
            ],

            'plan_id'=>$this->plan_id,
            'plan_type_id'=>$this->plan_type_id,
            'plan_price'=>$this->plan_price_id,
            'payment_mode'=>$this->payment_mode,
            'paid_amount'=>$this->paid_amount,
            'extra_as'=>$this->extra_as === 'price' ? 'price' : 'advance',
            'locker_amount'=>$locker,
            'payment_type'=>$payment_type,
            'start_date'=>$this->plan_start_date,
            'seat_no'=>$this->seat_no,
            'branchId'=>getCurrentBranch(),
            'discount_type'=>$this->discountType,
            'discount_amount'=>$this->discount_amount,
            'paid_date'=>date('Y-m-d'),
            'due_date'=>$this->due_date,
            'particular'=>'Website book form',
            'library_id'=>getLibraryId(),
            'exam_id'=>$this->exam_id,
            'exam_date'=>$this->exam_date,
            'syllabus'=>$this->syllabus,
            'extra_shifts'=>$extraShifts,
        ];
    }


    function moveTempFileToPublic($file, $filePrefix = 'file', $folder)
    {
        return app(FileUploadService::class)->moveTempFileToPublic($file, $filePrefix, $folder);
    }
}
