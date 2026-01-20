<?php

namespace App\Http\Controllers;

use App\Models\LeadContact;
use Illuminate\Http\Request;

class LeadContactController extends Controller
{
    public function index()
    {
        $leads = LeadContact::latest()->get();
        return view('administrator.leads', compact('leads'));
    }

    /* WhatsApp Action */
    public function sendWhatsapp(Request $request, LeadContact $lead)
    {
        $lead->update([
            'is_contact_saved' => true,
            'status' => 'follow_up'
        ]);

        // Static message
        $message = urlencode(
            "Hello {$lead->name}, this is regarding your library listing. Please let us know your interest."
        );

        return response()->json([
            'success' => true,
            'redirect' => "https://wa.me/{$lead->mobile}?text={$message}"
        ]);
    }

    /* Call Status Update */
    public function updateCallStatus(Request $request, LeadContact $lead)
    {
        $request->validate([
            'status' => 'required|in:called,not_answered,busy,follow_up',
            'lead_status' => 'required|in:hot,warm,cold',
        ]);

        $lead->update([
            'status' => $request->status,
            'lead_status' => $request->lead_status,
            'is_contact_saved' => true,
        ]);

        return response()->json(['success' => true]);
    }

    /* Add Follow-up Comment */
    public function addComment(Request $request, LeadContact $lead)
    {
        $request->validate(['comment' => 'required|string']);

        $comments = $lead->comments ?? [];

        $comments[] = [
            'comment' => $request->comment,
            'time' => now()->toDateTimeString()
        ];

        $lead->update(['comments' => $comments]);

        return response()->json(['success' => true]);
    }

    /* View History */
    public function history(LeadContact $lead)
    {
        return view('leads.history', compact('lead'));
    }
}
