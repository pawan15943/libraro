@extends('layouts.learner')

@section('title', 'Complaints')

@section('content')
<div class="lr-head">
    <div>
        <h1>Complaints</h1>
        <p>Something not right? Tell the library and track the reply.</p>
    </div>
</div>

@include('learner.partials.ticket-page', [
    'action' => route('learner.complaint.store'),
    'items' => collect($data ?? []),
    'noun' => 'complaint',
    'icon' => 'circle-alert',
    'titleHint' => 'e.g. AC not working near my seat',
    'descHint' => 'What happened, where, and since when?',
])
@endsection
