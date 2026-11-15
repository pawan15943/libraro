{{--
    Lists validation errors that the page has no inline @error spot for (e.g. hidden inputs
    like learner_id / plan_start_date), so a failed submit never looks like "nothing happened".

    Usage: @include('partials.form-error-summary', ['except' => ['name', 'mobile', ...]])
    where `except` = fields the page already shows inline via @error.
--}}
@php
    $shownInline = $except ?? [];
    $unshownErrors = collect($errors->getMessages())
        ->reject(fn ($messages, $field) => in_array($field, $shownInline, true))
        ->map(fn ($messages) => $messages[0]);
@endphp

@if ($unshownErrors->isNotEmpty())
    <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
            @foreach ($unshownErrors as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </div>
@endif
