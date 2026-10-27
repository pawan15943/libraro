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
