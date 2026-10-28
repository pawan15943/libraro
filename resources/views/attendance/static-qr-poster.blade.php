<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $libraryName }} - Attendance QR</title>
@php
    $branchName = $branch->display_name ?: $branch->name;
    $initials = collect(preg_split('/\s+/', trim((string) $libraryName)))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') ?: 'L';
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
        .kicker { font-size: 8px; font-weight: bold; letter-spacing: 1.6px; text-transform: uppercase; }

        .head td { vertical-align: middle; }
        .logo { width: 54px; padding-right: 12px; }
        .logo img { width: 50px; height: 50px; border-radius: 14px; }
        .logo div { width: 50px; height: 50px; padding-top: 14px; line-height: 22px; border-radius: 14px; background: #2d4fb3; color: #fff; text-align: center; font-size: 19px; font-weight: bold; }
        .lib { font-size: 22px; font-weight: bold; letter-spacing: -0.4px; line-height: 1.1; }
        .branch { margin-top: 3px; font-size: 10px; color: #6b6f7b; }
        .brand { text-align: right; font-size: 8px; color: #6b6f7b; }
        .brand b { display: block; font-size: 13px; color: #2d4fb3; letter-spacing: -0.2px; }
        .rule { height: 1px; background: #e6e7ec; margin: 12px 0 16px; }

        /* hero */
        .hero { background: #16171b; color: #fff; border-radius: 22px; }
        .hero-in { padding: 26px 30px 30px; text-align: center; }
        .hero .kicker { color: #9fb4ff; }
        .hero h1 { margin-top: 8px; font-size: 32px; line-height: 1.1; letter-spacing: -0.8px; font-weight: bold; color: #fff; }
        .hero p.lead { margin: 10px auto 0; width: 80%; font-size: 11.5px; line-height: 1.45; color: #c9ccd6; }
        .qr-card { margin: 20px auto 0; width: 330px; background: #fff; border-radius: 22px; padding: 16px 16px 12px; text-align: center; }
        .qr-card img { width: 298px; height: 298px; }
        .qr-card div { margin-top: 4px; font-size: 9px; font-weight: bold; color: #2d4fb3; letter-spacing: 1.4px; text-transform: uppercase; }
        .inout { margin-top: 16px; }
        .pill { display: inline-block; margin: 0 4px; padding: 5px 14px; border-radius: 12px; font-size: 10px; font-weight: bold; }

        /* steps */
        .sec { margin: 18px 0 8px; }
        .sec h2 { font-size: 13px; font-weight: bold; letter-spacing: -0.2px; }
        .steps td.st { width: 33.33%; padding: 0 4px; }
        .steps td.st.first { padding-left: 0; }
        .steps td.st.last { padding-right: 0; }
        .step { background: #f4f5f8; border-radius: 14px; padding: 12px 13px; height: 74px; }
        .step .n { font-size: 18px; font-weight: bold; color: #2d4fb3; }
        .step b { display: block; margin-top: 3px; font-size: 10.5px; }
        .step span { display: block; margin-top: 2px; font-size: 8.5px; color: #6b6f7b; line-height: 1.35; }

        /* app */
        .app { margin-top: 14px; background: #eef1fb; border-radius: 18px; }
        .app-qr { width: 120px; padding: 12px 0 12px 12px; vertical-align: middle; }
        .app-qr div { background: #fff; border-radius: 12px; padding: 6px; text-align: center; }
        .app-qr img { width: 96px; height: 96px; }
        .app-t { padding: 12px 18px; vertical-align: middle; }
        .app-t .kicker { color: #2d4fb3; }
        .app-t h3 { margin-top: 5px; font-size: 15px; font-weight: bold; letter-spacing: -0.3px; }
        .app-t p { margin-top: 4px; font-size: 9.5px; color: #454956; line-height: 1.45; }
        .url { margin-top: 6px; font-size: 7.5px; color: #6b6f7b; }

        .foot { margin-top: 14px; padding-top: 9px; border-top: 1px solid #e6e7ec; }
        .foot td { font-size: 8px; color: #6b6f7b; vertical-align: middle; }
        .foot b { color: #16171b; }
    </style>
</head>
<body>

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
                <div class="lib">{{ $libraryName }}</div>
                <div class="branch">
                    @if(strcasecmp($branchName, $libraryName) !== 0){{ $branchName }} &nbsp;&bull;&nbsp; @endif{{ $branch->library_address ?: 'Address not updated' }}
                </div>
            </td>
            <td class="brand" style="width: 120px;">
                <b>Libraro</b>
                Smart library
            </td>
        </tr>
    </table>
    <div class="rule"></div>

    <div class="hero">
        <div class="hero-in">
            <div class="kicker">Daily attendance</div>
            <h1>Scan to mark your Attendance</h1>
            <p class="lead">Scan this QR from the Attendance App every time you come in and when you leave. Your IN &amp; OUT time is recorded instantly.</p>
            <div class="qr-card">
                <img src="data:image/png;base64,{{ $qrCode }}" alt="Attendance QR">
                <div>Attendance &bull; IN / OUT</div>
            </div>
            <div class="inout">
                <span class="pill" style="background: #1f9d63;">First scan &rarr; IN</span>
                <span class="pill" style="background: #e2672c;">Last scan &rarr; OUT</span>
            </div>
        </div>
    </div>

    <table class="sec"><tr><td><h2>How to mark attendance</h2></td></tr></table>
    <table class="steps">
        <tr>
            <td class="st first"><div class="step"><div class="n">1</div><b>Open Attendance App</b><span>Log in once with your UID &amp; mobile number.</span></div></td>
            <td class="st"><div class="step"><div class="n">2</div><b>Tap &ldquo;Scan QR&rdquo;</b><span>Point your phone at the QR above.</span></div></td>
            <td class="st last"><div class="step"><div class="n">3</div><b>Done</b><span>Entry or exit is saved with the time.</span></div></td>
        </tr>
    </table>

    <table class="app">
        <tr>
            <td class="app-qr">
                <div><img src="data:image/png;base64,{{ $appQr }}" alt="Attendance app QR"></div>
            </td>
            <td class="app-t">
                <div class="kicker">New member?</div>
                <h3>Get the Attendance App first</h3>
                <p>Scan this small QR with your phone camera to open the Attendance App, then tap &ldquo;Add to Home screen&rdquo;.</p>
                <div class="url">{{ $appUrl }}</div>
            </td>
        </tr>
    </table>

    <table class="foot">
        <tr>
            <td>Help desk: <b>+91 {{ $branch->mobile ?? '—' }}</b>@if(!empty($branch->email)) &nbsp;&bull;&nbsp; {{ $branch->email }}@endif</td>
            <td style="text-align: right;">Please don&rsquo;t share photos of this QR &nbsp;&bull;&nbsp; Powered by <b>Libraro</b></td>
        </tr>
    </table>

</body>
</html>
