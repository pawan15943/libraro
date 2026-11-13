@extends('layouts.learner')

@section('title', 'Quiz result')

@section('content')
@php
    $pct = $attempt->total ? round($attempt->correct * 100 / $attempt->total) : 0;
    $ans = $attempt->answers ?? [];
    $mins = intdiv($attempt->seconds, 60); $secs = $attempt->seconds % 60;
@endphp
<div class="lr-head">
    <div>
        <h1>{{ $quiz->title }}</h1>
        <p>Taken on {{ $attempt->created_at->format('j M Y') }} · {{ $mins }}m {{ $secs }}s</p>
    </div>
</div>

<div class="lr-grid is-main">
    <div class="lr-stack">
        <div class="lr-card lr-score">
            <div class="lr-score-ring" style="--p:{{ $pct }}"><span>{{ $pct }}%</span></div>
            <b style="font-size:17px">{{ $attempt->correct }} of {{ $attempt->total }} correct</b>
            <div class="lr-score-row">
                <div><b style="color:var(--lp-ok-ink)">{{ $attempt->correct }}</b>Correct</div>
                <div><b style="color:var(--lp-bad-ink)">{{ $attempt->wrong }}</b>Wrong</div>
                <div><b>{{ $attempt->skipped }}</b>Skipped</div>
            </div>
            <div class="lr-btns" style="justify-content:center;margin-top:16px">
                <a href="{{ route('learner.quiz.play', $quiz->id) }}" class="lr-btn is-sm">Try again</a>
                <a href="{{ route('learner.quizzes') }}" class="lr-btn is-sm is-ghost">All quizzes</a>
            </div>
        </div>

        @if (!empty($attempt->by_subject))
            <div class="lr-card">
                <div class="lr-card-title"><h2><x-lb-icon name="chart-pie" /> Subject wise</h2></div>
                @foreach ($attempt->by_subject as $name => $r)
                    @php $sp = $r['total'] ? round($r['correct'] * 100 / $r['total']) : 0; @endphp
                    <div class="lr-sub-row"><span>{{ $name }}</span><div class="lr-prog"><i style="width:{{ $sp }}%"></i></div><b>{{ $r['correct'] }}/{{ $r['total'] }}</b></div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="lr-stack">
        <div class="lr-sec" style="margin-top:0">Review answers</div>
        @foreach ($quiz->questions as $i => $q)
            @php $given = $ans[$q->id] ?? ''; @endphp
            <div class="lr-q">
                <div class="lr-q-n">Q{{ $i + 1 }} · {{ $q->subject }}</div>
                <div class="lr-q-t">{{ $q->question }}</div>
                @foreach ($q->options() as $k => $text)
                    <div class="lr-opt is-static {{ $k === $q->correct ? 'is-right' : ($k === $given ? 'is-wrong' : '') }}"><i>{{ strtoupper($k) }}</i>{{ $text }}</div>
                @endforeach
                @if ($q->explanation)<div class="lr-why">{{ $q->explanation }}</div>@endif
            </div>
        @endforeach
    </div>
</div>
@endsection
