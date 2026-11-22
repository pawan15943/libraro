@extends('layouts.learner')

@section('title', 'Home')

@section('content')
@php
    $p = $lr['plan'] ?? null;
    $dues = $lr['dues'] ?? ['amount' => 0];
    $today = $lr['today'] ?? ['state' => 'none'];
    $first = $lr['first_name'] ?? 'there';
    $hour = (int) now()->format('G');
    $greet = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $fmt = function ($sec) { $sec = (int) $sec; $h = intdiv($sec, 3600); $m = intdiv($sec % 3600, 60); return $h ? $h . 'h ' . $m . 'm' : $m . 'm'; };
    $hrs = fn ($h) => rtrim(rtrim(number_format((float) $h, 1), '0'), '.');
    // pages the library switched off on Show / Hide Options (App\Support\FeatureToggles)
    $off = fn ($name) => \App\Support\FeatureToggles::routeHidden($name);
    $showGoalSetup = $showGoalSetup && !$off('learner.goal');
@endphp

@if ($showGoalSetup)
{{-- =====================================================================
     First visit: set the goal in 3 short steps, on a clean page
     ===================================================================== --}}
<div class="lh-setup">
    <div class="lh-setup-art">
        <span class="lh-setup-chip"><x-lb-icon name="graduation-cap" />Your study journey</span>
        <h2>Let's plan your success, <span>{{ $first }}</span>.</h2>
        <p>Three quick steps. Your countdown, topics and library hours will be ready right after.</p>
        @include('learner.partials.portal-study-art', ['class' => 'is-setup'])
        <ul class="lh-setup-points">
            <li><x-lb-icon name="calendar-clock" />Exam countdown</li>
            <li><x-lb-icon name="clock" />Library study hours</li>
            <li><x-lb-icon name="flame" />Daily streaks</li>
        </ul>
    </div>
    <form method="POST" action="{{ route('learner.goal.save') }}" class="lh-wiz" data-lh-wiz novalidate>
        @csrf
        <input type="hidden" name="from" value="home">

        <div class="lh-wiz-top">
            <span class="lh-wiz-badge"><x-lb-icon name="sparkles" />Welcome, {{ $first }}</span>
            <a href="{{ route('learner.home', ['goal' => 'later']) }}" class="lh-wiz-skip">Skip for now</a>
        </div>

        <div class="lh-wiz-steps" aria-hidden="true">
            <span class="is-on"></span><span></span><span></span>
        </div>

        @if ($errors->any())
            <div class="lr-alert bad" style="margin-bottom:16px"><span class="lr-alert-ic"><x-lb-icon name="circle-alert" /></span><div><b>{{ $errors->first() }}</b></div></div>
        @endif

        {{-- Step 1: exam --}}
        <section class="lh-step is-on" data-step="1">
            <span class="lh-step-k">Step 1 of 3</span>
            <h1>What are you preparing for?</h1>
            <p>Pick your exam. We will build your dashboard around it.</p>

            <div class="lh-search">
                <x-lb-icon name="search" />
                <input type="text" placeholder="Search exam…" data-lh-search autocomplete="off">
            </div>
            <div class="lh-exams" data-lh-exams>
                @foreach ($exams as $e)
                    <label class="lh-exam" data-name="{{ strtolower($e->name) }}">
                        <input type="radio" name="exam_id" value="{{ $e->id }}" @checked(old('exam_id') == $e->id)>
                        <span>{{ $e->name }}</span>
                    </label>
                @endforeach
                <label class="lh-exam is-other" data-name="other">
                    <input type="radio" name="exam_id" value="other" @checked(old('exam_id') === 'other')>
                    <span><x-lb-icon name="plus" />Other exam</span>
                </label>
            </div>
            <div class="lh-other" data-lh-other hidden>
                <input type="text" name="exam_name" maxlength="120" class="lh-input" value="{{ old('exam_name') }}" placeholder="Type your exam name, e.g. State Police Constable">
            </div>
            <p class="lh-err" data-lh-err hidden>Please pick your exam to continue.</p>
        </section>

        {{-- Step 2: date --}}
        <section class="lh-step" data-step="2" hidden>
            <span class="lh-step-k">Step 2 of 3</span>
            <h1>When is your exam?</h1>
            <p>We will count down the days and keep you on track.</p>
            <div class="lh-date">
                <x-lb-icon name="calendar-days" />
                <input type="date" name="exam_date" min="{{ now()->toDateString() }}" value="{{ old('exam_date') }}" class="lh-input" data-lh-date>
            </div>
            <div class="lh-quick" data-lh-quick>
                @foreach ([3 => '3 months', 6 => '6 months', 12 => '1 year'] as $m => $label)
                    <button type="button" data-months="{{ $m }}">In {{ $label }}</button>
                @endforeach
            </div>
            <p class="lh-days" data-lh-days hidden></p>
        </section>

        {{-- Step 3: topics --}}
        <section class="lh-step" data-step="3" hidden>
            <span class="lh-step-k">Step 3 of 3</span>
            <h1>Add your main topics</h1>
            <p>One topic per line. Optional, you can add them later too.</p>
            <textarea name="syllabus" rows="6" maxlength="5000" class="lh-input lh-textarea" placeholder="Polity&#10;Indian Geography&#10;Quantitative Aptitude">{{ old('syllabus') }}</textarea>
        </section>

        <div class="lh-wiz-nav">
            <button type="button" class="lh-btn is-ghost" data-lh-back hidden><x-lb-icon name="arrow-left" />Back</button>
            <button type="button" class="lh-btn" data-lh-next>Continue<x-lb-icon name="arrow-right" /></button>
            <button type="submit" class="lh-btn" data-lh-done hidden>Start my journey<x-lb-icon name="sparkles" /></button>
        </div>
    </form>
</div>

@else
{{-- =====================================================================
     Dashboard
     ===================================================================== --}}

{{-- 1. Attendance (top) --}}
@unless ($off('my-attendance'))
{{-- tap = straight to the scan / my-QR page; "Details" lower down opens the month calendar --}}
<a href="{{ route($off('learner.attendance.scan') ? 'my-attendance' : 'learner.attendance.scan') }}" class="lh-att lh-rv" data-st="{{ $today['state'] }}">
    <span class="lh-att-ic">
        <x-lb-icon name="{{ $today['state'] === 'none' ? 'scan-line' : ($today['state'] === 'in' ? 'log-in' : 'circle-check') }}" />
    </span>
    <div class="lh-att-main">
        <small>{{ $greet }}, {{ $first }} · {{ now()->format('D, j M') }}</small>
        <b>
            @if ($today['state'] === 'in') You are in the library since {{ $today['in']->format('g:i A') }}
            @elseif ($today['state'] === 'done') Done for today · {{ $today['in']->format('g:i A') }} – {{ $today['out']->format('g:i A') }}
            @else Not checked in yet. Tap to mark your attendance.
            @endif
        </b>
    </div>
    @if ($today['state'] !== 'done' && !$off('learner.attendance.scan'))
        <span class="lh-att-cta"><x-lb-icon name="qr-code" /> {{ $today['state'] === 'in' ? 'Mark OUT' : 'Mark IN' }}</span>
    @endif
    <div class="lh-att-stats">
        <span><b>{{ $libHours['present'] + $libHours['half'] }}</b><small>Present</small></span>
        <span><b>{{ $libHours['absent'] }}</b><small>Absent</small></span>
        <span class="lh-att-go"><x-lb-icon name="chevron-right" /></span>
    </div>
</a>
@endunless

{{-- The library's running global offer (docs/GLOBAL_DISCOUNT.md) --}}
@if (!empty($offer))
    @php $offerDays = today()->diffInDays($offer->end_date); @endphp
    <div class="lh-offer" role="status">
        <span class="lh-offer-ic"><x-lb-icon name="party-popper" /></span>
        <div><b>{{ $offer->name }}: {{ $offer->label() }}</b><p>On every new booking, renewal and plan change till {{ $offer->end_date->format('j M Y') }}. No code needed.</p></div>
        <span class="lh-offer-left">{{ $offerDays === 0 ? 'Last day' : $offerDays . ' ' . \Illuminate\Support\Str::plural('day', $offerDays) . ' left' }}</span>
    </div>
@endif

{{-- 2. Goal --}}
@if ($off('learner.goal'))
@elseif ($goal && ($goal['exam_name'] || $goal['exam_date']))
    @php
        $hasDate = $goal['exam_date'] && !$goal['is_past'];
        $span = 180;
        $done = $hasDate ? max(3, min(100, round(($span - min($span, $goal['days_left'])) * 100 / $span))) : 0;
    @endphp
    <section class="lh-goal lh-rv">
        <div class="lh-goal-stars" aria-hidden="true"></div>
        <div class="lh-goal-main">
            <span class="lh-k"><x-lb-icon name="flag" />My goal</span>
            <h1>{{ $goal['exam_name'] ?: 'Your exam' }}</h1>
            @if ($hasDate)
                <p>Exam on <b>{{ $goal['exam_date']->format('j M Y') }}</b>. Every hour you put in today counts.</p>
                <div class="lh-goal-bar"><span style="width:{{ $done }}%"></span></div>
            @elseif ($goal['is_past'])
                <p>Your exam date has passed. Set your next goal.</p>
            @else
                <p>Add your exam date to start the countdown.</p>
            @endif
            <div class="lh-goal-meta">
                @if (count($goal['topics']))<span><x-lb-icon name="notebook-pen" />{{ count($goal['topics']) }} {{ \Illuminate\Support\Str::plural('topic', count($goal['topics'])) }}</span>@endif
                @if ($stats['streak'] > 0)<span><x-lb-icon name="flame" />{{ $stats['streak'] }} day study streak</span>@endif
                <a href="{{ route('learner.goal') }}" class="lh-goal-edit">Edit goal <x-lb-icon name="arrow-right" /></a>
            </div>
        </div>
        @include('learner.partials.portal-study-art', ['class' => 'is-hero'])
        <div class="lh-goal-count">
            @if ($hasDate)
                <b>{{ $goal['days_left'] }}</b>
                <small>{{ $goal['days_left'] == 1 ? 'day' : 'days' }} to go</small>
            @else
                <a href="{{ route('learner.goal') }}" class="lh-btn is-sm">Set exam date</a>
            @endif
        </div>
    </section>
@else
    <a href="{{ route('learner.goal') }}" class="lh-goal is-empty lh-rv">
        <div class="lh-goal-stars" aria-hidden="true"></div>
        <div class="lh-goal-main">
            <span class="lh-k"><x-lb-icon name="flag" />My goal</span>
            <h1>Set your exam goal</h1>
            <p>Pick your exam and date. We will show your countdown and topics here.</p>
            <div class="lh-goal-meta"><span class="lh-goal-edit">Set goal <x-lb-icon name="arrow-right" /></span></div>
        </div>
        @include('learner.partials.portal-study-art', ['class' => 'is-hero'])
    </a>
@endif

{{-- 3. Library study hours (60%) + seat & plan (40%) --}}
<div class="lh-split">
    <section class="lh-card lh-hours lh-rv">
        <div class="lh-card-head">
            <div>
                <span class="lh-k"><x-lb-icon name="clock" />Library study hours</span>
                <h2>{{ $hrs($libHours['week_hours']) }}<small> hrs this week</small></h2>
            </div>
            @unless ($off('my-attendance'))<a href="{{ route('my-attendance') }}" class="lh-link">Details <x-lb-icon name="chevron-right" /></a>@endunless
        </div>

        <div class="lh-bars">
            @foreach ($libHours['week'] as $d)
                <div class="lh-bar-col {{ $d['today'] ? 'is-today' : '' }} {{ $d['hours'] > 0 ? '' : 'is-zero' }}">
                    <em>{{ $d['hours'] > 0 ? $hrs($d['hours']) . 'h' : '' }}</em>
                    <div class="lh-bar"><span style="height: {{ $d['hours'] > 0 ? max(8, round($d['hours'] * 100 / $libHours['week_max'])) : 0 }}%"></span></div>
                    <small>{{ $d['today'] ? 'Today' : $d['label'] }}</small>
                </div>
            @endforeach
        </div>

        <div class="lh-hours-foot">
            <div><b>{{ $hrs($libHours['month_hours']) }}h</b><small>In {{ $libHours['month_label'] }}</small></div>
            <div><b>{{ $libHours['visits'] }}</b><small>Days in library</small></div>
            <div><b>{{ $hrs($libHours['avg_hours']) }}h</b><small>Average per day</small></div>
            <div><b>{{ $fmt($stats['today_seconds']) }}</b><small>Stopwatch today</small></div>
        </div>
    </section>

    <section class="lh-card lh-seat lh-rv" data-state="{{ $p['state'] ?? 'none' }}">
        <div class="lh-card-head">
            <span class="lh-k"><x-lb-icon name="armchair" />My seat</span>
            @php [$stText, $stTone] = \App\Services\LearnerPortalService::stateLabel($p); @endphp
            <span class="lr-chip {{ $stTone }}">{{ $stText }}</span>
        </div>
        @if ($p)
            <div class="lh-seat-no">{{ $p['seat'] ?: 'General' }}<small>{{ $p['seat'] ? 'Seat' : 'No fixed seat' }}</small></div>
            <div class="lh-seat-plan">
                <b>{{ $p['name'] }}</b>
                @if ($p['type'] || $p['time'])<span><x-lb-icon name="clock-3" />{{ $p['type'] ?: $p['time'] }}</span>@endif
            </div>
            <div class="lh-seat-bar"><span style="width:{{ $p['state'] === 'expired' ? 100 : max(4, (int) $p['progress']) }}%"></span></div>
            <div class="lh-seat-dates">
                <span>{{ $p['start']?->format('j M') }}</span>
                <span>Ends {{ $p['end']?->format('j M Y') }}</span>
            </div>
        @else
            <p class="lh-muted">No active plan. Ask your library desk to book a seat.</p>
        @endif

        @if (($dues['amount'] ?? 0) > 0)
            <a @unless ($off('my-transactions')) href="{{ route('my-transactions') }}" @endunless class="lh-due {{ !empty($dues['overdue']) ? 'is-over' : '' }}">
                <x-lb-icon name="indian-rupee" />
                <span>₹{{ number_format($dues['amount']) }} {{ !empty($dues['overdue']) ? 'overdue' : 'due' }}@if (!empty($dues['due_date'])) · {{ \Carbon\Carbon::parse($dues['due_date'])->format('j M') }}@endif</span>
                <x-lb-icon name="chevron-right" />
            </a>
        @endif

        @unless ($off('learner.gk'))
        <a href="{{ route('learner.gk') }}" class="lh-gk {{ $gkAttempt ? 'is-done' : '' }}">
            <span class="lh-gk-ic"><x-lb-icon name="zap" /></span>
            <span>
                <b>Daily GK quiz</b>
                <small>
                    @if ($gkAttempt) {{ $gkAttempt->correct }}/{{ $gkAttempt->total }} correct today @if ($gkStreak > 1)· {{ $gkStreak }} day streak @endif
                    @else 10 questions · 3 minutes
                    @endif
                </small>
            </span>
            <em>{{ $gkAttempt ? 'Review' : 'Start' }}</em>
        </a>
        @endunless
    </section>
</div>

{{-- 4. Quick actions --}}
@php
    $actions = array_values(array_filter([
        ($lr['ordering'] ?? null) === 'on' ? ['learner.orders', 'coffee', 'Order', 'Tea, snacks, print…', 'orange'] : null,
        ['learner.attendance.scan', 'scan-line', 'Scan QR', 'Mark attendance', 'green'],
        ['learner.stopwatch', 'timer', 'Stopwatch', 'Track study time', 'teal'],
        ['learner.gk', 'zap', 'GK quiz', 'Daily 10 questions', 'amber'],
        ['learner.quizzes', 'list-checks', 'Quizzes', 'From your library', 'violet'],
        ['my-attendance', 'calendar-check', 'Attendance', 'Month calendar', 'green'],
        ['my-library-id', 'id-card', 'ID card', 'Show at the desk', 'sky'],
        ['my-transactions', 'receipt-text', 'Payments', 'Receipts & dues', 'rose'],
        ['learner.chat', 'message-circle', 'Chat', 'Talk to library', 'indigo'],
        ['learner.request', 'send', 'Requests', 'Seat, shift & more', 'orange'],
    ], fn ($a) => $a && Route::has($a[0]) && !$off($a[0])));
@endphp
<div class="lh-sec">Quick actions</div>
<div class="lh-actions">
    @foreach ($actions as [$name, $icon, $label, $sub, $tone])
        <a href="{{ route($name) }}" class="lh-action lh-rv" data-tone="{{ $tone }}">
            <span class="lh-action-ic"><x-lb-icon :name="$icon" /></span>
            <span class="lh-action-t"><b>{{ $label }}</b><small>{{ $sub }}</small></span>
            <span class="lh-action-go"><x-lb-icon name="arrow-up-right" /></span>
        </a>
    @endforeach
</div>
@endif

{{-- Welcome loader + shimmer, only right after login --}}
@if (session('lr_welcome'))
    <div class="lh-loader" data-lh-loader role="status" aria-live="polite">
        <div class="lh-loader-in">
            @include('learner.partials.portal-study-art', ['class' => 'is-loader'])
            <b>Welcome back, {{ $first }}</b>
            <small>Getting your study space ready…</small>
            <span class="lh-loader-bar"><i></i></span>
        </div>
    </div>
    <script>
        document.body.classList.add('lh-boot', 'lh-booting');
        (function () {
            var t0 = Date.now(), reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            var fired = false;
            function done() {
                if (fired) return; fired = true;
                var wait = reduce ? 0 : Math.max(0, 1100 - (Date.now() - t0));
                setTimeout(function () {
                    var l = document.querySelector('[data-lh-loader]');
                    if (l) { l.classList.add('is-out'); setTimeout(function () { l.remove(); }, 500); }
                    document.body.classList.remove('lh-booting');   // loader gone → shimmer cards show
                    setTimeout(function () { document.body.classList.remove('lh-boot'); }, reduce ? 0 : 750);
                }, wait);
            }
            if (document.readyState === 'complete') done(); else window.addEventListener('load', done);
            setTimeout(done, 4000); // never hang on a slow asset
        })();
    </script>
@endif
@endsection

@if ($showGoalSetup)
@push('lr-scripts')
<script>
(function () {
    var f = document.querySelector('[data-lh-wiz]');
    if (!f) return;
    var steps = f.querySelectorAll('.lh-step'), dots = f.querySelectorAll('.lh-wiz-steps span');
    var back = f.querySelector('[data-lh-back]'), next = f.querySelector('[data-lh-next]'), done = f.querySelector('[data-lh-done]');
    var other = f.querySelector('[data-lh-other]'), err = f.querySelector('[data-lh-err]');
    var i = 0;

    function show(k) {
        i = k;
        steps.forEach(function (s, j) { s.hidden = j !== i; s.classList.toggle('is-on', j === i); });
        dots.forEach(function (d, j) { d.classList.toggle('is-on', j <= i); });
        back.hidden = i === 0;
        next.hidden = i === steps.length - 1;
        done.hidden = i !== steps.length - 1;
        var focus = steps[i].querySelector('input:not([type=radio]), textarea');
        if (i > 0 && focus) setTimeout(function () { focus.focus(); }, 50);
    }
    function picked() { return f.querySelector('input[name=exam_id]:checked'); }
    function valid() {
        var p = picked();
        var ok = p && (p.value !== 'other' || f.exam_name.value.trim() !== '');
        err.hidden = !!ok;
        return !!ok;
    }

    f.addEventListener('change', function (e) {
        if (e.target.name === 'exam_id') {
            other.hidden = e.target.value !== 'other';
            if (!other.hidden) f.exam_name.focus();
            err.hidden = true;
        }
    });
    next.addEventListener('click', function () { if (i === 0 && !valid()) return; show(i + 1); });
    back.addEventListener('click', function () { show(i - 1); });
    f.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA' && i < steps.length - 1) { e.preventDefault(); next.click(); }
    });
    f.addEventListener('submit', function (e) {
        if (!valid()) { e.preventDefault(); show(0); return; }
        done.classList.add('is-busy');
    });

    // exam search
    var q = f.querySelector('[data-lh-search]');
    q.addEventListener('input', function () {
        var v = q.value.trim().toLowerCase();
        f.querySelectorAll('.lh-exam').forEach(function (l) {
            l.hidden = v && l.dataset.name !== 'other' && l.dataset.name.indexOf(v) === -1;
        });
    });

    // date helpers
    var date = f.querySelector('[data-lh-date]'), days = f.querySelector('[data-lh-days]');
    function count() {
        if (!date.value) { days.hidden = true; return; }
        var n = Math.round((new Date(date.value + 'T00:00:00') - new Date(new Date().toDateString())) / 86400000);
        days.hidden = n < 0;
        days.innerHTML = '<b>' + n + '</b> ' + (n === 1 ? 'day' : 'days') + ' to go. You can do this.';
    }
    f.querySelectorAll('[data-lh-quick] button').forEach(function (b) {
        b.addEventListener('click', function () {
            var d = new Date(); d.setMonth(d.getMonth() + parseInt(b.dataset.months, 10));
            date.value = d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0');
            count();
        });
    });
    date.addEventListener('change', count);

    var pre = picked();
    if (pre && pre.value === 'other') other.hidden = false;
    count();
    show(0);
})();
</script>
@endpush
@endif
