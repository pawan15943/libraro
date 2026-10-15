<div class="libraro-brand-welcome-splash" id="libraroBrandWelcomeSplash" role="status" aria-live="polite">
    <!-- Ambient Luminous Glow Orbs -->
    <div class="splash-ambient-orb splash-orb-1"></div>
    <div class="splash-ambient-orb splash-orb-2"></div>
    <div class="splash-ambient-orb splash-orb-3"></div>

    <div class="splash-container">
        <!-- Brand Logo Stage -->
        <div class="splash-logo-stage">
            <img src="{{ asset('public/img/libraro-white.webp') }}" alt="Libraro" class="splash-logo-img">
        </div>

        <!-- Staged Dynamic Messages -->
        <div class="splash-message-wrapper">
            <div class="splash-message-content" id="splashMessageContent">
                <span class="splash-badge" id="splashBadge">
                    <i class="fa-solid fa-sparkles"></i> SMART MANAGEMENT
                </span>
                <h1 class="splash-title" id="splashTitle">Manage Library with Ease</h1>
                <p class="splash-desc" id="splashDesc">Streamlining seats, shifts, student records, and bookings effortlessly.</p>
            </div>
        </div>

        <!-- Simple Rounded Chakri (Sun Style) Spinner Loader -->
        <div class="splash-loader-wrapper">
            <svg class="splash-sun-spinner" viewBox="0 0 50 50" aria-label="Loading...">
                <line x1="25" y1="6" x2="25" y2="13" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" opacity="1" transform="rotate(0 25 25)" />
                <line x1="25" y1="6" x2="25" y2="13" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" opacity="0.91" transform="rotate(30 25 25)" />
                <line x1="25" y1="6" x2="25" y2="13" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" opacity="0.82" transform="rotate(60 25 25)" />
                <line x1="25" y1="6" x2="25" y2="13" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" opacity="0.73" transform="rotate(90 25 25)" />
                <line x1="25" y1="6" x2="25" y2="13" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" opacity="0.64" transform="rotate(120 25 25)" />
                <line x1="25" y1="6" x2="25" y2="13" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" opacity="0.55" transform="rotate(150 25 25)" />
                <line x1="25" y1="6" x2="25" y2="13" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" opacity="0.46" transform="rotate(180 25 25)" />
                <line x1="25" y1="6" x2="25" y2="13" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" opacity="0.37" transform="rotate(210 25 25)" />
                <line x1="25" y1="6" x2="25" y2="13" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" opacity="0.28" transform="rotate(240 25 25)" />
                <line x1="25" y1="6" x2="25" y2="13" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" opacity="0.19" transform="rotate(270 25 25)" />
                <line x1="25" y1="6" x2="25" y2="13" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" opacity="0.12" transform="rotate(300 25 25)" />
                <line x1="25" y1="6" x2="25" y2="13" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" opacity="0.08" transform="rotate(330 25 25)" />
            </svg>
            <div class="splash-loader-status" id="splashStatusText">Loading workspace...</div>
        </div>

        <!-- Skip Button (Subtle & Keyboard Friendly) -->
        <button type="button" class="splash-skip-btn" id="splashSkipBtn" title="Press Esc to skip">
            <span>Skip</span> <i class="fa-solid fa-chevron-right ms-1"></i>
        </button>
    </div>
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

    var badge = document.getElementById('splashBadge');
    var title = document.getElementById('splashTitle');
    var desc = document.getElementById('splashDesc');
    var content = document.getElementById('splashMessageContent');
    var statusText = document.getElementById('splashStatusText');
    var skipBtn = document.getElementById('splashSkipBtn');

    var stages = [
        {
            time: 0,
            badge: '<i class="fa-solid fa-sparkles"></i> SMART MANAGEMENT',
            title: 'Manage Library with Ease',
            desc: 'Streamlining seats, shifts, student records, and bookings effortlessly.',
            status: 'Initializing workspace...'
        },
        {
            time: 1200,
            badge: '<i class="fa-solid fa-chart-line"></i> REAL-TIME TRACKING',
            title: 'Track Everything',
            desc: 'Live collection, attendance, dues, and library performance in one place.',
            status: 'Syncing live data...'
        },
        {
            time: 2400,
            badge: '<i class="fa-solid fa-rocket"></i> WORKSPACE READY',
            title: 'We are now taking you to dashboard',
            desc: 'Welcome back! Setting up your customized library workspace...',
            status: 'Launching dashboard...'
        }
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
        }, 650);
    }

    if (skipBtn) {
        skipBtn.addEventListener('click', dismissSplash);
    }

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') dismissSplash();
    });

    function updateStage(index) {
        if (!stages[index] || !content) return;
        content.classList.add('fade-out');

        setTimeout(function() {
            var st = stages[index];
            if (badge) badge.innerHTML = st.badge;
            if (title) title.textContent = st.title;
            if (desc) desc.textContent = st.desc;
            if (statusText) statusText.textContent = st.status;

            content.classList.remove('fade-out');
            content.classList.add('fade-in');
            setTimeout(function() {
                content.classList.remove('fade-in');
            }, 300);
        }, 220);
    }

    // Schedule progressive stages
    setTimeout(function() { updateStage(1); }, 1200);
    setTimeout(function() { updateStage(2); }, 2400);
    setTimeout(dismissSplash, 3600);
})();
</script>
