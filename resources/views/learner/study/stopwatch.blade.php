@extends('layouts.learner')

@section('title', 'Study stopwatch')

@section('content')
@php $fmt = function ($sec) { $sec = (int) $sec; $h = intdiv($sec, 3600); $m = intdiv($sec % 3600, 60); return $h ? $h . 'h ' . $m . 'm' : $m . 'm'; }; @endphp
<div class="lr-head">
    <div>
        <h1>Study stopwatch</h1>
        <p>Time your study or question practice. Save the session to build your streak.</p>
    </div>
</div>

<div class="lr-grid is-main">
    <div class="lr-stack">
        <div class="lr-card lr-sw" id="sw" data-save="{{ route('learner.stopwatch.save') }}">
            <div class="lr-sw-mode" role="tablist">
                <button type="button" class="is-on" data-mode="study">Study time</button>
                <button type="button" data-mode="questions">Question practice</button>
            </div>

            <div class="lr-sw-face" id="swFace" aria-live="off">00:00:00</div>
            <div class="lr-sw-sub" id="swSub">Ready when you are</div>

            <div class="lr-field" style="margin-top:16px;text-align:left">
                <label for="swSubject">What are you studying? <i>(optional)</i></label>
                <input type="text" id="swSubject" class="lr-input" list="swTopics" maxlength="120" placeholder="e.g. Polity">
                <datalist id="swTopics">@foreach ($topics as $t)<option value="{{ $t }}">@endforeach</datalist>
            </div>

            <div class="lr-sw-btns">
                <button type="button" class="lr-btn" id="swStart"><x-lb-icon name="circle-play" /> Start</button>
                <button type="button" class="lr-btn is-ghost" id="swLap" hidden><x-lb-icon name="check" /> Question done</button>
                <button type="button" class="lr-btn is-ghost" id="swPause" hidden><x-lb-icon name="circle-pause" /> Pause</button>
                <button type="button" class="lr-btn is-ok" id="swSave" hidden><x-lb-icon name="circle-check" /> Save session</button>
                <button type="button" class="lr-btn is-ghost" id="swReset" hidden><x-lb-icon name="rotate-ccw" /> Reset</button>
            </div>
            <div class="lr-laps" id="swLaps" hidden></div>
        </div>
    </div>

    <div class="lr-stack">
        <div class="lr-kpis" style="grid-template-columns:1fr 1fr">
            <div class="lr-kpi"><span class="lr-kpi-ic"><x-lb-icon name="timer" /></span><b id="kToday">{{ $fmt($stats['today_seconds']) }}</b><small>Today</small></div>
            <div class="lr-kpi" data-tone="warn"><span class="lr-kpi-ic"><x-lb-icon name="flame" /></span><b id="kStreak">{{ $stats['streak'] }} {{ $stats['streak'] == 1 ? 'day' : 'days' }}</b><small>Streak</small></div>
        </div>

        <div class="lr-card">
            <div class="lr-card-title"><h2><x-lb-icon name="chart-column" /> Last 7 days</h2></div>
            <div class="lr-week">
                @foreach ($stats['week'] as $d)
                    <div class="lr-week-col {{ $d['today'] ? 'is-today' : '' }}" title="{{ $fmt($d['seconds']) }}">
                        <div class="lr-week-bar" style="height: {{ max(4, round($d['seconds'] * 100 / $stats['week_max'])) }}%"></div>
                        <small>{{ $d['label'] }}</small>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="lr-card">
            <div class="lr-card-title"><h2><x-lb-icon name="clock" /> Recent sessions</h2></div>
            @forelse ($recent as $r)
                <div class="lr-row">
                    <span class="lr-row-ic"><x-lb-icon name="{{ $r->mode === 'questions' ? 'list-checks' : 'timer' }}" /></span>
                    <div class="lr-row-main"><b>{{ $r->subject ?: ($r->mode === 'questions' ? 'Question practice' : 'Study') }}</b><p>{{ $r->study_date->format('j M') }}@if ($r->questions) · {{ $r->questions }} questions @endif</p></div>
                    <div class="lr-row-end"><b>{{ $fmt($r->seconds) }}</b></div>
                </div>
            @empty
                <div class="lr-empty"><p>No sessions yet. Start the stopwatch.</p></div>
            @endforelse
        </div>
    </div>
</div>
@endsection

@push('lr-scripts')
<script>
(function () {
    var root = document.getElementById('sw'), KEY = 'lr-stopwatch';
    var face = document.getElementById('swFace'), sub = document.getElementById('swSub');
    var bStart = document.getElementById('swStart'), bPause = document.getElementById('swPause'), bSave = document.getElementById('swSave'),
        bReset = document.getElementById('swReset'), bLap = document.getElementById('swLap'), laps = document.getElementById('swLaps'),
        subject = document.getElementById('swSubject');
    // state survives refresh: elapsed ms before the current run, start time of the current run, lap list
    var st = { mode: 'study', base: 0, startedAt: null, laps: [] }, timer = null;

    function load() { try { var v = JSON.parse(localStorage.getItem(KEY)); if (v && typeof v.base === 'number') st = v; } catch (e) {} }
    function store() { try { localStorage.setItem(KEY, JSON.stringify(st)); } catch (e) {} }
    function elapsed() { return st.base + (st.startedAt ? Date.now() - st.startedAt : 0); }
    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function fmt(ms) { var s = Math.floor(ms / 1000); return pad(Math.floor(s / 3600)) + ':' + pad(Math.floor(s % 3600 / 60)) + ':' + pad(s % 60); }

    function paint() {
        face.textContent = fmt(elapsed());
        var running = !!st.startedAt, any = elapsed() > 0;
        face.classList.toggle('is-run', running);
        bStart.hidden = running || any; bPause.hidden = !running;
        bLap.hidden = !(st.mode === 'questions' && any);
        bSave.hidden = !any || running; bReset.hidden = !any;
        if (any && !running) { bStart.hidden = false; bStart.innerHTML = bStart.innerHTML.replace('Start', 'Resume'); }
        sub.textContent = running ? 'Keep going. Stay focused.' : (any ? 'Paused. Save your session or resume.' : 'Ready when you are');
        root.querySelectorAll('[data-mode]').forEach(function (b) { b.classList.toggle('is-on', b.dataset.mode === st.mode); });
        laps.hidden = st.mode !== 'questions' || !st.laps.length;
        laps.innerHTML = st.laps.slice().reverse().map(function (l, i) {
            return '<div class="lr-lap"><b>Q' + (st.laps.length - i) + '</b><span>' + fmt(l.t) + '</span></div>';
        }).join('');
    }
    function tick() { face.textContent = fmt(elapsed()); }
    function run() { if (!timer) timer = setInterval(tick, 250); }

    bStart.onclick = function () { st.startedAt = Date.now(); store(); run(); paint(); };
    bPause.onclick = function () { st.base = elapsed(); st.startedAt = null; clearInterval(timer); timer = null; store(); paint(); };
    bReset.onclick = function () {
        if (elapsed() > 60000 && !confirm('Discard this session?')) return;
        st = { mode: st.mode, base: 0, startedAt: null, laps: [] }; clearInterval(timer); timer = null; store(); paint();
        bStart.innerHTML = bStart.innerHTML.replace('Resume', 'Start');
    };
    bLap.onclick = function () {
        var last = st.laps.length ? st.laps[st.laps.length - 1].at : 0, now = elapsed();
        st.laps.push({ at: now, t: now - last }); store(); paint();
    };
    root.querySelectorAll('[data-mode]').forEach(function (b) {
        b.onclick = function () { if (elapsed() > 0) { alert('Save or reset this session first.'); return; } st.mode = b.dataset.mode; store(); paint(); };
    });
    bSave.onclick = function () {
        var secs = Math.round(elapsed() / 1000);
        if (secs < 10) { alert('Study at least 10 seconds before saving.'); return; }
        bSave.classList.add('is-busy');
        fetch(root.dataset.save, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ seconds: secs, subject: subject.value.trim(), mode: st.mode, questions: st.laps.length })
        }).then(function (r) { if (!r.ok) throw new Error(); return r.json(); }).then(function () {
            st = { mode: st.mode, base: 0, startedAt: null, laps: [] }; store(); location.reload();
        }).catch(function () { bSave.classList.remove('is-busy'); alert('Could not save. Check your internet and try again.'); });
    };

    load(); if (st.startedAt) run(); paint();
})();
</script>
@endpush
