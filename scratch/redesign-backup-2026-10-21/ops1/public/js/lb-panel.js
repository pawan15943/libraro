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
