@extends('layouts.learner')

@section('title', 'Study stopwatch')

@section('content')
@php
    $fmt = function ($sec) { $sec = (int) $sec; $h = intdiv($sec, 3600); $m = intdiv($sec % 3600, 60); return $h ? $h . 'h ' . $m . 'm' : $m . 'm'; };
    $todaySessions = $recent->filter(fn ($r) => $r->study_date && $r->study_date->isToday())->count();
@endphp

{{-- Stopwatch (navy hero) --}}
<section class="lh-goal lx-hero lx-sw" id="sw" data-save="{{ route('learner.stopwatch.save') }}">
    <div class="lh-goal-stars" aria-hidden="true"></div>

    <div class="lx-sw-top">
        <span class="lh-k"><x-lb-icon name="timer" />Study stopwatch</span>
        <div class="lx-seg" role="tablist" aria-label="Mode">
            <button type="button" class="is-on" data-mode="study"><x-lb-icon name="book-open" />Study time</button>
            <button type="button" data-mode="questions"><x-lb-icon name="list-checks" />Questions</button>
        </div>
    </div>

    <div class="lx-sw-dial">
        <svg viewBox="0 0 200 200" class="lx-ring" aria-hidden="true">
            <defs>
                <linearGradient id="lxRing" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0" stop-color="#5cc6d1" /><stop offset=".6" stop-color="#34939f" /><stop offset="1" stop-color="#f8a052" />
                </linearGradient>
            </defs>
            <circle cx="100" cy="100" r="88" class="lx-ring-bg" />
            <circle cx="100" cy="100" r="88" class="lx-ring-fg" id="swRing" />
            @for ($t = 0; $t < 60; $t++)
                <line x1="100" y1="{{ $t % 5 ? 6 : 4 }}" x2="100" y2="{{ $t % 5 ? 9 : 11 }}" transform="rotate({{ $t * 6 }} 100 100)" class="lx-tick {{ $t % 5 ? '' : 'is-5' }}" />
            @endfor
        </svg>
        <div class="lx-sw-read">
            <div class="lr-sw-face lx-face" id="swFace" aria-live="off">00:00:00</div>
            <div class="lr-sw-sub lx-sw-sub" id="swSub">Ready when you are</div>
        </div>
    </div>

    <div class="lx-sw-subject">
        <x-lb-icon name="notebook-pen" />
        <input type="text" id="swSubject" list="swTopics" maxlength="120" placeholder="What are you studying? (optional)" aria-label="Subject">
        <datalist id="swTopics">@foreach ($topics as $t)<option value="{{ $t }}">@endforeach</datalist>
    </div>

    <div class="lr-sw-btns lx-sw-btns">
        <button type="button" class="lx-btn is-go" id="swStart"><x-lb-icon name="circle-play" /> Start</button>
        <button type="button" class="lx-btn is-glass" id="swLap" hidden><x-lb-icon name="check" /> Question done</button>
        <button type="button" class="lx-btn is-glass" id="swPause" hidden><x-lb-icon name="circle-pause" /> Pause</button>
        <button type="button" class="lx-btn is-save" id="swSave" hidden><x-lb-icon name="circle-check" /> Save session</button>
        <button type="button" class="lx-btn is-glass" id="swReset" hidden><x-lb-icon name="rotate-ccw" /> Reset</button>
    </div>
    <div class="lr-laps lx-laps" id="swLaps" hidden></div>
</section>

{{-- Numbers --}}
<div class="lx-stats">
    <div class="lx-stat" data-tone="teal"><span><x-lb-icon name="timer" /></span><b id="kToday">{{ $fmt($stats['today_seconds']) }}</b><small>Today</small></div>
    <div class="lx-stat" data-tone="indigo"><span><x-lb-icon name="clock" /></span><b>{{ $fmt($stats['week_seconds']) }}</b><small>This week</small></div>
    <div class="lx-stat" data-tone="orange"><span><x-lb-icon name="flame" /></span><b id="kStreak">{{ $stats['streak'] }} {{ $stats['streak'] == 1 ? 'day' : 'days' }}</b><small>Streak</small></div>
    <div class="lx-stat" data-tone="violet"><span><x-lb-icon name="list-checks" /></span><b>{{ $stats['today_questions'] }}</b><small>Questions today</small></div>
</div>

<div class="lx-grid">
    <section class="lx-card">
        <div class="lx-card-head">
            <span class="lx-ic" data-tone="teal"><x-lb-icon name="chart-column" /></span>
            <div><h2>Last 7 days</h2><p>Time saved with the stopwatch</p></div>
        </div>
        <div class="lh-bars lx-bars">
            @foreach ($stats['week'] as $d)
                <div class="lh-bar-col {{ $d['today'] ? 'is-today' : '' }}">
                    <em>{{ $d['seconds'] ? $fmt($d['seconds']) : '' }}</em>
                    <div class="lh-bar"><span style="height: {{ $d['seconds'] ? max(8, round($d['seconds'] * 100 / $stats['week_max'])) : 0 }}%"></span></div>
                    <small>{{ $d['today'] ? 'Today' : $d['label'] }}</small>
                </div>
            @endforeach
        </div>
        @if ($stats['subjects']->isNotEmpty())
            <div class="lx-subjects">
                @foreach ($stats['subjects'] as $s)
                    <span>{{ $s->subject }} <b>{{ $fmt($s->secs) }}</b></span>
                @endforeach
            </div>
        @endif
    </section>

    <section class="lx-card">
        <div class="lx-card-head">
            <span class="lx-ic" data-tone="indigo"><x-lb-icon name="clock" /></span>
            <div><h2>Recent sessions</h2><p>{{ $todaySessions }} {{ \Illuminate\Support\Str::plural('session', $todaySessions) }} today</p></div>
        </div>
        @forelse ($recent as $r)
            <div class="lx-row">
                <span class="lx-row-ic" data-tone="{{ $r->mode === 'questions' ? 'violet' : 'teal' }}"><x-lb-icon name="{{ $r->mode === 'questions' ? 'list-checks' : 'timer' }}" /></span>
                <div class="lx-row-main">
                    <b>{{ $r->subject ?: ($r->mode === 'questions' ? 'Question practice' : 'Study') }}</b>
                    <small>{{ $r->study_date->isToday() ? 'Today' : $r->study_date->format('j M') }}@if ($r->questions) · {{ $r->questions }} questions @endif</small>
                </div>
                <b class="lx-row-end">{{ $fmt($r->seconds) }}</b>
            </div>
        @empty
            <div class="lx-empty"><x-lb-icon name="timer" /><p>No sessions yet. Press Start and begin.</p></div>
        @endforelse
    </section>
</div>
@endsection

@push('lr-scripts')
<script>
(function () {
    var root = document.getElementById('sw'), KEY = 'lr-stopwatch';
    var face = document.getElementById('swFace'), sub = document.getElementById('swSub'), ring = document.getElementById('swRing');
    var bStart = document.getElementById('swStart'), bPause = document.getElementById('swPause'), bSave = document.getElementById('swSave'),
        bReset = document.getElementById('swReset'), bLap = document.getElementById('swLap'), laps = document.getElementById('swLaps'),
        subject = document.getElementById('swSubject');
    var C = 2 * Math.PI * 88;
    ring.style.strokeDasharray = C; ring.style.strokeDashoffset = C;
    // state survives refresh: elapsed ms before the current run, start time of the current run, lap list
    var st = { mode: 'study', base: 0, startedAt: null, laps: [] }, timer = null;

    function load() { try { var v = JSON.parse(localStorage.getItem(KEY)); if (v && typeof v.base === 'number') st = v; } catch (e) {} }
    function store() { try { localStorage.setItem(KEY, JSON.stringify(st)); } catch (e) {} }
    function elapsed() { return st.base + (st.startedAt ? Date.now() - st.startedAt : 0); }
    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function fmt(ms) { var s = Math.floor(ms / 1000); return pad(Math.floor(s / 3600)) + ':' + pad(Math.floor(s % 3600 / 60)) + ':' + pad(s % 60); }
    // ring: one full lap per hour
    function dial() { var ms = elapsed(); ring.style.strokeDashoffset = C * (1 - (ms % 3600000) / 3600000); }

    function paint() {
        face.textContent = fmt(elapsed()); dial();
        var running = !!st.startedAt, any = elapsed() > 0;
        root.classList.toggle('is-run', running);
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
    function tick() { face.textContent = fmt(elapsed()); dial(); }
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
