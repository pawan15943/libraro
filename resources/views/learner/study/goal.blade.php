@extends('layouts.learner')

@section('title', 'My exam goal')

@push('lr-styles')
    <link rel="stylesheet" href="{{ asset('public/css/lb-learner-goal.css') }}?v={{ @filemtime(public_path('css/lb-learner-goal.css')) }}">
@endpush

@section('content')
@php
    $selExam = old('exam_id', ($goalRow->exam_id ?? null) ?: ((!($goalRow->exam_id ?? null) && ($goalRow->exam_name ?? null)) ? 'other' : ''));
    $selDate = old('exam_date', optional($goalRow->exam_date ?? null)->toDateString());
    $hasDate = $goal && $goal['exam_date'] && !$goal['is_past'];
    $topics = $goal['topics'] ?? [];
@endphp

{{-- Summary --}}
<section class="lh-goal lg-hero">
    <div class="lh-goal-stars" aria-hidden="true"></div>
    <div class="lh-goal-main">
        <span class="lh-k"><x-lb-icon name="flag" />{{ $goal ? 'Your current goal' : 'Exam goal' }}</span>
        <h1>{{ $goal['exam_name'] ?? 'Set your exam goal' }}</h1>
        @if ($hasDate)
            <p>Exam on <b>{{ $goal['exam_date']->format('l, j M Y') }}</b>. Keep showing up every day.</p>
        @elseif ($goal && $goal['is_past'])
            <p>Your exam date has passed. Pick your next exam and date below.</p>
        @elseif ($goal)
            <p>Add your exam date below to start the countdown.</p>
        @else
            <p>Tell us what you are preparing for. Your dashboard, countdown and stopwatch use it.</p>
        @endif
        <div class="lh-goal-meta">
            <span><x-lb-icon name="notebook-pen" />{{ count($topics) }} {{ \Illuminate\Support\Str::plural('topic', count($topics)) }}</span>
            @if ($hasDate)<span><x-lb-icon name="calendar-days" />{{ $goal['exam_date']->format('M Y') }}</span>@endif
        </div>
    </div>
    @include('learner.partials.portal-study-art', ['class' => 'is-hero'])
    @if ($hasDate)
        <div class="lh-goal-count">
            <b>{{ $goal['days_left'] }}</b>
            <small>{{ $goal['days_left'] == 1 ? 'day' : 'days' }} to go</small>
        </div>
    @endif
</section>

@if ($errors->any())
    <div class="lr-alert bad lg-alert"><span class="lr-alert-ic"><x-lb-icon name="circle-alert" /></span><div><b>{{ $errors->first() }}</b></div></div>
@endif

<form method="POST" action="{{ route('learner.goal.save') }}" class="lg-form" data-lg-form data-lr-form novalidate>
    @csrf

    {{-- 1. Exam --}}
    <section class="lg-sec">
        <div class="lg-sec-head">
            <span class="lg-num">1</span>
            <div>
                <h2>Target exam</h2>
                <p>Pick the exam you are preparing for.</p>
            </div>
        </div>
        <div class="lg-sec-body">
            <div class="lh-search lg-search">
                <x-lb-icon name="search" />
                <input type="text" placeholder="Search exam…" data-lg-search autocomplete="off" aria-label="Search exam">
            </div>
            <div class="lh-exams lg-exams" role="radiogroup" aria-label="Target exam">
                @foreach ($exams as $e)
                    <label class="lh-exam" data-name="{{ strtolower($e->name) }}">
                        <input type="radio" name="exam_id" value="{{ $e->id }}" @checked((string) $selExam === (string) $e->id)>
                        <span>{{ $e->name }}</span>
                    </label>
                @endforeach
                <label class="lh-exam is-other" data-name="other">
                    <input type="radio" name="exam_id" value="other" @checked($selExam === 'other')>
                    <span><x-lb-icon name="plus" />Other exam</span>
                </label>
            </div>
            <p class="lg-empty" data-lg-nomatch hidden>No exam found. Choose <b>Other exam</b> and type its name.</p>
            <div class="lg-other" data-lg-other hidden>
                <label class="lg-label" for="exam_name">Exam name</label>
                <input type="text" name="exam_name" id="exam_name" class="lh-input" maxlength="120" value="{{ old('exam_name', $goalRow->exam_name ?? '') }}" placeholder="e.g. State Police Constable">
            </div>
        </div>
    </section>

    {{-- 2. Date --}}
    <section class="lg-sec">
        <div class="lg-sec-head">
            <span class="lg-num">2</span>
            <div>
                <h2>Exam date <i>Optional</i></h2>
                <p>We count down the days on your dashboard.</p>
            </div>
        </div>
        <div class="lg-sec-body">
            <div class="lg-date-row">
                <div class="lh-date lg-date">
                    <x-lb-icon name="calendar-days" />
                    <input type="date" name="exam_date" id="exam_date" min="{{ now()->toDateString() }}" value="{{ $selDate }}" class="lh-input" data-lg-date aria-label="Exam date">
                    <button type="button" class="lg-clear" data-lg-clear title="Clear date" aria-label="Clear date" @if (!$selDate) hidden @endif><x-lb-icon name="x" /></button>
                </div>
                <div class="lg-count" data-lg-count @if (!$selDate) hidden @endif>
                    <b data-lg-days>0</b><small>days to go</small>
                </div>
            </div>
            <div class="lh-quick">
                @foreach ([1 => '1 month', 3 => '3 months', 6 => '6 months', 12 => '1 year'] as $m => $label)
                    <button type="button" data-months="{{ $m }}">In {{ $label }}</button>
                @endforeach
            </div>
        </div>
    </section>

    {{-- 3. Topics --}}
    <section class="lg-sec">
        <div class="lg-sec-head">
            <span class="lg-num">3</span>
            <div>
                <h2>Syllabus topics <i>Optional</i></h2>
                <p>They show on your dashboard and in the stopwatch.</p>
            </div>
        </div>
        <div class="lg-sec-body">
            <div class="lg-tags" data-lg-tags hidden>
                <div class="lg-tag-list" data-lg-list></div>
                <input type="text" class="lg-tag-input" data-lg-input maxlength="120" placeholder="Type a topic and press Enter" aria-label="Add topic">
            </div>
            <div class="lg-tag-foot" data-lg-foot hidden>
                <span data-lg-total>0 topics</span>
                <button type="button" data-lg-clearall>Clear all</button>
            </div>
            {{-- the field that is saved; shown as-is when JavaScript is off --}}
            <textarea name="syllabus" id="syllabus" class="lh-input lh-textarea lg-raw" rows="6" maxlength="5000" placeholder="Polity&#10;Indian Geography&#10;Quantitative Aptitude" data-lg-raw>{{ old('syllabus', $goalRow->syllabus ?? '') }}</textarea>
        </div>
    </section>

    {{-- Save bar --}}
    <div class="lg-bar">
        <p><x-lb-icon name="shield-check" />Only you can see your goal.</p>
        <a href="{{ route('learner.home') }}" class="lh-btn is-ghost is-sm">Cancel</a>
        <button type="submit" class="lh-btn is-sm lg-save">Save goal<x-lb-icon name="check" /></button>
    </div>
</form>
@endsection

@push('lr-scripts')
<script>
(function () {
    var f = document.querySelector('[data-lg-form]');
    if (!f) return;

    /* exam: search + other */
    var other = f.querySelector('[data-lg-other]'), q = f.querySelector('[data-lg-search]'), none = f.querySelector('[data-lg-nomatch]');
    function syncOther() { var p = f.querySelector('input[name=exam_id]:checked'); other.hidden = !p || p.value !== 'other'; }
    f.addEventListener('change', function (e) {
        if (e.target.name === 'exam_id') { syncOther(); if (!other.hidden) f.exam_name.focus(); }
    });
    q.addEventListener('input', function () {
        var v = q.value.trim().toLowerCase(), shown = 0;
        f.querySelectorAll('.lh-exam').forEach(function (l) {
            var hide = v && l.dataset.name !== 'other' && l.dataset.name.indexOf(v) === -1;
            l.hidden = hide;
            if (!hide && l.dataset.name !== 'other') shown++;
        });
        none.hidden = !v || shown > 0;
    });
    syncOther();
    var cur = f.querySelector('input[name=exam_id]:checked');
    if (cur) cur.closest('.lh-exam').scrollIntoView({ block: 'nearest' });

    /* date: countdown, quick picks, clear */
    var date = f.querySelector('[data-lg-date]'), count = f.querySelector('[data-lg-count]'), days = f.querySelector('[data-lg-days]'), clr = f.querySelector('[data-lg-clear]');
    function ymd(d) { return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0'); }
    function syncDate() {
        clr.hidden = !date.value;
        if (!date.value) { count.hidden = true; return; }
        var n = Math.round((new Date(date.value + 'T00:00:00') - new Date(new Date().toDateString())) / 86400000);
        count.hidden = n < 0; days.textContent = n;
    }
    f.querySelectorAll('[data-months]').forEach(function (b) {
        b.addEventListener('click', function () {
            var d = new Date(); d.setMonth(d.getMonth() + parseInt(b.dataset.months, 10));
            date.value = ymd(d); syncDate();
        });
    });
    clr.addEventListener('click', function () { date.value = ''; syncDate(); date.focus(); });
    date.addEventListener('change', syncDate);
    syncDate();

    /* topics: chips over the hidden textarea */
    var raw = f.querySelector('[data-lg-raw]'), wrap = f.querySelector('[data-lg-tags]'), list = f.querySelector('[data-lg-list]'),
        input = f.querySelector('[data-lg-input]'), foot = f.querySelector('[data-lg-foot]'), total = f.querySelector('[data-lg-total]');
    var tags = raw.value.split(/\r?\n/).map(function (s) { return s.trim(); }).filter(Boolean);
    raw.hidden = true; wrap.hidden = false;

    function render() {
        list.innerHTML = '';
        tags.forEach(function (t, i) {
            var c = document.createElement('span');
            c.className = 'lg-tag';
            c.textContent = t;
            var x = document.createElement('button');
            x.type = 'button'; x.setAttribute('aria-label', 'Remove ' + t); x.innerHTML = '&times;';
            x.addEventListener('click', function () { tags.splice(i, 1); render(); input.focus(); });
            c.appendChild(x);
            list.appendChild(c);
        });
        raw.value = tags.join('\n');
        foot.hidden = !tags.length;
        total.textContent = tags.length + (tags.length === 1 ? ' topic' : ' topics');
    }
    function add(v) {
        v.split(/[\n,]/).map(function (s) { return s.trim(); }).filter(Boolean).forEach(function (s) {
            if (tags.map(function (t) { return t.toLowerCase(); }).indexOf(s.toLowerCase()) === -1) tags.push(s);
        });
        render();
    }
    input.addEventListener('keydown', function (e) {
        if ((e.key === 'Enter' || e.key === ',') && input.value.trim()) { e.preventDefault(); add(input.value); input.value = ''; }
        else if (e.key === 'Enter') e.preventDefault();
        else if (e.key === 'Backspace' && !input.value && tags.length) { tags.pop(); render(); }
    });
    input.addEventListener('paste', function (e) {
        var t = (e.clipboardData || window.clipboardData).getData('text');
        if (/[\n,]/.test(t)) { e.preventDefault(); add(t); }
    });
    wrap.addEventListener('click', function (e) { if (e.target === wrap || e.target === list) input.focus(); });
    f.querySelector('[data-lg-clearall]').addEventListener('click', function () { tags = []; render(); input.focus(); });
    f.addEventListener('submit', function () { if (input.value.trim()) { add(input.value); input.value = ''; } });
    render();
})();
</script>
@endpush
