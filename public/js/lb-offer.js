/*
 * Global offer on the booking forms (docs/GLOBAL_DISCOUNT.md).
 *
 * The server decides and applies the offer (App\Services\GlobalDiscountService). This script only
 * mirrors it on screen: it writes the offer into each form's own discount fields, so the totals,
 * "paying now" and due amounts the form already works out include it, and shows a card with the
 * original price, the offer and the payable amount. No code or manual entry is needed.
 *
 * A form is described by selectors (resources/views/partials/global-offer.blade.php):
 *   { form, price, type, value, typeName, valueName, locker, lockerToggle, anchor }
 * If a manual discount on the form is bigger than the offer (e.g. carried over on change plan),
 * that discount stays, the same rule as the server (the bigger of the two, never both).
 */
(function () {
    if (window.__lbOfferInit) return;
    window.__lbOfferInit = true;

    function num(v) { v = parseFloat(String(v == null ? '' : v).replace(/,/g, '')); return isFinite(v) ? v : 0; }
    function r2(n) { return Math.round(n * 100) / 100; }
    function money(n) { return '₹' + r2(n).toLocaleString('en-IN', { maximumFractionDigits: 2 }); }
    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
    function fmtDate(d) { var m = /^(\d{4})-(\d{2})-(\d{2})/.exec(d || ''); return m ? new Date(+m[1], +m[2] - 1, +m[3]).toLocaleDateString('en-IN', { day: 'numeric', month: 'short' }) : ''; }
    // native events reach both addEventListener and jQuery .on() handlers, so the page's own totals update
    function fire(el, names) { names.forEach(function (n) { el.dispatchEvent(new Event(n, { bubbles: true })); }); }

    function start() {
        var O = window.LB_OFFER;
        if (!O) return;
        var icon = function (n) { return '<svg class="lb-i" aria-hidden="true"><use href="' + esc(O.sprite || '') + '#i-' + n + '"></use></svg>'; };

        function offAmount(price) {
            if (price <= 0) return 0;
            var off = O.discount_type === 'percentage' ? price * Math.min(100, Math.max(0, O.discount_value)) / 100 : Math.max(0, O.discount_value);
            return r2(Math.min(off, price));
        }

        function setup(cfg) {
            var priceEl = document.querySelector(cfg.price);
            var form = cfg.form ? document.querySelector(cfg.form) : (priceEl && priceEl.closest('form'));
            if (!form || form.__lbOffer) return;
            form.__lbOffer = cfg;
            var q = function (sel) { return sel ? form.querySelector(sel) || document.querySelector(sel) : null; };
            var price = q(cfg.price);
            if (!price) return;

            // the library may hide the discount fields (Show / Hide Options): add hidden ones so the offer still posts
            var type = q(cfg.type), value = q(cfg.value);
            if (!type) { type = document.createElement('input'); type.type = 'hidden'; type.id = cfg.type.replace('#', ''); type.name = cfg.typeName || 'discountType'; form.appendChild(type); }
            if (!value) { value = document.createElement('input'); value.type = 'hidden'; value.id = cfg.value.replace('#', ''); value.name = cfg.valueName || 'discount_amount'; form.appendChild(value); }

            var card = document.createElement('div');
            card.className = 'lbo-card'; card.setAttribute('role', 'status'); card.hidden = true;
            var anchor = q(cfg.anchor) || price;
            var row = cfg.anchor ? anchor : (anchor.closest('.row') || anchor.parentNode);
            if (row && row !== form && form.contains(row)) row.insertAdjacentElement('afterend', card);
            else form.insertBefore(card, form.firstChild);

            var written = null; // what this script last put in the fields
            var manual = null;  // a discount the page itself put there (carried over / typed before)
            var busy = false, locked = false;

            function lockerNow() {
                var amt = q(cfg.locker), tg = q(cfg.lockerToggle);
                if (!amt) return 0;
                return tg ? (tg.value === 'yes' ? num(amt.value) : 0) : num(amt.value);
            }
            function lock(on) {
                if (on) value.readOnly = true;
                else if (locked) value.readOnly = !type.value; // as the page has it: editable once a type is chosen
                locked = on;
                type.classList.toggle('lbo-locked', on); value.classList.toggle('lbo-locked', on);
                if (on) { type.setAttribute('tabindex', '-1'); type.setAttribute('aria-disabled', 'true'); }
                else { type.removeAttribute('tabindex'); type.removeAttribute('aria-disabled'); }
            }

            function sync() {
                if (busy) return;
                busy = true;
                try { run(); } finally { busy = false; }
            }

            function run() {
                var p = num(price.value), locker = lockerNow(), off = offAmount(p);
                var cur = { t: type.value || '', v: String(value.value || '') };
                if (!written || cur.t !== written.t || num(cur.v) !== num(written.v)) {
                    // someone else wrote the fields: keep it as the manual discount
                    manual = cur.t && num(cur.v) > 0 ? cur : null;
                    written = null;
                }
                var manualAmt = manual ? (manual.t === 'percentage' ? (p + locker) * num(manual.v) / 100 : num(manual.v)) : 0;

                if (off <= 0 || manualAmt > off + 0.009) {
                    if (written) { // give the fields back
                        type.value = manual ? manual.t : ''; fire(type, ['change']);
                        value.value = manual ? manual.v : ''; fire(value, ['input', 'change']);
                        written = null;
                    }
                    lock(false);
                    paint(p, locker, off, manualAmt);
                    return;
                }

                var want = String(off % 1 === 0 ? off : off.toFixed(2));
                if (type.value !== 'amount') { type.value = 'amount'; fire(type, ['change']); }
                if (num(value.value) !== off) { value.value = want; fire(value, ['input', 'change']); }
                written = { t: 'amount', v: want };
                lock(true);
                paint(p, locker, off, 0);
            }

            function paint(p, locker, off, manualAmt) {
                if (p <= 0) { card.hidden = true; return; }
                card.hidden = false;
                var head = '<span class="lbo-ic">' + icon('party-popper') + '</span><div class="lbo-t"><b>' + esc(O.name) + ' · ' + esc(O.label) + '</b>';
                if (manualAmt > off + 0.009) {
                    card.className = 'lbo-card is-muted';
                    card.innerHTML = head + '<small>The manual discount of ' + money(manualAmt) + ' is bigger than this offer (' + money(off) + '), so it stays.</small></div>';
                    return;
                }
                card.className = 'lbo-card';
                var gross = p + locker;
                card.innerHTML = head + '<small>Applied automatically' + (O.end_date ? ' · till ' + esc(fmtDate(O.end_date)) : '') + '</small></div>' +
                    '<dl class="lbo-sum">' +
                    '<div><dt>Original price</dt><dd>' + money(gross) + '</dd></div>' +
                    '<div class="is-off"><dt>Discount</dt><dd>− ' + money(off) + '</dd></div>' +
                    '<div class="is-pay"><dt>Final payable</dt><dd>' + money(Math.max(0, gross - off)) + '</dd></div>' +
                    '</dl>';
            }

            form.addEventListener('input', sync, true);
            form.addEventListener('change', function (e) { if (e.target !== type && e.target !== value) sync(); }, true);
            form.addEventListener('reset', function () { written = null; manual = null; setTimeout(sync, 0); });
            // prices are often filled in by other scripts without an event
            setInterval(function () { if (form.isConnected) sync(); }, 600);
            sync();
        }

        (window.LB_OFFER_FORMS || []).forEach(setup);
        window.LB_OFFER_FORMS = { push: setup };
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
    else start();
})();
