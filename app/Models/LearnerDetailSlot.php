<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * An extra shift (seat + shift + price) of a booking. Dates, status, freeze and payment
 * live on the parent learner_detail row. See docs/ADVANCED_SHIFTS.md.
 */
class LearnerDetailSlot extends Model
{
    use SoftDeletes;

    protected $table = 'learner_detail_slots';
    protected $guarded = [];

    public function learnerDetail()
    {
        return $this->belongsTo(LearnerDetail::class, 'learner_detail_id');
    }

    public function planType()
    {
        return $this->belongsTo(PlanType::class, 'plan_type_id')->withoutGlobalScopes()->withTrashed();
    }
}
