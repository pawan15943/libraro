{{--
    First-time setup steps (librarySetup()): Choose plan → Branch → Shift. Shown only while the
    library owner is in setup. $step: the page's own step (plan|branch|shift); defaults to the
    first step not done yet.
--}}
@php
    $lbSetup = librarySetup();
    $lbStepOn = $step ?? $lbSetup['current'];
    $lbSteps = ['plan' => 'Choose plan', 'branch' => 'Branch', 'shift' => 'Shift'];
    $lbKeys = array_keys($lbSteps);
@endphp
@if($lbSetup['in_setup'])
<nav class="lbs-steps" aria-label="Setup steps">
    <ol>
        @foreach($lbSteps as $key => $label)
            @php
                $state = $key === $lbStepOn ? 'is-on' : ($lbSetup['done'][$key] ? 'is-done' : '');
                $n = array_search($key, $lbKeys) + 1;
            @endphp
            <li class="{{ $state }}" @if($state === 'is-on') aria-current="step" @endif>
                <i>@if($state === 'is-done')<x-lb-icon name="check" />@else{{ $n }}@endif</i>
                <span><small>Step {{ $n }} of 3</small>{{ $label }}</span>
            </li>
        @endforeach
    </ol>
</nav>
<style>
    .lbs-steps { margin: 0 0 20px; }
    .lbs-steps ol { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; margin: 0; padding: 0; list-style: none; }
    .lbs-steps li { position: relative; display: flex; align-items: center; gap: 10px; min-width: 0; padding: 12px 14px; border-radius: 16px; border: 1px solid var(--lp-line, #e6e6e3); background: var(--lp-card, #fff); color: var(--lp-mut2, #8a8f98); }
    .lbs-steps li i { flex: none; width: 30px; height: 30px; border-radius: 50%; display: grid; place-items: center; font-style: normal; font-size: 13px; font-weight: 800; background: var(--lp-soft2, #f1f1ef); color: var(--lp-mut2, #8a8f98); }
    .lbs-steps li i .lb-i { width: 15px; height: 15px; }
    .lbs-steps li span { display: flex; flex-direction: column; min-width: 0; font-size: 14px; font-weight: 700; line-height: 1.25; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .lbs-steps li small { font-size: 11px; font-weight: 600; letter-spacing: .04em; text-transform: uppercase; opacity: .8; }
    .lbs-steps li.is-on { border-color: var(--lp-acc, #1d3c86); background: oklch(0.5 0.14 264 / .06); color: var(--lp-ink, #16181d); box-shadow: 0 0 0 3px oklch(0.5 0.14 264 / .08); }
    .lbs-steps li.is-on i { background: var(--lp-acc, #1d3c86); color: #fff; }
    .lbs-steps li.is-done { color: var(--lp-ink2, #44474f); }
    .lbs-steps li.is-done i { background: oklch(0.62 0.15 155 / .15); color: var(--lp-ok-ink, #1f7a4a); }
    @media (max-width: 560px) {
        .lbs-steps li { padding: 10px; gap: 8px; }
        .lbs-steps li small { display: none; }
        .lbs-steps li span { font-size: 13px; }
    }
</style>
@endif
