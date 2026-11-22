{{--
    Library card on the public QR pages (book seat, renew, pay link): logo, name, short intro,
    address, timings, contact and a few amenities. Mobile first. Needs $branch (any Branch, even
    one loaded with only id). Optional: $qhCompact = true → no intro / amenities (later steps).
--}}
@php
    $qhB = \App\Models\Branch::withoutGlobalScopes()->with(['library', 'city', 'state', 'hour'])->find($branch->id ?? null);
@endphp
@if($qhB)
@php
    $qhCompact = $qhCompact ?? false;
    $qhName = trim((string) ($qhB->display_name ?: ($qhB->library->library_name ?? $qhB->name)));
    $qhSub = ($qhB->name && strcasecmp(trim($qhB->name), $qhName) !== 0) ? trim($qhB->name) : null;
    $qhAbout = trim(strip_tags((string) $qhB->description));

    $qhAddr = collect([
        trim((string) $qhB->library_address),
        $qhB->city->city_name ?? null,
        trim(($qhB->state->state_name ?? '') . ($qhB->library_zip ? ' ' . $qhB->library_zip : '')),
    ])->map(fn ($p) => trim((string) $p, " ,"))->filter()->unique(fn ($p) => strtolower($p))->implode(', ');

    $qhHours = null;
    $qhHour = $qhB->hour;
    if ($qhHour && (int) $qhHour->hour >= 24) {
        $qhHours = 'Open 24 hours';
    } elseif ($qhHour && $qhHour->open_time && (int) $qhHour->hour > 0) {
        try {
            $qhOpen = \Carbon\Carbon::parse($qhHour->open_time);
            $qhHours = $qhOpen->format('g:i A') . ' – ' . $qhOpen->copy()->addHours((int) $qhHour->hour)->format('g:i A');
        } catch (\Throwable $e) {
            $qhHours = null;
        }
    } elseif ($qhHour && (int) $qhHour->hour > 0) {
        $qhHours = (int) $qhHour->hour . ' hours a day';
    }

    $qhDays = collect(preg_split('/\s*,\s*/', (string) $qhB->working_days))->filter();
    $qhDaysLabel = $qhDays->count() >= 7 ? 'All 7 days' : ($qhDays->count() ? $qhDays->map(fn ($d) => mb_substr($d, 0, 3))->implode(', ') : null);

    $qhPhone = preg_replace('/\D/', '', (string) $qhB->mobile);
    $qhPhone = strlen($qhPhone) > 10 && str_starts_with($qhPhone, '91') ? substr($qhPhone, 2) : $qhPhone;

    $qhMap = null;
    if (\Illuminate\Support\Str::startsWith((string) $qhB->google_map, ['https://maps.app.goo.gl', 'https://goo.gl/maps', 'https://www.google.com/maps', 'https://maps.google.'])
        && ! \Illuminate\Support\Str::startsWith((string) $qhB->google_map, 'https://www.google.com/maps/embed')) {
        $qhMap = $qhB->google_map;
    } elseif ($qhB->latitude && $qhB->longitude) {
        $qhMap = 'https://www.google.com/maps/search/?api=1&query=' . $qhB->latitude . ',' . $qhB->longitude;
    } elseif ($qhAddr) {
        $qhMap = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($qhName . ', ' . $qhAddr);
    }

    $qhFeatures = collect();
    if (! $qhCompact && is_array($qhB->features) && count($qhB->features)) {
        $qhFeatures = \App\Models\Feature::whereIn('id', array_map('intval', $qhB->features))->orderBy('id')->pluck('name');
    }

    $qhLogo = ($qhB->library_logo && is_file(public_path(ltrim($qhB->library_logo, '/')))) ? asset('public/' . ltrim($qhB->library_logo, '/')) : null;
    $qhInitials = collect(preg_split('/\s+/', $qhName))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');
@endphp
<style>
    .qh-card{--qh-acc:#0d9488;--qh-acc2:#0ea5e9;--qh-ink:#0f172a;--qh-mute:#64748b;--qh-line:#e2e8f0;background:#fff;border:1px solid var(--qh-line);border-radius:18px;overflow:hidden;box-shadow:0 1px 2px rgba(15,23,42,.04),0 8px 24px -12px rgba(15,23,42,.12);margin:0 0 16px;text-align:left;font-family:'Geist',system-ui,-apple-system,'Segoe UI',sans-serif}
    .qh-band{height:56px;background:linear-gradient(120deg,#ccfbf1 0%,#e0f2fe 100%)}
    .qh-body{padding:0 16px 16px}
    .qh-top{display:flex;align-items:flex-end;gap:12px;margin-top:-28px}
    .qh-logo{flex:0 0 64px;width:64px;height:64px;border-radius:16px;background:#fff;border:3px solid #fff;box-shadow:0 2px 8px rgba(15,23,42,.12);display:grid;place-items:center;overflow:hidden}
    .qh-logo img{width:100%;height:100%;object-fit:cover}
    .qh-logo span{width:100%;height:100%;display:grid;place-items:center;background:linear-gradient(135deg,var(--qh-acc),var(--qh-acc2));color:#fff;font-weight:700;font-size:22px;letter-spacing:.5px}
    .qh-title{min-width:0;padding-bottom:2px}
    .qh-title h1{font-size:19px;line-height:1.25;font-weight:700;color:var(--qh-ink);margin:0;word-break:break-word}
    .qh-title p{font-size:13px;color:var(--qh-mute);margin:2px 0 0}
    .qh-about{font-size:13.5px;line-height:1.55;color:#334155;margin:12px 0 0;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden}
    .qh-rows{list-style:none;margin:12px 0 0;padding:0;display:grid;gap:8px}
    .qh-rows li{display:flex;gap:10px;align-items:flex-start;font-size:13.5px;line-height:1.45;color:#334155;margin:0}
    .qh-rows svg{flex:0 0 18px;width:18px;height:18px;margin-top:1px;color:var(--qh-acc)}
    .qh-rows a{color:#0f766e;font-weight:600;text-decoration:none}
    .qh-chips{display:flex;flex-wrap:wrap;gap:6px;margin:12px 0 0;padding:0;list-style:none}
    .qh-chips li{font-size:12px;line-height:1;padding:7px 10px;border-radius:999px;background:#f0fdfa;color:#0f766e;border:1px solid #ccfbf1;margin:0}
    .qh-acts{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-top:14px}
    .qh-acts a{display:flex;align-items:center;justify-content:center;gap:6px;height:40px;border-radius:12px;border:1px solid var(--qh-line);background:#fff;color:var(--qh-ink)!important;font-size:13.5px;font-weight:600;text-decoration:none!important}
    .qh-acts a svg{width:16px;height:16px;color:var(--qh-acc)}
    .qh-acts a:only-child{grid-column:1/-1}
    /* the public site layout styles bare p / span / li — keep the card's own sizes there */
    .qh-card p,.qh-card span,.qh-card li,.qh-card a{letter-spacing:normal;text-transform:none}
    .qh-card .qh-about{font-size:13.5px;font-weight:400;line-height:1.55;color:#334155}
    .qh-card .qh-rows li>span{font-size:13.5px;font-weight:400;line-height:1.45;color:#334155}
    .qh-card .qh-title p{font-size:13px;font-weight:500;line-height:1.3;color:var(--qh-mute)}
    .qh-card .qh-chips li{font-size:12px;font-weight:500}
    .qh-card .qh-acts a{font-size:13.5px;font-weight:600}
    @media (min-width:576px){.qh-band{height:68px}.qh-body{padding:0 20px 20px}.qh-title h1{font-size:21px}}
</style>
<div class="qh-card">
    <div class="qh-band" aria-hidden="true"></div>
    <div class="qh-body">
        <div class="qh-top">
            <div class="qh-logo">@if($qhLogo)<img src="{{ $qhLogo }}" alt="{{ $qhName }} logo">@else<span>{{ $qhInitials ?: 'L' }}</span>@endif</div>
            <div class="qh-title">
                <h1>{{ $qhName }}</h1>
                @if($qhSub)<p>{{ $qhSub }}</p>@endif
            </div>
        </div>

        @if(! $qhCompact && $qhAbout !== '')
        <p class="qh-about">{{ $qhAbout }}</p>
        @endif

        <ul class="qh-rows">
            @if($qhAddr)
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg><span>{{ $qhAddr }}</span></li>
            @endif
            @if($qhHours || $qhDaysLabel)
            <li><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg><span>{{ collect([$qhHours, $qhDaysLabel])->filter()->implode(' · ') }}</span></li>
            @endif
        </ul>

        @if($qhFeatures->count())
        <ul class="qh-chips" aria-label="Facilities">
            @foreach($qhFeatures->take(5) as $qhF)<li>{{ $qhF }}</li>@endforeach
            @if($qhFeatures->count() > 5)<li>+{{ $qhFeatures->count() - 5 }} more</li>@endif
        </ul>
        @endif

        @if(strlen($qhPhone) >= 8 || $qhMap)
        <div class="qh-acts">
            @if(strlen($qhPhone) >= 8)
            <a href="tel:+91{{ $qhPhone }}" class="noLoader"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.34 1.85.57 2.81.7A2 2 0 0 1 22 16.92z"/></svg>Call library</a>
            @endif
            @if($qhMap)
            <a href="{{ $qhMap }}" target="_blank" rel="noopener" class="noLoader"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="3 11 22 2 13 21 11 13 3 11"/></svg>Directions</a>
            @endif
        </div>
        @endif
    </div>
</div>
@endif
