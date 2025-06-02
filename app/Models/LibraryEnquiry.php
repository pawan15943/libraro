<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LibraryEnquiry extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table = 'library_enquiry';
    // protected $fillable = ['name', 'mobile', 'library_id', 'shift_time', 'enquiry'];

    // LibraryEnquiry belongs to a Library
    public function library()
    {
        return $this->belongsTo(Library::class);
    }

    // LibraryEnquiry belongs to a PlanType (Shift Time)
    public function planType()
    {
        return $this->belongsTo(PlanType::class, 'shift_time');
    }
}
