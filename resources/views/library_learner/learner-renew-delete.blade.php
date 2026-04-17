@extends('layouts.library')
@section('content')
@php
$renewDays = \App\Services\RenewTransactionDeleteService::DELETE_WINDOW_DAYS;
$money = static fn ($v) => number_format((float) ($v ?? 0), 2);
@endphp

<style>
    table{
        margin-bottom: 1.5rem !important;
    }
    table.table.table-sm.table-striped.table-hover.mb-0.renew-delete-table.text-start th {
        padding: .8rem 1rem !important;
        font-weight: 700;
        font-family: 'Outfit','sans-sarif' !important;
    }

    td.small.text-start.align-top {
        padding: .5rem 1rem;
        line-height: 27px;
        font-family: 'Outfit','sans-sarif' !important;
    }
button.btn.btn-outline-danger.btn-sm {
    border-radius: 2rem;
    width: 130px;
    color: #fff !important;
    font-size: 1rem !IMPORTANT;
    background: rgb(185, 0, 0)
}

button.btn.btn-outline-danger.btn-sm * {
    color: #fff !IMPORTANT;
    font-size: 1rem !IMPORTANT;
}
button i {
    background: inherit !important;
    color: #ffffff ! IMPORTANT;
    text-transform: uppercase;
    font-size: .8rem ! IMPORTANT;
    font-weight: 600 !IMPORTANT;
}
</style>
@can('has-permission', 'Search Learner')
<div class="row mb-3 find-a-learner renew-delete-page">
    <div class="col-12 col-lg-12 text-start">
        <div class="alert alert-secondary">
            Note: If a student is mistakenly renewed, you can revert the operation from here within 5 days.
        </div>
        <form action="{{ route('create.renew.delete.index') }}" method="GET" class="mt-3">
            <div class="row g-3">
                <div class="col-12 col-md-8 col-lg-3">
                    <input type="text" name="search" class="form-control @error('search') is-invalid @enderror form-control-lg text-start" value="{{ request()->get('search') }}" placeholder="Search by Name | Mobile | Seat No" id="search-input">
                    @error('search')
                    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                    @enderror
                </div>
                <div class="col-12 col-lg-2">
                    <button type="submit" class="btn btn-primary button">Search</button>
                </div>
            </div>
        </form>



        @if(isset($learners) && $learners->isEmpty())
        <div class="alert alert-info mt-4 text-start small mb-0">
            No learner found with a renew in the last {{ $renewDays }} days.
        </div>
        @endif
    </div>
</div>

@if(isset($learners) && $learners->total() > 0)
<div class="row renew-delete-results text-start mb-4">
    <div class="col-12">
        <p class="text-muted small mb-2 text-start">{{ $learners->total() }} result(s){{ $learners->lastPage() > 1 ? ' — page ' . $learners->currentPage() . ' of ' . $learners->lastPage() : '' }}</p>
        <div class="table-responsive bg-white rounded border text-start">
            <table class="table table-sm table-striped table-hover mb-0 renew-delete-table text-start" style="text-align: left;">
                <thead class="table-light">
                    <tr>
                        <th class="text-start align-top" scope="col" style="text-align: left !important; min-width:9rem;">Learner info</th>
                        <th class="text-start align-top" scope="col" style="text-align: left !important; min-width:10rem;">Contact info</th>
                        <th class="text-start align-top" scope="col" style="text-align: left !important; min-width:11rem;">Plan info</th>
                        <th class="text-start align-top" scope="col" style="text-align: left !important; min-width:14rem;">Last trxn info</th>
                        <th class="text-start align-top" scope="col" style="text-align: left !important; min-width:6rem;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($learners as $bundle)
                    @php
                    $value = $bundle['row'];
                    $renewTx = $bundle['transaction'];
                    $canDel = $bundle['validation']['ok'];
                    $blockReason = $bundle['validation']['message'] ?? '';
                    $learnerId = (int) $value->id;

                    $shiftTime = '—';
                    if (!empty($value->start_time) && !empty($value->end_time)) {
                    try {
                    $shiftTime = \Carbon\Carbon::parse($value->start_time)->format('g:i A') . ' – ' . \Carbon\Carbon::parse($value->end_time)->format('g:i A');
                    } catch (\Throwable $e) {
                    $shiftTime = trim(($value->start_time ?? '') . ' – ' . ($value->end_time ?? '')) ?: '—';
                    }
                    }
                    $seatLabel = $value->seat_no ? getSeatDisplayByMainNo($value->seat_no) : 'GEN';
                    $paidDate = $renewTx && $renewTx->paid_date ? date('j M Y', strtotime($renewTx->paid_date)) : '—';
                    $recorded = $renewTx && $renewTx->created_at ? $renewTx->created_at->format('j M Y, g:i A') : '—';
                    $payMode = $value->payment_mode ?? null;
                    if ($payMode !== null && $payMode !== '') {
                    $payModeLabel = is_numeric($payMode)
                    ? ([1 => 'Online', 2 => 'Offline', 3 => 'Pay later'][(int) $payMode] ?? (string) $payMode)
                    : (string) $payMode;
                    } else {
                    $payModeLabel = '—';
                    }
                    @endphp
                    <tr>
                        <td class="small text-start align-top" style="text-align: left !important;">
                            Seat : {{ $seatLabel }}<br>
                            Name : {{ $value->name ?? '—' }}<br>
                            UID : {{ $value->learner_no ?? '—' }}
                        </td>
                        <td class="small text-start align-top" style="text-align: left !important;">
                            Mobile : +91-{{ $value->mobile ?? '—' }}<br>
                            @if(!empty($value->email))
                                Email: {{ $value->email }}
                            @endif
                        </td>
                        <td class="small text-start align-top" style="text-align: left !important;">
                            Plan : {{ $value->plan_name ?? '—' }} <br>
                            Shift : {{ $value->plan_type_name ?? '—' }}<br>
                            Shift time : {{ $shiftTime }}
                        </td>
                        <td class="small text-start align-top" style="text-align: left !important;">
                            @if($renewTx)
                            Total {{$money($renewTx->total_amount)}} | Paid {{ $money($renewTx->paid_amount) }} | Pending {{$money($renewTx->pending_amount)}}<br>

                            Locker / Discount :
                            {{ $money($renewTx->locker_amount ?? 0) }} / {{ $money($renewTx->discount_amount ?? 0) }}<br>
                            Date : {{ $paidDate }}<br>
                            Payment mode : {{ $payModeLabel }}

                            @else
                            —
                            @endif
                        </td>
                        <td class="small text-start align-top" style="text-align: left !important;">
                            @if($canDel && $renewTx)
                            <form method="POST" action="{{ route('create.renew.delete.destroy', ['transaction' => $renewTx->id]) }}" class="d-inline renew-delete-confirm-form" data-swal-title="Delete this renew?" data-swal-text="The previous booking will be restored. This cannot be undo from here.">
                                @csrf
                                <input type="hidden" name="learner_id" value="{{ $learnerId }}">
                                <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fa fa-trash"></i> Delete</button>
                            </form>
                            @elseif($blockReason)
                            <span class="small text-warning d-inline-block">{{ $blockReason }}</span>
                            @else
                            <span class="text-muted small">—</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

@if(isset($learners) && $learners->lastPage() > 1)
<ul class="paginations mt-3 justify-content-start renew-delete-results">
    <li>
        <a href="{{ $learners->onFirstPage() ? '#' : $learners->appends(request()->all())->previousPageUrl() }}" class="w-auto px-3 text-muted">Prev</a>
    </li>
    @for ($i = 1; $i <= $learners->lastPage(); $i++)
        <li>
            <a href="{{ $learners->appends(request()->all())->url($i) }}" class="{{ $learners->currentPage() == $i ? 'active' : '' }}">{{ $i }}</a>
        </li>
        @endfor
        <li>
            <a href="{{ $learners->hasMorePages() ? $learners->appends(request()->all())->nextPageUrl() : '#' }}" class="w-auto px-3 text-muted">Next</a>
        </li>
</ul>
@endif
@endcan

<style>
    /* Renew delete: left-align table and keep Search button left (override .find-a-learner button centering) */
    .renew-delete-page.find-a-learner .btn.btn-primary.button {
        margin-left: 0 !important;
        margin-right: auto !important;
    }

    .renew-delete-results .table th,
    .renew-delete-results .table td {
        text-align: left !important;
        vertical-align: top;
    }

    .renew-delete-results .table-responsive {
        text-align: left;
    }

</style>

<script>
    (function() {
        const input = document.getElementById("search-input");
        if (!input) return;
        const texts = ["Search by Learner Name", "Search by Learner Mobile Number", "Search by Learner Seat No"];
        let currentText = 0;
        let charIndex = 0;
        let typing = true;

        function typeEffect() {
            let current = texts[currentText];
            if (typing) {
                input.setAttribute("placeholder", current.substring(0, charIndex++));
                if (charIndex > current.length) {
                    typing = false;
                    setTimeout(typeEffect, 300);
                } else {
                    setTimeout(typeEffect, 100);
                }
            } else {
                charIndex--;
                input.setAttribute("placeholder", current.substring(0, charIndex));
                if (charIndex === 0) {
                    typing = true;
                    currentText = (currentText + 1) % texts.length;
                    setTimeout(typeEffect, 300);
                } else {
                    setTimeout(typeEffect, 50);
                }
            }
        }
        typeEffect();
    })();

    window.addEventListener('load', function() {
        document.querySelectorAll('form.renew-delete-confirm-form').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                var title = form.getAttribute('data-swal-title') || 'Confirm delete';
                var text = form.getAttribute('data-swal-text') || '';
                var submitForm = function() {
                    form.submit();
                };
                if (typeof Swal === 'undefined') {
                    if (window.confirm(title + (text ? '\n\n' + text : ''))) {
                        submitForm();
                    }
                    return;
                }
                Swal.fire({
                    icon: 'warning'
                    , title: title
                    , text: text
                    , showCancelButton: true
                    , confirmButtonText: 'Yes, delete'
                    , cancelButtonText: 'Cancel'
                    , reverseButtons: true
                    , confirmButtonColor: '#dc3545'
                    , cancelButtonColor: '#6c757d'
                , }).then(function(result) {
                    if (result.isConfirmed) {
                        submitForm();
                    }
                });
            });
        });
    });

</script>
@endsection
