<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ strtoupper($branch->display_name ?? $branch->name) }} - Seat Booking Poster</title>
@php
    use Carbon\Carbon;

    $title = $library_name ?: ($branch->display_name ?? $branch->name);
    $branchName = $branch->display_name ?: $branch->name;
    $initials = collect(preg_split('/\s+/', trim((string) $title)))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') ?: 'L';
    $seats = $branch->hour->seats ?? $branch->seats ?? null;
    $openHours = $branch->hour->hour ?? null;
    $planNames = isset($plans) ? $plans->pluck('name', 'id') : collect();

    $shifts = $branch->planTypes->map(function ($s) use ($planNames) {
        $st = !empty($s->start_time) ? Carbon::parse($s->start_time) : null;
        $en = !empty($s->end_time) ? Carbon::parse($s->end_time) : null;
        $hrs = null;
        if ($st && $en) {
            $mins = $st->diffInMinutes($en, false);
            if ($mins <= 0) $mins += 1440;
            $hrs = round($mins / 60, 1);
        }
        $startH = $st ? (int) $st->format('G') : 12;
        $kind = $hrs && $hrs >= 23.9 ? 'all' : (($startH >= 19 || $startH < 4) ? 'night' : ($startH < 12 ? 'morning' : 'evening'));
        return (object) [
            'name' => $s->name,
            'time' => $st && $en ? $st->format('g:i A') . ' – ' . $en->format('g:i A') : '—',
            'hrs' => $hrs ? rtrim(rtrim(number_format($hrs, 1), '0'), '.') . ' hrs' : null,
            'price' => optional($s->price)->price,
            'per' => optional($s->price)->plan_id ? ($planNames[$s->price->plan_id] ?? null) : null,
            'kind' => $kind,
        ];
    });
    $kindColor = ['morning' => '#e8a317', 'evening' => '#e2672c', 'night' => '#5b54d6', 'all' => '#1f9d63'];
    $kindLabel = ['morning' => 'Day', 'evening' => 'Evening', 'night' => 'Night', 'all' => '24 hrs'];

    $facts = array_filter([
        $seats ? ['Seats', $seats, ''] : null,
        $openHours ? ['Open', $openHours, 'hrs / day'] : null,
        ['Grace days', (int) ($branch->extend_days ?? 0), 'after expiry'],
        ((float) ($branch->locker_amount ?? 0)) > 0 ? ['Locker', '&#8377;' . number_format($branch->locker_amount), '/ month'] : null,
        ((float) ($branch->token_money ?? 0)) > 0 ? ['Token', '&#8377;' . number_format($branch->token_money), 'refundable'] : null,
    ]);
@endphp
    <style>
        /* dompdf applies "*" to the page box too, so margins live on body, not @page */
        @page { size: a4 portrait; margin: 0; }
        * { box-sizing: border-box; }
        h1, h2, h3, p, div, table, td { margin: 0; padding: 0; }
        body { margin: 12mm 13mm 10mm; font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif; color: #16171b; background: #fff; font-size: 9.5px; line-height: 1.35; }
        table { border-collapse: collapse; width: 100%; }
        td { vertical-align: top; }

        .muted { color: #6b6f7b; }
        .kicker { font-size: 7.5px; font-weight: bold; letter-spacing: 1.4px; text-transform: uppercase; }

        /* header */
        .head td { vertical-align: middle; }
        .logo { width: 54px; padding-right: 12px; }
        .logo img { width: 50px; height: 50px; border-radius: 14px; }
        .logo div { width: 50px; height: 50px; padding-top: 14px; line-height: 22px; border-radius: 14px; background: #2d4fb3; color: #fff; text-align: center; font-size: 19px; font-weight: bold; }
        .lib { font-size: 22px; font-weight: bold; letter-spacing: -0.4px; line-height: 1.1; }
        .branch { margin-top: 3px; font-size: 10px; color: #6b6f7b; }
        .brand { text-align: right; font-size: 8px; color: #6b6f7b; }
        .brand b { display: block; font-size: 13px; color: #2d4fb3; letter-spacing: -0.2px; }
        .rule { height: 1px; background: #e6e7ec; margin: 12px 0 14px; }

        /* hero */
        .hero { background: #16171b; color: #fff; border-radius: 18px; }
        .hero-l { padding: 18px 6px 18px 22px; vertical-align: middle; }
        .hero-r { width: 216px; padding: 16px 16px 16px 0; vertical-align: middle; }
        .hero .kicker { color: #9fb4ff; }
        .hero h1 { margin-top: 6px; font-size: 23px; line-height: 1.12; letter-spacing: -0.6px; font-weight: bold; color: #fff; }
        .hero p.lead { margin-top: 9px; font-size: 10.5px; line-height: 1.45; color: #c9ccd6; }
        .steps { margin-top: 10px; }
        .steps td { padding: 3px 0; vertical-align: middle; font-size: 9.5px; color: #e9eaf0; }
        .num { width: 20px; }
        .num { font-size: 10px; font-weight: bold; color: #9fb4ff; }
        .qr-card { background: #fff; border-radius: 14px; padding: 10px 10px 8px; text-align: center; }
        .qr-card img { width: 176px; height: 176px; }
        .qr-card div { margin-top: 2px; font-size: 8px; font-weight: bold; color: #2d4fb3; letter-spacing: 1px; text-transform: uppercase; }

        /* facts */
        .facts { margin-top: 12px; }
        .facts > tr > td, .facts td.fc { padding: 0 4px; }
        .facts td.fc.first { padding-left: 0; }
        .facts td.fc.last { padding-right: 0; }
        .fact { background: #f4f5f8; border-radius: 12px; padding: 9px 11px; }
        .fact .kicker { color: #8a8e9a; font-size: 6.8px; }
        .fact b { display: block; margin-top: 3px; font-size: 15px; letter-spacing: -0.3px; }
        .fact small { font-size: 7.5px; color: #6b6f7b; font-weight: normal; }

        /* section heading */
        .sec { margin: 14px 0 7px; }
        .sec td { vertical-align: bottom; }
        .sec h2 { font-size: 13px; font-weight: bold; letter-spacing: -0.2px; }
        .sec .muted { text-align: right; font-size: 8px; }

        /* shifts */
        .shifts td.sc { width: 50%; padding: 0 4px 7px 0; }
        .shifts td.sc.r { padding: 0 0 7px 4px; }
        .dense .s-main { padding-top: 5px; padding-bottom: 5px; }
        .dense .s-price { padding-top: 5px; padding-bottom: 5px; }
        .dense .bar div { height: 24px; }
        .dense td.sc { padding-bottom: 5px; }
        .dense .s-name { font-size: 10px; }
        .shift { border: 1px solid #e6e7ec; border-radius: 12px; }
        .shift td { vertical-align: middle; }
        .bar { width: 5px; padding: 0; }
        .bar div { width: 4px; height: 30px; border-radius: 3px; }
        .s-main { padding: 8px 6px 8px 10px; }
        .s-name { font-size: 11px; font-weight: bold; }
        .s-time { margin-top: 1px; font-size: 8.5px; color: #6b6f7b; }
        .s-price { width: 100px; padding: 8px 12px 8px 4px; text-align: right; }
        .s-price b { font-size: 13px; letter-spacing: -0.2px; }
        .s-price small { display: block; font-size: 7.5px; color: #6b6f7b; }

        .plans { margin-top: 2px; }
        .chip { display: inline-block; margin: 0 4px 4px 0; padding: 3px 9px; border-radius: 10px; background: #eef1fb; color: #2d4fb3; font-size: 8.5px; font-weight: bold; }

        /* attendance app */
        .app { margin-top: 14px; background: #eef1fb; border-radius: 18px; }
        .app-qr { width: 120px; padding: 12px 0 12px 12px; vertical-align: middle; }
        .app-qr div { background: #fff; border-radius: 12px; padding: 6px; text-align: center; }
        .app-qr img { width: 96px; height: 96px; }
        .app-t { padding: 12px 18px; vertical-align: middle; }
        .app-t .kicker { color: #2d4fb3; }
        .app-t h3 { margin-top: 5px; font-size: 16px; font-weight: bold; letter-spacing: -0.3px; }
        .app-t p { margin-top: 4px; font-size: 9.5px; color: #454956; line-height: 1.45; }
        .app-steps { margin-top: 8px; }
        .app-steps td { width: 33.3%; padding-right: 8px; font-size: 8.5px; color: #454956; }
        .app-steps b { color: #16171b; }
        .url { margin-top: 8px; font-size: 7.5px; color: #6b6f7b; }

        /* footer */
        .foot { margin-top: 12px; padding-top: 9px; border-top: 1px solid #e6e7ec; }
        .foot td { font-size: 8px; color: #6b6f7b; vertical-align: middle; }
        .foot b { color: #16171b; }
    </style>
</head>
<body>

    {{-- header --}}
    <table class="head">
        <tr>
            <td class="logo">
                @if(!empty($logo))
                    <img src="data:image/png;base64,{{ $logo }}" alt="Logo">
                @else
                    <div>{{ $initials }}</div>
                @endif
            </td>
            <td>
                <div class="lib">{{ $title }}</div>
                <div class="branch">
                    @if(strcasecmp($branchName, $title) !== 0){{ $branchName }} &nbsp;&bull;&nbsp; @endif{{ $branch->library_address ?: 'Address not updated' }}
                </div>
            </td>
            <td class="brand" style="width: 120px;">
                <b>Libraro</b>
                Smart library
            </td>
        </tr>
    </table>
    <div class="rule"></div>

    {{-- hero: booking QR --}}
    <table class="hero">
        <tr>
            <td class="hero-l">
                <div class="kicker">Seat booking</div>
                <h1>Scan to Book &amp; Re-new<br>your seat</h1>
                <p class="lead">With scanning of this QR you can Book &amp; Re-new your seat in a minute &mdash; pick a shift, choose a seat and pay online.</p>
                <table class="steps">
                    <tr><td class="num">1</td><td>Open your phone camera or Google Lens and scan</td></tr>
                    <tr><td class="num">2</td><td>Choose your plan, shift and an available seat</td></tr>
                    <tr><td class="num">3</td><td>Pay by UPI or at the desk &mdash; your seat is confirmed</td></tr>
                </table>
            </td>
            <td class="hero-r">
                <div class="qr-card">
                    <img src="data:image/png;base64,{{ $qrCode }}" alt="Seat booking QR">
                    <div>Book &bull; Re-new</div>
                </div>
            </td>
        </tr>
    </table>

    {{-- quick facts --}}
    @if(count($facts))
        <table class="facts">
            <tr>
                @foreach(array_values($facts) as $fi => [$label, $value, $unit])
                    <td class="fc {{ $fi === 0 ? 'first' : '' }} {{ $fi === count($facts) - 1 ? 'last' : '' }}" style="width: {{ round(100 / count($facts), 2) }}%;">
                        <div class="fact">
                            <div class="kicker">{{ $label }}</div>
                            <b>{!! $value !!} @if($unit)<small>{{ $unit }}</small>@endif</b>
                        </div>
                    </td>
                @endforeach
            </tr>
        </table>
    @endif

    {{-- shifts --}}
    <table class="sec">
        <tr>
            <td><h2>Shifts &amp; timings</h2></td>
            <td class="muted">{{ $shifts->count() }} {{ $shifts->count() == 1 ? 'shift' : 'shifts' }} available</td>
        </tr>
    </table>

    @if($shifts->isEmpty())
        <p class="muted">Shifts will be updated soon. Please ask at the desk.</p>
    @else
        <table class="shifts {{ $shifts->count() > 6 ? 'dense' : '' }}">
            @foreach($shifts->chunk(2) as $pair)
                <tr>
                    @foreach($pair->values() as $i => $s)
                        <td class="sc {{ $i === 1 ? 'r' : '' }}">
                            <table class="shift">
                                <tr>
                                    <td class="bar" style="padding-left: 8px;"><div style="background: {{ $kindColor[$s->kind] }};"></div></td>
                                    <td class="s-main">
                                        <div class="s-name">{{ $s->name }}</div>
                                        <div class="s-time"><b style="color: {{ $kindColor[$s->kind] }};">{{ $kindLabel[$s->kind] }}</b> &nbsp;&bull;&nbsp; {{ $s->time }}@if($s->hrs) &nbsp;&bull;&nbsp; {{ $s->hrs }}@endif</div>
                                    </td>
                                    <td class="s-price">
                                        @if($s->price)
                                            <b>&#8377;{{ number_format($s->price) }}</b>
                                            <small>{{ $s->per ?: 'per plan' }}</small>
                                        @else
                                            <small>Ask at desk</small>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>
                    @endforeach
                    @if($pair->count() == 1)<td class="sc r"></td>@endif
                </tr>
            @endforeach
        </table>
    @endif

    @if(isset($plans) && $plans->isNotEmpty())
        <table class="sec" style="margin-top: 10px;">
            <tr><td><h2>Plans</h2></td><td class="muted">Duration you can book for</td></tr>
        </table>
        <div class="plans">
            @foreach($plans as $plan)
                <span class="chip">{{ $plan->name }}</span>
            @endforeach
        </div>
    @endif

    {{-- attendance app --}}
    <table class="app">
        <tr>
            <td class="app-qr">
                <div><img src="data:image/png;base64,{{ $attendanceQr }}" alt="Attendance app QR"></div>
            </td>
            <td class="app-t">
                <div class="kicker">For members</div>
                <h3>Download the Attendance App</h3>
                <p>Scan this QR to get the Attendance App on your phone. Mark your entry and exit every day and see your plan &amp; due date anytime.</p>
                <table class="app-steps">
                    <tr>
                        <td><b>1.</b> Scan the QR</td>
                        <td><b>2.</b> Tap &ldquo;Add to Home screen&rdquo;</td>
                        <td><b>3.</b> Log in &amp; mark attendance</td>
                    </tr>
                </table>
                <div class="url">{{ $attendanceUrl }}</div>
            </td>
        </tr>
    </table>

    {{-- footer --}}
    <table class="foot">
        <tr>
            <td>
                Help desk: <b>+91 {{ $branch->mobile ?? '—' }}</b>@if(!empty($branch->email)) &nbsp;&bull;&nbsp; {{ $branch->email }}@endif
            </td>
            <td style="text-align: right;">Please keep silence &amp; keep the hall clean &nbsp;&bull;&nbsp; Powered by <b>Libraro</b></td>
        </tr>
    </table>

</body>
</html>
