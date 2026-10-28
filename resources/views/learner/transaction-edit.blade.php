@extends('layouts.library')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-swap.css') }}?v={{ @filemtime(public_path('css/lb-swap.css')) }}" />
<link rel="stylesheet" href="{{ asset('public/css/lb-opform.css') }}?v={{ @filemtime(public_path('css/lb-opform.css')) }}" />
<link rel="stylesheet" href="{{ asset('public/css/lb-txn.css') }}?v={{ @filemtime(public_path('css/lb-txn.css')) }}" />
@endpush

@section('content')

@php
    $fmt = fn ($amount) => rtrim(rtrim(number_format((float) ($amount ?? 0), 2, '.', ''), '0'), '.');
    $inr = fn ($amount) => '₹' . number_format((float) ($amount ?? 0), 0, '.', ',');
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
    $dateFmt = function ($date) {
        try {
            return $date ? \Carbon\Carbon::parse($date)->format('d M Y') : '-';
        } catch (\Throwable $e) {
            return (string) $date;
        }
    };

    $teName = trim((string) ($learner->name ?? 'Learner'));
    $teInitials = collect(explode(' ', $teName))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') ?: 'L';
    $tePhoto = ($learner?->profile_picture && file_exists(public_path($learner->profile_picture))) ? asset($learner->profile_picture) : null;
    $teSeat = !empty($transaction['seat_no']) && $transaction['seat_no'] != 0 ? getSeatDisplayShortFloorName($transaction['seat_no']) : null;
    $teBack = $learner ? route('learners.transactions', $learner->id) : (url()->previous() !== url()->current() ? url()->previous() : route('learners'));
    $teStatus = strtolower((string) ($transaction['status_badge'] ?? $transaction['status'] ?? ''));
    $teTone = in_array($teStatus, ['active', 'paid'], true) ? 'ok' : (in_array($teStatus, ['expired', 'inactive'], true) ? 'bad' : 'warn');

    $currentMode = old('payment_mode', strtoupper((string) ($transaction['payment_mode'] ?? '')));
    $modeId = in_array($currentMode, ['OFFLINE', '2'], true) ? '2' : (in_array($currentMode, ['PAYLATER', 'PAY LATER', '3'], true) ? '3' : '1');
    $modes = [
        '1' => ['Online', 'smartphone'],
        '2' => ['Offline', 'banknote'],
        '3' => ['Pay later', 'clock'],
    ];
@endphp

<div class="lsw ltxe">
    <form class="lsw-sheet" method="POST" action="{{ route('learners.transactions.update', $transaction['id']) }}" id="ltxeForm" novalidate>
        @csrf
        @method('PUT')

        <header class="lsw-head">
            <span class="lsw-head-ic"><x-lb-icon name="square-pen" /></span>
            <div class="lsw-head-t">
                <span class="lsw-kicker">Edit transaction</span>
                <h2>{{ ucwords(strtolower($transaction['transaction_type'] ?? 'Transaction')) }}</h2>
            </div>
            <a href="{{ $teBack }}" class="lsw-close" title="Close" aria-label="Close"><x-lb-icon name="x" /></a>
        </header>

        <div class="lsw-body">
            @if($errors->any())
                <div class="lef-alert" role="alert"><x-lb-icon name="circle-alert" /><span>{{ $errors->first() }}</span></div>
            @endif

            {{-- Who + which transaction --}}
            <section class="lsw-member">
                <div class="lsw-member-top">
                    <span class="lsw-ava">@if($tePhoto)<img src="{{ $tePhoto }}" alt="{{ $teName }}">@else{{ $teInitials }}@endif</span>
                    <div class="lsw-member-id">
                        <h3><span class="lsw-nm">{{ $teName }}</span> <span class="lsw-name-seat"><x-lb-icon name="armchair" />{{ $teSeat ? 'Seat ' . $teSeat : 'General seat' }}</span></h3>
                        <div class="lsw-member-tags">
                            <span class="lsw-uid">#{{ $transaction['transaction_ref'] ?: $transaction['id'] }}</span>
                            @if($teStatus)
                                <span class="lsw-pill is-{{ $teTone }}">{{ $transaction['status_badge'] ?? $transaction['status'] }}</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="lsw-chips">
                    <span class="lsw-chip"><x-lb-icon name="calendar-days" />{{ $transaction['plan_name'] ?? $transaction['plan'] ?? 'Plan' }}</span>
                    @if(!empty($transaction['plan_type_name']))
                        <span class="lsw-chip"><x-lb-icon name="clock" />{{ $transaction['plan_type_name'] }}</span>
                    @endif
                    @if(!empty($transaction['plan_start_date']))
                        <span class="lsw-chip"><x-lb-icon name="calendar-range" />{{ $dateFmt($transaction['plan_start_date']) }} – {{ $dateFmt($transaction['plan_end_date'] ?? null) }}</span>
                    @endif
                </div>
            </section>

            {{-- Fixed bill (read only) --}}
            <section class="ltxe-bill" aria-label="Bill">
                <div><span>Plan price</span><b>{{ $inr($transaction['plan_price'] ?? 0) }}</b></div>
                <div><span>Locker</span><b>{{ $inr($transaction['locker_amount'] ?? 0) }}</b></div>
                <div><span>Discount</span><b>− {{ $inr($transaction['discount_amount'] ?? 0) }}</b></div>
                <div class="is-total"><span>Total</span><b>{{ $inr($transaction['total_amount'] ?? 0) }}</b></div>
            </section>

            {{-- Paid amount --}}
            <section class="lef-sec">
                <div class="lef-sec-h">
                    <span class="lef-sec-ic is-green"><x-lb-icon name="indian-rupee" /></span>
                    <div>
                        <h4>Amount paid</h4>
                        <p>Pending or extra updates as you type.</p>
                    </div>
                </div>

                <label class="ltxe-amt @error('paid_amount') is-bad @enderror" for="txn_paid_amount">
                    <span>₹</span>
                    <input type="number" inputmode="decimal" step="0.01" min="0" max="{{ (float) ($transaction['total_amount'] ?? 0) }}" name="paid_amount" id="txn_paid_amount"
                           value="{{ old('paid_amount', $fmt($transaction['total_paid_amount'] ?? 0)) }}"
                           data-current-paid="{{ $transaction['total_paid_amount'] ?? 0 }}"
                           data-current-pending="{{ $transaction['pending_amount'] ?? 0 }}"
                           data-total="{{ $transaction['total_amount'] ?? 0 }}"
                           aria-describedby="ltxeBal">
                    <button type="button" class="ltxe-full" id="ltxeFull" data-full="{{ $fmt($transaction['total_amount'] ?? 0) }}">Full {{ $inr($transaction['total_amount'] ?? 0) }}</button>
                </label>
                @error('paid_amount') <p class="lef-err">{{ $message }}</p> @enderror
                <p class="lef-err" id="ltxeOver" hidden>Paid amount can’t be more than the total of {{ $inr($transaction['total_amount'] ?? 0) }}.</p>

                <div class="ltxe-bal" id="ltxeBal" data-state="{{ (float) ($transaction['pending_amount'] ?? 0) > 0 ? 'pending' : 'clear' }}" aria-live="polite">
                    <div class="ltxe-meter"><i id="ltxeMeter"></i></div>
                    <div class="ltxe-bal-row">
                        <span id="ltxeBalLabel">{{ (float) ($transaction['pending_amount'] ?? 0) > 0 ? 'Pending' : 'Fully paid' }}</span>
                        <b id="ltxeBalVal">{{ $inr($transaction['pending_amount'] ?? 0) }}</b>
                    </div>
                </div>
            </section>

            {{-- Mode + dates --}}
            <section class="lef-sec">
                <div class="lef-sec-h">
                    <span class="lef-sec-ic is-blue"><x-lb-icon name="credit-card" /></span>
                    <div>
                        <h4>Payment details</h4>
                        <p>How and when it was paid.</p>
                    </div>
                </div>

                <div class="lef-f">
                    <label>Payment mode <i>*</i></label>
                    <div class="ltxe-seg" role="radiogroup" aria-label="Payment mode">
                        @foreach($modes as $id => [$label, $icon])
                            <label class="ltxe-seg-o">
                                <input type="radio" name="payment_mode" value="{{ $id }}" @checked($modeId === (string) $id)>
                                <span><x-lb-icon :name="$icon" />{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('payment_mode') <p class="lef-err">{{ $message }}</p> @enderror
                    <p class="lef-hint is-warn" id="ltxeLaterHint" @if($modeId !== '3') hidden @endif>Pay later sets the paid amount to ₹0 and moves the full amount to pending.</p>
                </div>

                <div class="lef-grid ltxe-dates">
                    <div class="lef-f">
                        <label for="txn_paid_date">Payment date <i>*</i></label>
                        <div class="lef-in"><x-lb-icon name="calendar-check" /><input type="date" name="paid_date" id="txn_paid_date" class="@error('paid_date') is-invalid @enderror" value="{{ old('paid_date', $dateInput($transaction['paid_date'] ?? '')) }}"></div>
                        @error('paid_date') <p class="lef-err">{{ $message }}</p> @enderror
                    </div>
                    <div class="lef-f" id="ltxeDueWrap">
                        <label for="txn_due_date">Due date <span>For the pending amount</span></label>
                        <div class="lef-in"><x-lb-icon name="calendar-clock" /><input type="date" name="due_date" id="txn_due_date" class="@error('due_date') is-invalid @enderror" value="{{ old('due_date', $dateInput($transaction['due_date'] ?? '')) }}"></div>
                        @error('due_date') <p class="lef-err">{{ $message }}</p> @enderror
                    </div>
                </div>
            </section>

            @if(!empty($transaction['updated_by_name']) || !empty($transaction['updated_date']))
                <p class="ltxe-meta"><x-lb-icon name="info" />Last changed {{ $dateFmt($transaction['updated_date'] ?? null) }} by {{ $transaction['updated_by_name'] ?: ($transaction['added_by_name'] ?? 'Admin') }}</p>
            @endif
        </div>

        <footer class="lsw-foot">
            <div class="lsw-fee">
                <span>Paid now</span>
                <strong id="ltxeFootPaid">{{ $inr(old('paid_amount', $transaction['total_paid_amount'] ?? 0)) }}</strong>
            </div>
            <a href="{{ $teBack }}" class="lsw-btn-ghost">Cancel</a>
            <button type="submit" class="lsw-btn-primary" id="ltxeSave"><x-lb-icon name="check" /><span>Save changes</span></button>
        </footer>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var form = document.getElementById('ltxeForm');
        var paid = document.getElementById('txn_paid_amount');
        if (!form || !paid) return;

        var currentPaid = parseFloat(paid.dataset.currentPaid) || 0;
        var currentPending = parseFloat(paid.dataset.currentPending) || 0;
        var total = parseFloat(paid.dataset.total) || 0;
        var bal = document.getElementById('ltxeBal');
        var balLabel = document.getElementById('ltxeBalLabel');
        var balVal = document.getElementById('ltxeBalVal');
        var meter = document.getElementById('ltxeMeter');
        var over = document.getElementById('ltxeOver');
        var footPaid = document.getElementById('ltxeFootPaid');
        var save = document.getElementById('ltxeSave');
        var laterHint = document.getElementById('ltxeLaterHint');
        var amtBox = paid.closest('.ltxe-amt');
        var inr = function (n) { return '₹' + Math.round(n).toLocaleString('en-IN'); };

        function mode() {
            var on = form.querySelector('input[name="payment_mode"]:checked');
            return on ? on.value : '1';
        }

        function recalc() {
            var isLater = mode() === '3';
            var v = parseFloat(paid.value);
            var newPaid = isLater ? 0 : (isNaN(v) ? currentPaid : v);
            // Same formula as LearnerLifecycleService::updateTransaction(): anything below zero is extra.
            var raw = isLater ? currentPending + currentPaid : currentPending + currentPaid - newPaid;
            var tooMuch = !isLater && newPaid > total;

            paid.disabled = isLater;
            amtBox.classList.toggle('is-off', isLater);
            amtBox.classList.toggle('is-bad', tooMuch);
            over.hidden = !tooMuch;
            save.disabled = tooMuch;
            if (laterHint) laterHint.hidden = !isLater;

            bal.dataset.state = raw > 0 ? 'pending' : (raw < 0 ? 'extra' : 'clear');
            balLabel.textContent = raw > 0 ? 'Pending' : (raw < 0 ? 'Extra paid' : 'Fully paid');
            balVal.textContent = inr(Math.abs(raw));
            meter.style.width = (total > 0 ? Math.max(0, Math.min(100, newPaid / total * 100)) : 0) + '%';
            footPaid.textContent = inr(newPaid);
        }

        paid.addEventListener('input', recalc);
        form.querySelectorAll('input[name="payment_mode"]').forEach(function (r) { r.addEventListener('change', recalc); });
        document.getElementById('ltxeFull').addEventListener('click', function () {
            paid.value = this.dataset.full;
            recalc();
            paid.focus();
        });
        form.addEventListener('submit', function () {
            save.classList.add('is-busy');
        });
        recalc();
    });
</script>

@endsection
