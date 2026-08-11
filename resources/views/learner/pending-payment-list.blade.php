@extends('layouts.library')
@section('content')

@php
$activeFilter = request()->get('filter', 'all');
$tabs = [
    'all' => 'All',
    'pending' => 'Pending',
    'overdue' => 'Overdue',
    'adjusted' => 'Adjusted',
    'expired' => 'Expired',
    'received' => 'Received',
];
$queryWithoutFilter = collect(request()->query())->except('filter', 'page')->toArray();
@endphp

<div class="row mb-3">
    <div class="col-lg-12">
        <h4 class="mb-0">Pending Payment</h4>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-3 col-md-6 col-6">
        <div class="booking-count bg-3">
            <h6>Total Pending</h6>
            <div class="d-flex">
                <h4>{{ rtrim(rtrim(number_format((float) $summary['total_pending_amount'], 2, '.', ''), '0'), '.') }}</h4>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-6">
        <div class="booking-count bg-3">
            <h6>Expired Learner Due</h6>
            <div class="d-flex">
                <h4>{{ rtrim(rtrim(number_format((float) $summary['expired_learner_due_amount'], 2, '.', ''), '0'), '.') }}</h4>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-6">
        <div class="booking-count bg-3">
            <h6>Adjusted</h6>
            <div class="d-flex">
                <h4>{{ rtrim(rtrim(number_format((float) $summary['adjusted_pending_amount'], 2, '.', ''), '0'), '.') }}</h4>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6 col-6">
        <div class="booking-count bg-3">
            <h6>Final Due</h6>
            <div class="d-flex">
                <h4>{{ rtrim(rtrim(number_format((float) $summary['final_due'], 2, '.', ''), '0'), '.') }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-lg-12">
        <ul class="nav nav-pills">
            @foreach($tabs as $key => $label)
            <li class="nav-item">
                <a class="nav-link {{ $activeFilter == $key ? 'active' : '' }}"
                    href="{{ route('learner.pending.payment.list', array_merge($queryWithoutFilter, ['filter' => $key])) }}">
                    {{ $label }}
                </a>
            </li>
            @endforeach
        </ul>
    </div>
</div>

<div class="row mb-3" id="filterContainer">
    <div class="col-lg-12">
        <div class="filter p-3 bg-white">
            <form action="{{ route('learner.pending.payment.list') }}" method="GET">
                <input type="hidden" name="filter" value="{{ $activeFilter }}">
                <div class="row g-3">
                    <div class="col-lg-3">
                        <input type="text" class="form-control" name="search" placeholder="Enter Name, Mobile or Seat No" value="{{ request()->get('search') }}">
                    </div>

                    <div class="col-lg-2">
                        <select name="plan_type_id[]" class="form-select">
                            <option value="">Choose Plan Type</option>
                            @foreach($planTypes as $planType)
                            <option value="{{ $planType->id }}" {{ in_array($planType->id, (array) request()->get('plan_type_id', [])) ? 'selected' : '' }}>
                                {{ $planType->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-lg-2">
                        <select name="sort_by" class="form-select">
                            <option value="seat_no" {{ request()->get('sort_by') == 'seat_no' ? 'selected' : '' }}>Sort: Seat No</option>
                            <option value="name" {{ request()->get('sort_by') == 'name' ? 'selected' : '' }}>Sort: Name</option>
                            <option value="expire_date" {{ request()->get('sort_by') == 'expire_date' ? 'selected' : '' }}>Sort: Expire Date</option>
                            <option value="gen" {{ request()->get('sort_by') == 'gen' ? 'selected' : '' }}>Sort: General Seat</option>
                        </select>
                    </div>

                    <div class="col-lg-2">
                        <select name="sort_order" class="form-select">
                            <option value="asc" {{ request()->get('sort_order', 'asc') == 'asc' ? 'selected' : '' }}>Ascending</option>
                            <option value="desc" {{ request()->get('sort_order') == 'desc' ? 'selected' : '' }}>Descending</option>
                        </select>
                    </div>

                    <div class="col-lg-2">
                        <input type="date" class="form-control" name="from_date" value="{{ request()->get('from_date') }}" placeholder="From Date">
                    </div>

                    <div class="col-lg-2">
                        <input type="date" class="form-control" name="to_date" value="{{ request()->get('to_date') }}" placeholder="To Date">
                    </div>

                    <div class="col-lg-2">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>

                    <div class="col-lg-2">
                        <a href="{{ route('learner.pending.payment.list') }}" class="btn btn-outline-secondary w-100">Reset</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="pp-list">
            <div class="row g-2 align-items-center pp-row pp-row-head d-none d-md-flex bg-white">
                <div class="col-md-1"><strong>Seat No</strong></div>
                <div class="col-md-3"><strong>Learner</strong></div>
                <div class="col-md-2"><strong>Plan</strong></div>
                <div class="col-md-1"><strong>Plan End Date</strong></div>
                <div class="col-md-1"><strong>Status</strong></div>
                <div class="col-md-1"><strong>Pending Amount</strong></div>
                <div class="col-md-1"><strong>Due Date</strong></div>
                <div class="col-md-2 text-end"><strong>Action</strong></div>
            </div>

            @forelse($learners as $learner)
            <div class="row g-2 align-items-center pp-row bg-white">
                <div class="col-6 col-md-1">
                    <div class="pp-label d-md-none text-muted small">Seat No</div>
                    <div class="pp-value">{{ $learner['seat_no'] }}</div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="pp-label d-md-none text-muted small">Learner</div>
                    <div class="pp-value">
                        <a href="{{ route('learners.show', $learner['id']) }}">{{ $learner['name'] }}</a>
                        <div class="small text-muted">UID: {{ $learner['learner_no'] }} | {{ $learner['mobile'] }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-2">
                    <div class="pp-label d-md-none text-muted small">Plan</div>
                    <div class="pp-value">
                        {{ $learner['plan'] }}
                        <div class="small text-muted">{{ $learner['plan_type'] }}</div>
                    </div>
                </div>
                <div class="col-6 col-md-1">
                    <div class="pp-label d-md-none text-muted small">Plan End Date</div>
                    <div class="pp-value">{{ $learner['plan_end_date'] ? date('j M Y', strtotime($learner['plan_end_date'])) : '-' }}</div>
                </div>
                <div class="col-6 col-md-1">
                    <div class="pp-label d-md-none text-muted small">Status</div>
                    <div class="pp-value">
                        <span class="badge bg-{{ $learner['payment']['status'] == 'overdue' ? 'danger' : ($learner['payment']['status'] == 'paid' ? 'success' : 'warning') }}">
                            {{ ucfirst($learner['payment']['status']) }}
                        </span>
                    </div>
                </div>
                <div class="col-6 col-md-1">
                    <div class="pp-label d-md-none text-muted small">Pending Amount</div>
                    <div class="pp-value">{{ rtrim(rtrim(number_format((float) $learner['payment']['pending_amount'], 2, '.', ''), '0'), '.') }}</div>
                </div>
                <div class="col-6 col-md-1">
                    <div class="pp-label d-md-none text-muted small">Due Date</div>
                    <div class="pp-value">{{ $learner['payment']['due_date'] ? date('j M Y', strtotime($learner['payment']['due_date'])) : '-' }}</div>
                </div>
                <div class="col-12 col-md-2 text-md-end">
                    @if($learner['transaction_id'])
                    <a href="javascript:void(0)" data-id="{{ $learner['id'] }}" class="btn btn-sm btn-primary w-100 w-md-auto settlement-learner">
                        Pay Due Amount
                    </a>
                    @endif
                </div>
            </div>
            @empty
            <div class="pp-row bg-white text-center text-muted py-4">
                No pending payment records found.
            </div>
            @endforelse
        </div>
    </div>
</div>

<style>
    .pp-row {
        margin: 0 0 .75rem;
        padding: .75rem .5rem;
        border-radius: .5rem;
        border: 1px solid #e9ecef;
    }
    .pp-row-head {
        background: #f8f9fa !important;
        font-size: .85rem;
        text-transform: uppercase;
        color: #6c757d;
        border: none;
        padding-bottom: .5rem;
        margin-bottom: .5rem;
    }
    .pp-value {
        word-break: break-word;
    }
    @media (max-width: 767.98px) {
        .pp-row {
            box-shadow: 0 1px 3px rgba(0,0,0,.06);
        }
        .pp-row > [class*="col-"] {
            margin-bottom: .5rem;
        }
        .pp-row > [class*="col-"]:last-child {
            margin-bottom: 0;
        }
        .pp-label {
            font-size: .72rem;
            text-transform: uppercase;
            letter-spacing: .03em;
        }
    }
</style>

@if($learners->lastPage() > 1)
<div class="row mt-3">
    <div class="col-lg-12">
        <ul class="paginations">
            <li>
                <a href="{{ $learners->onFirstPage() ? '#' : $learners->appends(request()->all())->previousPageUrl() }}" class="w-auto px-3 text-muted">
                    Prev
                </a>
            </li>

            @for ($i = 1; $i <= $learners->lastPage(); $i++)
            <li>
                <a href="{{ $learners->appends(request()->all())->url($i) }}" class="{{ $learners->currentPage() == $i ? 'active' : '' }}">
                    {{ $i }}
                </a>
            </li>
            @endfor

            <li>
                <a href="{{ $learners->hasMorePages() ? $learners->appends(request()->all())->nextPageUrl() : '#' }}" class="w-auto px-3 text-muted">
                    Next
                </a>
            </li>
        </ul>
    </div>
</div>
@endif

@endsection
