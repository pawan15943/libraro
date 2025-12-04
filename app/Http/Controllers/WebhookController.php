<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NotificationLog;

class WebhookController extends Controller
{
    public function waba(Request $request)
    {
        $providerId = $request->input('message_id');
        $status = $request->input('status');

        $log = NotificationLog::where('unique_id', $providerId)->first();
        if(!$log) {
            return response('ok');
        }

        $log->delivery_status = json_encode($request->all());
        if($status == 'delivered') $log->message_status = 'delivered';
        elseif($status == 'failed') $log->message_status = 'failed';
        $log->save();

        return response('ok');
    }

    public function sms(Request $request)
    {
        // similar handling for sms providers
        return response('ok');
    }

    public function email(Request $request)
    {
        // similar handling for email providers
        return response('ok');
    }
}
