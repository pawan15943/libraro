{{-- <x-ui.avatar name="Gyan Deep" size="sm|lg" soft /> — first letters of the first two words --}}
@props(['name' => '', 'size' => null, 'soft' => false, 'src' => null])
@php $initials = collect(preg_split('/\s+/', trim($name)))->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('') ?: '?'; @endphp
<span {{ $attributes->merge(['class' => 'ui-avatar'.($size ? ' ui-avatar--'.$size : '').($soft ? ' ui-avatar--soft' : '')]) }} aria-hidden="true">
    @if($src)<img src="{{ $src }}" alt="">@else{{ $initials }}@endif
</span>
