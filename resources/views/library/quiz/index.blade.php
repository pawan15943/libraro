@extends('layouts.library')

@section('title', 'Student quizzes')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-pages.css') }}?v={{ @filemtime(public_path('css/lb-pages.css')) }}">
<link rel="stylesheet" href="{{ asset('public/css/lb-quiz.css') }}?v={{ @filemtime(public_path('css/lb-quiz.css')) }}">
@endpush

@section('content')
@php
    $live = $quizzes->where('is_published', true)->count();
    $attempts = $quizzes->sum('attempts_count');
@endphp

<div class="lpg lqz">
    <header class="lpg-head">
        <div class="lpg-head-t">
            <div class="lpg-crumb"><a href="{{ route('library.home') }}">Dashboard</a><x-lb-icon name="chevron-right" /><span>Student quizzes</span></div>
            <h1>Student quizzes</h1>
            <p>Make a practice test for your students. Once you publish it, they see it in their app and get their score right away.</p>
        </div>
        @if($quizzes->isNotEmpty())
        <div class="lpg-head-r">
            <a href="{{ route('quiz.create') }}" class="lp-btn"><x-lb-icon name="plus" />New quiz</a>
        </div>
        @endif
    </header>

    @if($quizzes->isEmpty())
        <section class="lpg-card lqz-empty">
            <span class="lqz-empty-ic"><x-lb-icon name="list-checks" /></span>
            <h2>Make your first quiz</h2>
            <p>Takes a few minutes. Up to {{ \App\Models\Quiz::MAX_QUESTIONS }} questions, in up to {{ \App\Models\Quiz::MAX_SUBJECTS }} subjects.</p>
            <ol class="lqz-steps">
                <li><i>1</i>Give the quiz a name</li>
                <li><i>2</i>Type or paste your questions and tap the right answer</li>
                <li><i>3</i>Publish, and your students can take it</li>
            </ol>
            <a href="{{ route('quiz.create') }}" class="lp-btn"><x-lb-icon name="plus" />New quiz</a>
        </section>
    @else
        <div class="lpg-stats lqz-stats">
            <div class="lpg-stat"><span class="lpg-stat-ic"><x-lb-icon name="list-checks" /></span><span>Quizzes</span><b>{{ $quizzes->count() }}</b></div>
            <div class="lpg-stat is-ok"><span class="lpg-stat-ic"><x-lb-icon name="eye" /></span><span>Published</span><b>{{ $live }}</b></div>
            <div class="lpg-stat is-violet"><span class="lpg-stat-ic"><x-lb-icon name="users-round" /></span><span>Attempts</span><b>{{ $attempts }}</b></div>
        </div>

        <div class="lqz-grid">
            @foreach ($quizzes as $q)
            @php
                $avg = ($q->attempts_count && $q->questions_count) ? (int) round(($q->avg_correct / $q->questions_count) * 100) : null;
            @endphp
            <article class="lqz-card {{ $q->is_published ? 'is-live' : '' }}">
                <div class="lqz-card-top">
                    <span class="lqz-card-ic"><x-lb-icon name="notebook-pen" /></span>
                    <div class="lqz-card-t">
                        <h3 title="{{ $q->title }}">{{ $q->title }}</h3>
                        <span class="lpg-tag {{ $q->is_published ? 'is-ok' : '' }}">
                            <x-lb-icon name="{{ $q->is_published ? 'eye' : 'eye-off' }}" />{{ $q->is_published ? 'Students can see it' : 'Draft · hidden' }}
                        </span>
                    </div>
                </div>

                <ul class="lqz-facts">
                    <li><x-lb-icon name="list-checks" /><b>{{ $q->questions_count }}</b> {{ \Illuminate\Support\Str::plural('question', $q->questions_count) }}</li>
                    <li><x-lb-icon name="timer" />{{ $q->duration_minutes ? $q->duration_minutes . ' min' : 'No time limit' }}</li>
                    <li><x-lb-icon name="users-round" /><b>{{ $q->attempts_count }}</b> {{ \Illuminate\Support\Str::plural('attempt', $q->attempts_count) }}</li>
                    @if($avg !== null)
                    <li><x-lb-icon name="chart-column" />Avg score <b>{{ $avg }}%</b></li>
                    @endif
                </ul>

                <div class="lqz-card-act">
                    <form method="POST" action="{{ route('quiz.toggle', $q->id) }}">@csrf
                        <button class="lp-btn {{ $q->is_published ? 'lp-btn--ghost' : '' }}">
                            <x-lb-icon name="{{ $q->is_published ? 'eye-off' : 'send' }}" />{{ $q->is_published ? 'Hide' : 'Publish' }}
                        </button>
                    </form>
                    <a href="{{ route('quiz.edit', $q->id) }}" class="lp-btn lp-btn--ghost"><x-lb-icon name="pencil" />Edit</a>
                    <form method="POST" action="{{ route('quiz.destroy', $q->id) }}" class="lqz-del" onsubmit="return confirm('Delete “{{ addslashes($q->title) }}”? Students will no longer see it.')">@csrf @method('DELETE')
                        <button class="lqz-ibtn is-danger" title="Delete quiz" aria-label="Delete quiz"><x-lb-icon name="trash" /></button>
                    </form>
                </div>
            </article>
            @endforeach
        </div>
    @endif
</div>
@endsection
