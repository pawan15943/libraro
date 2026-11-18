<?php

namespace App\Models\Ordering;

use App\Traits\HasBranch;
use Illuminate\Database\Eloquent\Model;

/** A cafeteria table learners can order to. */
class OrderTable extends Model
{
    use HasBranch;

    protected $guarded = ['id'];

    protected $casts = ['is_active' => 'boolean'];
}
