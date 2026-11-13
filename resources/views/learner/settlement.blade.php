@extends('layouts.library')

@section('content')
@php
    $planEndDate = $customer->plan_end_date ?? null;
    if ($planEndDate) {
        $today = \Carbon\Carbon::today();
        $diffInDays = $today->diffInDays(\Carbon\Carbon::parse($planEndDate), false);
        $diffExtendDay = $today->diffInDays(\Carbon\Carbon::parse($planEndDate)->addDays((int) getExtendDays()), false);
        if ($diffInDays < 0 && $diffExtendDay < 0) { $statusText = 'Expired ' . abs($diffInDays) . ' days ago'; $statusClass = 'status-expired'; }
        elseif ($diffInDays < 0) { $statusText = 'In extension'; $statusClass = 'status-warning'; }
        elseif ($diffInDays <= 5) { $statusText = $diffInDays == 0 ? 'Expires today' : 'Expires in ' . $diffInDays . ' day' . ($diffInDays == 1 ? '' : 's'); $statusClass = 'status-warning'; }
        else { $statusText = 'Active'; $statusClass = 'status-active'; }
    } else {
        $statusText = 'Active'; $statusClass = 'status-active';
    }

    $inr = fn ($v) => '₹' . number_format((float) $v, 0);
    $case = $netPending > 0 ? 'pending' : ($netExtra > 0 ? 'extra' : 'settled');
    $net = $case === 'pending' ? $netPending : ($case === 'extra' ? $netExtra : 0);
    $first = \Illuminate\Support\Str::of($learner->name ?? 'Learner')->explode(' ')->first();

    $opIcon = 'scale';
    $opKicker = 'Settlement';
    $opTitle = "Settle :name's account";
@endphp

<link rel="stylesheet" href="{{ asset('public/css/lb-swap.css') }}?v={{ @filemtime(public_path('css/lb-swap.css')) }}" />
<link rel="stylesheet" href="{{ asset('public/css/lb-opform.css') }}?v={{ @filemtime(public_path('css/lb-opform.css')) }}" />
<link rel="stylesheet" href="{{ asset('public/css/lb-opplan.css') }}?v={{ @filemtime(public_path('css/lb-opplan.css')) }}" />
<link rel="stylesheet" href="{{ asset('public/css/lb-opui.css') }}?v={{ @filemtime(public_path('css/lb-opui.css')) }}" />
<link rel="stylesheet" href="{{ asset('public/css/lb-settle.css') }}?v={{ @filemtime(public_path('css/lb-settle.css')) }}" />

<div class="lsw lst lou" id="operationContent" data-case="{{ $case }}" data-net="{{ (int) round($net) }}">
    <div class="lsw-sheet">
        @include('learner.partials.op-head')

        <div class="lsw-body">

            {{-- 1. Balance --}}
            <section class="lst-bal lst-bal--{{ $case }}">
                <span class="lst-bal-ic"><x-lb-icon :name="$case === 'pending' ? 'hand-coins' : ($case === 'extra' ? 'rotate-ccw' : 'badge-check')" /></span>
                <div class="lst-bal-t">
                    @if($case === 'pending')
                        <span>Pending due from {{ $first }}</span>
                        <b>{{ $inr($net) }}</b>
                        <small>Take the payment now, keep it to pay later, or waive it off.</small>
                    @elseif($case === 'extra')
                        <span>Refund due to {{ $first }}</span>
                        <b>{{ $inr($net) }}</b>
                        <small>{{ $first }} paid more than the plan price. Refund it now, refund it later, or waive it off.</small>
                    @else
                        <span>Account is settled</span>
                        <b>{{ $inr(0) }}</b>
                        <small>No pending amount and no extra amount. Nothing to do here.</small>
                    @endif
                </div>
            </section>

            {{-- 2. How to settle --}}
            @if($case !== 'settled')
            <section class="lef-sec lst-how">
                <div class="lef-sec-h">
                    <span class="lef-sec-ic {{ $case === 'pending' ? 'is-amber' : 'is-green' }}"><x-lb-icon name="sliders-horizontal" /></span>
                    <div><h4>How do you want to settle?</h4><p>Pick one</p></div>
                </div>

                <div class="lst-opts" role="radiogroup" aria-label="Settlement method">
                    <button type="button" class="lst-opt is-on" data-method="money" role="radio" aria-checked="true">
                        <span class="lst-opt-ic"><x-lb-icon :name="$case === 'pending' ? 'banknote' : 'rotate-ccw'" /></span>
                        <span class="lst-opt-t"><b>{{ $case === 'pending' ? 'Pay now' : 'Refund now' }}</b><small>{{ $case === 'pending' ? 'Record the payment received from ' . $first : 'Record the refund given to ' . $first }}</small></span>
                        <i class="lst-radio"></i>
                    </button>
                    <button type="button" class="lst-opt" data-method="adjust" role="radio" aria-checked="false">
                        <span class="lst-opt-ic"><x-lb-icon name="scale" /></span>
                        <span class="lst-opt-t"><b>Waive off</b><small>{{ $case === 'pending' ? 'Close the due without any payment' : 'Close the extra amount without a refund' }}</small></span>
                        <i class="lst-radio"></i>
                    </button>
                </div>

                <div class="lst-money" data-money>
                    <label class="lst-lbl" for="lstAmount">{{ $case === 'pending' ? 'Amount received' : 'Amount refunded' }}</label>
                    <div class="lst-amt">
                        <span>₹</span>
                        <input type="text" id="lstAmount" inputmode="numeric" autocomplete="off" value="{{ (int) round($net) }}" aria-describedby="lstAmtHint">
                        <button type="button" class="lst-full" data-full>Full {{ $inr($net) }}</button>
                    </div>
                    <div class="lst-err" id="lstAmtErr" hidden></div>

                    <div class="lst-rest" data-rest hidden>
                        <span class="lst-lbl">What about the remaining <b data-rest-amt>₹0</b>?</span>
                        <select class="form-select lst-select" id="lstRest" aria-label="Remaining amount">
                            <option value="keep" selected>{{ $case === 'pending' ? 'Pay later' : 'Refund later' }}</option>
                            <option value="adjust">Waive off</option>
                        </select>
                    </div>

                    <label class="lst-lbl" for="lstMode">Payment mode</label>
                    <select class="form-select lst-select" id="lstMode">
                        <option value="">Select payment mode</option>
                        <option value="1">Online</option>
                        <option value="2">Cash</option>
                    </select>
                    <div class="lst-err" id="lstModeErr" hidden>Choose how the money was {{ $case === 'pending' ? 'received' : 'paid' }}.</div>
                </div>

                <div class="lsw-tip lst-note" id="lstAmtHint"><x-lb-icon name="info" /><span data-hint></span></div>
            </section>
            @endif

            {{-- 3. Breakdown (read only) --}}
            @if(count($detailRows))
            <details class="lst-break" {{ $case === 'settled' ? 'open' : '' }}>
                <summary>
                    <span class="lef-sec-ic is-blue"><x-lb-icon name="receipt-text" /></span>
                    <span class="lst-break-t"><b>Plan-wise breakdown</b><small>{{ count($detailRows) }} plan{{ count($detailRows) == 1 ? '' : 's' }} · settlement covers the whole account</small></span>
                    <x-lb-icon name="chevron-down" class="lst-chev" />
                </summary>
                <ul class="lst-plans">
                    @foreach($detailRows as $row)
                    @php
                        $seat = $row['seat_no'] && $row['seat_no'] !== 'GEN' ? (getSeatDisplayShortFloorName($row['seat_no']) ?? $row['seat_no']) : 'General';
                        $fmt = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('d M Y') : '—';
                    @endphp
                    <li>
                        <div class="lst-plan-top">
                            <span class="lst-plan-n"><b>{{ $row['plan_name'] }}</b> · {{ $row['plan_type_name'] }}</span>
                            @if($row['pending_amount'] > 0)
                                <span class="lst-tag is-bad">{{ $inr($row['pending_amount']) }} due</span>
                            @elseif($row['extra_amount'] > 0)
                                <span class="lst-tag is-ok">{{ $inr($row['extra_amount']) }} extra</span>
                            @else
                                <span class="lst-tag">Settled</span>
                            @endif
                        </div>
                        <small class="lst-plan-meta"><x-lb-icon name="armchair" />{{ $seat }} · {{ $fmt($row['plan_start_date']) }} → {{ $fmt($row['plan_end_date']) }}</small>
                        <div class="lst-plan-nums">
                            <span><small>Plan price</small><b>{{ $inr($row['total_amount'] ?: $row['paid_amount']) }}</b></span>
                            <span><small>Paid</small><b>{{ $inr($row['paid_amount']) }}</b></span>
                            <span><small>Due</small><b class="{{ $row['pending_amount'] > 0 ? 'is-bad' : '' }}">{{ $inr($row['pending_amount']) }}</b></span>
                            <span><small>Extra</small><b class="{{ $row['extra_amount'] > 0 ? 'is-ok' : '' }}">{{ $inr($row['extra_amount']) }}</b></span>
                        </div>
                    </li>
                    @endforeach
                </ul>
            </details>
            @endif
        </div>

        <footer class="lsw-foot">
            <div class="lsw-fee">
                <span data-foot-k>{{ $case === 'pending' ? 'Pay now' : ($case === 'extra' ? 'Refund now' : 'Balance') }}</span>
                <strong data-foot-v>{{ $inr($net) }}</strong>
            </div>
            <a href="{{ route('learners') }}" class="lsw-btn-ghost">Cancel</a>
            @if($case === 'settled')
            <a href="{{ route('learners') }}" class="lsw-btn-primary"><x-lb-icon name="check" /><span>Done</span></a>
            @else
            <button type="button" class="lsw-btn-primary no-auto-enable" id="lstSubmit"><x-lb-icon name="check" /><span data-btn>Settle</span></button>
            @endif
        </footer>
    </div>
</div>

@if($case !== 'settled')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var root = document.getElementById('operationContent');
    var kind = root.dataset.case, net = Number(root.dataset.net) || 0, isDue = kind === 'pending';
    var st = { method: 'money', mode: '', rest: 'keep' };
    var one = function (s) { return root.querySelector(s); }, all = function (s) { return root.querySelectorAll(s); };
    var amtIn = one('#lstAmount'), inr = function (n) { return '₹' + Math.round(n).toLocaleString('en-IN'); };
    var first = @json((string) $first);

    function amount() { return Number(String(amtIn.value).replace(/\D/g, '')) || 0; }

    function render() {
        var money = st.method === 'money', a = Math.min(amount(), net), rest = net - a;
        all('.lst-opt').forEach(function (b) { var on = b.dataset.method === st.method; b.classList.toggle('is-on', on); b.setAttribute('aria-checked', on); });
        one('[data-money]').hidden = !money;
        one('[data-rest]').hidden = !(money && a > 0 && rest > 0);
        one('[data-rest-amt]').textContent = inr(rest);
        one('#lstRest').value = st.rest;
        one('#lstMode').value = st.mode || '';

        var hint, footK, footV, btn;
        if (!money) {
            hint = isDue ? 'The due of ' + inr(net) + ' will be waived off. No payment is recorded.' : 'The extra amount of ' + inr(net) + ' will be waived off. No refund is recorded.';
            footK = 'Waive off'; footV = inr(net); btn = 'Waive off ' + inr(net);
        } else {
            var restTxt = rest <= 0 ? 'The account will be fully settled.'
                : st.rest === 'keep' ? inr(rest) + (isDue ? ' will be paid later.' : ' will be refunded later.')
                : inr(rest) + ' will be waived off.';
            hint = (isDue ? inr(a) + ' is paid now by ' + first + '. ' : inr(a) + ' is refunded now to ' + first + '. ') + restTxt;
            footK = isDue ? 'Pay now' : 'Refund now'; footV = inr(a); btn = isDue ? 'Confirm payment ' + inr(a) : 'Confirm refund ' + inr(a);
        }
        one('[data-hint]').textContent = hint;
        one('[data-foot-k]').textContent = footK;
        one('[data-foot-v]').textContent = footV;
        one('[data-btn]').textContent = btn;
    }

    function err(id, msg) { var e = document.getElementById(id); e.hidden = !msg; if (msg) e.textContent = msg; }

    root.addEventListener('click', function (e) {
        var t = e.target.closest('[data-method],[data-full]');
        if (!t) return;
        if (t.dataset.method) st.method = t.dataset.method;
        else { amtIn.value = net; err('lstAmtErr', ''); }
        render();
    });
    one('#lstMode').addEventListener('change', function () { st.mode = this.value; err('lstModeErr', ''); render(); });
    one('#lstRest').addEventListener('change', function () { st.rest = this.value; render(); });
    amtIn.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 7);
        err('lstAmtErr', amount() > net ? 'This is more than ' + inr(net) + '.' : '');
        render();
    });

    document.getElementById('lstSubmit').addEventListener('click', function () {
        var money = st.method === 'money', a = amount();
        if (money) {
            if (!a) return err('lstAmtErr', 'Enter an amount, or choose “Waive off”.'), amtIn.focus();
            if (a > net) return err('lstAmtErr', 'This is more than ' + inr(net) + '.'), amtIn.focus();
            if (!st.mode) { err('lstModeErr', one('#lstModeErr').textContent); return one('#lstMode').focus(); }
        }
        var adjust = money ? (a < net && st.rest === 'adjust' ? 1 : 0) : 1;
        var btn = this;
        btn.classList.add('is-busy'); btn.disabled = true;
        $.ajax({
            url: @json(route('learners.settlement', $learner->id)),
            type: 'POST',
            data: {
                _token: @json(csrf_token()),
                learner_id: {{ (int) $learner->id }},
                adjust: adjust,
                pending_amount: money && isDue ? a : 0,
                pay_amount: money && isDue ? a : 0,
                refund_amount: money && !isDue ? a : 0,
                payment_mode: money ? st.mode : '',
                remark: ''
            },
            success: function (r) {
                Swal.fire({ icon: 'success', title: 'Settled', text: (r && r.message) || 'Settlement saved.', confirmButtonText: 'Done', confirmButtonColor: '#1d3c86' })
                    .then(function () { window.location.href = @json(route('learners')); });
            },
            error: function (x) {
                btn.classList.remove('is-busy'); btn.disabled = false;
                var j = x && x.responseJSON;
                Swal.fire({ icon: 'error', title: 'Could not settle', text: (j && (j.message || j.error)) || 'Please try again.', confirmButtonColor: '#1d3c86' });
            }
        });
    });

    render();
});
</script>
@endif
@endsection
