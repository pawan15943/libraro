{{-- Icons that show the page's counts, shift counts and filters (hidden until tapped).
     Sections are marked data-vt="counts|shifts|filters" (public/js/lb-viewtoggle.js).
     Params: $vt (keys shown, in order), $vtActive (number of filters in use: a badge on the filter icon). --}}
@php
    $vtMeta = [
        'counts' => ['chart-column', 'Counts'],
        'shifts' => ['clock-3', 'Shifts'],
        'filters' => ['funnel', 'Filters'],
    ];
@endphp
<div class="vt-bar" role="group" aria-label="Show on this page">
    @foreach(($vt ?? ['counts', 'shifts', 'filters']) as $vtKey)
    <button type="button" class="vt-btn" data-vt-btn="{{ $vtKey }}" aria-pressed="false" title="{{ $vtMeta[$vtKey][1] }}" aria-label="{{ $vtMeta[$vtKey][1] }}">
        <x-lb-icon name="{{ $vtMeta[$vtKey][0] }}" />
        @if($vtKey === 'filters' && !empty($vtActive))<em>{{ $vtActive }}</em>@endif
    </button>
    @endforeach
</div>
