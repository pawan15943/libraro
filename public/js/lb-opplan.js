/* Libraro — plan operations (upgrade / renew / change plan / edit plan / other payment).
   Pure presentation layer on top of the original form:
   - selects get tap-friendly chips (the select stays the source of truth and fires its normal change event)
   - a "current → after" journey card
   - a live bill + footer total that only READ the form values the page scripts already compute
   No field names, ids, validation or submit logic is changed. */
(function () {
    'use strict';
    var root = document.getElementById('operationContent');
    if (!root || !root.classList.contains('lop')) return;
    var op = root.getAttribute('data-op');
    var form = root.querySelector('form');
    if (!form) return;
    var $ = window.jQuery;

    var spriteUse = document.querySelector('svg.lb-i use');
    var SPRITE = spriteUse ? (spriteUse.getAttribute('href') || '').split('#')[0] : '';
    function ic(name) { return '<svg class="lb-i" aria-hidden="true"><use href="' + SPRITE + '#i-' + name + '"></use></svg>'; }
    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
    function el(id) { return (form && form.querySelector('[id="' + id + '"]')) || document.getElementById(id); }
    function num(id) { var e = el(id); if (!e) return 0; var v = parseFloat(String(e.value || '').replace(/[^0-9.\-]/g, '')); return isNaN(v) ? 0 : v; }
    function val(id) { var e = el(id); return e ? e.value : ''; }
    function money(n) { n = Math.round(Math.abs(n || 0)); return '₹' + n.toLocaleString('en-IN'); }
    function optText(id) { var e = el(id); if (!e || e.tagName !== 'SELECT') return ''; var o = e.options[e.selectedIndex]; return o && o.value !== '' ? o.text.trim() : ''; }
    function fmtDate(s) {
        if (!s) return '';
        var m = String(s).match(/^(\d{4})-(\d{2})-(\d{2})/); if (!m) return s;
        var d = new Date(+m[1], +m[2] - 1, +m[3]);
        return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
    }

    /* ---------------- chip pickers ---------------- */
    var PAY = { '1': ['smartphone', 'Online', 'UPI, card or bank'], '2': ['banknote', 'Offline', 'Cash at the desk'], '3': ['calendar-clock', 'Pay later', 'Collect on the due date'] };
    // Plan and shift are picked on cards (as on the booking form); every other choice (payment
    // mode, pay now / later, locker, discount, payment type) stays a plain dropdown
    var CHIPS = {
        plan_id10: { icon: 'calendar-days' },
        plan_type_id10: { icon: 'clock', grid: true }    // every shift with its timing; a booked one is disabled
    };
    var chipState = {};

    function buildChips(id) {
        var cfg = CHIPS[id];
        if (cfg.onlyIn && cfg.onlyIn !== op) return;
        var sel = form.querySelector('select[id="' + id + '"]');
        if (!sel) return;
        // custom plan (docs/CUSTOM_PLAN.md): one "Custom" chip for "Custom dates" and the hidden plan picked for them
        var isCustom = function (o) { return !!o && (o.hasAttribute('data-custom-trigger') || o.hasAttribute('data-custom-plan')); };
        var chipVal = function (o) { return o.hasAttribute('data-custom-trigger') ? '__custom' : o.value; };
        var curVal = function () { return isCustom(sel.options[sel.selectedIndex]) ? '__custom' : sel.value; };
        var opts = Array.prototype.filter.call(sel.options, function (o) { return !o.hasAttribute('data-custom-plan') && (o.value !== '' || cfg.withEmpty || o.hasAttribute('data-custom-trigger')); });
        var locked = sel.hasAttribute('readonly') || sel.disabled;
        var sig = opts.map(function (o) { return o.value + '|' + o.text + (o.disabled ? '|x' : ''); }).join('~') + (locked ? '#L' : '');
        var st = chipState[id];
        if (!st || st.sig !== sig) {
            var wrap = st ? st.wrap : document.createElement('div');
            if (!st) {
                wrap.className = 'lop-chips' + (cfg.cards ? ' is-cards' : '') + (cfg.seg ? ' is-seg' : '') + (cfg.grid ? ' is-grid' : '');
                wrap.setAttribute('role', 'radiogroup');
                var lab = sel.id && form.querySelector('label[for="' + sel.id + '"]');
                if (lab) { if (!lab.id) lab.id = 'lopLab_' + id; wrap.setAttribute('aria-labelledby', lab.id); }
                sel.classList.add('lop-native');
                sel.setAttribute('tabindex', '-1');
                sel.insertAdjacentElement('afterend', wrap);
                wrap.addEventListener('click', function (e) {
                    var b = e.target.closest('.lop-chip'); if (!b || b.disabled) return;
                    var v = b.getAttribute('data-v');
                    if (curVal() === v) return;
                    if (v === '__custom') sel.querySelector('option[data-custom-trigger]').selected = true;
                    else sel.value = v;
                    sel.dispatchEvent(new Event('change', { bubbles: true }));   // jQuery + native handlers both run
                    paintAll();
                });
                wrap.addEventListener('keydown', function (e) {
                    if (['ArrowRight', 'ArrowDown', 'ArrowLeft', 'ArrowUp'].indexOf(e.key) < 0) return;
                    var bs = Array.prototype.slice.call(wrap.querySelectorAll('.lop-chip:not(:disabled)'));
                    var i = bs.indexOf(document.activeElement); if (i < 0) return;
                    e.preventDefault(); i = (i + (e.key === 'ArrowRight' || e.key === 'ArrowDown' ? 1 : -1) + bs.length) % bs.length;
                    bs[i].focus(); bs[i].click();
                });
            }
            wrap.classList.toggle('is-locked', locked);
            wrap.innerHTML = opts.length ? opts.map(function (o) {
                var m = cfg.map && cfg.map[o.value];
                var label = o.hasAttribute('data-custom-trigger') ? 'Custom' : (m && m[1] ? m[1] : o.text.trim());
                var subTxt = m && m[2] && !cfg.seg ? m[2] : o.getAttribute('data-sub');
                var sub = subTxt ? '<small>' + esc(subTxt) + '</small>' : '';
                var icon = m ? m[0] : cfg.icon;
                return '<button type="button" class="lop-chip" role="radio" data-v="' + esc(chipVal(o)) + '"' + (locked || o.disabled ? ' disabled' : '') + '>' + (icon ? ic(icon) : '') + '<span>' + esc(label) + sub + '</span></button>';
            }).join('') : '<div class="lop-chips-empty">No options available</div>';
            chipState[id] = st = { wrap: wrap, sig: sig };
        }
        Array.prototype.forEach.call(st.wrap.querySelectorAll('.lop-chip'), function (b) {
            var on = b.getAttribute('data-v') === curVal();
            b.classList.toggle('is-on', on);
            b.setAttribute('aria-checked', on ? 'true' : 'false');
            b.tabIndex = on || (!sel.value && b === st.wrap.querySelector('.lop-chip')) ? 0 : -1;
        });
        st.wrap.classList.toggle('is-invalid-ring', sel.classList.contains('is-invalid'));
    }

    /* ---------------- journey: current -> after ---------------- */
    // the learner card on top and the summary at the bottom already say what changes
    var journey = null;
    if (false) {
        journey = document.createElement('section');
        journey.className = 'lop-journey';
        journey.setAttribute('aria-label', 'Plan change');
        journey.innerHTML =
            '<div class="lop-jbox is-cur"><span class="lop-jk">Current plan ' + ic('lock') + '</span><strong data-j="cp"></strong><span class="lop-js" data-j="cs"></span><span class="lop-jd" data-j="cd"></span></div>' +
            '<span class="lop-jarrow" aria-hidden="true">' + ic('arrow-right') + '</span>' +
            '<div class="lop-jbox is-new"><span class="lop-jk"><span data-j="nk"></span><span class="lop-jtag" data-j="tag"></span></span><strong data-j="np"></strong><span class="lop-js" data-j="ns"></span><span class="lop-jd" data-j="nd"></span></div>';
        var firstCard = form.querySelector('.plan-card');
        if (firstCard) firstCard.parentNode.insertBefore(journey, firstCard);
    }
    var lastJ = '';
    function paintJourney() {
        if (!journey) return;
        var cp = root.getAttribute('data-cur-plan') || '—', cs = root.getAttribute('data-cur-shift') || '';
        var cStart = root.getAttribute('data-cur-start'), cEnd = root.getAttribute('data-cur-end');
        var np = optText('plan_id10') || cp, ns = optText('plan_type_id10') || cs;
        var nStart = (op === 'edit' || op === 'reactive') ? val('start_date10') : (root.getAttribute('data-new-start') || val('start_date10'));
        var nEnd = op === 'edit' ? val('plan_end_date_edit') : '';
        var seatTxt = op === 'reactive' && seatRail ? (function () { var o = seatRail.native.options[seatRail.native.selectedIndex]; return o ? (o.value ? o.text.replace(/^Seat No -\s*/, 'Seat ') : 'General seat') : ''; })() : '';
        var billed = (el('chargeable_days10') && el('chargeable_days10').textContent.trim()) || '';
        var same = np === cp && ns === cs;
        var tag = op === 'reactive' ? 'Reactivation' : op === 'renew' ? 'Next term' : op === 'edit' ? ((nStart !== cStart) ? 'Dates changed' : 'Same plan') : (same ? 'No change' : (np !== cp && ns !== cs ? 'Plan + shift' : np !== cp ? 'New plan' : 'New shift'));
        var nk = op === 'reactive' ? 'Starts again as' : op === 'renew' ? 'Renews as' : op === 'edit' ? 'After edit' : 'Changes to';
        var cd = (cStart ? '<b>' + fmtDate(cStart) + '</b> → ' : '') + '<b>' + (fmtDate(cEnd) || '—') + '</b>';
        var nd = nStart ? ('Starts <b>' + fmtDate(nStart) + '</b>' + (nEnd ? ' · ends <b>' + fmtDate(nEnd) + '</b>' : '') + (billed ? ' · ' + esc(billed) : '')) : esc(billed);
        if (seatTxt) nd = esc(seatTxt) + ' · ' + nd;
        var key = [np, ns, nd, tag].join('|');
        var q = function (k) { return journey.querySelector('[data-j="' + k + '"]'); };
        q('cp').textContent = cp; q('cs').textContent = cs; q('cd').innerHTML = cd;
        q('nk').textContent = nk; q('np').textContent = np; q('ns').textContent = ns; q('nd').innerHTML = nd;
        var t = q('tag'); t.textContent = tag; t.classList.toggle('is-same', tag === 'No change' || tag === 'Same plan');
        if (lastJ && key !== lastJ) { var nb = journey.querySelector('.is-new'); nb.classList.remove('is-pulse'); void nb.offsetWidth; nb.classList.add('is-pulse'); }
        lastJ = key;
    }

    /* ---------------- add-on summary in the collapsible header ---------------- */
    var addonHead = form.querySelector('#planAddonsCollapse') ? form.querySelector('#planAddonsCollapse').closest('.plan-card').querySelector('.plan-card-header') : null;
    var addonSum = null;
    if (addonHead) {
        addonSum = document.createElement('span'); addonSum.className = 'lop-addon-sum';
        var tgl = addonHead.querySelector('.plan-header-toggle');
        if (tgl) addonHead.insertBefore(addonSum, tgl); else addonHead.appendChild(addonSum);
    }
    function discountValue(base) {
        var t = val('discountType10'), r = num('discount_amount10');
        return t === 'percentage' ? base * r / 100 : t === 'amount' ? r : 0;
    }
    function paintAddons() {
        if (!addonSum) return;
        var parts = [];
        var lk = el('toggleFieldCheckbox10');
        if (lk && lk.value === 'yes') parts.push('Locker ' + money(num('locker_amount10')));
        var dt = val('discountType10');
        if (dt && num('discount_amount10') > 0) parts.push(dt === 'percentage' ? num('discount_amount10') + '% off' : money(num('discount_amount10')) + ' off');
        addonSum.textContent = parts.length ? parts.join(' · ') : 'None';
        addonSum.classList.toggle('is-on', parts.length > 0);
    }



    /* ---------------- seat rail (reactivate): taps the original dropdown items, so the page script stays in charge ---------------- */
    var seatRail = null;
    (function () {
        var list = form.querySelector('#seatDropdownList'), native = form.querySelector('select[id="new_seat_id2"]');
        if (!list || !native) return;
        var items = Array.prototype.slice.call(list.querySelectorAll('.seat-dropdown-item'));
        var combo = form.querySelector('#seatSearchCombobox');
        var colEl = native.closest('[class*="col-"]');
        if (colEl) colEl.classList.add('lop-seat-col');
        var short = function (f) { return (f || '').split(/\s+/).filter(Boolean).map(function (w) { return w[0]; }).join('').toUpperCase().slice(0, 3); };
        var box = document.createElement('div'); box.className = 'lop-seatpick';
        box.innerHTML = '<div class="lop-seat-tools"><label class="lsw-search">' + ic('search') + '<input type="search" placeholder="Search seat number, e.g. 12" autocomplete="off" aria-label="Search seats"></label>' +
            '<span class="lsw-rail-nav"><button type="button" class="lsw-rail-btn" data-d="-1" aria-label="Previous seats">' + ic('chevron-left') + '</button><button type="button" class="lsw-rail-btn" data-d="1" aria-label="Next seats">' + ic('chevron-right') + '</button></span></div>' +
            '<div class="lsw-grid" role="listbox" aria-label="Available seats"></div><p class="lop-seat-empty" hidden>No free seat matches</p>';
        var grid = box.querySelector('.lsw-grid');
        items.forEach(function (it) {
            var v = it.getAttribute('data-value') || '', d = it.getAttribute('data-display') || 'General';
            var m = d.match(/(\d+)\s*(?:\((.+)\))?/), n = v ? (m ? m[1] : v) : 'Gen', fl = m && m[2] ? short(m[2]) : (v ? '' : 'Any seat');
            var b = document.createElement('button');
            b.type = 'button'; b.className = 'lsw-seat'; b.setAttribute('role', 'option'); b.setAttribute('data-value', v);
            b.setAttribute('data-q', (n + ' ' + v + ' ' + d + ' ' + fl).toLowerCase());
            b.innerHTML = ic('armchair') + '<b>' + esc(n) + '</b>' + (fl ? '<small>' + esc(fl) + '</small>' : '');
            b.addEventListener('click', function () { it.click(); paintAll(); });
            grid.appendChild(b);
        });
        if (combo) { combo.insertAdjacentElement('beforebegin', box); combo.classList.add('lop-native-box'); }
        var q = box.querySelector('input'), empty = box.querySelector('.lop-seat-empty');
        q.addEventListener('input', function () {
            var t = q.value.trim().toLowerCase(), n = 0;
            Array.prototype.forEach.call(grid.children, function (b) { var ok = !t || b.getAttribute('data-q').indexOf(t) >= 0; b.hidden = !ok; if (ok) n++; });
            empty.hidden = n > 0; grid.scrollLeft = 0;
        });
        q.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); var vis = Array.prototype.filter.call(grid.children, function (b) { return !b.hidden; }); if (vis.length === 1) vis[0].click(); } });
        box.querySelectorAll('.lsw-rail-btn').forEach(function (b) { b.addEventListener('click', function () { grid.scrollBy({ left: +b.getAttribute('data-d') * Math.max(160, grid.clientWidth - 90), behavior: 'smooth' }); }); });
        seatRail = { grid: grid, native: native, centered: false };
    })();
    function paintSeats() {
        if (!seatRail) return;
        var v = seatRail.native.value, on = null;
        Array.prototype.forEach.call(seatRail.grid.children, function (b) { var is = b.getAttribute('data-value') === v; b.classList.toggle('is-on', is); b.setAttribute('aria-selected', is ? 'true' : 'false'); if (is) on = b; });
        if (on && !seatRail.centered) { seatRail.centered = true; seatRail.grid.scrollLeft = Math.max(0, on.offsetLeft - seatRail.grid.clientWidth / 2 + on.offsetWidth / 2); }
    }

    /* ---------------- compact add-ons: two setting rows instead of a 5-field grid ---------------- */
    var addonSets = [];
    (function () {
        var col = function (id) { var f = form.querySelector('[id="' + id + '"]'); return f ? f.closest('[class*="col-"]') : null; };
        var collapse = form.querySelector('#planAddonsCollapse');
        if (!collapse) return;
        var card = collapse.closest('.plan-card'); if (!card) return;
        card.classList.add('lop-addons');
        var head = card.querySelector('.plan-card-header');
        if (head) { head.removeAttribute('data-bs-toggle'); head.removeAttribute('role'); head.classList.remove('collapsible-header'); }
        collapse.classList.add('show'); collapse.classList.remove('collapse'); collapse.style.height = '';
        var body = collapse.querySelector('.plan-card-body') || collapse;
        var holder = document.createElement('div'); holder.className = 'lop-sets';
        function set(key, icon, title, sub, mainId, extraIds) {
            var main = col(mainId); if (!main || main.classList.contains('d-none')) return;
            var row = document.createElement('div'); row.className = 'lop-set'; row.setAttribute('data-set', key);
            row.innerHTML = '<div class="lop-set-t"><span class="lop-set-ic">' + ic(icon) + '</span><div><b>' + esc(title) + '</b><small>' + esc(sub) + '</small></div></div><div class="lop-set-c"></div><div class="lop-set-x"></div>';
            row.querySelector('.lop-set-c').appendChild(main);
            extraIds.forEach(function (x) { var c = col(x); if (c) row.querySelector('.lop-set-x').appendChild(c); });
            holder.appendChild(row); addonSets.push(row);
        }
        set('locker', 'key-round', 'Locker', 'Add a personal locker to this term', 'toggleFieldCheckbox10', ['locker_amount10', 'locker_no10']);
        set('discount', 'tag', 'Discount', 'Flat amount or percent off', 'discountType10', ['discount_amount10']);
        if (!addonSets.length) return;
        body.insertBefore(holder, body.firstChild);
        Array.prototype.forEach.call(body.querySelectorAll(':scope > .row'), function (r) { if (!r.querySelector('input, select, textarea')) r.style.display = 'none'; });
    })();
    function paintSets() {
        addonSets.forEach(function (row) {
            var k = row.getAttribute('data-set');
            var on = k === 'locker' ? val('toggleFieldCheckbox10') === 'yes' : !!val('discountType10');
            row.classList.toggle('is-on', on);
        });
    }

    /* ---------------- compact payment: stat strip + only the fields that need a decision ---------------- */
    var pay = null;
    (function () {
        var modeSel = form.querySelector('select[id="payment_mode10"]') || (op === 'reactive' ? form.querySelector('select[name="payment_mode"]') : null);
        if (!modeSel) return;
        var card = modeSel.closest('.plan-card'); if (!card) return;
        var col = function (id) { var f = form.querySelector('[id="' + id + '"]'); return f ? f.closest('[class*="col-"]') : null; };
        card.classList.add('lop-pay');
        var body = card.querySelector('.plan-card-body');
        // change / edit: "previous paid + total" card is shown in the strip and the bill instead
        var prevCard = form.querySelector('[id="previous_amount10"]');
        prevCard = prevCard ? prevCard.closest('.plan-card') : null;
        if (prevCard && prevCard !== card) prevCard.classList.add('lop-quiet');
        var strip = document.createElement('div'); strip.className = 'lop-stats';
        var grid = document.createElement('div'); grid.className = 'lop-pay-grid';
        var hold = document.createElement('div'); hold.className = 'lop-hold'; hold.hidden = true;
        var timing = col('refund_pay_timing10'), diff = col('diffrence_amount10'), total = col('total_amount10');
        var due = col('due_date10'), mode = modeSel.closest('[class*="col-"]');
        var isDiffFlow = !!diff;
        if (timing) { timing.classList.add('lop-g-full'); grid.appendChild(timing); }
        if (isDiffFlow) { grid.appendChild(diff); } else if (total && total.closest('.lop-pay')) { grid.appendChild(total); var tl = total.querySelector('label'); if (tl) tl.innerHTML = 'Collecting now <span class="required-star">*</span>'; }
        if (isDiffFlow) {
            if (due) grid.appendChild(due);
            if (mode) { mode.classList.add('lop-g-full'); grid.appendChild(mode); }
        } else {
            if (mode) grid.appendChild(mode);          // "Collecting now" | "Payment mode" side by side
            if (due) grid.appendChild(due);
        }
        ['previous_pending10', 'pending_amt10'].forEach(function (id) { var c = col(id); if (c && c.closest('.lop-pay') && !grid.contains(c)) hold.appendChild(c); });
        var pl = form.querySelector('#payLaterInfoCol10'); if (pl && pl.closest('.lop-pay')) hold.appendChild(pl);
        body.insertBefore(strip, body.firstChild);
        strip.insertAdjacentElement('afterend', grid);
        grid.insertAdjacentElement('afterend', hold);
        Array.prototype.forEach.call(body.querySelectorAll('.row, .action-card-rows'), function (r) { if (!r.querySelector('input:not([type=hidden]), select, textarea')) r.style.display = 'none'; });
        pay = { card: card, strip: strip, diffFlow: isDiffFlow };
    })();
    function stat(label, amount, cls) { return '<div class="lop-stat ' + (cls || '') + '"><span>' + esc(label) + '</span><b>' + money(amount) + '</b></div>'; }
    function paintPay() {
        if (!pay) return;
        var mode = val('payment_mode10') || (op === 'reactive' ? val('payment_mode') : '');
        var pending = num('pending_amt10'), timing = val('refund_pay_timing10');
        var html = '';
        if (pay.diffFlow) {
            // change / edit, in plain words: paid so far → new plan → collect more / give back / nothing
            var prevPaid = num('previous_amount10'), total = num('total_amount10'), d = Math.round(total - prevPaid);
            var res = d > 0 ? ['Collect from learner', 'is-acc'] : d < 0 ? ['Refund to learner', 'is-ok'] : ['Nothing to settle', ''];
            html = stat('Paid so far', prevPaid) + stat('New plan costs', total) + stat(res[0], d, res[1] + ' is-res') + (pending > 0 ? stat(d < 0 ? 'Refund pending' : 'Stays pending', pending, 'is-warn') : '');
            var say = d > 0 ? 'The new plan costs ' + money(d) + ' more. Collect ' + money(d) + ' from the learner.'
                : d < 0 ? 'The new plan costs ' + money(d) + ' less. Give ' + money(d) + ' back to the learner.'
                : 'The new plan costs the same as what was paid. Nothing to collect or refund.';
            html += '<p class="lop-say ' + (d > 0 ? 'is-acc' : d < 0 ? 'is-ok' : '') + '">' + esc(say) + '</p>';
            pay.card.setAttribute('data-settle', d === 0 ? 'none' : (d > 0 ? 'collect' : 'refund'));
            // the questions follow the direction of the money
            var tl = el('refund_pay_timing_label10'), ts = el('refund_pay_timing10'), al = el('diffrence_amount_label10');
            var word = d < 0 ? 'refund' : 'collect';
            if (tl) tl.innerHTML = 'When will you ' + word + ' ' + esc(money(d)) + '? <span class="required-star">*</span>';
            if (ts) { var on = ts.querySelector('option[value="now"]'), lt = ts.querySelector('option[value="later"]');
                if (on) on.textContent = d < 0 ? 'Refund now' : 'Collect now'; if (lt) lt.textContent = d < 0 ? 'Refund later' : 'Collect later (add to dues)'; }
            if (al) al.innerHTML = (d < 0 ? 'Refunding now (₹)' : 'Collecting now (₹)') + ' <span class="required-star">*</span>';
        } else {
            var price = num('plan_price10'), locker = num('locker_amount10'), dtotal = Math.max(0, price + locker - discountValue(price + locker));
            var prev = num('previous_pending10');
            html = stat('Term total', dtotal) + (prev ? stat('Old dues', prev, 'is-warn') : '') + stat('Stays pending', pending, pending > 0 ? 'is-warn' : 'is-ok');
        }
        if (pay.strip.innerHTML !== html) pay.strip.innerHTML = html;
        var needDue = mode === '3' || pending > 0 || timing === 'later';
        pay.card.setAttribute('data-need-due', needDue ? '1' : '0');
        pay.card.setAttribute('data-timing', timing || '');
    }

    /* ---------------- live bill + footer total ---------------- */
    var aside = el('lopAside');
    var bar = form.querySelector('.form-action-bar');
    var due = null;
    if (bar) { due = document.createElement('div'); due.className = 'lop-due'; due.innerHTML = '<span></span><strong></strong>'; bar.insertBefore(due, bar.firstChild); }
    if (aside) {
        aside.innerHTML = '<section class="lop-bill"><div class="lop-bill-h">' + ic('receipt-text') + '<h4>' + (op === 'other' ? 'Payment summary' : 'Bill summary') + '</h4><span>Live</span></div><ul class="lop-lines"></ul><div class="lop-due-box"><span class="lop-due-k"></span><strong class="lop-due-v"></strong><span class="lop-due-s"></span></div><div class="lop-after"></div></section>' +
            '';
        // one column: the summary sits at the end of the form, just above the save bar
        if (bar) bar.parentNode.insertBefore(aside, bar);
    }
    var lastDue = '';
    function line(label, amount, cls, sub) { return '<li class="' + (cls || '') + '"><span>' + esc(label) + (sub ? '<small>' + esc(sub) + '</small>' : '') + '</span><b>' + (cls === 'is-minus' ? '− ' : cls === 'is-plus' ? '+ ' : '') + money(amount) + '</b></li>'; }

    function compute() {
        var r = { lines: '', k: '', v: 0, s: '', tone: '', after: [], steps: [], footK: '', footV: '' };
        var mode = val('payment_mode10') || (op === 'reactive' ? val('payment_mode') : '') || val('other_payment_mode'), modeName = PAY[mode] ? PAY[mode][1] : '';
        var dueDate = val('due_date10');
        if (op === 'other') {
            var tName = val('payment_type') === 'miscellaneous' && String(val('payment_for') || '').trim() ? String(val('payment_for')).trim() : optText('payment_type'), fees = num('fees');
            r.lines = '<li><span>Payment type</span><b>' + esc(tName || 'Not chosen') + '</b></li>' + line('Amount', fees, 'is-sub');
            r.k = 'Pay now'; r.v = fees; r.s = modeName ? 'Paid ' + modeName.toLowerCase() : 'Choose how it is paid'; r.tone = fees ? '' : 'is-zero';
            r.after.push(['circle-check', 'Adds to this learner’s transactions & your revenue', 'is-ok']);
            r.steps = [['Pick what the payment is for', !!val('payment_type')], ['Enter the amount', fees > 0], ['Choose payment mode', !!mode]];
            r.footK = 'Pay now'; r.footV = money(fees);
            return r;
        }
        var price = num('plan_price10'), locker = num('locker_amount10');
        var hasLocker = !el('toggleFieldCheckbox10') || val('toggleFieldCheckbox10') === 'yes' || locker > 0;
        var disc = discountValue(price + (hasLocker ? locker : 0));
        var newTotal = Math.max(0, price + (hasLocker ? locker : 0) - disc);
        var pending = num('pending_amt10');
        var lines = line('Plan price', price, '', optText('plan_id10') ? optText('plan_id10') + ' · ' + optText('plan_type_id10') : (root.getAttribute('data-cur-plan') || ''));
        if (hasLocker && locker) lines += line('Locker', locker, 'is-plus');
        if (disc) lines += line('Discount', disc, 'is-minus');
        if (op === 'upgrade' || op === 'renew' || op === 'reactive') {
            var prevPending = num('previous_pending10'), paying = num('total_amount10');
            lines += line(op === 'renew' ? 'Next term total' : op === 'reactive' ? 'New term total' : 'New plan total', newTotal, 'is-sub');
            if (prevPending) lines += line('Old dues', prevPending, 'is-plus', 'Pending from earlier');
            r.lines = lines;
            if (mode === '3') {
                r.k = 'Pay later'; r.v = pending || (paying + prevPending); r.tone = 'is-later'; r.s = dueDate ? 'Due on ' + fmtDate(dueDate) : 'Pick a due date';
                r.footK = 'Pay later'; r.footV = money(r.v);
            } else {
                r.k = 'Pay now'; r.v = paying; r.tone = paying ? '' : 'is-zero'; r.s = modeName ? 'Paid ' + modeName.toLowerCase() : 'Choose how it is paid';
                r.footK = 'Pay now'; r.footV = money(paying);
                if (pending > 0) r.after.push(['clock-alert', money(pending) + ' stays pending' + (dueDate ? ' · due ' + fmtDate(dueDate) : ' — add a due date'), 'is-warn']);
            }
            if (!(pending > 0) && mode && mode !== '3') r.after.push(['circle-check', 'Fully paid — nothing pending', 'is-ok']);
            var needDue = mode === '3' || pending > 0;
            r.steps = [[op === 'renew' ? 'Plan & shift (locked)' : op === 'reactive' ? 'Choose plan, shift & seat' : 'Choose plan & shift', !!(val('plan_id10') && val('plan_type_id10'))], ['Check the amount', price > 0], ['Choose payment mode', !!mode], [needDue ? 'Set the due date' : 'Due date (not needed)', !needDue || !!dueDate]];
            return r;
        }
        // change / edit: compare with what was already paid
        var prevPaid = num('previous_amount10'), total = num('total_amount10') || newTotal;
        var diff = total - prevPaid, refund = diff < 0, timing = val('refund_pay_timing10');
        var nowAmt = num('diffrence_amount10');
        lines += line('New total', total, 'is-sub');
        lines += line('Already paid', prevPaid, 'is-minus');
        lines += '<li class="is-sub"><span>' + (refund ? 'To refund' : 'Difference to pay') + '</span><b>' + (refund ? '− ' : '') + money(diff) + '</b></li>';
        r.lines = lines;
        if (Math.round(diff) === 0) {
            r.k = 'Nothing to settle'; r.v = 0; r.tone = 'is-zero'; r.s = 'New plan costs the same';
            r.footK = 'Nothing to settle'; r.footV = money(0);
        } else if (timing === 'later') {
            r.k = refund ? 'Refund later' : 'Pay later'; r.v = pending || Math.abs(diff); r.tone = 'is-later';
            r.s = dueDate ? 'Due on ' + fmtDate(dueDate) : 'Pick a due date';
            r.footK = refund ? 'Refund later' : 'Pay later'; r.footV = money(r.v);
        } else {
            r.k = refund ? 'Refund now' : 'Pay now'; r.v = nowAmt; r.tone = refund ? 'is-refund' : (nowAmt ? '' : 'is-zero');
            r.s = timing === 'now' ? (modeName ? (refund ? 'Given ' : 'Paid ') + modeName.toLowerCase() : 'Choose payment mode') : 'Choose when to settle';
            r.footK = r.k; r.footV = money(nowAmt);
            if (pending > 0) r.after.push(['clock-alert', money(pending) + (refund ? ' refund' : '') + ' stays pending' + (dueDate ? ' · due ' + fmtDate(dueDate) : ''), 'is-warn']);
        }
        var needDue2 = timing === 'later' || mode === '3' || pending > 0;
        r.steps = op === 'edit'
            ? [['Check start & end dates', !!val('start_date10')], ['Choose when to settle', !!timing || Math.round(diff) === 0], ['Choose payment mode', !!mode], [needDue2 ? 'Set the due date' : 'Due date (not needed)', !needDue2 || !!dueDate]]
            : [['Choose new plan & shift', !!(val('plan_id10') && val('plan_type_id10'))], ['Choose when to settle', !!timing || Math.round(diff) === 0], ['Choose payment mode', !!mode], [needDue2 ? 'Set the due date' : 'Due date (not needed)', !needDue2 || !!dueDate]];
        return r;
    }

    function paintBill() {
        var r = compute();
        if (aside) {
            aside.querySelector('.lop-lines').innerHTML = r.lines;
            var box = aside.querySelector('.lop-due-box');
            box.className = 'lop-due-box ' + (r.tone || '');
            aside.querySelector('.lop-due-k').textContent = r.k;
            var v = aside.querySelector('.lop-due-v'), txt = money(r.v);
            if (v.textContent !== txt) { v.textContent = txt; if (lastDue) { v.classList.remove('is-bump'); void v.offsetWidth; v.classList.add('is-bump'); } lastDue = txt; }
            aside.querySelector('.lop-due-s').textContent = r.s;
            aside.querySelector('.lop-after').innerHTML = r.after.map(function (a) { return '<div class="' + a[2] + '">' + ic(a[0]) + '<span>' + esc(a[1]) + '</span></div>'; }).join('');
            var stepsOl = aside.querySelector('.lop-steps ol');
            if (stepsOl) stepsOl.innerHTML = r.steps.map(function (s, i) { return '<li class="' + (s[1] ? 'is-done' : '') + '"><i><span>' + (i + 1) + '</span></i>' + esc(s[0]) + '</li>'; }).join('');
        }
        if (due) { due.querySelector('span').textContent = r.footK; due.querySelector('strong').textContent = r.footV; }
    }

    function paintAll() {
        Object.keys(CHIPS).forEach(buildChips);
        paintJourney(); paintAddons(); paintSets(); paintPay(); paintSeats(); paintBill();
    }

    // values are filled by AJAX / other scripts without events, so repaint on input and on a light timer
    var raf = 0;
    function soon() { if (raf) return; raf = requestAnimationFrame(function () { raf = 0; paintAll(); }); }
    form.addEventListener('input', soon, true);
    form.addEventListener('change', function () { setTimeout(soon, 80); }, true);
    if ($) $(document).ajaxComplete(function () { setTimeout(soon, 30); });
    setInterval(function () { if (!document.hidden) paintAll(); }, 400);

    // submit: spinner instead of the icon, one tap only (only when no validator stopped it)
    form.addEventListener('submit', function (e) {
        setTimeout(function () {
            if (e.defaultPrevented) return;
            var b = form.querySelector('.form-action-bar [type="submit"]');
            if (b) { b.classList.add('is-busy'); b.setAttribute('disabled', 'disabled'); }
        }, 0);
    });
    window.addEventListener('pageshow', function (e) {
        if (!e.persisted) return;
        var b = form.querySelector('.form-action-bar [type="submit"]');
        if (b) { b.classList.remove('is-busy'); b.removeAttribute('disabled'); }
    });

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', paintAll); else paintAll();
})();
