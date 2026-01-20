<?php

namespace App\Http\Controllers;

use App\Models\LeadContact;
use Illuminate\Http\Request;

class LeadContactController extends Controller
{
    public function index(Request $request)
    {
       
        $query = LeadContact::query();

        // Search by name or city
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                ->orWhere('city', 'like', "%{$search}%");
            });
        }

        // Filter by call status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by lead status
        if ($request->filled('lead_status')) {
            $query->where('lead_status', $request->lead_status);
        }

        // Filter by city
        if ($request->filled('city')) {
            $query->where('city', $request->city);
        }

        $leads = $query->latest()->paginate(15);

        // City dropdown data
        $cities = LeadContact::whereNotNull('city')
            ->distinct()
            ->pluck('city');
        $message = "Explore Libraro with our demo account and see how easy managing your library can be.

        Website: https://www.libraro.in

        Demo Access:
        Login: https://www.libraro.in/library/login

        Username: demoaccount@gmail.com
        Password: 123456789

        Plans start at just ₹49/-.
        The demo credentials are provided for evaluation purposes — please do not share them publicly.

        Instagram:
        https://www.instagram.com/libraro.in";

        return view('administrator.leads', compact('leads', 'cities','message'));
        
    }

    public function downloadContact(LeadContact $lead)
    {
        $vcard = "BEGIN:VCARD
        VERSION:3.0
        N:{$lead->name}
        FN:{$lead->name}
        TEL;TYPE=CELL:{$lead->mobile}
        END:VCARD";

        return response($vcard)
            ->header('Content-Type', 'text/vcard')
            ->header('Content-Disposition', 'attachment; filename="contact.vcf"');
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
        $request->validate([
            'comment' => 'required|string'
        ]);

        $comments = $lead->comments ?? [];

        $comments[] = [
            'comment' => $request->comment
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
