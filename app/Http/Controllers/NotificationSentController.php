<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\NotificationChannelSetting;
use App\Models\NotificationTemplate;
use App\Jobs\SendNotificationJob;
use App\Models\NotificationLog;
use Illuminate\Support\Str;

class NotificationSentController extends Controller
{
    public function templates()
    {
        $templates = NotificationTemplate::all();
        return view('admin.notifications.templates', compact('templates'));
    }

    public function manualSend(Request $request)
    {
        $data = $request->validate([
            'learner_id' => 'required|integer',
            'operation_id' => 'required|integer',
            'channels' => 'required|array',
        ]);

        foreach($data['channels'] as $channel){
            $unique = (string) Str::uuid();
            $log = NotificationLog::create([
                'unique_id' => $unique,
                'library_id' => auth()->user()->library_id ?? null,
                'branch_id' => auth()->user()->branch_id ?? null,
                'learner_id' => $data['learner_id'],
                'operation_id' => $data['operation_id'],
                'channel' => $channel,
                'template_id' => $request->input($channel.'_template_id'),
                'message_content' => $request->input('message'),
                'message_status' => 'queued',
            ]);

            SendNotificationJob::dispatch([
                'learner_id' => $data['learner_id'],
                'channel' => $channel,
                'message' => $request->input('message'),
                'operation_id' => $data['operation_id'],
                'library_id' => auth()->user()->library_id ?? null,
                'branch_id' => auth()->user()->branch_id ?? null,
                'template_id' => $request->input($channel.'_template_id'),
                'log_id' => $log->id,
            ]);
        }

        return response()->json(['status' => 'queued']);
    }
}
