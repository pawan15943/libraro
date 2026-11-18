@extends('layouts.learner')

@section('title', 'Library quizzes')

@section('content')
@php
    $count = $quizzes->count();
    $tried = $quizzes->filter(fn ($q) => $q->my_attempts->isNotEmpty())->count();
    $tones = ['teal', 'violet', 'orange', 'indigo', 'rose', 'sky'];
@endphp

<x-lr-hero icon="list-checks" kicker="Library quizzes" title="Practice tests" sub="Made by your library. Take them as many times as you like."
           :chips="[['notebook-pen', $count . ' ' . \Illuminate\Support\Str::plural('quiz', $count)], ['circle-check', $tried . ' tried']]">
    @if ($overview['quizzes_taken'])
        <div class="lx-score" style="--p:{{ $overview['avg'] }}">
            <span><b>{{ $overview['avg'] }}%</b><small>average</small></span>
        </div>
    @endif
</x-lr-hero>

@if ($quizzes->isEmpty())
    <div class="lx-card lx-empty is-big"><x-lb-icon name="list-checks" /><b>No quizzes yet</b><p>Your library has not added any quiz. Try the daily GK quiz meanwhile.</p>
        @if (Route::has('learner.gk'))<a href="{{ route('learner.gk') }}" class="lh-btn is-sm">Open GK quiz</a>@endif
    </div>
@else
    <div class="lx-quizzes">
        @foreach ($quizzes as $q)
            @php
                $best = $q->my_attempts->map(fn ($a) => $a->total ? round($a->correct * 100 / $a->total) : 0)->max();
                $last = $q->my_attempts->first();
                $tone = $tones[$loop->index % count($tones)];
            @endphp
            <article class="lx-quiz" data-tone="{{ $tone }}">
                <div class="lx-quiz-top">
                    <span class="lx-ic" data-tone="{{ $tone }}"><x-lb-icon name="notebook-pen" /></span>
                    @if ($last)<span class="lx-pill is-ok"><x-lb-icon name="circle-check" />Taken {{ $q->my_attempts->count() }}×</span>@else<span class="lx-pill">New</span>@endif
                </div>
                <h3>{{ $q->title }}</h3>
                @if ($q->description)<p>{{ \Illuminate\Support\Str::limit($q->description, 110) }}</p>@endif
                <div class="lx-quiz-meta">
                    <span><x-lb-icon name="list-checks" />{{ $q->questions_count }} questions</span>
                    @if ($q->duration_minutes)<span><x-lb-icon name="clock" />{{ $q->duration_minutes }} min</span>@endif
                </div>
                @if ($last)
                    <div class="lx-best"><span>Best score</span><b>{{ $best }}%</b></div>
                    <div class="lx-meter"><i style="width:{{ max(3, $best) }}%"></i></div>
                @endif
                <div class="lx-quiz-btns">
                    <a href="{{ route('learner.quiz.play', $q->id) }}" class="lh-btn is-sm">{{ $last ? 'Try again' : 'Start quiz' }}<x-lb-icon name="arrow-right" /></a>
                    @if ($last)<a href="{{ route('learner.quiz.result', $last->id) }}" class="lh-btn is-sm is-ghost">Last result</a>@endif
                </div>
            </article>
        @endforeach
    </div>
@endif
@endsection
