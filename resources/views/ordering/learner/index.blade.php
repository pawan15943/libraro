@extends('layouts.learner')

@section('title', 'Order food & more')

@push('lr-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-learner-order.css') }}?v={{ @filemtime(public_path('css/lb-learner-order.css')) }}">
@endpush

@section('content')
@php
    use App\Models\Ordering\Order;
    $inr = fn ($n) => '₹' . number_format((float) $n, (float) $n == floor((float) $n) ? 0 : 2);
    $s = $ctx['settings'];
    $modeLabel = ['seat' => 'My seat', 'table' => 'Cafeteria table', 'counter' => 'Counter pickup'];
    $modeIcon = ['seat' => 'armchair', 'table' => 'table', 'counter' => 'hand-coins'];
    $payLabel = ['online' => ['Pay online', 'Scan UPI QR after placing', 'qr-code'], 'offline' => ['Pay at counter', 'Cash / UPI when you get it', 'banknote'], 'pay_later' => ['Pay later', 'Added to your dues', 'calendar-clock']];
    $trackTxt = [Order::NEW => 'Waiting for the library', Order::ATTENTION => 'Waiting for the library', Order::UNDER_REVIEW => 'Library is checking',
        Order::REPLACEMENT_PENDING => 'Item ran out — choose now', Order::CONFIRMED => 'Order accepted', Order::PREPARING => 'Preparing your order',
        Order::READY => 'Ready at the counter', Order::OUT_FOR_DELIVERY => 'On the way to you', Order::DELIVERY_ATTEMPTED => 'We came to your seat'];
@endphp

<div class="zo">
    @if (! $ctx['eligible'])
        <section class="zo-blocked">
            <span class="zo-blocked-ic"><x-lb-icon name="coffee" /></span>
            <h1>Ordering is not available</h1>
            <p>{{ $ctx['reason'] }}</p>
            @if (Route::has('learner.orders.history'))<a class="zo-btn is-ghost" href="{{ route('learner.orders.history') }}">My orders</a>@endif
        </section>
    @else
        {{-- where + eta --}}
        <div class="zo-top">
            <button type="button" class="zo-loc" data-zo-loc-open aria-haspopup="dialog">
                <x-lb-icon name="map-pin" />
                <span><small>Order for</small><b data-zo-loc-txt>{{ $ctx['seat'] ? 'Seat ' . $ctx['seat'] : 'Choose' }}</b></span>
                <x-lb-icon name="chevron-down" class="zo-loc-chev" />
            </button>
            <span class="zo-eta"><x-lb-icon name="timer" />{{ $s->prep_minutes }} min</span>
        </div>

        {{-- orders in progress --}}
        @foreach ($current as $o)
            <a class="zo-track {{ in_array($o->status, [Order::READY, Order::OUT_FOR_DELIVERY]) ? 'is-ready' : ($o->status === Order::REPLACEMENT_PENDING ? 'is-warn' : '') }}" href="{{ route('learner.orders.show', $o->order_no) }}">
                <span class="zo-track-ic"><x-lb-icon :name="in_array($o->status, [Order::READY, Order::OUT_FOR_DELIVERY]) ? 'bell-ring' : 'flame'" /></span>
                <span class="zo-track-t"><b>{{ $trackTxt[$o->status] ?? $o->statusLabel() }}</b><small>Order #{{ \Illuminate\Support\Str::afterLast($o->order_no, '-') }} · {{ $inr($o->total) }}</small></span>
                <em>Track<x-lb-icon name="chevron-right" /></em>
            </a>
        @endforeach

        @unless ($ctx['open'])
            <p class="zo-alert"><x-lb-icon name="clock-alert" />Ordering opens {{ $ctx['opens_at'] }} – {{ $ctx['closes_at'] }}. You can look at the menu now.</p>
        @endunless
        @if ($ctx['limit_reached'])
            <p class="zo-alert"><x-lb-icon name="layers" />{{ $ctx['open_orders'] }} orders are in progress. You can order again when one is done.</p>
        @endif

        @if ($menu->isEmpty())
            <section class="zo-blocked">
                <span class="zo-blocked-ic"><x-lb-icon name="coffee" /></span>
                <h1>The menu is empty</h1>
                <p>The library has not added anything to order yet.</p>
            </section>
        @else
            <div class="zo-sticky">
                <label class="zo-search"><x-lb-icon name="search" /><input type="search" placeholder="Search tea, pen, print…" data-lo-search aria-label="Search the menu"></label>
                <div class="zo-chips" role="tablist" aria-label="Categories">
                    <button type="button" class="is-on" data-lo-cat="all">All</button>
                    @foreach ($menu as $c)
                        <button type="button" data-lo-cat="{{ $c->id }}">{{ $c->name }}</button>
                    @endforeach
                </div>
            </div>

            @foreach ($menu as $c)
                <section class="zo-cat" data-lo-sec="{{ $c->id }}">
                    <h2>{{ $c->name }} <span>{{ $c->items->count() }}</span></h2>
                    @foreach ($c->items as $it)
                        <article class="zi {{ $it->is_available ? '' : 'is-na' }}" data-lo-item="{{ $it->id }}" data-lo-name="{{ mb_strtolower($it->name . ' ' . $it->description . ' ' . $c->name) }}">
                            <div class="zi-t">
                                @if ($it->is_featured)<span class="zi-pop"><x-lb-icon name="star" />Popular</span>@endif
                                <h3>{{ $it->name }}</h3>
                                <div class="zi-price">{{ $inr($it->price) }}@if ($it->price_unit)<small> / {{ $it->price_unit }}</small>@endif</div>
                                @if ($it->description)<p>{{ $it->description }}</p>@endif
                                @if ($it->needs_upload)<span class="zi-tag"><x-lb-icon name="file-up" />Attach your file in the cart</span>@endif
                                @unless ($it->is_available)<span class="zi-na">Currently unavailable</span>@endunless
                            </div>
                            <div class="zi-media {{ $it->imageUrl() ? '' : 'is-noimg' }}">
                                @if ($it->imageUrl())<div class="zi-img"><img src="{{ $it->imageUrl() }}" alt="" loading="lazy"></div>@endif
                                <div class="zi-add" data-lo-add="{{ $it->id }}"></div>
                            </div>
                        </article>
                    @endforeach
                </section>
            @endforeach
            <p class="zo-none" data-lo-noresult hidden>Nothing matches your search.</p>
        @endif
    @endif
</div>

@if ($ctx['eligible'] && $menu->isNotEmpty())
    {{-- cart bar --}}
    <button type="button" class="zo-bar" data-lo-cartbar hidden>
        <span class="zo-bar-t"><b data-lo-count>0 items</b><small data-lo-bar-total>₹0</small></span>
        <span class="zo-bar-go">View cart<x-lb-icon name="chevron-right" /></span>
    </button>

    {{-- where sheet --}}
    <div class="zo-sheet" data-zo-loc hidden role="dialog" aria-modal="true" aria-labelledby="zoLocTitle">
        <div class="zo-sheet-bg" data-zo-loc-close></div>
        <div class="zo-sheet-box is-small">
            <header class="zo-sheet-h"><h2 id="zoLocTitle">Where should we bring it?</h2><button type="button" class="zo-x" data-zo-loc-close aria-label="Close"><x-lb-icon name="x" /></button></header>
            <div class="zo-sheet-body">
                <div class="zo-modes" role="radiogroup" aria-label="Order for">
                    @foreach ($ctx['modes'] as $m)
                        <label class="zo-mode">
                            <input type="radio" name="lo_mode" value="{{ $m }}" data-lo-mode>
                            <span><x-lb-icon :name="$modeIcon[$m]" /><b>{{ $modeLabel[$m] }}</b>
                                <small>@if ($m === 'seat'){{ $ctx['seat'] ? 'Seat ' . $ctx['seat'] : 'Type your seat' }}@elseif ($m === 'table')Pick your table @else Collect when ready @endif</small></span>
                            <x-lb-icon name="circle-check" class="zo-mode-ok" />
                        </label>
                    @endforeach
                </div>
                @if (in_array('seat', $ctx['modes']) && $ctx['seat_editable'])
                    <label class="zo-field" data-lo-for="seat" hidden><span>Seat number</span><input type="text" maxlength="20" placeholder="{{ $ctx['seat'] ?: 'e.g. 24' }}" data-lo-seat autocomplete="off"></label>
                @endif
                @if (in_array('table', $ctx['modes']))
                    <label class="zo-field" data-lo-for="table" hidden><span>Your table</span>
                        <select data-lo-table><option value="">Choose your table</option>@foreach ($ctx['tables'] as $t)<option value="{{ $t->id }}">Table {{ $t->label }}</option>@endforeach</select>
                    </label>
                @endif
            </div>
            <footer class="zo-sheet-f"><button type="button" class="zo-btn" data-zo-loc-close>Done</button></footer>
        </div>
    </div>

    {{-- cart / checkout sheet --}}
    <div class="zo-sheet" data-lo-sheet hidden role="dialog" aria-modal="true" aria-labelledby="zoCartTitle">
        <div class="zo-sheet-bg" data-lo-close></div>
        <div class="zo-sheet-box">
            <header class="zo-sheet-h"><h2 id="zoCartTitle">Your cart</h2><button type="button" class="zo-x" data-lo-close aria-label="Close"><x-lb-icon name="x" /></button></header>
            <div class="zo-sheet-body">
                <div class="zo-box">
                    <div class="zo-lines" data-lo-lines></div>
                    <button type="button" class="zo-addmore" data-lo-close><x-lb-icon name="plus" />Add more items</button>
                </div>

                <button type="button" class="zo-box zo-where" data-lo-change>
                    <x-lb-icon name="map-pin" /><span><small>Order for</small><b data-lo-where-sum>—</b></span><em>Change</em>
                </button>

                <div class="zo-box">
                    <label class="zo-field is-flat"><span>Note for the library</span><textarea rows="2" maxlength="500" placeholder="Less sugar, no onion…" data-lo-note></textarea></label>
                    @if ($s->partial_fulfilment)
                        <label class="zo-check"><input type="checkbox" checked data-lo-partial><span>If something runs out, send the rest first</span></label>
                    @endif
                </div>

                <div class="zo-box">
                    <h3>Bill details</h3>
                    <dl class="zo-bill">
                        <div><dt>Item total</dt><dd data-lo-sub>₹0</dd></div>
                        <div data-lo-del-row hidden><dt>Delivery fee</dt><dd data-lo-del>₹0</dd></div>
                        <div class="is-total"><dt>To pay</dt><dd data-lo-total>₹0</dd></div>
                    </dl>
                </div>

                <div class="zo-box">
                    <h3>Pay with</h3>
                    <div class="zo-pays" data-lo-pays role="radiogroup" aria-label="Payment">
                        @foreach ($ctx['payments'] as $p)
                            <label class="zo-pay" data-lo-pay="{{ $p }}">
                                <input type="radio" name="lo_pay" value="{{ $p }}">
                                <span class="zo-ico"><x-lb-icon :name="$payLabel[$p][2]" /></span>
                                <span><b>{{ $payLabel[$p][0] }}</b><small>{{ $payLabel[$p][1] }}@if ($p === 'pay_later' && $ctx['pay_later_room'] !== null) · up to {{ $inr($ctx['pay_later_room']) }}@endif</small></span>
                                <i class="zo-radio"></i>
                            </label>
                        @endforeach
                    </div>
                    <p class="zo-msg" data-lo-pay-none hidden>These items have no common payment method. Please order them separately.</p>
                </div>
                <p class="zo-msg is-bad" data-lo-error role="alert" hidden></p>
            </div>
            <footer class="zo-sheet-f">
                <button type="button" class="zo-btn is-big" data-lo-place><span>Place order</span><b data-lo-place-amt></b></button>
            </footer>
        </div>
    </div>
@endif
@endsection

@push('lr-scripts')
@if ($ctx['eligible'] && $menu->isNotEmpty())
<script>
(function () {
    var MENU = @json($menuData);
    var CTX = {
        modes: @json($ctx['modes']),
        payments: @json($ctx['payments']),
        seat: @json($ctx['seat']),
        tables: @json($ctx['tables']->map(fn ($t) => ['id' => $t->id, 'label' => $t->label])->values()),
        delivery: {{ (float) $s->delivery_charge }},
        open: @json($ctx['open']),
        limit: @json($ctx['limit_reached']),
        uploadTypes: @json($s->uploadExtensions()),
        uploadMb: {{ (int) $s->upload_max_mb }},
        placeUrl: @json(route('learner.orders.place')),
        csrf: @json(csrf_token()),
        key: @json($cartKey),
        sprite: @json(asset('public/img/lb-icons.svg') . '?v=' . @filemtime(public_path('img/lb-icons.svg')))
    };
    var MODE_TXT = { seat: 'seat delivery', table: 'cafeteria table', counter: 'counter pickup' };
    var doc = document, $ = function (s, r) { return (r || doc).querySelector(s); }, $$ = function (s, r) { return Array.prototype.slice.call((r || doc).querySelectorAll(s)); };

    var items = {}, cats = {};
    MENU.forEach(function (c) { cats[c.id] = c; c.items.forEach(function (i) { i.cat = c.id; items[i.id] = i; }); });
    function inr(n) { n = Math.round(n * 100) / 100; return '₹' + (n % 1 ? n.toFixed(2) : n).toLocaleString('en-IN'); }
    function esc(s) { var d = doc.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
    function uid() { return (window.crypto && crypto.randomUUID) ? crypto.randomUUID() : Date.now().toString(36) + Math.random().toString(36).slice(2); }
    function icon(n) { return '<svg class="lb-i" aria-hidden="true"><use href="' + CTX.sprite + '#i-' + n + '"></use></svg>'; }

    // ---- cart (kept in this browser: survives a lost connection or a closed tab)
    var cart = { mode: null, table: '', seat: '', note: '', lines: [], idem: uid() };
    try { var saved = JSON.parse(localStorage.getItem(CTX.key) || 'null'); if (saved && saved.lines) cart = Object.assign(cart, saved); } catch (e) {}
    var dropped = [];
    cart.lines = cart.lines.filter(function (l) { var it = items[l.id]; if (!it || !it.avail) { if (it) dropped.push(it.name); return false; } return true; });
    if (CTX.modes.indexOf(cart.mode) === -1) cart.mode = CTX.modes[0] || null;
    var files = {};
    function save() { try { localStorage.setItem(CTX.key, JSON.stringify(cart)); } catch (e) {} }

    function qtyOf(id) { var q = 0; cart.lines.forEach(function (l) { if (l.id === id) q += l.qty; }); return q; }
    function okForMode(it) { return !it.modes || !cart.mode || it.modes.indexOf(cart.mode) !== -1; }
    function lineTotal(l) { var it = items[l.id]; return it.price * l.qty * (it.upload ? (l.copies || 1) : 1); }
    function subtotal() { return cart.lines.reduce(function (s, l) { return s + lineTotal(l); }, 0); }
    function deliveryFee() { return (cart.mode === 'seat' || cart.mode === 'table') ? CTX.delivery : 0; }
    function payOptions() {
        var allowed = CTX.payments.slice();
        cart.lines.forEach(function (l) { var p = cats[items[l.id].cat].pay; allowed = allowed.filter(function (x) { return p.indexOf(x) !== -1; }); });
        return allowed;
    }
    function whereText() {
        if (cart.mode === 'seat') return 'Seat ' + (cart.seat || CTX.seat || '—');
        if (cart.mode === 'table') { var t = CTX.tables.filter(function (t) { return String(t.id) === String(cart.table); })[0]; return t ? 'Table ' + t.label : 'Choose your table'; }
        return 'Counter pickup';
    }

    function add(id) {
        var it = items[id]; if (!it || !it.avail || !okForMode(it)) return;
        if (it.upload) { cart.lines.push({ k: uid(), id: id, qty: 1, copies: 1, sides: 'single' }); }
        else { var l = cart.lines.filter(function (x) { return x.id === id; })[0]; if (l) l.qty = Math.min(50, l.qty + 1); else cart.lines.push({ k: uid(), id: id, qty: 1 }); }
        changed();
    }
    function dec(id) {
        for (var i = cart.lines.length - 1; i >= 0; i--) {
            if (cart.lines[i].id === id) { cart.lines[i].qty--; if (cart.lines[i].qty < 1) { delete files[cart.lines[i].k]; cart.lines.splice(i, 1); } break; }
        }
        changed();
    }
    function changed() { if (!cart.lines.length) cart.idem = uid(); save(); paintMenu(); paintBar(); if (!sheet.hidden) paintSheet(); }

    // ---- ADD buttons (on the photo, like food apps)
    function paintMenu() {
        $$('[data-lo-add]').forEach(function (box) {
            var id = +box.getAttribute('data-lo-add'), it = items[id]; if (!it) return;
            var card = box.closest('.zi');
            if (!it.avail) { box.innerHTML = ''; return; }
            var modeOk = okForMode(it);
            card.classList.toggle('is-mode-off', !modeOk);
            if (!modeOk) { box.innerHTML = '<span class="zi-off">Not for ' + esc(MODE_TXT[cart.mode]) + '</span>'; return; }
            var q = qtyOf(id);
            box.innerHTML = q && !it.upload
                ? '<div class="zi-step"><button type="button" data-dec aria-label="One less">' + icon('minus') + '</button><b>' + q + '</b><button type="button" data-inc aria-label="One more">' + icon('plus') + '</button></div>'
                : '<button type="button" class="zi-addbtn" data-inc>' + (q ? 'ADD MORE' : 'ADD') + '<i>+</i></button>';
        });
    }
    doc.addEventListener('click', function (e) {
        var box = e.target.closest('[data-lo-add]'); if (!box) return;
        var id = +box.getAttribute('data-lo-add');
        if (e.target.closest('[data-inc]')) add(id); else if (e.target.closest('[data-dec]')) dec(id);
    });

    // ---- where sheet
    var loc = $('[data-zo-loc]');
    function paintWhere() {
        $$('[data-lo-mode]').forEach(function (r) { r.checked = r.value === cart.mode; });
        $$('[data-lo-for]').forEach(function (f) { f.hidden = f.getAttribute('data-lo-for') !== cart.mode; });
        var t = $('[data-lo-table]'); if (t) t.value = cart.table || '';
        var s = $('[data-lo-seat]'); if (s) s.value = cart.seat || '';
        $('[data-zo-loc-txt]').textContent = whereText();
    }
    function openLoc() { loc.hidden = false; doc.body.classList.add('lo-noscroll'); }
    function closeLoc() { loc.hidden = true; if (sheet.hidden) doc.body.classList.remove('lo-noscroll'); paintWhere(); if (!sheet.hidden) paintSheet(); }
    $$('[data-zo-loc-open]').forEach(function (b) { b.addEventListener('click', openLoc); });
    $$('[data-zo-loc-close]').forEach(function (b) { b.addEventListener('click', closeLoc); });
    $$('[data-lo-mode]').forEach(function (r) { r.addEventListener('change', function () { cart.mode = r.value; paintWhere(); changed(); }); });
    var tSel = $('[data-lo-table]'); if (tSel) tSel.addEventListener('change', function () { cart.table = tSel.value; save(); paintWhere(); });
    var sIn = $('[data-lo-seat]'); if (sIn) sIn.addEventListener('input', function () { cart.seat = sIn.value.trim(); save(); $('[data-zo-loc-txt]').textContent = whereText(); });

    // ---- search + category chips
    var activeCat = 'all', q = '';
    function filter() {
        var shown = 0;
        $$('[data-lo-sec]').forEach(function (sec) {
            var catOk = activeCat === 'all' || sec.getAttribute('data-lo-sec') === activeCat, any = false;
            $$('.zi', sec).forEach(function (c) { var ok = catOk && (!q || c.getAttribute('data-lo-name').indexOf(q) !== -1); c.hidden = !ok; if (ok) { any = true; shown++; } });
            sec.hidden = !any;
        });
        var nr = $('[data-lo-noresult]'); if (nr) nr.hidden = shown > 0;
    }
    $$('[data-lo-cat]').forEach(function (b) { b.addEventListener('click', function () { activeCat = b.getAttribute('data-lo-cat'); $$('[data-lo-cat]').forEach(function (x) { x.classList.toggle('is-on', x === b); }); filter(); }); });
    var sq = $('[data-lo-search]'); if (sq) sq.addEventListener('input', function () { q = sq.value.trim().toLowerCase(); filter(); });

    // ---- cart bar
    var bar = $('[data-lo-cartbar]');
    function paintBar() {
        var n = cart.lines.reduce(function (s, l) { return s + (items[l.id].upload ? 1 : l.qty); }, 0); // a printout is one item, not one per page
        bar.hidden = !n;
        $('[data-lo-count]').textContent = n + (n === 1 ? ' item added' : ' items added');
        $('[data-lo-bar-total]').textContent = inr(subtotal() + (n ? deliveryFee() : 0)) + ' · ' + whereText();
        doc.body.classList.toggle('lo-has-cart', !!n);
    }

    // ---- cart sheet
    var sheet = $('[data-lo-sheet]');
    function openSheet() { paintSheet(); sheet.hidden = false; doc.body.classList.add('lo-noscroll'); }
    function closeSheet() { sheet.hidden = true; doc.body.classList.remove('lo-noscroll'); }
    bar.addEventListener('click', openSheet);
    $$('[data-lo-close]').forEach(function (b) { b.addEventListener('click', closeSheet); });
    $('[data-lo-change]').addEventListener('click', openLoc);
    doc.addEventListener('keydown', function (e) { if (e.key !== 'Escape') return; if (!loc.hidden) closeLoc(); else if (!sheet.hidden) closeSheet(); });

    function fileInput(l) {
        if (!files[l.k]) {
            var inp = doc.createElement('input'); inp.type = 'file';
            inp.accept = CTX.uploadTypes.map(function (t) { return '.' + t; }).join(',');
            inp.addEventListener('change', function () { error(''); var f = inp.files[0]; if (f && f.size > CTX.uploadMb * 1048576) { inp.value = ''; error('The file is bigger than ' + CTX.uploadMb + ' MB.'); } paintSheet(); });
            files[l.k] = inp;
        }
        return files[l.k];
    }
    function remove(l) { delete files[l.k]; cart.lines = cart.lines.filter(function (x) { return x !== l; }); }

    function paintSheet() {
        var box = $('[data-lo-lines]'); box.innerHTML = '';
        if (!cart.lines.length) box.innerHTML = '<p class="zo-none">Your cart is empty.</p>';
        cart.lines.forEach(function (l) {
            var it = items[l.id], modeOk = okForMode(it);
            var row = doc.createElement('div'); row.className = 'zo-line' + (modeOk ? '' : ' is-bad');
            row.innerHTML = '<div class="zo-line-t"><b>' + esc(it.name) + '</b><small>' + inr(it.price) + (it.unit ? ' / ' + esc(it.unit) : '') + '</small>' +
                (modeOk ? '' : '<small class="is-bad">Not for ' + esc(MODE_TXT[cart.mode]) + ' — remove it or change “Order for”</small>') + '</div>' +
                (it.upload ? '<button type="button" class="zo-rm" data-l-rm aria-label="Remove">' + icon('trash') + '</button>'
                    : '<div class="zi-step is-sm"><button type="button" data-l-dec aria-label="One less">' + icon('minus') + '</button><b>' + l.qty + '</b><button type="button" data-l-inc aria-label="One more">' + icon('plus') + '</button></div>') +
                '<span class="zo-line-p">' + inr(lineTotal(l)) + '</span>';
            if (it.upload) {
                var up = doc.createElement('div'); up.className = 'zo-up';
                var inp = fileInput(l), f = inp.files && inp.files[0];
                up.innerHTML = '<label class="zo-file' + (f ? ' is-done' : '') + '"><span>' + icon(f ? 'circle-check' : 'file-up') + '<b>' + (f ? esc(f.name) : 'Attach your file') + '</b><small>' + CTX.uploadTypes.join(', ').toUpperCase() + ' · up to ' + CTX.uploadMb + ' MB</small></span></label>' +
                    '<div class="zo-up-opts"><label>Pages<input type="number" min="1" max="50" value="' + l.qty + '" data-l-pages></label>' +
                    '<label>Copies<input type="number" min="1" max="50" value="' + (l.copies || 1) + '" data-l-copies></label>' +
                    '<label>Sides<select data-l-sides><option value="single"' + (l.sides !== 'double' ? ' selected' : '') + '>One side</option><option value="double"' + (l.sides === 'double' ? ' selected' : '') + '>Both sides</option></select></label></div>';
                up.querySelector('.zo-file').appendChild(inp);
                row.appendChild(up);
            }
            row.addEventListener('click', function (e) {
                if (e.target.closest('[data-l-inc]')) { l.qty = Math.min(50, l.qty + 1); changed(); }
                else if (e.target.closest('[data-l-dec]')) { l.qty--; if (l.qty < 1) remove(l); changed(); }
                else if (e.target.closest('[data-l-rm]')) { remove(l); changed(); }
            });
            row.addEventListener('change', function (e) {
                var v = Math.max(1, Math.min(50, parseInt(e.target.value, 10) || 1));
                if (e.target.matches('[data-l-pages]')) { l.qty = v; changed(); }
                else if (e.target.matches('[data-l-copies]')) { l.copies = v; changed(); }
                else if (e.target.matches('[data-l-sides]')) { l.sides = e.target.value; save(); }
            });
            box.appendChild(row);
        });

        $('[data-lo-where-sum]').textContent = whereText();

        var opts = payOptions(), cur = ($('input[name="lo_pay"]:checked') || {}).value;
        $$('[data-lo-pay]').forEach(function (p) { var ok = opts.indexOf(p.getAttribute('data-lo-pay')) !== -1; p.hidden = !ok; if (!ok) p.querySelector('input').checked = false; });
        if (!cur || opts.indexOf(cur) === -1) { var first = $('[data-lo-pay="' + opts[0] + '"] input'); if (first) first.checked = true; }
        $('[data-lo-pay-none]').hidden = !!opts.length || !cart.lines.length;

        var sub = subtotal(), del = cart.lines.length ? deliveryFee() : 0;
        $('[data-lo-sub]').textContent = inr(sub);
        $('[data-lo-del-row]').hidden = !del;
        $('[data-lo-del]').textContent = inr(del);
        $('[data-lo-total]').textContent = inr(sub + del);
        var note = $('[data-lo-note]'); if (note && note !== doc.activeElement) note.value = cart.note || '';

        var place = $('[data-lo-place]');
        place.disabled = !cart.lines.length || !opts.length || !CTX.open || CTX.limit;
        place.querySelector('span').textContent = !CTX.open ? 'Ordering is closed now' : CTX.limit ? 'Too many orders in progress' : 'Place order';
        $('[data-lo-place-amt]').textContent = CTX.open && !CTX.limit ? inr(sub + del) : '';
    }
    $('[data-lo-note]').addEventListener('input', function (e) { cart.note = e.target.value; save(); });
    function error(msg) { var el = $('[data-lo-error]'); el.textContent = msg || ''; el.hidden = !msg; if (msg) el.scrollIntoView({ block: 'nearest', behavior: 'smooth' }); }

    // ---- place the order (same key on retry → never two orders)
    var busy = false;
    $('[data-lo-place]').addEventListener('click', function () {
        if (busy) return;
        error('');
        var pay = ($('input[name="lo_pay"]:checked') || {}).value;
        if (cart.mode === 'table' && !cart.table) { openLoc(); return error('Choose your table first.'); }
        if (cart.mode === 'seat' && !CTX.seat && !cart.seat) { openLoc(); return error('Enter your seat number first.'); }
        var bad = cart.lines.filter(function (l) { return !okForMode(items[l.id]); });
        if (bad.length) return error(items[bad[0].id].name + ' is not for ' + MODE_TXT[cart.mode] + '. Remove it or change “Order for”.');
        var missing = cart.lines.filter(function (l) { return items[l.id].upload && !(files[l.k] && files[l.k].files[0]); });
        if (missing.length) return error('Attach the file for ' + items[missing[0].id].name + '.');
        if (!pay) return error('Choose how you will pay.');

        var fd = new FormData();
        fd.append('mode', cart.mode); fd.append('payment_method', pay); fd.append('idempotency_key', cart.idem);
        fd.append('note', cart.note || ''); fd.append('expected_total', (subtotal() + deliveryFee()).toFixed(2));
        var partial = $('[data-lo-partial]'); fd.append('allow_partial', partial && !partial.checked ? 0 : 1);
        if (cart.mode === 'seat' && cart.seat) fd.append('seat_no', cart.seat);
        if (cart.mode === 'table') fd.append('order_table_id', cart.table);
        cart.lines.forEach(function (l, i) {
            fd.append('lines[' + i + '][item_id]', l.id); fd.append('lines[' + i + '][qty]', l.qty);
            if (items[l.id].upload) { fd.append('lines[' + i + '][copies]', l.copies || 1); fd.append('lines[' + i + '][sides]', l.sides || 'single'); fd.append('lines[' + i + '][file]', files[l.k].files[0]); }
        });

        busy = true;
        var btn = this; btn.classList.add('is-busy'); btn.setAttribute('aria-busy', 'true');
        fetch(CTX.placeUrl, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CTX.csrf, 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json().catch(function () { return {}; }).then(function (j) { return { s: r.status, j: j }; }); })
            .then(function (res) {
                if (res.s === 200 && res.j.success) {
                    try { localStorage.setItem(CTX.key, JSON.stringify({ mode: cart.mode, table: cart.table, seat: cart.seat, note: '', lines: [], idem: uid() })); } catch (e) {}
                    location.href = res.j.redirect; return;
                }
                if (res.s === 409 && res.j.prices) {
                    Object.keys(res.j.prices).forEach(function (id) { if (items[id]) items[id].price = res.j.prices[id]; });
                    paintMenu(); paintBar(); paintSheet();
                    return error('Some prices changed. New total ' + inr(res.j.total) + ' — please check and place the order again.');
                }
                if (res.s === 419) return error('Your session ended. Sign in again — your cart is saved on this phone.');
                error(res.j.message || 'Could not place the order. Please try again.');
            })
            .catch(function () { error('No internet connection. Your cart is saved — try again when you are back online.'); })
            .finally(function () { busy = false; btn.classList.remove('is-busy'); btn.removeAttribute('aria-busy'); });
    });

    // ---- start: bar and sheets live on <body> so no page container can trap them under the content
    doc.body.appendChild(bar); doc.body.appendChild(sheet); doc.body.appendChild(loc);
    paintWhere(); paintMenu(); paintBar(); filter();
    if (dropped.length) {
        var n = doc.createElement('p'); n.className = 'zo-alert';
        n.innerHTML = icon('info') + 'Removed from your cart (not available now): ' + esc(dropped.join(', '));
        var top = $('.zo-top'); top.parentNode.insertBefore(n, top.nextSibling); save();
    }
})();
</script>
@endif
@endpush
