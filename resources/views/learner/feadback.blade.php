@extends('layouts.learner')

@section('title', 'Feedback')

@section('content')
@php
    $choice = function ($name, $options) {
        return collect($options)->map(fn ($label, $value) =>
            '<label class="lr-choice"><input type="radio" name="' . $name . '" value="' . $value . '"'
            . ((string) old($name) === (string) $value ? ' checked' : '') . ' required><span>' . e($label) . '</span></label>'
        )->implode('');
    };
@endphp

<div class="lr-head">
    <div>
        <h1>Feedback</h1>
        <p>Two minutes. It helps the library get better for you.</p>
    </div>
</div>

@if ($is_feedback)
    <div class="lr-card">
        <div class="lr-empty">
            <div class="lr-empty-ic" style="background:var(--lp-ok-soft);color:var(--lp-ok-ink)"><x-lb-icon name="circle-check" /></div>
            <b>Thanks, your feedback is in</b>
            <p>You have already shared your feedback with the library.</p>
            <div class="lr-btns" style="justify-content:center;margin-top:14px">
                <a href="{{ route('learner.suggestions') }}" class="lr-btn is-ghost is-sm"><x-lb-icon name="lightbulb" /> Share an idea</a>
                <a href="{{ route('learner.home') }}" class="lr-btn is-sm">Back to home</a>
            </div>
        </div>
    </div>
@else
    <form action="{{ route('learner.feedback.store') }}" method="POST" class="lr-card lr-form" data-lr-form novalidate style="max-width:640px">
        @csrf

        <div class="lr-field">
            <span class="lr-label">How do you rate the library?</span>
            <div class="lr-stars" role="radiogroup" aria-label="Rating">
                @for ($i = 5; $i >= 1; $i--)
                    <input type="radio" id="fb-r{{ $i }}" name="rating" value="{{ $i }}" @checked((int) old('rating') === $i) required>
                    <label for="fb-r{{ $i }}" title="{{ $i }} of 5"><x-lb-icon name="star" /><span hidden>{{ $i }} star</span></label>
                @endfor
            </div>
            @error('rating')<span class="lr-err">Please pick a rating.</span>@enderror
        </div>

        <div class="lr-field">
            <span class="lr-label">How often do you come?</span>
            <div class="lr-choices">{!! $choice('frequency', [1 => 'Daily', 2 => 'Weekly', 3 => 'Sometimes', 4 => 'Rarely']) !!}</div>
            @error('frequency')<span class="lr-err">Please choose one.</span>@enderror
        </div>

        <div class="lr-field">
            <label for="fb-purpose">Why do you come?</label>
            <input id="fb-purpose" type="text" name="purpose" class="lr-input @error('purpose') is-bad @enderror" maxlength="255" required
                value="{{ old('purpose') }}" placeholder="e.g. Exam prep, self study">
            @error('purpose')<span class="lr-err">{{ $message }}</span>@enderror
        </div>

        <div class="lr-grid-2">
            <div class="lr-field">
                <span class="lr-label">Books you need are there?</span>
                <div class="lr-choices">{!! $choice('resources', [1 => 'Yes', 2 => 'No']) !!}</div>
                @error('resources')<span class="lr-err">Please choose one.</span>@enderror
            </div>
            <div class="lr-field">
                <span class="lr-label">Staff is helpful?</span>
                <div class="lr-choices">{!! $choice('staff', [1 => 'Yes', 2 => 'No']) !!}</div>
                @error('staff')<span class="lr-err">Please choose one.</span>@enderror
            </div>
        </div>

        <div class="lr-field">
            <label for="fb-res">Books or things you want added <i>(optional)</i></label>
            <textarea id="fb-res" name="resource_suggestions" class="lr-textarea" maxlength="500" style="min-height:80px">{{ old('resource_suggestions') }}</textarea>
        </div>

        <div class="lr-field">
            <label for="fb-com">Anything else? <i>(optional)</i></label>
            <textarea id="fb-com" name="comments" class="lr-textarea" maxlength="500" style="min-height:80px"
                placeholder="What do you like most? What should change?">{{ old('comments') }}</textarea>
        </div>

        <button type="submit" class="lr-btn is-block"><x-lb-icon name="send" /> Send feedback</button>
    </form>
@endif
@endsection
