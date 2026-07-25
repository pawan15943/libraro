@extends('layouts.admin')

@section('content')
<link rel="stylesheet" href="{{ asset('public/css/library-style.css') }}">

<div class="actions mb-4">
    <div class="upper-box">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="mb-3">{{ $branch->display_name ?? $branch->name }} — Plans</h4>
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
        <h5 class="m-0" id="planFormTitle">Add Plan</h5>
        <button type="button" class="btn btn-sm btn-primary" id="togglePlanForm"><i class="fa-solid fa-plus"></i> Add Plan</button>
    </div>

    <form id="planForm" method="POST" action="{{ route('library.branch.plans.save', $branch->id) }}" class="mt-3" style="display:none;">
        @csrf
        <input type="hidden" name="id" id="plan_id_field" value="">
        <div class="row g-4">
            <div class="col-lg-4">
                <label>Type <span>*</span></label>
                <select class="form-select" name="type" id="plan_type" required>
                    <option value="">Select Type</option>
                    <option value="MONTH">MONTH</option>
                    <option value="YEAR">YEAR</option>
                    <option value="DAY">DAY</option>
                    <option value="WEEK">WEEK</option>
                </select>
            </div>
            <div class="col-lg-4">
                <label>Plan (digits) <span>*</span></label>
                <input type="number" min="1" class="form-control" name="plan_id" id="plan_number" required placeholder="Ex: 1 for 1 Month">
            </div>
            <div class="col-lg-4">
                <label>Monthly Days</label>
                <input type="number" min="1" class="form-control" name="monthdays" id="plan_monthdays" placeholder="Leave blank for calendar-wise">
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-primary" id="savePlanBtn">Add Plan</button>
        </div>
    </form>
</div>

@if($branchPlans->isEmpty())
<div class="no-data-found">
    <h4>No plans added for this library yet.</h4>
</div>
@else
<div class="row g-4">
    @foreach($branchPlans as $key => $plan)
    <div class="col-lg-4 col-md-6">
        <div class="planBox">
            <div class="heading d-flex justify-content-between align-items-center">
                <h4 class="m-0">Plan {{ $key + 1 }}</h4>
                @if($plan->deleted_at)
                <span class="inactive text-danger">Inactive</span>
                @else
                <span class="active">Active</span>
                @endif
            </div>
            <div class="plan border-top">
                <ul>
                    <li><span>ID</span><p class="m-0">{{ $plan->id }}</p></li>
                    <li><span>Library ID</span><p class="m-0">{{ $plan->library_id }}</p></li>
                    <li><span>Branch ID</span><p class="m-0">{{ $plan->branch_id ?? '—' }}</p></li>
                    <li><span>Plan Name</span><p class="m-0">{{ $plan->name }}</p></li>
                    <li><span>Type</span><p class="m-0">{{ $plan->type }}</p></li>
                    <li><span>Plan Number</span><p class="m-0">{{ $plan->plan_id }}</p></li>
                    <li><span>Plan Days</span><p class="m-0">{{ $plan->monthdays ?: 'Automatic (Calendar Wise)' }}</p></li>
                    <li><span>Created At</span><p class="m-0">{{ optional($plan->created_at)->format('d-m-Y H:i') ?? '—' }}</p></li>
                    <li><span>Updated At</span><p class="m-0">{{ optional($plan->updated_at)->format('d-m-Y H:i') ?? '—' }}</p></li>
                    <li><span>Deleted At</span><p class="m-0">{{ optional($plan->deleted_at)->format('d-m-Y H:i') ?? '—' }}</p></li>
                </ul>
            </div>
            <ul class="actionalbles">
                <li>
                    <a href="javascript:void(0)" class="plan-edit"
                        data-id="{{ $plan->id }}"
                        data-type="{{ $plan->type }}"
                        data-plan-id="{{ $plan->plan_id }}"
                        data-monthdays="{{ $plan->monthdays }}"
                        title="Edit"><i class="fas fa-edit"></i></a>
                </li>
                <li>
                    <a href="javascript:void(0)" class="plan-delete"
                        data-url="{{ route('library.branch.plans.delete', $plan->id) }}"
                        title="Delete"><i class="fa fa-trash"></i></a>
                </li>
            </ul>
        </div>
    </div>
    @endforeach
</div>
@endif

<script>
document.getElementById('togglePlanForm').addEventListener('click', function () {
    const form = document.getElementById('planForm');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
    document.getElementById('plan_id_field').value = '';
    document.getElementById('planForm').reset();
    document.getElementById('planFormTitle').textContent = 'Add Plan';
    document.getElementById('savePlanBtn').textContent = 'Add Plan';
});

document.querySelectorAll('.plan-edit').forEach(function (link) {
    link.addEventListener('click', function () {
        document.getElementById('planForm').style.display = 'block';
        document.getElementById('plan_id_field').value = this.dataset.id;
        document.getElementById('plan_type').value = this.dataset.type;
        document.getElementById('plan_number').value = this.dataset.planId;
        document.getElementById('plan_monthdays').value = this.dataset.monthdays || '';
        document.getElementById('planFormTitle').textContent = 'Edit Plan';
        document.getElementById('savePlanBtn').textContent = 'Update Plan';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
});

document.querySelectorAll('.plan-delete').forEach(function (link) {
    link.addEventListener('click', function () {
        const url = this.dataset.url;
        Swal.fire({
            title: 'Delete this plan?',
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
                  .then(function () { window.location.reload(); })
                  .catch(function () { Swal.fire('Error', 'Failed to delete plan.', 'error'); });
            }
        });
    });
});
</script>
@endsection
