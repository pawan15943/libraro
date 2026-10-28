/* Libraro panel shell — theme toggle + sidebar state. No dependencies. */
(function () {
    'use strict';
    var root = document.documentElement;

    function setTheme(t) {
        root.setAttribute('data-lp-theme', t);
        root.setAttribute('data-theme', t);
        if (t === 'dark') root.setAttribute('data-bs-theme', 'dark'); else root.removeAttribute('data-bs-theme');
        try { localStorage.setItem('lp-theme', t); } catch (e) {}
        document.dispatchEvent(new CustomEvent('lp:theme', { detail: t }));
    }

    function init() {
        var tb = document.getElementById('lpThemeBtn');
        if (tb) tb.addEventListener('click', function () {
            setTheme(root.getAttribute('data-lp-theme') === 'dark' ? 'light' : 'dark');
        });

        // Desktop: remember whether the sidebar was hidden.
        var sb = document.getElementById('lpSidebar');
        var toggle = document.getElementById('sidebar');
        var desk = function () { return window.innerWidth > 991; };
        try { if (sb && desk() && localStorage.getItem('lp-sbc') === '1') sb.classList.add('w-120'); } catch (e) {}
        if (sb && toggle) {
            toggle.addEventListener('click', function () {
                // the layout's jQuery handler toggles the class; read it after it runs
                setTimeout(function () {
                    if (desk()) { try { localStorage.setItem('lp-sbc', sb.classList.contains('w-120') ? '1' : '0'); } catch (e) {} }
                    toggle.setAttribute('aria-expanded', desk() ? String(!sb.classList.contains('w-120')) : String(sb.classList.contains('sidebar-open-mob')));
                }, 0);
            });
        }
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && sb && sb.classList.contains('sidebar-open-mob')) {
                sb.classList.remove('sidebar-open-mob');
                var bd = document.getElementById('sidebarBackdrop'); if (bd) bd.classList.remove('active');
            }
        });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();

/* Learner action links (learner/partials/learner-actions): swap the Font Awesome glyphs for the panel icon set.
   Links, classes and handlers are untouched. Used by the details drawer, the members list menus and quick icons. */
(function () {
    'use strict';
    var SPRITE = '';
    function sprite() { if (!SPRITE) { var u = document.querySelector('use[href*="lb-icons.svg"]'); SPRITE = u ? u.getAttribute('href').split('#')[0] : ''; } return SPRITE; }
    function ic(n) { return '<svg class="lb-i" aria-hidden="true"><use href="' + sprite() + '#i-' + n + '"></use></svg>'; }
    function brand(n) { return '<svg class="lb-ib" aria-hidden="true"><use href="' + sprite() + '#b-' + n + '"></use></svg>'; }
    var MAP = [
        [/renew/i, 'refresh-cw'], [/upgrade/i, 'circle-arrow-up'], [/change plan/i, 'repeat'], [/edit plan/i, 'square-pen'],
        [/swap/i, 'arrow-left-right'], [/other pay|misc/i, 'hand-coins'], [/wallet/i, 'wallet'], [/settle/i, 'scale'],
        [/transaction/i, 'receipt'], [/receipt/i, 'receipt-text'], [/id card/i, 'id-card'], [/attendance link|share/i, 'share-2'],
        [/attendance/i, 'calendar-check'], [/activity/i, 'activity'], [/close/i, 'circle-x'], [/delete/i, 'trash'],
        [/gift/i, 'gift'], [/unfreez|freez/i, 'snowflake'], [/reactiv/i, 'rotate-ccw'], [/sms|text/i, 'message-square-text'],
        [/whatsapp/i, '@whatsapp'], [/reminder/i, 'bell-ring'], [/profile|full details|^\s*view\b/i, 'user-round'],
        [/edit/i, 'pencil'], [/history/i, 'clock-3'], [/expire|extension/i, 'hourglass'], [/payment|collect/i, 'indian-rupee']
    ];
    function pick(t) { for (var i = 0; i < MAP.length; i++) if (MAP[i][0].test(t)) return MAP[i][1]; return null; }
    window.lpStyleOps = function (root) {
        var el = root && root.jquery ? root[0] : root;
        if (!el || !el.querySelectorAll) return;
        el.querySelectorAll('.modal-op-item, .mobile-action-item').forEach(function (it) {
            if (it.getAttribute('data-lp-op')) return;
            it.setAttribute('data-lp-op', '1');
            var label = (it.querySelector('.op-icon-label, .mobile-action-label') || {}).textContent || '';
            var a = it.matches('.mobile-action-item') ? it.querySelector('.action-btn') : it;
            var title = (a && (a.getAttribute('title') || a.getAttribute('data-bs-title') || a.getAttribute('data-bs-original-title'))) || '';
            var t = (label + ' ' + title).trim();
            var n = pick(t);
            var box = it.matches('.mobile-action-item') ? a : it.querySelector('.op-icon-circle');
            if (box && n && box.querySelector('i')) box.innerHTML = n.charAt(0) === '@' ? brand(n.slice(1)) : ic(n);
            if (/delete|close seat|^close/i.test(t)) it.classList.add('is-danger');
            if (a && !a.getAttribute('aria-label')) a.setAttribute('aria-label', label.trim() || title);
        });
    };
})();

/* Floating menus: the member "⋯" menus (and other dropdowns inside member lists) live in cards / list rows that clip
   their overflow. Instead of Bootstrap's static placement, position them with Popper as `fixed` so they escape the
   clipping box, flip above when there's no room below, and stay inside the screen on every side (top bar and mobile
   dock included). The menu stays where it is in the DOM, so every existing click handler keeps working. */
(function () {
    'use strict';
    var SEL = '.lml [data-bs-toggle="dropdown"], .lp-float[data-bs-toggle="dropdown"]';

    function pad() {
        var phone = window.matchMedia('(max-width: 767.98px)').matches;
        var bar = document.querySelector('.content-area .header.lp-top');
        var top = bar ? Math.max(0, bar.getBoundingClientRect().bottom) + 8 : 12;
        return { top: top, right: 12, left: 12, bottom: phone ? 96 : 12 };
    }

    // Cap the menu height to the room Popper actually has, so tall menus scroll inside instead of running off-screen.
    var fitHeight = {
        name: 'lpFitHeight', enabled: true, phase: 'beforeWrite', requires: ['computeStyles'],
        fn: function (o) {
            var st = o.state, p = pad(), r = st.rects.reference, place = st.placement.split('-')[0];
            var room = place === 'top' ? r.y - p.top - 8 : window.innerHeight - (r.y + r.height) - p.bottom - 8;
            var max = Math.max(180, Math.min(room, window.innerHeight - p.top - p.bottom));
            st.styles.popper.maxHeight = max + 'px';
        }
    };

    function make(t) {
        var inst = bootstrap.Dropdown.getInstance(t);
        if (inst && inst._lpFloat) return;
        if (inst) inst.dispose();
        t.removeAttribute('data-bs-display');
        var end = t.parentElement && t.parentElement.querySelector('.dropdown-menu-end');
        var d = new bootstrap.Dropdown(t, {
            display: 'dynamic',
            autoClose: t.getAttribute('data-bs-auto-close') || true,
            popperConfig: function (cfg) {
                var p = pad();
                cfg.strategy = 'fixed';
                cfg.placement = end ? 'bottom-end' : 'bottom-start';
                cfg.modifiers = (cfg.modifiers || []).filter(function (m) { return ['flip', 'preventOverflow', 'offset', 'computeStyles'].indexOf(m.name) === -1; }).concat([
                    { name: 'offset', options: { offset: [0, 8] } },
                    { name: 'flip', options: { padding: p, fallbackPlacements: end ? ['top-end', 'bottom-start', 'top-start'] : ['top-start', 'bottom-end', 'top-end'] } },
                    { name: 'preventOverflow', options: { padding: p, altAxis: true, tether: false } },
                    { name: 'computeStyles', options: { gpuAcceleration: false, adaptive: false } },
                    fitHeight
                ]);
                return cfg;
            }
        });
        d._lpFloat = true;
    }

    // Bootstrap listens on `document` in the capture phase; `window` capture runs before it, so the very first click
    // already opens the floating menu.
    window.addEventListener('click', function (e) {
        var t = e.target.closest && e.target.closest(SEL);
        if (t && window.bootstrap && bootstrap.Dropdown) make(t);
    }, true);
    window.addEventListener('keydown', function (e) {
        var t = e.target.closest && e.target.closest(SEL);
        if (t && window.bootstrap && bootstrap.Dropdown && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) make(t);
    }, true);

})();

/* Quick rail show / hide. Desktop: collapses the rail and remembers it. Phones: slides it in over the page
   (tap outside, Esc or the arrow closes it). */
(function () {
    'use strict';
    function init() {
        var btn = document.getElementById('lpRailToggle');
        var rail = document.getElementById('lpRail');
        if (!btn || !rail) return;
        var body = document.body;
        var mq = window.matchMedia('(max-width: 991.98px)');

        function isOpen() { return mq.matches ? body.classList.contains('lp-rail-open') : !body.classList.contains('lp-rail-closed'); }
        function sync() {
            var open = isOpen();
            var label = open ? 'Hide quick actions' : 'Show quick actions';
            btn.setAttribute('aria-expanded', String(open));
            btn.setAttribute('aria-label', label);
            btn.setAttribute('title', label);
        }
        function set(open) {
            if (mq.matches) {
                body.classList.toggle('lp-rail-open', open);
            } else {
                body.classList.toggle('lp-rail-closed', !open);
                try { localStorage.setItem('lp-rail', open ? 'open' : 'closed'); } catch (e) {}
            }
            sync();
        }

        btn.addEventListener('click', function (e) { e.stopPropagation(); set(!isOpen()); });
        var bd = document.getElementById('lpRailBackdrop');
        if (bd) bd.addEventListener('click', function () { set(false); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && mq.matches && isOpen()) set(false); });
        // phones: picking an action closes the rail
        rail.addEventListener('click', function (e) { if (mq.matches && e.target.closest('a, button')) setTimeout(function () { set(false); }, 150); });
        // crossing the breakpoint: phones always start closed, desktop restores the saved choice
        var onChange = function () {
            body.classList.remove('lp-rail-open');
            var closed = false;
            try { closed = localStorage.getItem('lp-rail') === 'closed'; } catch (e) {}
            body.classList.toggle('lp-rail-closed', !mq.matches && closed);
            sync();
        };
        if (mq.addEventListener) mq.addEventListener('change', onChange); else mq.addListener(onChange);
        sync();
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();
})();
