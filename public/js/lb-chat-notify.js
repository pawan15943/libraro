/*
 * Libraro chat notifier: runs on every page of the student portal and the owner panel.
 * Polls a tiny endpoint (counts only, never message text: messages are end-to-end encrypted),
 * keeps every [data-lc-badge] up to date, and tells the user about new messages with a toast,
 * a soft sound, the tab title and (if allowed) a browser notification.
 *
 * window.LibraroNotify.start({ url, scope, who: 'student'|'library', chatUrl, name, onChatPage })
 */
(function () {
    'use strict';
    var hiddenTick = 0, cfg, timer = null, seenKey, first = true, baseTitle = document.title, last = { unread: 0, chats: 0, latest: 0 };

    function lsGet(k) { try { return localStorage.getItem(k); } catch (e) { return null; } }
    function lsSet(k, v) { try { localStorage.setItem(k, v); } catch (e) {} }

    // ----- badges: students show the number of unread messages, the owner the number of chats with unread messages -----
    function paint(n) {
        document.querySelectorAll('[data-lc-badge]').forEach(function (b) {
            b.textContent = n > 9 ? '9+' : String(n);
            b.hidden = n <= 0;
            b.setAttribute('aria-label', n + ' unread');
        });
        document.title = n > 0 ? '(' + n + ') ' + baseTitle.replace(/^\(\d+\)\s*/, '') : baseTitle.replace(/^\(\d+\)\s*/, '');
    }

    // ----- sound: short, quiet two-note chime (needs the page to have been touched once, else silently skipped) -----
    function chime() {
        try {
            var A = window.AudioContext || window.webkitAudioContext; if (!A) return;
            var ctx = new A(), t = ctx.currentTime;
            [660, 880].forEach(function (f, i) {
                var o = ctx.createOscillator(), g = ctx.createGain();
                o.type = 'sine'; o.frequency.value = f; o.connect(g); g.connect(ctx.destination);
                g.gain.setValueAtTime(0.0001, t + i * 0.12); g.gain.exponentialRampToValueAtTime(0.12, t + i * 0.12 + 0.02); g.gain.exponentialRampToValueAtTime(0.0001, t + i * 0.12 + 0.25);
                o.start(t + i * 0.12); o.stop(t + i * 0.12 + 0.28);
            });
            setTimeout(function () { ctx.close && ctx.close(); }, 700);
        } catch (e) {}
    }

    // ----- toast -----
    var host = null;
    function toast(title, body) {
        if (!host) { host = document.createElement('div'); host.className = 'lcn-host'; host.setAttribute('aria-live', 'polite'); document.body.appendChild(host); }
        var a = document.createElement('a'); a.className = 'lcn-toast'; a.href = cfg.chatUrl;
        var ic = document.createElement('span'); ic.className = 'lcn-ic'; ic.textContent = '💬';
        var t = document.createElement('span'); t.className = 'lcn-t';
        var b1 = document.createElement('b'); b1.textContent = title; var s1 = document.createElement('small'); s1.textContent = body;
        t.appendChild(b1); t.appendChild(s1);
        var x = document.createElement('button'); x.type = 'button'; x.className = 'lcn-x'; x.setAttribute('aria-label', 'Dismiss'); x.textContent = '×';
        x.onclick = function (e) { e.preventDefault(); e.stopPropagation(); gone(); };
        a.appendChild(ic); a.appendChild(t); a.appendChild(x); host.appendChild(a);
        while (host.children.length > 3) host.firstChild.remove();
        var timer2 = setTimeout(gone, 7000);
        function gone() { clearTimeout(timer2); a.classList.add('is-out'); setTimeout(function () { a.remove(); }, 250); }
    }

    function systemNotice(title, body) {
        try {
            if (!('Notification' in window) || Notification.permission !== 'granted' || !document.hidden) return;
            var n = new Notification(title, { body: body, tag: 'libraro-chat', icon: cfg.icon || undefined });
            n.onclick = function () { window.focus(); location.href = cfg.chatUrl; n.close(); };
        } catch (e) {}
    }

    function describe(d) {
        if (cfg.who === 'library') {
            var r = (d.recent && d.recent[0]) || null;
            var title = r ? 'New message from ' + r.name : 'New student message';
            var more = d.chats > 1 ? d.chats + ' chats have unread messages' : (r && r.count > 1 ? r.count + ' new messages' : 'Tap to reply');
            return [title, more];
        }
        return ['New message from ' + (cfg.name || 'your library'), d.unread > 1 ? d.unread + ' new messages' : 'Tap to read and reply'];
    }

    function handle(d) {
        var n = cfg.who === 'library' ? d.chats : d.unread;
        paint(n);
        var latest = d.latest || 0, seen = parseInt(lsGet(seenKey) || '0', 10) || 0;
        if (first) { first = false; if (latest > seen) lsSet(seenKey, String(latest)); last = d; return; } // do not shout about old unread messages on page load
        if (latest > seen) {
            lsSet(seenKey, String(latest)); // other open tabs see this and stay quiet
            var quiet = cfg.onChatPage && !document.hidden; // the open conversation already shows it
            if (!quiet) { var m = describe(d); toast(m[0], m[1]); chime(); systemNotice(m[0], m[1]); }
        }
        last = d;
    }

    function poll() {
        if (document.hidden && (hiddenTick = (hiddenTick + 1) % 3) !== 0) return; // hidden tabs still check, just less often
        fetch(cfg.url, { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, cache: 'no-store' })
            .then(function (r) { if (!r.ok) { if (r.status === 403 || r.status === 401 || r.status === 419) stop(); throw new Error(); } return r.json(); })
            .then(handle).catch(function () {});
    }
    function stop() { clearInterval(timer); timer = null; }
    function start(c) {
        cfg = c; seenKey = 'lcn-seen:' + c.scope;
        poll(); timer = setInterval(poll, c.interval || 5000);
        document.addEventListener('visibilitychange', function () { if (!document.hidden) poll(); });
        window.addEventListener('lc:read', poll); // chat page tells us it marked messages read
    }

    // chat page asks for permission from a button press
    function askPermission() {
        if (!('Notification' in window)) return Promise.resolve('denied');
        return Notification.requestPermission();
    }

    window.LibraroNotify = { start: start, askPermission: askPermission, refresh: function () { if (cfg) poll(); } };
})();
