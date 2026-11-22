@extends('layouts.learner')

@section('title', 'Attendance QR')

@push('lr-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-learner-scan.css') }}?v={{ @filemtime(public_path('css/lb-learner-scan.css')) }}">
@endpush

@section('content')
@php
    $today = $lr['today'] ?? ['state' => 'none'];
    $plan = $lr['plan'] ?? null;
    $planExpired = $plan && $plan['state'] === 'expired';
    $tab = request('show') === 'qr' ? 'qr' : 'scan';
@endphp

<div class="lr-head">
    <div>
        <a href="{{ route('my-attendance') }}" class="lr-back"><x-lb-icon name="arrow-left" /> Attendance</a>
        <h1>Attendance QR</h1>
        <p>Mark IN when you arrive and OUT when you leave: scan the library's QR, or show yours at the desk.</p>
    </div>
</div>

<div class="lsc">
    <div class="lsc-today {{ $today['state'] === 'in' ? 'is-in' : '' }}">
        <x-lb-icon name="{{ $today['state'] === 'none' ? 'scan-line' : ($today['state'] === 'in' ? 'log-in' : 'circle-check') }}" />
        <div>
            <b>@if ($today['state'] === 'in') You are in the library @elseif ($today['state'] === 'done') Done for today @else Not checked in today @endif</b>
            <span>@if ($today['state'] === 'in') Since {{ $today['in']->format('g:i A') }}. Scan again when you leave. @elseif ($today['state'] === 'done') {{ $today['in']->format('g:i A') }} – {{ $today['out']->format('g:i A') }} @else Scan to mark your entry. @endif</span>
        </div>
    </div>

    <div class="lsc-tabs" role="tablist" data-on="{{ $tab }}">
        <button type="button" role="tab" id="lscTabScan" data-tab="scan" aria-controls="lscPaneScan" aria-selected="{{ $tab === 'scan' ? 'true' : 'false' }}"><x-lb-icon name="scan-line" /> Scan library QR</button>
        <button type="button" role="tab" id="lscTabQr" data-tab="qr" aria-controls="lscPaneQr" aria-selected="{{ $tab === 'qr' ? 'true' : 'false' }}"><x-lb-icon name="qr-code" /> My QR</button>
    </div>

    <section class="lsc-card" id="lscPaneScan" role="tabpanel" aria-labelledby="lscTabScan" @if ($tab !== 'scan') hidden @endif>
        <div class="lsc-view" id="lscView" data-state="idle">
            <div id="lscReader" class="lsc-reader"></div>
            <div class="lsc-frame" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
            <div class="lsc-idle">
                <span class="lsc-idle-ic"><x-lb-icon name="camera" /></span>
                <b>Camera is off</b>
                <small>Allow camera access when your browser asks.</small>
            </div>
        </div>

        <div class="lsc-result" id="lscResult" hidden>
            <span class="lsc-result-ic"><x-lb-icon name="circle-check" class="is-ok" /><x-lb-icon name="circle-alert" class="is-bad" /></span>
            <div><b id="lscResultTitle"></b><p id="lscResultText"></p></div>
        </div>

        <div class="lr-btns lsc-acts">
            <button type="button" class="lr-btn is-block" id="lscStart"><x-lb-icon name="camera" /> <span>Start camera</span></button>
            <a href="{{ route('my-attendance') }}" class="lr-btn is-ghost is-block" id="lscDone" hidden><x-lb-icon name="calendar-check" /> See my attendance</a>
        </div>
        <p class="lr-hint lsc-hint" id="lscHint">Hold the phone steady so the whole QR is inside the box.</p>
    </section>

    <section class="lsc-card lsc-my" id="lscPaneQr" role="tabpanel" aria-labelledby="lscTabQr" @if ($tab !== 'qr') hidden @endif>
        @if ($planExpired)
            <div class="lsc-my-off">
                <span class="lsc-idle-ic"><x-lb-icon name="qr-code" /></span>
                <b>Your plan has expired</b>
                <small>Renew your plan to use your attendance QR.</small>
            </div>
        @elseif (!empty($qrPayload))
            <div class="lsc-my-code">
                {!! QrCode::size(260)->margin(0)->errorCorrection('M')->generate($qrPayload) !!}
            </div>
            <div class="lsc-my-who">
                <b>{{ $learner->name }}</b>
                <span>Learner no. {{ $learner->learner_no }}</span>
            </div>
        @endif
        <p class="lr-hint lsc-hint">Show this QR to the library desk scanner to mark IN / OUT. Turn up your screen brightness if it doesn't read.</p>
    </section>
</div>
@endsection

@push('lr-scripts')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(function () {
    var view = document.getElementById('lscView');
    var startBtn = document.getElementById('lscStart');
    var startLabel = startBtn.querySelector('span');
    var doneBtn = document.getElementById('lscDone');
    var hint = document.getElementById('lscHint');
    var box = document.getElementById('lscResult');
    var token = document.querySelector('meta[name="csrf-token"]').content;
    var scanner = null, busy = false;

    function show(ok, title, text) {
        box.hidden = false;
        box.className = 'lsc-result ' + (ok ? 'is-ok' : 'is-bad');
        document.getElementById('lscResultTitle').textContent = title;
        document.getElementById('lscResultText').textContent = text || '';
    }

    function stop() {
        var s = scanner;
        scanner = null;
        view.dataset.state = 'idle';
        return s ? s.stop().then(function () { s.clear(); }).catch(function () {}) : Promise.resolve();
    }

    function submit(qr) {
        busy = true;
        view.dataset.state = 'busy';
        stop().then(function () {
            return fetch(@json(route('learner.attendance.scan.store')), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ qr: qr })
            });
        }).then(function (r) {
            return r.json().catch(function () { return { status: false, message: 'Something went wrong. Please try again.' }; });
        }).then(function (d) {
            if (d.status) {
                show(true, d.message || 'Attendance marked', 'Your attendance is saved.');
                doneBtn.hidden = false;
                startLabel.textContent = 'Scan again';
            } else {
                show(false, d.message || 'Attendance not marked', d.type === 'expired' ? 'Renew your plan to mark attendance.' : 'Try again, or tell the library desk.');
                startLabel.textContent = 'Try again';
            }
        }).catch(function () {
            show(false, 'No internet connection', 'Check your connection and try again.');
            startLabel.textContent = 'Try again';
        }).then(function () {
            busy = false;
            startBtn.hidden = false;
        });
    }

    function start() {
        if (typeof Html5Qrcode === 'undefined') {
            show(false, 'Scanner did not load', 'Check your internet connection and reload the page.');
            return;
        }
        if (!window.isSecureContext) {
            show(false, 'Camera needs a secure link', 'Open the portal with https:// to use the camera.');
            return;
        }
        box.hidden = true;
        startBtn.hidden = true;
        view.dataset.state = 'on';
        scanner = new Html5Qrcode('lscReader');
        scanner.start(
            { facingMode: 'environment' },
            { fps: 10, qrbox: function (w, h) { var s = Math.floor(Math.min(w, h) * 0.7); return { width: s, height: s }; } },
            function (text) { if (!busy && text) { submit(text); } },
            function () {}
        ).catch(function () {
            scanner = null;
            view.dataset.state = 'idle';
            startBtn.hidden = false;
            startLabel.textContent = 'Try again';
            show(false, 'Camera not available', 'Allow camera access for this site in your browser settings, then try again.');
        });
    }

    var tabs = document.querySelector('.lsc-tabs');
    var panes = { scan: document.getElementById('lscPaneScan'), qr: document.getElementById('lscPaneQr') };
    tabs.addEventListener('click', function (e) {
        var b = e.target.closest('[data-tab]');
        if (!b || tabs.dataset.on === b.dataset.tab) { return; }
        var t = b.dataset.tab;
        tabs.dataset.on = t;
        tabs.querySelectorAll('[data-tab]').forEach(function (x) { x.setAttribute('aria-selected', x === b ? 'true' : 'false'); });
        panes.scan.hidden = t !== 'scan';
        panes.qr.hidden = t !== 'qr';
        if (t === 'qr' && scanner) {
            stop().then(function () { startBtn.hidden = false; startLabel.textContent = 'Start camera'; });
        }
        try { history.replaceState(null, '', t === 'qr' ? '?show=qr' : location.pathname); } catch (err) {}
    });

    startBtn.addEventListener('click', start);
    window.addEventListener('pagehide', stop);
})();
</script>
@endpush
