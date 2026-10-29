/*
 * Libraro time picker.
 *
 * Any <input data-lb-time> becomes a friendly "6:30 AM" field with a picker
 * (hour, minute, AM/PM). The input itself keeps the value as "HH:MM" (24 hour),
 * so forms, validation and existing scripts keep reading it as before:
 *   - setting input.value / $(input).val() from code updates the field,
 *   - picking a time fires native "input" and "change" events on the input,
 *   - disabled / readonly on the input are mirrored on the field.
 *
 * Options (data attributes on the input):
 *   data-lb-time-step="15"        minute step (default 5)
 *   data-lb-time-class="ob-select" classes for the field (default: form-control when the input has it)
 *   data-lb-time-placeholder="..." text when empty
 *   data-lb-time-clear="0"         hide the Clear button (hidden anyway for required inputs)
 *   data-lb-time-icon="0"          no clock icon in the field
 *
 * Inputs added later (cloned rows, modals) are picked up automatically.
 * window.LbTime.attach(input) / LbTime.refresh(input) are there for manual use.
 */
(function () {
    'use strict';
    if (window.LbTime) return;

    var NS = 'http://www.w3.org/2000/svg';
    var bound = new WeakMap();
    var valueDesc = Object.getOwnPropertyDescriptor(HTMLInputElement.prototype, 'value');
    var pop = null, state = null;

    function pad(n) { return (n < 10 ? '0' : '') + n; }

    // "6:30", "06:30", "06:30:00", "6:30 pm" -> minutes since midnight, or null.
    function parse(v) {
        var m = String(v || '').trim().match(/^(\d{1,2}):(\d{2})(?::\d{2})?\s*([ap]\.?m\.?)?$/i);
        if (!m) return null;
        var h = parseInt(m[1], 10), mi = parseInt(m[2], 10);
        if (m[3]) {
            if (h < 1 || h > 12) return null;
            h = h % 12 + (/^p/i.test(m[3]) ? 12 : 0);
        }
        if (h > 23 || mi > 59) return null;
        return h * 60 + mi;
    }
    // Stored value is always "HH:MM" when it reads as a time ("06:00:00" -> "06:00").
    function norm(v) {
        var m = parse(v);
        return m === null ? (v == null ? '' : v) : to24(m);
    }
    function to24(min) { return pad(Math.floor(min / 60)) + ':' + pad(min % 60); }
    function label(min) {
        var h = Math.floor(min / 60), h12 = h % 12 || 12;
        return h12 + ':' + pad(min % 60) + ' ' + (h < 12 ? 'AM' : 'PM');
    }
    function el(tag, cls, text) {
        var e = document.createElement(tag);
        if (cls) e.className = cls;
        if (text != null) e.textContent = text;
        return e;
    }
    function clockIcon() {
        var s = document.createElementNS(NS, 'svg');
        s.setAttribute('viewBox', '0 0 24 24');
        s.setAttribute('class', 'lbt-ico');
        s.setAttribute('aria-hidden', 'true');
        s.innerHTML = '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>';
        return s;
    }
    function fire(input, type) {
        input.dispatchEvent(new Event(type, { bubbles: true }));
    }
    function opt(input, name, def) {
        var v = input.getAttribute('data-lb-time-' + name);
        return v === null ? def : v;
    }

    /* ---------- Field ---------- */

    function attach(input) {
        if (!input || input.tagName !== 'INPUT' || bound.has(input)) return;

        // A cloned row carries a copy of the old field: drop it, this input gets its own.
        var next = input.nextElementSibling;
        if (next && next.classList.contains('lbt-field')) next.remove();

        if (input.type === 'time') input.type = 'text';
        input.classList.add('lbt-native');
        input.setAttribute('tabindex', '-1');
        input.setAttribute('aria-hidden', 'true');
        input.autocomplete = 'off';

        var field = el('button', 'lbt-field');
        field.type = 'button';
        var cls = opt(input, 'class', input.classList.contains('form-control') ? 'form-control' : '');
        if (cls) cls.split(/\s+/).forEach(function (c) { if (c) field.classList.add(c); });
        if (opt(input, 'icon', '1') !== '0') field.appendChild(clockIcon());
        var text = el('span', 'lbt-text');
        field.appendChild(text);
        field.setAttribute('aria-haspopup', 'dialog');
        if (input.id) {
            field.id = input.id + '_lbt';
            var lab = document.querySelector('label[for="' + input.id + '"]');
            if (lab) { lab.setAttribute('for', field.id); }
        }
        input.parentNode.insertBefore(field, input.nextSibling);

        var rec = { input: input, field: field, text: text };
        bound.set(input, rec);

        // Code that sets input.value (or jQuery .val()) repaints the field.
        Object.defineProperty(input, 'value', {
            configurable: true,
            get: function () { return valueDesc.get.call(this); },
            set: function (v) { valueDesc.set.call(this, norm(v)); paint(rec); }
        });
        valueDesc.set.call(input, norm(valueDesc.get.call(input)));
        if (input.form) input.form.addEventListener('reset', function () { setTimeout(function () { paint(rec); }); });
        new MutationObserver(function () { paint(rec); })
            .observe(input, { attributes: true, attributeFilter: ['disabled', 'readonly', 'placeholder', 'class'] });

        field.addEventListener('click', function () {
            if (state && state.rec === rec) { close(); return; }
            open(rec);
        });
        field.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowDown' || e.key === 'ArrowUp') { e.preventDefault(); open(rec); }
            if ((e.key === 'Backspace' || e.key === 'Delete') && canClear(rec) && input.value) {
                e.preventDefault(); commit(rec, null);
            }
        });
        // Old-style "label wraps input" or code that focuses the input opens the picker.
        input.addEventListener('focus', function () { if (!field.disabled) field.focus(); });

        paint(rec);
    }

    function paint(rec) {
        var input = rec.input, field = rec.field;
        var min = parse(valueDesc.get.call(input));
        var off = input.disabled || input.readOnly;
        field.disabled = input.disabled;
        field.classList.toggle('is-readonly', input.readOnly && !input.disabled);
        field.classList.toggle('is-empty', min === null);
        field.classList.toggle('is-invalid', input.classList.contains('is-invalid'));
        rec.text.textContent = min === null
            ? opt(input, 'placeholder', input.getAttribute('placeholder') && !/^hh/i.test(input.getAttribute('placeholder')) ? input.getAttribute('placeholder') : 'Select time')
            : label(min);
        field.setAttribute('aria-label', (input.getAttribute('aria-label') || input.name || 'Time') + ': ' + rec.text.textContent);
        if (off && state && state.rec === rec) close();
    }

    function canClear(rec) {
        return !rec.input.required && opt(rec.input, 'clear', '1') !== '0';
    }

    function commit(rec, min) {
        var v = min === null ? '' : to24(min);
        if (valueDesc.get.call(rec.input) === v) { paint(rec); return; }
        rec.input.value = v;
        fire(rec.input, 'input');
        fire(rec.input, 'change');
    }

    /* ---------- Popover ---------- */

    function build() {
        pop = el('div', 'lbt-pop');
        pop.setAttribute('role', 'dialog');
        pop.setAttribute('aria-label', 'Choose time');
        pop.innerHTML =
            '<div class="lbt-head">' +
                '<div class="lbt-read"><b class="lbt-rh">--</b><i>:</i><b class="lbt-rm">--</b></div>' +
                '<div class="lbt-ap" role="group" aria-label="AM or PM">' +
                    '<button type="button" data-ap="0">AM</button><button type="button" data-ap="1">PM</button>' +
                '</div>' +
            '</div>' +
            '<div class="lbt-sec"><span>Hour</span></div>' +
            '<div class="lbt-grid lbt-hours"></div>' +
            '<div class="lbt-sec"><span>Minutes</span></div>' +
            '<div class="lbt-grid lbt-mins"></div>' +
            '<div class="lbt-foot">' +
                '<button type="button" class="lbt-btn lbt-clear">Clear</button>' +
                '<button type="button" class="lbt-btn lbt-now">Now</button>' +
                '<button type="button" class="lbt-btn lbt-ok">Set time</button>' +
            '</div>';
        var hours = pop.querySelector('.lbt-hours');
        for (var h = 1; h <= 12; h++) {
            var b = el('button', 'lbt-cell', String(h));
            b.type = 'button';
            b.setAttribute('data-h', h % 12);
            hours.appendChild(b);
        }

        pop.addEventListener('mousedown', function (e) { e.stopPropagation(); });
        pop.addEventListener('click', function (e) {
            var t = e.target.closest('button');
            if (!t || !state) return;
            var d = state.draft, h = Math.floor(d / 60), m = d % 60;
            if (t.hasAttribute('data-h')) set((parseInt(t.getAttribute('data-h'), 10) + (h >= 12 ? 12 : 0)) * 60 + m);
            else if (t.hasAttribute('data-m')) set(h * 60 + parseInt(t.getAttribute('data-m'), 10));
            else if (t.hasAttribute('data-ap')) set((h % 12 + (t.getAttribute('data-ap') === '1' ? 12 : 0)) * 60 + m);
            else if (t.classList.contains('lbt-now')) { var n = new Date(); set(n.getHours() * 60 + n.getMinutes()); }
            else if (t.classList.contains('lbt-clear')) { var r = state.rec; close(); commit(r, null); }
            else if (t.classList.contains('lbt-ok')) { var r2 = state.rec, v = state.draft; close(true); commit(r2, v); }
        });
        pop.addEventListener('keydown', function (e) {
            if (!state) return;
            var step = state.step, d = state.draft;
            if (e.key === 'Escape') { e.preventDefault(); close(true); }
            else if (e.key === 'Enter' && !e.target.closest('.lbt-foot')) { e.preventDefault(); var r = state.rec; close(true); commit(r, d); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); set((d + 60) % 1440); }
            else if (e.key === 'ArrowDown') { e.preventDefault(); set((d + 1380) % 1440); }
            else if (e.key === 'ArrowRight') { e.preventDefault(); set((d + step) % 1440); }
            else if (e.key === 'ArrowLeft') { e.preventDefault(); set((d - step + 1440) % 1440); }
        });
    }

    function set(min) {
        state.draft = min;
        render();
    }

    function render() {
        var d = state.draft, h = Math.floor(d / 60), m = d % 60;
        pop.querySelector('.lbt-rh').textContent = String(h % 12 || 12);
        pop.querySelector('.lbt-rm').textContent = pad(m);
        pop.querySelectorAll('[data-ap]').forEach(function (b) {
            var on = (b.getAttribute('data-ap') === '1') === (h >= 12);
            b.classList.toggle('is-on', on);
            b.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
        pop.querySelectorAll('[data-h]').forEach(function (b) {
            b.classList.toggle('is-on', parseInt(b.getAttribute('data-h'), 10) === h % 12);
        });
        pop.querySelectorAll('[data-m]').forEach(function (b) {
            b.classList.toggle('is-on', parseInt(b.getAttribute('data-m'), 10) === m);
        });
        pop.querySelector('.lbt-ok').textContent = 'Set ' + label(d);
    }

    function open(rec) {
        var input = rec.input;
        if (input.disabled || input.readOnly) return;
        if (!pop) build();
        close();

        var step = Math.max(1, Math.min(30, parseInt(opt(input, 'step', '5'), 10) || 5));
        var mins = pop.querySelector('.lbt-mins');
        mins.innerHTML = '';
        mins.classList.toggle('is-few', 60 / step <= 4);
        for (var m = 0; m < 60; m += step) {
            var b = el('button', 'lbt-cell', pad(m));
            b.type = 'button';
            b.setAttribute('data-m', m);
            mins.appendChild(b);
        }

        var cur = parse(valueDesc.get.call(input));
        if (cur === null) {
            cur = parse(opt(input, 'default', ''));
            if (cur === null) {
                var n = new Date();
                cur = (n.getHours() * 60 + Math.round(n.getMinutes() / step) * step) % 1440;
            }
        }
        pop.querySelector('.lbt-clear').hidden = !canClear(rec) || !valueDesc.get.call(input);

        state = { rec: rec, draft: cur, step: step };
        render();

        // Inside a Bootstrap modal the picker lives in the modal, so its focus trap leaves it alone.
        var host = rec.field.closest('.modal') || document.body;
        host.appendChild(pop);
        pop.classList.add('is-open');
        rec.field.classList.add('is-open');
        rec.field.setAttribute('aria-expanded', 'true');
        place();

        var sel = pop.querySelector('.lbt-hours .is-on') || pop.querySelector('.lbt-ok');
        try { sel.focus({ preventScroll: true }); } catch (e) { sel.focus(); }

        document.addEventListener('mousedown', outside, true);
        document.addEventListener('touchstart', outside, true);
        window.addEventListener('resize', place);
        window.addEventListener('scroll', place, true);
    }

    function place() {
        if (!state) return;
        var r = state.rec.field.getBoundingClientRect();
        var vw = document.documentElement.clientWidth, vh = window.innerHeight;
        if (r.bottom < 0 || r.top > vh) { close(); return; }
        if (vw < 520) {
            pop.classList.add('is-sheet');
            pop.style.left = pop.style.top = '';
            return;
        }
        pop.classList.remove('is-sheet');
        var w = pop.offsetWidth, h = pop.offsetHeight;
        var left = Math.max(8, Math.min(r.left, vw - w - 8));
        var top = r.bottom + 6;
        if (top + h > vh - 8 && r.top - h - 6 >= 8) top = r.top - h - 6;
        pop.style.left = left + 'px';
        pop.style.top = Math.max(8, top) + 'px';
    }

    function outside(e) {
        if (!state) return;
        if (pop.contains(e.target) || state.rec.field.contains(e.target)) return;
        close();
    }

    function close(refocus) {
        if (!state) return;
        var field = state.rec.field;
        field.classList.remove('is-open');
        field.setAttribute('aria-expanded', 'false');
        pop.classList.remove('is-open');
        if (pop.parentNode) pop.parentNode.removeChild(pop);
        state = null;
        document.removeEventListener('mousedown', outside, true);
        document.removeEventListener('touchstart', outside, true);
        window.removeEventListener('resize', place);
        window.removeEventListener('scroll', place, true);
        if (refocus) field.focus();
    }

    /* ---------- Auto setup ---------- */

    function scan(root) {
        if (!root || !root.querySelectorAll) return;
        if (root.matches && root.matches('input[data-lb-time]')) attach(root);
        root.querySelectorAll('input[data-lb-time]').forEach(attach);
    }

    function start() {
        scan(document);
        new MutationObserver(function (list) {
            list.forEach(function (m) { m.addedNodes.forEach(function (n) { if (n.nodeType === 1) scan(n); }); });
        }).observe(document.body, { childList: true, subtree: true });
    }

    window.LbTime = {
        attach: attach,
        refresh: function (input) { var r = bound.get(input); if (r) paint(r); },
        format: function (v) { var m = parse(v); return m === null ? '' : label(m); },
        close: function () { close(); }
    };

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
    else start();
})();
