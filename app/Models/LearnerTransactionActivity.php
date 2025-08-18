<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearnerTransactionActivity extends Model
{
    use HasFactory;
     protected $table = 'learner_transaction_activity';
    protected $guarded = [];

     public function learner()
    {
        return $this->belongsTo(Learner::class, 'learner_id')->withTrashed();
    }
     public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id')->withTrashed();
    }
}
