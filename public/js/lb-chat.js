/*
 * Libraro chat UI (shared by the student page and the owner page). Needs lb-chat-crypto.js.
 * LibraroChat.mount({ root, role: 'learner'|'library', scope, otherProp: 'library'|'learner', otherName, urls:{key,messages,send,read}, csrf })
 * Message text is only ever put into the page with textContent.
 */
(function () {
    'use strict';
    var C = window.LibraroCrypto, K = window.LibraroKeys;

    // static, trusted icon markup (never built from user text)
    var SVG = {
        clip: '<path d="m21 11.5-8.6 8.6a5.5 5.5 0 0 1-7.8-7.8l8.6-8.6a3.7 3.7 0 0 1 5.2 5.2l-8.6 8.6a1.8 1.8 0 0 1-2.6-2.6l7.9-7.9"/>',
        smile: '<circle cx="12" cy="12" r="9"/><path d="M8.5 14.2a4.2 4.2 0 0 0 7 0"/><path d="M9 9.5h.01M15 9.5h.01"/>',
        mic: '<rect x="9" y="3" width="6" height="11" rx="3"/><path d="M5.5 11a6.5 6.5 0 0 0 13 0M12 17.5V21"/>',
        send: '<path d="M5 12 3.5 4.2a.6.6 0 0 1 .85-.65l16.4 7.6a.6.6 0 0 1 0 1.1L4.35 20.4a.6.6 0 0 1-.85-.65L5 12Zm0 0h8"/>',
        trash: '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
        file: '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8l-5-5Z"/><path d="M14 3v5h5"/>',
        down: '<path d="M12 4v12m0 0 4-4m-4 4-4-4M5 20h14"/>',
        play: '<path d="M7 4.5v15a.8.8 0 0 0 1.2.7l12-7.5a.8.8 0 0 0 0-1.4l-12-7.5A.8.8 0 0 0 7 4.5Z"/>'
    };
    function icon(name, size) {
        var n = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        n.setAttribute('viewBox', '0 0 24 24'); n.setAttribute('width', size || 20); n.setAttribute('height', size || 20);
        n.setAttribute('fill', 'none'); n.setAttribute('stroke', 'currentColor'); n.setAttribute('stroke-width', '1.9');
        n.setAttribute('stroke-linecap', 'round'); n.setAttribute('stroke-linejoin', 'round'); n.setAttribute('aria-hidden', 'true');
        n.innerHTML = SVG[name]; // eslint-disable-line -- constant markup above
        return n;
    }
    function iconBtn(name, label, cls) {
        var b = document.createElement('button'); b.type = 'button'; b.className = 'lc-ib' + (cls ? ' ' + cls : '');
        b.setAttribute('aria-label', label); b.title = label; b.appendChild(icon(name)); return b;
    }
    var EMOJI = {
        'Smileys': '😀 😃 😄 😁 😆 😅 😂 🤣 🙂 😉 😊 😇 🥰 😍 🤩 😘 😋 😛 😜 🤪 🤗 🤔 🫡 😎 🥳 😴 😌 😬 🙄 😢 😭 😤 😡 🥺 😳 😱 🤯 🤒',
        'Hands': '👍 👎 👏 🙌 🙏 🤝 👌 ✌️ 🤞 🤟 👋 💪 ✋ 🫶 👀 🙋 🙇 🤷',
        'Hearts': '❤️ 🧡 💛 💚 💙 💜 🖤 🤍 💔 💕 💖 💯 ✨ 🔥 ⭐ 🌟 🎉 🎊',
        'Study': '📚 📖 📝 ✏️ 🖊️ 📒 🎓 🧠 💡 ⏰ ⏳ 📅 ✅ ❌ ❓ ❗ 📌 🏆 🥇 🎯 💻 📱 ☕ 🍎',
        'More': '🌞 🌙 ☔ 🌈 🌸 🍀 🐱 🐶 🍕 🍔 🍫 🎵 🚀 🏠 🚗 💰 🎁 🔒 🔔 📞'
    };
    var MAX_FILE = 10 * 1024 * 1024, MAX_REC = 300; // 10 MB, 5 minutes
    var ENV = '{"__lc":1';
    function parseEnv(text) { if (typeof text !== 'string' || text.indexOf(ENV) !== 0) return null; try { var o = JSON.parse(text); return o && o.__lc === 1 ? o : null; } catch (e) { return null; } }
    function humanSize(n) { return n < 1024 ? n + ' B' : n < 1048576 ? Math.round(n / 1024) + ' KB' : (n / 1048576).toFixed(1) + ' MB'; }
    function mmss(sec) { sec = Math.max(0, Math.round(sec)); return Math.floor(sec / 60) + ':' + ('0' + (sec % 60)).slice(-2); }
    var SAFE_IMG = /^image\/(png|jpe?g|gif|webp)$/;

    function el(tag, cls, text) { var e = document.createElement(tag); if (cls) e.className = cls; if (text != null) e.textContent = text; return e; }

    function mount(cfg) {
        var root = cfg.root, me = null, other = null, threadId = null, lastId = 0, timer = null, busy = false, localKeys = [];
        var aesCache = {}, shown = {}, ready = false;

        // ---------- layout ----------
        root.textContent = '';
        var banner = el('div', 'lc-banner'); banner.hidden = true;
        var head = el('div', 'lc-head');
        var av = el('span', 'lc-av', (String(cfg.otherName || '?').trim().split(/\s+/).map(function (w) { return w.charAt(0); }).slice(0, 2).join('') || '?').toUpperCase());
        var title = el('div', 'lc-title'); title.appendChild(el('b', null, cfg.otherName)); var sub = el('small', null, (cfg.otherSub ? cfg.otherSub + ' · ' : '') + 'End-to-end encrypted'); title.appendChild(sub);
        var who = el('div', 'lc-who'); who.appendChild(av); who.appendChild(title);
        var tools = el('div', 'lc-tools');
        var btnCode = el('button', 'lc-link', 'Security code'); btnCode.type = 'button';
        var btnBackup = el('button', 'lc-link', 'Key backup'); btnBackup.type = 'button';
        tools.appendChild(btnCode); tools.appendChild(btnBackup);
        if (window.LibraroNotify && 'Notification' in window && Notification.permission === 'default') {
            var btnAlerts = el('button', 'lc-link', 'Alerts'); btnAlerts.type = 'button'; btnAlerts.title = 'Get a notification when a new message arrives';
            btnAlerts.onclick = function () { LibraroNotify.askPermission().then(function () { btnAlerts.remove(); }); };
            tools.insertBefore(btnAlerts, tools.firstChild);
        }
        head.appendChild(who); head.appendChild(tools);
        var panel = el('div', 'lc-panel'); panel.hidden = true;
        var list = el('div', 'lc-list'); list.setAttribute('aria-live', 'polite');
        var empty = el('div', 'lc-empty');
        empty.appendChild(el('div', 'lc-empty-ic', '🔒'));
        empty.appendChild(el('b', null, cfg.emptyTitle || 'Start a private conversation'));
        empty.appendChild(el('p', null, cfg.emptyText || 'Your messages are scrambled before they leave this device. Only you and ' + cfg.otherName + ' can read them.'));
        if (cfg.quick && cfg.quick.length) {
            var chips = el('div', 'lc-chips');
            cfg.quick.forEach(function (t) {
                var c = el('button', 'lc-chip', t); c.type = 'button';
                c.onclick = function () { if (input.disabled) return; input.value = t; autosize(); input.focus(); };
                chips.appendChild(c);
            });
            empty.appendChild(chips);
        }
        list.appendChild(empty);
        var form = el('form', 'lc-form');
        var btnClip = iconBtn('clip', 'Attach a photo or file'), btnEmoji = iconBtn('smile', 'Emoji');
        var fileIn = el('input'); fileIn.type = 'file'; fileIn.hidden = true;
        fileIn.accept = 'image/*,audio/*,application/pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv,.zip';
        var input = el('textarea', 'lc-input'); input.rows = 1; input.maxLength = 2000; input.placeholder = 'Type a message'; input.setAttribute('aria-label', 'Message');
        var btnMic = iconBtn('mic', 'Record a voice message', 'lc-mic');
        var send = el('button', 'lc-send'); send.type = 'submit'; send.setAttribute('aria-label', 'Send'); send.title = 'Send'; send.appendChild(icon('send')); send.hidden = true;
        form.appendChild(btnClip); form.appendChild(btnEmoji); form.appendChild(fileIn); form.appendChild(input); form.appendChild(btnMic); form.appendChild(send);
        var pick = el('div', 'lc-emoji'); pick.hidden = true;
        var rec = el('div', 'lc-rec'); rec.hidden = true;
        var recCancel = iconBtn('trash', 'Cancel recording', 'lc-rec-x');
        var recDot = el('i', 'lc-rec-dot'), recTime = el('b', 'lc-rec-time', '0:00'), recBars = el('span', 'lc-rec-bars');
        for (var bi = 0; bi < 18; bi++) recBars.appendChild(el('i'));
        var recSend = el('button', 'lc-send'); recSend.type = 'button'; recSend.setAttribute('aria-label', 'Send voice message'); recSend.appendChild(icon('send'));
        rec.appendChild(recCancel); rec.appendChild(recDot); rec.appendChild(recTime); rec.appendChild(recBars); rec.appendChild(recSend);
        var note = el('div', 'lc-note'); note.hidden = true;
        root.appendChild(banner); root.appendChild(head); root.appendChild(panel); root.appendChild(list); root.appendChild(note); root.appendChild(pick); root.appendChild(rec); root.appendChild(form);

        function setNote(t) { note.textContent = t || ''; note.hidden = !t; }
        // no spinner or greyed buttons while the key is being prepared: the composer stays normal
        function lock(on, why) {
            input.disabled = on; send.disabled = on; btnClip.disabled = on; btnEmoji.disabled = on; btnMic.disabled = on;
            if (why !== undefined) setNote(why);
        }

        function api(url, opts) {
            opts = opts || {}; opts.headers = Object.assign({ 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': cfg.csrf }, opts.headers || {});
            opts.credentials = 'same-origin';
            return fetch(url, opts).then(function (r) { if (!r.ok) { var e = new Error('HTTP ' + r.status); e.status = r; throw e; } return r.json(); });
        }
        function postJson(url, body) { return api(url, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(body || {}) }); }

        // ---------- rendering ----------
        function bubble(m, text, cantRead, env, aesKey, local) {
            var mine = m.sender === cfg.role;
            var row = el('div', 'lc-row ' + (mine ? 'is-me' : 'is-them')); row.dataset.id = m.id;
            var b = el('div', 'lc-bubble' + (cantRead ? ' is-lock' : '') + (env && env.k !== 'text' ? ' has-att' : ''));
            if (env && env.k !== 'text') b.appendChild(attachment(env, aesKey, m.sender, local)); else b.appendChild(el('span', 'lc-text', text));
            var meta = el('small', 'lc-meta', m.at ? new Date(m.at).toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' }) : '…');
            if (mine) { var tick = el('i', 'lc-tick', m.read ? ' ✓✓' : ' ✓'); tick.dataset.tick = '1'; meta.appendChild(tick); }
            b.appendChild(meta); row.appendChild(b);
            return row;
        }
        function dayLabel(iso) { return new Date(iso).toLocaleDateString([], { day: 'numeric', month: 'short', year: 'numeric' }); }
        var lastDay = '';
        function append(m, text, cantRead, env, aesKey) {
            if (empty.parentNode) empty.remove();
            if (m.at) { var d = dayLabel(m.at); if (d !== lastDay) { list.appendChild(el('div', 'lc-day', d)); lastDay = d; } }
            var atBottom = list.scrollHeight - list.scrollTop - list.clientHeight < 80;
            list.appendChild(bubble(m, text, cantRead, env, aesKey));
            if (atBottom || m.sender === cfg.role) list.scrollTop = list.scrollHeight;
        }

        // ---------- crypto glue ----------
        function privateFor(fp) { return localKeys.filter(function (k) { return k.fp === fp; })[0]; }
        function aes(priv, otherJwk) {
            var id = priv.fp + '>' + otherJwk.x + otherJwk.y;
            return aesCache[id] || (aesCache[id] = C.deriveKey(priv.privateJwk, otherJwk, threadId));
        }
        function keyFor(m, keys) {
            var myKeyId = m.sender === cfg.role ? m.sender_key_id : m.recipient_key_id;
            var theirKeyId = m.sender === cfg.role ? m.recipient_key_id : m.sender_key_id;
            var mk = keys[myKeyId], tk = keys[theirKeyId], priv = mk && privateFor(mk.fingerprint);
            if (!priv || !tk) return Promise.resolve(null);
            return aes(priv, tk.jwk).catch(function () { return null; });
        }
        function readMessage(m, keys) {
            return keyFor(m, keys).then(function (k) {
                if (!k) return { text: null };
                return C.decrypt(k, m.ciphertext, m.iv, threadId, m.sender).then(function (t) { return { text: t, key: k }; });
            }).catch(function () { return { text: null }; });
        }

        // ---------- attachments: download -> decrypt in the browser ----------
        function loadBlob(env, aesKey, sender) {
            return fetch(cfg.urls.file + '/' + encodeURIComponent(env.f.id), { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.arrayBuffer(); })
                .then(function (buf) { return C.decryptBytes(aesKey, buf, env.f.iv, threadId, sender); })
                .then(function (bytes) { return new Blob([bytes], { type: SAFE_IMG.test(env.f.m || '') || /^audio\//.test(env.f.m || '') ? env.f.m : 'application/octet-stream' }); });
        }
        function lightbox(src) {
            var ov = el('div', 'lc-lightbox'); var im = el('img'); im.src = src; im.alt = '';
            ov.appendChild(im); ov.onclick = function () { ov.remove(); };
            document.addEventListener('keydown', function esc(e) { if (e.key === 'Escape') { ov.remove(); document.removeEventListener('keydown', esc); } });
            document.body.appendChild(ov);
        }
        function attachment(env, aesKey, sender, local) {
            var f = env.f || {}, box = el('div', 'lc-att');
            if (env.k === 'image' && SAFE_IMG.test(f.m || '')) {
                var ph = el('div', 'lc-img is-wait'); box.appendChild(ph);
                var show = function (blob) {
                    var url = URL.createObjectURL(blob), im = el('img'); im.alt = f.n || 'Photo'; im.src = url; im.onclick = function () { lightbox(url); };
                    ph.classList.remove('is-wait'); ph.textContent = ''; ph.appendChild(im);
                };
                if (local) show(local); else if (!aesKey) ph.textContent = 'Photo'; else loadBlob(env, aesKey, sender).then(show).catch(function () { ph.textContent = 'Could not load photo'; });
            } else if (env.k === 'audio' && /^audio\//.test(f.m || '')) {
                var wrap = el('div', 'lc-voice'); wrap.appendChild(el('span', 'lc-voice-t', 'Voice message' + (f.d ? ' · ' + mmss(f.d) : '')));
                box.appendChild(wrap);
                var put = function (blob) { var a = el('audio'); a.controls = true; a.preload = 'metadata'; a.src = URL.createObjectURL(blob); wrap.appendChild(a); };
                if (local) put(local); else if (aesKey) loadBlob(env, aesKey, sender).then(put).catch(function () { wrap.appendChild(el('small', null, 'Could not load')); });
            } else {
                var card = el('button', 'lc-file'); card.type = 'button';
                card.appendChild(icon('file', 22));
                var t = el('span', 'lc-file-t'); t.appendChild(el('b', null, f.n || 'File')); t.appendChild(el('small', null, humanSize(f.s || 0)));
                card.appendChild(t); card.appendChild(icon('down', 18));
                card.onclick = function () {
                    var go = function (blob) {
                        var a = el('a'); a.href = URL.createObjectURL(blob); a.download = (f.n || 'file').replace(/[\\/:*?"<>|]/g, '_'); document.body.appendChild(a); a.click();
                        setTimeout(function () { URL.revokeObjectURL(a.href); a.remove(); }, 2000);
                    };
                    if (local) go(local); else if (aesKey) loadBlob(env, aesKey, sender).then(go).catch(function () { setNote('Could not download this file.'); setTimeout(function () { setNote(''); }, 3000); });
                };
                box.appendChild(card);
            }
            if (env.t) box.appendChild(el('span', 'lc-text', env.t));
            return box;
        }
        var LOCKED = "Can't decrypt on this device. Restore your key backup to read it.";

        function handle(data) {
            var keys = data.keys || {}, chain = Promise.resolve();
            (data.messages || []).forEach(function (m) {
                chain = chain.then(function () {
                    if (shown[m.id]) { var t = list.querySelector('[data-id="' + m.id + '"] [data-tick]'); if (t) t.textContent = m.read ? ' ✓✓' : ' ✓'; return; }
                    // replace my optimistic copy when its real row arrives
                    return readMessage(m, keys).then(function (r) {
                        var text = r.text, env = parseEnv(text);
                        var tmp = m.sender === cfg.role && text !== null ? [].filter.call(list.querySelectorAll('[data-id^="tmp-"]'), function (x) {
                            return !x.classList.contains('is-failed') && (env ? (env.f && x.dataset.fid === env.f.id) : x.dataset.body === text);
                        })[0] : null;
                        if (tmp) tmp.remove();
                        shown[m.id] = true; if (m.id > lastId) lastId = m.id;
                        append(m, text === null ? LOCKED : (env ? (env.t || '') : text), text === null, env, r.key);
                    });
                });
            });
            return chain;
        }

        // ---------- other side's key: change warning ----------
        function trackPeer(peer) {
            if (!peer) return;
            var k = 'lc-peer:' + cfg.scope, known = null;
            try { known = localStorage.getItem(k); } catch (e) {}
            if (!known) { try { localStorage.setItem(k, peer.fingerprint); } catch (e) {} return; }
            if (known !== peer.fingerprint) {
                banner.hidden = false; banner.textContent = '';
                banner.appendChild(el('b', null, 'The security code with ' + cfg.otherName + ' has changed. '));
                banner.appendChild(document.createTextNode('This happens when they use a new device or reset their key. If you did not expect it, check the code with them in person or by phone before sharing anything private. '));
                var ok = el('button', 'lc-link', 'I have checked'); ok.type = 'button';
                ok.onclick = function () { try { localStorage.setItem(k, peer.fingerprint); } catch (e) {} banner.hidden = true; };
                banner.appendChild(ok);
            }
        }
        function setPeer(peer) {
            var changed = (!other && peer) || (other && peer && other.fingerprint !== peer.fingerprint);
            other = peer;
            if (changed) trackPeer(peer);
            if (!ready) return;
            if (!other) lock(true, cfg.noPeerText); else lock(false, '');
        }

        // ---------- poll ----------
        function poll() {
            if (busy || document.hidden || !ready) return Promise.resolve();
            busy = true;
            return api(cfg.urls.messages + '?since=' + lastId).then(function (d) {
                setPeer(d[cfg.otherProp] || null);
                return handle(d).then(function () {
                    var unread = (d.messages || []).some(function (m) { return m.sender !== cfg.role && !m.read; });
                    if (unread) return postJson(cfg.urls.read).then(function () { window.dispatchEvent(new Event('lc:read')); }).catch(function () {});
                });
            }).catch(function () {}).then(function () { busy = false; });
        }
        function loop() { clearInterval(timer); timer = setInterval(poll, 4000); }
        document.addEventListener('visibilitychange', function () { if (!document.hidden) poll(); });

        // ---------- send ----------
        function autosize() { input.style.height = 'auto'; input.style.height = Math.min(input.scrollHeight, 140) + 'px'; }
        // mic when the box is empty, send when there is text (nothing ever shows a spinner)
        function toggleSend() { var has = input.value.trim().length > 0; send.hidden = !has; btnMic.hidden = has; }
        input.addEventListener('input', function () { autosize(); toggleSend(); });
        input.addEventListener('keydown', function (e) { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); form.requestSubmit(); } });
        toggleSend();

        function notReady() {
            if (ready && me && other) return false;
            setNote(ready ? cfg.noPeerText : 'Getting ready. Try again in a moment.');
            if (!ready) setTimeout(function () { setNote(''); }, 2500);
            return true;
        }
        function addRow(row) { if (empty.parentNode) empty.remove(); list.appendChild(row); list.scrollTop = list.scrollHeight; }
        function failRow(row, err, again) {
            row.classList.remove('is-sending'); row.classList.add('is-failed');
            var st = err && err.status && err.status.status;
            row.querySelector('.lc-meta').textContent = st === 429 ? 'Too fast. Wait a moment, then tap to retry.' : st === 409 ? 'Not sent. They have not turned on chat yet.' : st === 413 ? 'Too big. Maximum is 10 MB.' : 'Not sent. Tap to retry.';
            row.onclick = function () { row.remove(); again(); };
        }
        function postSend(k, plain, row) {
            return C.encrypt(k, plain, threadId, cfg.role).then(function (enc) {
                return postJson(cfg.urls.send, { ciphertext: enc.ciphertext, iv: enc.iv, sender_key_id: me.id, recipient_key_id: other.id });
            }).then(function () { row.classList.remove('is-sending'); return poll(); });
        }

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var text = input.value.trim();
            if (!text || notReady()) return;
            input.value = ''; autosize(); toggleSend(); pick.hidden = true;
            var row = bubble({ id: 'tmp-' + Date.now(), sender: cfg.role, at: new Date().toISOString(), read: false }, text, false);
            row.dataset.body = text; row.classList.add('is-sending'); addRow(row);
            aes(privateFor(me.fingerprint), other.jwk).then(function (k) { return postSend(k, text, row); })
                .catch(function (err) { failRow(row, err, function () { input.value = text; autosize(); toggleSend(); input.focus(); }); });
        });

        // photo / file / voice note: encrypt the bytes here, upload ciphertext, then send a small encrypted message that points to it
        function sendAttachment(blob, kind, meta, caption) {
            if (notReady()) return;
            if (caption === undefined) { caption = input.value.trim(); input.value = ''; autosize(); toggleSend(); }
            pick.hidden = true;
            var env = { k: kind, f: { n: meta.n, m: meta.m, s: blob.size, d: meta.d || 0 }, t: caption };
            var row = bubble({ id: 'tmp-' + Date.now(), sender: cfg.role, at: new Date().toISOString(), read: false }, caption, false, env, null, blob);
            row.classList.add('is-sending'); addRow(row);
            var key;
            aes(privateFor(me.fingerprint), other.jwk).then(function (k) {
                key = k;
                return blob.arrayBuffer();
            }).then(function (buf) { return C.encryptBytes(key, buf, threadId, cfg.role); }).then(function (enc) {
                return fetch(cfg.urls.upload, {
                    method: 'POST', body: enc.data, credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/octet-stream', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': cfg.csrf }
                }).then(function (r) { if (!r.ok) { var e = new Error('HTTP ' + r.status); e.status = r; throw e; } return r.json(); }).then(function (r) {
                    row.dataset.fid = r.id;
                    return postSend(key, JSON.stringify({ __lc: 1, k: kind, t: caption, f: { id: r.id, n: meta.n, m: meta.m, s: blob.size, iv: enc.iv, d: meta.d || 0 } }), row);
                });
            }).catch(function (err) { failRow(row, err, function () { sendAttachment(blob, kind, meta, caption); }); });
        }

        btnClip.onclick = function () { if (!notReady()) fileIn.click(); };
        fileIn.addEventListener('change', function () {
            var f = fileIn.files && fileIn.files[0]; fileIn.value = '';
            if (!f) return;
            if (f.size > MAX_FILE) { setNote('This file is too big. The limit is 10 MB.'); setTimeout(function () { setNote(''); }, 3500); return; }
            sendAttachment(f, SAFE_IMG.test(f.type) ? 'image' : 'file', { n: f.name || 'file', m: f.type || 'application/octet-stream' });
        });

        // ---------- emoji ----------
        (function () {
            var tabs = el('div', 'lc-emoji-tabs'), grid = el('div', 'lc-emoji-grid'), cur = '';
            function show(name) {
                cur = name; grid.textContent = '';
                [].forEach.call(tabs.children, function (t) { t.classList.toggle('is-on', t.dataset.n === name); });
                EMOJI[name].split(' ').forEach(function (g) {
                    var b = el('button', 'lc-emoji-i', g); b.type = 'button';
                    b.onclick = function () {
                        var st = input.selectionStart == null ? input.value.length : input.selectionStart, en = input.selectionEnd == null ? st : input.selectionEnd;
                        if (input.value.length + g.length > 2000) return;
                        input.setRangeText(g, st, en, 'end'); input.focus(); autosize(); toggleSend();
                    };
                    grid.appendChild(b);
                });
            }
            Object.keys(EMOJI).forEach(function (n) {
                var t = el('button', 'lc-emoji-tab', n); t.type = 'button'; t.dataset.n = n; t.onclick = function () { show(n); }; tabs.appendChild(t);
            });
            pick.appendChild(tabs); pick.appendChild(grid); show('Smileys');
            btnEmoji.onclick = function (e) { e.stopPropagation(); pick.hidden = !pick.hidden; };
            document.addEventListener('click', function (e) { if (!pick.hidden && !pick.contains(e.target) && e.target !== btnEmoji && !btnEmoji.contains(e.target)) pick.hidden = true; });
            document.addEventListener('keydown', function (e) { if (e.key === 'Escape') pick.hidden = true; });
        })();

        // ---------- voice message ----------
        var recState = null;
        function recTick() {
            if (!recState) return;
            var sec = (Date.now() - recState.t0) / 1000; recTime.textContent = mmss(sec);
            if (sec >= MAX_REC) stopRec(true);
        }
        function startRec() {
            if (notReady()) return;
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia || !window.MediaRecorder) { setNote('Voice messages need a secure (https) page and an up-to-date browser.'); return; }
            navigator.mediaDevices.getUserMedia({ audio: true }).then(function (stream) {
                var type = ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4', 'audio/ogg;codecs=opus'].filter(function (t) { return MediaRecorder.isTypeSupported(t); })[0];
                var mr = new MediaRecorder(stream, type ? { mimeType: type } : undefined), chunks = [];
                mr.ondataavailable = function (e) { if (e.data && e.data.size) chunks.push(e.data); };
                mr.start(250);
                recState = { mr: mr, stream: stream, chunks: chunks, t0: Date.now(), timer: setInterval(recTick, 250) };
                recTime.textContent = '0:00'; form.hidden = true; rec.hidden = false; pick.hidden = true; setNote('');
            }).catch(function () { setNote('Microphone is blocked. Allow it in your browser settings and try again.'); });
        }
        function stopRec(sendIt) {
            var st = recState; if (!st) return; recState = null; clearInterval(st.timer);
            rec.hidden = true; form.hidden = false;
            st.mr.onstop = function () {
                st.stream.getTracks().forEach(function (t) { t.stop(); });
                if (!sendIt) return;
                var dur = (Date.now() - st.t0) / 1000;
                if (dur < 1) { setNote('Hold a little longer to record a voice message.'); setTimeout(function () { setNote(''); }, 2500); return; }
                var type = (st.mr.mimeType || 'audio/webm').split(';')[0], blob = new Blob(st.chunks, { type: type });
                sendAttachment(blob, 'audio', { n: 'voice-message', m: type, d: Math.round(dur) }, '');
            };
            if (st.mr.state !== 'inactive') st.mr.stop(); else st.mr.onstop();
        }
        btnMic.onclick = startRec;
        recCancel.onclick = function () { stopRec(false); };
        recSend.onclick = function () { stopRec(true); };

        // ---------- security code + backup panels ----------
        function showPanel(kind) {
            if (!panel.hidden && panel.dataset.kind === kind) { panel.hidden = true; return; }
            panel.hidden = false; panel.dataset.kind = kind; panel.textContent = '';
            if (kind === 'code') {
                panel.appendChild(el('b', null, 'Security code'));
                var code = el('div', 'lc-code', other && me ? '…' : 'Available once both of you have opened chat.');
                panel.appendChild(code);
                panel.appendChild(el('p', null, 'Only your browser and ' + cfg.otherName + "'s browser can read these messages. Not even Libraro's server can. To be sure nobody swapped a key on the way, ask them to open this page and compare this code. It must be exactly the same on both screens."));
                if (other && me) C.safetyCode(me.jwk, other.jwk).then(function (c) { code.textContent = c; });
            } else {
                panel.appendChild(el('b', null, 'Key backup'));
                panel.appendChild(el('p', null, 'Your private key lives only in this browser. If you clear browser data or use a new phone or computer, you cannot read old messages unless you saved a backup. The backup is locked with a passphrase you choose. Libraro cannot recover it for you.'));
                var pass = el('input', 'lc-field'); pass.type = 'password'; pass.placeholder = 'Passphrase (8+ characters)'; pass.autocomplete = 'new-password';
                var blob = el('textarea', 'lc-field'); blob.rows = 3; blob.placeholder = 'Backup text appears here. To restore, paste it here.';
                var msg = el('div', 'lc-msg');
                var bx = el('button', 'lc-link is-solid', 'Create backup'); bx.type = 'button';
                var br = el('button', 'lc-link', 'Restore from backup'); br.type = 'button';
                bx.onclick = function () {
                    if (pass.value.length < 8) { msg.textContent = 'Use a passphrase with at least 8 characters.'; return; }
                    msg.textContent = 'Working…';
                    K.exportBackup(cfg.scope, pass.value).then(function (t) { blob.value = t; msg.textContent = 'Copy this text and keep it safe. You also need the passphrase.'; }).catch(function (e) { msg.textContent = e.message; });
                };
                br.onclick = function () {
                    if (!blob.value.trim() || !pass.value) { msg.textContent = 'Paste your backup text and enter the passphrase.'; return; }
                    msg.textContent = 'Restoring…';
                    K.importBackup(cfg.scope, blob.value, pass.value).then(function () { msg.textContent = 'Restored. Reloading…'; setTimeout(function () { location.reload(); }, 600); }).catch(function (e) { msg.textContent = e.message; });
                };
                var r = el('div', 'lc-btnrow'); r.appendChild(bx); r.appendChild(br);
                panel.appendChild(pass); panel.appendChild(blob); panel.appendChild(r); panel.appendChild(msg);
            }
        }
        btnCode.onclick = function () { showPanel('code'); };
        btnBackup.onclick = function () { showPanel('backup'); };

        // ---------- start ----------
        if (!window.crypto || !crypto.subtle || !window.indexedDB) {
            lock(true, 'This browser cannot do private chat. Open the site over https in an up-to-date browser.');
            return;
        }
        K.ensureKey(cfg.scope).then(function (k) {
            return postJson(cfg.urls.key, { jwk: k.publicJwk }).then(function (r) {
                me = { id: r.me.id, jwk: r.me.jwk, fingerprint: r.me.fingerprint }; threadId = r.thread_id;
                return K.allKeys(cfg.scope).then(function (all) { localKeys = all; ready = true; setPeer(r[cfg.otherProp] || null); });
            });
        }).then(function () { return poll(); }).then(loop).catch(function () { lock(true, 'Could not start chat. Check your internet and reload.'); });
    }

    window.LibraroChat = { mount: mount };
})();
