{{-- <x-ui.badge tone="green|red|orange|yellow|blue|purple|teal|indigo|gray|solid" dot pill>Paid</x-ui.badge>   color="#7c3aed" for a custom colour --}}
@props(['tone' => 'gray', 'dot' => false, 'pill' => false, 'color' => null])
<span {{ $attributes->merge(['class' => 'ui-badge'.($color ? ' ui-badge--custom' : ($tone !== 'gray' ? ' ui-badge--'.$tone : '')).($pill ? ' ui-badge--pill' : '')]) }} @if($color) style="--c: {{ $color }}" @endif>
    @if($dot)<span class="ui-dot" aria-hidden="true"></span>@endif{{ $slot }}
</span>
