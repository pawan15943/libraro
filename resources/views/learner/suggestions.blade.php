@extends('layouts.learner')

@section('title', 'Suggestions')

@section('content')
<div class="lr-head">
    <div>
        <h1>Suggestions</h1>
        <p>Have an idea to make the library better? Share it.</p>
    </div>
</div>

@include('learner.partials.ticket-page', [
    'action' => route('learner.suggestion.store'),
    'items' => collect($data ?? []),
    'noun' => 'suggestion',
    'icon' => 'lightbulb',
    'titleHint' => 'e.g. Add a water cooler on the first floor',
    'descHint' => 'Tell us your idea and how it would help.',
])
@endsection
