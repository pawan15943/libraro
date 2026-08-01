<?php

namespace App\Http\Requests;

/**
 * Learner self-service renewal — reuses LearnerOperationRequest's exact
 * validation rules (same fields as the staff "library/learners/operation"
 * RENEW flow), but forces learner_id/payment_type from the authenticated
 * learner_api user so a learner can never renew another learner's seat.
 */
class LearnerRenewRequest extends LearnerOperationRequest
{
    protected function prepareForValidation()
    {
        parent::prepareForValidation();

        $this->merge([
            'learner_id'   => auth('learner_api')->id(),
            'payment_type' => 'RENEW',
        ]);
    }
}
