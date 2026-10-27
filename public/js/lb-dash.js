/* Libraro panel — owner dashboard (dashboard/admin.blade.php).
   Renders the section tabs, live attendance, the collection drawer, the fees chart and the highlights carousel
   from the JSON in #lpdData. Live attendance re-polls every minute. */
(function () {
    'use strict';

    var dataEl = document.getElementById('lpdData');
    if (!dataEl) return;
    var D = {};
    try { D = JSON.parse(dataEl.textContent || '{}'); } catch (e) { return; }

    /* ---------- helpers ---------- */
    var esc = function (s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); };
    var icon = function (n, cls) { return '<svg class="lb-i' + (cls ? ' ' + cls : '') + '" aria-hidden="true" focusable="false"><use href="' + D.sprite + '#i-' + n + '"></use></svg>'; };
    var inr = function (v) { var n = Number(v) || 0; return (n < 0 ? '−' : '') + '₹' + Math.abs(n).toLocaleString('en-IN', { maximumFractionDigits: 2 }); };
    var short = function (v) { var n = Number(v) || 0; return n >= 100000 ? '₹' + (n / 100000).toFixed(n % 100000 ? 1 : 0) + 'L' : n >= 1000 ? '₹' + (n / 1000).toFixed(n % 1000 ? 1 : 0) + 'k' : '₹' + n; };
    var tone = function (name) { var h = 0; String(name || '').split('').forEach(function (c) { h = (h * 31 + c.charCodeAt(0)) >>> 0; }); return 'c' + (h % 5 + 1); };
    var face = function (p, cls) { return '<span class="lp-face ' + tone(p.name) + (cls ? ' ' + cls : '') + '">' + (p.img ? '<img src="' + esc(p.img) + '" alt="" loading="lazy">' : esc(p.initials || '?')) + '</span>'; };
    var tm = function (ts) { return new Date(ts * 1000).toLocaleTimeString('en-IN', { hour: 'numeric', minute: '2-digit' }); };
    var dur = function (sec, withSec) {
        sec = Math.max(0, Math.floor(sec));
        var h = Math.floor(sec / 3600), m = Math.floor(sec % 3600 / 60), s = sec % 60;
        var out = (h ? h + 'h ' : '') + (h ? String(m).padStart(2, '0') : m) + 'm';
        return withSec ? out + ' ' + String(s).padStart(2, '0') + 's' : out;
    };
    var $ = function (sel, root) { return (root || document).querySelector(sel); };
    var $$ = function (sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); };

    /* =====================================================================
       Section tabs (Command Center / Live Attendance / Insights & Reports)
       ===================================================================== */
    var TAB_KEY = 'lpd-tab';
    var tabBtns = $$('[data-lpd-tab]');
    function showTab(key, focus) {
        if (!$('[data-lpd-pane="' + key + '"]')) key = 'desk';
        tabBtns.forEach(function (b) {
            var on = b.dataset.lpdTab === key;
            b.classList.toggle('is-on', on);
            b.setAttribute('aria-selected', on ? 'true' : 'false');
            b.tabIndex = on ? 0 : -1;
            if (on && focus) b.focus();
        });
        $$('[data-lpd-pane]').forEach(function (p) {
            var on = p.dataset.lpdPane === key;
            p.classList.toggle('is-on', on);
            p.hidden = !on;
        });
        try { localStorage.setItem(TAB_KEY, key); } catch (e) {}
        if (history.replaceState) history.replaceState(null, '', key === 'desk' ? location.pathname + location.search : '#' + key);
        document.dispatchEvent(new CustomEvent('lpd:tab', { detail: key }));
    }
    if (tabBtns.length) {
        tabBtns.forEach(function (b) { b.addEventListener('click', function () { showTab(b.dataset.lpdTab); }); });
        // Arrow keys move between tabs (WAI-ARIA tabs pattern).
        tabBtns[0].parentNode.addEventListener('keydown', function (e) {
            var i = tabBtns.indexOf(document.activeElement);
            if (i < 0 || (e.key !== 'ArrowRight' && e.key !== 'ArrowLeft')) return;
            e.preventDefault();
            showTab(tabBtns[(i + (e.key === 'ArrowRight' ? 1 : -1) + tabBtns.length) % tabBtns.length].dataset.lpdTab, true);
        });
        var start = location.hash.replace('#', '');
        if (!start) { try { start = localStorage.getItem(TAB_KEY) || ''; } catch (e) {} }
        if (start && start !== 'desk') showTab(start);
    }
    document.addEventListener('click', function (e) {
        var a = e.target.closest('[data-lpd-scroll]');
        if (!a) return;
        var el = document.querySelector(a.getAttribute('href'));
        if (!el) return;
        e.preventDefault();
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    /* =====================================================================
       Live attendance
       ===================================================================== */
    var L = D.live, skew = 0, pv = { tab: 'all', shift: 'all', q: '' };
    var STATE = {
        in: { label: 'Inside', tag: 'INSIDE', icon: 'log-in', cls: 'in' },
        out: { label: 'Left', tag: 'LEFT', icon: 'log-out', cls: 'out' },
        late: { label: 'Not arrived', tag: 'ABSENT', icon: 'clock-alert', cls: 'late' }
    };

    function nowSec() { return Math.floor(Date.now() / 1000 + skew); }
    function setLive(data) { L = data; skew = data.now - Date.now() / 1000; }

    function shiftOf(key) { return (L.shifts || []).filter(function (s) { return s.key === key; })[0] || { name: 'General', time: '' }; }
    function insideSec(p, now) { var t = 0, a = p.punches; for (var i = 0; i < a.length; i += 2) t += (i + 1 < a.length ? a[i + 1] : now) - a[i]; return t; }
    function breakSec(p) { var t = 0, a = p.punches; for (var i = 1; i + 1 < a.length; i += 2) t += a[i + 1] - a[i]; return t; }
    function overtime(p, now) {
        if (!p.shiftEnd) return 0;
        if (p.state === 'in') return now - p.shiftEnd;
        if (p.state === 'out') return p.punches[p.punches.length - 1] - p.shiftEnd;
        return 0;
    }
    function runningShift(now) {
        var on = (L.shifts || []).filter(function (s) { return s.start && s.end && s.start <= now && now < s.end; });
        if (!on.length) return 'All shifts';
        return on.slice(0, 2).map(function (s) { return s.name; }).join(' + ') + (on.length > 2 ? ' +' + (on.length - 2) : '');
    }
    function setAll(key, html, isHtml) { $$('[data-live="' + key + '"]').forEach(function (el) { if (isHtml) el.innerHTML = html; else el.textContent = html; }); }

    function renderLiveCard() {
        if (!L) return;
        var now = nowSec(), c = L.counts, pct = c.all ? Math.round(c.in / c.all * 100) : 0;
        var ring = $('[data-live="ring"]');
        if (ring) ring.style.setProperty('--p', pct);
        setAll('pct', pct + '%');
        setAll('in', c.in);
        setAll('noun', c.in === 1 ? 'student' : 'students');
        setAll('all', c.all);
        setAll('shift', runningShift(now));
        setAll('clock', 'Now ' + new Date(now * 1000).toLocaleTimeString('en-IN', { hour: 'numeric', minute: '2-digit' }));
        var ot = L.people.filter(function (p) { return p.state === 'in' && overtime(p, now) > 0; }).length;
        setAll('hint', !c.all ? 'No active members today' : ot ? ot + ' on overtime · ' + c.late + ' yet to arrive' : c.late + ' yet to arrive · ' + c.out + ' have left');
        setAll('floors', (L.floors.length ? L.floors : [{ name: 'Nobody inside yet', n: '' }]).map(function (f) {
            return '<span class="lp-pill">' + icon('layers') + esc(f.name) + (f.n !== '' ? '<b>' + f.n + '</b>' : '') + '</span>';
        }).join(''), true);
        var inside = L.people.filter(function (p) { return p.state === 'in'; });
        setAll('faces', inside.slice(0, 5).map(function (p) { return face(p); }).join('') + (inside.length > 5 ? '<span class="lp-face lpd-more">+' + (inside.length - 5) + '</span>' : ''), true);
        setAll('punches', c.punches);
        setAll('last', L.last ? L.last.name + ' · ' + L.last.kind + ' ' + tm(L.last.t) : '–');
    }

    function renderDrawer() {
        var root = $('#lpdPresent');
        if (!root || !L) return;
        var now = nowSec(), c = L.counts;

        $('[data-pv="tabs"]', root).innerHTML = [['all', 'All', c.all], ['in', 'Inside', c.in], ['out', 'Left', c.out], ['late', 'Not arrived', c.late]].map(function (t) {
            return '<button type="button" data-pv-tab="' + t[0] + '" class="lpd-pv-tab lpd-pv-tab--' + t[0] + (pv.tab === t[0] ? ' is-on' : '') + '"><b>' + t[2] + '</b><span><i></i>' + t[1] + '</span></button>';
        }).join('');

        var q = pv.q.trim().toLowerCase(), rank = { in: 0, out: 1, late: 2 };
        var list = L.people.filter(function (p) {
            return (pv.tab === 'all' || p.state === pv.tab) && (pv.shift === 'all' || p.shift === pv.shift) && (!q || (p.name + ' ' + p.seat).toLowerCase().indexOf(q) > -1);
        }).sort(function (a, b) { return rank[a.state] - rank[b.state] || (b.punches[0] || 0) - (a.punches[0] || 0); });

        var html = '';
        L.shifts.forEach(function (s) {
            var items = list.filter(function (p) { return p.shift === s.key; });
            if (!items.length) return;
            var all = L.people.filter(function (p) { return p.shift === s.key; });
            var n = function (st) { return all.filter(function (p) { return p.state === st; }).length; };
            var ot = all.filter(function (p) { return p.state === 'in' && overtime(p, now) > 0; }).length;
            html += '<div class="lpd-pv-g"><div class="lpd-pv-gh">' + icon('clock-3') + '<b>' + esc(s.name) + '</b><small>' + esc(s.time) + '</small>' +
                '<span><em class="lpd-ok">' + n('in') + ' in</em><em class="lpd-bad">' + n('out') + ' left</em><em class="lpd-warn">' + n('late') + ' absent</em>' + (ot ? '<em class="lpd-ot">' + ot + ' overtime</em>' : '') + '</span></div>';
            items.forEach(function (p) { html += personCard(p, s, now); });
            html += '</div>';
        });
        $('[data-pv="list"]', root).innerHTML = html || '<div class="lp-empty lpd-fill"><span>' + icon('user-search') + '</span><b>No students here</b>Try another tab, shift or search.</div>';
    }

    function personCard(p, s, now) {
        var st = STATE[p.state], n = p.punches.length, ot = overtime(p, now);
        var time = p.state === 'late' ? 'Absent' : dur(insideSec(p, now), p.state === 'in');
        var chips = '';
        if (!n) {
            chips = '<span class="lpd-pn lpd-pn--late">' + icon('clock') + 'Booked · ' + esc(s.time || 'no punch yet') + '</span>';
        } else {
            var idx = n === 1 ? [0] : [0, n - 1];
            idx.forEach(function (j, k) {
                var isIn = j % 2 === 0;
                if (k) chips += '<span class="lpd-pn-sep">' + (n > 2 ? '<em>+' + (n - 2) + '</em>' : '') + '</span>';
                chips += '<span class="lpd-pn ' + (isIn ? 'lpd-pn--in' : 'lpd-pn--out') + (k === idx.length - 1 ? ' is-last' : '') + '">' + icon(isIn ? 'log-in' : 'log-out') +
                    '<small>' + (k ? 'LAST' : 'FIRST') + '</small>' + (isIn ? 'IN' : 'OUT') + ' <b>' + tm(p.punches[j]) + '</b></span>';
            });
        }
        return '<article class="lpd-pc lpd-pc--' + st.cls + (ot > 0 ? ' is-ot' : '') + '">' +
            '<div class="lpd-pc-blk">' + icon(st.icon) + '<span>' + st.tag + '</span></div>' +
            '<div class="lpd-pc-b">' +
                '<div class="lpd-pc-top">' + face(p, 'lpd-pc-av') +
                    '<a href="' + esc(p.url) + '" class="lpd-pc-n"><b>' + esc(p.name) + '</b><small><strong>' + esc(p.seat) + '</strong> · ' + esc(s.name) + ' <strong>' + esc(s.time) + '</strong></small></a>' +
                    '<span class="lpd-pc-d"><b>' + time + '</b><small>' + (p.state === 'in' ? 'inside now · live' : p.state === 'out' ? 'total time inside' : 'no punch yet') + '</small></span>' +
                '</div>' +
                (ot > 0 ? '<div class="lpd-pc-ot">' + icon('timer-reset') + '<span>' + (p.state === 'in' ? 'Overtime · shift ended ' + tm(p.shiftEnd) : 'Stayed past shift end ' + tm(p.shiftEnd)) + '</span><b>+' + dur(ot, p.state === 'in') + '</b></div>' : '') +
                '<div class="lpd-pc-f"><div class="lpd-pc-chips">' + chips + '</div>' +
                    '<button type="button" class="lpd-pc-all" data-ps-open="' + p.id + '" title="View all punches" aria-label="View all punches">' + icon('list-tree') + (n ? '<i>' + n + '</i>' : '') + '</button></div>' +
            '</div></article>';
    }

    function openPunches(id) {
        var p = (L.people || []).filter(function (x) { return x.id === id; })[0], body = $('[data-ps="body"]');
        if (!p || !body || typeof bootstrap === 'undefined') return;
        var now = nowSec(), s = shiftOf(p.shift), n = p.punches.length, rows = '';
        p.punches.forEach(function (t, j) {
            var isIn = j % 2 === 0, next = p.punches[j + 1], last = j === n - 1, gap;
            if (isIn) gap = '<span class="lpd-tl-gap lpd-tl-gap--in">' + (next ? 'Inside for ' + dur(next - t) : 'Inside now · ' + dur(now - t, true)) + '</span>';
            else if (next) gap = '<span class="lpd-tl-gap">Break · ' + dur(next - t) + '</span>';
            else gap = '<span class="lpd-tl-gap lpd-tl-gap--out">Left the library</span>';
            rows += '<div class="lpd-tl' + (last ? ' is-last' : '') + '"><span class="lpd-tl-dot ' + (isIn ? 'is-in' : 'is-out') + '">' + icon(isIn ? 'log-in' : 'log-out') + '</span>' +
                '<div class="lpd-tl-t"><div><b class="' + (isIn ? 'lpd-ok' : 'lpd-bad') + '">' + (isIn ? 'IN' : 'OUT') + ' punch #' + (j + 1) + '</b><strong>' + tm(t) + '</strong></div>' + gap + '</div></div>';
        });
        body.innerHTML = '<div class="lpd-ps-h lpd-ps-h--' + STATE[p.state].cls + '">' + face(p) + '<span><b>' + esc(p.name) + '</b><small><strong>' + esc(p.seat) + '</strong> · ' + esc(s.name) + ' · ' + esc(s.time) + '</small></span>' +
            '<button type="button" data-bs-dismiss="modal" aria-label="Close">' + icon('x') + '</button></div>' +
            '<div class="lpd-ps-stats"><div><b>' + n + '</b><small>Punches</small></div><div><b>' + (n ? dur(insideSec(p, now)) : '–') + '</b><small>Time inside</small></div><div><b>' + (breakSec(p) ? dur(breakSec(p)) : '–') + '</b><small>Breaks</small></div></div>' +
            '<div class="lpd-ps-b">' + (rows || '<div class="lp-empty"><span>' + icon('clock') + '</span><b>No punches yet today</b>' + esc(p.name) + ' has not checked in.</div>') + '</div>' +
            '<div class="lpd-ps-f"><a href="' + esc(p.url) + '" class="lp-btn lp-btn--ghost lp-btn--sm">' + icon('calendar-days') + 'Attendance history</a></div>';
        bootstrap.Modal.getOrCreateInstance(document.getElementById('lpdPunches')).show();
    }

    if (L) {
        setLive(L);
        renderLiveCard();
        // The attendance list lives in the "Live Attendance" tab; it only re-renders while that tab is shown.
        var drawer = document.getElementById('lpdPresent');
        var drawerOpen = !!drawer && !drawer.closest('[hidden]');
        if (drawerOpen) renderDrawer();
        document.addEventListener('lpd:tab', function (e) {
            drawerOpen = !!drawer && e.detail === 'attendance';
            if (drawerOpen) { renderDrawer(); renderLiveCard(); }
        });
        if (drawer) {
            drawer.addEventListener('click', function (e) {
                var t = e.target.closest('[data-pv-tab],[data-ps-open]');
                if (!t) return;
                if (t.dataset.pvTab) pv.tab = t.dataset.pvTab;
                else return openPunches(Number(t.dataset.psOpen));
                renderDrawer();
            });
            var qi = $('[data-pv="q"]', drawer);
            if (qi) qi.addEventListener('input', function () { pv.q = qi.value; renderDrawer(); });
        }
        // Tick: drawer every second, card every 15s.
        var tick = 0;
        setInterval(function () {
            if (document.hidden) return;
            tick++;
            if (drawerOpen) renderDrawer();
            if (drawerOpen || tick % 15 === 0) renderLiveCard();
        }, 1000);
        // Refresh from the server every minute.
        if (D.liveUrl && window.fetch) {
            setInterval(function () {
                if (document.hidden) return;
                fetch(D.liveUrl, { credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(function (r) { return r.ok ? r.json() : null; })
                    .then(function (d) { if (d && d.people) { setLive(d); renderLiveCard(); if (drawerOpen) renderDrawer(); } })
                    .catch(function () {});
            }, 60000);
        }
    }

    /* =====================================================================
       Collection summary drawer
       ===================================================================== */
    var C = D.collections, cfs = { view: 'today', mode: 'All', q: '' };
    var MODE_IC = { All: 'layers', Cash: 'banknote', UPI: 'smartphone', Online: 'credit-card', Other: 'wallet' };

    function dayLabel(dateStr, d) {
        var dt = new Date(dateStr + 'T00:00:00'), lbl = dt.toLocaleDateString('en-IN', { weekday: 'short', day: 'numeric', month: 'short' });
        return d === 0 ? 'Today · ' + lbl : d === 1 ? 'Yesterday · ' + lbl : lbl;
    }

    function renderCollect() {
        var root = document.getElementById('lpdCollect');
        if (!root || !C) return;
        var base = C.items.filter(function (x) { return cfs.view === 'today' ? x.d === 0 : true; });
        var sum = function (a) { return a.reduce(function (n, x) { return n + x.amt; }, 0); };
        var tot = sum(base);

        $$('[data-fs-view]', root).forEach(function (b) { b.classList.toggle('is-on', b.dataset.fsView === cfs.view); });
        $('[data-fs="range"]', root).textContent = cfs.view === 'today'
            ? 'Collected today · ' + new Date(D.live ? D.live.now * 1000 : Date.now()).toLocaleDateString('en-IN', { weekday: 'short', day: 'numeric', month: 'short' })
            : 'Collected in the last ' + C.period.days + ' days · ' + C.period.from + ' – ' + C.period.to;
        $('[data-fs="total"]', root).textContent = inr(tot);
        var third = cfs.view === 'today' ? [base[0] ? base[0].time : '–', 'Last payment'] : [inr(Math.round(tot / C.period.days)), 'Avg per day'];
        $('[data-fs="stats"]', root).innerHTML = [[String(base.length), 'Payments'], [inr(Math.round(tot / Math.max(1, base.length))), 'Avg payment'], third].map(function (s) {
            return '<div><b>' + esc(s[0]) + '</b><small>' + s[1] + '</small></div>';
        }).join('');

        var modes = ['All'].concat(['Cash', 'UPI', 'Online', 'Other'].filter(function (m) { return base.some(function (x) { return x.mode === m; }); }));
        if (modes.indexOf(cfs.mode) < 0) cfs.mode = 'All';
        $('[data-fs="modes"]', root).innerHTML = modes.map(function (m) {
            return '<button type="button" data-fs-mode="' + m + '" class="lpd-chip-btn' + (cfs.mode === m ? ' is-on' : '') + '">' + icon(MODE_IC[m]) + m + '<b>' + short(sum(m === 'All' ? base : base.filter(function (x) { return x.mode === m; }))) + '</b></button>';
        }).join('');

        var q = cfs.q.trim().toLowerCase();
        var shown = base.filter(function (x) { return (cfs.mode === 'All' || x.mode === cfs.mode) && (!q || (x.name + ' ' + x.seat + ' ' + x.ref).toLowerCase().indexOf(q) > -1); });
        var groups = [];
        shown.forEach(function (x) { var g = groups[groups.length - 1]; if (!g || g.date !== x.date) groups.push(g = { date: x.date, d: x.d, list: [] }); g.list.push(x); });
        $('[data-fs="list"]', root).innerHTML = groups.map(function (g) {
            return '<div class="lpd-fs-g"><div class="lpd-fs-gh"><b>' + esc(dayLabel(g.date, g.d)) + '</b><small>' + g.list.length + (g.list.length === 1 ? ' payment' : ' payments') + ' · <strong>' + inr(sum(g.list)) + '</strong></small></div>' +
                g.list.map(function (x) {
                    return '<div class="lpd-fs-row">' + face(x) + '<span class="lpd-fs-t"><b>' + esc(x.name) + '</b><small>' + esc(x.seat) + ' · ' + esc(x.type) + ' · ' + esc(x.ref) + '</small></span>' +
                        '<span class="lpd-fs-a"><b>+' + inr(x.amt) + '</b><small>' + icon(MODE_IC[x.mode] || 'wallet') + esc(x.modeRaw) + (x.time ? ' · ' + esc(x.time) : '') + '</small></span></div>';
                }).join('') + '</div>';
        }).join('') || '<div class="lp-empty lpd-fill lpd-fill--sm"><span>' + icon('receipt-text') + '</span><b>' + (base.length ? 'No payments match' : cfs.view === 'today' ? 'No payments yet today' : 'No payments in this period') + '</b>' + (base.length ? 'Try another mode or search.' : 'Collected fees will show up here.') + '</div>';
    }

    if (C) {
        var cr = document.getElementById('lpdCollect');
        if (cr) {
            cr.addEventListener('show.bs.offcanvas', renderCollect);
            cr.addEventListener('click', function (e) {
                var t = e.target.closest('[data-fs-view],[data-fs-mode]');
                if (!t) return;
                if (t.dataset.fsView) { cfs.view = t.dataset.fsView; cfs.mode = 'All'; } else cfs.mode = t.dataset.fsMode;
                renderCollect();
            });
            var cq = $('[data-fs="q"]', cr);
            if (cq) cq.addEventListener('input', function () { cfs.q = cq.value; renderCollect(); });
        }
    }

    /* =====================================================================
       Fees collected (daily / weekly / monthly bars)
       ===================================================================== */
    var F = D.fees, fees = document.getElementById('lpdFees');
    function renderFees(mode) {
        if (!F || !fees) return;
        var rows = F[mode] || [], max = Math.max.apply(null, rows.map(function (r) { return r[1]; }).concat([1]));
        var total = rows.reduce(function (n, r) { return n + r[1]; }, 0);
        var done = rows.slice(0, -1), last = done[done.length - 1], early = done.slice(0, -1);
        var avg = early.length ? early.reduce(function (n, r) { return n + r[1]; }, 0) / early.length : 0;
        var pct = avg > 0 && last ? Math.round((last[1] - avg) / avg * 100) : null;
        var unit = { Daily: 'yesterday', Weekly: 'last week', Monthly: 'last month' }[mode];

        $$('[data-fees]', fees).forEach(function (b) { b.classList.toggle('is-on', b.dataset.fees === mode); });
        $('[data-fees-total]', fees).textContent = inr(total);
        var dEl = $('[data-fees-delta]', fees);
        dEl.textContent = pct === null ? 'No earlier data' : last[1] === 0 ? 'Nothing collected ' + unit : (pct >= 0 ? '+' : '') + pct + '% ' + unit + ' vs avg';
        dEl.className = 'lpd-delta ' + (pct === null ? 'is-flat' : pct >= 0 ? 'is-up' : 'is-down');
        $('[data-fees-sub]', fees).textContent = { Daily: 'last 7 days', Weekly: 'last 4 weeks', Monthly: 'last 6 months' }[mode];
        $('[data-fees-bars]', fees).innerHTML = total === 0
            ? '<div class="lp-empty lpd-fill lpd-fill--sm"><span>' + icon('chart-column') + '</span><b>No fees collected yet</b>Collections in this period will show up here.</div>'
            : rows.map(function (r, i) {
                var isLast = i === rows.length - 1;
                return '<div class="lpd-fb"><span>' + short(r[1]) + (isLast && r[1] ? ' so far' : '') + '</span><i class="' + (isLast ? 'is-now' : '') + '" style="height:' + Math.max(6, Math.round(r[1] / max * 110)) + 'px"></i><small>' + esc(r[0]) + '</small></div>';
            }).join('');
    }
    if (F && fees) {
        renderFees('Daily');
        fees.addEventListener('click', function (e) { var b = e.target.closest('[data-fees]'); if (b) renderFees(b.dataset.fees); });
    }

    /* =====================================================================
       Highlights carousel
       ===================================================================== */
    var hl = document.getElementById('lpdHl');
    if (hl) {
        var track = $('.lpd-hl-track', hl), n = Number(hl.dataset.count) || 1, at = 0, hold = false, x0 = null;
        var go = function (i) {
            at = (i + n) % n;
            track.style.transform = 'translateX(-' + at * 100 + '%)';
            $$('.lpd-hl-dots button', hl).forEach(function (b, k) { b.classList.toggle('is-on', k === at); });
        };
        hl.addEventListener('click', function (e) {
            var b = e.target.closest('[data-go],[data-step]');
            if (!b) return;
            go(b.dataset.go != null ? Number(b.dataset.go) : at + Number(b.dataset.step));
        });
        hl.addEventListener('mouseenter', function () { hold = true; });
        hl.addEventListener('mouseleave', function () { hold = false; });
        hl.addEventListener('touchstart', function (e) { x0 = e.touches[0].clientX; hold = true; }, { passive: true });
        hl.addEventListener('touchend', function (e) {
            hold = false;
            if (x0 === null) return;
            var dx = e.changedTouches[0].clientX - x0; x0 = null;
            if (Math.abs(dx) > 40) go(at + (dx < 0 ? 1 : -1));
        });
        var still = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (n > 1 && !still) setInterval(function () { if (!hold && !document.hidden) go(at + 1); }, 5500);
    }
})();
