@php
    $splashUser = getAuthenticatedUser();
    $splashLib = getLibrary();
    $splashName = trim(strtok((string) ($splashUser->name ?? ''), ' ') ?: '');
    $splashLibName = optional($splashLib)->library_name ?: ($splashUser->library_name ?? null);
@endphp
<div class="libraro-brand-welcome-splash" id="libraroBrandWelcomeSplash" role="status" aria-live="polite">
    <div class="splash-container">
        <div class="splash-logo-stage">
            <img src="{{ asset('public/img/libraro.svg') }}" alt="Libraro" class="splash-logo-img splash-logo-light">
            <img src="{{ asset('public/img/libraro-white.svg') }}" alt="" aria-hidden="true" class="splash-logo-img splash-logo-dark">
        </div>

        <h1 class="splash-title">Welcome back{{ $splashName !== '' ? ', ' . $splashName : '' }}</h1>
        @if($splashLibName)
            <p class="splash-desc">{{ $splashLibName }}</p>
        @endif

        <div class="splash-progress" aria-hidden="true"><span class="splash-progress-bar"></span></div>
        <div class="splash-loader-status" id="splashStatusText">Preparing your dashboard…</div>
    </div>

    <button type="button" class="splash-skip-btn" id="splashSkipBtn" title="Press Esc to skip">
        Skip <kbd>Esc</kbd>
    </button>
</div>

<script>
(function() {
    // Immediately suppress any legacy loaders
    try {
        var l1 = document.getElementById('loaderone'); if (l1) l1.style.display = 'none';
        var l2 = document.getElementById('loader'); if (l2) l2.style.display = 'none';
    } catch(e) {}

    var splash = document.getElementById('libraroBrandWelcomeSplash');
    if (!splash) return;

    var statusText = document.getElementById('splashStatusText');
    var skipBtn = document.getElementById('splashSkipBtn');
    var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var duration = reduceMotion ? 900 : 2200;

    var stages = [
        { time: 0, status: 'Preparing your dashboard…' },
        { time: 800, status: 'Syncing seats, learners and collections…' },
        { time: 1600, status: 'Almost ready…' }
    ];

    var dismissed = false;

    function dismissSplash() {
        if (dismissed) return;
        dismissed = true;
        try {
            sessionStorage.setItem('libraro_splash_seen', Date.now());
        } catch(e) {}

        splash.classList.add('splash-exit');
        setTimeout(function() {
            if (splash && splash.parentNode) {
                splash.parentNode.removeChild(splash);
            }
        }, 400);
    }

    if (skipBtn) {
        skipBtn.addEventListener('click', dismissSplash);
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') dismissSplash();
    });

    splash.style.setProperty('--splash-duration', duration + 'ms');

    if (!reduceMotion) {
        stages.forEach(function(st) {
            if (!st.time) return;
            setTimeout(function() {
                if (dismissed || !statusText) return;
                statusText.classList.add('is-swapping');
                setTimeout(function() {
                    statusText.textContent = st.status;
                    statusText.classList.remove('is-swapping');
                }, 180);
            }, st.time);
        });
    }

    setTimeout(dismissSplash, duration);
})();
</script>
