@extends('layouts.learner')

@section('title', 'Order food & more')

@push('lr-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-learner-order.css') }}?v={{ @filemtime(public_path('css/lb-learner-order.css')) }}">
@endpush

@section('content')
@php
    $inr = fn ($n) => '₹' . number_format((float) $n, (float) $n == floor((float) $n) ? 0 : 2);
    $s = $ctx['settings'];
    $modeLabel = ['seat' => 'My seat', 'table' => 'Cafeteria table', 'counter' => 'Counter pickup'];
    $modeIcon = ['seat' => 'armchair', 'table' => 'table', 'counter' => 'hand-coins'];
    $payLabel = ['online' => ['Pay online', 'UPI QR — pay in any UPI app', 'qr-code'], 'offline' => ['Pay at counter', 'Cash / UPI when you collect', 'banknote'], 'pay_later' => ['Pay later', 'Settle with the library later', 'calendar-clock']];
    $chips = [];
    if ($ctx['eligible']) {
        $chips[] = ['timer', 'Ready in ~' . $s->prep_minutes . ' min'];
        if ($ctx['seat']) $chips[] = ['armchair', 'Seat ' . $ctx['seat']];
        if ($ctx['opens_at']) $chips[] = ['clock', $ctx['opens_at'] . ' – ' . $ctx['closes_at']];
    }
@endphp

<x-lr-hero icon="coffee" kicker="Order & Services" title="Order food & more"
    sub="Snacks, tea, stationery, printouts and services — brought to your seat or ready at the counter. / सीट पर मँगवाएँ।"
    :chips="$chips" :art="false" />

@if (! $ctx['eligible'])
    <section class="lx-card lx-empty is-big">
        <x-lb-icon name="coffee" />
        <b>Ordering is not available</b>
        <p>{{ $ctx['reason'] }}</p>
        @if (Route::has('learner.orders.history'))<a class="lh-btn is-sm" href="{{ route('learner.orders.history') }}">My orders</a>@endif
    </section>
@else
    @if ($current->isNotEmpty())
        <h2 class="lx-sec">In progress</h2>
        <div class="lo-cur">
            @foreach ($current as $o)
                <a class="lo-cur-card" href="{{ route('learner.orders.show', $o->order_no) }}">
                    <span class="lo-cur-ic"><x-lb-icon name="receipt-text" /></span>
                    <span class="lo-cur-t"><b>#{{ \Illuminate\Support\Str::afterLast($o->order_no, '-') }} · {{ $inr($o->total) }}</b><small>{{ $o->locationLabel() }} · {{ $o->created_at->format('g:i A') }}</small></span>
                    <span class="lx-pill {{ in_array($o->status, ['ready', 'out_for_delivery']) ? 'is-ok' : ($o->status === 'replacement_pending' ? 'is-warn' : '') }}">{{ $o->statusLabel() }}</span>
                </a>
            @endforeach
        </div>
    @endif

    @unless ($ctx['open'])
        <div class="lo-note is-warn"><x-lb-icon name="clock-alert" /><span>Orders are taken from <b>{{ $ctx['opens_at'] }}</b> to <b>{{ $ctx['closes_at'] }}</b>. You can look at the menu now and order then.</span></div>
    @endunless
    @if ($ctx['limit_reached'])
        <div class="lo-note is-warn"><x-lb-icon name="layers" /><span>You have {{ $ctx['open_orders'] }} orders in progress — the most at one time. You can order again when one is done.</span></div>
    @endif

    {{-- where --}}
    <section class="lx-card lo-where">
        <div class="lx-card-head">
            <span class="lx-ic" data-tone="orange"><x-lb-icon name="map-pin" /></span>
            <div><h2>Where should we bring it?</h2><p>Choose once — it stays for your next order too.</p></div>
        </div>
        <div class="lo-modes" role="radiogroup" aria-label="Order for">
            @foreach ($ctx['modes'] as $m)
                <label class="lo-mode">
                    <input type="radio" name="lo_mode" value="{{ $m }}" data-lo-mode>
                    <span><x-lb-icon :name="$modeIcon[$m]" /><b>{{ $modeLabel[$m] }}</b>
                        <small>@if ($m === 'seat'){{ $ctx['seat'] ? 'Seat ' . $ctx['seat'] : 'Type your seat' }}@elseif ($m === 'table')Pick your table @else Collect when ready @endif</small></span>
                </label>
            @endforeach
        </div>
        @if (in_array('seat', $ctx['modes']) && $ctx['seat_editable'])
            <label class="lo-field" data-lo-for="seat" hidden>
                <span>Seat number</span>
                <input type="text" maxlength="20" placeholder="{{ $ctx['seat'] ?: 'e.g. 24' }}" value="" data-lo-seat autocomplete="off">
            </label>
        @endif
        @if (in_array('table', $ctx['modes']))
            <label class="lo-field" data-lo-for="table" hidden>
                <span>Table</span>
                <select data-lo-table>
                    <option value="">Choose your table</option>
                    @foreach ($ctx['tables'] as $t)<option value="{{ $t->id }}">Table {{ $t->label }}</option>@endforeach
                </select>
            </label>
        @endif
    </section>

    @if ($menu->isEmpty())
        <section class="lx-card lx-empty is-big">
            <x-lb-icon name="coffee" />
            <b>The menu is empty</b>
            <p>The library has not added anything to order yet.</p>
        </section>
    @else
        <div class="lo-bar">
            <label class="lo-search"><x-lb-icon name="search" /><input type="search" placeholder="Search tea, pen, print…" data-lo-search aria-label="Search the menu"></label>
            <div class="lo-chips" role="tablist" aria-label="Categories">
                <button type="button" class="lo-chip is-on" data-lo-cat="all">All</button>
                @foreach ($menu as $c)
                    <button type="button" class="lo-chip" data-lo-cat="{{ $c->id }}"><x-lb-icon :name="$c->icon ?: 'shapes'" />{{ $c->name }}</button>
                @endforeach
            </div>
        </div>

        @php $featured = $menu->flatMap->items->where('is_featured', true)->where('is_available', true); @endphp
        @if ($featured->isNotEmpty())
            <section class="lo-sec" data-lo-featured>
                <h2 class="lx-sec">Popular</h2>
                <div class="lo-feat">
                    @foreach ($featured as $it)
                        <article class="lo-item is-feat" data-lo-item="{{ $it->id }}">
                            <span class="lo-th">@if ($it->imageUrl())<img src="{{ $it->imageUrl() }}" alt="" loading="lazy">@else<x-lb-icon :name="$menu->firstWhere('id', $it->order_category_id)->icon ?: 'shapes'" />@endif</span>
                            <div class="lo-it"><b>{{ $it->name }}</b><span class="lo-price">{{ $inr($it->price) }}@if ($it->price_unit)<small>/{{ $it->price_unit }}</small>@endif</span></div>
                            <div class="lo-add" data-lo-add="{{ $it->id }}"></div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        @foreach ($menu as $c)
            <section class="lo-sec" data-lo-sec="{{ $c->id }}">
                <h2 class="lx-sec"><x-lb-icon :name="$c->icon ?: 'shapes'" />{{ $c->name }}</h2>
                <div class="lo-grid">
                    @foreach ($c->items as $it)
                        <article class="lo-item {{ $it->is_available ? '' : 'is-na' }}" data-lo-item="{{ $it->id }}" data-lo-name="{{ mb_strtolower($it->name . ' ' . $it->description . ' ' . $c->name) }}">
                            <span class="lo-th">@if ($it->imageUrl())<img src="{{ $it->imageUrl() }}" alt="" loading="lazy">@else<x-lb-icon :name="$c->icon ?: 'shapes'" />@endif</span>
                            <div class="lo-it">
                                <b>{{ $it->name }}</b>
                                @if ($it->description)<small>{{ $it->description }}</small>@endif
                                <span class="lo-price">{{ $inr($it->price) }}@if ($it->price_unit)<small>/{{ $it->price_unit }}</small>@endif</span>
                                @if ($it->needs_upload)<em class="lo-tag"><x-lb-icon name="file-up" />Attach file at checkout</em>@endif
                            </div>
                            <div class="lo-add" data-lo-add="{{ $it->id }}">@unless ($it->is_available)<span class="lo-na">Not available</span>@endunless</div>
                        </article>
                    @endforeach
                </div>
            </section>
        @endforeach
        <p class="lx-empty" data-lo-noresult hidden>Nothing matches your search.</p>
    @endif
@endif

@if ($ctx['eligible'] && $menu->isNotEmpty())
    {{-- cart bar --}}
    <button type="button" class="lo-cartbar" data-lo-cartbar hidden>
        <span class="lo-cartbar-n" data-lo-count>0</span>
        <span class="lo-cartbar-t"><b data-lo-bar-total>₹0</b><small>View cart & order</small></span>
        <x-lb-icon name="chevron-right" />
    </button>

    {{-- cart / checkout sheet --}}
    <div class="lo-sheet" data-lo-sheet hidden role="dialog" aria-modal="true" aria-labelledby="loSheetTitle">
        <div class="lo-sheet-bg" data-lo-close></div>
        <div class="lo-sheet-box">
            <header class="lo-sheet-h">
                <h2 id="loSheetTitle">Your order</h2>
                <button type="button" class="lo-x" data-lo-close aria-label="Close"><x-lb-icon name="x" /></button>
            </header>
            <div class="lo-sheet-body">
                <div class="lo-lines" data-lo-lines></div>

                <div class="lo-where-sum" data-lo-where-sum></div>

                <label class="lo-field">
                    <span>Note for the library <i>(optional)</i></span>
                    <textarea rows="2" maxlength="500" placeholder="Less sugar, no onion…" data-lo-note></textarea>
                </label>

                @if ($s->partial_fulfilment)
                    <label class="lo-check">
                        <input type="checkbox" checked data-lo-partial>
                        <span>If something runs out, send the rest first</span>
                    </label>
                @endif

                <h3 class="lo-h3">Payment</h3>
                <div class="lo-pays" data-lo-pays role="radiogroup" aria-label="Payment">
                    @foreach ($ctx['payments'] as $p)
                        <label class="lo-pay" data-lo-pay="{{ $p }}">
                            <input type="radio" name="lo_pay" value="{{ $p }}">
                            <span><x-lb-icon :name="$payLabel[$p][2]" /><b>{{ $payLabel[$p][0] }}</b><small>{{ $payLabel[$p][1] }}@if ($p === 'pay_later' && $ctx['pay_later_room'] !== null) · up to {{ $inr($ctx['pay_later_room']) }}@endif</small></span>
                        </label>
                    @endforeach
                </div>
                <p class="lo-msg" data-lo-pay-none hidden>These items have no common payment method. Please order them separately.</p>

                <dl class="lo-sum">
                    <div><dt>Items</dt><dd data-lo-sub>₹0</dd></div>
                    <div data-lo-del-row hidden><dt>Delivery</dt><dd data-lo-del>₹0</dd></div>
                    <div class="is-total"><dt>Total</dt><dd data-lo-total>₹0</dd></div>
                </dl>
                <p class="lo-msg is-bad" data-lo-error role="alert" hidden></p>
            </div>
            <footer class="lo-sheet-f">
                <button type="button" class="lx-btn is-save lo-place" data-lo-place>
                    <x-lb-icon name="check" /><span>Place order</span>
                </button>
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
        seatEditable: @json($ctx['seat_editable']),
        tables: @json($ctx['tables']->map(fn ($t) => ['id' => $t->id, 'label' => $t->label])->values()),
        delivery: {{ (float) $s->delivery_charge }},
        open: @json($ctx['open']),
        limit: @json($ctx['limit_reached']),
        uploadTypes: @json($s->uploadExtensions()),
        uploadMb: {{ (int) $s->upload_max_mb }},
        placeUrl: @json(route('learner.orders.place')),
        csrf: @json(csrf_token()),
        key: @json($cartKey)
    };
    var MODE_TXT = { seat: 'Seat delivery', table: 'Cafeteria table', counter: 'Counter pickup' };
    var doc = document, $ = function (s, r) { return (r || doc).querySelector(s); }, $$ = function (s, r) { return Array.prototype.slice.call((r || doc).querySelectorAll(s)); };

    // ---- data
    var items = {}, cats = {};
    MENU.forEach(function (c) { cats[c.id] = c; c.items.forEach(function (i) { i.cat = c.id; items[i.id] = i; }); });
    function inr(n) { n = Math.round(n * 100) / 100; return '₹' + (n % 1 ? n.toFixed(2) : n).toLocaleString('en-IN'); }
    function esc(s) { var d = doc.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
    function uid() { return (window.crypto && crypto.randomUUID) ? crypto.randomUUID() : Date.now().toString(36) + Math.random().toString(36).slice(2); }
    function icon(n) { return '<svg class="lb-i" aria-hidden="true"><use href="{{ asset('public/img/lb-icons.svg') }}#i-' + n + '"></use></svg>'; }

    // ---- cart state (kept in this browser: survives a lost connection or a closed tab)
    var cart = { mode: null, table: '', seat: '', note: '', lines: [], idem: uid() };
    try { var saved = JSON.parse(localStorage.getItem(CTX.key) || 'null'); if (saved && saved.lines) cart = Object.assign(cart, saved); } catch (e) {}
    var dropped = [];
    cart.lines = cart.lines.filter(function (l) { var it = items[l.id]; if (!it || !it.avail) { if (it) dropped.push(it.name); return false; } return true; });
    if (CTX.modes.indexOf(cart.mode) === -1) cart.mode = CTX.modes[0] || null;
    var files = {}; // line key → <input type=file> (files cannot be saved in the browser)
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

    function add(id) {
        var it = items[id]; if (!it || !it.avail || !okForMode(it)) return;
        if (it.upload) { cart.lines.push({ k: uid(), id: id, qty: 1, copies: 1, sides: 'single' }); }
        else {
            var l = cart.lines.filter(function (x) { return x.id === id; })[0];
            if (l) l.qty = Math.min(50, l.qty + 1); else cart.lines.push({ k: uid(), id: id, qty: 1 });
        }
        changed();
    }
    function dec(id) {
        for (var i = cart.lines.length - 1; i >= 0; i--) {
            if (cart.lines[i].id === id) { cart.lines[i].qty--; if (cart.lines[i].qty < 1) { delete files[cart.lines[i].k]; cart.lines.splice(i, 1); } break; }
        }
        changed();
    }
    function changed() { if (!cart.lines.length) cart.idem = uid(); save(); paintMenu(); paintBar(); if (!sheet.hidden) paintSheet(); }

    // ---- menu buttons
    function paintMenu() {
        $$('[data-lo-add]').forEach(function (box) {
            var id = +box.getAttribute('data-lo-add'), it = items[id]; if (!it) return;
            var card = box.closest('.lo-item');
            if (!it.avail) return;
            var modeOk = okForMode(it);
            card.classList.toggle('is-mode-off', !modeOk);
            if (!modeOk) { box.innerHTML = '<span class="lo-na">Not for ' + esc(MODE_TXT[cart.mode].toLowerCase()) + '</span>'; return; }
            var q = qtyOf(id);
            box.innerHTML = q && !it.upload
                ? '<div class="lo-step"><button type="button" data-dec aria-label="One less">' + icon('minus') + '</button><b>' + q + '</b><button type="button" data-inc aria-label="One more">' + icon('plus') + '</button></div>'
                : '<button type="button" class="lo-addbtn" data-inc>' + icon('plus') + (q ? 'Add another (' + q + ')' : 'Add') + '</button>';
        });
    }
    doc.addEventListener('click', function (e) {
        var box = e.target.closest('[data-lo-add]'); if (!box) return;
        var id = +box.getAttribute('data-lo-add');
        if (e.target.closest('[data-inc]')) add(id); else if (e.target.closest('[data-dec]')) dec(id);
    });

    // ---- where
    function paintWhere() {
        $$('[data-lo-mode]').forEach(function (r) { r.checked = r.value === cart.mode; });
        $$('[data-lo-for]').forEach(function (f) { f.hidden = f.getAttribute('data-lo-for') !== cart.mode; });
        var t = $('[data-lo-table]'); if (t) t.value = cart.table || '';
        var s = $('[data-lo-seat]'); if (s) s.value = cart.seat || '';
    }
    $$('[data-lo-mode]').forEach(function (r) { r.addEventListener('change', function () { cart.mode = r.value; paintWhere(); changed(); }); });
    var tSel = $('[data-lo-table]'); if (tSel) tSel.addEventListener('change', function () { cart.table = tSel.value; save(); if (!sheet.hidden) paintSheet(); });
    var sIn = $('[data-lo-seat]'); if (sIn) sIn.addEventListener('input', function () { cart.seat = sIn.value.trim(); save(); });

    // ---- search + category chips
    var activeCat = 'all', q = '';
    function filter() {
        var shown = 0;
        $$('[data-lo-sec]').forEach(function (sec) {
            var catOk = activeCat === 'all' || sec.getAttribute('data-lo-sec') === activeCat, any = false;
            $$('.lo-item', sec).forEach(function (c) { var ok = catOk && (!q || c.getAttribute('data-lo-name').indexOf(q) !== -1); c.hidden = !ok; if (ok) { any = true; shown++; } });
            sec.hidden = !any;
        });
        var f = $('[data-lo-featured]'); if (f) f.hidden = activeCat !== 'all' || !!q;
        var nr = $('[data-lo-noresult]'); if (nr) nr.hidden = shown > 0;
    }
    $$('[data-lo-cat]').forEach(function (b) { b.addEventListener('click', function () { activeCat = b.getAttribute('data-lo-cat'); $$('[data-lo-cat]').forEach(function (x) { x.classList.toggle('is-on', x === b); }); filter(); }); });
    var sq = $('[data-lo-search]'); if (sq) sq.addEventListener('input', function () { q = sq.value.trim().toLowerCase(); filter(); });

    // ---- cart bar
    var bar = $('[data-lo-cartbar]');
    function paintBar() {
        var n = cart.lines.reduce(function (s, l) { return s + l.qty; }, 0);
        bar.hidden = !n;
        $('[data-lo-count]').textContent = n;
        $('[data-lo-bar-total]').textContent = inr(subtotal() + (n ? deliveryFee() : 0));
        doc.body.classList.toggle('lo-has-cart', !!n);
    }

    // ---- sheet
    var sheet = $('[data-lo-sheet]');
    function openSheet() { paintSheet(); sheet.hidden = false; doc.body.classList.add('lo-noscroll'); }
    function closeSheet() { sheet.hidden = true; doc.body.classList.remove('lo-noscroll'); }
    bar.addEventListener('click', openSheet);
    $$('[data-lo-close]').forEach(function (b) { b.addEventListener('click', closeSheet); });
    doc.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !sheet.hidden) closeSheet(); });

    function fileInput(l) {
        if (!files[l.k]) {
            var inp = doc.createElement('input'); inp.type = 'file';
            inp.accept = CTX.uploadTypes.map(function (t) { return '.' + t; }).join(',');
            inp.addEventListener('change', function () { error(''); var f = inp.files[0]; if (f && f.size > CTX.uploadMb * 1048576) { inp.value = ''; error('The file is bigger than ' + CTX.uploadMb + ' MB.'); } paintSheet(); });
            files[l.k] = inp;
        }
        return files[l.k];
    }

    function paintSheet() {
        var box = $('[data-lo-lines]'); box.innerHTML = '';
        if (!cart.lines.length) { box.innerHTML = '<p class="lx-empty">Your cart is empty.</p>'; }
        cart.lines.forEach(function (l) {
            var it = items[l.id], modeOk = okForMode(it);
            var row = doc.createElement('div'); row.className = 'lo-line' + (modeOk ? '' : ' is-bad');
            row.innerHTML = '<div class="lo-line-t"><b>' + esc(it.name) + '</b><small>' + esc(cats[it.cat].name) + ' · ' + inr(it.price) + (it.unit ? '/' + esc(it.unit) : '') + '</small>' +
                (modeOk ? '' : '<small class="is-bad">Not for ' + esc(MODE_TXT[cart.mode].toLowerCase()) + ' — remove it or change the option</small>') + '</div>' +
                '<div class="lo-step"><button type="button" data-l-dec aria-label="One less">' + icon('minus') + '</button><b>' + l.qty + '</b><button type="button" data-l-inc aria-label="One more">' + icon('plus') + '</button></div>' +
                '<span class="lo-line-p">' + inr(lineTotal(l)) + '</span>' +
                '<button type="button" class="lo-x is-sm" data-l-rm aria-label="Remove ' + esc(it.name) + '">' + icon('trash') + '</button>';
            if (it.upload) {
                var up = doc.createElement('div'); up.className = 'lo-up';
                var inp = fileInput(l), f = inp.files && inp.files[0];
                up.innerHTML = '<label class="lo-file"><span>' + icon('file-up') + '<b>' + (f ? esc(f.name) : 'Attach the file') + '</b><small>' + CTX.uploadTypes.join(', ').toUpperCase() + ' · up to ' + CTX.uploadMb + ' MB</small></span></label>' +
                    '<div class="lo-up-opts"><label>Pages<input type="number" min="1" max="50" value="' + l.qty + '" data-l-pages></label>' +
                    '<label>Copies<input type="number" min="1" max="50" value="' + (l.copies || 1) + '" data-l-copies></label>' +
                    '<label>Sides<select data-l-sides><option value="single"' + (l.sides !== 'double' ? ' selected' : '') + '>One side</option><option value="double"' + (l.sides === 'double' ? ' selected' : '') + '>Both sides</option></select></label></div>';
                up.querySelector('.lo-file').appendChild(inp);
                row.appendChild(up);
                row.querySelector('.lo-step').remove();
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

        // where summary
        var w = cart.mode === 'seat' ? 'Seat ' + (cart.seat || CTX.seat || '—') : cart.mode === 'table'
            ? (cart.table ? 'Table ' + (CTX.tables.filter(function (t) { return String(t.id) === String(cart.table); })[0] || {}).label : 'Choose your table on the page')
            : 'Counter pickup';
        $('[data-lo-where-sum]').innerHTML = icon('map-pin') + '<span><b>' + esc(MODE_TXT[cart.mode] || '') + '</b> · ' + esc(w) + '</span><button type="button" data-lo-change>Change</button>';

        // payments
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
        var blocked = !cart.lines.length || !opts.length || !CTX.open || CTX.limit;
        place.disabled = blocked;
        place.querySelector('span').textContent = !CTX.open ? 'Ordering is closed now' : CTX.limit ? 'Too many orders in progress' : 'Place order · ' + inr(sub + del);
    }
    function remove(l) { delete files[l.k]; cart.lines = cart.lines.filter(function (x) { return x !== l; }); }
    $('[data-lo-note]').addEventListener('input', function (e) { cart.note = e.target.value; save(); });
    sheet.addEventListener('click', function (e) { if (e.target.closest('[data-lo-change]')) { closeSheet(); $('.lo-where').scrollIntoView({ behavior: 'smooth', block: 'center' }); } });

    function error(msg) { var el = $('[data-lo-error]'); el.textContent = msg || ''; el.hidden = !msg; }

    // ---- place the order (same key on retry → never two orders)
    var busy = false;
    $('[data-lo-place]').addEventListener('click', function () {
        if (busy) return;
        error('');
        var pay = ($('input[name="lo_pay"]:checked') || {}).value;
        if (cart.mode === 'table' && !cart.table) return error('Choose your table first.');
        if (cart.mode === 'seat' && !CTX.seat && !cart.seat) return error('Enter your seat number first.');
        var bad = cart.lines.filter(function (l) { return !okForMode(items[l.id]); });
        if (bad.length) return error(items[bad[0].id].name + ' is not for ' + MODE_TXT[cart.mode].toLowerCase() + '. Remove it or change the option.');
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

    // ---- start: cart bar and sheet live on <body> so no page container (transforms, isolation) can trap them under the content
    doc.body.appendChild(bar); doc.body.appendChild(sheet);
    paintWhere(); paintMenu(); paintBar(); filter();
    if (dropped.length) { var n = doc.createElement('div'); n.className = 'lo-note is-warn'; n.innerHTML = icon('info') + '<span>Removed from your cart (not available now): ' + esc(dropped.join(', ')) + '</span>'; var w = $('.lo-where'); w.parentNode.insertBefore(n, w); save(); }
})();
</script>
@endif
@endpush
