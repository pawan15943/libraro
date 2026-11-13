/* Libraro home (premium redesign): showcase tabs, YouTube lite embeds, slider progress + autoplay,
   mobile sticky CTA, language menu. Slider arrows, pricing, compare and reviews live in lb-site.js. */
(function () {
    'use strict';
    var doc = document;
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ---------- Operations showcase: tabs + light/dark screens ---------- */
    doc.querySelectorAll('[data-lh-ops]').forEach(function (sec) {
        var list = sec.querySelector('[data-lh-tabs]');
        var tabs = Array.prototype.slice.call(list.querySelectorAll('[role="tab"]'));
        var mac = sec.querySelector('[data-lh-show-mac]');
        var imgL = sec.querySelector('[data-lh-show-img="light"]'), imgD = sec.querySelector('[data-lh-show-img="dark"]');
        var desc = sec.querySelector('[data-lh-show-desc]');
        var segBtns = sec.querySelectorAll('[data-lh-theme-seg] button');
        tabs.forEach(function (t) { [t.getAttribute('data-light'), t.getAttribute('data-dark')].forEach(function (u) { var i = new Image(); i.src = u; }); });
        function setMode(m) {
            mac.classList.toggle('lh-mode-dark', m === 'dark'); mac.classList.toggle('lh-mode-light', m !== 'dark');
            segBtns.forEach(function (b) { b.setAttribute('aria-pressed', b.getAttribute('data-mode') === m ? 'true' : 'false'); });
        }
        var siteTheme = doc.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'light';
        setMode(siteTheme);
        segBtns.forEach(function (b) { b.addEventListener('click', function () { setMode(b.getAttribute('data-mode')); stopAuto(); }); });
        function select(tab, focus) {
            tabs.forEach(function (t) { var on = t === tab; t.setAttribute('aria-selected', on ? 'true' : 'false'); t.tabIndex = on ? 0 : -1; });
            if (focus) tab.focus();
            if (imgL.getAttribute('src') === tab.getAttribute('data-light')) return;
            mac.classList.add('is-swapping');
            setTimeout(function () {
                imgL.src = tab.getAttribute('data-light'); imgD.src = tab.getAttribute('data-dark');
                imgL.alt = 'Libraro ' + tab.getAttribute('data-alt'); imgD.alt = imgL.alt + ' (dark mode)';
                if (desc) desc.textContent = tab.getAttribute('data-desc');
                mac.classList.remove('is-swapping');
            }, reduce ? 0 : 220);
        }
        tabs.forEach(function (t, i) {
            t.addEventListener('click', function () { select(t, false); stopAuto(); });
            t.addEventListener('keydown', function (e) {
                var n = null;
                if (e.key === 'ArrowRight') n = tabs[(i + 1) % tabs.length];
                if (e.key === 'ArrowLeft') n = tabs[(i - 1 + tabs.length) % tabs.length];
                if (n) { e.preventDefault(); select(n, true); stopAuto(); }
            });
        });
        var timer = null, visible = false;
        function stopAuto() { if (timer) { clearInterval(timer); timer = null; } }
        if (!reduce && 'IntersectionObserver' in window) {
            new IntersectionObserver(function (en) { visible = en[0].isIntersecting; }, { threshold: .4 }).observe(mac);
            timer = setInterval(function () {
                if (!visible || doc.hidden) return;
                var cur = tabs.findIndex(function (t) { return t.getAttribute('aria-selected') === 'true'; });
                select(tabs[(cur + 1) % tabs.length], false);
            }, 4500);
        }
    });

    /* ---------- Libraro Shorts: silent preview while visible, tap to play with sound ---------- */
    var shorts = Array.prototype.slice.call(doc.querySelectorAll('[data-lh-short]'));
    function stopFull(except) {
        shorts.forEach(function (c) {
            if (c === except || !c.classList.contains('is-playing')) return;
            var v = c.querySelector('video');
            v.pause(); v.controls = false; v.muted = true; v.loop = true;
            v.src = v.getAttribute('data-preview'); c.classList.remove('is-playing');
            if (c.dataset.visible === '1' && !reduce) v.play().catch(function () {});
        });
    }
    if (shorts.length) {
        var io = 'IntersectionObserver' in window ? new IntersectionObserver(function (en) {
            en.forEach(function (e) {
                var c = e.target, v = c.querySelector('video');
                c.dataset.visible = e.isIntersecting ? '1' : '0';
                if (c.classList.contains('is-playing')) { if (!e.isIntersecting) v.pause(); return; }
                if (e.isIntersecting && !reduce) {
                    if (!v.getAttribute('src')) v.src = v.getAttribute('data-preview');
                    v.play().catch(function () {});
                } else { v.pause(); }
            });
        }, { threshold: .3 }) : null;
        shorts.forEach(function (c) {
            if (io) io.observe(c);
            c.querySelector('[data-lh-short-play]').addEventListener('click', function () {
                var v = c.querySelector('video');
                stopFull(c);
                c.classList.add('is-playing');
                v.src = v.getAttribute('data-full'); v.loop = false; v.muted = false; v.controls = true;
                v.play().catch(function () { v.muted = true; v.play().catch(function () {}); });
            });
            c.querySelector('video').addEventListener('ended', function () { stopFull(null); });
        });
    }

    /* ---------- Parallax background (videos section) ---------- */
    doc.querySelectorAll('[data-lh-parallax]').forEach(function (img) {
        if (reduce) return;
        var sec = img.closest('section'), ticking = false;
        function paint() {
            var r = sec.getBoundingClientRect(), vh = window.innerHeight;
            if (r.bottom < -200 || r.top > vh + 200) { ticking = false; return; }
            var p = (r.top + r.height / 2 - vh / 2) / (vh + r.height);
            img.style.transform = 'translate3d(0,' + (p * -22).toFixed(2) + '%,0) scale(1.18)';
            ticking = false;
        }
        window.addEventListener('scroll', function () { if (!ticking) { ticking = true; requestAnimationFrame(paint); } }, { passive: true });
        window.addEventListener('resize', paint);
        paint();
    });

    /* ---------- YouTube: load the player only on click ---------- */
    doc.addEventListener('click', function (e) {
        var b = e.target.closest('[data-lh-yt]');
        if (!b) return;
        var id = b.getAttribute('data-lh-yt');
        var f = doc.createElement('iframe');
        f.src = 'https://www.youtube-nocookie.com/embed/' + encodeURIComponent(id) + '?autoplay=1&rel=0&modestbranding=1';
        f.title = b.getAttribute('aria-label') || 'Libraro video';
        f.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
        f.allowFullscreen = true;
        b.innerHTML = '';
        b.appendChild(f);
        b.removeAttribute('data-lh-yt');
        var fig = b.closest('.lh-short'); if (fig) fig.classList.add('is-playing');
        b.style.cursor = 'default';
    });

    /* ---------- Slider progress + autoplay (problem slider) ---------- */
    doc.querySelectorAll('[data-lh-progress]').forEach(function (bar) {
        var track = doc.querySelector('[data-lb-track="' + bar.getAttribute('data-lh-progress') + '"]');
        var fill = bar.querySelector('i');
        if (!track || !fill) return;
        function paint() {
            var max = track.scrollWidth - track.clientWidth;
            var vis = track.clientWidth / track.scrollWidth;
            fill.style.width = Math.max(10, vis * 100) + '%';
            fill.style.transform = 'translateX(' + (max > 0 ? (track.scrollLeft / max) * ((1 / Math.max(vis, .1)) - 1) * 100 : 0) + '%)';
        }
        track.addEventListener('scroll', function () { requestAnimationFrame(paint); }, { passive: true });
        window.addEventListener('resize', paint);
        paint();
    });
    doc.querySelectorAll('[data-lh-autoplay]').forEach(function (track) {
        if (reduce) return;
        var paused = false;
        ['mouseenter', 'focusin', 'touchstart', 'pointerdown'].forEach(function (ev) { track.addEventListener(ev, function () { paused = true; }, { passive: true }); });
        track.addEventListener('mouseleave', function () { paused = false; });
        setInterval(function () {
            if (paused || doc.hidden) return;
            var r = track.getBoundingClientRect();
            if (r.bottom < 0 || r.top > window.innerHeight) return;
            var c = track.firstElementChild; if (!c) return;
            var step = c.offsetWidth + 16;
            if (track.scrollLeft + track.clientWidth >= track.scrollWidth - 8) track.scrollTo({ left: 0, behavior: 'smooth' });
            else track.scrollBy({ left: step, behavior: 'smooth' });
        }, 4200);
    });

    /* ---------- Mobile sticky CTA: after the hero, hidden near the end ---------- */
    var sticky = doc.querySelector('[data-lh-sticky]');
    var hero = doc.querySelector('.lh-hero');
    var final = doc.querySelector('.lh-final');
    if (sticky && hero) {
        sticky.hidden = false;
        var tick = false;
        window.addEventListener('scroll', function () {
            if (tick) return; tick = true;
            requestAnimationFrame(function () {
                var past = hero.getBoundingClientRect().bottom < 0;
                var atEnd = final && final.getBoundingClientRect().top < window.innerHeight;
                sticky.classList.toggle('is-on', past && !atEnd);
                tick = false;
            });
        }, { passive: true });
    }

    /* Language menu: now in lb-site.js (every website page). */
})();

/* Hero: rotate real portal screens */
(function () {
    var doc = document, root = doc.querySelector('[data-lh-hero-rotator]');
    if (!root) return;
    var imgs = root.querySelectorAll('.lh-rot-img'), pills = doc.querySelectorAll('[data-lh-rot]');
    var stage = root.closest('.lh-hero-stage'), ms = 4200, i = 0, timer = null;
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    doc.documentElement.style.setProperty('--lh-rot-ms', ms + 'ms');
    function show(n) {
        i = (n + imgs.length) % imgs.length;
        imgs.forEach(function (im, k) { im.classList.toggle('is-on', k === i); });
        pills.forEach(function (p, k) {
            p.setAttribute('aria-selected', k === i ? 'true' : 'false');
            var bar = p.querySelector('.lh-rot-bar'); if (bar) { bar.style.animation = 'none'; void bar.offsetWidth; bar.style.animation = ''; }
        });
    }
    function start() { stop(); if (!reduce) timer = setInterval(function () { if (!doc.hidden) show(i + 1); }, ms); }
    function stop() { if (timer) clearInterval(timer); timer = null; }
    pills.forEach(function (p, k) { p.addEventListener('click', function () { show(k); start(); }); });
    if (stage) {
        stage.addEventListener('mouseenter', function () { stop(); stage.parentElement.classList.add('lh-rot-paused'); });
        stage.addEventListener('mouseleave', function () { start(); stage.parentElement.classList.remove('lh-rot-paused'); });
    }
    if (reduce) pills.forEach(function (p) { var b = p.querySelector('.lh-rot-bar'); if (b) b.style.display = 'none'; });
    start();
})();

/* 3D tilt (problem cards), cursor spotlight, animated shine borders */
(function () {
    var doc = document;
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var fine = window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches;

    if (fine && !reduce) {
        doc.querySelectorAll('[data-lh-tilt]').forEach(function (card) {
            card.addEventListener('pointermove', function (e) {
                var r = card.getBoundingClientRect();
                var px = (e.clientX - r.left) / r.width, py = (e.clientY - r.top) / r.height;
                card.classList.add('is-tilting');
                card.style.setProperty('--ry', ((px - .5) * 14).toFixed(2) + 'deg');
                card.style.setProperty('--rx', ((.5 - py) * 12).toFixed(2) + 'deg');
                card.style.setProperty('--gx', (px * 100).toFixed(1) + '%');
                card.style.setProperty('--gy', (py * 100).toFixed(1) + '%');
            });
            card.addEventListener('pointerleave', function () {
                card.classList.remove('is-tilting');
                card.style.setProperty('--rx', '0deg'); card.style.setProperty('--ry', '0deg');
            });
        });
    }

    var spots = doc.querySelectorAll('.lh-solve-row, .lh-why-card, .lh-ben, .lh-review, .lh-mobile-tiles li');
    spots.forEach(function (el) {
        el.classList.add('lh-spot');
        if (!fine) return;
        el.addEventListener('pointermove', function (e) {
            var r = el.getBoundingClientRect();
            el.style.setProperty('--mx', (e.clientX - r.left) + 'px');
            el.style.setProperty('--my', (e.clientY - r.top) + 'px');
        });
    });

    doc.querySelectorAll('.lh-pricing .lb-plan--featured, .lh-refund, .lh-final-card').forEach(function (el) {
        if (getComputedStyle(el).position === 'static') el.style.position = 'relative';
        var b = doc.createElement('span'); b.className = 'lh-shine-b'; b.setAttribute('aria-hidden', 'true');
        el.appendChild(b);
    });
})();

/* Phones: plan row opens on the recommended plan */
(function () {
    if (window.innerWidth > 720) return;
    var row = document.querySelector('.lh-pricing .lb-plans'), f = row && row.querySelector('.lb-plan--featured');
    if (row && f) row.scrollLeft = f.offsetLeft - (row.clientWidth - f.offsetWidth) / 2;
})();

/* Big background words + thank-you note: animated in CSS (scroll-driven), see lb-home.css v8 */
