@extends('layouts.admin')

@section('content')
<link rel="stylesheet" href="{{ asset('public/css/library-style.css') }}">

<div class="actions mb-4">
    <div class="upper-box">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="mb-3">{{ $branch->display_name ?? $branch->name }} — Plan Prices</h4>
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
        <h5 class="m-0" id="priceFormTitle">Add Plan Price</h5>
        <button type="button" class="btn btn-sm btn-primary" id="togglePriceForm"><i class="fa-solid fa-plus"></i> Add Plan Price</button>
    </div>

    <form id="priceForm" method="POST" action="{{ route('library.branch.prices.save', $branch->id) }}" class="mt-3" style="display:none;">
        @csrf
        <input type="hidden" name="id" id="price_id_field" value="">
        <div class="row g-4">
            <div class="col-lg-4">
                <label>Plan <span>*</span></label>
                <select class="form-select" name="plan_id" id="price_plan_id" required>
                    <option value="">Select Plan</option>
                    @foreach($branchPlansForSelect as $plan)
                    <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-4">
                <label>Plan Type / Shift <span>*</span></label>
                <select class="form-select" name="plan_type_id" id="price_plan_type_id" required>
                    <option value="">Select Plan Type</option>
                    @foreach($branchPlanTypesForSelect as $planType)
                    <option value="{{ $planType->id }}">{{ $planType->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-4">
                <label>Price <span>*</span></label>
                <input type="number" min="0" step="0.01" class="form-control" name="price" id="price_amount" required>
            </div>
        </div>
        <div class="mt-3">
            <button type="submit" class="btn btn-primary" id="savePriceBtn">Add Plan Price</button>
        </div>
    </form>
</div>

@if($prices->isEmpty())
<div class="no-data-found">
    <h4>No plan prices added for this branch yet.</h4>
</div>
@else
<div class="row g-4 mb-4">
    @foreach($prices as $key => $price)
    @php
        $isInactive = (bool) $price->deleted_at;
        $planDuration = optional($price->plan)->name ?? '1 MONTH';
        $shiftName = optional($price->planType)->name ?? '—';
        $priceFormatted = number_format((float)($price->price ?? 0));
    @endphp
    <div class="col-lg-4 col-md-6">
        <div class="plan-price-card">
            <div>
                <!-- Card Header: Title & Status -->
                <div class="plan-card-header">
                    <h4 class="plan-card-title">Plan {{ $key + 1 }} Price</h4>
                    @if($isInactive)
                    <span class="plan-status-badge inactive">
                        <span class="status-dot"></span> INACTIVE
                    </span>
                    @else
                    <span class="plan-status-badge active">
                        <span class="status-dot"></span> ACTIVE
                    </span>
                    @endif
                </div>

                <!-- Plan Duration Badge -->
                <div class="plan-duration-badge mb-3">
                    <i class="fa-regular fa-calendar me-1.5" style="color: #18225f;"></i> {{ strtoupper($planDuration) }}
                </div>

                <!-- Shift Name Meta -->
                <div class="plan-meta-section">
                    <div class="plan-meta-label">Shift Name</div>
                    <div class="plan-meta-value text-truncate" title="{{ $shiftName }}">{{ $shiftName }}</div>
                </div>

                <!-- Plan Price Meta -->
                <div class="plan-price-section">
                    <div class="plan-price-label">Plan Price</div>
                    <div class="plan-price-amount">₹{{ $priceFormatted }}</div>
                </div>
            </div>

            <!-- Bottom Action Buttons Row -->
            <div class="plan-card-actions">
                <a href="javascript:void(0)" class="btn-plan-action btn-action-edit price-edit"
                    data-id="{{ $price->id }}"
                    data-plan-id="{{ $price->plan_id }}"
                    data-plan-type-id="{{ $price->plan_type_id }}"
                    data-price="{{ $price->price }}"
                    title="Edit Plan Price">
                    <i class="fa-solid fa-pen-to-square me-1"></i> Edit
                </a>

                <a href="javascript:void(0)" class="btn-plan-action btn-action-delete price-delete"
                    data-url="{{ route('library.branch.prices.delete', $price->id) }}"
                    title="Delete Plan Price">
                    <i class="fa-solid fa-trash me-1"></i> Delete
                </a>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endif

<script>
function resetPriceForm() {
    document.getElementById('price_id_field').value = '';
    document.getElementById('priceForm').reset();
    document.getElementById('priceFormTitle').textContent = 'Add Plan Price';
    document.getElementById('savePriceBtn').textContent = 'Add Plan Price';
}

document.getElementById('togglePriceForm').addEventListener('click', function () {
    const form = document.getElementById('priceForm');
    form.style.display = form.style.display === 'none' ? 'block' : 'none';
    resetPriceForm();
});

document.querySelectorAll('.price-edit').forEach(function (link) {
    link.addEventListener('click', function () {
        document.getElementById('priceForm').style.display = 'block';
        document.getElementById('price_id_field').value = this.dataset.id;
        document.getElementById('price_plan_id').value = this.dataset.planId;
        document.getElementById('price_plan_type_id').value = this.dataset.planTypeId;
        document.getElementById('price_amount').value = this.dataset.price;
        document.getElementById('priceFormTitle').textContent = 'Edit Plan Price';
        document.getElementById('savePriceBtn').textContent = 'Update Plan Price';
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
});

document.querySelectorAll('.price-delete').forEach(function (link) {
    link.addEventListener('click', function () {
        const url = this.dataset.url;
        Swal.fire({
            title: 'Delete this plan price?',
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
                  .catch(function () { Swal.fire('Error', 'Failed to delete plan price.', 'error'); });
            }
        });
    });
});
</script>
@endsection
