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

    public function rules()
    {
       
        return [

            'plan_start_date' => 'nullable|date',

            'plan_id' => [
                'required',
                Rule::exists('plans','id')->where(fn($q)=>
                    $q->where('library_id',getLibraryId())
                )
            ],

            'plan_type_id' => [
                'required',
                Rule::exists('plan_types','id')->where(fn($q)=>
                    $q->where('branch_id',getCurrentBranch())
                )
            ],

            'plan_price_id' => 'required|numeric|min:0',

            'paid_amount' => 'required|numeric|min:0',

            'payment_mode' => 'required',

            'user_id' => 'required|exists:learners,id',

            'discountType' => 'nullable|in:amount,percentage',

            'discount_amount' => [
                'nullable',
                function ($attribute,$value,$fail){

                    if(
                        in_array($this->discountType,['amount','percentage'])
                        && empty($value)
                    ){
                        $fail('Discount amount required when discount type selected.');
                    }

                    if(
                        !in_array($this->discountType,['amount','percentage'])
                        && $value
                    ){
                        $fail('Discount type must be selected.');
                    }

                }
            ],

            'locker_no'=>[
                'nullable',
                'required_if:locker,yes',
                'numeric'
            ],

            'locker_amount'=>'nullable|numeric|min:0',

            'seat_no'=>'nullable|numeric'

        ];
    }
}