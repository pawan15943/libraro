@props(['name', 'size' => 18])
@php($lbIconSprite = asset('public/img/lb-icons.svg') . '?v=' . @filemtime(public_path('img/lb-icons.svg')))
<svg {{ $attributes->merge(['class' => 'lb-ib']) }} width="{{ $size }}" height="{{ $size }}" aria-hidden="true" focusable="false"><use href="{{ $lbIconSprite }}#b-{{ $name }}"></use></svg>
