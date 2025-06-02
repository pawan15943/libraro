<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Scopes\LibraryScope;
use App\Traits\HasBranch;
use App\Traits\HasSeatType;
class Hour extends Model
{
    use HasFactory,SoftDeletes;
    use HasBranch;
    use HasSeatType;

    protected $guarded = [];
    protected $table = 'hour';
   

}
