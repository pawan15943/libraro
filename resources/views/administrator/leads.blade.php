@extends('layouts.admin')
@section('content')
<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header">
            <h5>Library Leads</h5>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Mobile</th>
                    <th>City</th>
                    <th>Status</th>
                    <th>Latest Comment</th>
                    <th>Action</th>
                </tr>
                </thead>

                <tbody>
                @foreach($leads as $lead)
                    <tr>
                        <td>{{ $lead->name }}</td>

                        <td>
                            <a href="tel:{{ $lead->mobile }}">
                                {{ $lead->mobile }}
                            </a>
                        </td>

                        <td>{{ $lead->city }}</td>

                        <td>
                            <span class="badge bg-info">
                                {{ ucfirst($lead->lead_status) }}
                            </span><br>
                            <small>{{ $lead->status }}</small>
                        </td>

                        <td>
                            {{ $lead->latest_comment['comment'] ?? '-' }}
                        </td>

                        <td class="text-nowrap">
                            <button onclick="sendWhatsapp({{ $lead->id }})" class="btn btn-success btn-sm">
                                WhatsApp
                            </button>

                            <button onclick="openCallModal({{ $lead->id }})" class="btn btn-primary btn-sm">
                                Call
                            </button>

                            <a href="{{ route('leads.history',$lead) }}" class="btn btn-secondary btn-sm">
                                History
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>

            </table>
        </div>
    </div>
</div>

<script>
function sendWhatsapp(id) {
    fetch(`/leads/whatsapp/${id}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        }
    })
    .then(res => res.json())
    .then(data => {
        if (data.redirect) {
            window.open(data.redirect, '_blank');
            location.reload();
        }
    });
}

function openCallModal(id) {
    let status = prompt("Enter call status: called, not_answered, busy, follow_up");
    let lead = prompt("Lead status: hot, warm, cold");

    fetch(`/leads/call/${id}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            status: status,
            lead_status: lead
        })
    }).then(() => location.reload());
}
</script>

@endsection
