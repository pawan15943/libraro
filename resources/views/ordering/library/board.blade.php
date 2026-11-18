@extends('layouts.library')

@section('title', 'Order Board')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-pages.css') }}?v={{ @filemtime(public_path('css/lb-pages.css')) }}">
<link rel="stylesheet" href="{{ asset('public/css/lb-ordering.css') }}?v={{ @filemtime(public_path('css/lb-ordering.css')) }}">
@endpush

@section('content')
<div class="lpg od ob" data-ob>
    <header class="lpg-head">
        <div class="lpg-head-t">
            <p>New orders appear here by themselves. Accept, prepare and hand over in a tap — the learner sees every step live.</p>
        </div>
        <div class="lpg-head-r">
            <button type="button" class="lp-btn lp-btn--ghost" data-ob-alerts><x-lb-icon name="bell-ring" /><span>Turn on alerts</span></button>
            @if (Route::has('ordering.items') && Gate::allows('has-permission', ['Ordering Setup']))
                <a class="lp-btn lp-btn--ghost" href="{{ route('ordering.items') }}"><x-lb-icon name="list" />Menu & Items</a>
            @endif
        </div>
    </header>

    @unless ($settings->is_enabled)
        <a class="od-note" href="{{ Route::has('ordering.settings') ? route('ordering.settings') : '#' }}">
            <x-lb-icon name="info" /><span><b>Ordering is off for learners.</b> Old orders still show here. Switch it on in Order Settings.</span><x-lb-icon name="chevron-right" />
        </a>
    @endunless

    <div class="ob-attn" data-ob-attn hidden role="alert">
        <x-lb-icon name="clock-alert" /><span><b data-ob-attn-n>0</b> waiting too long — please open them now.</span>
    </div>

    <div class="ob-tabs" role="tablist" aria-label="Order queues">
        @foreach ($tabs as $key => $label)
            <button type="button" role="tab" class="ob-tab {{ $loop->first ? 'is-on' : '' }}" data-ob-tab="{{ $key }}" aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                {{ $label }} <em data-ob-count="{{ $key }}">0</em>
            </button>
        @endforeach
        <span class="ob-live" data-ob-live title="Updates every few seconds"><i></i>Live</span>
    </div>

    <div class="ob-grid" data-ob-list aria-live="polite"></div>
    <div class="lpg-empty ob-empty" data-ob-empty hidden>
        <span><x-lb-icon name="coffee" /></span>
        <b data-ob-empty-t>No new orders</b>
        <p>Keep this page open — new orders pop up with a sound.</p>
    </div>
</div>
<script>
(function () {
    var FEED = @json(route('orders.feed')), CSRF = @json(csrf_token()), SOUND = @json((bool) $settings->sound_enabled);
    var ICON = @json(asset('public/img/lb-icons.svg') . '?v=' . @filemtime(public_path('img/lb-icons.svg')));
    var EMPTY = { new: 'No new orders', kitchen: 'Nothing being prepared', handover: 'Nothing waiting to be handed over', payments: 'No payments to check', done: 'No finished orders today' };
    var doc = document, $ = function (s, r) { return (r || doc).querySelector(s); }, $$ = function (s, r) { return Array.prototype.slice.call((r || doc).querySelectorAll(s)); };
    var list = $('[data-ob-list]'), tab = 'new', data = [], seen = null, baseTitle = doc.title, timer = null, busy = {};
    try { tab = sessionStorage.getItem('ob-tab') || 'new'; } catch (e) {}

    function icon(n) { return '<svg class="lb-i" aria-hidden="true"><use href="' + ICON + '#i-' + n + '"></use></svg>'; }
    function esc(s) { var d = doc.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
    function inr(n) { return '₹' + (Math.round(n * 100) / 100).toLocaleString('en-IN'); }
    function ago(m) { return m < 1 ? 'just now' : m < 60 ? m + ' min' : Math.floor(m / 60) + ' h ' + (m % 60) + ' min'; }

    // ---- alerts: a short chime (no file needed) + system notification
    var ctx = null;
    function chime() {
        if (!SOUND) return;
        try {
            ctx = ctx || new (window.AudioContext || window.webkitAudioContext)();
            [880, 1320].forEach(function (f, i) {
                var o = ctx.createOscillator(), g = ctx.createGain(), t = ctx.currentTime + i * 0.18;
                o.frequency.value = f; o.type = 'sine';
                g.gain.setValueAtTime(0.0001, t); g.gain.exponentialRampToValueAtTime(0.35, t + 0.02); g.gain.exponentialRampToValueAtTime(0.0001, t + 0.35);
                o.connect(g); g.connect(ctx.destination); o.start(t); o.stop(t + 0.4);
            });
        } catch (e) {}
    }
    function notify(o) {
        try {
            if (window.Notification && Notification.permission === 'granted') {
                var n = new Notification('New order #' + o.short + ' · ' + inr(o.total), { body: o.learner + ' · ' + o.where + '\n' + o.items.map(function (i) { return i.q + ' × ' + i.n; }).join(', '), tag: 'order-' + o.id });
                n.onclick = function () { window.focus(); location.href = o.url; };
            }
        } catch (e) {}
    }
    var alertBtn = $('[data-ob-alerts]');
    function paintAlertBtn() {
        var on = (!window.Notification || Notification.permission === 'granted') && ctx && ctx.state === 'running';
        alertBtn.classList.toggle('is-on', !!on);
        alertBtn.querySelector('span').textContent = on ? 'Alerts on' : 'Turn on alerts';
    }
    alertBtn.addEventListener('click', function () {
        try { ctx = ctx || new (window.AudioContext || window.webkitAudioContext)(); ctx.resume(); } catch (e) {}
        if (window.Notification && Notification.permission === 'default') Notification.requestPermission().then(paintAlertBtn);
        chime(); setTimeout(paintAlertBtn, 300);
    });
    // browsers allow sound only after the first click anywhere on the page
    doc.addEventListener('click', function once() { try { ctx = ctx || new (window.AudioContext || window.webkitAudioContext)(); ctx.resume(); } catch (e) {} paintAlertBtn(); doc.removeEventListener('click', once); });

    // ---- render
    function inTab(o) { return tab === 'payments' ? o.money : tab === 'done' ? o.done_today : (o.tab === tab && ['completed', 'rejected', 'cancelled'].indexOf(o.status) === -1); }
    function card(o) {
        var tone = o.status === 'attention' ? 'is-bad' : o.status === 'new' ? 'is-new' : '';
        var modeIc = o.mode === 'seat' ? 'armchair' : o.mode === 'table' ? 'table' : 'hand-coins';
        var payTone = o.pay_status === 'paid' ? 'is-ok' : (o.pay_status === 'processing' || o.pay_status === 'refund_pending') ? 'is-warn' : '';
        var payTxt = { online: 'UPI', offline: 'Counter', pay_later: 'Pay later' }[o.pay_method] + ' · ' + o.pay_label + (o.due > 0 && o.pay_status !== 'paid' ? ' ' + inr(o.due) : '');
        var acts = o.actions.filter(function (a) { return a.key !== 'undo'; }).map(function (a) {
            return '<button type="button" class="ob-act is-' + a.tone + '" data-act="' + a.key + '">' + esc(a.label) + '</button>';
        }).join('');
        return '<article class="ob-card ' + tone + '" data-id="' + o.id + '">' +
            '<div class="ob-head"><b class="ob-no">#' + esc(o.short) + '</b><span class="ob-age" title="' + esc(o.at) + '">' + ago(o.mins) + '</span><span class="lpg-tag ' + (o.status === 'attention' ? 'is-bad' : (o.status === 'ready' || o.status === 'out_for_delivery') ? 'is-ok' : 'is-acc') + '">' + esc(o.status_label) + '</span></div>' +
            '<div class="ob-who"><span class="ob-where">' + icon(modeIc) + esc(o.where) + '</span><span class="ob-name">' + esc(o.learner) + (o.learner_no ? ' · ' + esc(o.learner_no) : '') + '</span></div>' +
            '<ul class="ob-items">' + o.items.map(function (i) { return '<li><b>' + i.q + '×</b> ' + esc(i.n) + (i.f ? ' ' + icon('file-text') : '') + (i.s === 'unavailable' || i.s === 'replacement_pending' ? ' <em>waiting</em>' : '') + '</li>'; }).join('') + '</ul>' +
            (o.note ? '<p class="ob-note">' + icon('message-square') + esc(o.note) + '</p>' : '') +
            '<div class="ob-pay ' + payTone + '">' + icon('wallet') + '<span>' + esc(payTxt) + '</span><b>' + inr(o.total) + '</b></div>' +
            '<div class="ob-foot">' + acts + '<a class="ob-act is-ghost" href="' + o.url + '">Open</a></div>' +
        '</article>';
    }
    function paint() {
        var rows = data.filter(inTab);
        if (tab === 'new' || tab === 'kitchen' || tab === 'handover') rows.sort(function (a, b) { return (b.status === 'attention') - (a.status === 'attention') || a.id - b.id; });
        list.innerHTML = rows.map(card).join('');
        $('[data-ob-empty]').hidden = rows.length > 0;
        $('[data-ob-empty-t]').textContent = EMPTY[tab];
        $$('[data-ob-tab]').forEach(function (b) { var on = b.getAttribute('data-ob-tab') === tab; b.classList.toggle('is-on', on); b.setAttribute('aria-selected', on ? 'true' : 'false'); });
    }

    // ---- poll
    function load() {
        if (doc.hidden && seen !== null) return;
        fetch(FEED, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { if (!r.ok) throw r; return r.json(); })
            .then(function (j) {
                $('[data-ob-live]').classList.remove('is-off');
                var fresh = j.orders.filter(function (o) { return o.status === 'new' && seen && !seen[o.id]; });
                if (fresh.length) { chime(); fresh.forEach(notify); }
                seen = seen || {}; j.orders.forEach(function (o) { seen[o.id] = 1; });
                data = j.orders;
                Object.keys(j.counts).forEach(function (k) { var el = $('[data-ob-count="' + k + '"]'); if (el) { el.textContent = j.counts[k]; el.classList.toggle('is-hot', k === 'new' && j.counts[k] > 0); } });
                var waiting = j.counts.new;
                doc.title = (waiting ? '(' + waiting + ') ' : '') + baseTitle;
                $('[data-ob-attn]').hidden = !j.attention; $('[data-ob-attn-n]').textContent = j.attention;
                if (!list.contains(doc.activeElement)) paint();
            })
            .catch(function () { $('[data-ob-live]').classList.add('is-off'); });
    }
    function start() { clearInterval(timer); timer = setInterval(load, 5000); load(); }
    doc.addEventListener('visibilitychange', function () { if (!doc.hidden) load(); });

    // ---- actions
    list.addEventListener('click', function (e) {
        var b = e.target.closest('[data-act]'); if (!b) return;
        var cardEl = b.closest('[data-id]'), id = +cardEl.getAttribute('data-id'), o = data.filter(function (x) { return x.id === id; })[0];
        if (!o || busy[id]) return;
        var action = b.getAttribute('data-act'), reason = null;
        if (action === 'reject' || action === 'cancel') {
            reason = window.prompt(action === 'reject' ? 'Why are you rejecting order #' + o.short + '? The learner will see this.' : 'Why are you cancelling order #' + o.short + '? The learner will see this.', '');
            if (reason === null) return;
        }
        busy[id] = true; b.classList.add('is-busy');
        var fd = new FormData(); fd.append('_token', CSRF); fd.append('action', action); fd.append('v', o.v); if (reason !== null) fd.append('reason', reason);
        fetch(@json(url('library/orders')) + '/' + id + '/act', { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
            .then(function (res) { if (window.toastr) toastr[res.ok ? 'success' : 'warning'](res.j.message || 'Done'); else if (!res.ok) alert(res.j.message); })
            .catch(function () { if (window.toastr) toastr.error('No connection. Try again.'); })
            .finally(function () { delete busy[id]; load(); });
    });
    $$('[data-ob-tab]').forEach(function (b) { b.addEventListener('click', function () { tab = b.getAttribute('data-ob-tab'); try { sessionStorage.setItem('ob-tab', tab); } catch (e) {} paint(); }); });

    paintAlertBtn();
    start();
})();
</script>
@endsection
