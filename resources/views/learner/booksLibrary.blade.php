@extends('layouts.learner')

@section('title', 'Books')

@section('content')
{{-- The book catalogue is not built yet; the old page showed two made-up books. --}}
<div class="lr-head">
    <div>
        <h1>Books</h1>
        <p>Books you can read at the library.</p>
    </div>
</div>

<div class="lr-card">
    <div class="lr-empty">
        <div class="lr-empty-ic"><x-lb-icon name="book-open" /></div>
        <b>Book list coming soon</b>
        <p>Your library has not added its books here yet. Ask at the desk for what is available.</p>
        <div class="lr-btns" style="justify-content:center;margin-top:14px">
            <a href="{{ route('learner.suggestions') }}" class="lr-btn is-ghost is-sm"><x-lb-icon name="lightbulb" /> Suggest a book</a>
        </div>
    </div>
</div>
@endsection
