@php
    $inr = fn ($n) => '₹' . rtrim(rtrim(number_format((float) $n, 2, '.', ','), '0'), '.');
    $isRenew = ($purpose ?? 'renew') === 'renew';
    $state = $expired ? 'expired' : ($state ?? 'nothing');
    $pageTitle = $isRenew ? 'Renew your seat' : 'Pay pending fee';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#0d9488">
    <title>{{ $pageTitle }} · {{ $branch->display_name ?? 'Libraro' }}</title>
    <link rel="icon" href="{{ asset('public/img/favicon.ico') }}" type="image/x-icon">
    <link rel="preload" href="{{ asset('public/fonts/geist/Geist-Variable.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('public/css/lb-font.css') }}?v={{ @filemtime(public_path('css/lb-font.css')) }}">
    <style>
        *,*::before,*::after{box-sizing:border-box}
        :root{--acc:#0d9488;--acc-d:#0f766e;--acc2:#0ea5e9;--ink:#0f172a;--mute:#64748b;--line:#e2e8f0;--soft:#f8fafc;--ok:#16a34a;--warn:#d97706;--bad:#dc2626}
        html,body{margin:0}
        body{font-family:'Geist',system-ui,-apple-system,'Segoe UI',sans-serif;background:#f1f5f9;color:var(--ink);-webkit-font-smoothing:antialiased;line-height:1.45}
        .pl-wrap{max-width:480px;margin:0 auto;padding:16px 16px calc(24px + env(safe-area-inset-bottom))}
        .pl-card{background:#fff;border:1px solid var(--line);border-radius:18px;padding:16px;margin:0 0 16px;box-shadow:0 1px 2px rgba(15,23,42,.04)}
        .pl-h{display:flex;align-items:center;gap:10px;margin:0 0 12px}
        .pl-h b{font-size:16px;font-weight:700}
        .pl-h .pl-step{flex:0 0 26px;width:26px;height:26px;border-radius:50%;background:#ccfbf1;color:var(--acc-d);font-size:13px;font-weight:700;display:grid;place-items:center}
        .pl-tag{display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:600;padding:5px 10px;border-radius:999px;background:#f0fdfa;color:var(--acc-d);border:1px solid #ccfbf1;margin:0 0 10px}
        .pl-tag.is-due{background:#fff7ed;color:#c2410c;border-color:#fed7aa}
        .pl-who{display:grid;grid-template-columns:1fr 1fr;gap:10px 12px;margin:0}
        .pl-who div{min-width:0}
        .pl-who dt{font-size:12px;color:var(--mute);margin:0}
        .pl-who dd{font-size:14px;font-weight:600;margin:2px 0 0;word-break:break-word}
        .pl-lines{margin:0;padding:0;list-style:none}
        .pl-lines li{display:flex;justify-content:space-between;gap:12px;font-size:14px;padding:8px 0;border-bottom:1px dashed var(--line);margin:0}
        .pl-lines li:last-child{border-bottom:0}
        .pl-lines li span{color:#475569}
        .pl-lines li s{color:#94a3b8;font-weight:400;margin-right:6px}
        .pl-lines li small{display:block;color:var(--mute);font-size:12px}
        .pl-total{display:flex;justify-content:space-between;align-items:center;margin-top:8px;padding:12px 14px;border-radius:14px;background:linear-gradient(120deg,#f0fdfa,#f0f9ff);border:1px solid #ccfbf1}
        .pl-total span{font-size:14px;font-weight:600;color:var(--acc-d)}
        .pl-total b{font-size:24px;font-weight:800;letter-spacing:-.3px}
        .pl-qr{display:grid;place-items:center;padding:14px;border:1px solid var(--line);border-radius:16px;background:#fff;width:max-content;max-width:100%;margin:0 auto}
        .pl-qr svg{display:block;width:200px;height:200px}
        .pl-upi{display:flex;align-items:center;justify-content:space-between;gap:8px;margin:12px 0 0;padding:10px 12px;border-radius:12px;background:var(--soft);border:1px solid var(--line);font-size:13px}
        .pl-upi code{font-family:inherit;font-weight:600;font-size:14px;word-break:break-all}
        .pl-upi button{flex:0 0 auto;border:1px solid var(--line);background:#fff;border-radius:10px;height:32px;padding:0 12px;font:inherit;font-size:12.5px;font-weight:600;color:var(--acc-d);cursor:pointer}
        .pl-btn{display:flex;align-items:center;justify-content:center;gap:8px;width:100%;height:50px;border:0;border-radius:14px;background:linear-gradient(120deg,var(--acc),var(--acc2));color:#fff!important;font:inherit;font-size:16px;font-weight:700;text-decoration:none;cursor:pointer;margin-top:12px}
        .pl-btn[disabled]{opacity:.6;cursor:not-allowed}
        .pl-btn.is-ghost{background:#fff;color:var(--ink)!important;border:1px solid var(--line)}
        .pl-note{font-size:12.5px;color:var(--mute);text-align:center;margin:10px 0 0}
        .pl-drop{display:block;border:1.5px dashed #99f6e4;border-radius:16px;background:#f0fdfa;padding:18px 14px;text-align:center;cursor:pointer}
        .pl-drop b{display:block;font-size:14.5px;color:var(--acc-d)}
        .pl-drop small{display:block;font-size:12.5px;color:var(--mute);margin-top:4px}
        .pl-drop input{position:absolute;width:1px;height:1px;opacity:0;pointer-events:none}
        .pl-prev{display:none;margin:12px auto 0;max-width:100%;max-height:260px;border-radius:12px;border:1px solid var(--line)}
        .pl-alert{border-radius:14px;padding:12px 14px;font-size:14px;margin:0 0 16px;border:1px solid}
        .pl-alert.is-bad{background:#fef2f2;border-color:#fecaca;color:#991b1b}
        .pl-alert.is-ok{background:#f0fdf4;border-color:#bbf7d0;color:#166534}
        .pl-state{text-align:center;padding:24px 16px}
        .pl-state .pl-ic{width:56px;height:56px;border-radius:50%;display:grid;place-items:center;margin:0 auto 12px}
        .pl-state .pl-ic svg{width:28px;height:28px}
        .pl-state .is-ok{background:#dcfce7;color:var(--ok)}
        .pl-state .is-wait{background:#fef3c7;color:var(--warn)}
        .pl-state .is-info{background:#e0f2fe;color:#0369a1}
        .pl-state h2{font-size:18px;margin:0 0 6px}
        .pl-state p{font-size:14px;color:#475569;margin:0}
        .pl-foot{display:flex;align-items:center;justify-content:center;gap:6px;font-size:12px;color:#94a3b8;margin-top:8px}
        .pl-foot img{height:16px;opacity:.8}
        .pl-err{color:var(--bad);font-size:13px;margin:8px 0 0}
        @media (min-width:576px){.pl-wrap{padding-top:28px}}
    </style>
</head>
<body>
<main class="pl-wrap">
    @if(! empty($branch))
        @include('qrcode.partials.branch-hero', ['branch' => $branch, 'qhCompact' => true])
    @endif

    @if(session('error'))<div class="pl-alert is-bad" role="alert">{{ session('error') }}</div>@endif
    @if($errors->any())<div class="pl-alert is-bad" role="alert">{{ $errors->first() }}</div>@endif

    @if($state === 'expired')
        <section class="pl-card pl-state">
            <div class="pl-ic is-info"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg></div>
            <h2>This link has expired</h2>
            <p>Please ask the library to send you a new payment link, or pay at the desk.</p>
        </section>

    @elseif($state === 'waiting')
        <section class="pl-card pl-state">
            <div class="pl-ic {{ session('success') ? 'is-ok' : 'is-wait' }}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">@if(session('success'))<path d="M20 6 9 17l-5-5"/>@else<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>@endif</svg></div>
            <h2>{{ session('success') ? 'Payment sent!' : 'Waiting for verification' }}</h2>
            <p>The library has your screenshot of <b>{{ $inr($amount) }}</b>{{ $waiting?->created_at ? ' (sent ' . $waiting->created_at->format('d M, g:i A') . ')' : '' }}. Once they check it, your {{ $isRenew ? 'renewal' : 'payment' }} is confirmed.</p>
        </section>

    @elseif($state === 'nothing')
        <section class="pl-card pl-state">
            <div class="pl-ic is-ok"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg></div>
            <h2>{{ $isRenew ? 'Nothing to renew right now' : 'All clear' }}</h2>
            <p>{{ $message }}</p>
        </section>

    @else
        {{-- who + what --}}
        <section class="pl-card">
            <span class="pl-tag {{ $isRenew ? '' : 'is-due' }}">{{ $isRenew ? 'Seat renewal' : 'Pending fee' }}</span>
            <dl class="pl-who">
                <div><dt>Name</dt><dd>{{ $learner->name }}</dd></div>
                <div><dt>Seat</dt><dd>{{ $seat }}</dd></div>
                <div><dt>Plan</dt><dd>{{ $detail->plan->name ?? '—' }}</dd></div>
                <div><dt>Shift</dt><dd>{{ $detail->planType->name ?? '—' }}</dd></div>
                @if($detail?->plan_end_date)
                <div><dt>{{ \Carbon\Carbon::parse($detail->plan_end_date)->isPast() ? 'Plan ended' : 'Plan ends' }}</dt><dd>{{ \Carbon\Carbon::parse($detail->plan_end_date)->format('d M Y') }}</dd></div>
                @endif
                @if($learner->learner_no)<div><dt>Member ID</dt><dd>{{ $learner->learner_no }}</dd></div>@endif
            </dl>
        </section>

        <section class="pl-card">
            <div class="pl-h"><b>Amount to pay</b></div>
            <ul class="pl-lines">
                @if($isRenew)
                <li>
                    <span>Renewal fee<small>{{ $renew_start->format('d M Y') }} – {{ $renew_end->format('d M Y') }}</small></span>
                    <b>@if($plan_price > $plan_amount)<s>{{ $inr($plan_price) }}</s>@endif{{ $inr($plan_amount) }}</b>
                </li>
                @if($pending > 0)
                <li><span>Previous pending</span><b>{{ $inr($pending) }}</b></li>
                @endif
                @else
                <li><span>Pending fee</span><b>{{ $inr($pending) }}</b></li>
                @endif
            </ul>
            <div class="pl-total"><span>Total</span><b>{{ $inr($amount) }}</b></div>
        </section>

        @if($state === 'no_upi')
        <section class="pl-card pl-state">
            <div class="pl-ic is-info"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/></svg></div>
            <h2>Pay at the library desk</h2>
            <p>Online payment is not set up for this library yet.</p>
        </section>
        @else
        <section class="pl-card">
            <div class="pl-h"><span class="pl-step">1</span><b>Pay {{ $inr($amount) }} by UPI</b></div>
            <div class="pl-qr" aria-label="UPI QR code">{!! $qrSvg !!}</div>
            <p class="pl-note">Scan with any UPI app — GPay, PhonePe, Paytm, BHIM</p>
            <a href="{{ $upiLink }}" class="pl-btn" id="plUpiApp">Pay with UPI app</a>
            <div class="pl-upi">
                <span>UPI ID <code id="plUpiId">{{ trim($branch->upi_id) }}</code></span>
                <button type="button" id="plCopy">Copy</button>
            </div>
        </section>

        <form class="pl-card" method="POST" action="{{ $formUrl }}" enctype="multipart/form-data" id="plForm">
            @csrf
            <div class="pl-h"><span class="pl-step">2</span><b>Send the payment screenshot</b></div>
            <label class="pl-drop" for="plShot">
                <input type="file" id="plShot" name="payment_screenshot" accept="image/*" required>
                <b id="plShotLabel">Tap to add screenshot</b>
                <small>Show the amount and the UTR / transaction ID</small>
                <img class="pl-prev" id="plPrev" alt="Screenshot preview">
            </label>
            @error('payment_screenshot')<p class="pl-err">{{ $message }}</p>@enderror
            <button type="submit" class="pl-btn" id="plSend" disabled>Send for verification</button>
            <p class="pl-note">The library checks the amount and confirms your {{ $isRenew ? 'renewal' : 'payment' }}.</p>
        </form>
        @endif
    @endif

    <div class="pl-foot">Secured by <img src="{{ asset('public/img/libraro.webp') }}" alt="Libraro"></div>
</main>
<script>
(function () {
    var copy = document.getElementById('plCopy');
    if (copy) copy.addEventListener('click', function () {
        var id = document.getElementById('plUpiId').textContent.trim();
        var done = function () { copy.textContent = 'Copied'; setTimeout(function () { copy.textContent = 'Copy'; }, 1500); };
        if (navigator.clipboard) { navigator.clipboard.writeText(id).then(done, function () {}); } else {
            var t = document.createElement('textarea'); t.value = id; document.body.appendChild(t); t.select();
            try { document.execCommand('copy'); done(); } catch (e) {} document.body.removeChild(t);
        }
    });
    var shot = document.getElementById('plShot'), send = document.getElementById('plSend');
    if (shot) shot.addEventListener('change', function () {
        var f = shot.files && shot.files[0], prev = document.getElementById('plPrev');
        send.disabled = !f;
        document.getElementById('plShotLabel').textContent = f ? 'Change screenshot' : 'Tap to add screenshot';
        if (f && window.FileReader) { var r = new FileReader(); r.onload = function (e) { prev.src = e.target.result; prev.style.display = 'block'; }; r.readAsDataURL(f); }
        else { prev.style.display = 'none'; }
    });
    var form = document.getElementById('plForm');
    if (form) form.addEventListener('submit', function () { send.disabled = true; send.textContent = 'Sending…'; });
    // the UPI deep link only works on phones; on a computer the QR is the way
    var app = document.getElementById('plUpiApp');
    if (app && !/Android|iPhone|iPad|iPod/i.test(navigator.userAgent)) app.style.display = 'none';
})();
</script>
</body>
</html>
