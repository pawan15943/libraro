<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationSubscription extends Model
{
    protected $table = 'notification_subscription';
     protected $guarded = []; 

    // protected $fillable = ['library_id','waba_amount','text_amount','email_amount','waba_start_date','waba_end_date','text_start_date','text_end_date','email_start_date','email_end_date','total_paid_amount'];
}
