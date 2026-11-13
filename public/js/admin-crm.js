/*
 * Calling CRM sheets (resources/views/crm/partials/sheets.blade.php)
 *
 *  Call button   → the phone dialer opens (tel:) and the "Log call" sheet opens with
 *                  a running timer; if the browser was left for the call, the sheet
 *                  opens again when the agent comes back.
 *  Log call      → disposition chips; picking one suggests the stage and the next
 *                  call time (from the disposition settings).
 *  WhatsApp/SMS  → template → editable text → opens wa.me / sms: and logs it.
 *  Follow-up     → quick times or any date/time.
 *  Status        → stage + sub-stage + priority + note.
 *
 * A lead's data comes from the closest [data-lead] element (JSON from CrmLead::sheetData()).
 */
(function () {
    'use strict';

    var cfgEl = document.getElementById('crmConfig');
    if (!cfgEl) return;
    // sheets live at the end of <body> so no page wrapper can trap them under the backdrop
    Array.prototype.forEach.call(document.querySelectorAll('.crm-sheet'), function (s) { document.body.appendChild(s); });
    var CFG = JSON.parse(cfgEl.textContent);
    var PENDING_KEY = 'crmPendingCall';
    var current = null;   // lead the open sheet is about
    var timer = null;

    var store = {
        get: function () { try { return JSON.parse(localStorage.getItem(PENDING_KEY) || 'null'); } catch (e) { return null; } },
        set: function (v) { try { localStorage.setItem(PENDING_KEY, JSON.stringify(v)); } catch (e) {} },
        clear: function () { try { localStorage.removeItem(PENDING_KEY); } catch (e) {} }
    };

    function $(sel, root) { return (root || document).querySelector(sel); }
    function $$(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }
    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function localValue(d) { return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes()); }
    function stageById(id) { return CFG.stages.filter(function (s) { return String(s.id) === String(id); })[0]; }
    function leadFrom(el) {
        var host = el.closest('[data-lead]');
        try { return host ? JSON.parse(host.getAttribute('data-lead')) : null; } catch (e) { return null; }
    }

    function modal(id) {
        var el = document.getElementById(id);
        return el && window.bootstrap ? bootstrap.Modal.getOrCreateInstance(el) : null;
    }

    function fill(sheet, lead) {
        $$('[data-crm-fill]', sheet).forEach(function (n) {
            var k = n.getAttribute('data-crm-fill');
            if (k === 'tel') { n.textContent = '+91 ' + lead.mobile; n.href = 'tel:+91' + lead.mobile; }
            else if (k === 'mobile') { n.textContent = '+91 ' + lead.mobile; }
            else { n.textContent = lead[k] || ''; }
        });
    }

    /* ---------- quick time chips ---------- */
    function quickTimes() {
        var now = new Date(), out = [];
        function at(days, h, m) { var d = new Date(now); d.setDate(d.getDate() + days); d.setHours(h, m || 0, 0, 0); return d; }
        function plus(min) { var d = new Date(now.getTime() + min * 60000); d.setMinutes(Math.ceil(d.getMinutes() / 5) * 5, 0, 0); return d; }
        out.push(['In 1 hour', plus(60)], ['In 3 hours', plus(180)]);
        if (now.getHours() < 17) out.push(['Today 6 PM', at(0, 18)]);
        out.push(['Tomorrow 10 AM', at(1, 10)], ['Tomorrow 5 PM', at(1, 17)], ['In 3 days', at(3, 11)]);
        var mon = new Date(now); mon.setDate(now.getDate() + ((8 - now.getDay()) % 7 || 7)); mon.setHours(10, 0, 0, 0);
        out.push(['Next Monday', mon]);
        return out;
    }

    function renderQuick(sheet) {
        $$('[data-crm-quick]', sheet).forEach(function (box) {
            var input = $('[name="' + box.getAttribute('data-crm-quick') + '"]', sheet);
            box.innerHTML = '';
            quickTimes().forEach(function (q) {
                var b = document.createElement('button');
                b.type = 'button'; b.className = 'crm-chip'; b.textContent = q[0];
                b.addEventListener('click', function () {
                    input.value = localValue(q[1]);
                    $$('.crm-chip', box).forEach(function (c) { c.classList.remove('on'); });
                    b.classList.add('on');
                });
                box.appendChild(b);
            });
            if (box.getAttribute('data-crm-quick') === 'follow_up_at') {
                var none = document.createElement('button');
                none.type = 'button'; none.className = 'crm-chip is-muted'; none.textContent = 'No follow-up';
                none.addEventListener('click', function () { input.value = ''; $$('.crm-chip', box).forEach(function (c) { c.classList.remove('on'); }); none.classList.add('on'); });
                box.appendChild(none);
            }
            input.min = localValue(new Date());
        });
    }

    /* ---------- stage selects ---------- */
    function fillStageSelect(select, keepFirst) {
        var first = keepFirst ? select.options[0].outerHTML : '';
        select.innerHTML = first + CFG.stages.map(function (s) { return '<option value="' + s.id + '">' + esc(s.name) + '</option>'; }).join('');
    }

    function fillSubSelect(select, stageId, value) {
        var st = stageById(stageId);
        select.innerHTML = '<option value="">—</option>' + (st ? st.subs.map(function (s) {
            return '<option value="' + s.id + '"' + (String(s.id) === String(value) ? ' selected' : '') + '>' + esc(s.name) + '</option>';
        }).join('') : '');
        select.disabled = !st || !st.subs.length;
    }

    /* ---------- Log call ---------- */
    function openCall(lead, startedAt) {
        var sheet = document.getElementById('crmCallSheet');
        if (!sheet) return;
        current = lead;
        var form = $('form', sheet);
        form.reset();
        form.action = lead.urls.call;
        fill(sheet, lead);
        renderQuick(sheet);

        // disposition chips
        ['1', '0'].forEach(function (conn) {
            var box = $('[data-crm-dispositions="' + conn + '"]', sheet);
            box.innerHTML = CFG.dispositions.filter(function (d) { return (d.connected ? '1' : '0') === conn; }).map(function (d) {
                return '<label class="crm-chip is-pick" style="--c:' + esc(d.color) + '"><input type="radio" name="disposition_id" value="' + d.id + '" required><span>' + esc(d.name) + '</span></label>';
            }).join('');
        });

        var stageSel = $('[data-crm-stage-select]', sheet), subSel = $('[data-crm-sub-select]', sheet);
        fillStageSelect(stageSel, true);
        fillSubSelect(subSel, null);
        $$('[name="priority"]', sheet).forEach(function (r) { r.checked = r.value === lead.priority; });

        // timer
        clearInterval(timer);
        var t = $('[data-crm-timer]', sheet), dur = $('[data-crm-duration]', sheet);
        if (startedAt) {
            t.hidden = false;
            var tick = function () {
                var s = Math.max(0, Math.round((Date.now() - startedAt) / 1000));
                t.querySelector('span').textContent = Math.floor(s / 60) + ':' + pad(s % 60);
                dur.value = Math.floor(s / 60) + ':' + pad(s % 60);
            };
            tick();
            timer = setInterval(tick, 1000);
            dur.addEventListener('input', function stop() { clearInterval(timer); t.hidden = true; dur.removeEventListener('input', stop); });
        } else {
            t.hidden = true;
            dur.value = '';
        }
        loadHistory(sheet, lead);
        modal('crmCallSheet').show();
    }

    /* ---------- earlier conversation in the Log call sheet ---------- */
    var HISTORY_SHOWN = 3;
    var KIND_ICON = { call: 'fa-solid fa-phone', note: 'fa-regular fa-note-sticky', whatsapp: 'fa-brands fa-whatsapp', sms: 'fa-regular fa-comment-dots', stage: 'fa-solid fa-flag' };

    function loadHistory(sheet, lead) {
        var box = $('[data-crm-history]', sheet), body = $('[data-crm-history-body]', sheet), sum = $('[data-crm-history-sum]', sheet), more = $('[data-crm-history-more]', sheet);
        if (!box) return;
        var url = lead.urls && lead.urls.history;
        box.hidden = !url;
        if (!url) return;
        body.innerHTML = '<div class="crm-history-loading"><span class="ui-spinner"></span> Loading…</div>';
        sum.textContent = '';
        more.hidden = true;
        box.dataset.lead = lead.id;

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.ok ? r.json() : Promise.reject(r.status); })
            .then(function (data) {
                if (box.dataset.lead !== String(lead.id)) return; // another lead was opened meanwhile
                var s = data.summary || {}, items = data.items || [];
                sum.textContent = s.calls ? s.calls + (s.calls === 1 ? ' call' : ' calls') + ' · ' + s.connected + ' connected' + (s.last ? ' · last ' + s.last : '') : 'First call';

                var html = '';
                if (s.lead_notes) html += '<div class="crm-history-pinned"><i class="fa-solid fa-thumbtack"></i>' + esc(s.lead_notes) + '</div>';
                if (!items.length) {
                    html += '<div class="crm-history-empty">No earlier calls — this is the first conversation.</div>';
                }
                html += items.map(function (it, i) {
                    var isCall = it.kind === 'call';
                    return '<div class="crm-history-item' + (i >= HISTORY_SHOWN ? ' is-extra' : '') + (isCall && !it.connected ? ' is-missed' : '') + '">' +
                        '<span class="crm-history-ico is-' + esc(it.kind) + '"><i class="' + (KIND_ICON[it.kind] || 'fa-solid fa-circle') + '"></i></span>' +
                        '<div class="crm-history-main">' +
                            '<div class="crm-history-top">' +
                                (isCall ? '<span class="crm-result' + (it.connected ? ' is-on' : '') + '" style="--c:' + esc(it.color) + '">' + esc(it.result) + '</span>'
                                        : '<b>' + esc(it.result) + '</b>') +
                                '<span class="crm-history-when" title="' + esc(it.when) + '">' + esc(it.ago) + '</span>' +
                            '</div>' +
                            (it.notes ? '<div class="crm-history-notes">' + esc(it.notes).replace(/\n/g, '<br>') + '</div>' : '') +
                            '<div class="crm-history-meta">' + [it.agent, it.duration ? it.duration : null, it.stage ? '→ ' + it.stage : null, it.next ? 'next call ' + it.next : null]
                                .filter(Boolean).map(esc).join(' · ') + '</div>' +
                        '</div></div>';
                }).join('');
                body.innerHTML = html;

                var extra = Math.max(0, items.length - HISTORY_SHOWN);
                more.hidden = !extra;
                more.textContent = 'Show all ' + items.length + ' entries';
                box.classList.remove('is-open');
            })
            .catch(function () {
                body.innerHTML = '<div class="crm-history-empty">Could not load the earlier calls. <a href="' + esc(lead.urls.show) + '" target="_blank" rel="noopener">Open the lead</a></div>';
            });
    }

    document.addEventListener('click', function (e) {
        var more = e.target.closest('[data-crm-history-more]');
        if (!more) return;
        var box = more.closest('[data-crm-history]');
        var open = box.classList.toggle('is-open');
        more.textContent = open ? 'Show less' : 'Show all ' + box.querySelectorAll('.crm-history-item').length + ' entries';
    });

    document.addEventListener('change', function (e) {
        var sheet = e.target.closest('#crmCallSheet');
        if (!sheet) return;
        if (e.target.name === 'disposition_id') {
            var d = CFG.dispositions.filter(function (x) { return String(x.id) === e.target.value; })[0];
            if (!d) return;
            var stageSel = $('[data-crm-stage-select]', sheet), subSel = $('[data-crm-sub-select]', sheet);
            stageSel.value = d.stage || '';
            fillSubSelect(subSel, d.stage, d.sub);
            var st = stageById(d.stage), when = $('[name="follow_up_at"]', sheet), block = $('[data-crm-follow-block]', sheet);
            block.classList.toggle('is-off', !!(st && st.closed));
            $$('.crm-chip', $('[data-crm-quick]', sheet)).forEach(function (c) { c.classList.remove('on'); });
            if (st && st.closed) { when.value = ''; }
            else if (d.minutes) {
                var t = new Date(Date.now() + d.minutes * 60000);
                if (d.minutes >= 1440 && (t.getHours() < 10 || t.getHours() > 19)) t.setHours(11, 0, 0, 0);
                t.setMinutes(Math.ceil(t.getMinutes() / 5) * 5, 0, 0);
                when.value = localValue(t);
            }
            $('[name="follow_up_note"]', sheet).value = 'After call: ' + d.name;
        }
        if (e.target.matches('[data-crm-stage-select]')) {
            fillSubSelect($('[data-crm-sub-select]', sheet), e.target.value);
        }
    });

    /* ---------- WhatsApp / SMS ---------- */
    function openMessage(lead, channel) {
        var sheet = document.getElementById('crmMessageSheet');
        if (!sheet) return;
        current = lead;
        fill(sheet, lead);
        $$('[name="crm_channel"]', sheet).forEach(function (r) { r.checked = r.value === (channel || 'whatsapp'); });
        renderTemplates(sheet);
        modal('crmMessageSheet').show();
    }

    function channelOf(sheet) { var r = $('[name="crm_channel"]:checked', sheet); return r ? r.value : 'whatsapp'; }

    function renderTemplates(sheet) {
        var ch = channelOf(sheet), sel = $('[data-crm-template]', sheet);
        var list = CFG.templates.filter(function (t) { return t.channel === ch; });
        sel.innerHTML = list.map(function (t) { return '<option value="' + t.id + '">' + esc(t.name) + '</option>'; }).join('') + '<option value="">Write my own</option>';
        // a lead that registered but did not pay gets the payment template first
        var pay = list.filter(function (t) { return /payment/i.test(t.name); })[0];
        if (current && current.source === 'signup' && pay) sel.value = pay.id;
        applyTemplate(sheet);
    }

    function applyTemplate(sheet) {
        var sel = $('[data-crm-template]', sheet), t = CFG.templates.filter(function (x) { return String(x.id) === sel.value; })[0];
        var lead = current || {};
        $('[data-crm-text]', sheet).value = t ? t.body
            .replace(/\{name\}/g, lead.contact || lead.library || 'there')
            .replace(/\{library\}/g, lead.library || 'your library')
            .replace(/\{agent\}/g, CFG.agent)
            .replace(/\{mobile\}/g, lead.mobile || '')
            .replace(/\{login_link\}/g, CFG.loginLink) : '';
        updateSend(sheet);
    }

    function updateSend(sheet) {
        var ch = channelOf(sheet), text = $('[data-crm-text]', sheet).value, a = $('[data-crm-send]', sheet), lead = current || {};
        a.href = ch === 'sms'
            ? 'sms:+91' + lead.mobile + '?body=' + encodeURIComponent(text)
            : 'https://wa.me/91' + lead.mobile + '?text=' + encodeURIComponent(text);
        a.className = 'ui-btn crm-send' + (ch === 'sms' ? '' : ' ui-btn--success');
        a.querySelector('i').className = ch === 'sms' ? 'fa-regular fa-comment-dots' : 'fa-brands fa-whatsapp';
        a.querySelector('span').textContent = ch === 'sms' ? 'Open messages' : 'Open WhatsApp';
        a.target = ch === 'sms' ? '_self' : '_blank';
        var c = $('[data-crm-count]', sheet);
        if (c) c.textContent = text.length + ' characters' + (ch === 'sms' ? ' · ' + Math.max(1, Math.ceil(text.length / 160)) + ' SMS' : '');
    }

    document.addEventListener('change', function (e) {
        var sheet = e.target.closest('#crmMessageSheet');
        if (!sheet) return;
        if (e.target.name === 'crm_channel') renderTemplates(sheet);
        if (e.target.matches('[data-crm-template]')) applyTemplate(sheet);
    });
    document.addEventListener('input', function (e) {
        var sheet = e.target.closest('#crmMessageSheet');
        if (sheet && e.target.matches('[data-crm-text]')) updateSend(sheet);
    });
    document.addEventListener('click', function (e) {
        var a = e.target.closest('[data-crm-send]');
        if (!a || !current) return;
        var sheet = a.closest('#crmMessageSheet'), text = $('[data-crm-text]', sheet).value.trim();
        if (!text) { e.preventDefault(); $('[data-crm-text]', sheet).focus(); return; }
        var sel = $('[data-crm-template]', sheet);
        var body = new FormData();
        body.append('_token', CFG.csrf);
        body.append('channel', channelOf(sheet));
        body.append('text', text);
        if (sel.value) body.append('template', sel.options[sel.selectedIndex].text);
        // keepalive: the log is saved even though WhatsApp / the SMS app takes over the page
        try { fetch(current.urls.message, { method: 'POST', body: body, keepalive: true, credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } }); } catch (err) {}
        setTimeout(function () { var m = modal('crmMessageSheet'); if (m) m.hide(); if (window.toastr) toastr.success('Saved on the timeline.', 'Message opened'); }, 300);
    });

    /* ---------- Follow-up ---------- */
    function openReminder(lead) {
        var sheet = document.getElementById('crmReminderSheet');
        if (!sheet) return;
        current = lead;
        var form = $('form', sheet);
        form.reset();
        form.action = lead.urls.reminder;
        fill(sheet, lead);
        renderQuick(sheet);
        modal('crmReminderSheet').show();
    }

    /* ---------- Status ---------- */
    function openStage(lead) {
        var sheet = document.getElementById('crmStageSheet');
        if (!sheet) return;
        current = lead;
        var form = $('form', sheet);
        form.reset();
        form.action = lead.urls.stage;
        fill(sheet, lead);
        var pick = $('[data-crm-stage-pick]', sheet);
        pick.innerHTML = CFG.stages.map(function (s) {
            return '<label class="crm-stage-opt' + (s.closed ? ' is-closed' : '') + '" style="--c:' + esc(s.color) + '"><input type="radio" name="stage_id" value="' + s.id + '" required' +
                (String(s.id) === String(lead.stage) ? ' checked' : '') + '><span><span class="dot"></span>' + esc(s.name) +
                (s.closed ? '<small>' + (s.won ? 'won' : 'closed') + '</small>' : '') + '</span></label>';
        }).join('');
        fillSubSelect($('[data-crm-sub-select]', sheet), lead.stage, lead.sub);
        $$('[name="priority"]', sheet).forEach(function (r) { r.checked = r.value === lead.priority; });
        modal('crmStageSheet').show();
    }

    document.addEventListener('change', function (e) {
        var sheet = e.target.closest('#crmStageSheet');
        if (sheet && e.target.name === 'stage_id') fillSubSelect($('[data-crm-sub-select]', sheet), e.target.value);
    });

    /* ---------- wiring ---------- */
    document.addEventListener('click', function (e) {
        var opener = e.target.closest('[data-crm-open]');
        if (opener) {
            var lead = leadFrom(opener);
            if (!lead) return;
            e.preventDefault();
            var kind = opener.getAttribute('data-crm-open');
            if (kind === 'call') openCall(lead, null);
            if (kind === 'message') openMessage(lead, opener.getAttribute('data-channel'));
            if (kind === 'reminder') openReminder(lead);
            if (kind === 'stage') openStage(lead);
            return;
        }

        // Call: the dialer opens (default action), the log sheet opens with a timer
        var call = e.target.closest('[data-crm-call]');
        if (call) {
            var l = leadFrom(call);
            if (!l) return;
            var started = Date.now();
            store.set({ lead: l, started: started });
            setTimeout(function () { openCall(l, started); }, 500);
        }
    });

    // duration "m:ss" → seconds; a saved call clears the pending one
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (form.matches('[data-crm-form="call"]')) {
            var raw = ($('[data-crm-duration]', form).value || '').trim(), secs = '';
            if (raw) {
                var p = raw.split(':');
                secs = p.length > 1 ? (parseInt(p[0], 10) || 0) * 60 + (parseInt(p[1], 10) || 0) : Math.round(parseFloat(raw) * 60) || 0;
            }
            $('[name="duration_seconds"]', form).value = secs;
            if (!$('[name="follow_up_at"]', form).value) $('[name="follow_up_note"]', form).value = '';
            store.clear();
            clearInterval(timer);
        }
        var btn = form.querySelector('.crm-submit');
        if (btn && form.matches('[data-crm-form]')) {
            // ui-btn "is-loading" draws the spinner and blocks a second tap (a disabled ui-btn would fade out)
            setTimeout(function () { if (!e.defaultPrevented) { btn.classList.add('is-loading'); btn.setAttribute('aria-busy', 'true'); } }, 0);
        }
    });

    $$('.crm-sheet').forEach(function (s) {
        s.addEventListener('hidden.bs.modal', function () { clearInterval(timer); });
    });
    var callSheet = document.getElementById('crmCallSheet');
    if (callSheet) {
        // closing the log sheet without saving keeps nothing pending
        callSheet.addEventListener('hidden.bs.modal', function () { if (!callSheet.querySelector('form').dataset.submitting) store.clear(); });
        callSheet.querySelector('form').addEventListener('submit', function () { this.dataset.submitting = '1'; });
    }

    // back from the phone app: reopen the log sheet of the call that was started
    function resumePending() {
        var p = store.get();
        if (!p || !p.lead) return;
        if (Date.now() - p.started > 3 * 3600 * 1000) { store.clear(); return; }
        var open = document.querySelector('.crm-sheet.show');
        if (!open) openCall(p.lead, p.started);
    }
    document.addEventListener('visibilitychange', function () { if (document.visibilityState === 'visible') setTimeout(resumePending, 300); });
    document.addEventListener('DOMContentLoaded', function () { setTimeout(resumePending, 400); });

    // open a sheet straight from a link like ?sheet=call
    document.addEventListener('DOMContentLoaded', function () {
        var m = /[?&]sheet=(call|message|reminder|stage)/.exec(window.location.search);
        var host = document.querySelector('[data-lead-page]');
        if (m && host) {
            var lead = JSON.parse(host.getAttribute('data-lead'));
            ({ call: function () { openCall(lead, null); }, message: function () { openMessage(lead); }, reminder: function () { openReminder(lead); }, stage: function () { openStage(lead); } })[m[1]]();
        }
    });

    window.crmSheets = { call: openCall, message: openMessage, reminder: openReminder, stage: openStage };
})();
