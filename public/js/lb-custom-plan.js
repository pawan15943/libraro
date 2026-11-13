/* Libraro — custom plan (docs/CUSTOM_PLAN.md).
   The owner picks "Custom" in a plan dropdown and sets the end date in the form's own end date
   field. The server returns the hidden plan for that many days, which is added to the dropdown and
   selected, so the page's own price, end date, locker and seat code runs on it as on any other plan.
   The plan price becomes editable.
   - changing the end date changes the length (a new hidden plan)
   - changing the start date keeps the length and moves the end date
   The edit form has no plan dropdown: changing its end date makes the booking a custom plan.
   Markup: learner/partials/custom-plan-options + learner/partials/custom-plan-dates. */
(function () {
    'use strict';

    function q(sel) { return sel ? document.querySelector(sel) : null; }
    function parse(s) { var m = /^(\d{4})-(\d{2})-(\d{2})/.exec(s || ''); return m ? new Date(Date.UTC(+m[1], +m[2] - 1, +m[3])) : null; }
    function iso(d) { return d.toISOString().slice(0, 10); }
    function nice(s) { var d = parse(s); return d ? d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric', timeZone: 'UTC' }) : ''; }
    function addDays(s, n) { var d = parse(s); if (!d) return ''; d.setUTCDate(d.getUTCDate() + n); return iso(d); }
    function fire(el) { if (el) el.dispatchEvent(new Event('change', { bubbles: true })); }
    function token() { var m = q('meta[name="csrf-token"]') || q('meta[name="_token"]'); return m ? m.getAttribute('content') : ''; }

    function init(box) {
        var sel = q(box.getAttribute('data-select'));
        if (!sel || box.__lcp) return;
        box.__lcp = true;
        var isSelect = sel.tagName === 'SELECT';
        var startEl = q(box.getAttribute('data-start'));
        var priceEl = q(box.getAttribute('data-price'));
        var endEl = q(box.getAttribute('data-end')) || box.querySelector('[data-custom-end]');
        // read-only "valid till" text the end date input stands in for while a custom plan is picked
        var endShow = q(box.getAttribute('data-end-display'));
        var note = box.querySelector('[data-custom-note]');
        var locked = box.hasAttribute('data-locked');
        var url = box.getAttribute('data-url');
        var priceRO = !!(priceEl && priceEl.hasAttribute('readonly'));
        // hidden plan id -> days, for the edit form's hidden plan input (a select keeps it on the option)
        var days = {};
        if (box.getAttribute('data-current-days')) days[sel.value] = +box.getAttribute('data-current-days');
        var req = null;

        function startVal() { return (startEl && startEl.value) || box.getAttribute('data-start-date') || ''; }
        function opt() { return isSelect ? sel.options[sel.selectedIndex] : null; }
        function trigger() { return isSelect ? sel.querySelector('option[data-custom-trigger]') : null; }
        function daysOf(id) {
            if (isSelect) { var o = sel.querySelector('option[value="' + id + '"]'); return o ? +(o.getAttribute('data-days') || 0) : 0; }
            return days[id] || 0;
        }
        // off | pick (custom chosen, no end date yet) | plan (a hidden plan is selected)
        function mode() {
            if (!isSelect) return daysOf(sel.value) ? 'plan' : 'off';
            var o = opt();
            if (!o) return 'off';
            if (o.hasAttribute('data-custom-trigger')) return 'pick';
            return o.hasAttribute('data-custom-plan') ? 'plan' : 'off';
        }
        function say(text, bad) { if (note) { note.textContent = text || ''; note.classList.toggle('is-bad', !!bad); } }
        function sayPlan() {
            var n = daysOf(sel.value);
            say(n && startVal() && endEl && endEl.value ? 'Custom plan · ' + n + (n === 1 ? ' day' : ' days') + ' · ' + nice(startVal()) + ' – ' + nice(endEl.value) : '');
        }

        function paint() {
            var m = mode();
            if (isSelect) box.hidden = m === 'off';
            if (endEl && endShow) { endEl.hidden = m === 'off'; endShow.hidden = m !== 'off'; }
            if (endEl && isSelect) endEl.readOnly = locked;
            if (priceEl) {
                if (m === 'plan' && !locked) { priceEl.removeAttribute('readonly'); priceEl.classList.add('lcp-editable'); }
                else { if (priceRO) priceEl.setAttribute('readonly', 'readonly'); priceEl.classList.remove('lcp-editable'); }
            }
            if (m === 'plan') sayPlan();
            else if (m === 'pick') say(endEl && endEl.value ? '' : 'Pick the end date');
            else say('');
        }

        function select(id, name, n) {
            if (isSelect) {
                var o = sel.querySelector('option[value="' + id + '"]');
                if (!o) {
                    o = document.createElement('option');
                    o.value = id;
                    o.setAttribute('data-custom-plan', '1');
                    sel.insertBefore(o, trigger());
                }
                o.textContent = name;
                o.setAttribute('data-days', n);
                sel.value = String(id);
                fire(sel);
            } else {
                days[id] = n;
                sel.value = String(id);
                // the edit form reads the plan on a start date change (price + end date)
                fire(startEl);
            }
            paint();
        }

        function resolve() {
            if (locked) return;
            var start = startVal(), end = endEl ? endEl.value : '';
            if (!start || !end) { paint(); return; }
            if (req) req.abort();
            say('Working out the plan…');
            req = window.jQuery.ajax({
                url: url, type: 'POST', dataType: 'json', global: false,
                headers: { 'X-CSRF-TOKEN': token() },
                data: { plan_start_date: start, custom_end_date: end }
            }).done(function (r) {
                if (r && r.success) select(r.id, r.name, r.days);
            }).fail(function (x) {
                if (x.statusText === 'abort') return;
                var msg = (x.responseJSON && x.responseJSON.message) || 'Could not set the custom plan. Check the dates.';
                if (isSelect && mode() === 'plan') { var t = trigger(); if (t) { t.selected = true; fire(sel); } }
                say(msg, true);
            }).always(function () { req = null; });
        }

        sel.addEventListener('change', function () {
            var m = mode();
            if (m === 'pick') {
                if (priceEl) priceEl.value = '';
                if (endEl && endEl.value) { resolve(); return; }
            }
            if (m === 'plan' && endEl && !endEl.value && startVal()) endEl.value = addDays(startVal(), daysOf(sel.value) - 1);
            paint();
        });
        if (endEl) endEl.addEventListener('change', function () {
            // the edit form: any end date the owner types makes it a custom plan
            if (!isSelect || mode() !== 'off') resolve();
        });
        if (startEl) startEl.addEventListener('change', function () {
            var m = mode();
            if (m === 'plan' && endEl) { endEl.value = addDays(startVal(), daysOf(sel.value) - 1); paint(); }
            else if (m === 'pick' && endEl && endEl.value) resolve();
        });
        // an owner-typed price feeds the page's totals the same way a loaded price does
        if (priceEl) priceEl.addEventListener('input', function () {
            if (mode() !== 'plan') return;
            var clean = priceEl.value.replace(/[^0-9.]/g, '');
            if (clean !== priceEl.value) priceEl.value = clean;
            if (typeof window.calculatePaidAmount === 'function') window.calculatePaidAmount();
        });
        // the edit form has no plan dropdown: stop a save while the new end date is being worked out
        var form = sel.form || box.closest('form');
        if (form && !isSelect) form.addEventListener('submit', function (e) {
            if (req) {
                e.preventDefault(); e.stopImmediatePropagation();
                say('Wait a moment, working out the plan…', true);
            }
        }, true);

        if (!isSelect && endEl && !locked) { endEl.readOnly = false; endEl.removeAttribute('readonly'); }
        if (isSelect && mode() === 'plan' && endEl && !endEl.value && startVal()) endEl.value = addDays(startVal(), daysOf(sel.value) - 1);
        paint();
    }

    function boot() { Array.prototype.forEach.call(document.querySelectorAll('[data-custom-plan-box]'), init); }
    window.lbCustomPlanInit = boot;
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();
})();
