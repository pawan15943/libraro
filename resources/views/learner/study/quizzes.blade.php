@extends('layouts.learner')

@section('title', 'Library quizzes')

@section('content')
<div class="lr-head">
    <div>
        <h1>Library quizzes</h1>
        <p>Practice tests made by your library.</p>
    </div>
</div>

@forelse ($quizzes as $q)
    @php $best = $q->my_attempts->map(fn ($a) => $a->total ? round($a->correct * 100 / $a->total) : 0)->max(); $last = $q->my_attempts->first(); @endphp
    <div class="lr-card" style="margin-bottom:12px">
        <div class="lr-row" style="border:0;padding:0">
            <span class="lr-row-ic"><x-lb-icon name="notebook-pen" /></span>
            <div class="lr-row-main">
                <b>{{ $q->title }}</b>
                <p>{{ $q->questions_count }} questions @if ($q->duration_minutes) · {{ $q->duration_minutes }} min @endif</p>
            </div>
        </div>
        @if ($q->description)<p class="lr-hint">{{ $q->description }}</p>@endif
        <div class="lr-btns" style="margin-top:12px;align-items:center">
            <a href="{{ route('learner.quiz.play', $q->id) }}" class="lr-btn is-sm">{{ $last ? 'Try again' : 'Start quiz' }}</a>
            @if ($last)
                <a href="{{ route('learner.quiz.result', $last->id) }}" class="lr-btn is-sm is-ghost">Last result</a>
                <small style="color:var(--lp-mut2)">Best {{ $best }}% · taken {{ $q->my_attempts->count() }}×</small>
            @endif
        </div>
    </div>
@empty
    <div class="lr-card lr-empty"><div class="lr-empty-ic"><x-lb-icon name="list-checks" /></div><p>Your library has not added any quiz yet.</p></div>
@endforelse
@endsection
