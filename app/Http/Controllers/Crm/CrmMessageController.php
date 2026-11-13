<?php

namespace App\Http\Controllers\Crm;

use Illuminate\Http\Request;

/**
 * WhatsApp / SMS reminders open the agent's WhatsApp or messages app with the text
 * filled in (wa.me / sms: links); this endpoint records what was sent on the timeline.
 */
class CrmMessageController extends CrmBaseController
{
    public function store(Request $request, int $id)
    {
        $data = $request->validate([
            'channel' => ['required', 'in:whatsapp,sms'],
            'text' => ['required', 'string', 'max:2000'],
            'template' => ['nullable', 'string', 'max:80'],
        ]);
        $lead = $this->leads->findVisible($this->user(), $id);
        $this->leads->logMessage($lead, $this->user(), $data['channel'], $data['text'], $data['template'] ?? null);

        return response()->json(['ok' => true]);
    }
}
