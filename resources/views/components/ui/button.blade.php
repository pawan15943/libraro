{{--
  <x-ui.button>Save</x-ui.button>
  <x-ui.button variant="tinted|gray|plain|outline|destructive|danger|success" size="sm|lg" icon="fa-solid fa-plus" href="..." block pill>Text</x-ui.button>
  Icon-only: <x-ui.button icon="fa-solid fa-pen" variant="gray" label="Edit" />
--}}
@props(['variant' => 'filled', 'size' => null, 'icon' => null, 'href' => null, 'type' => 'button', 'block' => false, 'pill' => false, 'label' => null])
@php
    $iconOnly = $slot->isEmpty() && $icon;
    $classes = collect([
        'ui-btn',
        $variant !== 'filled' ? 'ui-btn--'.$variant : null,
        $size ? 'ui-btn--'.$size : null,
        $iconOnly ? 'ui-btn--icon' : null,
        $block ? 'ui-btn--block' : null,
        $pill ? 'ui-btn--pill' : null,
    ])->filter()->implode(' ');
@endphp
@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }} @if($iconOnly && $label) aria-label="{{ $label }}" title="{{ $label }}" @endif>
        @if($icon)<i class="{{ $icon }}" aria-hidden="true"></i>@endif{{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }} @if($iconOnly && $label) aria-label="{{ $label }}" title="{{ $label }}" @endif>
        @if($icon)<i class="{{ $icon }}" aria-hidden="true"></i>@endif{{ $slot }}
    </button>
@endif
