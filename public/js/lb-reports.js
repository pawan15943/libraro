/* Libraro reports — shared behaviour for every report page.
 *
 *  <form data-rpt action="...">  filters; any change reloads #rptBody over AJAX (no page refresh)
 *  [data-set="k=v,k2=v2"]         preset buttons inside the form: set inputs, then reload
 *  [data-rpt-csv="file-name"]     download every [data-csv] row inside #rptBody as CSV
 *  [data-rpt-find]                instant client-side filter over [data-q] rows in #rptBody
 *  [data-rpt-page="n"]            pagination link inside #rptBody: sets the form's "page" input
 *  Any other filter change sends the form back to page 1 (when it has a "page" input).
 *
 * The controller answers an AJAX request with {html} = the rendered body partial.
 */
(function () {
    'use strict';

    var body = function () { return document.getElementById('rptBody'); };
    var timer = null, inflight = null;

    function params(form) {
        var p = new URLSearchParams();
        [].forEach.call(form.elements, function (el) {
            if (!el.name || el.disabled || el.closest('[hidden]') || el.value === '') return;
            if ((el.type === 'checkbox' || el.type === 'radio') && !el.checked) return;
            p.append(el.name, el.value);
        });
        return p;
    }

    function firstPage(form) {
        if (form.elements.page) form.elements.page.value = 1;
    }

    function load(form) {
        var b = body();
        if (!b) return form.submit();
        var p = params(form), url = form.action.split('?')[0] + (p.toString() ? '?' + p : '');

        if (inflight) inflight.abort();
        inflight = new AbortController();
        b.classList.add('is-loading');
        form.querySelectorAll('[data-set]').forEach(syncPreset);

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            credentials: 'same-origin',
            signal: inflight.signal
        })
            .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
            .then(function (d) {
                b.innerHTML = d.html || '';
                b.classList.remove('is-loading');
                history.replaceState(null, '', url);
                document.dispatchEvent(new CustomEvent('rpt:loaded', { detail: d }));
                applyFind();
            })
            .catch(function (e) {
                if (e.name === 'AbortError') return;
                b.classList.remove('is-loading');
                window.location.href = url; // fall back to a normal page load
            });
    }

    // a preset button is "on" when every k=v it sets matches the form
    function syncPreset(btn) {
        var form = btn.closest('form');
        var on = btn.dataset.set.split(',').every(function (pair) {
            var kv = pair.split('='), el = form.elements[kv[0]];
            return el && String(el.value) === (kv[1] || '');
        });
        btn.classList.toggle('is-on', on);
        btn.setAttribute('aria-pressed', on ? 'true' : 'false');
    }

    document.addEventListener('change', function (e) {
        var form = e.target.closest('form[data-rpt]');
        if (!form || e.target.matches('[data-rpt-find]')) return;
        firstPage(form);
        load(form);
    });

    document.addEventListener('input', function (e) {
        var form = e.target.closest('form[data-rpt]');
        if (!form || e.target.type !== 'search' || e.target.matches('[data-rpt-find]')) return;
        clearTimeout(timer);
        timer = setTimeout(function () { firstPage(form); load(form); }, 380);
    });

    document.addEventListener('submit', function (e) {
        var form = e.target.closest('form[data-rpt]');
        if (!form) return;
        e.preventDefault();
        load(form);
    });

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('form[data-rpt] [data-set]');
        if (btn) {
            e.preventDefault();
            var form = btn.closest('form');
            firstPage(form);
            btn.dataset.set.split(',').forEach(function (pair) {
                var kv = pair.split('='), el = form.elements[kv[0]];
                if (el) el.value = kv[1] || '';
            });
            load(form);
            return;
        }

        var pg = e.target.closest('#rptBody [data-rpt-page]');
        if (pg) {
            var f = document.querySelector('form[data-rpt]');
            if (!f || !f.elements.page) return;
            e.preventDefault();
            f.elements.page.value = pg.dataset.rptPage;
            load(f);
            var top = document.getElementById('rptBody').getBoundingClientRect().top + window.scrollY - 90;
            if (window.scrollY > top) window.scrollTo({ top: top, behavior: 'smooth' });
            return;
        }

        var csv = e.target.closest('[data-rpt-csv]');
        if (csv) {
            e.preventDefault();
            exportCsv(csv.dataset.rptCsv || 'report');
        }
    });

    function exportCsv(name) {
        var rows = [];
        (body() || document).querySelectorAll('[data-csv]').forEach(function (el) {
            if (el.closest('[hidden]')) return;
            try { rows.push(JSON.parse(el.dataset.csv)); } catch (err) {}
        });
        if (!rows.length) {
            if (window.Swal) Swal.fire({ icon: 'info', title: 'Nothing to export', text: 'There are no rows for the current filters.' });
            return;
        }
        var cols = Object.keys(rows[0]);
        var esc = function (v) {
            v = v == null ? '' : String(v);
            return /[",\n]/.test(v) ? '"' + v.replace(/"/g, '""') + '"' : v;
        };
        var lines = [cols.map(esc).join(',')].concat(rows.map(function (r) {
            return cols.map(function (c) { return esc(r[c]); }).join(',');
        }));
        var blob = new Blob(['﻿' + lines.join('\n')], { type: 'text/csv;charset=utf-8' });
        var a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = name + '-' + new Date().toISOString().slice(0, 10) + '.csv';
        document.body.appendChild(a);
        a.click();
        setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 500);
    }

    // instant search over rows already on the page
    function applyFind() {
        var input = document.querySelector('[data-rpt-find]');
        var b = body();
        if (!input || !b) return;
        var q = input.value.trim().toLowerCase(), n = 0;
        var rows = b.querySelectorAll('[data-q]');
        rows.forEach(function (r) {
            var hit = !q || r.dataset.q.indexOf(q) > -1;
            r.hidden = !hit;
            if (hit) n++;
        });
        var none = b.querySelector('[data-rpt-none]');
        if (none) none.hidden = n > 0 || !rows.length;
    }
    document.addEventListener('input', function (e) {
        if (e.target.matches('[data-rpt-find]')) applyFind();
    });

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('form[data-rpt] [data-set]').forEach(syncPreset);
    });
})();
