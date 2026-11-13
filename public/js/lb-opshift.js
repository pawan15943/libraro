/* Libraro — plan pages (upgrade, change plan, reactivate, edit): advanced shifts (docs/ADVANCED_SHIFTS.md).
   - a duration/free main shift shows its timing fields (posted as shift_start_time / shift_end_time);
     a free shift's price is typed by the owner
   - fixed shifts on the same seat can be added to the booking: they post as
     extra_shifts[k][seat_no|plan_type_id|price] and their prices are added to the plan price
   The plan price field stays the booking total the form already posts (slot_prices_included):
   the price the page loads (data-base, kept extra shifts included) plus the added shifts.
   Markup: learner/partials/op-shift-extras. */
(function () {
    'use strict';

    function q(sel, root) { return sel ? (root || document).querySelector(sel) : null; }
    function num(v) { var n = parseFloat(String(v == null ? '' : v).replace(/[^0-9.\-]/g, '')); return isNaN(n) ? 0 : n; }
    function esc(v) { return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
    function toMin(t) { var x = /^(\d{1,2}):(\d{2})/.exec(t || ''); return x ? (+x[1]) * 60 + (+x[2]) : null; }
    function fromMin(m) { m = ((m % 1440) + 1440) % 1440; var p = function (n) { return (n < 10 ? '0' : '') + n; }; return p(Math.floor(m / 60)) + ':' + p(m % 60); }
    function label(t) { var m = toMin(t); if (m === null) return ''; var h = Math.floor(m / 60) % 24, mm = m % 60; return (h % 12 || 12) + (mm ? ':' + (mm < 10 ? '0' : '') + mm : '') + ' ' + (h < 12 ? 'AM' : 'PM'); }
    function overlap(a, b) {
        if (!a || !b) return false;
        var s1 = toMin(a[0]), e1 = toMin(a[1]), s2 = toMin(b[0]), e2 = toMin(b[1]);
        if (s1 === null || e1 === null || s2 === null || e2 === null) return false;
        if (e1 <= s1) e1 += 1440;
        if (e2 <= s2) e2 += 1440;
        for (var o = -1440; o <= 1440; o += 1440) if (s1 < e2 + o && e1 > s2 + o) return true;
        return false;
    }
    function fire(el, type) { if (el) el.dispatchEvent(new Event(type || 'change', { bubbles: true })); }

    function init(box) {
        var form = box.closest('form') || document;
        var sel = q(box.getAttribute('data-select'), form) || q(box.getAttribute('data-select'));
        var planEl = q(box.getAttribute('data-plan'), form) || q(box.getAttribute('data-plan'));
        var startEl = q(box.getAttribute('data-start'), form) || q(box.getAttribute('data-start'));
        var priceEl = q(box.getAttribute('data-price'), form) || q(box.getAttribute('data-price'));
        var seatInput = q(box.getAttribute('data-seat-input'));
        if (!sel || !priceEl || box.__los) return;
        box.__los = true;
        var $ = window.jQuery;
        var times = {}, cands = [];
        try { times = JSON.parse(box.getAttribute('data-times') || '{}') || {}; } catch (e) {}
        try { cands = JSON.parse(box.getAttribute('data-candidates') || '[]') || []; } catch (e) {}
        var tBox = q('[data-os-timing]', box), tStart = q('#osShiftStart', box), tEnd = q('#osShiftEnd', box), tNote = q('[data-os-timing-note]', box);
        var addBox = q('[data-os-add]', box), list = q('[data-os-list]', box), inputs = q('[data-os-inputs]', box);
        var priceRO = priceEl.hasAttribute('readonly');
        var picked = [], prices = {}, base = null;

        function seat() { return seatInput ? String(seatInput.value || '') : String(box.getAttribute('data-seat') || ''); }
        function opt() { return sel.tagName === 'SELECT' ? sel.options[sel.selectedIndex] : null; }
        function mode() { var o = opt(); return o ? (o.getAttribute('data-mode') || 'fixed') : 'fixed'; }
        function mainId() { return String(sel.value || ''); }
        function mainTimes() {
            var o = opt(), m = mode();
            if (m === 'duration' || m === 'free') return tStart && tStart.value && tEnd && tEnd.value ? [tStart.value, tEnd.value] : null;
            if (o && o.getAttribute('data-s')) return [o.getAttribute('data-s'), o.getAttribute('data-e')];
            return times[mainId()] || null;
        }
        // extra shifts the booking keeps (learner/partials/extra-shifts-carry), same seat only
        function keptTimes() {
            var out = [];
            Array.prototype.forEach.call(form.querySelectorAll('[data-carry-slot]'), function (row) {
                var cb = row.querySelector('input[name="drop_slot_ids[]"]');
                if (row.getAttribute('data-same-seat') === '1' && !(cb && cb.checked)) out.push(times[row.getAttribute('data-pt')]);
            });
            return out;
        }
        function customPlan() { var o = planEl && planEl.tagName === 'SELECT' ? planEl.options[planEl.selectedIndex] : null; return !!(o && o.hasAttribute('data-custom-plan')); }
        function canAdd() { return Number(seat()) > 0 && mode() === 'fixed' && !!mainId(); }
        function extrasTotal() { var t = 0; picked.forEach(function (v) { t += num(prices[v]); }); return t; }

        /* ---- total: loaded price (data-base) + added shifts ---- */
        // base is taken from the field the first time a shift is added, then kept by lopExtrasHook
        function writeTotal() {
            if (base === null) return;
            priceEl.value = String(Math.round((base + extrasTotal()) * 100) / 100);
            if (typeof window.calculatePaidAmount === 'function') window.calculatePaidAmount();
        }
        // learner/script.blade.php getPlanPriceAmount() calls this after loading the plan price
        var prevHook = window.lopExtrasHook;
        window.lopExtrasHook = function () {
            if (typeof prevHook === 'function') prevHook();
            base = num(priceEl.getAttribute('data-base'));
            writeTotal();
        };
        priceEl.addEventListener('input', function () {
            if (priceEl.readOnly) return;
            base = num(priceEl.value) - extrasTotal();
            if (typeof window.calculatePaidAmount === 'function') window.calculatePaidAmount();
        });

        /* ---- price lock: editable for a custom plan or a free shift ---- */
        function syncLock() {
            var open = customPlan() || mode() === 'free';
            if (open) { priceEl.removeAttribute('readonly'); priceEl.classList.add('lcp-editable'); }
            else if (priceRO) { priceEl.setAttribute('readonly', 'readonly'); priceEl.classList.remove('lcp-editable'); }
        }

        /* ---- timing for duration / free ---- */
        function syncEnd() {
            var o = opt();
            if (mode() !== 'duration' || !tStart || !tEnd) return;
            var s = toMin(tStart.value), h = num(o && o.getAttribute('data-hours'));
            tEnd.value = s === null ? '' : fromMin(s + Math.round(h * 60));
        }
        function paintTiming() {
            if (!tBox) return;
            var m = mode(), on = m === 'duration' || m === 'free', o = opt();
            tBox.hidden = !on;
            tStart.disabled = !on; tEnd.disabled = !on;
            tEnd.readOnly = m === 'duration';
            if (!on) return;
            syncEnd();
            var anyTime = o && String(o.getAttribute('data-s') || '').slice(0, 5) === '00:00' && String(o.getAttribute('data-e') || '').slice(0, 5) === '00:00';
            var w = o ? (label(o.getAttribute('data-s')) + ' – ' + label(o.getAttribute('data-e'))) : '';
            tNote.textContent = (m === 'duration' ? num(o.getAttribute('data-hours')) + ' hr shift. ' : 'Set the time and the price. ') + (anyTime ? 'Any time of the day.' : (w.trim() !== '–' ? 'Allowed ' + w + '.' : ''));
        }

        /* ---- added shifts ---- */
        function blocked(c) {
            if (!c.free) return 'Not free';
            if (String(c.id) === mainId()) return 'Main shift';
            var t = times[c.id], main = mainTimes();
            if (main && overlap(t, main)) return 'Overlaps main shift';
            if (keptTimes().some(function (k) { return overlap(t, k); })) return 'Overlaps a kept shift';
            var other = picked.filter(function (v) { return v !== String(c.id); });
            if (other.some(function (v) { return overlap(t, times[v]); })) return 'Overlaps an added shift';
            return '';
        }
        function writeInputs() {
            var h = '', st = seat();
            picked.forEach(function (v, k) {
                h += '<input type="hidden" name="extra_shifts[' + k + '][seat_no]" value="' + esc(st) + '">' +
                     '<input type="hidden" name="extra_shifts[' + k + '][plan_type_id]" value="' + esc(v) + '">' +
                     '<input type="hidden" name="extra_shifts[' + k + '][price]" value="' + esc(prices[v] == null ? '' : prices[v]) + '">';
            });
            inputs.innerHTML = h;
        }
        function paintAdd() {
            if (!addBox) return;
            var ok = canAdd();
            if (!ok && picked.length) { picked = []; prices = {}; writeInputs(); writeTotal(); }
            // drop added shifts that no longer fit (new main shift, kept shift put back)
            var keep = picked.filter(function (v) { var c = cands.filter(function (x) { return String(x.id) === v; })[0]; return c && !blocked(c); });
            if (keep.length !== picked.length) { picked = keep; writeInputs(); writeTotal(); }
            // shifts this seat can't take are already shown as "Not free" in the shift list above
            var rows = cands.filter(function (c) { return String(c.id) !== mainId() && c.free; });
            addBox.hidden = !ok || !rows.length;
            if (addBox.hidden) return;
            list.innerHTML = rows.map(function (c) {
                var on = picked.indexOf(String(c.id)) >= 0, why = on ? '' : blocked(c);
                var sub = on ? (prices[c.id] == null ? 'Loading price…' : c.time + ' · ₹' + num(prices[c.id]).toLocaleString('en-IN')) : (why || c.time);
                return '<button type="button" class="lop-chip' + (on ? ' is-on' : '') + '" data-os-v="' + esc(c.id) + '" aria-pressed="' + on + '"' + (why ? ' disabled' : '') + '><span>' + esc(c.name) + '<small>' + esc(sub) + '</small></span></button>';
            }).join('');
        }
        function loadPrice(v) {
            prices[v] = null;
            var plan = planEl ? planEl.value : '';
            if (!plan || !$) { prices[v] = 0; writeInputs(); writeTotal(); paintAdd(); return; }
            $.ajax({ url: box.getAttribute('data-price-url'), type: 'GET', dataType: 'json', global: false,
                data: { plan_type_id: v, plan_id: plan, plan_start_date: startEl ? startEl.value : '' } })
                .done(function (p) { prices[v] = num(p); })
                .fail(function () { prices[v] = 0; })
                .always(function () { if (picked.indexOf(v) >= 0) { writeInputs(); writeTotal(); paintAdd(); } });
        }
        if (list) list.addEventListener('click', function (e) {
            var b = e.target.closest('[data-os-v]'); if (!b || b.disabled) return;
            var v = b.getAttribute('data-os-v'), i = picked.indexOf(v);
            if (i >= 0) { picked.splice(i, 1); delete prices[v]; writeInputs(); writeTotal(); }
            else { if (base === null) base = num(priceEl.value); picked.push(v); writeInputs(); loadPrice(v); }
            paintAdd();
        });

        /* ---- reactivate: the seat decides which shifts are free ---- */
        function refreshSeat() {
            if (!seatInput || !$) return;
            var st = seat();
            $.ajax({ url: box.getAttribute('data-seats-url'), type: 'GET', dataType: 'json', global: false, data: { seatNo: st, with_templates: 1 } })
                .done(function (rows) {
                    var free = {};
                    (Array.isArray(rows) ? rows : []).forEach(function (r) { if (r && r.id) free[String(r.id)] = true; });
                    var numbered = Number(st) > 0;
                    Array.prototype.forEach.call(sel.options, function (o) {
                        if (!o.value) return;
                        var m = o.getAttribute('data-mode') || 'fixed', okSeat = m === 'flexible' ? !numbered : (m === 'fixed' ? true : numbered);
                        var on = okSeat && (m === 'flexible' || !!free[o.value]);
                        o.disabled = !on;
                        if (m === 'fixed') o.setAttribute('data-sub', on ? (o.getAttribute('data-time') || '') : 'Not free');
                        else if (!okSeat) o.setAttribute('data-sub', numbered ? 'General seat only' : 'Numbered seat only');
                    });
                    if (opt() && opt().disabled) { sel.value = ''; fire(sel); }
                    cands.forEach(function (c) { c.free = !!free[String(c.id)]; });
                    paintAll();
                });
        }

        function paintAll() { syncLock(); paintTiming(); paintAdd(); }

        sel.addEventListener('change', function () { if (tStart) { tStart.value = ''; tEnd.value = ''; } paintAll(); });
        if (planEl) planEl.addEventListener('change', function () { picked.forEach(loadPrice); setTimeout(syncLock, 0); });
        if (startEl) startEl.addEventListener('change', function () { picked.forEach(loadPrice); });
        if (tStart) {
            tStart.addEventListener('change', function () { syncEnd(); paintAdd(); });
            tEnd.addEventListener('change', function () { paintAdd(); });
        }
        if (seatInput) seatInput.addEventListener('change', function () { picked = []; prices = {}; writeInputs(); writeTotal(); refreshSeat(); });
        form.addEventListener('change', function (e) { if (e.target && e.target.name === 'drop_slot_ids[]') setTimeout(paintAdd, 0); });
        // the custom plan script re-locks the price on a plan change; keep a free shift's price open
        setInterval(function () { if (mode() === 'free' && priceEl.readOnly) syncLock(); }, 500);

        paintAll();
        if (seatInput) refreshSeat();
    }

    function boot() { Array.prototype.forEach.call(document.querySelectorAll('[data-op-shifts]'), init); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();
})();
