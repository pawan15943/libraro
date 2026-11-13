/*
 * Libraro chat: end-to-end encryption helper (WebCrypto only, no libraries).
 *   ECDH P-256 key pair per browser -> HKDF-SHA-256 (info "libraro-chat-v1", salt = thread id) -> AES-256-GCM.
 * Private keys never leave the browser (IndexedDB). The server only sees public keys and ciphertext.
 * Exposes window.LibraroCrypto (pure crypto) and window.LibraroKeys (IndexedDB key store + backup).
 */
(function (root) {
    'use strict';
    var C = (typeof crypto !== 'undefined' && crypto.subtle) ? crypto : (typeof require === 'function' ? require('crypto').webcrypto : null);
    var S = C.subtle, enc = new TextEncoder(), dec = new TextDecoder();

    function b64(buf) { var a = new Uint8Array(buf), s = ''; for (var i = 0; i < a.length; i++) s += String.fromCharCode(a[i]); return btoa(s); }
    function unb64(str) { var s = atob(str), a = new Uint8Array(s.length); for (var i = 0; i < s.length; i++) a[i] = s.charCodeAt(i); return a; }
    function hex(buf) { return Array.prototype.map.call(new Uint8Array(buf), function (x) { return ('0' + x.toString(16)).slice(-2); }).join(''); }

    function publicOf(jwk) { return { kty: 'EC', crv: 'P-256', x: jwk.x, y: jwk.y }; }

    // same value the server stores: sha256(x + "." + y) as hex
    function fingerprint(jwk) { return S.digest('SHA-256', enc.encode(jwk.x + '.' + jwk.y)).then(hex); }

    function generatePair() {
        return S.generateKey({ name: 'ECDH', namedCurve: 'P-256' }, true, ['deriveBits']).then(function (kp) {
            return Promise.all([S.exportKey('jwk', kp.privateKey), S.exportKey('jwk', kp.publicKey)]).then(function (r) {
                return { privateJwk: r[0], publicJwk: publicOf(r[1]) };
            });
        });
    }

    function importPrivate(jwk) { return S.importKey('jwk', jwk, { name: 'ECDH', namedCurve: 'P-256' }, false, ['deriveBits']); }

    // AES-GCM key shared by me and the other side for one thread
    function deriveKey(privateJwk, otherPublicJwk, threadId) {
        return Promise.all([
            importPrivate(privateJwk),
            S.importKey('jwk', publicOf(otherPublicJwk), { name: 'ECDH', namedCurve: 'P-256' }, false, [])
        ]).then(function (k) {
            return S.deriveBits({ name: 'ECDH', public: k[1] }, k[0], 256);
        }).then(function (bits) {
            return S.importKey('raw', bits, 'HKDF', false, ['deriveKey']);
        }).then(function (hk) {
            return S.deriveKey({ name: 'HKDF', hash: 'SHA-256', salt: enc.encode(String(threadId)), info: enc.encode('libraro-chat-v1') },
                hk, { name: 'AES-GCM', length: 256 }, false, ['encrypt', 'decrypt']);
        });
    }

    function aad(threadId, senderType) { return enc.encode(threadId + ':' + senderType); }

    function encrypt(aesKey, text, threadId, senderType) {
        var iv = C.getRandomValues(new Uint8Array(12));
        return S.encrypt({ name: 'AES-GCM', iv: iv, additionalData: aad(threadId, senderType) }, aesKey, enc.encode(text)).then(function (ct) {
            return { ciphertext: b64(ct), iv: b64(iv) };
        });
    }

    function decrypt(aesKey, ciphertext, iv, threadId, senderType) {
        return S.decrypt({ name: 'AES-GCM', iv: unb64(iv), additionalData: aad(threadId, senderType) }, aesKey, unb64(ciphertext)).then(function (pt) { return dec.decode(pt); });
    }

    // attachments / voice notes: the file bytes are encrypted with the same thread key (its own IV, AAD ends with ":file")
    function aadFile(threadId, senderType) { return enc.encode(threadId + ':' + senderType + ':file'); }
    function encryptBytes(aesKey, bytes, threadId, senderType) {
        var iv = C.getRandomValues(new Uint8Array(12));
        return S.encrypt({ name: 'AES-GCM', iv: iv, additionalData: aadFile(threadId, senderType) }, aesKey, bytes).then(function (ct) { return { data: ct, iv: b64(iv) }; });
    }
    function decryptBytes(aesKey, data, iv, threadId, senderType) {
        return S.decrypt({ name: 'AES-GCM', iv: unb64(iv), additionalData: aadFile(threadId, senderType) }, aesKey, data);
    }

    // 6 groups of 5 digits; identical on both sides (fingerprints are sorted before hashing)
    function safetyCode(jwkA, jwkB) {
        return Promise.all([fingerprint(jwkA), fingerprint(jwkB)]).then(function (f) {
            return S.digest('SHA-256', enc.encode(f.sort().join(':')));
        }).then(function (d) {
            var v = new DataView(d), out = [];
            for (var i = 0; i < 6; i++) out.push(('00000' + (v.getUint32(i * 4) % 100000)).slice(-5));
            return out.join(' ');
        });
    }

    // ---- backup: private keys wrapped with a passphrase (PBKDF2-SHA-256 600k -> AES-GCM) ----
    var ITER = 600000;
    function passKey(pass, salt, iter) {
        return S.importKey('raw', enc.encode(pass), 'PBKDF2', false, ['deriveKey']).then(function (b) {
            return S.deriveKey({ name: 'PBKDF2', hash: 'SHA-256', salt: salt, iterations: iter }, b, { name: 'AES-GCM', length: 256 }, false, ['encrypt', 'decrypt']);
        });
    }
    function wrapBackup(payload, pass) {
        var salt = C.getRandomValues(new Uint8Array(16)), iv = C.getRandomValues(new Uint8Array(12));
        return passKey(pass, salt, ITER).then(function (k) {
            return S.encrypt({ name: 'AES-GCM', iv: iv }, k, enc.encode(JSON.stringify(payload)));
        }).then(function (ct) {
            return 'LCB1.' + btoa(JSON.stringify({ i: ITER, s: b64(salt), v: b64(iv), c: b64(ct) }));
        });
    }
    function unwrapBackup(blob, pass) {
        blob = String(blob || '').trim();
        if (blob.indexOf('LCB1.') !== 0) return Promise.reject(new Error('This is not a Libraro chat backup.'));
        var o;
        try { o = JSON.parse(atob(blob.slice(5))); } catch (e) { return Promise.reject(new Error('This backup text is damaged.')); }
        return passKey(pass, unb64(o.s), o.i).then(function (k) {
            return S.decrypt({ name: 'AES-GCM', iv: unb64(o.v) }, k, unb64(o.c));
        }).then(function (pt) { return JSON.parse(dec.decode(pt)); }, function () { throw new Error('Wrong passphrase or damaged backup.'); });
    }

    var Crypto = { b64: b64, unb64: unb64, fingerprint: fingerprint, generatePair: generatePair, deriveKey: deriveKey, encrypt: encrypt, decrypt: decrypt, encryptBytes: encryptBytes, decryptBytes: decryptBytes, safetyCode: safetyCode, wrapBackup: wrapBackup, unwrapBackup: unwrapBackup, publicOf: publicOf };

    // ---- key store (IndexedDB). One record per key pair; "active" pointer per scope (e.g. "learner:12") ----
    function db() {
        return new Promise(function (res, rej) {
            var r = indexedDB.open('libraro-chat', 1);
            r.onupgradeneeded = function () { var d = r.result; d.createObjectStore('keys', { keyPath: 'id' }); d.createObjectStore('meta', { keyPath: 'scope' }); };
            r.onsuccess = function () { res(r.result); };
            r.onerror = function () { rej(r.error); };
        });
    }
    function tx(store, mode, fn) {
        return db().then(function (d) {
            return new Promise(function (res, rej) {
                var t = d.transaction(store, mode), out = fn(t.objectStore(store));
                t.oncomplete = function () { res(out && out.result !== undefined ? out.result : undefined); };
                t.onerror = function () { rej(t.error); };
            });
        });
    }
    function allKeys(scope) { return tx('keys', 'readonly', function (s) { return s.getAll(); }).then(function (all) { return (all || []).filter(function (k) { return k.scope === scope; }); }); }
    function getActive(scope) { return tx('meta', 'readonly', function (s) { return s.get(scope); }); }
    function setActive(scope, fp) { return tx('meta', 'readwrite', function (s) { s.put({ scope: scope, active: fp }); }); }
    function putKey(scope, pair, fp, createdAt) { return tx('keys', 'readwrite', function (s) { s.put({ id: scope + '/' + fp, scope: scope, fp: fp, privateJwk: pair.privateJwk, publicJwk: pair.publicJwk, createdAt: createdAt || Date.now() }); }); }

    // the key this browser uses now; created on first use
    function ensureKey(scope) {
        return Promise.all([allKeys(scope), getActive(scope)]).then(function (r) {
            var keys = r[0], act = r[1], cur = act && keys.filter(function (k) { return k.fp === act.active; })[0];
            if (cur) return cur;
            if (keys.length && !act) { keys.sort(function (a, b) { return b.createdAt - a.createdAt; }); return setActive(scope, keys[0].fp).then(function () { return keys[0]; }); }
            return generatePair().then(function (pair) {
                return fingerprint(pair.publicJwk).then(function (fp) {
                    return putKey(scope, pair, fp).then(function () { return setActive(scope, fp); }).then(function () { return { fp: fp, privateJwk: pair.privateJwk, publicJwk: pair.publicJwk, scope: scope }; });
                });
            });
        });
    }

    function exportBackup(scope, pass) {
        return Promise.all([allKeys(scope), getActive(scope)]).then(function (r) {
            if (!r[0].length) throw new Error('There is no key on this device to back up.');
            return wrapBackup({ v: 1, scope: scope, active: r[1] && r[1].active, keys: r[0].map(function (k) { return { fp: k.fp, privateJwk: k.privateJwk, publicJwk: k.publicJwk, createdAt: k.createdAt }; }) }, pass);
        });
    }
    function importBackup(scope, blob, pass) {
        return unwrapBackup(blob, pass).then(function (p) {
            if (p.scope !== scope) throw new Error('This backup belongs to a different chat account.');
            return Promise.all(p.keys.map(function (k) { return putKey(scope, k, k.fp, k.createdAt); })).then(function () { return setActive(scope, p.active || p.keys[0].fp); });
        });
    }

    var Keys = { ensureKey: ensureKey, allKeys: allKeys, exportBackup: exportBackup, importBackup: importBackup };

    root.LibraroCrypto = Crypto;
    root.LibraroKeys = Keys;
    if (typeof module !== 'undefined' && module.exports) module.exports = Crypto;
})(typeof window !== 'undefined' ? window : globalThis);
