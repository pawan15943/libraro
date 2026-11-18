@extends('layouts.learner')

@section('title', 'My orders')

@push('lr-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-learner-order.css') }}?v={{ @filemtime(public_path('css/lb-learner-order.css')) }}">
@endpush

@section('content')
@php
    use App\Models\Ordering\Order;
    $inr = fn ($n) => '₹' . number_format((float) $n, (float) $n == floor((float) $n) ? 0 : 2);
    $tone = fn ($o) => in_array($o->status, [Order::CANCELLED, Order::REJECTED]) ? 'bad' : (in_array($o->status, [Order::DELIVERED, Order::COLLECTED, Order::COMPLETED]) ? 'ok' : 'wait');
@endphp

<div class="zo">
    <div class="zo-head">
        <h1>My orders</h1>
        @if (Route::has('learner.orders'))<a class="zo-btn" href="{{ route('learner.orders') }}"><x-lb-icon name="plus" />New order</a>@endif
    </div>

    <div class="zo-sums">
        <div><b>{{ (int) $totals->orders }}</b><small>Orders</small></div>
        <div><b>{{ $inr($totals->spent) }}</b><small>Spent</small></div>
        <div class="{{ $totals->due > 0 ? 'is-due' : '' }}"><b>{{ $inr($totals->due) }}</b><small>{{ $totals->due > 0 ? 'To pay' : 'Nothing to pay' }}</small></div>
    </div>

    @forelse ($orders as $o)
        <a class="zo-box zo-hist" href="{{ route('learner.orders.show', $o->order_no) }}">
            <div class="zo-hist-top">
                <b>Order #{{ \Illuminate\Support\Str::afterLast($o->order_no, '-') }}</b>
                <em class="zo-pill is-{{ $tone($o) }}">{{ $o->statusLabel() }}</em>
            </div>
            <small>{{ $o->created_at->format('j M Y, g:i A') }} · {{ $o->locationLabel() }}</small>
            <div class="zo-hist-bot">
                <span>{{ $o->lines_count }} {{ \Illuminate\Support\Str::plural('item', $o->lines_count) }} · {{ $o->paymentLabel() }}</span>
                <b>{{ $inr($o->total) }}</b>
                <x-lb-icon name="chevron-right" />
            </div>
        </a>
    @empty
        <section class="zo-blocked">
            <span class="zo-blocked-ic"><x-lb-icon name="receipt" /></span>
            <h1>No orders yet</h1>
            <p>Order tea, snacks, stationery or a printout — it comes to your seat.</p>
            @if (Route::has('learner.orders'))<a class="zo-btn" href="{{ route('learner.orders') }}">Order now</a>@endif
        </section>
    @endforelse

    @if ($orders->hasPages())
        <nav class="zo-pages">
            @if ($orders->previousPageUrl())<a href="{{ $orders->previousPageUrl() }}"><x-lb-icon name="chevron-left" />Newer</a>@endif
            <span>Page {{ $orders->currentPage() }} of {{ $orders->lastPage() }}</span>
            @if ($orders->nextPageUrl())<a href="{{ $orders->nextPageUrl() }}">Older<x-lb-icon name="chevron-right" /></a>@endif
        </nav>
    @endif
</div>
@endsection
