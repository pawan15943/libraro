@extends('layouts.library')
@section('content')
@can('has-permission', 'Search Learner')
@php
    $fmtMoney = static fn ($v) => number_format((float) ($v ?? 0), 2);
    $fmtDate = static fn ($d) => $d ? date('j M Y', strtotime($d)) : '—';
    $fmtDateInput = static fn ($d) => $d ? date('Y-m-d', strtotime($d)) : '';

    $seatDisplay = ($detail && $detail->seat_no)
        ? getSeatDisplayByMainNo($detail->seat_no)
        : (($learner->seat_no ?? null) ? getSeatDisplayByMainNo($learner->seat_no) : null);

    $lockerAmt = $transaction->locker_amount ?? null;
    $discountAmt = $transaction->discount_amount ?? null;
    $diffStored = $transaction->diffrence_amount ?? $transaction->difference_amount ?? null;
    if ($diffStored === null && isset($transaction->total_amount)) {
        $diffStored = round(
            (float) $transaction->total_amount - (float) $transaction->paid_amount - (float) $transaction->pending_amount,
            2
        );
    }

    $planTypeImage = $detail && $detail->planType && !empty($detail->planType->image)
        ? asset($detail->planType->image)
        : null;
@endphp

<div class="row mt-4">
    <div class="col-lg-12 mb-3 d-flex flex-wrap align-items-center gap-2 justify-content-between">
        <a href="{{ route('create.renew.delete.index', request()->only(['search'])) }}" class="btn btn-outline-secondary btn-sm">&larr; Back to list</a>
        <span class="text-muted small">Latest renew only (within last {{ \App\Services\RenewTransactionDeleteService::DELETE_WINDOW_DAYS }} days) — read-only detail</span>
    </div>
</div>

@if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
@endif
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if(!$canDelete && $deleteBlockReason)
    <div class="alert alert-warning">{{ $deleteBlockReason }}</div>
@endif

<div class="row g-4">
    <div class="col-lg-9 order-2 order-md-1">
        <div class="library-operations mt-2">
            <div class="info__section">
                <h4 class="inner-heading">Learner Info</h4>
                <div class="row g-4">
                    <div class="col-lg-6">
                        <label class="text-white">Seat Owner Name</label>
                        <input type="text" class="form-control" value="{{ $learner->name ?? '' }}" readonly tabindex="-1">
                    </div>
                    <div class="col-lg-6">
                        <label class="text-white">DOB</label>
                        <input type="text" class="form-control" value="{{ $fmtDateInput($learner->dob ?? null) }}" readonly tabindex="-1">
                    </div>
                    <div class="col-lg-6">
                        <label class="text-white">Mobile Number</label>
                        <input type="text" class="form-control" value="{{ $learner->mobile ?? '' }}" readonly tabindex="-1">
                    </div>
                    <div class="col-lg-6">
                        <label class="text-white">Email Id</label>
                        <input type="text" class="form-control" value="{{ $learner->email ?? '' }}" readonly tabindex="-1">
                    </div>
                    <div class="col-lg-6">
                        <label class="text-white">Learner No.</label>
                        <input type="text" class="form-control" value="{{ $learner->learner_no ?? '—' }}" readonly tabindex="-1">
                    </div>
                </div>
            </div>

            <div class="form-input mb-4">
                <h4 class="inner-heading">Plan &amp; booking (this renew)</h4>
                <p class="text-secondary small mb-3">
                    This screen mirrors seat booking layout for quick review. Dates and amounts reflect the learner detail and transaction linked to this renew.
                </p>
                <div class="row g-4">
                    <div class="col-lg-6">
                        <label>Plan</label>
                        <select class="form-select" disabled>
                            <option selected>{{ $detail->plan->name ?? '—' }}</option>
                        </select>
                    </div>
                    <div class="col-lg-6">
                        <label>Plan Type</label>
                        <select class="form-select" disabled>
                            <option selected>{{ $detail->planType->name ?? '—' }}</option>
                        </select>
                    </div>
                    <div class="col-lg-6">
                        <label>Plan Start Date</label>
                        <input type="date" class="form-control" value="{{ $fmtDateInput($detail->plan_start_date ?? null) }}" readonly tabindex="-1">
                    </div>
                    <div class="col-lg-6">
                        <label>Plan End Date</label>
                        <input type="date" class="form-control" value="{{ $fmtDateInput($detail->plan_end_date ?? null) }}" readonly tabindex="-1">
                    </div>
                    <div class="col-lg-4">
                        <label>Plan Price</label>
                        <input type="text" class="form-control" value="{{ $detail->plan_price_id ?? '—' }}" readonly tabindex="-1">
                    </div>
                    <div class="col-lg-4">
                        <label>Seat</label>
                        <input type="text" class="form-control" value="{{ $seatDisplay ? $seatDisplay : 'GEN' }}" readonly tabindex="-1">
                    </div>
                    <div class="col-lg-4">
                        <label>Learner detail ID</label>
                        <input type="text" class="form-control" value="{{ $detail->id ?? '—' }}" readonly tabindex="-1">
                    </div>
                </div>

                <h4 class="mt-4 mb-3 text-secondary" style="font-size: 0.95rem;">Your plan Add-on's</h4>
                <div class="row g-4 border-top pt-3">
                    <div class="col-lg-4">
                        <label>Locker amount</label>
                        <input type="text" class="form-control" value="{{ $lockerAmt !== null ? $fmtMoney($lockerAmt) : $fmtMoney(0) }}" readonly tabindex="-1">
                    </div>
                    <div class="col-lg-4">
                        <label>Locker No.</label>
                        <input type="text" class="form-control" value="{{ $learner->locker_no ?? '—' }}" readonly tabindex="-1">
                    </div>
                    <div class="col-lg-4">
                        <label>Discount amount</label>
                        <input type="text" class="form-control" value="{{ $discountAmt !== null ? $fmtMoney($discountAmt) : $fmtMoney(0) }}" readonly tabindex="-1">
                    </div>
                    @if(($transaction->discount_type ?? '') !== '')
                        <div class="col-lg-6">
                            <label>Discount type</label>
                            <input type="text" class="form-control" value="{{ $transaction->discount_type }}" readonly tabindex="-1">
                        </div>
                    @endif
                </div>

                <h4 class="mt-4 pt-3 border-top mb-3 fw-semibold text-uppercase small" style="color: #007c8c; letter-spacing: 0.02em;">Payment &amp; transaction</h4>
                <div class="row g-4">
                    <div class="col-lg-4">
                        <label>Total amount</label>
                        <input type="text" class="form-control" value="{{ $fmtMoney($transaction->total_amount ?? 0) }}" readonly tabindex="-1">
                    </div>
                    <div class="col-lg-4">
                        <label>Paid amount</label>
                        <input type="text" class="form-control" value="{{ $fmtMoney($transaction->paid_amount ?? 0) }}" readonly tabindex="-1">
                    </div>
                    <div class="col-lg-4">
                        <label>Pending amount</label>
                        <input type="text" class="form-control" value="{{ $fmtMoney($transaction->pending_amount ?? 0) }}" readonly tabindex="-1">
                    </div>
                    <div class="col-lg-4">
                        <label>Difference amount</label>
                        <input type="text" class="form-control" value="{{ $fmtMoney($diffStored) }}" readonly tabindex="-1">
                    </div>
                    <div class="col-lg-4">
                        <label>Payment mode (booking)</label>
                        <input type="text" class="form-control" value="{{ $paymentModeLabel }}" readonly tabindex="-1">
                    </div>
                    <div class="col-lg-4">
                        <label>Paid date</label>
                        <input type="text" class="form-control" value="{{ $fmtDate($transaction->paid_date ?? null) }}" readonly tabindex="-1">
                    </div>
                    <div class="col-lg-4">
                        <label>Recorded at</label>
                        <input type="text" class="form-control" value="{{ $transactionCreatedDisplay }}" readonly tabindex="-1">
                    </div>
                    <div class="col-lg-4">
                        <label>Transaction row ID</label>
                        <input type="text" class="form-control" value="{{ $transaction->id }}" readonly tabindex="-1">
                    </div>
                    <div class="col-lg-4">
                        <label>Is paid (flag)</label>
                        <input type="text" class="form-control" value="{{ isset($transaction->is_paid) ? ($transaction->is_paid ? 'Yes' : 'No') : '—' }}" readonly tabindex="-1">
                    </div>
                    @if(!empty($transaction->transaction_id))
                        <div class="col-lg-6">
                            <label>Payment reference / transaction id</label>
                            <input type="text" class="form-control" value="{{ $transaction->transaction_id }}" readonly tabindex="-1">
                        </div>
                    @endif
                    @if(!empty($transaction->transaction_image))
                        <div class="col-lg-6">
                            <label>Transaction image</label>
                            <div>
                                <a href="{{ asset($transaction->transaction_image) }}" target="_blank" rel="noopener">View upload</a>
                            </div>
                        </div>
                    @endif
                    @if($transaction->updated_at)
                        <div class="col-lg-4">
                            <label>Last updated</label>
                            <input type="text" class="form-control" value="{{ \Carbon\Carbon::parse($transaction->updated_at)->format('j M Y, g:i A') }}" readonly tabindex="-1">
                        </div>
                    @endif
                </div>

                <div class="table-responsive mt-4 pt-2 border-top">
                    <table class="table table-bordered table-sm align-middle mb-0">
                        <caption class="small text-muted">Summary row (same figures as above)</caption>
                        <thead class="table-light">
                            <tr>
                                <th>Total</th>
                                <th>Paid</th>
                                <th>Pending</th>
                                <th>Locker</th>
                                <th>Discount</th>
                                <th>Paid date</th>
                                <th>Recorded at</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>{{ $fmtMoney($transaction->total_amount ?? 0) }}</td>
                                <td>{{ $fmtMoney($transaction->paid_amount ?? 0) }}</td>
                                <td>{{ $fmtMoney($transaction->pending_amount ?? 0) }}</td>
                                <td>{{ $fmtMoney($transaction->locker_amount ?? 0) }}</td>
                                <td>{{ $fmtMoney($transaction->discount_amount ?? 0) }}</td>
                                <td>{{ $fmtDate($transaction->paid_date ?? null) }}</td>
                                <td>{{ $transactionCreatedDisplay }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                @if($canDelete)
                    <form method="POST" action="{{ route('create.renew.delete.destroy', ['transaction' => $transaction->id]) }}"
                        class="mt-4 pt-4 border-top renew-delete-confirm-form"
                        data-swal-title="Delete renew transaction?"
                        data-swal-text="The previous booking will be restored. Are you sure you want to continue?">
                        @csrf
                        <input type="hidden" name="learner_id" value="{{ $learner->id }}">
                        <button type="submit" class="btn btn-danger">Delete renew transaction</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-3 order-1 order-md-2">
        <div class="seatnumber mt-2">
            @if($planTypeImage)
                <img src="{{ $planTypeImage }}" alt="" class="py-3" style="width:60px; display:block; margin:0 auto;">
            @else
                <i class="fa fa-chair fa-3x py-3" style="color:#c62828; display:block; margin:0 auto;"></i>
            @endif
            @if($seatDisplay)
                <span class="d-block small text-muted">Seat</span>
                <span class="d-block fw-semibold">{{ $seatDisplay }}</span>
            @else
                <span class="d-block small text-muted">Seating</span>
                <span class="d-block fw-semibold">General</span>
            @endif
            <div class="seat--plan">{{ $detail->planType->name ?? '—' }}</div>
        </div>
    </div>
</div>
@endcan
<script>
window.addEventListener('load', function () {
    document.querySelectorAll('form.renew-delete-confirm-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var title = form.getAttribute('data-swal-title') || 'Confirm delete';
            var text = form.getAttribute('data-swal-text') || '';
            var submitForm = function () {
                form.submit();
            };
            if (typeof Swal === 'undefined') {
                if (window.confirm(title + (text ? '\n\n' + text : ''))) {
                    submitForm();
                }
                return;
            }
            Swal.fire({
                icon: 'warning',
                title: title,
                text: text,
                showCancelButton: true,
                confirmButtonText: 'Yes, delete',
                cancelButtonText: 'Cancel',
                reverseButtons: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
            }).then(function (result) {
                if (result.isConfirmed) {
                    submitForm();
                }
            });
        });
    });
});
</script>
@endsection
