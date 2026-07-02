<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Learner;
use App\Models\PlanType;
use Exception;

class LearnerOperationRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $data = [];

        if ($this->exists('discountType')) {
            $discountType = is_string($this->discountType) ? trim($this->discountType) : $this->discountType;
            $data['discountType'] = $discountType === '' ? null : $discountType;
        }

        if ($this->exists('discount_amount')) {
            $discountAmount = is_string($this->discount_amount) ? trim($this->discount_amount) : $this->discount_amount;
            $data['discount_amount'] = $discountAmount === '' && $this->filled('discountType') ? 0 : ($discountAmount === '' ? null : $discountAmount);
        }

        if ($this->exists('diffrence_amount')) {
            $differenceAmount = is_string($this->diffrence_amount) ? trim($this->diffrence_amount) : $this->diffrence_amount;
            $data['diffrence_amount'] = $differenceAmount === '' ? 0 : $differenceAmount;
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

    public function rules()
    {
         
        $isEditLearner = $this->payment_type === 'EDITLEARNER';
        $isEditOperation = $this->payment_type === 'EDIT';
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

                }
            ],
            'locker'=>'nullable|in:yes,no',

            'locker_no'=>[
                'nullable',
                'required_if:locker,yes',
                'numeric'
            ],

            'locker_amount'=>'nullable|numeric|min:0',

            'seat_no'=>'nullable|numeric',
            'learner_detail'=>'nullable',
           
            'payment_type'=>'nullable',
            'previous_pending'=>'nullable|min:0',
            'pending_amount'=>'nullable',
            'due_date'=>'nullable',
             'diffrence_amount' => [
                    'nullable',
                    'numeric',
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
            'id_proof_name' => 'nullable|integer|in:1,2,3,4,5',
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
            $paymentMode = (int) $this->input('payment_mode');
            if ($paymentMode !== 3) {
                return;
            }

            $pendingAmount = $this->input('pending_amount');
            if ($pendingAmount !== null && $pendingAmount !== '' && (float) $pendingAmount !== 0.0) {
                $validator->errors()->add('pending_amount', 'For Pay Later, do not pass pending amount in request.');
            }
        });
    }
}
