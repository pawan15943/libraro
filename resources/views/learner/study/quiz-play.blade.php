@extends('layouts.learner')

@section('title', $quiz->title)

@section('content')
@php $total = $quiz->questions->count(); $n = 0; $subjectNames = $subjects->keys()->values(); @endphp
<div class="lr-head">
    <div>
        <h1>{{ $quiz->title }}</h1>
        <p>{{ $total }} questions · {{ $subjects->count() }} {{ \Illuminate\Support\Str::plural('subject', $subjects->count()) }} @if ($quiz->duration_minutes) · {{ $quiz->duration_minutes }} min @endif</p>
    </div>
</div>

<form method="POST" action="{{ route('learner.quiz.submit', $quiz->id) }}" id="qzForm" data-lr-form data-limit="{{ $quiz->duration_minutes * 60 }}">
    @csrf
    <input type="hidden" name="seconds" id="qzSecs" value="0">
    <div class="lr-qz-bar">
        <span class="lr-qz-time" id="qzTime">{{ $quiz->duration_minutes ? sprintf('%02d:00', $quiz->duration_minutes) : '00:00' }}</span>
        <div class="lr-prog"><i id="qzProg" style="width:0%"></i></div>
        <b id="qzCount" style="font-size:13.5px">0/{{ $total }}</b>
    </div>

    @if ($subjects->count() > 1)
        <div class="lr-tabs-sub" role="tablist">
            @foreach ($subjectNames as $i => $name)
                <button type="button" data-tab="{{ $i }}" class="{{ $i === 0 ? 'is-on' : '' }}">{{ $name }} ({{ $subjects[$name]->count() }})</button>
            @endforeach
        </div>
    @endif

    @foreach ($subjectNames as $si => $name)
        <section data-pane="{{ $si }}" @if ($si !== 0) hidden @endif>
            @foreach ($subjects[$name] as $q)
                @php $n++; @endphp
                <div class="lr-q">
                    <div class="lr-q-n">Q{{ $n }} · {{ $name }}</div>
                    <div class="lr-q-t">{{ $q->question }}</div>
                    @foreach ($q->options() as $k => $text)
                        <label class="lr-opt"><input type="radio" name="a[{{ $q->id }}]" value="{{ $k }}"><i>{{ strtoupper($k) }}</i>{{ $text }}</label>
                    @endforeach
                </div>
            @endforeach
        </section>
    @endforeach

    <button type="submit" class="lr-btn is-block" style="margin-top:16px">Submit quiz</button>
</form>
@endsection

@push('lr-scripts')
<script>
(function () {
    var form = document.getElementById('qzForm'), limit = parseInt(form.dataset.limit, 10) || 0, total = {{ $total }}, t0 = Date.now(), submitted = false;
    var time = document.getElementById('qzTime'), secs = document.getElementById('qzSecs');
    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function mmss(s) { return pad(Math.floor(s / 60)) + ':' + pad(s % 60); }
    setInterval(function () {
        var s = Math.floor((Date.now() - t0) / 1000); secs.value = s;
        if (limit) {
            var left = Math.max(0, limit - s); time.textContent = mmss(left); time.classList.toggle('is-low', left <= 60);
            if (left === 0 && !submitted) { submitted = true; form.submit(); }
        } else time.textContent = mmss(s);
    }, 1000);
    form.addEventListener('change', function () {
        var done = form.querySelectorAll('input[type=radio]:checked').length;
        document.getElementById('qzCount').textContent = done + '/' + total;
        document.getElementById('qzProg').style.width = (done * 100 / total) + '%';
    });
    form.querySelectorAll('[data-tab]').forEach(function (b) {
        b.addEventListener('click', function () {
            form.querySelectorAll('[data-tab]').forEach(function (x) { x.classList.toggle('is-on', x === b); });
            form.querySelectorAll('[data-pane]').forEach(function (p) { p.hidden = p.dataset.pane !== b.dataset.tab; });
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    });
    form.addEventListener('submit', function (e) {
        if (submitted) return;
        var done = form.querySelectorAll('input[type=radio]:checked').length;
        if (done < total && !confirm((total - done) + ' question(s) not answered. Submit anyway?')) { e.preventDefault(); form.querySelector('[type=submit]').classList.remove('is-busy'); return; }
        submitted = true;
    });
})();
</script>
@endpush
