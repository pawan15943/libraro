@php
    /* Learner portal shell. $lr comes from LearnerPortalService (view composer). */
    $lr = $lr ?? null;
    $route = Route::currentRouteName();
    $libName = $lr['library']['name'] ?? 'Libraro';
    $libInitial = mb_strtoupper(mb_substr($libName, 0, 1)) ?: 'L';
    $pageTitle = trim($__env->yieldContent('title')) ?: 'Libraro';

    // Menu: single links and groups (dropdown categories). Links whose route does not exist yet are skipped.
    $groups = [
        ['Study', 'graduation-cap', [
            ['learner.goal', 'flag', 'My exam goal'],
            ['learner.stopwatch', 'timer', 'Study stopwatch'],
            ['learner.gk', 'zap', 'Daily GK quiz'],
            ['learner.quizzes', 'list-checks', 'Library quizzes'],
        ]],
        ['My library', 'library', [
            ['learner.profile', 'user-round', 'My profile'],
            ['my-attendance', 'calendar-check', 'Attendance'],
            ['my-transactions', 'wallet', 'Payments'],
            ['my-library-id', 'id-card', 'ID card'],
        ]],
        ['Help', 'headset', [
            ['learner.chat', 'message-circle', 'Chat with library'],
            ['learner.request', 'send', 'Requests'],
            ['complaints', 'circle-alert', 'Complaints'],
            ['learner.suggestions', 'lightbulb', 'Suggestions'],
            ['learner.feadback', 'star', 'Feedback'],
            ['support', 'headset', 'Contact library'],
        ]],
        ['Read', 'book-open', [
            ['learner.blog', 'newspaper', 'Blog'],
            ['books-library', 'book-open', 'Books'],
        ]],
    ];
    $groups = array_values(array_filter(array_map(function ($g) {
        $g[2] = array_values(array_filter($g[2], fn ($i) => Route::has($i[0])));
        return $g[2] ? $g : null;
    }, $groups)));
    $subLink = Route::has('learner.subscription');
    $tabs = [
        ['learner.home', 'house', 'Home'],
        ['learner.stopwatch', 'timer', 'Stopwatch'],
        ['learner.gk', 'zap', 'GK quiz'],
        ['my-attendance', 'calendar-check', 'Attendance'],
    ];
    // pages that are not in the menu: the menu page they belong to
    $parentOf = [
        'blog.detail.show' => 'learner.blog',
        'learner.quiz.play' => 'learner.quizzes',
        'learner.quiz.result' => 'learner.quizzes',
        'learner.attendance.day' => 'my-attendance',
    ];
    $activeName = $parentOf[$route] ?? $route;
    $isOn = fn ($name) => $route === $name || $activeName === $name;

    // breadcrumb: Home > group > menu page > (this page)
    $crumbs = [];
    if ($route !== 'learner.home') {
        $crumbs[] = [route('learner.home'), 'Home'];
        if ($route === 'learner.subscription') {
            $crumbs[] = [null, 'Subscription'];
        }
        foreach ($groups as [$gLabel, , $gItems]) {
            foreach ($gItems as [$iName, , $iLabel]) {
                if ($iName === $activeName) {
                    $crumbs[] = [null, $gLabel];
                    $crumbs[] = [$iName !== $route ? route($iName) : null, $iLabel];
                }
            }
        }
        if (isset($parentOf[$route])) {
            $crumbs[] = [null, $pageTitle];
        }
    }
    $tabs = array_values(array_filter($tabs, fn ($t) => Route::has($t[0])));

    // header card: the student's subscription (plan name, state, days left, bar)
    $plan = $lr['plan'] ?? null;
    [$pillText, $pillTone] = \App\Services\LearnerPortalService::stateLabel($plan);
    $pillTag = match ($plan['state'] ?? 'none') { 'active' => 'Active', 'soon' => 'Ending', 'grace' => 'Grace', 'frozen' => 'On hold', 'expired' => 'Ended', default => 'No plan' };
    $pillFill = $plan ? ($plan['state'] === 'expired' ? 0 : max(4, 100 - (int) $plan['progress'])) : 0;
    $pillSub = $plan ? $pillText : 'Ask the library desk';
    $pillName = $plan ? ($plan['name'] ?? 'Plan') : 'Subscription';
    $chatUnread = (int) ($lr['chat_unread'] ?? 0);
    $libPhone = $lr['library']['phone'] ?? null;
    $tabNames = array_column($tabs, 0);
    $moreOn = !in_array($route, $tabNames, true);
@endphp
<!DOCTYPE html>
<html lang="en" translate="no">
<head>
    <meta charset="UTF-8">
    {{-- signed-in portal: never indexed or machine-translated; lp-ref traces a copied page to the account (portal:watermark) --}}
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="google" content="notranslate">
    <meta name="lp-ref" content="{{ portalWatermark() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="theme-color" content="#1d3c86">
    <meta name="format-detection" content="telephone=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle }} · {{ $libName }}</title>
    <script>
        (function () {
            try {
                var t = localStorage.getItem('lp-theme') === 'dark' ? 'dark' : 'light';
                document.documentElement.setAttribute('data-lp-theme', t);
            } catch (e) {}
        })();
    </script>
    <link rel="icon" href="{{ asset('public/img/favicon.ico') }}" type="image/x-icon">
    <link rel="preload" href="{{ asset('public/fonts/geist/Geist-Variable.woff2') }}" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="{{ asset('public/css/lb-font.css') }}?v={{ @filemtime(public_path('css/lb-font.css')) }}">
    {{-- panel tokens (--lp-*); its shell rules are scoped to body.lp and do not apply here --}}
    <link rel="stylesheet" href="{{ asset('public/css/lb-panel.css') }}?v={{ @filemtime(public_path('css/lb-panel.css')) }}">
    <link rel="stylesheet" href="{{ asset('public/css/lb-learner.css') }}?v={{ @filemtime(public_path('css/lb-learner.css')) }}">
    <link rel="stylesheet" href="{{ asset('public/css/lb-chat.css') }}?v={{ @filemtime(public_path('css/lb-chat.css')) }}">
    @stack('lr-styles')
</head>

<body class="lr" data-ref="{{ portalWatermark() }}">
<div class="lr-app">

    {{-- Sidebar (desktop) --}}
    <aside class="lr-side" aria-label="Menu">
        <div class="lr-side-brand">
            <span class="lr-logo">{{ $libInitial }}</span>
            <div class="lr-side-brand-t"><b>{{ $libName }}</b></div>
            <span class="lr-badge">Student</span>
        </div>
        <div class="lr-side-scroll">
            <nav class="lr-nav">
                <a href="{{ route('learner.home') }}" class="{{ $isOn('learner.home') ? 'is-on' : '' }}"><x-lb-icon name="layout-dashboard" /> <span>Dashboard</span></a>
                @if ($subLink)<a href="{{ route('learner.subscription') }}" class="{{ $isOn('learner.subscription') ? 'is-on' : '' }}"><x-lb-icon name="gem" /> <span>Subscription</span></a>@endif
            </nav>
            @foreach ($groups as [$gLabel, $gIcon, $gItems])
                @php $open = collect($gItems)->contains(fn ($i) => $isOn($i[0])); @endphp
                <details class="lr-grp" @if($open) open @endif>
                    <summary><x-lb-icon name="{{ $gIcon }}" /> <span>{{ $gLabel }}</span> <x-lb-icon name="chevron-down" class="lr-grp-chev" /></summary>
                    <nav class="lr-nav">
                        @foreach ($gItems as [$name, $icon, $label])
                            <a href="{{ route($name) }}" class="{{ $isOn($name) ? 'is-on' : '' }}"><x-lb-icon :name="$icon" /> {{ $label }}@if ($name === 'learner.chat')<em data-lc-badge @if (!$chatUnread) hidden @endif>{{ $chatUnread > 9 ? '9+' : $chatUnread }}</em>@endif</a>
                        @endforeach
                    </nav>
                </details>
            @endforeach
        </div>
        <div class="lr-side-foot">
            {{-- Help: this library's contact --}}
            <div class="lr-help">
                <b><x-lb-icon name="headset" /> Need help?</b>
                <p>{{ $libName }}@if (!empty($lr['library']['address'])) · {{ \Illuminate\Support\Str::limit($lr['library']['address'], 60) }}@endif</p>
                @if ($libPhone)<a href="tel:{{ preg_replace('/\s+/', '', $libPhone) }}"><x-lb-icon name="phone" /> {{ $libPhone }}</a>@endif
                @if (Route::has('learner.chat'))<a href="{{ route('learner.chat') }}" class="is-alt"><x-lb-icon name="message-circle" /> Chat with library</a>@endif
            </div>
        </div>
    </aside>

    <div class="lr-main">
        {{-- Top bar --}}
        <header class="lr-top">
            <button type="button" class="lr-ibtn lr-hide-m" data-lr-collapse title="Show or hide menu" aria-label="Show or hide menu"><x-lb-icon name="panel-left-close" /></button>
            <button type="button" class="lr-ibtn lr-show-m" data-lr-sheet-open aria-label="Open menu"><x-lb-icon name="menu" /></button>

            <div class="lr-branch" title="Your library">
                <span class="lr-branch-ic"><x-lb-icon name="building" /></span>
                <span class="lr-branch-t"><small>Library</small><b>{{ $libName }}</b></span>
            </div>

            <div class="lr-top-spacer"></div>

            {{-- Subscription card --}}
            <a href="{{ $subLink ? route('learner.subscription') : '#' }}" class="lr-plan is-{{ $pillTone }}" title="Your subscription">
                <span class="lr-plan-ic"><x-lb-icon name="gem" /></span>
                <span class="lr-plan-t">
                    <b>{{ $pillName }}<span class="lr-tag lr-tag--{{ $pillTone }}">{{ $pillTag }}</span></b>
                    <small>{{ $pillSub }}</small>
                </span>
                <span class="lr-plan-bar"><span style="width:{{ $pillFill }}%"></span></span>
            </a>
            <a href="{{ $subLink ? route('learner.subscription') : '#' }}" class="lr-plan-mini is-{{ $pillTone }}" title="{{ $pillName }}"><x-lb-icon name="gem" />{{ $plan ? ($plan['state'] === 'expired' ? 'Ended' : ($plan['days_left'] >= 0 ? $plan['days_left'] . 'd' : $pillTag)) : '—' }}</a>

            <button type="button" class="lr-ibtn" data-lr-theme title="Switch light / dark" aria-label="Switch light or dark theme">
                <x-lb-icon name="moon" class="lr-theme-moon" /><x-lb-icon name="sun" class="lr-theme-sun" />
            </button>
            @if (Route::has('learner.chat'))
                <a href="{{ route('learner.chat') }}" class="lr-ibtn" title="Chat with library" aria-label="Chat with library{{ $chatUnread ? ' (' . $chatUnread . ' unread)' : '' }}">
                    <x-lb-icon name="message-circle" /><em data-lc-badge class="lr-ibtn-badge" @if (!$chatUnread) hidden @endif>{{ $chatUnread > 9 ? '9+' : $chatUnread }}</em>
                </a>
            @endif

            <div class="lr-menu">
                <button type="button" class="lr-profile" data-lr-menu aria-haspopup="true" aria-expanded="false" aria-label="Account menu">
                    <span class="lr-avatar">{{ $lr['initials'] ?? 'L' }}</span>
                    <span class="lr-profile-t"><b>{{ $lr['first_name'] ?? 'Student' }}</b><small>Student</small></span>
                    <x-lb-icon name="chevron-down" class="lr-profile-chev" />
                </button>
                <div class="lr-menu-pop" data-lr-menu-pop hidden>
                    <div class="lr-menu-head">
                        <b>{{ $lr['name'] ?? '' }}</b>
                        <small>ID {{ $lr['learner_no'] ?? '' }}</small>
                    </div>
                    <a href="{{ route('learner.profile') }}"><x-lb-icon name="user-round" /> My profile</a>
                    @if ($subLink)<a href="{{ route('learner.subscription') }}"><x-lb-icon name="gem" /> Subscription</a>@endif
                    <a href="{{ route('my-library-id') }}"><x-lb-icon name="id-card" /> My ID card</a>
                    <button type="submit" form="lrLogout" class="is-bad"><x-lb-icon name="log-out" /> Log out</button>
                </div>
            </div>
        </header>

        <main class="lr-content" id="main">
            @if ($crumbs)
                <nav class="lr-crumbs" aria-label="Breadcrumb">
                    <ol>
                        @foreach ($crumbs as $i => [$url, $label])
                            <li @if ($loop->last) aria-current="page" @endif>
                                @if ($url && !$loop->last)<a href="{{ $url }}">{{ $label }}</a>@else<span>{{ $label }}</span>@endif
                            </li>
                        @endforeach
                    </ol>
                </nav>
            @endif
            @if (session('success'))
                <div class="lr-alert ok lr-flash" role="status">
                    <span class="lr-alert-ic"><x-lb-icon name="circle-check" /></span>
                    <div><b>{{ session('success') }}</b></div>
                    <button type="button" class="lr-alert-x" data-lr-close aria-label="Close"><x-lb-icon name="x" /></button>
                </div>
            @endif
            @if (session('error'))
                <div class="lr-alert bad lr-flash" role="alert">
                    <span class="lr-alert-ic"><x-lb-icon name="circle-alert" /></span>
                    <div><b>{{ session('error') }}</b></div>
                    <button type="button" class="lr-alert-x" data-lr-close aria-label="Close"><x-lb-icon name="x" /></button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    {{-- Bottom tabs (phones) --}}
    <nav class="lr-tabs" aria-label="Main">
        @foreach ($tabs as [$name, $icon, $label])
            <a href="{{ route($name) }}" class="lr-tab {{ $isOn($name) ? 'is-on' : '' }}" @if($isOn($name)) aria-current="page" @endif>
                <x-lb-icon :name="$icon" /><span>{{ $label }}</span>
            </a>
        @endforeach
        <button type="button" class="lr-tab {{ $moreOn ? 'is-on' : '' }}" data-lr-sheet-open aria-haspopup="dialog">
            <x-lb-icon name="layout-grid" /><span>More</span>
        </button>
    </nav>

    {{-- "More" sheet (phones) --}}
    <div class="lr-sheet-bg" data-lr-sheet-close></div>
    <div class="lr-sheet" role="dialog" aria-modal="true" aria-label="More">
        <div class="lr-sheet-grip"></div>
        <div class="lr-sheet-who">
            <span class="lr-avatar is-lg">{{ $lr['initials'] ?? 'L' }}</span>
            <div><b>{{ $lr['name'] ?? '' }}</b><small>ID {{ $lr['learner_no'] ?? '' }} · {{ $libName }}</small></div>
        </div>
        <nav class="lr-nav">
            <a href="{{ route('learner.home') }}" class="{{ $isOn('learner.home') ? 'is-on' : '' }}"><x-lb-icon name="layout-dashboard" /> Dashboard</a>
            @if ($subLink)<a href="{{ route('learner.subscription') }}" class="{{ $isOn('learner.subscription') ? 'is-on' : '' }}"><x-lb-icon name="gem" /> Subscription</a>@endif
        </nav>
        @foreach ($groups as [$gLabel, $gIcon, $gItems])
            @php $open = collect($gItems)->contains(fn ($i) => $isOn($i[0])); @endphp
            <details class="lr-grp" @if($open) open @endif>
                <summary><x-lb-icon name="{{ $gIcon }}" /> {{ $gLabel }} <x-lb-icon name="chevron-down" class="lr-grp-chev" /></summary>
                <nav class="lr-nav">
                    @foreach ($gItems as [$name, $icon, $label])
                        <a href="{{ route($name) }}" class="{{ $isOn($name) ? 'is-on' : '' }}"><x-lb-icon :name="$icon" /> {{ $label }}@if ($name === 'learner.chat')<em data-lc-badge @if (!$chatUnread) hidden @endif>{{ $chatUnread > 9 ? '9+' : $chatUnread }}</em>@endif</a>
                    @endforeach
                </nav>
            </details>
        @endforeach
        <div class="lr-sheet-group">Account</div>
        <nav class="lr-nav">
            <button type="button" data-lr-theme><x-lb-icon name="moon" /> <span data-lr-theme-label>Dark mode</span></button>
            <button type="submit" form="lrLogout" class="is-bad"><x-lb-icon name="log-out" /> Log out</button>
        </nav>
    </div>
</div>

@if (Route::has('learner.chat') && $route !== 'learner.chat')
    {{-- Floating chat launcher on every page --}}
    <a href="{{ route('learner.chat') }}" class="lr-fab" aria-label="Chat with {{ $libName }}{{ $chatUnread ? ' (' . $chatUnread . ' unread)' : '' }}">
        <span class="lr-fab-glow" aria-hidden="true"></span>
        <span class="lr-fab-ring" aria-hidden="true"></span>
        <span class="lr-fab-body">
            <span class="lr-fab-ic"><x-lb-icon name="message-circle" /><x-lb-icon name="sparkles" class="lr-fab-spark" /></span>
            <span class="lr-fab-txt"><b>Chat with library</b><small>Ask anything, privately</small></span>
        </span>
        <em class="lr-fab-badge" data-lc-badge @if (!$chatUnread) hidden @endif>{{ $chatUnread > 9 ? '9+' : $chatUnread }}</em>
    </a>
@endif

<form id="lrLogout" action="{{ route('logout', ['portal' => currentPortal()]) }}" method="POST" hidden>@csrf</form>
<div class="lr-offline" data-lr-offline hidden>You are offline. Check your internet.</div>

<script>
(function () {
    var doc = document, root = doc.documentElement;

    // account menu
    var menuBtn = doc.querySelector('[data-lr-menu]'), pop = doc.querySelector('[data-lr-menu-pop]');
    if (menuBtn && pop) {
        menuBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            pop.hidden = !pop.hidden;
            menuBtn.setAttribute('aria-expanded', String(!pop.hidden));
        });
        doc.addEventListener('click', function (e) {
            if (!pop.hidden && !pop.contains(e.target)) { pop.hidden = true; menuBtn.setAttribute('aria-expanded', 'false'); }
        });
    }

    // desktop: collapse / show the sidebar (remembered)
    var app = doc.querySelector('.lr-app');
    try { if (localStorage.getItem('lr-side') === 'off') app.classList.add('is-collapsed'); } catch (e) {}
    doc.querySelectorAll('[data-lr-collapse]').forEach(function (b) {
        b.addEventListener('click', function () {
            app.classList.toggle('is-collapsed');
            try { localStorage.setItem('lr-side', app.classList.contains('is-collapsed') ? 'off' : 'on'); } catch (e) {}
        });
    });

    // "More" sheet
    function sheet(open) { doc.body.classList.toggle('lr-sheet-open', open); }
    doc.querySelectorAll('[data-lr-sheet-open]').forEach(function (b) { b.addEventListener('click', function () { sheet(true); }); });
    doc.querySelectorAll('[data-lr-sheet-close]').forEach(function (b) { b.addEventListener('click', function () { sheet(false); }); });
    doc.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') { sheet(false); if (pop) pop.hidden = true; }
    });

    // dark mode
    function themeLabel() {
        var dark = root.getAttribute('data-lp-theme') === 'dark';
        doc.querySelectorAll('[data-lr-theme-label]').forEach(function (s) { s.textContent = dark ? 'Light mode' : 'Dark mode'; });
    }
    themeLabel();
    doc.querySelectorAll('[data-lr-theme]').forEach(function (b) {
        b.addEventListener('click', function () {
            var t = root.getAttribute('data-lp-theme') === 'dark' ? 'light' : 'dark';
            root.setAttribute('data-lp-theme', t);
            try { localStorage.setItem('lp-theme', t); } catch (e) {}
            themeLabel();
        });
    });

    // close flash messages
    doc.querySelectorAll('[data-lr-close]').forEach(function (b) {
        b.addEventListener('click', function () { b.closest('.lr-alert').remove(); });
    });

    // forms: one submit only, show busy state
    doc.querySelectorAll('form[data-lr-form]').forEach(function (f) {
        f.addEventListener('submit', function () {
            var btn = f.querySelector('[type="submit"]');
            if (btn) { btn.classList.add('is-busy'); btn.setAttribute('aria-busy', 'true'); }
        });
    });

    // file picker: show the chosen file name
    doc.querySelectorAll('.lr-file input[type="file"]').forEach(function (inp) {
        inp.addEventListener('change', function () {
            var b = inp.closest('.lr-file').querySelector('b');
            if (b && inp.files && inp.files[0]) b.textContent = inp.files[0].name;
        });
    });

    // offline notice
    var off = doc.querySelector('[data-lr-offline]');
    function net() { if (off) off.hidden = navigator.onLine; }
    window.addEventListener('online', net); window.addEventListener('offline', net); net();
})();
</script>
@if (Route::has('learner.chat.unread'))
<script src="{{ asset('public/js/lb-chat-notify.js') }}?v={{ @filemtime(public_path('js/lb-chat-notify.js')) }}"></script>
<script>
    LibraroNotify.start({ url: @json(route('learner.chat.unread')), scope: 'learner:{{ Auth::guard('learner')->id() }}', who: 'student', chatUrl: @json(route('learner.chat')), name: @json($libName), onChatPage: {{ $route === 'learner.chat' ? 'true' : 'false' }}, interval: 5000 });
</script>
@endif
@stack('lr-scripts')
</body>
</html>
