@extends('layouts.admin')
@section('content')
<div class="modal fade" id="commentModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Update Lead</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                <input type="hidden" id="lead_id">

                <!-- Lead Status -->
                <div class="mb-3">
                    <label class="form-label">Lead Status</label>
                    <select id="lead_status" class="form-select select2">
                        <option value="">Select</option>
                        <option value="hot">Hot</option>
                        <option value="warm">Warm</option>
                        <option value="cold">Cold</option>
                    </select>
                </div>

                <!-- Call Status -->
                <div class="mb-3">
                    <label class="form-label">Call Status</label>
                    <select id="status" class="form-select select2">
                        <option value="">Select</option>
                        @foreach($lead_status as $key => $value)
                            <option value="{{$value}}">{{$value}}</option>
                        @endforeach
                    
                    </select>
                </div>

                <!-- Comment -->
                <div class="mb-3">
                    <label class="form-label">Follow-up Comment</label>
                    <textarea id="comment_text"
                              class="form-control"
                              rows="3"
                              placeholder="Enter comment (optional)"></textarea>
                </div>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" onclick="saveComment()">Save</button>
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
                           
                            {{-- <a href="https://wa.me/91{{ $lead->mobile }}?text={{ urlencode($message) }}"
                            target="_blank"
                            class="btn btn-success btn-sm">
                            <i class="fab fa-whatsapp"></i>
                            </a> --}}
                            <a href="{{ route('leads.saveContact', $lead->id) }}"
                            onclick="openWhatsapp('{{ $lead->mobile }}','{{ urlencode($message) }}')"
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
function openWhatsapp(mobile, message) {
    setTimeout(() => {
        window.open(
            `https://wa.me/91${mobile}?text=${message}`,
            '_blank'
        );
    }, 1500); // gives time to tap "Save"
}



function startCall(mobile) {
    setTimeout(() => {
        window.location.href = 'tel:' + mobile;
    }, 1500); // gives time to save contact
}

function openCommentModal(leadId, leadStatus = '', callStatus = '') {
    document.getElementById('lead_id').value = leadId;
    document.getElementById('comment_text').value = '';

    $('#lead_status').val(leadStatus).trigger('change');
    $('#status').val(callStatus).trigger('change');

    let modal = new bootstrap.Modal(document.getElementById('commentModal'));
    modal.show();
}

function saveComment() {
    let leadId = $('#lead_id').val();

    let data = {
        comment: $('#comment_text').val().trim(),
        lead_status: $('#lead_status').val(),
        status: $('#status').val()
    };

    let url = "{{ route('leads.comment', ':id') }}";
    url = url.replace(':id', leadId);

    $.ajax({
        url: url,
        type: 'POST',
        data: data, // jQuery handles form encoding
        dataType: 'json',
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (response) {
            if (response.success) {
                $('#commentModal').modal('hide');
                
                Swal.fire({
                    icon: 'success',
                    title: 'Saved!',
                    text: 'Comment updated successfully',
                    timer: 1500,
                    showConfirmButton: false
                });

                setTimeout(() => {
                    location.reload();
                }, 1600);
            }
        },
        error: function (xhr) {
            console.error(xhr.responseText);
            alert('Error saving comment');
        }
    });
}

// init select2 when modal opens
$('#commentModal').on('shown.bs.modal', function () {
    $('.select2').select2({
        dropdownParent: $('#commentModal'),
        width: '100%'
    });
});
</script>


@endsection
