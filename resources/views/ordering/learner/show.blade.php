@extends('layouts.learner')

@section('title', 'Order #' . \Illuminate\Support\Str::afterLast($order->order_no, '-'))

@push('lr-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-learner-order.css') }}?v={{ @filemtime(public_path('css/lb-learner-order.css')) }}">
@endpush

@section('content')
@php
    use App\Models\Ordering\Order;
    use App\Models\Ordering\OrderLine;
    $inr = fn ($n) => '₹' . number_format((float) $n, (float) $n == floor((float) $n) ? 0 : 2);
    $short = \Illuminate\Support\Str::afterLast($order->order_no, '-');
    $closed = in_array($order->status, [Order::CANCELLED, Order::REJECTED], true);
    $last = $order->mode === 'counter' ? [Order::READY, 'Ready at the counter', Order::COLLECTED, 'Collected']
        : [Order::OUT_FOR_DELIVERY, 'On the way', Order::DELIVERED, 'Delivered'];
    $steps = [
        [Order::NEW, 'Order placed', $order->created_at],
        [Order::CONFIRMED, 'Accepted', $order->confirmed_at],
        [Order::PREPARING, 'Preparing', $order->preparing_at],
        [$last[0], $last[1], $order->ready_at],
        [$last[2], $last[3], $order->delivered_at ?: $order->completed_at],
    ];
    $rank = [Order::NEW => 0, Order::ATTENTION => 0, Order::UNDER_REVIEW => 0, Order::REPLACEMENT_PENDING => 0, Order::CONFIRMED => 1, Order::PREPARING => 2,
        Order::READY => 3, Order::OUT_FOR_DELIVERY => 3, Order::DELIVERY_ATTEMPTED => 3, Order::DELIVERED => 4, Order::COLLECTED => 4, Order::COMPLETED => 4];
    $at = $rank[$order->status] ?? -1;
    $lineTone = [OrderLine::UNAVAILABLE => 'is-bad', OrderLine::REPLACEMENT_PENDING => 'is-warn', OrderLine::REMOVED => 'is-bad', OrderLine::REPLACED => '', OrderLine::FULFILLED => 'is-ok'];
    $lineTxt = [OrderLine::UNAVAILABLE => 'Not available', OrderLine::REPLACEMENT_PENDING => 'Your answer needed', OrderLine::REMOVED => 'Removed', OrderLine::REPLACED => 'Replaced', OrderLine::FULFILLED => 'Done'];
    $payTone = [Order::PAY_PAID => 'is-ok', Order::PAY_PROCESSING => 'is-warn', Order::PAY_FAILED => 'is-bad', Order::PAY_REFUND_PENDING => 'is-warn', Order::PAY_REFUNDED => 'is-ok'];
    $methodTxt = ['online' => 'Online (UPI)', 'offline' => 'Pay at counter', 'pay_later' => 'Pay later'];
@endphp

<x-lr-hero icon="receipt-text" kicker="Order {{ $order->order_no }}" title="Order #{{ $short }}"
    :sub="e($order->locationLabel()) . ' · ' . e($order->created_at->format('j M, g:i A'))"
    :chips="[['circle-check', $order->statusLabel()], ['wallet', $order->paymentLabel()]]" :art="false">
    <div class="lx-hero-amt"><small>Total</small><b>{{ $inr($order->total) }}</b></div>
</x-lr-hero>

<div class="lx-grid lo-show">
    {{-- status --}}
    <section class="lx-card">
        <div class="lx-card-head">
            <span class="lx-ic" data-tone="{{ $closed ? 'rose' : 'teal' }}"><x-lb-icon :name="$closed ? 'circle-x' : 'activity'" /></span>
            <div><h2>{{ $order->statusLabel() }}</h2>
                <p>
                    @if ($order->status === Order::CANCELLED){{ $order->cancel_reason }}
                    @elseif ($order->status === Order::REJECTED){{ $order->reject_reason ?: 'The library could not take this order.' }}
                    @elseif ($order->status === Order::REPLACEMENT_PENDING)Something ran out — the library suggested a change. Please answer below.
                    @elseif ($at >= 4)Enjoy! Thank you for ordering.
                    @elseif ($order->estimated_ready_at)Usually ready by {{ $order->estimated_ready_at->format('g:i A') }}
                    @endif
                </p>
            </div>
            <span class="lo-live" data-lo-live title="Updates by itself"><i></i>Live</span>
        </div>
        @unless ($closed)
            <ol class="lo-steps">
                @foreach ($steps as $i => [$key, $label, $time])
                    <li class="{{ $i < $at ? 'is-done' : ($i === $at ? 'is-now' : '') }}">
                        <span class="lo-dot">@if ($i <= $at)<x-lb-icon name="check" />@endif</span>
                        <b>{{ $label }}</b>
                        @if ($time && $i <= $at)<small>{{ $time->format('g:i A') }}</small>@endif
                    </li>
                @endforeach
            </ol>
        @endunless
    </section>

    {{-- items --}}
    <section class="lx-card">
        <div class="lx-card-head">
            <span class="lx-ic" data-tone="orange"><x-lb-icon name="list" /></span>
            <div><h2>Items</h2><p>{{ $order->lines->count() }} {{ \Illuminate\Support\Str::plural('item', $order->lines->count()) }}</p></div>
        </div>
        @foreach ($order->lines as $l)
            <div class="lx-row {{ in_array($l->fulfilment_status, [OrderLine::REMOVED, OrderLine::REPLACED]) ? 'lo-struck' : '' }}">
                <div class="lx-row-main">
                    <b>{{ $l->quantity }} × {{ $l->item_name }}</b>
                    <small>{{ $l->category_name }} · {{ $inr($l->unit_price) }}
                        @if ($l->options) · {{ $l->options['copies'] ?? 1 }} {{ \Illuminate\Support\Str::plural('copy', $l->options['copies'] ?? 1) }}, {{ ($l->options['sides'] ?? 'single') === 'double' ? 'both sides' : 'one side' }}@endif
                        @if ($l->upload_name) · {{ $l->upload_name }}@endif
                    </small>
                </div>
                @if (isset($lineTxt[$l->fulfilment_status]))<span class="lx-pill {{ $lineTone[$l->fulfilment_status] }}">{{ $lineTxt[$l->fulfilment_status] }}</span>@endif
                <span class="lx-row-end">{{ $inr($l->line_total) }}</span>
            </div>
        @endforeach
        <dl class="lo-sum">
            <div><dt>Items</dt><dd>{{ $inr($order->subtotal) }}</dd></div>
            @if ((float) $order->delivery_charge > 0)<div><dt>Delivery</dt><dd>{{ $inr($order->delivery_charge) }}</dd></div>@endif
            <div class="is-total"><dt>Total</dt><dd>{{ $inr($order->total) }}</dd></div>
        </dl>
        @if ($order->note)<p class="lo-notetxt"><x-lb-icon name="message-square" />{{ $order->note }}</p>@endif
    </section>

    {{-- payment --}}
    <section class="lx-card" id="pay">
        <div class="lx-card-head">
            <span class="lx-ic" data-tone="green"><x-lb-icon name="wallet" /></span>
            <div><h2>Payment</h2><p>{{ $methodTxt[$order->payment_method] }}</p></div>
            <span class="lx-pill {{ $payTone[$order->payment_status] ?? '' }}">{{ $order->paymentLabel() }}</span>
        </div>

        @if ($canPay && $upi)
            <div class="lo-upi">
                <div class="lo-upi-qr">{!! $upi['qr'] !!}</div>
                <div class="lo-upi-t">
                    <b>Pay {{ $inr($upi['amount']) }}</b>
                    <small>to {{ $upi['payee'] }} · {{ $upi['upi_id'] }}</small>
                    <ol>
                        <li>Scan the QR in any UPI app (or tap the button on your phone).</li>
                        <li>Pay, then send the screenshot here.</li>
                    </ol>
                    <a class="lh-btn is-sm lo-upi-open" href="{{ $upi['link'] }}"><x-lb-icon name="smartphone" />Open UPI app</a>
                </div>
            </div>
            <form method="POST" action="{{ route('learner.orders.pay', $order->order_no) }}" enctype="multipart/form-data" class="lo-proof" data-lr-form>
                @csrf
                <label class="lr-file lo-file">
                    <span><x-lb-icon name="image" /><b>Payment screenshot</b><small>JPG / PNG, up to 5 MB</small></span>
                    <input type="file" name="screenshot" accept="image/jpeg,image/png,image/webp" required>
                </label>
                @error('screenshot')<p class="lo-msg is-bad">{{ $message }}</p>@enderror
                <label class="lo-field">
                    <span>UTR / reference no. <i>(optional)</i></span>
                    <input type="text" name="utr" maxlength="30" value="{{ old('utr') }}" placeholder="12 digit number in your UPI app" inputmode="text" autocomplete="off">
                </label>
                @error('utr')<p class="lo-msg is-bad">{{ $message }}</p>@enderror
                <button type="submit" class="lx-btn is-save"><x-lb-icon name="send" />Send payment proof</button>
            </form>
        @elseif ($order->payment_method === 'online' && $canPay)
            <p class="lo-msg">Online payment is not set up for this branch. Please pay {{ $inr($order->dueAmount()) }} at the counter.</p>
        @elseif ($order->payment_status === Order::PAY_PROCESSING)
            <p class="lo-msg"><x-lb-icon name="hourglass" />Payment proof sent. The library is checking it.</p>
        @elseif ($order->payment_status === Order::PAY_PAID)
            <p class="lo-msg is-ok"><x-lb-icon name="circle-check" />Paid {{ $inr($order->paid_amount) }}. Thank you!</p>
        @elseif ($order->payment_status === Order::PAY_REFUND_PENDING)
            <p class="lo-msg"><x-lb-icon name="rotate-ccw" />The library will return your money as per its refund rule.</p>
        @elseif (! $closed && $order->dueAmount() > 0)
            <p class="lo-msg"><x-lb-icon name="banknote" />{{ $order->payment_method === 'pay_later' ? 'Pay later: ' . $inr($order->dueAmount()) . ' is added to your dues.' : 'Pay ' . $inr($order->dueAmount()) . ' when you get your order.' }}</p>
        @endif
    </section>

    @if ($canCancel)
        <form method="POST" action="{{ route('learner.orders.cancel', $order->order_no) }}" class="lo-cancel" data-lo-cancel>
            @csrf
            <input type="hidden" name="reason" value="">
            <button type="submit" class="lo-cancel-btn"><x-lb-icon name="circle-x" />Cancel this order</button>
        </form>
    @endif

    <a class="lo-back" href="{{ route('learner.orders') }}"><x-lb-icon name="arrow-left" />Order something else</a>
</div>
@endsection

@push('lr-scripts')
<script>
(function () {
    var c = document.querySelector('[data-lo-cancel]');
    if (c) c.addEventListener('submit', function (e) {
        e.preventDefault();
        var why = window.prompt('Cancel this order? Tell the library why (optional):', '');
        if (why === null) return;
        c.querySelector('[name="reason"]').value = why;
        c.submit();
    });

    // live status: poll; reload when something changed (paused while the tab is hidden)
    @if ($order->isOpen() || $order->payment_status === \App\Models\Ordering\Order::PAY_PROCESSING)
    var url = @json(route('learner.orders.status', $order->order_no)), v = @json($order->version . '-' . optional($order->updated_at)->timestamp), live = document.querySelector('[data-lo-live]');
    function tick() {
        if (document.hidden) return;
        fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (j) {
                if (!j) return;
                if (j.v !== v) {
                    var busy = document.activeElement && /INPUT|TEXTAREA|SELECT/.test(document.activeElement.tagName);
                    if (!busy) location.reload();
                }
            })
            .catch(function () { if (live) live.classList.add('is-off'); });
    }
    setInterval(tick, 8000);
    document.addEventListener('visibilitychange', tick);
    @else
    var live = document.querySelector('[data-lo-live]'); if (live) live.hidden = true;
    @endif
})();
</script>
@endpush
