/* ==========================================================================
   Libraro new design — shared behaviour (no jQuery needed)
   Used by the redesigned website and auth pages only.
   ========================================================================== */
(function () {
    'use strict';

    var doc = document;
    var root = doc.documentElement;

    /* ---------- Theme toggle ---------- */
    function setTheme(t) {
        root.setAttribute('data-theme', t);
        try { localStorage.setItem('lb-theme', t); } catch (e) { /* private mode */ }
        doc.querySelectorAll('.lb-theme-toggle').forEach(function (b) {
            b.setAttribute('aria-label', t === 'dark' ? 'Switch to light mode' : 'Switch to dark mode');
            b.setAttribute('aria-pressed', t === 'dark' ? 'true' : 'false');
        });
    }
    doc.addEventListener('click', function (e) {
        var btn = e.target.closest('.lb-theme-toggle');
        if (!btn) return;
        setTheme(root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
    });
    // Follow the OS setting live, unless the user picked a theme themselves.
    if (window.matchMedia) {
        var mq = window.matchMedia('(prefers-color-scheme: dark)');
        var onChange = function (ev) {
            var saved = null;
            try { saved = localStorage.getItem('lb-theme'); } catch (e) {}
            if (!saved) root.setAttribute('data-theme', ev.matches ? 'dark' : 'light');
        };
        if (mq.addEventListener) mq.addEventListener('change', onChange); else if (mq.addListener) mq.addListener(onChange);
    }
    setTheme(root.getAttribute('data-theme') || 'light');

    /* ---------- Show / hide password ---------- */
    doc.addEventListener('click', function (e) {
        var btn = e.target.closest('.lb-eye');
        if (!btn) return;
        e.preventDefault();
        var box = btn.closest('.lb-input');
        var input = box && box.querySelector('input');
        if (!input) return;
        var show = input.type === 'password';
        // Toggle every password box in the same form together (password + confirm).
        var form = btn.closest('form') || doc;
        form.querySelectorAll('.lb-eye').forEach(function (b) {
            var i = b.closest('.lb-input').querySelector('input');
            if (i) i.type = show ? 'text' : 'password';
            b.classList.toggle('is-on', show);
            b.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
        });
    });

    /* ---------- Input filters (same behaviour as main-validation.js) ---------- */
    doc.addEventListener('input', function (e) {
        var el = e.target;
        if (el.classList.contains('digit-only')) {
            var v = el.value.replace(/\D/g, '');
            var max = parseInt(el.getAttribute('data-maxlength') || el.maxLength, 10);
            // Pasted "+91 98765 43210" keeps the last 10 digits.
            if (max > 0 && v.length > max) v = e.inputType === 'insertFromPaste' ? v.slice(-max) : v.slice(0, max);
            if (v !== el.value) el.value = v;
        } else if (el.classList.contains('char-only')) {
            var c = el.value.replace(/[^A-Za-z\s.&'-]/g, '');
            if (c !== el.value) el.value = c;
        }
    });

    /* ---------- Client-side validation + double-submit guard ---------- */
    var MSG = {
        valueMissing: 'This field is required.',
        typeMismatch: 'Please enter a valid value.',
        tooShort: 'Too short.',
        patternMismatch: 'Please match the requested format.'
    };

    function fieldBox(input) {
        return input.closest('.lb-input') || input;
    }
    function fieldWrap(input) {
        return input.closest('.lb-field') || input.closest('.lb-check-wrap') || input.parentNode;
    }
    function clearError(input) {
        fieldBox(input).classList.remove('is-invalid');
        if (input.type === 'checkbox') input.classList.remove('is-invalid');
        var wrap = fieldWrap(input);
        var err = wrap && wrap.querySelector('.lb-error[data-client]');
        if (err) err.remove();
    }
    function showError(input, text) {
        clearError(input);
        fieldBox(input).classList.add('is-invalid');
        if (input.type === 'checkbox') input.classList.add('is-invalid');
        var wrap = fieldWrap(input);
        if (!wrap || wrap.querySelector('.lb-error:not([data-client])')) return;
        var span = doc.createElement('span');
        span.className = 'lb-error';
        span.setAttribute('data-client', '');
        span.setAttribute('role', 'alert');
        span.textContent = window.LB_T ? window.LB_T(text) : text; // website pages: Hindi / Hinglish when chosen
        wrap.appendChild(span);
    }
    function messageFor(input) {
        var v = input.validity;
        if (input.dataset.msg && !v.valid) return input.dataset.msg;
        if (v.valueMissing) {
            if (input.type === 'checkbox') return 'Please accept to continue.';
            return MSG.valueMissing;
        }
        if (v.typeMismatch && input.type === 'email') return 'Please enter a valid email address.';
        if (v.tooShort) return 'Use at least ' + input.minLength + ' characters.';
        if (v.patternMismatch) return input.title || MSG.patternMismatch;
        return input.validationMessage || MSG.typeMismatch;
    }

    doc.addEventListener('input', function (e) {
        if (e.target.matches && e.target.matches('.lb-form input, .lb-form select, .lb-form textarea')) clearError(e.target);
    });
    doc.addEventListener('change', function (e) {
        if (e.target.matches && e.target.matches('.lb-form input[type=checkbox]')) clearError(e.target);
    });

    doc.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form.classList || !form.classList.contains('lb-form')) return;

        // Custom validators hook (e.g. confirm password, OTP).
        if (typeof form.lbBeforeValidate === 'function' && form.lbBeforeValidate() === false) {
            e.preventDefault();
            return;
        }

        var firstBad = null;
        Array.prototype.forEach.call(form.elements, function (el) {
            if (!el.willValidate || el.type === 'hidden' || el.classList.contains('no-validate')) return;
            if (!el.checkValidity()) {
                showError(el, messageFor(el));
                if (!firstBad) firstBad = el;
            }
        });
        if (firstBad) {
            e.preventDefault();
            firstBad.focus({ preventScroll: false });
            return;
        }

        var btn = form.querySelector('button[type=submit]');
        if (btn) {
            if (btn.dataset.busy === '1') { e.preventDefault(); return; }
            btn.dataset.busy = '1';
            btn.classList.add('is-loading');
            var label = btn.querySelector('.lb-btn-label');
            if (label && btn.dataset.loadingText) label.textContent = btn.dataset.loadingText;
            var sp = doc.createElement('span');
            sp.className = 'lb-spinner';
            btn.insertBefore(sp, btn.firstChild);
            // Safety: re-enable after 15s in case navigation was blocked.
            setTimeout(function () {
                btn.dataset.busy = '';
                btn.classList.remove('is-loading');
                if (sp.parentNode) sp.remove();
            }, 15000);
        }
    }, true);
    // Turn off browser bubbles: we render our own messages.
    doc.querySelectorAll('form.lb-form').forEach(function (f) { f.setAttribute('novalidate', ''); });

    /* ---------- Keep CSRF token fresh on long-idle tabs ---------- */
    var csrfUrl = root.getAttribute('data-csrf-refresh');
    if (csrfUrl) {
        doc.addEventListener('visibilitychange', function () {
            if (doc.visibilityState !== 'visible') return;
            fetch(csrfUrl, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.ok ? r.json() : null; })
                .then(function (d) {
                    if (!d || !d.token) return;
                    doc.querySelectorAll('input[name="_token"]').forEach(function (i) { i.value = d.token; });
                    var m = doc.querySelector('meta[name="csrf-token"]');
                    if (m) m.setAttribute('content', d.token);
                })
                .catch(function () {});
        });
    }

    /* ---------- Reload pages restored from back/forward cache (stale CSRF) ---------- */
    if (doc.body && doc.body.hasAttribute('data-bfcache-reload')) {
        window.addEventListener('pageshow', function (ev) {
            if (ev.persisted) window.location.reload();
        });
    }

    /* ---------- Reveal on scroll ---------- */
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if ('IntersectionObserver' in window && !reduce) {
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (en) {
                if (en.isIntersecting) { en.target.classList.remove('is-hidden'); io.unobserve(en.target); }
            });
        }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
        doc.querySelectorAll('[data-reveal]').forEach(function (el) {
            if (el.getBoundingClientRect().top < window.innerHeight) return;
            var pe = el.parentElement;
            if (pe && pe.scrollWidth > pe.clientWidth + 4) return; // inside a swipe track
            el.classList.add('is-hidden');
            io.observe(el);
        });
    }

    /* ---------- Motion: staggered card reveal + cursor spotlight ---------- */
    var CARD_SEL = '.lb-pain, .lb-op, .lb-plan, .lb-review, .lb-value, .lb-contact-card, .lb-truststrip li, .lb-legal-sec, .lb-legal-glance li, .lb-vs2-row, .lb-faq details, .lb-tile, .lb-who-card, .lb-lib, .lb-post-card, .lb-related-card, .lb-fact, .lb-facility, .lb-cmp-card, .lb-legal-help, .lb-demo-card';
    var SPOT_SEL = '.lb-pain, .lb-plan, .lb-value, .lb-contact-card, .lb-who-card, .lb-legal-glance li, .lb-suite-panel, .lb-review, .lb-lib';
    var revealIO = ('IntersectionObserver' in window && !reduce) ? new IntersectionObserver(function (entries) {
        entries.forEach(function (en) {
            if (!en.isIntersecting) return;
            en.target.classList.remove('is-hidden');
            revealIO.unobserve(en.target);
            setTimeout(function () { en.target.classList.remove('lb-auto-reveal'); en.target.style.removeProperty('--lb-i'); }, 1400);
        });
    }, { threshold: 0.08, rootMargin: '0px 0px -30px 0px' }) : null;
    function prepCards(root) {
        if (!revealIO || !root.querySelectorAll) return;
        var groups = new Map();
        root.querySelectorAll(CARD_SEL).forEach(function (el) {
            if (el.dataset.lbMotion || el.closest('[data-reveal], .lb-qc, dialog, [hidden]')) return;
            el.dataset.lbMotion = '1';
            if (el.getBoundingClientRect().top < window.innerHeight * 0.92) return; // already on screen: no flash
            var key = el.parentElement;
            if (key && key.scrollWidth > key.clientWidth + 4) return; // swipe tracks: show as-is
            var n = groups.get(key) || 0; groups.set(key, n + 1);
            el.style.setProperty('--lb-i', Math.min(n, 6));
            el.classList.add('lb-auto-reveal', 'is-hidden');
            revealIO.observe(el);
        });
    }
    function prepSpots(root) {
        if (!root.querySelectorAll || !window.matchMedia('(hover: hover) and (pointer: fine)').matches) return;
        root.querySelectorAll(SPOT_SEL).forEach(function (el) { el.classList.add('lb-spot'); });
    }
    prepCards(doc); prepSpots(doc);
    if (window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches) {
        doc.addEventListener('pointermove', function (e) {
            var el = e.target.closest && e.target.closest('.lb-spot');
            if (!el) return;
            var r = el.getBoundingClientRect();
            el.style.setProperty('--mx', (e.clientX - r.left) + 'px');
            el.style.setProperty('--my', (e.clientY - r.top) + 'px');
        }, { passive: true });
    }
    // Cards added later (e.g. "load more" libraries) get the same treatment.
    if ('MutationObserver' in window && doc.body) {
        new MutationObserver(function (muts) {
            muts.forEach(function (m) { m.addedNodes.forEach(function (n) { if (n.nodeType === 1) { prepCards(n.parentElement || n); prepSpots(n.parentElement || n); } }); });
        }).observe(doc.body, { childList: true, subtree: true });
    }

    window.LB = window.LB || {};
    window.LB.setTheme = setTheme;
    window.LB.showError = showError;
    window.LB.clearError = clearError;
})();
