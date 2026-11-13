/* Libraro panel — counts · shifts · filters behind icons (partials/view-toggles).
   A button [data-vt-btn="filters"] shows / hides every [data-vt="filters"] on the page
   (public/css/lb-clean.css hides them until they have .vt-on). Sections the server opens
   (e.g. filters in use) come with .vt-on already. Sections re-rendered by AJAX keep the
   state the owner chose. */
(function () {
    'use strict';
    var KEYS = ['counts', 'shifts', 'filters'];
    var chosen = {};   // key -> true/false once the owner taps its icon

    function sections(key, root) { return (root || document).querySelectorAll('[data-vt="' + key + '"]'); }
    function sync(key) {
        var on = !!document.querySelector('[data-vt="' + key + '"].vt-on');
        Array.prototype.forEach.call(document.querySelectorAll('[data-vt-btn="' + key + '"]'), function (b) {
            b.classList.toggle('is-on', on);
            b.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
    }
    function apply(key, on, root) {
        Array.prototype.forEach.call(sections(key, root), function (el) { el.classList.toggle('vt-on', on); });
    }

    document.addEventListener('click', function (e) {
        var b = e.target.closest('[data-vt-btn]');
        if (!b) return;
        e.preventDefault();
        var key = b.getAttribute('data-vt-btn');
        var on = !b.classList.contains('is-on');
        chosen[key] = on;
        apply(key, on);
        sync(key);
        if (on) {
            var first = document.querySelector('[data-vt="' + key + '"]');
            if (first && first.getBoundingClientRect().top > window.innerHeight - 80) first.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    });

    function boot() {
        KEYS.forEach(sync);
        if (!window.MutationObserver) return;
        new MutationObserver(function (list) {
            var touched = false;
            list.forEach(function (m) {
                Array.prototype.forEach.call(m.addedNodes, function (n) {
                    if (n.nodeType !== 1) return;
                    KEYS.forEach(function (key) {
                        if (!(key in chosen)) return;
                        if (n.matches && n.matches('[data-vt="' + key + '"]')) { n.classList.toggle('vt-on', chosen[key]); touched = true; }
                        if (n.querySelector && n.querySelector('[data-vt="' + key + '"]')) { apply(key, chosen[key], n); touched = true; }
                    });
                });
            });
            if (touched) KEYS.forEach(sync);
        }).observe(document.body, { childList: true, subtree: true });
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();
})();
