<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasBranch;
use App\Models\Scopes\LibraryScope;
class LearnerOperationsLog extends Model
{
    use HasFactory;
    use HasBranch;
    protected $guarded = [];
    protected $table = 'learner_operations_log';
      protected static function booted()
    {
        
        static::addGlobalScope(new LibraryScope());
    }
    public function learner()
    {
        return $this->belongsTo(Learner::class, 'learner_id');
    }
}
