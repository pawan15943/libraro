<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Learner;
use App\Models\PlanType;
use App\Services\FileUploadService;

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
            'id_proof' => 'nullable|file|mimes:jpg,png,jpeg,webp|max:200',
            'id_proof_file' => 'nullable|string',
            "id_proof_number"=>'nullable',
            'profile_picture_image' => 'nullable|file|mimes:jpg,png,jpeg,webp|max:200',
            'profile_picture' => 'nullable|string',

            

            'exam_id' => 'nullable|exists:exams,id',

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

                    $total = $planPrice + $locker - $discount;

                    return ($total - $paid) > 0;
                }),
            ],

        ];

        if ($this->general_seat != 'yes') {
            $rules['seat_no'] = 'required|integer';
        }

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

    public function prepareData()
    {


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
            'exam_id'=>$this->exam_id
        ];
    }


    function moveTempFileToPublic($file, $filePrefix = 'file', $folder)
    {
        return app(FileUploadService::class)->moveTempFileToPublic($file, $filePrefix, $folder);
    }
}
