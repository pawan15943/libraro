@extends('layouts.library')

@section('title', 'Order Board')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-pages.css') }}?v={{ @filemtime(public_path('css/lb-pages.css')) }}">
<link rel="stylesheet" href="{{ asset('public/css/lb-ordering.css') }}?v={{ @filemtime(public_path('css/lb-ordering.css')) }}">
@endpush

@section('content')
<div class="lpg od zb" data-zb>
    {{-- sound is blocked until the first tap on the page (browser rule): say it plainly --}}
    <button type="button" class="zb-sound" data-zb-unlock hidden>
        <x-lb-icon name="bell-ring" />
        <span><b>Order sound is off</b><small>Tap here once to hear new orders</small></span>
        <em>Turn on</em>
    </button>

    <div class="zb-head">
        <div class="zb-views" role="tablist" aria-label="Board">
            <button type="button" class="zb-view is-on" data-zb-view="live">Live orders <em data-zb-n="live">0</em></button>
            <button type="button" class="zb-view" data-zb-view="payments">Payments <em data-zb-n="payments">0</em></button>
            <button type="button" class="zb-view" data-zb-view="done">Done today <em data-zb-n="done">0</em></button>
        </div>
        <div class="zb-tools">
            <span class="zb-live" data-zb-live><i></i>Live</span>
            <button type="button" class="zb-mute" data-zb-mute title="Sound on / off"><x-lb-icon name="bell-ring" /><span>Sound on</span></button>
            @if (Route::has('ordering.items') && Gate::allows('has-permission', ['Ordering Setup']))
                <a class="zb-mute" href="{{ route('ordering.items') }}"><x-lb-icon name="list" /><span>Menu</span></a>
            @endif
        </div>
    </div>

    @unless ($settings->is_enabled)
        <a class="od-note" href="{{ Route::has('ordering.settings') ? route('ordering.settings') : '#' }}">
            <x-lb-icon name="info" /><span><b>Ordering is off for learners.</b> Switch it on in Order Settings.</span><x-lb-icon name="chevron-right" />
        </a>
    @endunless

    <div class="zb-attn" data-zb-attn hidden role="alert"><x-lb-icon name="clock-alert" /><span><b data-zb-attn-n>0</b> order waiting too long — accept or reject now</span></div>

    {{-- phones: one column at a time --}}
    <div class="zb-seg" role="tablist" aria-label="Order stage" data-zb-seg>
        <button type="button" class="is-on" data-zb-col-btn="new">New <em data-zb-n="new">0</em></button>
        <button type="button" data-zb-col-btn="kitchen">Preparing <em data-zb-n="kitchen">0</em></button>
        <button type="button" data-zb-col-btn="handover">Ready <em data-zb-n="handover">0</em></button>
    </div>

    <div class="zb-board" data-zb-board>
        <section class="zb-col is-show" data-zb-col="new"><h2><span class="zb-dot is-new"></span>New <em data-zb-n="new">0</em></h2><div class="zb-list" data-zb-list="new"></div></section>
        <section class="zb-col" data-zb-col="kitchen"><h2><span class="zb-dot is-kitchen"></span>Preparing <em data-zb-n="kitchen">0</em></h2><div class="zb-list" data-zb-list="kitchen"></div></section>
        <section class="zb-col" data-zb-col="handover"><h2><span class="zb-dot is-ready"></span>Ready / on the way <em data-zb-n="handover">0</em></h2><div class="zb-list" data-zb-list="handover"></div></section>
    </div>
    <div class="zb-flat" data-zb-flat hidden></div>
</div>

<script>
(function () {
    var FEED = @json(route('orders.feed')), ACT = @json(url('library/orders')), CSRF = @json(csrf_token());
    var ICON = @json(asset('public/img/lb-icons.svg') . '?v=' . @filemtime(public_path('img/lb-icons.svg')));
    var SOUND_DEFAULT = @json((bool) $settings->sound_enabled), ESCALATE = {{ (int) $settings->escalation_minutes }};
    var EMPTY = { new: ['coffee', 'No new orders', 'New orders ring and show here.'], kitchen: ['flame', 'Nothing cooking', 'Accepted orders come here.'],
        handover: ['hand-coins', 'Nothing to hand over', 'Ready orders come here.'], payments: ['wallet', 'No payments to check', 'UPI screenshots and dues show here.'], done: ['circle-check', 'Nothing finished yet today', ''] };
    var doc = document, $ = function (s, r) { return (r || doc).querySelector(s); }, $$ = function (s, r) { return Array.prototype.slice.call((r || doc).querySelectorAll(s)); };
    var data = [], seen = null, view = 'live', col = 'new', busy = {}, baseTitle = doc.title;
    var muted = !SOUND_DEFAULT; try { if (localStorage.getItem('zb-muted') !== null) muted = localStorage.getItem('zb-muted') === '1'; } catch (e) {}

    function icon(n) { return '<svg class="lb-i" aria-hidden="true"><use href="' + ICON + '#i-' + n + '"></use></svg>'; }
    function esc(s) { var d = doc.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
    function inr(n) { return '₹' + (Math.round(n * 100) / 100).toLocaleString('en-IN'); }
    // short age: 12 min, 2 h 5 m, 1 d 13 h
    function ago(m) { return m < 1 ? 'Just now' : m < 60 ? m + ' min' : m < 1440 ? Math.floor(m / 60) + ' h ' + (m % 60) + ' m' : Math.floor(m / 1440) + ' d ' + Math.floor((m % 1440) / 60) + ' h'; }
    function placed(iso) { try { var d = new Date(iso); return d.toLocaleString('en-IN', { day: 'numeric', month: 'short', hour: 'numeric', minute: '2-digit' }); } catch (e) { return ''; } }

    // ================= sound =================
    // Browsers only allow sound after the person taps the page once. Until then a yellow bar asks for that tap.
    var ctx = null;
    function audioOk() { return ctx && ctx.state === 'running'; }
    function unlock() {
        try { ctx = ctx || new (window.AudioContext || window.webkitAudioContext)(); if (ctx.state !== 'running') ctx.resume().then(paintSound); } catch (e) {}
        if (window.Notification && Notification.permission === 'default') { try { Notification.requestPermission(); } catch (e) {} }
        setTimeout(paintSound, 150);
    }
    function ring(times) {
        if (muted || !audioOk()) return;
        var t0 = ctx.currentTime;
        for (var r = 0; r < (times || 3); r++) {
            [[988, 0], [1319, 0.16], [1568, 0.32]].forEach(function (n) {
                var o = ctx.createOscillator(), g = ctx.createGain(), t = t0 + r * 0.9 + n[1];
                o.type = 'triangle'; o.frequency.value = n[0];
                g.gain.setValueAtTime(0.0001, t); g.gain.exponentialRampToValueAtTime(0.8, t + 0.02); g.gain.exponentialRampToValueAtTime(0.0001, t + 0.45);
                o.connect(g); g.connect(ctx.destination); o.start(t); o.stop(t + 0.5);
            });
        }
    }
    function paintSound() {
        $('[data-zb-unlock]').hidden = muted || audioOk();
        var m = $('[data-zb-mute]');
        m.classList.toggle('is-off', muted);
        m.querySelector('span').textContent = muted ? 'Sound off' : 'Sound on';
    }
    $('[data-zb-unlock]').addEventListener('click', function () { unlock(); setTimeout(function () { ring(1); }, 250); });
    $('[data-zb-mute]').addEventListener('click', function () {
        muted = !muted; try { localStorage.setItem('zb-muted', muted ? '1' : '0'); } catch (e) {}
        if (!muted) { unlock(); setTimeout(function () { ring(1); }, 250); }
        paintSound();
    });
    doc.addEventListener('pointerdown', function once() { unlock(); doc.removeEventListener('pointerdown', once); });

    function notify(o) {
        try {
            if (window.Notification && Notification.permission === 'granted') {
                var n = new Notification('New order #' + o.short + ' · ' + inr(o.total), { body: o.where + ' · ' + o.items.map(function (i) { return i.q + '× ' + i.n; }).join(', '), tag: 'order-' + o.id, requireInteraction: true });
                n.onclick = function () { window.focus(); n.close(); };
            }
        } catch (e) {}
    }
    function waiting() { return data.filter(function (o) { return o.status === 'new' || o.status === 'attention'; }).length; }

    // keeps ringing (every 20 s) while an order still waits for someone to open / accept it
    setInterval(function () { if (waiting()) ring(2); }, 20000);
    // title blinks while orders wait, so a tab in the background still shows it
    var blink = false;
    setInterval(function () {
        var n = waiting(); blink = !blink;
        doc.title = n ? (blink ? '🔔 ' + n + ' NEW ORDER' + (n > 1 ? 'S' : '') : '(' + n + ') ' + baseTitle) : baseTitle;
    }, 1000);

    // ================= cards =================
    var PRIMARY = { accept: 'Accept order', prepare: 'Start preparing', ready: 'Mark ready', dispatch: 'Out for delivery', deliver: 'Delivered', collect: 'Collected', complete: 'Close order' };
    var GO_ICON = { accept: 'check', prepare: 'flame', ready: 'bell-ring', dispatch: 'navigation', deliver: 'circle-check', collect: 'hand-coins', complete: 'badge-check' };
    function card(o) {
        var modeIc = o.mode === 'seat' ? 'armchair' : o.mode === 'table' ? 'table' : 'hand-coins';
        var late = (o.status === 'new' || o.status === 'attention' || o.status === 'under_review') && o.mins >= ESCALATE;
        var main = o.actions.filter(function (a) { return a.tone === 'go'; })[0];
        var minor = o.actions.filter(function (a) { return a !== main && a.key !== 'undo'; });
        var stage = { new: 'is-s-new', kitchen: 'is-s-kitchen', handover: 'is-s-ready' }[o.tab] || 'is-s-done';
        var stageTxt = o.status === 'attention' ? 'Waiting too long' : o.status === 'under_review' ? 'Opened' : o.status_label;
        var pay = o.pay_status === 'paid' ? ['is-ok', 'circle-check', 'Paid']
            : o.pay_status === 'processing' ? ['is-warn', 'image', 'Check UPI screenshot']
            : o.pay_status === 'failed' ? ['is-bad', 'circle-alert', 'UPI not received']
            : o.pay_status === 'refund_pending' ? ['is-bad', 'rotate-ccw', 'Refund due']
            : o.pay_method === 'online' ? ['is-warn', 'qr-code', 'UPI · not paid yet']
            : o.pay_method === 'pay_later' ? ['', 'calendar-clock', 'Pay later']
            : ['', 'banknote', 'Collect ' + inr(o.due) + ' at handover'];
        var count = o.items.reduce(function (s, i) { return s + i.q; }, 0);
        return '<article class="zc ' + stage + (shown && !shown[o.id] ? ' is-fresh' : '') + (o.status === 'new' ? ' is-new' : '') + (late ? ' is-late' : '') + '" data-id="' + o.id + '">' +
            '<div class="zc-top">' +
                '<div class="zc-id"><b class="zc-no">#' + esc(o.short) + '</b><span class="zc-stage">' + esc(stageTxt) + '</span></div>' +
                '<span class="zc-time' + (late ? ' is-late' : '') + '" title="Placed ' + esc(placed(o.at)) + '">' + icon('clock') + ago(o.mins) + '</span>' +
            '</div>' +
            '<div class="zc-where">' +
                '<span class="zc-where-ic">' + icon(modeIc) + '</span>' +
                '<span class="zc-where-t"><b>' + esc(o.where) + '</b><small>' + icon('user-round') + esc(o.learner) + (o.learner_no ? ' · ' + esc(o.learner_no) : '') + '</small></span>' +
            '</div>' +
            (o.waiting ? '<p class="zc-flag is-wait">' + icon('hourglass') + 'Waiting for the learner to choose a replacement</p>' : '') +
            (o.status === 'delivery_attempted' ? '<p class="zc-flag">' + icon('circle-alert') + 'Learner was not at the seat</p>' : '') +
            '<div class="zc-items"><small>' + count + ' ' + (count === 1 ? 'item' : 'items') + '</small><ul>' + o.items.map(function (i) {
                return '<li><b>' + i.q + '×</b><span>' + esc(i.n) + (i.f ? ' ' + icon('file-text') : '') + (i.s === 'unavailable' || i.s === 'replacement_pending' ? ' <em class="zc-out">ran out</em>' : '') + '</span></li>';
            }).join('') + '</ul></div>' +
            (o.note ? '<p class="zc-note">' + icon('message-square') + esc(o.note) + '</p>' : '') +
            '<div class="zc-bill"><span class="zc-pay ' + pay[0] + '">' + icon(pay[1]) + esc(pay[2]) + '</span><b>' + inr(o.total) + '</b></div>' +
            (main ? '<button type="button" class="zc-go" data-act="' + main.key + '">' + icon(GO_ICON[main.key] || 'check') + '<span>' + esc(PRIMARY[main.key] || main.label) + '</span></button>' : '') +
            '<div class="zc-more">' + minor.map(function (a) { return '<button type="button" class="' + (a.tone === 'bad' ? 'is-bad' : '') + '" data-act="' + a.key + '">' + esc({ cancel: 'Cancel', reject: 'Reject', attempted: 'Not at seat' }[a.key] || a.label) + '</button>'; }).join('') +
            '<a href="' + o.url + '">Details ' + icon('chevron-right') + '</a></div>' +
        '</article>';
    }
    function empty(k) { var e = EMPTY[k]; return '<div class="zb-empty">' + icon(e[0]) + '<b>' + e[1] + '</b>' + (e[2] ? '<small>' + e[2] + '</small>' : '') + '</div>'; }
    function live(o) { return ['completed', 'rejected', 'cancelled'].indexOf(o.status) === -1; }
    var shown = null, lastJson = '';
    function paint() {
        var groups = { new: [], kitchen: [], handover: [] };
        data.forEach(function (o) { if (groups[o.tab] && live(o)) groups[o.tab].push(o); });
        groups.new.sort(function (a, b) { return (b.status === 'attention') - (a.status === 'attention') || a.id - b.id; });
        Object.keys(groups).forEach(function (k) {
            $('[data-zb-list="' + k + '"]').innerHTML = groups[k].length ? groups[k].map(card).join('') : empty(k);
            $$('[data-zb-n="' + k + '"]').forEach(function (el) { el.textContent = groups[k].length; el.classList.toggle('is-hot', k === 'new' && groups[k].length > 0); });
        });
        var pay = data.filter(function (o) { return o.money; }), done = data.filter(function (o) { return o.done_today; });
        $$('[data-zb-n="live"]').forEach(function (el) { el.textContent = groups.new.length + groups.kitchen.length + groups.handover.length; });
        $$('[data-zb-n="payments"]').forEach(function (el) { el.textContent = pay.length; el.classList.toggle('is-warn', pay.length > 0); });
        $$('[data-zb-n="done"]').forEach(function (el) { el.textContent = done.length; });
        var flat = $('[data-zb-flat]');
        flat.innerHTML = view === 'payments' ? (pay.length ? pay.map(card).join('') : empty('payments')) : view === 'done' ? (done.length ? done.map(card).join('') : empty('done')) : '';
        flat.hidden = view === 'live';
        $('[data-zb-board]').hidden = view !== 'live';
        $('[data-zb-seg]').hidden = view !== 'live';
        shown = shown || {}; data.forEach(function (o) { shown[o.id] = 1; });
    }

    // ================= poll (also while the tab is in the background, so the bell rings) =================
    function load() {
        fetch(FEED, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { if (!r.ok) throw r; return r.json(); })
            .then(function (j) {
                $('[data-zb-live]').classList.remove('is-off');
                var fresh = j.orders.filter(function (o) { return o.status === 'new' && seen && !seen[o.id]; });
                if (fresh.length) { ring(3); fresh.forEach(notify); }
                seen = seen || {}; j.orders.forEach(function (o) { seen[o.id] = 1; });
                var json = JSON.stringify(j.orders);
                data = j.orders;
                $('[data-zb-attn]').hidden = !j.attention; $('[data-zb-attn-n]').textContent = j.attention;
                if (json !== lastJson) { lastJson = json; paint(); } // repaint only when something changed (no flicker)
            })
            .catch(function () { $('[data-zb-live]').classList.add('is-off'); });
    }
    setInterval(load, 5000);
    doc.addEventListener('visibilitychange', function () { if (!doc.hidden) load(); });

    // ================= actions =================
    doc.addEventListener('click', function (e) {
        var b = e.target.closest('[data-zb] [data-act]'); if (!b) return;
        var id = +b.closest('[data-id]').getAttribute('data-id'), o = data.filter(function (x) { return x.id === id; })[0];
        if (!o || busy[id]) return;
        var action = b.getAttribute('data-act'), reason = null;
        if (action === 'reject' || action === 'cancel') {
            reason = window.prompt((action === 'reject' ? 'Reject' : 'Cancel') + ' order #' + o.short + '? Write the reason — the learner will see it.', '');
            if (reason === null) return;
        }
        busy[id] = true; b.disabled = true; b.classList.add('is-busy');
        var fd = new FormData(); fd.append('_token', CSRF); fd.append('action', action); fd.append('v', o.v); if (reason !== null) fd.append('reason', reason);
        fetch(ACT + '/' + id + '/act', { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
            .then(function (res) { if (window.toastr) toastr[res.ok ? 'success' : 'warning'](res.j.message || 'Done'); else if (!res.ok) alert(res.j.message); })
            .catch(function () { if (window.toastr) toastr.error('No connection. Try again.'); })
            .finally(function () { delete busy[id]; load(); });
    });
    $$('[data-zb-view]').forEach(function (b) { b.addEventListener('click', function () { view = b.getAttribute('data-zb-view'); $$('[data-zb-view]').forEach(function (x) { x.classList.toggle('is-on', x === b); }); paint(); }); });
    $$('[data-zb-col-btn]').forEach(function (b) {
        b.addEventListener('click', function () {
            col = b.getAttribute('data-zb-col-btn');
            $$('[data-zb-col-btn]').forEach(function (x) { x.classList.toggle('is-on', x === b); });
            $$('[data-zb-col]').forEach(function (c) { c.classList.toggle('is-show', c.getAttribute('data-zb-col') === col); });
        });
    });

    paintSound();
    load();
})();
</script>
@endsection
