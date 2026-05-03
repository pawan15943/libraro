<?php

namespace App\DTO;

use App\Enums\LearnerOperation;

class LearnerOperationDTO
{
    // controller s serive m data clean formate m bhejan
    public function __construct(

        public int $learner_id,

        public ?int $plan_id,

        public ?int $plan_type_id,

        public ?float $plan_price,

        public ?int $seat_no,

        public ?float $locker_amount,

        public ?int $locker_no,

        public ?float $paid_amount,

        public ?string $discount_type,

        public ?float $discount_amount,

        public ?int $payment_mode,

        public ?string $start_date,

        public ?string $due_date,

        public ?string $paid_date,

        public ?int $branch_id,

        public ?int $library_id,

        public string $operation ,
        public ?float $diffrence_amount,
        // ✅ ADD THESE (VERY IMPORTANT)
    public ?string $name = null,
    public ?string $email = null,
    public ?string $mobile = null,
    public ?string $dob = null,
    public ?string $father_name = null,
    public ?string $address = null,
    public ?string $remark = null,
    public ?string $alternate_mobile = null,
    /** Document type id: 1=Aadhar, 2=Driving License, 3=Other, 4=Pan, 5=Voter Id */
    public ?int $id_proof_name = null,
    public ?string $id_proof_number = null,

    /** Uploaded file (e.g. profile_picture_image) or temp/storage URL string (profile_picture) */
    public mixed $profile_picture = null,
    /** Uploaded file (id_proof) or temp/storage URL string (id_proof_file) */
    public mixed $id_proof_file = null,

    /** 0 or 1 — matches learners.no_expiry (see seat-book / StoreLearnerRequest) */
    public ?int $no_expiry = null,
    /** whatsapp | text | both | no */
    public ?string $sended_message_type = null,

    /** yes | no from payload, used to know when locker is explicitly removed */
    public ?string $locker = null,
    /** True when locker key was sent in the payload */
    public bool $locker_present = false,
    /** True when locker_no key was sent in the payload, even if blank/null */
    public bool $locker_no_present = false,
    /** True when locker_amount key was sent in the payload, even if blank/null */
    public bool $locker_amount_present = false,

    /** True when discountType key was sent in the payload, even if blank/null */
    public bool $discount_type_present = false,
    /** True when discount_amount key was sent in the payload, even if blank/null */
    public bool $discount_amount_present = false,

    ) {}


    public static function fromRequest($request)
    {
        $nullableInt = fn ($value) => $value !== null && $value !== '' ? (int) $value : null;
        $nullableFloat = fn ($value) => $value !== null && $value !== '' ? (float) $value : null;
       
        return new self(

            learner_id:$request->learner_id,
            plan_id:$nullableInt($request->plan_id),
            plan_type_id:$nullableInt($request->plan_type_id),
            plan_price:$nullableFloat($request->plan_price_id),

            seat_no:$nullableInt($request->seat_no),

            paid_amount:$nullableFloat($request->paid_amount),

            locker_amount:$nullableFloat($request->locker_amount),
            locker_no:$nullableInt($request->locker_no),

            discount_type:$request->discountType,
            discount_amount:$nullableFloat($request->discount_amount),
            diffrence_amount:$nullableFloat($request->diffrence_amount),

            payment_mode:$nullableInt($request->payment_mode),

            start_date:$request->plan_start_date,

            due_date:$request->due_date,
            paid_date:$request->paid_date,

            branch_id:getCurrentBranch(),
            library_id:getLibraryId(),

            operation:$request->payment_type ?? 'RENEW',
                // ✅ OPTIONAL FIELDS
            name: $request->name ?? null,
            email: $request->email ?? null,
            mobile: $request->mobile ?? null,
            dob: $request->dob ?? null,
            father_name: $request->father_name ?? null,
            address: $request->address ?? null,
            remark: $request->remark ?? null,
            alternate_mobile: $request->alternate_mobile ?? null,
            id_proof_name: $request->input('id_proof_name') !== null && $request->input('id_proof_name') !== ''
                ? (int) $request->input('id_proof_name')
                : null,
            id_proof_number: $request->id_proof_number ?? null,

            profile_picture: $request->hasFile('profile_picture_image')
                ? $request->file('profile_picture_image')
                : ($request->profile_picture ?? null),
            id_proof_file: $request->hasFile('id_proof')
                ? $request->file('id_proof')
                : ($request->id_proof_file ?? null),

            no_expiry: $request->input('no_expiry') !== null && $request->input('no_expiry') !== ''
                ? (int) $request->input('no_expiry')
                : null,
            sended_message_type: $request->input('sended_message_type') !== null && $request->input('sended_message_type') !== ''
                ? (string) $request->input('sended_message_type')
                : null,
            locker: $request->locker ?? null,
            locker_present: $request->exists('locker'),
            locker_no_present: $request->exists('locker_no'),
            locker_amount_present: $request->exists('locker_amount'),
            discount_type_present: $request->exists('discountType'),
            discount_amount_present: $request->exists('discount_amount'),

        );
    }

}
