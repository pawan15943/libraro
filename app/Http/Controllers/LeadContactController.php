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

        Instagram:https://www.instagram.com/libraro.in";

        $lead_status=['Interested','Not interested','Middium interested','Discontinued','Language barrier','Future Lead','JUNK','Registerd','Other software','Excel sufficient','Manuel Sufficient','Call disconnect','called','No response','busy','follow_up','Call later','Switch off','Not Reachable','Will think and decide','DNP','Fee issue'];

        return view('administrator.leads', compact('leads', 'cities','message','lead_status'));
        
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
   public function saveContact(LeadContact $lead)
    {
        // mark saved in DB
        if (!$lead->is_contact_saved) {
            $lead->update(['is_contact_saved' => true]);
        }

        $vcard = "BEGIN:VCARD
            VERSION:3.0
            N:{$lead->name}
            FN:{$lead->name}
            TEL;TYPE=CELL:{$lead->mobile}
            END:VCARD";

        return response($vcard)
            ->header('Content-Type', 'text/vcard')
            ->header('Content-Disposition', 'attachment; filename="{$lead->name}.vcf"');
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
        $data = [];

        // ✅ COMMENT (optional)
        if ($request->filled('comment')) {

            // Always copy to local variable
            $comments = $lead->comments;

            // Ensure array
            if (!is_array($comments)) {
                $comments = [];
            }

            // Append new comment
            $comments[] = [
                'comment' => $request->comment,
                'time'    => now()->toDateTimeString(),
            ];

            // Assign back
            $data['comments'] = $comments;
        }

        // ✅ LEAD STATUS (optional)
        if ($request->filled('lead_status')) {
            $data['lead_status'] = $request->lead_status;
        }

        // ✅ CALL STATUS (optional)
        if ($request->filled('status')) {
            $data['status'] = $request->status;
        }

        // ✅ Update only if something changed
        if (!empty($data)) {
            $lead->update($data);
        }

        return response()->json([
            'success' => true
        ]);
    }


    /* View History */
    public function history(LeadContact $lead)
    {
        return view('leads.history', compact('lead'));
    }
}
