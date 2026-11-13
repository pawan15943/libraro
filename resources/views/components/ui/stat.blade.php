{{-- <x-ui.stat label="Libraries" :value="311" note="215 joined" icon="fa-solid fa-building" tone="green|red|orange" href="..." alert /> --}}
@props(['label', 'value', 'note' => null, 'icon' => null, 'tone' => null, 'href' => null, 'alert' => false])
@php $cls = 'ui-stat'.($tone ? ' ui-stat--'.$tone : '').($alert ? ' ui-stat--alert' : ''); @endphp
<{{ $href ? 'a' : 'div' }} @if($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => $cls]) }}>
    <div class="ui-stat__top">
        <span class="ui-stat__label">{{ $label }}</span>
        @if($icon)<span class="ui-stat__icon"><i class="{{ $icon }}"></i></span>@endif
    </div>
    <div class="ui-stat__value">{{ $value }}</div>
    @if($note || $slot->isNotEmpty())<div class="ui-stat__note">{{ $note }}{{ $slot }}</div>@endif
</{{ $href ? 'a' : 'div' }}>
