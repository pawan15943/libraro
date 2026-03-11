<?php

namespace App\DTO;

use App\Enums\LearnerOperation;

class LearnerOperationDTO
{
    // controller s serive m data clean formate m bhejan
    public function __construct(

        public int $learner_id,

        public int $plan_id,

        public int $plan_type_id,

        public float $plan_price,

        public ?int $seat_no,

        public ?float $locker_amount,

        public ?int $locker_no,

        public float $paid_amount,

        public ?string $discount_type,

        public ?float $discount_amount,

        public int $payment_mode,

        public ?string $start_date,

        public ?string $due_date,

        public ?string $paid_date,

        public int $branch_id,

        public int $library_id,

        public string $operation 

    ) {}


   public static function fromRequest($request)
    {
        return new self(

            learner_id:$request->user_id,
            plan_id:$request->plan_id,
            plan_type_id:$request->plan_type_id,
            plan_price:(float)$request->plan_price_id,

            seat_no:$request->seat_no,

            paid_amount:(float)$request->paid_amount,

            locker_amount:$request->locker_amount,
            locker_no:$request->locker_no,

            discount_type:$request->discountType,
            discount_amount:$request->discount_amount,

            payment_mode:$request->payment_mode,

            start_date:$request->plan_start_date,

            due_date:$request->due_date,
            paid_date:$request->paid_date,

            branch_id:getCurrentBranch(),
            library_id:getLibraryId(),

            operation:$request->payment_type ?? 'RENEW'

        );
    }

}