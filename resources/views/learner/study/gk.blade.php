@extends('layouts.learner')

@section('title', 'Daily GK quiz')

@section('content')
<div class="lr-head">
    <div>
        <h1>Daily GK quiz</h1>
        <p>{{ now()->format('l, j F') }} · {{ $questions->count() }} questions from Libraro @if ($streak > 1) · {{ $streak }} day streak @endif</p>
    </div>
</div>

@if ($questions->isEmpty())
    <div class="lr-card lr-empty"><div class="lr-empty-ic"><x-lb-icon name="zap" /></div><p>Today's quiz is not ready yet. Check back soon.</p></div>
@elseif ($attempt)
    @php $pct = $attempt->total ? round($attempt->correct * 100 / $attempt->total) : 0; $ans = $attempt->answers ?? []; @endphp
    <div class="lr-card lr-score">
        <div class="lr-score-ring" style="--p:{{ $pct }}"><span>{{ $attempt->correct }}/{{ $attempt->total }}</span></div>
        <b style="font-size:17px">{{ $pct >= 80 ? 'Excellent work!' : ($pct >= 50 ? 'Good job. Keep practising.' : 'Keep going. You will improve.') }}</b>
        <div class="lr-score-row">
            <div><b style="color:var(--lp-ok-ink)">{{ $attempt->correct }}</b>Correct</div>
            <div><b style="color:var(--lp-bad-ink)">{{ $attempt->wrong }}</b>Wrong</div>
            <div><b>{{ $attempt->skipped }}</b>Skipped</div>
        </div>
        <p class="lr-hint" style="margin-top:12px">Come back tomorrow for a new quiz.</p>
    </div>

    <div class="lr-sec">Answers</div>
    @foreach ($questions as $i => $q)
        @php $given = $ans[$q->id] ?? ''; @endphp
        <div class="lr-q">
            <div class="lr-q-n">Q{{ $i + 1 }} · {{ $q->category }}</div>
            <div class="lr-q-t">{{ $q->question }}</div>
            @foreach ($q->options() as $k => $text)
                <div class="lr-opt is-static {{ $k === $q->correct ? 'is-right' : ($k === $given ? 'is-wrong' : '') }}"><i>{{ strtoupper($k) }}</i>{{ $text }}</div>
            @endforeach
            @if ($q->explanation)<div class="lr-why">{{ $q->explanation }}</div>@endif
        </div>
    @endforeach
@else
    <form method="POST" action="{{ route('learner.gk.submit') }}" id="gkForm" data-lr-form>
        @csrf
        <input type="hidden" name="seconds" id="gkSecs" value="0">
        <div class="lr-qz-bar">
            <span class="lr-qz-time" id="gkTime">00:00</span>
            <div class="lr-prog"><i id="gkProg" style="width:0%"></i></div>
            <b id="gkCount" style="font-size:13.5px">0/{{ $questions->count() }}</b>
        </div>
        @foreach ($questions as $i => $q)
            <div class="lr-q">
                <div class="lr-q-n">Q{{ $i + 1 }} · {{ $q->category }}</div>
                <div class="lr-q-t">{{ $q->question }}</div>
                @foreach ($q->options() as $k => $text)
                    <label class="lr-opt"><input type="radio" name="a[{{ $q->id }}]" value="{{ $k }}"><i>{{ strtoupper($k) }}</i>{{ $text }}</label>
                @endforeach
            </div>
        @endforeach
        <button type="submit" class="lr-btn is-block" style="margin-top:16px">Submit quiz</button>
    </form>
@endif
@endsection

@push('lr-scripts')
@if (!$attempt && $questions->isNotEmpty())
<script>
(function () {
    var form = document.getElementById('gkForm'), t0 = Date.now(), total = {{ $questions->count() }};
    var time = document.getElementById('gkTime'), secs = document.getElementById('gkSecs');
    function pad(n) { return (n < 10 ? '0' : '') + n; }
    setInterval(function () { var s = Math.floor((Date.now() - t0) / 1000); secs.value = s; time.textContent = pad(Math.floor(s / 60)) + ':' + pad(s % 60); }, 1000);
    form.addEventListener('change', function () {
        var done = new Set([].map.call(form.querySelectorAll('input[type=radio]:checked'), function (r) { return r.name; })).size;
        document.getElementById('gkCount').textContent = done + '/' + total;
        document.getElementById('gkProg').style.width = (done * 100 / total) + '%';
    });
    form.addEventListener('submit', function (e) {
        var done = form.querySelectorAll('input[type=radio]:checked').length;
        if (done < total && !confirm((total - done) + ' question(s) not answered. Submit anyway?')) { e.preventDefault(); form.querySelector('[type=submit]').classList.remove('is-busy'); }
    });
})();
</script>
@endif
@endpush
