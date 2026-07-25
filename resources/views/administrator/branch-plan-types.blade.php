@extends('layouts.admin')

@section('content')
<link rel="stylesheet" href="{{ asset('public/css/library-style.css') }}">

<div class="actions mb-4">
    <div class="upper-box">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="mb-3">{{ $branch->display_name ?? $branch->name }} — Plan Types / Shifts</h4>
            <a href="javascript:void(0);" class="go-back" onclick="window.history.back();">Go Back <i class="fa-solid fa-backward pl-2"></i></a>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if($errors->any())
<div class="alert alert-danger">
    <ul class="m-0">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="card p-3 mb-4">
    <div class="d-flex justify-content-between align-items-center">
        <h5 class="m-0" id="planTypeFormTitle">Add Plan Type</h5>
        <button type="button" class="btn btn-sm btn-primary" id="togglePlanTypeForm"><i class="fa-solid fa-plus"></i> Add Plan Type</button>
    </div>

    <form id="planTypeForm" method="POST" action="{{ route('library.branch.plantypes.save', $branch->id) }}" class="mt-3" style="display:none;">
        @csrf
        <input type="hidden" name="id" id="plantype_id_field" value="">
        <div class="row g-4">
            <div class="col-lg-3">
                <label>Shift Name <span>*</span></label>
                <input type="text" class="form-control" name="name" id="plantype_name" required placeholder="Ex: Full Day, Morning Shift">
            </div>
            <div class="col-lg-3">
                <label>Start Time <span>*</span></label>
                <input type="text" id="plantype_start_time" class="form-control" name="start_time" required placeholder="Select start time" style="pointer-events:all;">
            </div>
            <div class="col-lg-3">
                <label>End Time <span>*</span></label>
                <input type="text" id="plantype_end_time" class="form-control" name="end_time" required placeholder="Select end time" style="pointer-events:all;">
            </div>
            <div class="col-lg-3">
                <label>Slot Duration (hrs) <span>*</span></label>
                <input type="number" min="1" max="24" id="plantype_slot_hours" class="form-control" name="slot_hours" required readonly>
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-primary" id="savePlanTypeBtn">Add Plan Type</button>
        </div>
    </form>
</div>
 

@if($branchPlanTypes->isEmpty())
<div class="no-data-found">
    <h4>No plan types added for this branch yet.</h4>
</div>
@else
<div class="row g-4">
  
    @foreach($branchPlanTypes as $key => $planType)
    <div class="col-lg-4 col-md-6">
        <div class="planBox">
            <div class="heading d-flex justify-content-between align-items-center">
                <h4 class="m-0">Shift {{ $key + 1 }}</h4>
                @if($planType->deleted_at)
                <span class="inactive text-danger">Inactive</span>
                @else
                <span class="active">Active</span>
                @endif
            </div>
            <div class="plan border-top">
                <ul>
                    <li><span>ID</span><p class="m-0">{{ $planType->id }}</p></li>
                    <li><span>Library ID</span><p class="m-0">{{ $planType->library_id }}</p></li>
                    <li><span>Branch ID</span><p class="m-0">{{ $planType->branch_id }}</p></li>
                    <li><span>Shift Name</span><p class="m-0">{{ $planType->name }}</p></li>
                    <li><span>Day Type ID</span><p class="m-0">{{ $planType->day_type_id ?? '—' }}</p></li>
                    <li><span>Shift Hrs</span><p class="m-0">{{ $planType->slot_hours }}</p></li>
                    <li><span>Start Time</span><p class="m-0">{{ $planType->start_time }}</p></li>
                    <li><span>End Time</span><p class="m-0">{{ $planType->end_time }}</p></li>
                    <li><span>Active Learners</span><p class="m-0">{{ $planType->active_learners_count ?? 0 }}</p></li>
                    <li><span>Created At</span><p class="m-0">{{ optional($planType->created_at)->format('d-m-Y H:i') ?? '—' }}</p></li>
                    <li><span>Updated At</span><p class="m-0">{{ optional($planType->updated_at)->format('d-m-Y H:i') ?? '—' }}</p></li>
                    <li><span>Deleted At</span><p class="m-0">{{ optional($planType->deleted_at)->format('d-m-Y H:i') ?? '—' }}</p></li>
                </ul>
            </div>

            @if(($planType->active_learners_count ?? 0) > 0)
            <div class="text-center py-3 border-top text-danger">
                Active learners assigned — view only
            </div>
            @else
            <ul class="actionalbles">
                <li>
                    <a href="javascript:void(0)" class="plantype-edit"
                        data-id="{{ $planType->id }}"
                        data-name="{{ $planType->name }}"
                        data-start-time="{{ $planType->start_time }}"
                        data-end-time="{{ $planType->end_time }}"
                        data-slot-hours="{{ $planType->slot_hours }}"
                        title="Edit"><i class="fas fa-edit"></i></a>
                </li>
                <li>
                    <a href="javascript:void(0)" class="plantype-delete"
                        data-url="{{ route('library.branch.plantypes.delete', $planType->id) }}"
                        title="Delete"><i class="fa fa-trash"></i></a>
                </li>
            </ul>
            @endif
        </div>
    </div>
    @endforeach
</div>
@endif

<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
flatpickr("#plantype_start_time", {
    enableTime: true, noCalendar: true, dateFormat: "H:i", time_24hr: true,
    onChange: calculateSlotHours
});
flatpickr("#plantype_end_time", {
    enableTime: true, noCalendar: true, dateFormat: "H:i", time_24hr: true,
    onChange: calculateSlotHours
});

function calculateSlotHours() {
    const startTime = document.getElementById('plantype_start_time').value;
    const endTime = document.getElementById('plantype_end_time').value;
    if (!startTime || !endTime) return;

    const start = new Date("1970-01-01T" + startTime + ":00Z");
    const end = new Date("1970-01-01T" + endTime + ":00Z");
    let diffInMinutes = (end - start) / 1000 / 60;

    if (diffInMinutes === 0) {
        document.getElementById('plantype_slot_hours').value = 24;
    } else {
        if (diffInMinutes < 0) diffInMinutes += 24 * 60;
        document.getElementById('plantype_slot_hours').value = Math.floor(diffInMinutes / 60);
    }
}

function resetPlanTypeForm() {
    document.getElementById('plantype_id_field').value = '';
    document.getElementById('planTypeForm').reset();
    document.getElementById('planTypeFormTitle').textContent = 'Add Plan Type';
    document.getElementById('savePlanTypeBtn').textContent = 'Add Plan Type';
}

document.getElementById('togglePlanTypeForm').addEventListener('click', function () {
    const form = document.getElementById('planTypeForm');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
    resetPlanTypeForm();
});

document.querySelectorAll('.plantype-edit').forEach(function (link) {
    link.addEventListener('click', function () {
        document.getElementById('planTypeForm').style.display = 'block';
        document.getElementById('plantype_id_field').value = this.dataset.id;
        document.getElementById('plantype_name').value = this.dataset.name;
        document.getElementById('plantype_start_time').value = this.dataset.startTime;
        document.getElementById('plantype_end_time').value = this.dataset.endTime;
        document.getElementById('plantype_slot_hours').value = this.dataset.slotHours;
        document.getElementById('planTypeFormTitle').textContent = 'Edit Plan Type';
        document.getElementById('savePlanTypeBtn').textContent = 'Update Plan Type';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
});

document.querySelectorAll('.plantype-delete').forEach(function (link) {
    link.addEventListener('click', function () {
        const url = this.dataset.url;
        Swal.fire({
            title: 'Delete this plan type?',
            text: 'This cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete'
        }).then(function (result) {
            if (result.isConfirmed) {
                fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    }
                }).then(function (res) { return res.json(); })
                  .then(function (data) {
                      if (data.status === false) {
                          Swal.fire('Cannot delete', data.message, 'error');
                      } else {
                          window.location.reload();
                      }
                  })
                  .catch(function () { Swal.fire('Error', 'Failed to delete plan type.', 'error'); });
            }
        });
    });
});
</script>
@endsection
