/* ==========================================================================
   Libraro new design — website behaviour (vanilla JS, no jQuery)
   Uses the SAME backend endpoints as the old home page:
     - GET  subscriptions.getSubscriptionPrice  (plan_mode 1 = monthly, 2 = yearly)
     - POST store.selected.plan                  (then redirect to register)
     - POST demo-request
   ========================================================================== */
(function () {
    'use strict';
    var doc = document;
    var cfg = window.LB_SITE || {};
    var csrf = function () { var m = doc.querySelector('meta[name="csrf-token"]'); return m ? m.getAttribute('content') : ''; };

    /* ---------- Toast ---------- */
    var toastEl, toastT;
    function toast(msg) {
        if (!toastEl) { toastEl = doc.createElement('div'); toastEl.className = 'lb-toast'; toastEl.setAttribute('role', 'status'); doc.body.appendChild(toastEl); }
        toastEl.textContent = msg; toastEl.classList.add('is-on');
        clearTimeout(toastT); toastT = setTimeout(function () { toastEl.classList.remove('is-on'); }, 2600);
    }
    window.LB = window.LB || {}; window.LB.toast = toast;

    /* ---------- Mobile menu ---------- */
    var nav = doc.querySelector('.lb-nav');
    if (nav) {
        var burger = nav.querySelector('.lb-nav-burger');
        var setOpen = function (open) {
            nav.classList.toggle('is-open', open);
            if (burger) burger.setAttribute('aria-expanded', open ? 'true' : 'false');
        };
        if (burger) burger.addEventListener('click', function () { setOpen(!nav.classList.contains('is-open')); });
        nav.querySelectorAll('.lb-nav-menu a').forEach(function (a) { a.addEventListener('click', function () { setOpen(false); }); });
        doc.addEventListener('keydown', function (e) { if (e.key === 'Escape') setOpen(false); });
        window.addEventListener('resize', function () { if (window.innerWidth > 900) setOpen(false); });
    }

    /* ---------- Horizontal slider arrows ---------- */
    doc.querySelectorAll('[data-lb-track]').forEach(function (track) {
        var id = track.getAttribute('data-lb-track');
        var step = function () { var c = track.firstElementChild; return c ? c.offsetWidth + 16 : 376; };
        doc.querySelectorAll('[data-lb-prev="' + id + '"]').forEach(function (b) { b.addEventListener('click', function () { track.scrollBy({ left: -step(), behavior: 'smooth' }); }); });
        doc.querySelectorAll('[data-lb-next="' + id + '"]').forEach(function (b) { b.addEventListener('click', function () { track.scrollBy({ left: step(), behavior: 'smooth' }); }); });
    });

    /* ---------- Testimonial slider: dots + gentle autoplay (pauses on hover/focus/touch) ---------- */
    (function () {
        var track = doc.querySelector('[data-lb-track="rv"]');
        var dots = doc.querySelector('[data-lb-dots="rv"]');
        if (!track) return;
        var cards = Array.prototype.slice.call(track.children);
        var pages = function () { var per = Math.max(1, Math.round(track.clientWidth / (cards[0].offsetWidth + 16))); return Math.max(1, cards.length - per + 1); };
        var current = function () { return Math.round(track.scrollLeft / (cards[0].offsetWidth + 16)); };
        var go = function (i) { track.scrollTo({ left: i * (cards[0].offsetWidth + 16), behavior: 'smooth' }); };
        var build = function () {
            if (!dots) return;
            var n = pages(); dots.innerHTML = '';
            dots.hidden = n < 2;
            var ctrl = dots.closest('.lb-rv-ctrl'); if (ctrl) ctrl.hidden = n < 2;
            for (var i = 0; i < n; i++) {
                var b = doc.createElement('button'); b.type = 'button'; b.setAttribute('aria-label', 'Go to review ' + (i + 1));
                (function (k) { b.addEventListener('click', function () { go(k); }); })(i);
                dots.appendChild(b);
            }
            mark();
        };
        var mark = function () {
            if (!dots) return;
            var c = Math.min(current(), dots.children.length - 1);
            Array.prototype.forEach.call(dots.children, function (b, i) { b.setAttribute('aria-current', i === c ? 'true' : 'false'); });
        };
        var t; track.addEventListener('scroll', function () { clearTimeout(t); t = setTimeout(mark, 80); }, { passive: true });
        build(); window.addEventListener('resize', build);
        var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (reduce || cards.length < 2) return;
        var paused = false, wrap = track.closest('[data-lb-rv]') || track;
        ['mouseenter', 'focusin', 'touchstart'].forEach(function (ev) { wrap.addEventListener(ev, function () { paused = true; }, { passive: true }); });
        ['mouseleave', 'focusout'].forEach(function (ev) { wrap.addEventListener(ev, function () { paused = false; }); });
        setInterval(function () {
            if (paused || doc.hidden) return;
            var r = track.getBoundingClientRect();
            if (r.bottom < 0 || r.top > window.innerHeight) return;
            var n = pages(), c = current();
            go(c + 1 >= n ? 0 : c + 1);
        }, 5000);
    })();

    /* ---------- Hero parallax (transform only, skipped for reduced motion) ---------- */
    (function () {
        var bg = doc.querySelector('[data-lb-parallax]');
        if (!bg || (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches)) return;
        var hero = bg.parentElement, ticking = false;
        var update = function () {
            ticking = false;
            var h = hero.offsetHeight, y = window.scrollY || window.pageYOffset;
            if (y > h) return;
            bg.style.transform = 'translate3d(0,' + (y * 0.35).toFixed(1) + 'px,0) scale(' + (1 + y / h * 0.06).toFixed(4) + ')';
        };
        window.addEventListener('scroll', function () { if (!ticking) { ticking = true; requestAnimationFrame(update); } }, { passive: true });
        update();
    })();

    /* ---------- Pricing: monthly / yearly ---------- */
    var planMode = 1;
    var inr = function (n) {
        var x = Number(n);
        if (!isFinite(x)) return n;
        return '₹' + x.toLocaleString('en-IN', { maximumFractionDigits: 0 });
    };
    function loadPrices(mode) {
        if (!cfg.priceUrl) return;
        planMode = mode;
        doc.querySelectorAll('[data-billing]').forEach(function (b) { b.setAttribute('aria-pressed', String(b.getAttribute('data-billing')) === String(mode) ? 'true' : 'false'); });
        var plans = doc.querySelectorAll('[data-plan-id]');
        plans.forEach(function (p) { p.classList.add('lb-price-loading'); });
        fetch(cfg.priceUrl + '?plan_mode=' + encodeURIComponent(mode), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); })
            .then(function (res) {
                var per = mode == 2 ? '/year' : '/month';
                var perShort = mode == 2 ? '/yr' : '/mo';
                (res.subscription_prices || []).forEach(function (s) {
                    var fees = Number(s.fees) || 0, slash = Number(s.slash_price) || 0;
                    doc.querySelectorAll('[data-price-for="' + s.id + '"]').forEach(function (el) { el.textContent = inr(fees); });
                    doc.querySelectorAll('[data-per-for="' + s.id + '"]').forEach(function (el) { el.textContent = per; });
                    doc.querySelectorAll('[data-cmp-price-for="' + s.id + '"]').forEach(function (el) { el.textContent = inr(fees) + perShort; });
                    doc.querySelectorAll('[data-note-for="' + s.id + '"]').forEach(function (el) {
                        el.textContent = '';
                        if (slash > fees) {
                            var st = doc.createElement('s'); st.textContent = inr(slash);
                            var b = doc.createElement('b'); b.textContent = 'You save ' + inr(slash - fees);
                            el.appendChild(st); el.appendChild(b);
                        } else {
                            el.textContent = mode == 2 ? 'Billed yearly' : 'Billed monthly';
                        }
                    });
                });
            })
            .catch(function () { toast('Could not load prices. Please refresh the page.'); })
            .then(function () { plans.forEach(function (p) { p.classList.remove('lb-price-loading'); }); });
    }
    doc.querySelectorAll('[data-billing]').forEach(function (b) {
        b.addEventListener('click', function () { loadPrices(parseInt(b.getAttribute('data-billing'), 10)); });
    });
    if (doc.querySelector('[data-plan-id]')) loadPrices(1);

    /* ---------- Buy now → remember plan in session → register ---------- */
    doc.addEventListener('click', function (e) {
        var btn = e.target.closest('.buy-now-btn');
        if (!btn || !cfg.selectPlanUrl) return;
        e.preventDefault();
        if (btn.dataset.busy === '1') return;
        btn.dataset.busy = '1'; btn.classList.add('is-loading');
        var body = new FormData();
        body.append('_token', csrf());
        body.append('plan_id', btn.getAttribute('data-id'));
        body.append('plan_mode', planMode);
        fetch(cfg.selectPlanUrl, { method: 'POST', body: body, credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf() } })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res && res.success) { window.location.href = cfg.registerUrl; return; }
                throw new Error('fail');
            })
            .catch(function () {
                btn.dataset.busy = ''; btn.classList.remove('is-loading');
                window.location.href = cfg.registerUrl; // still let them sign up
            });
    });

    /* ---------- Mobile: show all features ---------- */
    doc.addEventListener('click', function (e) {
        var b = e.target.closest('.lb-plan-more');
        if (!b) return;
        var plan = b.closest('.lb-plan');
        var open = !plan.classList.contains('is-expanded');
        plan.classList.toggle('is-expanded', open);
        b.setAttribute('aria-expanded', open ? 'true' : 'false');
        var l = b.querySelector('.lb-plan-more-label');
        if (l) l.textContent = open ? 'Show less' : l.getAttribute('data-more');
    });

    /* ---------- Compare plans: groups ---------- */
    var groups = doc.querySelectorAll('.lb-cmp-group');
    function setGroup(btn, open) {
        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        var rows = doc.getElementById(btn.getAttribute('aria-controls'));
        if (rows) rows.hidden = !open;
    }
    groups.forEach(function (g, i) {
        if (window.innerWidth < 760 && i > 0) setGroup(g, false);
        g.addEventListener('click', function () { setGroup(g, g.getAttribute('aria-expanded') !== 'true'); });
    });
    var ex = doc.querySelector('[data-cmp="expand"]'), co = doc.querySelector('[data-cmp="collapse"]');
    if (ex) ex.addEventListener('click', function () { groups.forEach(function (g) { setGroup(g, true); }); });
    if (co) co.addEventListener('click', function () { groups.forEach(function (g) { setGroup(g, false); }); });

    /* ---------- FAQ: one open at a time ---------- */
    var faqs = doc.querySelectorAll('.lb-faq details');
    faqs.forEach(function (d) {
        d.addEventListener('toggle', function () {
            if (d.open) faqs.forEach(function (o) { if (o !== d) o.open = false; });
        });
    });

    /* ---------- Review "read more" dialog ---------- */
    var dlg = doc.getElementById('lb-review-dialog');
    doc.addEventListener('click', function (e) {
        var b = e.target.closest('.lb-review-more');
        if (!b || !dlg) return;
        dlg.querySelector('[data-r-text]').textContent = b.getAttribute('data-text');
        dlg.querySelector('[data-r-name]').textContent = b.getAttribute('data-name');
        dlg.querySelector('[data-r-sub]').textContent = b.getAttribute('data-sub');
        dlg.querySelector('[data-r-initial]').textContent = (b.getAttribute('data-name') || 'L').trim().charAt(0).toUpperCase();
        if (dlg.showModal) dlg.showModal(); else dlg.setAttribute('open', '');
    });
    if (dlg) {
        dlg.addEventListener('click', function (e) { if (e.target === dlg || e.target.closest('.lb-dialog-close')) dlg.close ? dlg.close() : dlg.removeAttribute('open'); });
    }
    // Only show "Read more" when the text is actually clamped.
    function markClamped() {
        doc.querySelectorAll('.lb-review').forEach(function (r) {
            var t = r.querySelector('.lb-review-text'), m = r.querySelector('.lb-review-more');
            if (t && m) m.hidden = t.scrollHeight <= t.clientHeight + 2;
        });
    }
    markClamped(); window.addEventListener('resize', markClamped);

    /* ---------- AJAX forms (demo request, contact callback) — same endpoints as before ----------
       <form data-lb-ajax="URL">; success panel = .lb-form-success inside the same card. */
    doc.querySelectorAll('form[data-lb-ajax]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (e.defaultPrevented) return; // client validation failed (lb-core)
            e.preventDefault();
            var btn = form.querySelector('button[type=submit]');
            fetch(form.getAttribute('data-lb-ajax'), { method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, status: r.status, j: j }; }, function () { return { ok: false, status: r.status, j: null }; }); })
                .then(function (res) {
                    if (res.ok && res.j && res.j.status === 'success') {
                        var card = form.closest('[data-lb-form-card]') || form.parentNode;
                        var ok = card.querySelector('.lb-form-success');
                        form.reset();
                        if (ok) { form.hidden = true; ok.hidden = false; }
                        toast(res.j.message || 'Thank you! We will get back to you soon.');
                    } else if (res.j && res.j.errors) {
                        Object.keys(res.j.errors).forEach(function (k) {
                            var f = form.querySelector('[name="' + k + '"]');
                            var msg = res.j.errors[k]; msg = Array.isArray(msg) ? msg[0] : msg;
                            if (f && window.LB.showError) window.LB.showError(f, msg);
                        });
                    } else if (res.status === 419) {
                        toast('Your session expired. Please refresh the page and try again.');
                    } else {
                        toast('Something went wrong. Please call us at +91 81144 79678.');
                    }
                })
                .catch(function () { toast('Network error. Please try again.'); })
                .then(function () {
                    if (btn) { btn.dataset.busy = ''; btn.classList.remove('is-loading'); var sp = btn.querySelector('.lb-spinner'); if (sp) sp.remove(); }
                });
        });
    });
})();
