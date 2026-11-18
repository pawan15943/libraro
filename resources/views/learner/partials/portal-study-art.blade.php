{{-- Animated study scene (open book with a turning page, books, desk lamp, floating cap and sparkles).
     Drawn for a dark navy background. Pure SVG + CSS (lb-learner-home.css → .lh-art). --}}
<svg class="lh-art {{ $class ?? '' }}" viewBox="0 0 320 240" fill="none" aria-hidden="true" focusable="false">
    <defs>
        <radialGradient id="lhaGlow" cx="50%" cy="55%" r="50%">
            <stop offset="0" stop-color="#34939f" stop-opacity=".55" />
            <stop offset="1" stop-color="#34939f" stop-opacity="0" />
        </radialGradient>
        <linearGradient id="lhaCone" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#ffd59e" stop-opacity=".55" />
            <stop offset="1" stop-color="#ffd59e" stop-opacity="0" />
        </linearGradient>
        <linearGradient id="lhaPage" x1="0" y1="0" x2="1" y2="0">
            <stop offset="0" stop-color="#ffffff" />
            <stop offset="1" stop-color="#e3e8fb" />
        </linearGradient>
    </defs>

    {{-- soft glow + orbit --}}
    <circle cx="160" cy="135" r="118" fill="url(#lhaGlow)" class="lha-glow" />
    <ellipse cx="160" cy="135" rx="128" ry="44" stroke="#8ea2ff" stroke-opacity=".22" stroke-dasharray="3 7" class="lha-orbit" />
    <circle r="4" fill="#f8a052" class="lha-planet"><animateMotion dur="9s" repeatCount="indefinite" path="M288 135a128 44 0 1 1 -256 0a128 44 0 1 1 256 0" /></circle>

    {{-- desk --}}
    <rect x="18" y="200" width="284" height="5" rx="2.5" fill="#8ea2ff" fill-opacity=".25" />

    {{-- book stack --}}
    <g class="lha-stack">
        <rect x="34" y="186" width="74" height="14" rx="3" fill="#34939f" />
        <rect x="34" y="186" width="6" height="14" rx="2" fill="#2a7a84" />
        <rect x="42" y="172" width="66" height="14" rx="3" fill="#f8a052" />
        <rect x="42" y="172" width="6" height="14" rx="2" fill="#e0853a" />
        <rect x="38" y="158" width="62" height="14" rx="3" fill="#8ea2ff" />
        <rect x="38" y="158" width="6" height="14" rx="2" fill="#6f84ea" />
        <rect x="54" y="163" width="30" height="3" rx="1.5" fill="#ffffff" fill-opacity=".6" />
    </g>

    {{-- desk lamp + light --}}
    <polygon points="236,122 206,200 296,200 262,122" fill="url(#lhaCone)" class="lha-cone" />
    <g class="lha-lamp">
        <ellipse cx="270" cy="199" rx="20" ry="4" fill="#c7cdea" />
        <path d="M270 197 L282 150 L250 120" stroke="#c7cdea" stroke-width="5" stroke-linecap="round" stroke-linejoin="round" />
        <path d="M232 112 L262 102 L268 124 L238 132 Z" fill="#f8a052" />
        <circle cx="249" cy="124" r="4" fill="#fff4d6" class="lha-bulb" />
    </g>

    {{-- open book --}}
    <g class="lha-book">
        <path d="M160 140 C140 128 112 128 94 134 L94 190 C112 184 140 184 160 194 Z" fill="url(#lhaPage)" />
        <path d="M160 140 C180 128 208 128 226 134 L226 190 C208 184 180 184 160 194 Z" fill="#f4f6ff" />
        <g stroke="#b8c0e6" stroke-width="2" stroke-linecap="round">
            <path d="M106 146 C120 142 136 142 148 147" /><path d="M106 156 C120 152 136 152 148 157" />
            <path d="M106 166 C120 162 132 162 142 166" />
            <path d="M172 147 C184 142 200 142 214 146" /><path d="M172 157 C184 152 200 152 214 156" />
            <path d="M178 167 C188 162 202 162 214 166" />
        </g>
        <rect x="157.5" y="138" width="5" height="58" rx="2.5" fill="#34939f" />
        {{-- the page that keeps turning --}}
        <path class="lha-flip" d="M160 140 C180 128 208 128 226 134 L226 190 C208 184 180 184 160 194 Z" fill="#ffffff" />
    </g>

    {{-- floating graduation cap --}}
    <g class="lha-cap">
        <path d="M160 30 L200 46 L160 62 L120 46 Z" fill="#ffffff" />
        <path d="M138 54 L138 68 C150 76 170 76 182 68 L182 54 L160 62 Z" fill="#c7cdea" />
        <path d="M200 46 L200 66" stroke="#f8a052" stroke-width="2.5" stroke-linecap="round" />
        <circle cx="200" cy="68" r="3.5" fill="#f8a052" />
    </g>

    {{-- floating study symbols --}}
    <text x="62" y="112" class="lha-sym lha-s1">A+</text>
    <text x="246" y="74" class="lha-sym lha-s2">π</text>
    <text x="92" y="64" class="lha-sym lha-s3">∑</text>

    {{-- sparkles --}}
    <g fill="#ffe7b8">
        <path class="lha-star lha-t1" d="M58 40 l3 7 7 3 -7 3 -3 7 -3 -7 -7 -3 7 -3z" />
        <path class="lha-star lha-t2" d="M276 40 l2 5 5 2 -5 2 -2 5 -2 -5 -5 -2 5 -2z" />
        <path class="lha-star lha-t3" d="M222 22 l2 4 4 2 -4 2 -2 4 -2 -4 -4 -2 4 -2z" />
        <path class="lha-star lha-t4" d="M30 132 l2 4 4 2 -4 2 -2 4 -2 -4 -4 -2 4 -2z" />
    </g>
    <circle cx="300" cy="110" r="2" fill="#ffffff" class="lha-star lha-t2" />
    <circle cx="120" cy="20" r="1.8" fill="#ffffff" class="lha-star lha-t3" />
</svg>
