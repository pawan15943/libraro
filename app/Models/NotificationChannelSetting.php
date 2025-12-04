<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationChannelSetting extends Model
{
    protected $table = 'notification_channel_settings';

    protected $casts = [
        'activity_ids' => 'array',
        'is_waba' => 'boolean',
        'is_text' => 'boolean',
        'is_email' => 'boolean',
    ];
     protected $guarded = []; 

    // protected $fillable = ['branch_id','activity_ids','is_waba','is_text','is_email','waba_template_id','text_template_id','email_template_id'];
}
