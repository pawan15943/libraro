<?php

namespace App\Http\Controllers;

use App\Models\LeadContact;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class LeadContactController extends Controller
{
    public function index(Request $request)
    {
       
       if ($request->ajax()) {

            $query = LeadContact::select(
                'id',
                'library_name',
                'mobile',
                'city',
                'lead_status',
                'status',
                'comments',
                'created_at'
            )->orderByDesc('created_at');

            // Global search
            if (!empty($request->search['value'])) {
                $search = $request->search['value'];
                $query->where(function ($q) use ($search) {
                    $q->where('library_name', 'like', "%$search%")
                      ->orWhere('mobile', 'like', "%$search%")
                      ->orWhere('city', 'like', "%$search%");
                });
            }

            // Filters
            if ($request->filled('filter_status')) {
                $query->where('status', $request->filter_status);
            }

            if ($request->filled('filter_lead_status')) {
                $query->where('lead_status', $request->filter_lead_status);
            }

            if ($request->filled('filter_city')) {
                $query->where('city', $request->filter_city);
            }

            return DataTables::of($query)
                ->addIndexColumn()

                ->addColumn('latest_comment', function ($row) {
                    if (empty($row->comments)) return '-';
                    $last = end($row->comments);
                    return $last['comment'] ?? '-';
                })

                ->addColumn('status_display', function ($row) {
                    return '<span class="badge bg-info">'
                        . ucfirst($row->lead_status)
                        . '</span><br><small>'
                        . ($row->status ?? '')
                        . '</small>';
                })

                ->addColumn('action', function ($row) {

                $btn = '
                
                    

                        <a href="javascript:void(0)"
                        onclick="startCall('.$row->id.', \''.$row->mobile.'\')"
                        class="dropdown-item"
                        title="Call">
                            <i class="fas fa-phone"></i> 
                        </a>

                        <a href="javascript:void(0)"
                        onclick="sendWhatsapp('.$row->id.', \''.$row->mobile.'\')"
                        class="dropdown-item"
                        title="WhatsApp">
                            <i class="fab fa-whatsapp"></i> 
                        </a>

                        <a href="javascript:void(0)"
                        onclick="openCommentModal(
                                '.$row->id.',
                                \''.$row->lead_status.'\',
                                \''.$row->status.'\'
                            )"
                        class="dropdown-item"
                        title="Update Lead">
                            <i class="fas fa-edit"></i> 
                        </a>

                    
                ';

                return $btn;
            })

                ->rawColumns(['status_display','action'])
                ->make(true);
        }

        $cities = LeadContact::whereNotNull('city')->distinct()->pluck('city');
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

        return view('administrator.leads', compact( 'cities','message','lead_status'));
        
    }
    

   /* ADD LEAD (AJAX) */
    public function store(Request $request)
    {
        $request->validate([
            'library_name' => 'required',
            'mobile' => 'required|digits:10|unique:lead_contacts,mobile',
            'city' => 'required',
            'lead_status' => 'required'
        ]);

        LeadContact::create($request->all());

        return response()->json(['success' => true]);
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
