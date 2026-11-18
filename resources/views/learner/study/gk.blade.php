@extends('layouts.learner')

@section('title', 'Daily GK quiz')

@section('content')
@php
    $n = $questions->count();
    $pct = $attempt && $attempt->total ? round($attempt->correct * 100 / $attempt->total) : 0;
    $chips = [['calendar-days', now()->format('l, j M')], ['list-checks', $n . ' questions']];
    if ($streak > 0) $chips[] = ['flame', $streak . ' day streak'];
@endphp

<x-lr-hero icon="zap" kicker="Daily GK quiz" :title="$attempt ? ($pct >= 80 ? 'Excellent work!' : ($pct >= 50 ? 'Good job today!' : 'Keep going, you will improve')) : 'Today\'s 10 questions'"
           :sub="$attempt ? 'You have taken today\'s quiz. A new one comes tomorrow.' : 'Same questions for everyone today. Takes about 3 minutes.'"
           :chips="$chips" :art="!$attempt">
    @if ($attempt)
        <div class="lx-score" style="--p:{{ $pct }}">
            <span><b>{{ $attempt->correct }}/{{ $attempt->total }}</b><small>{{ $pct }}%</small></span>
        </div>
    @endif
</x-lr-hero>

@if ($questions->isEmpty())
    <div class="lx-card lx-empty is-big"><x-lb-icon name="zap" /><b>Today's quiz is not ready yet</b><p>Check back soon.</p></div>
@elseif ($attempt)
    @php $ans = $attempt->answers ?? []; @endphp
    <div class="lx-stats is-3">
        <div class="lx-stat" data-tone="green"><span><x-lb-icon name="circle-check" /></span><b>{{ $attempt->correct }}</b><small>Correct</small></div>
        <div class="lx-stat" data-tone="rose"><span><x-lb-icon name="circle-x" /></span><b>{{ $attempt->wrong }}</b><small>Wrong</small></div>
        <div class="lx-stat" data-tone="indigo"><span><x-lb-icon name="minus" /></span><b>{{ $attempt->skipped }}</b><small>Skipped</small></div>
    </div>

    <div class="lx-sec">Answers</div>
    @foreach ($questions as $i => $q)
        @php $given = $ans[$q->id] ?? ''; $right = $given === $q->correct; @endphp
        <div class="lr-q lx-q {{ $given === '' ? 'is-skip' : ($right ? 'is-right' : 'is-wrong') }}">
            <div class="lx-q-top">
                <span class="lx-q-n">Q{{ $i + 1 }}</span>
                <span class="lx-q-cat">{{ $q->category }}</span>
                <span class="lx-q-res">{{ $given === '' ? 'Skipped' : ($right ? 'Correct' : 'Wrong') }}</span>
            </div>
            <div class="lr-q-t">{{ $q->question }}</div>
            @foreach ($q->options() as $k => $text)
                <div class="lr-opt is-static {{ $k === $q->correct ? 'is-right' : ($k === $given ? 'is-wrong' : '') }}"><i>{{ strtoupper($k) }}</i>{{ $text }}</div>
            @endforeach
            @if ($q->explanation)<div class="lr-why"><x-lb-icon name="lightbulb" /> {{ $q->explanation }}</div>@endif
        </div>
    @endforeach
@else
    <form method="POST" action="{{ route('learner.gk.submit') }}" id="gkForm" data-lr-form>
        @csrf
        <input type="hidden" name="seconds" id="gkSecs" value="0">
        <div class="lr-qz-bar lx-qz-bar">
            <span class="lx-qz-time"><x-lb-icon name="clock" /><span class="lr-qz-time" id="gkTime">00:00</span></span>
            <div class="lr-prog"><i id="gkProg" style="width:0%"></i></div>
            <b id="gkCount">0/{{ $n }}</b>
        </div>
        @foreach ($questions as $i => $q)
            <div class="lr-q lx-q">
                <div class="lx-q-top">
                    <span class="lx-q-n">Q{{ $i + 1 }}</span>
                    <span class="lx-q-cat">{{ $q->category }}</span>
                </div>
                <div class="lr-q-t">{{ $q->question }}</div>
                @foreach ($q->options() as $k => $text)
                    <label class="lr-opt"><input type="radio" name="a[{{ $q->id }}]" value="{{ $k }}"><i>{{ strtoupper($k) }}</i>{{ $text }}</label>
                @endforeach
            </div>
        @endforeach
        <button type="submit" class="lh-btn lx-submit">Submit quiz <x-lb-icon name="arrow-right" /></button>
    </form>
@endif
@endsection

@push('lr-scripts')
@if (!$attempt && $questions->isNotEmpty())
<script>
(function () {
    var form = document.getElementById('gkForm'), t0 = Date.now(), total = {{ $n }};
    var time = document.getElementById('gkTime'), secs = document.getElementById('gkSecs');
    function pad(n) { return (n < 10 ? '0' : '') + n; }
    setInterval(function () { var s = Math.floor((Date.now() - t0) / 1000); secs.value = s; time.textContent = pad(Math.floor(s / 60)) + ':' + pad(s % 60); }, 1000);
    form.addEventListener('change', function (e) {
        var done = new Set([].map.call(form.querySelectorAll('input[type=radio]:checked'), function (r) { return r.name; })).size;
        document.getElementById('gkCount').textContent = done + '/' + total;
        document.getElementById('gkProg').style.width = (done * 100 / total) + '%';
        var q = e.target.closest('.lx-q'); if (q) q.classList.add('is-done');
    });
    form.addEventListener('submit', function (e) {
        var done = form.querySelectorAll('input[type=radio]:checked').length;
        if (done < total && !confirm((total - done) + ' question(s) not answered. Submit anyway?')) { e.preventDefault(); form.querySelector('[type=submit]').classList.remove('is-busy'); }
    });
})();
</script>
@endif
@endpush
