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
    $pickup = $order->mode === 'counter';
    $closed = in_array($order->status, [Order::CANCELLED, Order::REJECTED], true);
    $eta = $order->estimated_ready_at ? $order->estimated_ready_at->format('g:i A') : null;

    // one big sentence per status (food-app style)
    [$title, $sub, $tone] = match ($order->status) {
        Order::NEW, Order::ATTENTION => ['Order placed', 'Waiting for the library to accept it', 'wait'],
        Order::UNDER_REVIEW => ['Library is checking your order', 'You will see it accepted in a moment', 'wait'],
        Order::REPLACEMENT_PENDING => ['Something ran out', 'Please choose what to do below', 'warn'],
        Order::CONFIRMED => ['Order accepted', $eta ? 'Ready by about ' . $eta : 'They will start preparing soon', 'go'],
        Order::PREPARING => ['Preparing your order', $eta ? 'Ready by about ' . $eta : 'It will be ready soon', 'go'],
        Order::READY => ['Ready! Collect it at the counter', 'Show order #' . $short . ' at the counter', 'ready'],
        Order::OUT_FOR_DELIVERY => ['On the way to ' . $order->locationLabel(), 'Please stay at your ' . ($order->mode === 'table' ? 'table' : 'seat'), 'ready'],
        Order::DELIVERY_ATTEMPTED => ['We came to your ' . ($order->mode === 'table' ? 'table' : 'seat'), 'You were not there — please ask at the counter', 'warn'],
        Order::DELIVERED => ['Delivered. Enjoy!', $order->dueAmount() > 0 ? 'Please pay ' . $inr($order->dueAmount()) : 'Thank you for ordering', 'done'],
        Order::COLLECTED => ['Collected. Enjoy!', $order->dueAmount() > 0 ? 'Please pay ' . $inr($order->dueAmount()) : 'Thank you for ordering', 'done'],
        Order::COMPLETED => ['Order complete', 'Thank you for ordering', 'done'],
        Order::REJECTED => ['Order not accepted', $order->reject_reason ?: 'The library could not take this order', 'bad'],
        Order::CANCELLED => ['Order cancelled', $order->cancel_reason ?: '', 'bad'],
        default => [$order->statusLabel(), '', 'go'],
    };
    $stage = [Order::NEW => 1, Order::ATTENTION => 1, Order::UNDER_REVIEW => 1, Order::REPLACEMENT_PENDING => 1, Order::CONFIRMED => 2, Order::PREPARING => 3,
        Order::READY => 4, Order::OUT_FOR_DELIVERY => 4, Order::DELIVERY_ATTEMPTED => 4, Order::DELIVERED => 5, Order::COLLECTED => 5, Order::COMPLETED => 5][$order->status] ?? 0;
    $steps = ['Placed', 'Accepted', 'Preparing', $pickup ? 'Ready' : 'On the way', $pickup ? 'Collected' : 'Delivered'];
    $lineTxt = [OrderLine::UNAVAILABLE => 'Not available', OrderLine::REPLACEMENT_PENDING => 'Your answer needed', OrderLine::REMOVED => 'Removed', OrderLine::REPLACED => 'Replaced'];
    $methodTxt = ['online' => 'UPI', 'offline' => 'Pay at counter', 'pay_later' => 'Pay later'];
@endphp

<div class="zo zo-order">
    {{-- big status --}}
    <section class="zt is-{{ $tone }}">
        <div class="zt-row">
            <span class="zt-no">Order #{{ $short }}</span>
            <span class="zt-live" data-lo-live><i></i>Live</span>
        </div>
        <h1>{{ $title }}</h1>
        @if ($sub)<p>{{ $sub }}</p>@endif
        @unless ($closed)
            <div class="zt-bar" aria-label="Step {{ $stage }} of 5">
                @foreach ($steps as $i => $label)<i class="{{ $i < $stage ? 'is-on' : '' }} {{ $i === $stage - 1 && $stage < 5 ? 'is-now' : '' }}"></i>@endforeach
            </div>
            <div class="zt-steps">@foreach ($steps as $i => $label)<span class="{{ $i < $stage ? 'is-on' : '' }}">{{ $label }}</span>@endforeach</div>
        @endunless
        <div class="zt-where">
            <x-lb-icon :name="$order->mode === 'seat' ? 'armchair' : ($order->mode === 'table' ? 'table' : 'hand-coins')" />
            <span>{{ $order->locationLabel() }} · placed {{ $order->created_at->format('g:i A') }}</span>
        </div>
    </section>

    {{-- pay now (online) --}}
    @if ($canPay && $upi)
        <section class="zo-box zo-paynow" id="pay">
            <h3>Pay {{ $inr($upi['amount']) }} to the library</h3>
            <div class="zo-upi">
                <div class="zo-upi-qr">{!! $upi['qr'] !!}</div>
                <div class="zo-upi-t">
                    <b>{{ $upi['payee'] }}</b>
                    <small>{{ $upi['upi_id'] }}</small>
                    <p>Scan with any UPI app, pay, then send the screenshot below.</p>
                    <a class="zo-btn is-ghost zo-upi-open" href="{{ $upi['link'] }}"><x-lb-icon name="smartphone" />Open UPI app</a>
                </div>
            </div>
            <form method="POST" action="{{ route('learner.orders.pay', $order->order_no) }}" enctype="multipart/form-data" class="zo-proof" data-lr-form>
                @csrf
                <label class="zo-file" data-zo-file>
                    <span><x-lb-icon name="image" /><b>Add payment screenshot</b><small>JPG / PNG, up to 5 MB</small></span>
                    <input type="file" name="screenshot" accept="image/jpeg,image/png,image/webp" required>
                </label>
                @error('screenshot')<p class="zo-msg is-bad">{{ $message }}</p>@enderror
                <label class="zo-field"><span>UTR / reference no. (optional)</span><input type="text" name="utr" maxlength="30" value="{{ old('utr') }}" placeholder="12 digit number from your UPI app" autocomplete="off"></label>
                @error('utr')<p class="zo-msg is-bad">{{ $message }}</p>@enderror
                <button type="submit" class="zo-btn is-big"><span>Send payment proof</span></button>
            </form>
        </section>
    @endif

    {{-- items + bill --}}
    <section class="zo-box">
        <h3>Your order</h3>
        <div class="zo-lines">
            @foreach ($order->lines as $l)
                <div class="zo-line is-static {{ in_array($l->fulfilment_status, [OrderLine::REMOVED, OrderLine::REPLACED]) ? 'is-gone' : '' }}">
                    <div class="zo-line-t">
                        <b><span class="zo-qty">{{ $l->quantity }}×</span> {{ $l->item_name }}</b>
                        @if ($l->options)<small>{{ $l->options['copies'] ?? 1 }} {{ \Illuminate\Support\Str::plural('copy', $l->options['copies'] ?? 1) }} · {{ ($l->options['sides'] ?? 'single') === 'double' ? 'both sides' : 'one side' }}@if ($l->upload_name) · {{ $l->upload_name }}@endif</small>@endif
                        @if (isset($lineTxt[$l->fulfilment_status]))<small class="{{ in_array($l->fulfilment_status, [OrderLine::REMOVED, OrderLine::UNAVAILABLE]) ? 'is-bad' : 'is-warn' }}">{{ $lineTxt[$l->fulfilment_status] }}</small>@endif
                    </div>
                    <span class="zo-line-p">{{ $inr($l->line_total) }}</span>
                </div>
            @endforeach
        </div>
        <dl class="zo-bill">
            <div><dt>Item total</dt><dd>{{ $inr($order->subtotal) }}</dd></div>
            @if ((float) $order->delivery_charge > 0)<div><dt>Delivery fee</dt><dd>{{ $inr($order->delivery_charge) }}</dd></div>@endif
            <div class="is-total"><dt>Total</dt><dd>{{ $inr($order->total) }}</dd></div>
        </dl>
        @if ($order->note)<p class="zo-note"><x-lb-icon name="message-square" />{{ $order->note }}</p>@endif
    </section>

    {{-- payment status --}}
    <section class="zo-box zo-paystate">
        <span class="zo-ico"><x-lb-icon name="wallet" /></span>
        <span><b>{{ $methodTxt[$order->payment_method] }}</b>
            <small>
                @if ($order->payment_status === Order::PAY_PAID)Paid {{ $inr($order->paid_amount) }} — thank you!
                @elseif ($order->payment_status === Order::PAY_PROCESSING)Screenshot sent — the library is checking it
                @elseif ($order->payment_status === Order::PAY_FAILED)The library did not receive it — please pay again above
                @elseif ($order->payment_status === Order::PAY_REFUND_PENDING)Your money will be returned by the library
                @elseif ($order->payment_status === Order::PAY_REFUNDED)Money returned
                @elseif ($closed)No payment needed
                @elseif ($order->payment_method === 'pay_later'){{ $inr($order->dueAmount()) }} added to your dues
                @elseif ($order->payment_method === 'offline')Pay {{ $inr($order->dueAmount()) }} when you get your order
                @else Pay {{ $inr($order->dueAmount()) }} @endif
            </small>
        </span>
        <em class="zo-pill is-{{ $order->payment_status === Order::PAY_PAID ? 'ok' : (in_array($order->payment_status, [Order::PAY_FAILED]) ? 'bad' : 'wait') }}">{{ $order->paymentLabel() }}</em>
    </section>

    @if ($canCancel)
        <form method="POST" action="{{ route('learner.orders.cancel', $order->order_no) }}" class="zo-cancel" data-lo-cancel>
            @csrf<input type="hidden" name="reason" value="">
            <button type="submit">Cancel order</button>
        </form>
    @endif

    <a class="zo-btn is-ghost zo-more" href="{{ route('learner.orders') }}"><x-lb-icon name="plus" />Order something else</a>
</div>
@endsection

@push('lr-scripts')
<script>
(function () {
    // payment screenshot: show the chosen file name
    document.querySelectorAll('[data-zo-file] input').forEach(function (inp) {
        inp.addEventListener('change', function () { var b = inp.closest('[data-zo-file]'); if (inp.files[0]) { b.querySelector('b').textContent = inp.files[0].name; b.classList.add('is-done'); } });
    });
    var c = document.querySelector('[data-lo-cancel]');
    if (c) c.addEventListener('submit', function (e) {
        e.preventDefault();
        var why = window.prompt('Cancel this order? Tell the library why (optional):', '');
        if (why === null) return;
        c.querySelector('[name="reason"]').value = why;
        c.submit();
    });
    var live = document.querySelector('[data-lo-live]');
    @if ($order->isOpen() || $order->payment_status === \App\Models\Ordering\Order::PAY_PROCESSING)
    var url = @json(route('learner.orders.status', $order->order_no)), v = @json($order->version . '-' . optional($order->updated_at)->timestamp);
    function tick() {
        if (document.hidden) return;
        fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (j) {
                if (!j) return;
                live.classList.remove('is-off');
                if (j.v !== v && !(document.activeElement && /INPUT|TEXTAREA|SELECT/.test(document.activeElement.tagName))) location.reload();
            })
            .catch(function () { live.classList.add('is-off'); });
    }
    setInterval(tick, 8000);
    document.addEventListener('visibilitychange', tick);
    @else
    if (live) live.hidden = true;
    @endif
})();
</script>
@endpush
