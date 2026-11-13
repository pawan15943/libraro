@extends('layouts.learner')

@section('title', 'My exam goal')

@section('content')
<div class="lr-head">
    <div>
        <h1>My exam goal</h1>
        <p>Tell us what you are preparing for. We use it on your dashboard.</p>
    </div>
</div>

@if ($errors->any())
    <div class="lr-alert bad"><span class="lr-alert-ic"><x-lb-icon name="circle-alert" /></span><div><b>{{ $errors->first() }}</b></div></div>
@endif

<form method="POST" action="{{ route('learner.goal.save') }}" class="lr-card lr-form" data-lr-form>
    @csrf
    <div class="lr-field">
        <label for="exam_id">Target exam</label>
        <select name="exam_id" id="exam_id" class="lr-select">
            <option value="">Choose your exam</option>
            @foreach ($exams as $e)
                <option value="{{ $e->id }}" @selected(old('exam_id', $goalRow->exam_id ?? '') == $e->id)>{{ $e->name }}</option>
            @endforeach
            <option value="other" @selected(old('exam_id') === 'other' || (!($goalRow->exam_id ?? null) && ($goalRow->exam_name ?? null)))>Other exam</option>
        </select>
    </div>
    <div class="lr-field" id="otherExam" hidden>
        <label for="exam_name">Exam name</label>
        <input type="text" name="exam_name" id="exam_name" class="lr-input" maxlength="120" value="{{ old('exam_name', $goalRow->exam_name ?? '') }}" placeholder="e.g. State Police Constable">
    </div>
    <div class="lr-field">
        <label for="exam_date">Exam date <i>(optional)</i></label>
        <input type="date" name="exam_date" id="exam_date" class="lr-input" min="{{ now()->toDateString() }}" value="{{ old('exam_date', optional($goalRow->exam_date ?? null)->toDateString()) }}">
        <span class="lr-hint">We will count down the days for you.</span>
    </div>
    <div class="lr-field">
        <label for="syllabus">Syllabus <i>(one topic per line)</i></label>
        <textarea name="syllabus" id="syllabus" class="lr-textarea" rows="8" maxlength="5000" placeholder="Polity&#10;Indian Geography&#10;Quantitative Aptitude">{{ old('syllabus', $goalRow->syllabus ?? '') }}</textarea>
        <span class="lr-hint">Your topics show on the dashboard and in the stopwatch.</span>
    </div>
    <button type="submit" class="lr-btn is-block">Save my goal</button>
</form>
@endsection

@push('lr-scripts')
<script>
(function () {
    var sel = document.getElementById('exam_id'), box = document.getElementById('otherExam');
    function sync() { box.hidden = sel.value !== 'other'; }
    sel.addEventListener('change', sync); sync();
})();
</script>
@endpush
