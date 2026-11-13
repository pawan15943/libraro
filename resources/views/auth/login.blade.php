{{-- Platform admin login. Lives on a secret URL (config/admin.php → ADMIN_LOGIN_PATH). Standalone page: no layout, no third-party assets. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="referrer" content="same-origin">
    <title>Sign in · Libraro</title>
    <link rel="icon" href="{{ asset('public/img/favicon.ico') }}" type="image/x-icon">
    <style>
        :root {
            --bg: #f2f4f8; --surface: #fff; --label: #0f172a; --label-2: #475569; --label-3: #94a3b8;
            --line: #e2e8f0; --field: #f8fafc; --tint: #1e3a8a; --tint-hover: #1e40af; --ring: rgba(30, 58, 138, .18);
            --bad: #b91c1c; --bad-bg: #fef2f2; --ok: #166534; --ok-bg: #f0fdf4;
            --shadow: 0 1px 2px rgba(15, 23, 42, .04), 0 12px 32px rgba(15, 23, 42, .08);
            color-scheme: light;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #0b1120; --surface: #111a2e; --label: #e2e8f0; --label-2: #a5b4cb; --label-3: #64748b;
                --line: #1f2b44; --field: #0d1527; --tint: #4f74d9; --tint-hover: #6385e0; --ring: rgba(99, 133, 224, .28);
                --bad: #fca5a5; --bad-bg: rgba(220, 38, 38, .14); --ok: #86efac; --ok-bg: rgba(22, 163, 74, .14);
                --shadow: 0 0 0 1px #1f2b44, 0 16px 40px rgba(0, 0, 0, .4);
                color-scheme: dark;
            }
            .logo-light { display: none; }
            .logo-dark { display: block !important; }
        }
        * { box-sizing: border-box; }
        html, body { height: 100%; }
        body {
            margin: 0; display: grid; place-items: center; min-height: 100%; padding: 24px 16px;
            background: var(--bg); color: var(--label);
            font: 15px/1.5 -apple-system, BlinkMacSystemFont, "SF Pro Text", "Segoe UI", Roboto, Inter, sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        main { width: 100%; max-width: 380px; }
        .logo { display: flex; justify-content: center; margin-bottom: 24px; }
        .logo img { height: 30px; width: auto; }
        .logo-dark { display: none; }
        .card { background: var(--surface); border-radius: 18px; box-shadow: var(--shadow); padding: 28px 24px 24px; }
        h1 { margin: 0; font-size: 21px; font-weight: 700; letter-spacing: -.015em; text-align: center; }
        .sub { margin: 4px 0 22px; text-align: center; font-size: 13.5px; color: var(--label-2); }
        .alert { display: flex; gap: 8px; align-items: flex-start; margin-bottom: 16px; padding: 10px 12px; border-radius: 10px; font-size: 13.5px; }
        .alert.bad { background: var(--bad-bg); color: var(--bad); }
        .alert.ok { background: var(--ok-bg); color: var(--ok); }
        .alert svg { flex: none; margin-top: 2px; }
        label.f { display: block; margin-bottom: 6px; font-size: 13px; font-weight: 600; color: var(--label-2); }
        .field { position: relative; margin-bottom: 16px; }
        input[type=email], input[type=password], input[type=text] {
            width: 100%; height: 46px; padding: 0 14px; border: 1px solid var(--line); border-radius: 11px;
            background: var(--field); color: var(--label); font: inherit; outline: none;
            transition: border-color .15s, box-shadow .15s, background-color .15s;
        }
        input:focus { border-color: var(--tint); box-shadow: 0 0 0 4px var(--ring); background: var(--surface); }
        .is-invalid input { border-color: var(--bad); }
        .pw input { padding-right: 46px; }
        .eye { position: absolute; right: 4px; bottom: 4px; width: 38px; height: 38px; display: grid; place-items: center; border: 0; border-radius: 9px; background: none; color: var(--label-3); cursor: pointer; }
        .eye:hover { color: var(--label-2); }
        .eye .off { display: none; }
        .eye[aria-pressed=true] .on { display: none; }
        .eye[aria-pressed=true] .off { display: block; }
        .err { margin-top: 5px; font-size: 12.5px; color: var(--bad); }
        .remember { display: flex; align-items: center; gap: 8px; margin: 2px 0 20px; font-size: 13.5px; color: var(--label-2); cursor: pointer; user-select: none; }
        .remember input { width: 16px; height: 16px; margin: 0; accent-color: var(--tint); }
        button.submit {
            width: 100%; height: 46px; border: 0; border-radius: 11px; background: var(--tint); color: #fff;
            font: 600 15px/1 inherit; font-family: inherit; cursor: pointer; transition: background-color .15s, transform .1s;
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
        }
        button.submit:hover { background: var(--tint-hover); }
        button.submit:active { transform: scale(.985); }
        button.submit[disabled] { opacity: .75; cursor: progress; }
        .spin { width: 16px; height: 16px; border: 2px solid rgba(255, 255, 255, .4); border-top-color: #fff; border-radius: 50%; animation: s .7s linear infinite; display: none; }
        button.submit[disabled] .spin { display: block; }
        @keyframes s { to { transform: rotate(360deg); } }
        .foot { margin-top: 18px; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 12.5px; color: var(--label-3); }
        @media (max-width: 420px) { .card { padding: 24px 18px 20px; border-radius: 16px; } }
        @media (prefers-reduced-motion: reduce) { * { transition: none !important; animation: none !important; } }
    </style>
</head>
<body>
<main>
    <div class="logo">
        <img src="{{ asset('public/img/libraro.svg') }}" alt="Libraro" class="logo-light" width="124" height="30">
        <img src="{{ asset('public/img/libraro-white.svg') }}" alt="Libraro" class="logo-dark" width="124" height="30">
    </div>

    <div class="card">
        <h1>Admin sign in</h1>
        <p class="sub">Libraro team only</p>

        @if($errors->has('error'))
            <div class="alert bad" role="alert">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                <span>{{ $errors->first('error') }}</span>
            </div>
        @elseif(session('info'))
            <div class="alert ok" role="status">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M20 6 9 17l-5-5"/></svg>
                <span>{{ session('info') }}</span>
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" id="adminLogin" novalidate>
            @csrf
            <input type="hidden" name="user_type" value="superadmin">

            <div class="field {{ $errors->has('email') ? 'is-invalid' : '' }}">
                <label class="f" for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="username" inputmode="email" autocapitalize="off" spellcheck="false" required autofocus>
                @error('email')<div class="err">{{ $message }}</div>@enderror
            </div>

            <div class="field pw {{ $errors->has('password') ? 'is-invalid' : '' }}">
                <label class="f" for="password">Password</label>
                <input id="password" type="password" name="password" autocomplete="current-password" required>
                <button type="button" class="eye" aria-label="Show password" aria-pressed="false" aria-controls="password">
                    <svg class="on" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                    <svg class="off" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.9 4.2A10 10 0 0 1 12 4c6.5 0 10 8 10 8a17 17 0 0 1-2.2 3.2M6.6 6.6C3.8 8.5 2 12 2 12s3.5 7 10 7a9.7 9.7 0 0 0 5.4-1.6M3 3l18 18M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>
                </button>
                @error('password')<div class="err">{{ $message }}</div>@enderror
            </div>

            <label class="remember"><input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }}> Keep me signed in</label>

            <button type="submit" class="submit"><span class="spin" aria-hidden="true"></span><span>Sign in</span></button>
        </form>
    </div>

    <p class="foot">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>
        Secure area · activity is logged
    </p>
</main>

<script>
    (function () {
        var eye = document.querySelector('.eye'), pw = document.getElementById('password');
        eye.addEventListener('click', function () {
            var show = pw.type === 'password';
            pw.type = show ? 'text' : 'password';
            eye.setAttribute('aria-pressed', show);
            eye.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            pw.focus();
        });
        document.getElementById('adminLogin').addEventListener('submit', function (e) {
            if (!this.checkValidity()) { e.preventDefault(); this.reportValidity(); return; }
            this.querySelector('.submit').disabled = true;
        });
    })();
</script>
</body>
</html>
