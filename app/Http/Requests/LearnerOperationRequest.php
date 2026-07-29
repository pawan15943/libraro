<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use App\Models\Learner;
use App\Models\PlanType;
use Exception;
use Illuminate\Support\Facades\Log;

class LearnerOperationRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        Log::info('LearnerOperationRequest raw input', [
            'all' => $this->except(['profile_picture_image', 'id_proof']),
            'route' => optional($this->route())->getName() ?? $this->path(),
            'method' => $this->method(),
        ]);

        $data = [];

        if ($this->exists('discountType')) {
            $discountType = is_string($this->discountType) ? trim($this->discountType) : $this->discountType;
            $data['discountType'] = $discountType === '' ? null : $discountType;
        }

        if ($this->exists('discount_amount')) {
            $discountAmount = is_string($this->discount_amount) ? trim($this->discount_amount) : $this->discount_amount;
            $data['discount_amount'] = $discountAmount === '' && $this->filled('discountType') ? 0 : ($discountAmount === '' ? null : $discountAmount);
        }

        if ($this->exists('difference_amount') && ! $this->exists('diffrence_amount')) {
            $data['diffrence_amount'] = $this->normalizeMoneyInput($this->input('difference_amount'), 0);
        }

        if ($this->exists('diffrence_amount')) {
            $data['diffrence_amount'] = $this->normalizeMoneyInput($this->input('diffrence_amount'), 0);
        }

        if ($this->exists('locker')) {
            $locker = is_string($this->locker) ? trim($this->locker) : $this->locker;
            $data['locker'] = $locker === '' ? null : $locker;
        }

        if ($this->exists('locker_no')) {
            $lockerNo = is_string($this->locker_no) ? trim($this->locker_no) : $this->locker_no;
            $data['locker_no'] = $lockerNo === '' ? null : $lockerNo;
        }

        if ($this->exists('locker_amount')) {
            $lockerAmount = is_string($this->locker_amount) ? trim($this->locker_amount) : $this->locker_amount;
            $data['locker_amount'] = $lockerAmount === '' ? null : $lockerAmount;
        }

        if ($data !== []) {
            $this->merge($data);
        }
    }

    protected function failedValidation(ValidatorContract $validator)
    {
        Log::warning('LearnerOperationRequest validation failed', [
            'all' => $this->except(['profile_picture_image', 'id_proof']),
            'errors' => $validator->errors()->toArray(),
            'route' => optional($this->route())->getName() ?? $this->path(),
            'method' => $this->method(),
        ]);

        parent::failedValidation($validator);
    }

    private function normalizeMoneyInput($value, $emptyValue = null)
    {
        if ($value === null) {
            return $emptyValue;
        }

        if (is_string($value)) {
            $value = trim($value);

            if ($value === '') {
                return $emptyValue;
            }

            $value = preg_replace('/[^\d.\-]/', '', $value);

            if ($value === '' || $value === '-' || $value === '.' || $value === '-.') {
                return $emptyValue;
            }
        }

        return $value;
    }

    public function rules()
    {
         
        $isEditLearner = $this->payment_type === 'EDITLEARNER';
        $isEditOperation = $this->payment_type === 'EDIT';
        // RENEW never uses seat_no (LearnerOperationService keeps the learner's
        // existing seat for every operation except REACTIVE), so a malformed/
        // display-string seat_no from the app shouldn't block a renewal.
        $isReactive = ($this->payment_type ?? 'REACTIVE') === 'REACTIVE';
        return [

            'plan_start_date' => 'nullable|date',

            'plan_id' => [
                'nullable',
                Rule::requiredIf(fn () => !$isEditLearner && !$isEditOperation),
                Rule::exists('plans', 'id')->where(fn ($q) =>
                    $q->where('library_id', getLibraryId())
                )
            ],

           // ✅ plan_type_id (NOT required for EDITLEARNER)
            'plan_type_id' => [
                'nullable',
                Rule::requiredIf(fn () => !$isEditLearner && !$isEditOperation),
                Rule::exists('plan_types', 'id')->where(fn ($q) =>
                    $q->where('branch_id', getCurrentBranch())
                )
            ],

            // ✅ plan_price_id (NOT required for EDITLEARNER)
            'plan_price_id' => [
                'nullable',
                'numeric',
                'min:0',
                Rule::requiredIf(fn () => !$isEditLearner && !$isEditOperation),
            ],

                // ✅ Paid Amount
            'paid_amount' => [
                'nullable',
                'numeric',
                'min:0',
                Rule::requiredIf(fn () =>
                    !in_array($this->payment_type, ['CHANGE PLAN', 'EDIT', 'EDITLEARNER'])
                ),
            ],

            // ✅ Payment Mode
            'payment_mode' => [
                'nullable',
                Rule::requiredIf(fn () => !$isEditLearner),
                'in:1,2,3',
            ],

            'user_id' => 'nullable|exists:learners,id',
            'learner_id'=>'required|exists:learners,id',

            'discountType' => 'nullable|in:amount,percentage',

            'discount_amount' => [
                'nullable',
                'numeric',
                'min:0',
                function ($attribute,$value,$fail){

                    if(
                        in_array($this->discountType,['amount','percentage'])
                        && ($value === null || $value === '')
                    ){
                        $fail('Discount amount required when discount type selected.');
                    }

                    if(
                        !in_array($this->discountType,['amount','percentage'])
                        && (float) ($value ?? 0) > 0
                    ){
                        $fail('Discount type must be selected.');
                    }

                    if(
                        $this->discountType==='percentage'
                        && (float) ($value ?? 0) > 100
                    ){
                        $fail('Discount percentage cannot exceed 100.');
                    }

                }
            ],
            'locker'=>'nullable|in:yes,no',

            'locker_no'=>[
                'nullable',
                'required_if:locker,yes',
            ],

            'locker_amount'=>'nullable|numeric|min:0',

            'seat_no' => [
                'nullable',
                Rule::when($isReactive, ['numeric']),
            ],
            'learner_detail'=>'nullable',
           
            'payment_type'=>'nullable|in:RENEW,UPGRADE,CHANGE PLAN,EDIT,REACTIVE,EDITLEARNER',
            'previous_pending'=>'nullable|min:0',
            'pending_amount'=>'nullable',
            'due_date'=>[
                'nullable',
                'date',
                Rule::requiredIf(fn () => (int) $this->payment_mode === 3),
            ],
             'diffrence_amount' => [
                    'nullable',
                ],
            'dob'=>'nullable|date',

            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'mobile' => 'nullable|digits:10',
            'father_name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'remark' => 'nullable|string|max:1000',
            'alternate_mobile' => 'nullable|digits:10',
            'exam_id' => 'nullable|exists:exams,id',
            // 1=Aadhar, 2=Driving License, 3=Other, 4=Pan Card, 5=Voter Id (same as learner forms)
            'id_proof_name' => 'nullable|in:1,2,3,4,5',
            'id_proof_number' => 'nullable|string|max:255',

            'profile_picture' => 'nullable|string',
            'profile_picture_image' => 'nullable|file|mimes:jpg,png,jpeg,webp|max:200',
            'id_proof' => 'nullable|file|mimes:jpg,png,jpeg,webp|max:200',
            'id_proof_file' => 'nullable|string',

            'no_expiry' => 'nullable|in:0,1',
            'sended_message_type' => 'nullable|in:whatsapp,text,both,no',

        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            $lockerNo = $this->input('locker_no');

            if ($this->locker === 'yes' && $lockerNo !== null && $lockerNo !== '') {

                $exists = Learner::where('branch_id', getCurrentBranch())
                    ->where('status', 1)
                    ->whereNull('deleted_at')
                    ->where('locker_no', $lockerNo)
                    ->where('id', '!=', $this->learner_id)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add('locker_no', 'This locker number is already assigned to another active learner in this branch.');
                }
            }

        });
    }

}
