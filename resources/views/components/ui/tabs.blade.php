{{-- <x-ui.tabs :items="[['label' => 'Dashboard', 'href' => route(...), 'active' => true, 'icon' => 'fa-solid fa-chart-line', 'count' => 3]]" /> --}}
@props(['items' => []])
<nav {{ $attributes->merge(['class' => 'ui-tabs']) }}>
    @foreach($items as $t)
        <a href="{{ $t['href'] }}" class="ui-tab {{ !empty($t['active']) ? 'is-active' : '' }}" @if(!empty($t['active'])) aria-current="page" @endif>
            @if(!empty($t['icon']))<i class="{{ $t['icon'] }}" aria-hidden="true"></i>@endif{{ $t['label'] }}@if(!empty($t['count']))<span class="ui-count">{{ $t['count'] }}</span>@endif
        </a>
    @endforeach
</nav>
