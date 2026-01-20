@extends('layouts.admin')
@section('content')
<div class="modal fade" id="commentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Add Follow-up Comment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="lead_id">

                <textarea id="comment_text"
                          class="form-control"
                          rows="4"
                          placeholder="Enter follow-up comment..."></textarea>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" onclick="saveComment()">Save Comment</button>
            </div>

        </div>
    </div>
</div>


<div class="container-fluid">
    <div class="card shadow-sm">
        <div class="card-header">
            <h5>Library Leads</h5>
            <form method="GET" action="{{ route('leads.index') }}" class="mb-3">
                <div class="row g-2 align-items-end">

                    <!-- Search -->
                    <div class="col-md-4 col-12">
                        <label class="form-label">Search</label>
                        <input type="text"
                            name="search"
                            class="form-control"
                            placeholder="Library name or city"
                            value="{{ request('search') }}">
                    </div>

                    <!-- Call Status -->
                    <div class="col-md-2 col-6">
                        <label class="form-label">Call Status</label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            @foreach(['called','not_answered','busy','follow_up'] as $status)
                                <option value="{{ $status }}"
                                    {{ request('status') == $status ? 'selected' : '' }}>
                                    {{ ucfirst(str_replace('_',' ',$status)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Lead Status -->
                    <div class="col-md-2 col-6">
                        <label class="form-label">Lead Status</label>
                        <select name="lead_status" class="form-select">
                            <option value="">All</option>
                            @foreach(['hot','warm','cold'] as $lead)
                                <option value="{{ $lead }}"
                                    {{ request('lead_status') == $lead ? 'selected' : '' }}>
                                    {{ ucfirst($lead) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- City -->
                    <div class="col-md-2 col-6">
                        <label class="form-label">City</label>
                        <select name="city" class="form-select">
                            <option value="">All</option>
                            @foreach($cities as $city)
                                <option value="{{ $city }}"
                                    {{ request('city') == $city ? 'selected' : '' }}>
                                    {{ $city }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Buttons -->
                    <div class="col-md-2 col-6 d-flex gap-2">
                        <button class="btn btn-primary w-100">Filter</button>
                        <a href="{{ route('leads.index') }}" class="btn btn-outline-secondary w-100">
                            Reset
                        </a>
                    </div>

                </div>
            </form>


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
                        <td>{{ $lead->library_name ?? '' }}</td>

                        <td>
                            <a href="tel:{{ $lead->mobile }}">
                                {{ $lead->mobile }}
                            </a>
                        </td>

                        <td>{{ $lead->city ?? '' }}</td>

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
                           
                            <a href="https://wa.me/91{{ $lead->mobile }}?text={{ urlencode($message) }}"
                            target="_blank"
                            class="btn btn-success btn-sm">
                            <i class="fab fa-whatsapp"></i>
                            </a>
                        
                            <a href="{{ route('leads.saveContact', $lead->id) }}"
                                class="btn btn-primary btn-sm"
                                onclick="startCall('{{ $lead->mobile }}')">
                                <i class="fa-solid fa-phone"></i>
                            </a>

                        
                            <button class="btn btn-secondary btn-sm"
                                    onclick="openCommentModal({{ $lead->id }})">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                            </button>
                           
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

function startCall(mobile) {
    setTimeout(() => {
        window.location.href = 'tel:' + mobile;
    }, 1500); // gives time to save contact
}

function openCommentModal(leadId) {
    document.getElementById('lead_id').value = leadId;
    document.getElementById('comment_text').value = '';

    let modal = new bootstrap.Modal(document.getElementById('commentModal'));
    modal.show();
}

function saveComment() {
    let leadId = document.getElementById('lead_id').value;
    let comment = document.getElementById('comment_text').value.trim();

    if (!comment) {
        alert('Please enter a comment');
        return;
    }

    fetch(`/leads/comment/${leadId}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ comment: comment })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(
                document.getElementById('commentModal')
            ).hide();

            location.reload(); // OR update row dynamically
        }
    });
}
</script>


@endsection
