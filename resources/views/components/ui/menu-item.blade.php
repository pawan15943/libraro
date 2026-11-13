{{-- see x-ui.menu --}}
@props(['href' => null, 'icon' => null, 'danger' => false])
@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => 'dropdown-item'.($danger ? ' is-danger' : '')]) }}>@if($icon)<i class="{{ $icon }}"></i>@endif{{ $slot }}</a>
@else
    <button type="button" {{ $attributes->merge(['class' => 'dropdown-item'.($danger ? ' is-danger' : '')]) }}>@if($icon)<i class="{{ $icon }}"></i>@endif{{ $slot }}</button>
@endif
