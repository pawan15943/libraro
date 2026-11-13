{{-- <x-ui.banner tone="success|danger|warning|info" dismissible>Saved.</x-ui.banner> --}}
@props(['tone' => 'info', 'dismissible' => false, 'icon' => null])
@php $icons = ['success' => 'fa-solid fa-circle-check', 'danger' => 'fa-solid fa-circle-exclamation', 'warning' => 'fa-solid fa-triangle-exclamation', 'info' => 'fa-solid fa-circle-info']; @endphp
<div {{ $attributes->merge(['class' => 'ui-banner'.($tone !== 'info' ? ' ui-banner--'.$tone : '')]) }} role="{{ $tone === 'danger' ? 'alert' : 'status' }}">
    <i class="{{ $icon ?? $icons[$tone] ?? $icons['info'] }}" aria-hidden="true"></i>
    <div class="ui-banner__body">{{ $slot }}</div>
    @if($dismissible)<button type="button" class="ui-banner__close" aria-label="Close" onclick="this.parentNode.remove()">&times;</button>@endif
</div>
