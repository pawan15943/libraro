{{--
    Limo — the Libraro AI mascot. A round, friendly bot holding a little open book.
    @include('partials.limo-avatar', ['uid' => 'unique-id', 'class' => 'extra-class'])
    `uid` keeps gradient ids unique per copy (a copy inside a hidden element would otherwise
    break the gradients of the others).
--}}
@php($u = 'limo-'.($uid ?? 'x'))
<svg class="limo {{ $class ?? '' }}" viewBox="0 0 64 64" aria-hidden="true" focusable="false">
    <defs>
        <linearGradient id="{{ $u }}-body" x1="12" y1="10" x2="54" y2="56" gradientUnits="userSpaceOnUse">
            <stop offset="0" stop-color="#2dd4bf"/>
            <stop offset="1" stop-color="#38bdf8"/>
        </linearGradient>
        <radialGradient id="{{ $u }}-shine" cx="22" cy="17" r="24" gradientUnits="userSpaceOnUse">
            <stop offset="0" stop-color="#ffffff" stop-opacity=".6"/>
            <stop offset="1" stop-color="#ffffff" stop-opacity="0"/>
        </radialGradient>
        <linearGradient id="{{ $u }}-ear" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#14b8a6"/>
            <stop offset="1" stop-color="#0e9fbf"/>
        </linearGradient>
    </defs>

    {{-- antenna --}}
    <path d="M32 12.5V6.5" stroke="#0f766e" stroke-width="2.4" stroke-linecap="round"/>
    <circle class="limo-bulb" cx="32" cy="5" r="3.3" fill="#fbbf24"/>

    {{-- ears --}}
    <rect x="4.5" y="27" width="7.5" height="15" rx="3.75" fill="url(#{{ $u }}-ear)"/>
    <rect x="52" y="27" width="7.5" height="15" rx="3.75" fill="url(#{{ $u }}-ear)"/>

    {{-- head --}}
    <rect x="9" y="11" width="46" height="43" rx="20" fill="url(#{{ $u }}-body)"/>
    <rect x="9" y="11" width="46" height="43" rx="20" fill="url(#{{ $u }}-shine)"/>

    {{-- face --}}
    <rect x="15" y="20.5" width="34" height="25" rx="12.5" fill="#ffffff"/>
    <g class="limo-eyes">
        <ellipse cx="25.5" cy="31.5" rx="3.4" ry="4.1" fill="#12263a"/>
        <ellipse cx="38.5" cy="31.5" rx="3.4" ry="4.1" fill="#12263a"/>
        <circle cx="26.7" cy="30" r="1.25" fill="#ffffff"/>
        <circle cx="39.7" cy="30" r="1.25" fill="#ffffff"/>
    </g>
    <ellipse cx="20.3" cy="38.3" rx="2.7" ry="1.6" fill="#fda4af" opacity=".85"/>
    <ellipse cx="43.7" cy="38.3" rx="2.7" ry="1.6" fill="#fda4af" opacity=".85"/>
    <path class="limo-smile" d="M28.2 39.2Q32 42.6 35.8 39.2" stroke="#12263a" stroke-width="2" fill="none" stroke-linecap="round"/>

    {{-- little open book --}}
    <g class="limo-book">
        <path d="M32 54.5c-3.6-2.4-7.6-2.9-12-1.6v8.2c4.4-1.3 8.4-.8 12 1.6z" fill="#ffffff" stroke="#0f766e" stroke-width="1.5" stroke-linejoin="round"/>
        <path d="M32 54.5c3.6-2.4 7.6-2.9 12-1.6v8.2c-4.4-1.3-8.4-.8-12 1.6z" fill="#ffffff" stroke="#0f766e" stroke-width="1.5" stroke-linejoin="round"/>
        <path d="M23 56.4c2-.4 3.9-.2 5.8.6M36.2 57c1.9-.8 3.8-1 5.8-.6" stroke="#5eead4" stroke-width="1.2" stroke-linecap="round"/>
    </g>
</svg>
