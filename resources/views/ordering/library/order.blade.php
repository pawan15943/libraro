@extends('layouts.library')

@section('title', 'Order details')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-opform.css') }}?v={{ @filemtime(public_path('css/lb-opform.css')) }}">
<link rel="stylesheet" href="{{ asset('public/css/lb-pages.css') }}?v={{ @filemtime(public_path('css/lb-pages.css')) }}">
<link rel="stylesheet" href="{{ asset('public/css/lb-ordering.css') }}?v={{ @filemtime(public_path('css/lb-ordering.css')) }}">
@endpush

@section('content')
@php
    use App\Models\Ordering\Order;
    use App\Models\Ordering\OrderLine;
    $inr = fn ($n) => '₹' . number_format((float) $n, (float) $n == floor((float) $n) ? 0 : 2);
    $short = \Illuminate\Support\Str::afterLast($order->order_no, '-');
    $methodTxt = ['online' => 'Online (UPI QR)', 'offline' => 'Pay at counter', 'pay_later' => 'Pay later'];
    $payMethod = ['upi_qr' => 'UPI QR (learner)', 'cash' => 'Cash', 'upi_counter' => 'UPI at counter', 'card' => 'Card', 'other' => 'Other'];
    $lineTxt = [OrderLine::PENDING => ['', 'To prepare'], OrderLine::FULFILLED => ['is-ok', 'Done'], OrderLine::UNAVAILABLE => ['is-bad', 'Unavailable'],
        OrderLine::REPLACEMENT_PENDING => ['is-acc', 'Waiting for learner'], OrderLine::REPLACED => ['', 'Replaced'], OrderLine::REMOVED => ['is-bad', 'Removed']];
    $statusTone = in_array($order->status, [Order::CANCELLED, Order::REJECTED, Order::ATTENTION]) ? 'is-bad' : (in_array($order->status, [Order::COMPLETED, Order::DELIVERED, Order::COLLECTED]) ? 'is-ok' : 'is-acc');
    $actor = function ($a) use ($staffNames, $learner) {
        return match ($a->actor_type) {
            'learner' => ($learner->name ?? 'Learner') . ' (learner)',
            'library' => 'Owner',
            'library_user' => ($staffNames[$a->actor_id] ?? 'Staff') . ' (staff)',
            default => 'System',
        };
    };
    $events = ['placed' => 'Order placed', 'opened' => 'Opened by staff', 'accepted' => 'Accepted', 'preparing' => 'Preparation started', 'ready' => 'Ready for pickup',
        'out_for_delivery' => 'Out for delivery', 'delivery_attempted' => 'Learner not at seat', 'delivered' => 'Delivered', 'collected' => 'Collected', 'completed' => 'Closed',
        'rejected' => 'Rejected', 'cancelled_by_staff' => 'Cancelled by library', 'cancelled_by_learner' => 'Cancelled by learner', 'status_corrected' => 'Status corrected',
        'payment_proof_sent' => 'Payment proof sent', 'payment_approved' => 'Payment approved', 'payment_rejected' => 'Payment proof rejected',
        'payment_recorded' => 'Payment taken', 'refund_recorded' => 'Refund given'];
    $due = $order->dueAmount();
    $closed = in_array($order->status, [Order::CANCELLED, Order::REJECTED], true);
@endphp

<div class="lpg od ob-order">
    <header class="lpg-head">
        <div class="lpg-head-t">
            <p><b>Order #{{ $short }}</b> · {{ $order->order_no }} · placed {{ $order->created_at->format('j M, g:i A') }} ({{ $order->created_at->diffForHumans() }})</p>
        </div>
        <div class="lpg-head-r">
            <a class="lp-btn lp-btn--ghost" href="{{ route('orders.board') }}"><x-lb-icon name="arrow-left" />Order Board</a>
        </div>
    </header>

    @if ($errors->any())<div class="lef-alert" role="alert">{{ $errors->first() }}</div>@endif

    <div class="ob-cols">
        <div class="ob-col">
            {{-- status + next step --}}
            <section class="od-card ob-status">
                <div class="ob-status-h">
                    <span class="lpg-tag {{ $statusTone }}">{{ $order->statusLabel() }}</span>
                    <span class="ob-where">@switch($order->mode)@case('seat')<x-lb-icon name="armchair" />@break @case('table')<x-lb-icon name="table" />@break @default<x-lb-icon name="hand-coins" />@endswitch{{ $order->locationLabel() }}</span>
                </div>
                @if ($order->reject_reason || $order->cancel_reason)
                    <p class="ob-reason"><x-lb-icon name="circle-x" />{{ $order->reject_reason ?: $order->cancel_reason }}</p>
                @endif
                @if ($order->status === Order::REPLACEMENT_PENDING)
                    <p class="od-warn"><x-lb-icon name="hourglass" />Waiting for the learner to answer the replacement.</p>
                @endif

                @php $moves = collect($actions)->whereNotIn('key', ['reject', 'cancel', 'undo']); @endphp
                @if ($moves->isNotEmpty())
                    <div class="ob-steps">
                        @foreach ($moves as $a)
                            <form method="POST" action="{{ route('orders.act', $order->id) }}">@csrf
                                <input type="hidden" name="action" value="{{ $a['key'] }}"><input type="hidden" name="v" value="{{ $order->version }}">
                                <button class="lp-btn {{ $a['tone'] === 'ghost' ? 'lp-btn--ghost' : '' }}"><x-lb-icon name="check" />{{ $a['label'] }}</button>
                            </form>
                        @endforeach
                    </div>
                @elseif ($order->isOpen())
                    <p class="od-none">Nothing to do right now.</p>
                @endif
                @if ($order->status === Order::DELIVERED || $order->status === Order::COLLECTED)
                    @if ($due > 0)<p class="od-warn"><x-lb-icon name="wallet" />Handed over. It closes by itself once {{ $inr($due) }} is paid.</p>@endif
                @endif

                @foreach (collect($actions)->whereIn('key', ['reject', 'cancel', 'undo']) as $a)
                    <details class="ob-reason-form" @if ($errors->has('reason')) open @endif>
                        <summary class="{{ $a['key'] === 'undo' ? '' : 'is-bad' }}"><x-lb-icon :name="$a['key'] === 'undo' ? 'rotate-ccw' : 'circle-x'" />{{ $a['label'] }}</summary>
                        <form method="POST" action="{{ route('orders.act', $order->id) }}">@csrf
                            <input type="hidden" name="action" value="{{ $a['key'] }}"><input type="hidden" name="v" value="{{ $order->version }}">
                            <div class="lef-f">
                                <label for="obReason{{ $a['key'] }}">{{ $a['key'] === 'undo' ? 'Why correct the status?' : 'Reason (the learner sees this)' }} <i>*</i></label>
                                <div class="lef-in"><x-lb-icon name="message-square" /><input type="text" id="obReason{{ $a['key'] }}" name="reason" maxlength="255" required placeholder="{{ $a['key'] === 'reject' ? 'e.g. Kitchen closed for today' : ($a['key'] === 'undo' ? 'e.g. Tapped Delivered by mistake' : 'e.g. Learner asked to cancel') }}"></div>
                            </div>
                            <button class="lp-btn {{ $a['key'] === 'undo' ? 'lp-btn--ghost' : 'ob-btn-bad' }}">{{ $a['label'] }}</button>
                        </form>
                    </details>
                @endforeach
                @if ($order->isOpen() && ! $isOwner && collect($actions)->where('key', 'cancel')->isEmpty() && in_array($order->status, \App\Services\Ordering\OrderStatusService::STARTED))
                    <p class="lef-hint">Preparation has started: only the library owner can cancel now.</p>
                @endif
            </section>

            {{-- items --}}
            <section class="od-card">
                <h3><x-lb-icon name="list" />Items</h3>
                <ul class="ob-lines">
                    @foreach ($order->lines as $l)
                        <li class="{{ in_array($l->fulfilment_status, [OrderLine::REMOVED, OrderLine::REPLACED]) ? 'is-gone' : '' }}">
                            <div>
                                <b>{{ $l->quantity }} × {{ $l->item_name }}</b>
                                <small>{{ $l->category_name }} · {{ $inr($l->unit_price) }}
                                    @if ($l->options) · {{ $l->options['pages'] ?? $l->quantity }} pages × {{ $l->options['copies'] ?? 1 }} {{ \Illuminate\Support\Str::plural('copy', $l->options['copies'] ?? 1) }}, {{ ($l->options['sides'] ?? 'single') === 'double' ? 'both sides' : 'one side' }}@endif
                                </small>
                                @if ($l->note)<small class="ob-lnote">{{ $l->note }}</small>@endif
                                @if ($l->upload_path)
                                    <a class="ob-file" href="{{ route('orders.file', [$order->id, $l->id]) }}"><x-lb-icon name="download" />{{ $l->upload_name ?: 'Download file' }}</a>
                                @endif
                            </div>
                            <span class="lpg-tag {{ $lineTxt[$l->fulfilment_status][0] ?? '' }}">{{ $lineTxt[$l->fulfilment_status][1] ?? $l->fulfilment_status }}</span>
                            <b class="ob-amt">{{ $inr($l->line_total) }}</b>
                        </li>
                    @endforeach
                </ul>
                <dl class="ob-sum">
                    <div><dt>Items</dt><dd>{{ $inr($order->subtotal) }}</dd></div>
                    @if ((float) $order->delivery_charge > 0)<div><dt>Delivery</dt><dd>{{ $inr($order->delivery_charge) }}</dd></div>@endif
                    <div class="is-total"><dt>Total</dt><dd>{{ $inr($order->total) }}</dd></div>
                </dl>
                @if ($order->note)<p class="ob-note"><x-lb-icon name="message-square" />{{ $order->note }}</p>@endif
                @unless ($order->allow_partial)<p class="lef-hint">The learner wants everything together (no partial delivery).</p>@endunless
            </section>
        </div>

        <div class="ob-col">
            {{-- learner --}}
            <section class="od-card">
                <h3><x-lb-icon name="user-round" />Learner</h3>
                <div class="ob-learner">
                    <b>{{ $learner->name ?? 'Learner' }}</b>
                    <small>{{ $learner->learner_no ?? '' }}@if ($learner && $learner->seat_no) · Booked seat {{ $learner->seat_no }}@endif</small>
                    @if ($learner && $learner->mobile)
                        <a class="lp-btn lp-btn--ghost" href="tel:{{ $learner->mobile }}"><x-lb-icon name="phone" />Call {{ $learner->mobile }}</a>
                    @endif
                </div>
            </section>

            {{-- payment --}}
            <section class="od-card">
                <h3><x-lb-icon name="wallet" />Payment <span class="lpg-tag {{ $order->payment_status === Order::PAY_PAID ? 'is-ok' : (in_array($order->payment_status, [Order::PAY_PROCESSING, Order::PAY_REFUND_PENDING]) ? 'is-acc' : '') }}">{{ $order->paymentLabel() }}</span></h3>
                <dl class="ob-sum is-top">
                    <div><dt>Method</dt><dd>{{ $methodTxt[$order->payment_method] }}</dd></div>
                    <div><dt>Total</dt><dd>{{ $inr($order->total) }}</dd></div>
                    <div><dt>Received</dt><dd>{{ $inr($order->paid_amount) }}</dd></div>
                    @if ((float) $order->refunded_amount > 0)<div><dt>Refunded</dt><dd>{{ $inr($order->refunded_amount) }}</dd></div>@endif
                    @if (! $closed)<div class="is-total"><dt>To collect</dt><dd>{{ $inr($due) }}</dd></div>@endif
                    @if ($refundable > 0)<div class="is-total is-bad"><dt>To refund</dt><dd>{{ $inr($refundable) }}</dd></div>@endif
                </dl>

                {{-- learner's UPI proofs --}}
                @foreach ($order->payments->where('kind', 'payment')->where('method', 'upi_qr') as $p)
                    <div class="ob-proof {{ $p->status === 'pending' ? 'is-pending' : '' }}">
                        @if ($p->screenshot)
                            <a href="{{ route('orders.payment.proof', [$order->id, $p->id]) }}" target="_blank" rel="noopener" class="ob-proof-img"><img src="{{ route('orders.payment.proof', [$order->id, $p->id]) }}" alt="Payment screenshot" loading="lazy"></a>
                        @endif
                        <div class="ob-proof-t">
                            <b>{{ $inr($p->amount) }} · UPI screenshot</b>
                            <small>{{ $p->created_at->format('j M, g:i A') }}@if ($p->txn_ref) · UTR {{ $p->txn_ref }}@endif</small>
                            <span class="lpg-tag {{ $p->status === 'verified' ? 'is-ok' : ($p->status === 'failed' ? 'is-bad' : 'is-acc') }}">{{ ['pending' => 'Check it', 'verified' => 'Approved', 'failed' => 'Rejected'][$p->status] }}</span>
                            @if ($p->status === 'pending')
                                <p class="lef-hint">Match the amount and UTR with your bank / UPI app before approving.</p>
                                <div class="ob-proof-a">
                                    <form method="POST" action="{{ route('orders.payment.review', [$order->id, $p->id]) }}">@csrf<input type="hidden" name="decision" value="approve"><button class="lp-btn"><x-lb-icon name="check" />Approve</button></form>
                                    <form method="POST" action="{{ route('orders.payment.review', [$order->id, $p->id]) }}" data-ob-reject>@csrf<input type="hidden" name="decision" value="reject"><input type="hidden" name="note" value=""><button class="lp-btn lp-btn--ghost">Not received</button></form>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach

                @if (! $closed && $due > 0)
                    <details class="ob-money" @if ($errors->hasAny(['amount', 'method', 'ref'])) open @endif>
                        <summary><x-lb-icon name="banknote" />Take payment at the counter</summary>
                        <form method="POST" action="{{ route('orders.payment.store', $order->id) }}">@csrf
                            <div class="lef-grid">
                                <div class="lef-f"><label for="obAmt">Amount <i>*</i></label><div class="lef-in"><span class="lef-pre">₹</span><input type="number" id="obAmt" name="amount" min="0.01" max="{{ $due }}" step="any" value="{{ old('amount', $due) }}" required></div></div>
                                <div class="lef-f"><label for="obMethod">Paid by <i>*</i></label><div class="lef-in"><x-lb-icon name="wallet" /><select id="obMethod" name="method">@foreach ($methods as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div></div>
                            </div>
                            <div class="lef-f od-mt"><label for="obRef">Reference / UTR</label><div class="lef-in"><x-lb-icon name="hash" /><input type="text" id="obRef" name="ref" maxlength="100" value="{{ old('ref') }}" placeholder="Optional"></div></div>
                            <button class="lp-btn od-mt"><x-lb-icon name="check" />Mark {{ $inr($due) }} received</button>
                        </form>
                    </details>
                @endif

                @if ($refundable > 0)
                    <details class="ob-money is-bad" open>
                        <summary><x-lb-icon name="rotate-ccw" />Give back {{ $inr($refundable) }}</summary>
                        <form method="POST" action="{{ route('orders.refund.store', $order->id) }}">@csrf
                            <div class="lef-grid">
                                <div class="lef-f"><label for="obRAmt">Amount <i>*</i></label><div class="lef-in"><span class="lef-pre">₹</span><input type="number" id="obRAmt" name="amount" min="0.01" max="{{ $refundable }}" step="any" value="{{ $refundable }}" required></div></div>
                                <div class="lef-f"><label for="obRMethod">Given by <i>*</i></label><div class="lef-in"><x-lb-icon name="wallet" /><select id="obRMethod" name="method">@foreach ($methods as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div></div>
                            </div>
                            <div class="lef-f od-mt"><label for="obRRef">Reference / UTR</label><div class="lef-in"><x-lb-icon name="hash" /><input type="text" id="obRRef" name="ref" maxlength="100" placeholder="Optional"></div></div>
                            <button class="lp-btn od-mt"><x-lb-icon name="check" />Mark refund given</button>
                        </form>
                    </details>
                @endif

                @php $ledger = $order->payments->where('status', 'verified'); @endphp
                @if ($ledger->isNotEmpty())
                    <ul class="ob-ledger">
                        @foreach ($ledger as $p)
                            <li><span>{{ $p->kind === 'refund' ? 'Refund' : 'Received' }} · {{ $payMethod[$p->method] ?? $p->method }}@if ($p->txn_ref) · {{ $p->txn_ref }}@endif<small>{{ optional($p->verified_at ?: $p->created_at)->format('j M, g:i A') }}</small></span><b class="{{ $p->kind === 'refund' ? 'is-bad' : '' }}">{{ $p->kind === 'refund' ? '−' : '' }}{{ $inr($p->amount) }}</b></li>
                        @endforeach
                    </ul>
                @endif
            </section>

            {{-- history --}}
            <section class="od-card">
                <h3><x-lb-icon name="activity" />History</h3>
                <ol class="ob-log">
                    @foreach ($order->activities->sortByDesc('id') as $a)
                        <li>
                            <b>{{ $events[$a->action] ?? ucfirst(str_replace('_', ' ', $a->action)) }}</b>
                            <small>{{ $a->created_at->format('j M, g:i:s A') }} · {{ $actor($a) }}</small>
                            @if (! empty($a->meta['reason']) || ! empty($a->meta['note']))<em>{{ $a->meta['reason'] ?? $a->meta['note'] }}</em>@endif
                            @if (! empty($a->meta['amount']) && str_starts_with($a->action, 'payment') || ! empty($a->meta['amount']) && str_starts_with($a->action, 'refund'))<em>{{ $inr($a->meta['amount']) }}</em>@endif
                        </li>
                    @endforeach
                </ol>
            </section>
        </div>
    </div>
</div>

<script>
(function () {
    document.querySelectorAll('[data-ob-reject]').forEach(function (f) {
        f.addEventListener('submit', function (e) {
            e.preventDefault();
            var why = window.prompt('Why is this payment not accepted? (the learner can pay again)', 'Amount not received');
            if (why === null) return;
            f.querySelector('[name="note"]').value = why;
            f.submit();
        });
    });
    // forms submit once
    document.querySelectorAll('.ob-order form').forEach(function (f) {
        f.addEventListener('submit', function () { var b = f.querySelector('button'); if (b) { setTimeout(function () { b.disabled = true; }, 0); } });
    });
})();
</script>
@endsection
