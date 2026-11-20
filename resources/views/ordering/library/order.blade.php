@extends('layouts.library')

@section('title', 'Order details')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-opform.css') }}?v={{ @filemtime(public_path('css/lb-opform.css')) }}">
<link rel="stylesheet" href="{{ asset('public/css/lb-pages.css') }}?v={{ @filemtime(public_path('css/lb-pages.css')) }}">
<link rel="stylesheet" href="{{ asset('public/css/lb-ordering.css') }}?v={{ @filemtime(public_path('css/lb-ordering.css')) }}">
@endpush

@section('content')
@include('ordering.partials.dialog')
@php
    use App\Models\Ordering\Order;
    use App\Models\Ordering\OrderLine;
    $inr = fn ($n) => '₹' . number_format((float) $n, (float) $n == floor((float) $n) ? 0 : 2);
    $short = \Illuminate\Support\Str::afterLast($order->order_no, '-');
    $methodTxt = ['online' => 'Online (UPI QR)', 'offline' => 'Pay at counter', 'pay_later' => 'Pay later'];
    $payMethod = ['upi_qr' => 'UPI QR (learner)', 'cash' => 'Cash', 'upi_counter' => 'UPI at counter', 'card' => 'Card', 'other' => 'Other'];
    // a line only gets a tag once something happened to it ("to prepare" on every row is noise)
    $lineTxt = [OrderLine::FULFILLED => ['is-ok', 'Done'], OrderLine::UNAVAILABLE => ['is-bad', 'Unavailable'],
        OrderLine::REPLACEMENT_PENDING => ['is-warn', 'Waiting for learner'], OrderLine::REPLACED => ['', 'Replaced'], OrderLine::REMOVED => ['is-bad', 'Removed']];
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
        'payment_recorded' => 'Payment taken', 'refund_recorded' => 'Refund given',
        'replacement_requested' => 'Item ran out — learner asked', 'replacement_answered' => 'Learner answered the replacement', 'replacement_timeout' => 'No answer in time',
        'item_removed_by_staff' => 'Item removed by staff', 'staff_reminded' => 'Staff reminded', 'escalated' => 'Waiting too long — owner alerted', 'cancelled_no_items' => 'Cancelled: nothing available'];
    $due = $order->dueAmount();
    $closed = in_array($order->status, [Order::CANCELLED, Order::REJECTED], true);
    $pendingRepl = $order->replacements->where('status', 'pending');

    // progress steps: [label, activity keys that mark it done]
    $pickup = $order->mode === 'counter';
    $steps = [
        ['Placed', ['placed']],
        ['Accepted', ['accepted']],
        ['Preparing', ['preparing']],
        $pickup ? ['Ready', ['ready']] : ['On the way', ['out_for_delivery']],
        $pickup ? ['Collected', ['collected']] : ['Delivered', ['delivered']],
    ];
    $reached = match ($order->status) {
        Order::CONFIRMED, Order::REPLACEMENT_PENDING => 1,
        Order::PREPARING => 2,
        Order::READY, Order::OUT_FOR_DELIVERY, Order::DELIVERY_ATTEMPTED => 3,
        Order::DELIVERED, Order::COLLECTED, Order::COMPLETED => 5, // handed over: every step is done
        default => 0,
    };
    $stepTime = fn ($keys) => optional($order->activities->whereIn('action', $keys)->sortByDesc('id')->first())->created_at;

    $moves = collect($actions)->whereNotIn('key', ['reject', 'cancel', 'undo']);
    $dangers = collect($actions)->whereIn('key', ['reject', 'cancel', 'undo']);
    $initials = collect(preg_split('/\s+/', trim($learner->name ?? 'L')))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
@endphp

<div class="lpg od ob-order">
    <header class="lpg-head">
        <div class="lpg-head-r">
            <a class="lp-btn lp-btn--ghost" href="{{ route('orders.board') }}"><x-lb-icon name="arrow-left" />Order Board</a>
        </div>
    </header>

    @if ($errors->any())<div class="lef-alert" role="alert">{{ $errors->first() }}</div>@endif

    <div class="oz-stack">
    {{-- 1. the order: number, place, learner, what was ordered --}}
    <section class="od-card oz-hero">
        <div class="oz-top">
            <div class="oz-id">
                <div class="oz-id-r">
                    <h2>Order #{{ $short }}</h2>
                </div>
                <p>
                    <span class="oz-where">@switch($order->mode)@case('seat')<x-lb-icon name="armchair" />@break @case('table')<x-lb-icon name="table" />@break @default<x-lb-icon name="hand-coins" />@endswitch{{ $order->locationLabel() }}</span>
                    <span>{{ $order->created_at->format('j M, g:i A') }} · {{ $order->created_at->diffForHumans() }}</span>
                </p>
            </div>
            <div class="oz-who">
                <span class="oz-av">{{ $initials }}</span>
                <span class="oz-who-t"><b>{{ $learner->name ?? 'Learner' }}</b><small>{{ $learner->learner_no ?? '' }}</small></span>
                @if ($learner && $learner->mobile)
                    <a class="oz-call" href="tel:{{ $learner->mobile }}" title="Call {{ $learner->mobile }}" aria-label="Call {{ $learner->name }} on {{ $learner->mobile }}"><x-lb-icon name="phone" /></a>
                @endif
            </div>
        </div>

        <div class="oz-items">
        <ul class="ob-lines oz-lines">
            @foreach ($order->lines as $l)
                <li class="{{ in_array($l->fulfilment_status, [OrderLine::REMOVED, OrderLine::REPLACED]) ? 'is-gone' : '' }}">
                    @php $th = $thumbs[$l->order_item_id] ?? null; @endphp
                    <span class="oz-th">@if ($th && $th['img'])<img src="{{ $th['img'] }}" alt="" loading="lazy">@else<x-lb-icon :name="$th['icon'] ?? 'shapes'" />@endif</span>
                    <div>
                        <b><span class="oz-qty">{{ $l->quantity }}×</span> {{ $l->item_name }}</b>
                        <small>{{ $inr($l->unit_price) }} each · {{ $l->category_name }}
                            @if ($l->options) · {{ $l->options['pages'] ?? $l->quantity }} pages × {{ $l->options['copies'] ?? 1 }} {{ \Illuminate\Support\Str::plural('copy', $l->options['copies'] ?? 1) }}, {{ ($l->options['sides'] ?? 'single') === 'double' ? 'both sides' : 'one side' }}@endif
                        </small>
                        @if ($l->note)<small class="ob-lnote">“{{ $l->note }}”</small>@endif
                        @if ($l->upload_path)
                            <a class="ob-file" href="{{ route('orders.file', [$order->id, $l->id]) }}"><x-lb-icon name="download" />{{ $l->upload_name ?: 'Download file' }}</a>
                        @endif
                        @isset($lineTxt[$l->fulfilment_status])<span class="lpg-tag {{ $lineTxt[$l->fulfilment_status][0] }} oz-ltag">{{ $lineTxt[$l->fulfilment_status][1] }}</span>@endisset
                    </div>
                    <b class="ob-amt">{{ $inr($l->line_total) }}</b>
                    @php
                        $openRepl = $pendingRepl->where('order_line_id', $l->id)->first();
                        $canEdit = $suggestions->has($l->id) && in_array($l->fulfilment_status, [OrderLine::PENDING, OrderLine::UNAVAILABLE, OrderLine::REPLACEMENT_PENDING], true);
                    @endphp
                    @if ($openRepl)
                        <div class="ob-repl">
                            <x-lb-icon name="hourglass" />
                            <span>@if ($openRepl->suggested_item_id)Suggested <b>{{ $openRepl->quantity }}× {{ $openRepl->suggested_name }}</b> ({{ (float) $openRepl->price_diff > 0 ? '+' : '' }}{{ $inr($openRepl->price_diff) }}) — waiting for the learner @else Asked the learner to remove it @endif
                            @if ($openRepl->deadline_at)· till {{ $openRepl->deadline_at->format('g:i A') }}@endif</span>
                        </div>
                    @endif
                    @if ($canEdit)
                        <details class="ob-runout" @if ($l->fulfilment_status === OrderLine::UNAVAILABLE) open @endif>
                            <summary>{{ $l->fulfilment_status === OrderLine::PENDING ? 'Ran out?' : ($openRepl ? 'Change suggestion' : 'Suggest another / remove') }}</summary>
                            <form method="POST" action="{{ route('orders.unavailable', $order->id) }}" class="ob-runout-f">@csrf
                                <input type="hidden" name="line_id" value="{{ $l->id }}">
                                <div class="lef-in"><x-lb-icon name="arrow-left-right" />
                                    <select name="item_id" aria-label="Suggest instead of {{ $l->item_name }}">
                                        <option value="">No replacement — ask learner to remove</option>
                                        @if ($settings->replacement_enabled)
                                            @foreach ($suggestions[$l->id] as $sug)
                                                @php $d = (float) $sug->price * $l->quantity - (float) $l->line_total; @endphp
                                                <option value="{{ $sug->id }}">{{ $sug->name }} · {{ $inr($sug->price) }} ({{ abs($d) < 0.01 ? 'same' : ($d > 0 ? '+' : '−') . $inr(abs($d)) }})</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div class="lef-in ob-qty"><span class="lef-pre">Qty</span><input type="number" name="qty" min="1" max="50" value="{{ $l->quantity }}"></div>
                                <button class="lp-btn">Send to learner</button>
                            </form>
                            <div class="ob-runout-x">
                                <small class="ob-keep">It is also marked “out of stock” in the menu.</small>
                                <form method="POST" action="{{ route('orders.line.remove', [$order->id, $l->id]) }}" data-od-confirm="Remove {{ $l->item_name }} from this order?" data-od-text="The learner is told and pays less. It cannot be undone." data-od-ok="Remove item" data-od-cancel="Keep it" data-od-tone="bad" data-od-icon="trash">@csrf @method('DELETE')
                                    <button class="ob-linkbtn is-bad">Remove from order</button>
                                </form>
                            </div>
                        </details>
                    @endif
                </li>
            @endforeach
        </ul>
        <dl class="ob-sum">
            @if ((float) $order->delivery_charge > 0)
                <div><dt>Items</dt><dd>{{ $inr($order->subtotal) }}</dd></div>
                <div><dt>Delivery</dt><dd>{{ $inr($order->delivery_charge) }}</dd></div>
            @endif
            <div class="is-total"><dt>Total</dt><dd>{{ $inr($order->total) }}</dd></div>
        </dl>
        @if ($order->note)<p class="ob-note oz-note"><x-lb-icon name="message-square" /><span><b>Learner's note:</b> {{ $order->note }}</span></p>@endif
        @unless ($order->allow_partial)<p class="lef-hint oz-hint">The learner wants everything together (no partial delivery).</p>@endunless
        </div>
    </section>

    {{-- 2. order status: progress + the next step --}}
    <section class="od-card oz-status">
        <h3><x-lb-icon name="activity" />Order status <span class="lpg-tag {{ $statusTone }}">{{ $order->statusLabel() }}</span></h3>
        @if ($closed)
            <p class="oz-closed"><x-lb-icon name="circle-x" /><span><b>{{ $order->statusLabel() }}</b>@if ($order->reject_reason || $order->cancel_reason) — {{ $order->reject_reason ?: $order->cancel_reason }}@endif</span></p>
        @else
            <ol class="oz-steps" aria-label="Order progress">
                @foreach ($steps as $i => [$label, $keys])
                    @php
                        $state = $i < $reached || $reached === 5 ? 'is-done' : ($i === $reached ? 'is-now' : '');
                        $t = $i <= $reached ? $stepTime($keys) : null;
                        if ($i === 3 && $order->status === Order::DELIVERY_ATTEMPTED) { $label = 'Not at seat'; $state = 'is-now is-warn'; }
                    @endphp
                    <li class="{{ $state }}" @if (str_contains($state, 'is-now')) aria-current="step" @endif>
                        <i>@if (str_contains($state, 'is-done'))<x-lb-icon name="check" />@else{{ $i + 1 }}@endif</i>
                        <b>{{ $label }}</b>
                        <small>{{ $t ? $t->format('g:i A') : '' }}</small>
                    </li>
                @endforeach
            </ol>
        @endif

        @if ($pendingRepl->count())
            <p class="od-warn"><x-lb-icon name="hourglass" />Waiting for the learner to answer the replacement.</p>
        @endif
        @php
            $handedDue = in_array($order->status, [Order::DELIVERED, Order::COLLECTED], true) && $due > 0;
        @endphp

        @if ($moves->isNotEmpty() || $dangers->isNotEmpty() || $handedDue)
            <div class="oz-act">
                @if ($handedDue && $moves->isEmpty())
                    <p class="oz-wait"><x-lb-icon name="wallet" />Handed over · closes by itself once {{ $inr($due) }} is paid below.</p>
                @endif
                @if ($moves->isNotEmpty())
                    <div class="oz-next">
                        @foreach ($moves as $a)
                            <form method="POST" action="{{ route('orders.act', $order->id) }}">@csrf
                                <input type="hidden" name="action" value="{{ $a['key'] }}"><input type="hidden" name="v" value="{{ $order->version }}">
                                <button class="lp-btn {{ $a['tone'] === 'ghost' ? 'lp-btn--ghost' : '' }}">@if ($a['tone'] !== 'ghost')<x-lb-icon name="check" />@endif{{ $a['label'] }}</button>
                            </form>
                        @endforeach
                    </div>
                @endif
                @if ($dangers->isNotEmpty())
                    <div class="oz-more">
                        @foreach ($dangers as $a)
                            <button type="button" class="oz-link {{ $a['key'] === 'undo' ? '' : 'is-bad' }}" data-oz-open="ozWhy{{ $a['key'] }}" aria-expanded="{{ $errors->has('reason') ? 'true' : 'false' }}"><x-lb-icon :name="$a['key'] === 'undo' ? 'rotate-ccw' : 'circle-x'" />{{ $a['label'] }}</button>
                        @endforeach
                    </div>
                @endif
            </div>
            @foreach ($dangers as $a)
                <form method="POST" action="{{ route('orders.act', $order->id) }}" class="oz-why" id="ozWhy{{ $a['key'] }}" @unless ($errors->has('reason')) hidden @endunless>@csrf
                    <input type="hidden" name="action" value="{{ $a['key'] }}"><input type="hidden" name="v" value="{{ $order->version }}">
                    <div class="lef-in"><x-lb-icon name="message-square" /><input type="text" name="reason" maxlength="255" required aria-label="{{ $a['key'] === 'undo' ? 'Why correct the status?' : 'Reason (the learner sees this)' }}" placeholder="{{ $a['key'] === 'reject' ? 'Reason, learner sees it — e.g. Kitchen closed today' : ($a['key'] === 'undo' ? 'Why? e.g. Tapped Delivered by mistake' : 'Reason, learner sees it — e.g. Learner asked to cancel') }}"></div>
                    <button class="lp-btn {{ $a['key'] === 'undo' ? 'lp-btn--ghost' : 'ob-btn-bad' }}">{{ $a['label'] }}</button>
                    <button type="button" class="oz-link" data-oz-close>Keep order</button>
                </form>
            @endforeach
        @elseif ($order->isOpen() && ! $pendingRepl->count())
            <p class="od-none oz-none">Nothing to do right now.</p>
        @endif
        @if ($order->isOpen() && ! $isOwner && $dangers->where('key', 'cancel')->isEmpty() && in_array($order->status, \App\Services\Ordering\OrderStatusService::STARTED))
            <p class="lef-hint oz-hint">Preparation has started: only the library owner can cancel now.</p>
        @endif
    </section>

    {{-- 3. payment --}}
    <section class="od-card">
        <h3><x-lb-icon name="wallet" />Payment <span class="lpg-tag {{ $order->payment_status === Order::PAY_PAID ? 'is-ok' : (in_array($order->payment_status, [Order::PAY_PROCESSING, Order::PAY_REFUND_PENDING]) ? 'is-warn' : '') }}">{{ $order->paymentLabel() }}</span></h3>

        @php $nothingPaid = $closed && (float) $order->paid_amount == 0; @endphp
        <div class="oz-pay {{ $refundable > 0 ? 'is-bad' : (! $closed && $due > 0 ? 'is-due' : ($nothingPaid ? 'is-none' : 'is-ok')) }}">
            @if ($refundable > 0)
                <b>{{ $inr($refundable) }}</b><span>to give back</span>
            @elseif (! $closed && $due > 0)
                <b>{{ $inr($due) }}</b><span>to collect</span>
            @else
                <b>@if ($nothingPaid)Nothing to collect @else<x-lb-icon name="circle-check" />All settled @endif</b>
            @endif
            <small>{{ $methodTxt[$order->payment_method] }} · {{ $inr($order->paid_amount) }} received of {{ $inr($order->total) }}@if ((float) $order->refunded_amount > 0) · {{ $inr($order->refunded_amount) }} refunded @endif</small>
        </div>

        {{-- learner's UPI proofs --}}
        @foreach ($order->payments->where('kind', 'payment')->where('method', 'upi_qr') as $p)
            <div class="ob-proof {{ $p->status === 'pending' ? 'is-pending' : '' }}">
                @if ($p->screenshot)
                    <a href="{{ route('orders.payment.proof', [$order->id, $p->id]) }}" target="_blank" rel="noopener" class="ob-proof-img"><img src="{{ route('orders.payment.proof', [$order->id, $p->id]) }}" alt="Payment screenshot" loading="lazy"></a>
                @endif
                <div class="ob-proof-t">
                    <b>{{ $inr($p->amount) }} · UPI screenshot</b>
                    <small>{{ $p->created_at->format('j M, g:i A') }}@if ($p->txn_ref) · UTR {{ $p->txn_ref }}@endif</small>
                    <span class="lpg-tag {{ $p->status === 'verified' ? 'is-ok' : ($p->status === 'failed' ? 'is-bad' : 'is-warn') }}">{{ ['pending' => 'Check it', 'verified' => 'Approved', 'failed' => 'Rejected'][$p->status] }}</span>
                    @if ($p->status === 'pending')
                        <p class="lef-hint">Match the amount and UTR with your bank / UPI app before approving.</p>
                        <div class="ob-proof-a">
                            <form method="POST" action="{{ route('orders.payment.review', [$order->id, $p->id]) }}">@csrf<input type="hidden" name="decision" value="approve"><button class="lp-btn"><x-lb-icon name="check" />Approve</button></form>
                            <form method="POST" action="{{ route('orders.payment.review', [$order->id, $p->id]) }}" data-od-confirm="Payment not received?" data-od-text="The learner is asked to pay again." data-od-ok="Mark not received" data-od-cancel="Go back" data-od-tone="bad" data-od-icon="circle-x" data-od-field="note" data-od-label="Reason the learner sees" data-od-value="Amount not received" data-od-required="1" data-od-chips="Amount not received|Wrong amount|Screenshot not clear">@csrf<input type="hidden" name="decision" value="reject"><input type="hidden" name="note" value=""><button class="lp-btn lp-btn--ghost">Not received</button></form>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach

        @if (! $closed && $due > 0)
            @php $proofWaiting = $order->payments->where('kind', 'payment')->where('status', 'pending')->isNotEmpty(); @endphp
            @if ($proofWaiting)
                <p class="oz-collect-wait"><x-lb-icon name="hourglass" /><span><b>Check the UPI screenshot above first.</b> Approve it, or mark it not received — then you can take money at the counter. This stops the same payment being counted twice.</span></p>
            @else
            {{-- take money at the counter: always open, one tap for the usual case (full amount, cash) --}}
            <form method="POST" action="{{ route('orders.payment.store', $order->id) }}" class="oz-collect" id="ozCollect">@csrf
                <p class="oz-collect-h"><x-lb-icon name="banknote" />Collect payment</p>
                <div class="oz-methods" role="radiogroup" aria-label="Paid by">
                    @foreach ($methods as $k => $v)
                        <label><input type="radio" name="method" value="{{ $k }}" @checked(old('method', 'cash') === $k)><span>{{ $v }}</span></label>
                    @endforeach
                </div>
                <div class="oz-collect-r">
                    <div class="lef-in"><span class="lef-pre">₹</span><input type="number" name="amount" min="0.01" max="{{ $due }}" step="any" value="{{ old('amount', $due) }}" required aria-label="Amount"></div>
                    <div class="lef-in"><x-lb-icon name="hash" /><input type="text" name="ref" maxlength="100" value="{{ old('ref') }}" placeholder="UTR / reference (optional)" aria-label="Reference or UTR"></div>
                </div>
                <button class="lp-btn oz-collect-go" data-oz-collect-btn><x-lb-icon name="check" />Collect <span data-oz-collect-amt>{{ $inr(old('amount', $due)) }}</span></button>
            </form>
            @endif
        @endif

        @if ($refundable > 0)
            <details class="ob-money is-bad" open>
                <summary><x-lb-icon name="rotate-ccw" />Give back {{ $inr($refundable) }}<x-lb-icon name="chevron-down" class="oz-chev" /></summary>
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
    </div>
</div>

<script>
(function () {
    // the button says the amount being collected
    var ozAmt = document.querySelector('#ozCollect [name="amount"]'), ozLbl = document.querySelector('[data-oz-collect-amt]');
    if (ozAmt && ozLbl) {
        ozAmt.addEventListener('input', function () {
            var n = parseFloat(ozAmt.value);
            ozLbl.textContent = n > 0 ? '₹' + (n % 1 ? n.toFixed(2) : n.toLocaleString('en-IN')) : '';
        });
    }
    // Cancel / Reject / Undo: open the small reason form under the buttons
    document.querySelectorAll('[data-oz-open]').forEach(function (b) {
        b.addEventListener('click', function () {
            document.querySelectorAll('.oz-why').forEach(function (f) { f.hidden = f.id !== b.dataset.ozOpen || !f.hidden; });
            document.querySelectorAll('[data-oz-open]').forEach(function (x) { x.setAttribute('aria-expanded', String(!document.getElementById(x.dataset.ozOpen).hidden)); });
            var f = document.getElementById(b.dataset.ozOpen);
            if (!f.hidden) f.querySelector('[name="reason"]').focus();
        });
    });
    document.querySelectorAll('[data-oz-close]').forEach(function (b) {
        b.addEventListener('click', function () {
            b.closest('.oz-why').hidden = true;
            document.querySelectorAll('[data-oz-open]').forEach(function (x) { x.setAttribute('aria-expanded', 'false'); });
        });
    });
    // forms submit once
    document.querySelectorAll('.ob-order form').forEach(function (f) {
        f.addEventListener('submit', function () { var b = f.querySelector('button:not([type="button"])'); if (b) { setTimeout(function () { b.disabled = true; }, 0); } });
    });
})();
</script>
@endsection
