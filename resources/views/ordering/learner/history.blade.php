@extends('layouts.learner')

@section('title', 'My orders')

@push('lr-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-learner-order.css') }}?v={{ @filemtime(public_path('css/lb-learner-order.css')) }}">
@endpush

@section('content')
@php
    use App\Models\Ordering\Order;
    $inr = fn ($n) => '₹' . number_format((float) $n, (float) $n == floor((float) $n) ? 0 : 2);
    $tone = fn ($o) => in_array($o->status, [Order::CANCELLED, Order::REJECTED]) ? 'is-bad' : (in_array($o->status, [Order::DELIVERED, Order::COLLECTED, Order::COMPLETED]) ? 'is-ok' : 'is-warn');
@endphp

<x-lr-hero icon="receipt" kicker="Order & Services" title="My orders" sub="Everything you ordered and what you paid. / मेरे ऑर्डर" :art="false">
    @if (Route::has('learner.orders'))<a class="lx-btn is-go lx-hero-btn" href="{{ route('learner.orders') }}"><x-lb-icon name="plus" />New order</a>@endif
</x-lr-hero>

<div class="lx-stats lo-stats">
    <div class="lx-stat" data-tone="orange" title="Not counting cancelled orders"><span><x-lb-icon name="receipt" /></span><b>{{ (int) $totals->orders }}</b><small>Orders</small></div>
    <div class="lx-stat" data-tone="teal"><span><x-lb-icon name="indian-rupee" /></span><b>{{ $inr($totals->spent) }}</b><small>Spent in all</small></div>
    <div class="lx-stat" data-tone="{{ $totals->due > 0 ? 'rose' : 'green' }}"><span><x-lb-icon name="wallet" /></span><b>{{ $inr($totals->due) }}</b><small>{{ $totals->due > 0 ? 'To pay' : 'Nothing to pay' }}</small></div>
</div>

<section class="lx-card lo-hist">
    @forelse ($orders as $o)
        <a class="lx-row lo-hist-row" href="{{ route('learner.orders.show', $o->order_no) }}">
            <span class="lx-row-ic" data-tone="orange"><x-lb-icon name="receipt-text" /></span>
            <div class="lx-row-main">
                <b>#{{ \Illuminate\Support\Str::afterLast($o->order_no, '-') }} · {{ $o->lines_count }} {{ \Illuminate\Support\Str::plural('item', $o->lines_count) }}</b>
                <small>{{ $o->created_at->format('j M Y, g:i A') }} · {{ $o->locationLabel() }}</small>
                <span class="lo-hist-pills">
                    <span class="lx-pill {{ $tone($o) }}">{{ $o->statusLabel() }}</span>
                    <span class="lx-pill {{ $o->payment_status === Order::PAY_PAID ? 'is-ok' : '' }}">{{ $o->paymentLabel() }}</span>
                </span>
            </div>
            <span class="lx-row-end">{{ $inr($o->total) }}</span>
        </a>
    @empty
        <div class="lx-empty is-big">
            <x-lb-icon name="receipt" />
            <b>No orders yet</b>
            <p>Order tea, snacks, stationery or a printout — it comes to your seat.</p>
            @if (Route::has('learner.orders'))<a class="lh-btn is-sm" href="{{ route('learner.orders') }}">Order now</a>@endif
        </div>
    @endforelse
</section>

@if ($orders->hasPages())
    <nav class="lo-pages">
        @if ($orders->previousPageUrl())<a href="{{ $orders->previousPageUrl() }}"><x-lb-icon name="chevron-left" />Newer</a>@endif
        <span>Page {{ $orders->currentPage() }} of {{ $orders->lastPage() }}</span>
        @if ($orders->nextPageUrl())<a href="{{ $orders->nextPageUrl() }}">Older<x-lb-icon name="chevron-right" /></a>@endif
    </nav>
@endif
@endsection
