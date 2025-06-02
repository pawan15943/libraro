<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Scopes\LibraryScope;
class LibraryTransaction extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'library_transactions';
    protected static function booted()
    {
        
        static::addGlobalScope(new LibraryScope());
    }
   
    public function subscription()
    {
        return $this->belongsTo(Subscription::class, 'subscription', 'id');
    }

    
    
}
