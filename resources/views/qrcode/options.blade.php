@extends('sitelayouts.layout')
@section('content')
<style>
    header, footer { display: none; }
    .qo-page { min-height: 100vh; background: #f1f5f9; padding: 16px 0 32px; font-family: 'Geist', system-ui, -apple-system, 'Segoe UI', sans-serif; }
    .qo-wrap { max-width: 480px; margin: 0 auto; padding: 0 16px; }
    .qo-brand { display: block; width: 120px; margin: 4px auto 16px; }
    .qo-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 18px; padding: 16px; box-shadow: 0 1px 2px rgba(15, 23, 42, .04); }
    .qo-card h2 { font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 4px; }
    .qo-card > p { font-size: 13.5px; color: #64748b; margin: 0 0 12px; }
    .qo-list { list-style: none; margin: 0; padding: 0; display: grid; gap: 10px; }
    .qo-list li { margin: 0; }
    .qo-list a { display: flex; align-items: center; gap: 12px; padding: 14px; border-radius: 14px; border: 1px solid #e2e8f0; background: #fff; text-decoration: none !important; transition: border-color .15s, background .15s; }
    .qo-list a:hover, .qo-list a:focus-visible { border-color: #5eead4; background: #f0fdfa; }
    .qo-ic { flex: 0 0 44px; width: 44px; height: 44px; border-radius: 12px; display: grid; place-items: center; }
    .qo-ic svg { width: 22px; height: 22px; }
    .qo-ic.is-book { background: #ccfbf1; color: #0f766e; }
    .qo-ic.is-renew { background: #e0f2fe; color: #0369a1; }
    .qo-ic.is-att { background: #ede9fe; color: #6d28d9; }
    .qo-t { flex: 1; min-width: 0; }
    .qo-t b { display: block; font-size: 15px; font-weight: 700; color: #0f172a; }
    .qo-t small { display: block; font-size: 12.5px; color: #64748b; margin-top: 2px; }
    .qo-go { flex: 0 0 18px; width: 18px; height: 18px; color: #94a3b8; }
    @media (min-width: 576px) { .qo-page { padding-top: 32px; } }
</style>

<div class="qo-page">
    <div class="qo-wrap">
        <a href="{{ '/' }}"><img src="{{ asset('public/img/libraro.webp') }}" alt="Libraro" class="qo-brand"></a>

        @include('qrcode.partials.branch-hero', ['branch' => $branch])

        <div class="qo-card">
            <h2>What would you like to do?</h2>
            <p>Choose an option to continue.</p>
            <ul class="qo-list">
                <li>
                    <a href="{{ route('booking.form', $branch->uuid) }}">
                        <span class="qo-ic is-book"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 18v-6a5 5 0 1 1 10 0v6"/><path d="M5 21h14"/><path d="M5 18h14"/></svg></span>
                        <span class="qo-t"><b>Book a seat</b><small>New here? Pick your plan, shift and seat</small></span>
                        <svg class="qo-go" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                    </a>
                </li>
                <li>
                    <a href="{{ route('renew.form', $branch->uuid) }}">
                        <span class="qo-ic is-renew"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 12a9 9 0 0 1-15.36 6.36L3 16"/><path d="M3 12a9 9 0 0 1 15.36-6.36L21 8"/><path d="M21 3v5h-5"/><path d="M3 21v-5h5"/></svg></span>
                        <span class="qo-t"><b>Renew my seat</b><small>Already a member? Continue your plan</small></span>
                        <svg class="qo-go" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                    </a>
                </li>
                <li>
                    <a href="{{ route('qr.attendance.link') }}">
                        <span class="qo-ic is-att"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="m9 16 2 2 4-4"/></svg></span>
                        <span class="qo-t"><b>Mark attendance</b><small>Open the attendance app</small></span>
                        <svg class="qo-go" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
@endsection
