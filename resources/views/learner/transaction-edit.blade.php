@extends('layouts.library')
@section('content')

@php
    $fmt = fn ($amount) => rtrim(rtrim(number_format((float) ($amount ?? 0), 2, '.', ''), '0'), '.');
    $dateInput = function ($date) {
        if (empty($date)) {
            return '';
        }
        try {
            return \Carbon\Carbon::parse($date)->format('Y-m-d');
        } catch (\Throwable $e) {
            return '';
        }
    };
@endphp

<style>
    .txn-edit-page { max-width: 760px; margin: 0 auto; }
    .txn-edit-summary { border: 1px solid #dfe3ea; border-radius: 8px; background: #fff; padding: 16px 18px; display: flex; align-items: center; gap: 14px; margin-bottom: 18px; }
    .txn-edit-summary .icon { width: 46px; height: 46px; border-radius: 50%; background: #eef1ff; color: #07156f; display: grid; place-items: center; font-weight: 800; }
    .txn-edit-summary h5 { margin: 0; color: #07156f; font-weight: 800; }
    .txn-edit-summary small { color: #777; }
    .txn-edit-summary .badge-status { margin-left: auto; background: #d9ffc9; color: #188000; border-radius: 5px; padding: 6px 12px; font-size: .8rem; font-weight: 700; }
    .txn-edit-form { border: 1px solid #dfe3ea; border-radius: 8px; background: #fff; padding: 22px; }
    .txn-edit-form label { color: #555; font-weight: 600; font-size: .86rem; margin-bottom: 6px; }
    .txn-edit-form .form-control[readonly], .txn-edit-form .form-control:disabled { background: #f0f1f5; color: #555; }
    .txn-edit-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; }
</style>

<div class="txn-edit-page">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <a href="javascript:history.back()" class="btn btn-light border"><i class="fa-solid fa-arrow-left"></i></a>
        <h4 class="mb-0 text-primary fw-bold">Edit Transaction</h4>
        <span></span>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="txn-edit-summary">
        <div class="icon">{{ strtoupper(substr($transaction['transaction_type'] ?? 'TX', 0, 2)) }}</div>
        <div>
            <h5>{{ $transaction['transaction_type'] ?? 'Transaction' }}</h5>
            <small>{{ $transaction['transaction_ref'] ?: ('#'.$transaction['id']) }} &bull; {{ $transaction['plan'] ?? 'NA' }}</small>
        </div>
        <span class="badge-status">{{ $transaction['status'] ?? '' }}</span>
    </div>

    <form class="txn-edit-form" method="POST" action="{{ route('learners.transactions.update', $transaction['id']) }}">
        @csrf
        @method('PUT')

        <div class="row g-3">
            <div class="col-12">
                <label>Plan Price</label>
                <input type="text" class="form-control" value="{{ $fmt($transaction['plan_price'] ?? 0) }}" readonly>
            </div>

            <div class="col-6">
                <label>Locker</label>
                <input type="text" class="form-control" value="{{ $fmt($transaction['locker_amount'] ?? 0) }}" readonly>
            </div>
            <div class="col-6">
                <label>Discount</label>
                <input type="text" class="form-control" value="{{ $fmt($transaction['discount_amount'] ?? 0) }}" readonly>
            </div>

            <div class="col-6">
                <label>Total Amt.</label>
                <input type="text" class="form-control" value="{{ $fmt($transaction['total_amount'] ?? 0) }}" readonly>
            </div>
            <div class="col-6">
                <label for="txn_paid_amount">Paid Amt.*</label>
                <input type="number" step="0.01" min="0" name="paid_amount" id="txn_paid_amount" class="form-control @error('paid_amount') is-invalid @enderror"
                       value="{{ old('paid_amount', $transaction['total_paid_amount'] ?? 0) }}"
                       data-current-paid="{{ $transaction['total_paid_amount'] ?? 0 }}"
                       data-current-pending="{{ $transaction['pending_amount'] ?? 0 }}">
                @error('paid_amount') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-6">
                <label id="txn_pending_amount_label">Pending Amount</label>
                <input type="text" class="form-control" id="txn_pending_amount_display" value="{{ $fmt($transaction['pending_amount'] ?? 0) }}" readonly>
                <small class="text-muted">Updates instantly as you change Paid Amt.</small>
            </div>
            <div class="col-6">
                <label for="txn_due_date">Due Date</label>
                <input type="date" name="due_date" id="txn_due_date" class="form-control @error('due_date') is-invalid @enderror"
                       value="{{ old('due_date', $dateInput($transaction['due_date'] ?? '')) }}">
                @error('due_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>

            <div class="col-6">
                <label for="txn_paid_date">Payment Date*</label>
                <input type="date" name="paid_date" id="txn_paid_date" class="form-control @error('paid_date') is-invalid @enderror"
                       value="{{ old('paid_date', $dateInput($transaction['paid_date'] ?? '')) }}">
                @error('paid_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-6">
                <label for="txn_payment_mode">Payment Mode *</label>
                @php $currentMode = old('payment_mode', strtoupper((string) ($transaction['payment_mode'] ?? ''))); @endphp
                <select name="payment_mode" id="txn_payment_mode" class="form-select @error('payment_mode') is-invalid @enderror">
                    <option value="1" @selected(!in_array($currentMode, ['OFFLINE', '2', 'PAYLATER', '3'], true))>ONLINE</option>
                    <option value="2" @selected($currentMode === 'OFFLINE' || $currentMode === '2')>OFFLINE</option>
                    <option value="3" @selected($currentMode === 'PAYLATER' || $currentMode === '3')>PAYLATER</option>
                </select>
                @error('payment_mode') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="txn-edit-actions">
            <button type="submit" class="btn btn-primary px-4">Update</button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const paidInput = document.getElementById('txn_paid_amount');
        const pendingDisplay = document.getElementById('txn_pending_amount_display');
        const pendingLabel = document.getElementById('txn_pending_amount_label');
        if (!paidInput || !pendingDisplay) {
            return;
        }

        const currentPaid = parseFloat(paidInput.dataset.currentPaid) || 0;
        const currentPending = parseFloat(paidInput.dataset.currentPending) || 0;

        const fmt = (n) => (Math.round(n * 100) / 100).toString();

        const recalc = function () {
            const newPaid = parseFloat(paidInput.value);
            // Same formula as LearnerLifecycleService::updateTransaction():
            // pending = max(0, oldPending + oldPaid - newPaid); any negative remainder is extra/overpaid.
            const raw = currentPending + currentPaid - (isNaN(newPaid) ? currentPaid : newPaid);

            if (raw >= 0) {
                pendingLabel.textContent = 'Pending Amount';
                pendingDisplay.value = fmt(raw);
                pendingDisplay.classList.remove('text-success');
                pendingDisplay.classList.add('text-danger');
            } else {
                pendingLabel.textContent = 'Extra Amt.';
                pendingDisplay.value = fmt(Math.abs(raw));
                pendingDisplay.classList.remove('text-danger');
                pendingDisplay.classList.add('text-success');
            }
        };

        paidInput.addEventListener('input', recalc);
    });
</script>

@endsection
