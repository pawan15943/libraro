/*
 * Ordering & Services: one friendly modal instead of the browser's alert / confirm / prompt.
 *
 *   OdDialog.open({ title, text, icon, tone: 'bad' | 'go', ok, cancel,          // cancel: false = only OK
 *                   input: { label, placeholder, value, required, multiline, max }, chips: ['…'] })
 *     → Promise: typed text (input), true (no input), or null (closed / cancelled)
 *
 * Or with no JS, on a <form>:
 *   data-od-confirm="Title"  data-od-text  data-od-ok  data-od-cancel  data-od-tone  data-od-icon
 *   data-od-field="reason"   (name of the input to fill with what was typed; shows a text box)
 *   data-od-label  data-od-placeholder  data-od-value  data-od-required="1"  data-od-chips="A|B|C"
 * The form submits only after OK.
 */
(function () {
    'use strict';
    if (window.OdDialog) return;

    var open = null;

    function sprite() {
        var u = document.querySelector('svg.lb-i use');
        var h = u && (u.getAttribute('href') || u.getAttribute('xlink:href'));
        return h ? h.split('#')[0] : null;
    }
    function icon(name) {
        var base = sprite();
        if (!base || !name) return '';
        return '<svg class="lb-i" aria-hidden="true"><use href="' + base + '#i-' + name + '"></use></svg>';
    }
    function esc(s) {
        return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
    }

    function show(o) {
        if (open) open.close(null);
        o = o || {};
        var tone = o.tone === 'bad' ? 'bad' : 'go';
        var inp = o.input || null;
        var last = document.activeElement;

        var el = document.createElement('div');
        el.className = 'odd is-' + tone;
        el.setAttribute('role', 'dialog');
        el.setAttribute('aria-modal', 'true');
        el.setAttribute('aria-labelledby', 'oddTitle');
        el.innerHTML =
            '<div class="odd-back" data-odd-x></div>' +
            '<div class="odd-card">' +
                '<span class="odd-ic">' + icon(o.icon || (tone === 'bad' ? 'circle-alert' : 'circle-check')) + '</span>' +
                '<h3 id="oddTitle">' + esc(o.title || 'Are you sure?') + '</h3>' +
                (o.text ? '<p class="odd-text">' + esc(o.text) + '</p>' : '') +
                (inp ? '<label class="odd-f"><span>' + esc(inp.label || '') + (inp.required ? ' <i>*</i>' : ' <em>(optional)</em>') + '</span>' +
                    (inp.multiline
                        ? '<textarea rows="3" maxlength="' + (inp.max || 255) + '" placeholder="' + esc(inp.placeholder || '') + '"></textarea>'
                        : '<input type="text" maxlength="' + (inp.max || 255) + '" placeholder="' + esc(inp.placeholder || '') + '" autocomplete="off">') +
                    '</label>' : '') +
                (inp && o.chips && o.chips.length ? '<div class="odd-chips">' + o.chips.map(function (c) { return '<button type="button" data-odd-chip>' + esc(c) + '</button>'; }).join('') + '</div>' : '') +
                '<div class="odd-btns">' +
                    (o.cancel === false ? '' : '<button type="button" class="odd-btn is-ghost" data-odd-x>' + esc(o.cancel || 'Go back') + '</button>') +
                    '<button type="button" class="odd-btn is-' + tone + '" data-odd-ok>' + esc(o.ok || 'OK') + '</button>' +
                '</div>' +
            '</div>';
        document.body.appendChild(el);
        document.documentElement.classList.add('odd-lock');

        var field = el.querySelector('input, textarea'), okBtn = el.querySelector('[data-odd-ok]');
        if (field && inp.value) field.value = inp.value;
        function sync() { if (field && inp.required) okBtn.disabled = !field.value.trim(); }
        sync();

        return new Promise(function (resolve) {
            function close(val) {
                if (!el.parentNode) return;
                el.classList.remove('is-in');
                document.removeEventListener('keydown', onKey, true);
                setTimeout(function () { el.remove(); }, 160);
                document.documentElement.classList.remove('odd-lock');
                open = null;
                if (last && last.focus) try { last.focus({ preventScroll: true }); } catch (e) {}
                resolve(val);
            }
            function ok() {
                if (okBtn.disabled) return;
                close(field ? field.value.trim() : true);
            }
            function onKey(e) {
                if (e.key === 'Escape') { e.preventDefault(); close(null); }
                else if (e.key === 'Enter' && (!field || field.tagName === 'INPUT') && document.activeElement !== el.querySelector('.is-ghost')) { e.preventDefault(); ok(); }
                else if (e.key === 'Tab') {
                    var f = [].slice.call(el.querySelectorAll('button:not([disabled]), input, textarea'));
                    if (!f.length) return;
                    var i = f.indexOf(document.activeElement);
                    if (e.shiftKey && i <= 0) { e.preventDefault(); f[f.length - 1].focus(); }
                    else if (!e.shiftKey && i === f.length - 1) { e.preventDefault(); f[0].focus(); }
                }
            }
            el.addEventListener('click', function (e) {
                var chip = e.target.closest('[data-odd-chip]');
                if (chip && field) { field.value = chip.textContent; sync(); field.focus(); return; }
                if (e.target.closest('[data-odd-ok]')) ok();
                else if (e.target.closest('[data-odd-x]')) close(null);
            });
            if (field) field.addEventListener('input', sync);
            document.addEventListener('keydown', onKey, true);
            open = { close: close };
            requestAnimationFrame(function () {
                el.classList.add('is-in');
                (field || okBtn).focus({ preventScroll: true });
            });
        });
    }

    window.OdDialog = { open: show };

    // forms: <form data-od-confirm="…"> asks first, then submits
    document.addEventListener('submit', function (e) {
        var f = e.target;
        if (!f || !f.matches || !f.matches('form[data-od-confirm]') || f._odOk) return;
        e.preventDefault();
        e.stopImmediatePropagation();
        var d = f.dataset, name = d.odField;
        show({
            title: d.odConfirm, text: d.odText, ok: d.odOk, cancel: d.odCancel, tone: d.odTone, icon: d.odIcon,
            input: name ? { label: d.odLabel, placeholder: d.odPlaceholder, value: d.odValue, required: d.odRequired === '1', multiline: d.odMultiline === '1' } : null,
            chips: d.odChips ? d.odChips.split('|') : null,
        }).then(function (val) {
            if (val === null) return;
            if (name) {
                var t = f.querySelector('[name="' + name + '"]');
                if (!t) { t = document.createElement('input'); t.type = 'hidden'; t.name = name; f.appendChild(t); }
                t.value = val;
            }
            f._odOk = true;
            if (f.requestSubmit) f.requestSubmit(); else f.submit();
            setTimeout(function () { f._odOk = false; }, 0);
        });
    }, true);
})();
