/*
 * Calling CRM follow-up alerts, on every admin page of a user who works leads.
 * Every minute (and when the tab comes back into view) it asks which follow-ups
 * are due, updates the badges and shows a toast + browser notification for each
 * new one. The server marks a reminder as notified, so it alerts only once.
 */
(function () {
    'use strict';

    var script = document.currentScript;
    var url = script && script.getAttribute('data-due-url');
    if (!url) return;

    var busy = false;

    function badges(count) {
        document.querySelectorAll('[data-crm-due-badge]').forEach(function (b) {
            b.hidden = !count;
            b.textContent = count > 99 ? '99+' : String(count);
        });
    }

    function alertFor(item) {
        var text = item.at + (item.note ? ' · ' + item.note : '');
        if (window.toastr) {
            toastr.options = { closeButton: true, timeOut: 12000, extendedTimeOut: 4000, positionClass: 'toast-bottom-right', tapToDismiss: false };
            toastr.info('<a href="' + item.url + '" class="crm-toast-link">Call ' + escapeHtml(item.name) + ' now →</a><br><small>' + escapeHtml(text) + '</small>', 'Follow-up due');
        }
        if ('Notification' in window && Notification.permission === 'granted' && document.visibilityState !== 'visible') {
            try {
                var n = new Notification('Follow-up due: ' + item.name, { body: text + '\n+91 ' + item.mobile, tag: 'crm-' + item.id });
                n.onclick = function () { window.focus(); window.location = item.url; };
            } catch (e) {}
        }
    }

    function escapeHtml(s) {
        return String(s || '').replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
    }

    function check() {
        if (busy || document.prerendering || document.visibilityState === 'hidden' && !('Notification' in window && Notification.permission === 'granted')) return;
        busy = true;
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (!data) return;
                badges(data.count || 0);
                (data.items || []).forEach(alertFor);
            })
            .catch(function () {})
            .then(function () { busy = false; });
    }

    // "Turn on alerts" buttons (Follow-ups page)
    document.addEventListener('click', function (e) {
        var b = e.target.closest && e.target.closest('[data-crm-enable-alerts]');
        if (!b || !('Notification' in window)) return;
        Notification.requestPermission().then(function (p) {
            b.textContent = p === 'granted' ? 'Alerts are on' : 'Alerts blocked in browser';
            b.disabled = true;
        });
    });

    function initButtons() {
        document.querySelectorAll('[data-crm-enable-alerts]').forEach(function (b) {
            if (!('Notification' in window)) { b.hidden = true; return; }
            if (Notification.permission === 'granted') { b.textContent = 'Alerts are on'; b.disabled = true; }
        });
    }

    // The server marks a reminder as notified when it is fetched, so a page that is only
    // prerendered (Speculation Rules) must not ask: start once the page is really shown.
    function start() {
        check();
        setInterval(check, 60000);
        document.addEventListener('visibilitychange', function () { if (document.visibilityState === 'visible') check(); });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initButtons); else initButtons();
    if (document.prerendering) document.addEventListener('prerenderingchange', start, { once: true }); else start();
})();
