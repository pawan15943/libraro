<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function getEmailAttribute($value)
    {
        return $this->decryptContactValue($value);
    }

    public function setEmailAttribute($value): void
    {
        $this->attributes['email'] = $this->encryptContactValue($value);
    }

    public function getMobileAttribute($value)
    {
        return $this->decryptContactValue($value);
    }

    public function setMobileAttribute($value): void
    {
        $this->attributes['mobile'] = $this->encryptContactValue($value);
    }

    private function decryptContactValue($value)
    {
        if ($value === null || $value === '') {
            return $value;
        }

        $decrypted = decryptData($value);

        return $decrypted === false ? $value : $decrypted;
    }

    private function encryptContactValue($value)
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return decryptData($value) === false ? encryptData($value) : $value;
    }

     public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id', 'id');
                 
    }
       public function plan()
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function planType()
    {
        return $this->belongsTo(PlanType::class, 'plan_type_id');
    }
}
