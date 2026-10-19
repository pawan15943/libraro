@props(['name', 'size' => null])
@php($lbIconSprite = asset('public/img/lb-icons.svg') . '?v=' . @filemtime(public_path('img/lb-icons.svg')))
<svg {{ $attributes->merge(['class' => 'lb-i lb-i-' . $name]) }} @if($size) width="{{ $size }}" height="{{ $size }}" style="width:{{ $size }}px;height:{{ $size }}px" @endif aria-hidden="true" focusable="false"><use href="{{ $lbIconSprite }}#i-{{ $name }}"></use></svg>
