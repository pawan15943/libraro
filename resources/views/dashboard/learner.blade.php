@extends('layouts.learner')

@section('title', 'Home')

@section('content')
@php
    $p = $lr['plan'] ?? null;
    $dues = $lr['dues'] ?? ['amount' => 0];
    $today = $lr['today'] ?? ['state' => 'none'];
    $hour = (int) now()->format('G');
    $greet = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $phone = $lr['library']['phone'] ?? null;
    $fmt = function ($sec) { $sec = (int) $sec; $h = intdiv($sec, 3600); $m = intdiv($sec % 3600, 60); return $h ? $h . 'h ' . $m . 'm' : $m . 'm'; };
@endphp

<div class="lr-head">
    <div>
        <h1>{{ $greet }}, {{ $lr['first_name'] ?? '' }}</h1>
        <p>{{ $goal && $goal['exam_name'] ? 'Keep going. Every hour counts for ' . $goal['exam_name'] . '.' : 'Plan your study and keep your seat on track.' }}</p>
    </div>
</div>

{{-- The library's running global offer (docs/GLOBAL_DISCOUNT.md) --}}
@if (!empty($offer))
    @php $offerDays = today()->diffInDays($offer->end_date); @endphp
    <div class="lr-promo is-offer" role="status">
        <span class="lr-promo-ic"><x-lb-icon name="party-popper" /></span>
        <div>
            <b>{{ $offer->name }}: {{ $offer->label() }}</b>
            <p>On every new booking, renewal and plan change till {{ $offer->end_date->format('j M Y') }}. It is taken off by itself, no code needed.</p>
        </div>
        <span class="lr-offer-left">{{ $offerDays === 0 ? 'Last day' : $offerDays . ' ' . \Illuminate\Support\Str::plural('day', $offerDays) . ' left' }}</span>
    </div>
@endif

<div class="lr-grid is-main">
    <div class="lr-stack">
        {{-- Exam goal + countdown --}}
        @if ($goal && ($goal['exam_name'] || $goal['exam_date']))
            <div class="lr-goal">
                <div class="lr-goal-k">Preparing for</div>
                <div class="lr-goal-name">{{ $goal['exam_name'] ?: 'Your exam' }}</div>
                @if ($goal['exam_date'] && !$goal['is_past'])
                    <div class="lr-goal-days"><b>{{ $goal['days_left'] }}</b><span>{{ $goal['days_left'] == 1 ? 'day' : 'days' }} to go · {{ $goal['exam_date']->format('j M Y') }}</span></div>
                    @php $span = 180; $done = max(0, min(100, round(($span - min($span, $goal['days_left'])) * 100 / $span))); @endphp
                    <div class="lr-bar"><span style="width:{{ $done }}%"></span></div>
                @elseif ($goal['is_past'])
                    <p>Your exam date has passed. Set your next goal.</p>
                @else
                    <p>Add your exam date to see the countdown.</p>
                @endif
                <a href="{{ route('learner.goal') }}" class="lr-btn is-sm">{{ $goal['exam_date'] && !$goal['is_past'] ? 'Edit goal' : 'Set exam date' }}</a>
            </div>
        @else
            <div class="lr-goal is-empty">
                <div class="lr-goal-k">Your goal</div>
                <div class="lr-goal-name">Which exam are you preparing for?</div>
                <p>Add your exam, date and syllabus. We will show your countdown and topics here.</p>
                <a href="{{ route('learner.goal') }}" class="lr-btn is-sm">Set my goal</a>
            </div>
        @endif

        {{-- Study numbers --}}
        <div class="lr-kpis">
            <div class="lr-kpi"><span class="lr-kpi-ic"><x-lb-icon name="timer" /></span><b>{{ $fmt($stats['today_seconds']) }}</b><small>Studied today</small></div>
            <div class="lr-kpi" data-tone="violet"><span class="lr-kpi-ic"><x-lb-icon name="clock" /></span><b>{{ $fmt($stats['week_seconds']) }}</b><small>This week</small></div>
            <div class="lr-kpi" data-tone="warn"><span class="lr-kpi-ic"><x-lb-icon name="flame" /></span><b>{{ $stats['streak'] }} {{ $stats['streak'] == 1 ? 'day' : 'days' }}</b><small>Study streak</small></div>
            <div class="lr-kpi" data-tone="ok"><span class="lr-kpi-ic"><x-lb-icon name="clipboard-check" /></span><b>{{ $quizOverview['quizzes_taken'] ? $quizOverview['avg'] . '%' : '—' }}</b><small>Quiz average</small></div>
        </div>

        {{-- Daily GK quiz --}}
        <div class="lr-promo {{ $gkAttempt ? 'is-done' : '' }}">
            <span class="lr-promo-ic"><x-lb-icon name="zap" /></span>
            <div>
                <b>Daily GK quiz</b>
                <p>
                    @if ($gkAttempt) Today: {{ $gkAttempt->correct }}/{{ $gkAttempt->total }} correct @if ($gkStreak > 1) · {{ $gkStreak }} day streak @endif
                    @else 10 questions from Libraro. Takes 3 minutes.
                    @endif
                </p>
            </div>
            <a href="{{ route('learner.gk') }}" class="lr-btn is-sm">{{ $gkAttempt ? 'Review' : 'Start' }}</a>
        </div>

        {{-- Last 7 days --}}
        <div class="lr-card">
            <div class="lr-card-title"><h2><x-lb-icon name="chart-column" /> Last 7 days</h2><a href="{{ route('learner.stopwatch') }}">Open stopwatch</a></div>
            <div class="lr-week">
                @foreach ($stats['week'] as $d)
                    <div class="lr-week-col {{ $d['today'] ? 'is-today' : '' }}" title="{{ $fmt($d['seconds']) }}">
                        <div class="lr-week-bar" style="height: {{ max(4, round($d['seconds'] * 100 / $stats['week_max'])) }}%"></div>
                        <small>{{ $d['label'] }}</small>
                    </div>
                @endforeach
            </div>
        </div>


        {{-- Today --}}
        <a href="{{ route('my-attendance') }}" class="lr-card lr-today" data-st="{{ $today['state'] }}" style="display:flex">
            <span class="lr-today-ic"><x-lb-icon name="{{ $today['state'] === 'none' ? 'fingerprint-pattern' : ($today['state'] === 'in' ? 'log-in' : 'circle-check') }}" /></span>
            <div style="flex:1;min-width:0">
                <b>
                    @if ($today['state'] === 'in') You are in the library
                    @elseif ($today['state'] === 'done') Done for today
                    @else Not checked in today
                    @endif
                </b>
                <p>
                    @if ($today['state'] === 'in') Since {{ $today['in']->format('g:i A') }}
                    @elseif ($today['state'] === 'done') {{ $today['in']->format('g:i A') }} – {{ $today['out']->format('g:i A') }}
                    @else Scan the library QR when you arrive
                    @endif
                </p>
            </div>
            <x-lb-icon name="chevron-right" style="color:var(--lp-mut3)" />
        </a>
    </div>

    <div class="lr-stack">
        @if ($goal && count($goal['topics']))
            <div class="lr-card">
                <div class="lr-card-title"><h2><x-lb-icon name="notebook-pen" /> My syllabus</h2><a href="{{ route('learner.goal') }}">Edit</a></div>
                <div class="lr-topics">
                    @foreach (array_slice($goal['topics'], 0, 12) as $t)<span class="lr-topic">{{ $t }}</span>@endforeach
                    @if (count($goal['topics']) > 12)<span class="lr-topic">+{{ count($goal['topics']) - 12 }} more</span>@endif
                </div>
            </div>
        @endif

        @if ($libraryQuizzes->isNotEmpty())
            <div class="lr-card">
                <div class="lr-card-title"><h2><x-lb-icon name="list-checks" /> Quizzes from your library</h2><a href="{{ route('learner.quizzes') }}">See all</a></div>
                <div class="lr-rows">
                    @foreach ($libraryQuizzes as $q)
                        <a href="{{ route('learner.quiz.play', $q->id) }}" class="lr-row">
                            <span class="lr-row-ic"><x-lb-icon name="notebook-pen" /></span>
                            <div class="lr-row-main"><b>{{ $q->title }}</b><p>{{ $q->questions_count }} questions @if($q->duration_minutes) · {{ $q->duration_minutes }} min @endif</p></div>
                            <x-lb-icon name="chevron-right" style="color:var(--lp-mut3)" />
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <div>
            <div class="lr-sec" style="margin-top:0">Quick links</div>
            <div class="lr-actions">
                <a href="{{ route('my-library-id') }}" class="lr-action"><span class="lr-action-ic"><x-lb-icon name="id-card" /></span>ID card</a>
                <a href="{{ route('my-attendance') }}" class="lr-action" data-tone="ok"><span class="lr-action-ic"><x-lb-icon name="calendar-check" /></span>Attendance</a>
                <a href="{{ route('my-transactions') }}" class="lr-action" data-tone="violet"><span class="lr-action-ic"><x-lb-icon name="receipt-text" /></span>Receipts</a>
                <a href="{{ route('learner.request') }}" class="lr-action" data-tone="warn"><span class="lr-action-ic"><x-lb-icon name="send" /></span>Requests</a>
                <a href="{{ route('complaints') }}" class="lr-action" data-tone="pink"><span class="lr-action-ic"><x-lb-icon name="circle-alert" /></span>Complaint</a>
                <a href="{{ route('support') }}" class="lr-action"><span class="lr-action-ic"><x-lb-icon name="headset" /></span>Contact</a>
            </div>
        </div>

        @if (isset($learnerupdates) && $learnerupdates->isNotEmpty())
            <div class="lr-card">
                <div class="lr-card-title"><h2><x-lb-icon name="megaphone" /> Updates</h2></div>
                <div class="lr-updates">
                    @foreach ($learnerupdates as $u)
                        <div class="lr-update"><x-lb-icon name="sparkles" /><span>{{ $u->message }}</span></div>
                    @endforeach
                </div>
            </div>
        @endif

        @if (isset($features) && $features->isNotEmpty())
            <div class="lr-card">
                <div class="lr-card-title"><h2><x-lb-icon name="sparkles" /> At your library</h2></div>
                <div class="lr-feats">
                    @foreach ($features as $f)
                        <span class="lr-feat">
                            @if ($f->image)<img src="{{ asset('public/' . $f->image) }}" alt="" loading="lazy">@endif
                            {{ $f->name }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
