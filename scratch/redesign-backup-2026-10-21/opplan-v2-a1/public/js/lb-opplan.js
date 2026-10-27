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
    function el(id) { return document.getElementById(id); }
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
    var CHIPS = {
        plan_id10: { icon: 'calendar-days' },
        plan_type_id10: { icon: 'clock' },
        payment_mode10: { cards: true, map: PAY },
        other_payment_mode: { cards: true, map: PAY },
        refund_pay_timing10: { cards: true, map: { now: ['zap', 'Now', 'Settle it today'], later: ['calendar-clock', 'Later', 'Keep it as pending'] } },
        toggleFieldCheckbox10: { map: { no: ['ban', 'No locker'], yes: ['key-round', 'Locker'] } },
        discountType10: { withEmpty: 'No discount', map: { '': ['ban', 'No discount'], amount: ['indian-rupee', '₹ Amount'], percentage: ['tag', '% Percent'] } },
        payment_type: { cards: true, map: { token_money: ['badge-indian-rupee', 'Token money', 'Security deposit'], pending_refund: ['rotate-ccw', 'Refund to pay', 'Pay back the pending refund'], miscellaneous: ['receipt', 'Miscellaneous', 'Any other fee'] } }
    };
    var chipState = {};

    function buildChips(id) {
        var sel = el(id), cfg = CHIPS[id];
        if (!sel || sel.tagName !== 'SELECT') return;
        var opts = Array.prototype.filter.call(sel.options, function (o) { return o.value !== '' || cfg.withEmpty; });
        var locked = sel.hasAttribute('readonly') || sel.disabled;
        var sig = opts.map(function (o) { return o.value + '|' + o.text; }).join('~') + (locked ? '#L' : '');
        var st = chipState[id];
        if (!st || st.sig !== sig) {
            var wrap = st ? st.wrap : document.createElement('div');
            if (!st) {
                wrap.className = 'lop-chips' + (cfg.cards ? ' is-cards' : '');
                wrap.setAttribute('role', 'radiogroup');
                var lab = sel.id && form.querySelector('label[for="' + sel.id + '"]');
                if (lab) { if (!lab.id) lab.id = 'lopLab_' + id; wrap.setAttribute('aria-labelledby', lab.id); }
                sel.classList.add('lop-native');
                sel.setAttribute('tabindex', '-1');
                sel.insertAdjacentElement('afterend', wrap);
                wrap.addEventListener('click', function (e) {
                    var b = e.target.closest('.lop-chip'); if (!b || b.disabled) return;
                    if (sel.value === b.getAttribute('data-v')) return;
                    sel.value = b.getAttribute('data-v');
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
                var label = m ? m[1] : o.text.trim();
                var sub = m && m[2] ? '<small>' + esc(m[2]) + '</small>' : '';
                var icon = m ? m[0] : cfg.icon;
                return '<button type="button" class="lop-chip" role="radio" data-v="' + esc(o.value) + '"' + (locked ? ' disabled' : '') + '>' + (icon ? ic(icon) : '') + '<span>' + esc(label) + sub + '</span></button>';
            }).join('') : '<div class="lop-chips-empty">No options available</div>';
            chipState[id] = st = { wrap: wrap, sig: sig };
        }
        Array.prototype.forEach.call(st.wrap.querySelectorAll('.lop-chip'), function (b) {
            var on = b.getAttribute('data-v') === sel.value;
            b.classList.toggle('is-on', on);
            b.setAttribute('aria-checked', on ? 'true' : 'false');
            b.tabIndex = on || (!sel.value && b === st.wrap.querySelector('.lop-chip')) ? 0 : -1;
        });
        st.wrap.classList.toggle('is-invalid-ring', sel.classList.contains('is-invalid'));
    }

    /* ---------------- journey: current -> after ---------------- */
    var journey = null;
    if (op === 'upgrade' || op === 'renew' || op === 'change' || op === 'edit') {
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
        var nStart = op === 'edit' ? val('start_date10') : (root.getAttribute('data-new-start') || val('start_date10'));
        var nEnd = op === 'edit' ? val('plan_end_date_edit') : '';
        var billed = (el('chargeable_days10') && el('chargeable_days10').textContent.trim()) || '';
        var same = np === cp && ns === cs;
        var tag = op === 'renew' ? 'Next term' : op === 'edit' ? ((nStart !== cStart) ? 'Dates changed' : 'Same plan') : (same ? 'No change' : (np !== cp && ns !== cs ? 'Plan + shift' : np !== cp ? 'New plan' : 'New shift'));
        var nk = op === 'renew' ? 'Renews as' : op === 'edit' ? 'After edit' : 'Changes to';
        var cd = (cStart ? '<b>' + fmtDate(cStart) + '</b> → ' : '') + '<b>' + (fmtDate(cEnd) || '—') + '</b>';
        var nd = nStart ? ('Starts <b>' + fmtDate(nStart) + '</b>' + (nEnd ? ' · ends <b>' + fmtDate(nEnd) + '</b>' : '') + (billed ? ' · ' + esc(billed) : '')) : esc(billed);
        var key = [np, ns, nd, tag].join('|');
        var q = function (k) { return journey.querySelector('[data-j="' + k + '"]'); };
        q('cp').textContent = cp; q('cs').textContent = cs; q('cd').innerHTML = cd;
        q('nk').textContent = nk; q('np').textContent = np; q('ns').textContent = ns; q('nd').innerHTML = nd;
        var t = q('tag'); t.textContent = tag; t.classList.toggle('is-same', tag === 'No change' || tag === 'Same plan');
        if (lastJ && key !== lastJ) { var nb = journey.querySelector('.is-new'); nb.classList.remove('is-pulse'); void nb.offsetWidth; nb.classList.add('is-pulse'); }
        lastJ = key;
    }

    /* ---------------- add-on summary in the collapsible header ---------------- */
    var addonHead = form.querySelector('.collapsible-header');
    var addonSum = null;
    if (addonHead) {
        addonSum = document.createElement('span'); addonSum.className = 'lop-addon-sum';
        var tgl = addonHead.querySelector('.plan-header-toggle');
        addonHead.insertBefore(addonSum, tgl);
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

    /* ---------------- live bill + footer total ---------------- */
    var aside = el('lopAside');
    var bar = form.querySelector('.form-action-bar');
    var due = null;
    if (bar) { due = document.createElement('div'); due.className = 'lop-due'; due.innerHTML = '<span></span><strong></strong>'; bar.insertBefore(due, bar.firstChild); }
    if (aside) {
        aside.innerHTML = '<section class="lop-bill"><div class="lop-bill-h">' + ic('receipt-text') + '<h4>' + (op === 'other' ? 'Payment summary' : 'Bill summary') + '</h4><span>Live</span></div><ul class="lop-lines"></ul><div class="lop-due-box"><span class="lop-due-k"></span><strong class="lop-due-v"></strong><span class="lop-due-s"></span></div><div class="lop-after"></div></section>' +
            '<section class="lop-steps"><h5>Checklist</h5><ol></ol></section>';
    }
    var lastDue = '';
    function line(label, amount, cls, sub) { return '<li class="' + (cls || '') + '"><span>' + esc(label) + (sub ? '<small>' + esc(sub) + '</small>' : '') + '</span><b>' + (cls === 'is-minus' ? '− ' : cls === 'is-plus' ? '+ ' : '') + money(amount) + '</b></li>'; }

    function compute() {
        var r = { lines: '', k: '', v: 0, s: '', tone: '', after: [], steps: [], footK: '', footV: '' };
        var mode = val('payment_mode10') || val('other_payment_mode'), modeName = PAY[mode] ? PAY[mode][1] : '';
        var dueDate = val('due_date10');
        if (op === 'other') {
            var tName = optText('payment_type'), fees = num('fees');
            r.lines = '<li><span>Payment type</span><b>' + esc(tName || 'Not chosen') + '</b></li>' + line('Amount', fees, 'is-sub');
            r.k = 'Collect now'; r.v = fees; r.s = modeName ? 'Paid ' + modeName.toLowerCase() : 'Choose how it is paid'; r.tone = fees ? '' : 'is-zero';
            r.after.push(['circle-check', 'Adds to this learner’s transactions & your revenue', 'is-ok']);
            r.steps = [['Pick what the payment is for', !!val('payment_type')], ['Enter the amount', fees > 0], ['Choose payment mode', !!mode]];
            r.footK = 'Collect now'; r.footV = money(fees);
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
        if (op === 'upgrade' || op === 'renew') {
            var prevPending = num('previous_pending10'), paying = num('total_amount10');
            lines += line(op === 'renew' ? 'Next term total' : 'New plan total', newTotal, 'is-sub');
            if (prevPending) lines += line('Old dues', prevPending, 'is-plus', 'Pending from earlier');
            r.lines = lines;
            if (mode === '3') {
                r.k = 'Pay later'; r.v = pending || (paying + prevPending); r.tone = 'is-later'; r.s = dueDate ? 'Due on ' + fmtDate(dueDate) : 'Pick a due date';
                r.footK = 'Pay later · due'; r.footV = money(r.v);
            } else {
                r.k = 'Collect now'; r.v = paying; r.tone = paying ? '' : 'is-zero'; r.s = modeName ? 'Paid ' + modeName.toLowerCase() : 'Choose how it is paid';
                r.footK = 'Collect now'; r.footV = money(paying);
                if (pending > 0) r.after.push(['clock-alert', money(pending) + ' stays pending' + (dueDate ? ' · due ' + fmtDate(dueDate) : ' — add a due date'), 'is-warn']);
            }
            if (!(pending > 0) && mode && mode !== '3') r.after.push(['circle-check', 'Fully paid — nothing pending', 'is-ok']);
            var needDue = mode === '3' || pending > 0;
            r.steps = [[op === 'renew' ? 'Plan & shift (locked)' : 'Choose plan & shift', !!(val('plan_id10') && val('plan_type_id10'))], ['Check the amount', price > 0], ['Choose payment mode', !!mode], [needDue ? 'Set the due date' : 'Due date (not needed)', !needDue || !!dueDate]];
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
            r.footK = (refund ? 'Refund' : 'Pending') + ' · later'; r.footV = money(r.v);
        } else {
            r.k = refund ? 'Refund now' : 'Collect now'; r.v = nowAmt; r.tone = refund ? 'is-refund' : (nowAmt ? '' : 'is-zero');
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
            aside.querySelector('.lop-steps ol').innerHTML = r.steps.map(function (s, i) { return '<li class="' + (s[1] ? 'is-done' : '') + '"><i><span>' + (i + 1) + '</span></i>' + esc(s[0]) + '</li>'; }).join('');
        }
        if (due) { due.querySelector('span').textContent = r.footK; due.querySelector('strong').textContent = r.footV; }
    }

    function paintAll() {
        Object.keys(CHIPS).forEach(buildChips);
        paintJourney(); paintAddons(); paintBill();
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
