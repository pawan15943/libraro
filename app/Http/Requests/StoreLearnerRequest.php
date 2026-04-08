<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Learner;
use App\Models\PlanType;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

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
                function ($attribute, $value, $fail) {

                    $planPrice = (float) $this->input('plan_price_id', 0);
                    $paid = (float) $this->input('paid_amount', 0);
                    $locker = (float) $this->input('locker_amount', 0);
                    $discount = (float) $this->input('discount_amount', 0);

                    $total = $planPrice + $locker - $discount;
                    $pending = $total - $paid;

                    if ($pending > 0 && empty($value)) {
                        $fail('Due date is required if there is pending amount.');
                    }
                }
            ],

        ];

        if ($this->general_seat != 'yes') {
            $rules['seat_no'] = 'required|integer';
        }

        return $rules;
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

        // if ($this->hasFile('id_proof_file')) {
        //      $file = $this->file('id_proof_file');
            
        //     $name = "id_proof_file".time().$file->getClientOriginalName();

        //     $file->move('public/uploade/', $name);

        //     $id_proof_file = 'public/uploade/'.$name;
        // }


      

        if ($this->hasFile('id_proof')) {
  
            $id_proof_file = $this->moveTempFileToPublic(
                $this->file('id_proof'),
                'id_proof_file',
                'uploade/id_proof_file'
            );

        } elseif (!empty($this->id_proof_file) && is_string($this->id_proof_file)) {
            
            $id_proof_file = $this->moveTempFileToPublic(
                $this->id_proof,
                'id_proof_file',
                'uploade/id_proof_file'
            );
        }
        

        $profile_picture = null;

        // if ($this->hasFile('profile_picture')) {

        //     $file = $this->file('profile_picture');
        //     $name = "profile_picture".time().$file->getClientOriginalName();

        //     $file->move('public/uploade/', $name);

        //     $profile_picture = 'public/uploade/'.$name;
        // }
         if ($this->hasFile('profile_picture_image')) {
  
            $profile_picture = $this->moveTempFileToPublic(
                $this->file('profile_picture_image'),
                'profile_picture',
                'uploade/profile_picture'
            );

        } elseif (!empty($this->profile_picture) && is_string($this->profile_picture)) {
            
            $profile_picture = $this->moveTempFileToPublic(
                $this->profile_picture,
                'profile_picture',
                'uploade/profile_picture'
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
        /* ========= CASE 1: FILE (WEB) ========= */
        if ($file instanceof \Illuminate\Http\UploadedFile) {

            $fileName = $filePrefix . '_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

            $destinationFolder = public_path($folder);

            if (!File::exists($destinationFolder)) {
                File::makeDirectory($destinationFolder, 0777, true);
            }

            $file->move($destinationFolder, $fileName);

            return $folder . '/' . $fileName;
        }

        /* ========= CASE 2: STRING (APP URL) ========= */
        if (is_string($file)) {

            $path = parse_url($file, PHP_URL_PATH);

            /* ===== TEMP FILE MOVE ===== */
            if (str_contains($file, '/temp/')) {

                if (str_contains($path, '/storage/')) {
                    $path = substr($path, strpos($path, '/storage/') + 9);
                }

                if (!str_starts_with($path, 'temp/')) {
                    return null;
                }

                $sourcePath = storage_path('app/public/' . $path);

                if (File::exists($sourcePath)) {

                    $fileName = $filePrefix . '_' . time() . '_' . uniqid() . '.' . pathinfo($path, PATHINFO_EXTENSION);

                    $destinationFolder = public_path($folder);

                    if (!File::exists($destinationFolder)) {
                        File::makeDirectory($destinationFolder, 0777, true);
                    }

                    $destinationPath = $destinationFolder . '/' . $fileName;

                    File::move($sourcePath, $destinationPath);

                    return $folder . '/' . $fileName;
                }
                
            }

            /* ===== ALREADY UPLOADED FILE ===== */
            if (str_contains($file, '/uploade/')) {

                $pos = strpos($path, 'uploade/');

                if ($pos !== false) {
                    return substr($path, $pos);
                }
            }
        }

        return null;
    }
}