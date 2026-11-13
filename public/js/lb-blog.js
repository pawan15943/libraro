/* Libraro new design — blog article: TOC, FAQ accordion, inline CTA, reading progress, save, share, copy */
(function () {
    'use strict';
    var doc = document;
    var T = function (s, r) { return window.LB_T ? window.LB_T(s, r) : s; };
    var toast = function (m) { if (window.LB && window.LB.toast) window.LB.toast(m); };

    /* Table of contents from h2/h3 in the article body (skips empty headings) */
    var body = doc.getElementById('postArticleBody'), toc = doc.getElementById('tocContainer'), list = doc.getElementById('tocList');
    if (body && toc && list) {
        var used = {};
        var slug = function (t) {
            var s = t.toLowerCase().replace(/[^a-z0-9ऀ-ॿ]+/g, '-').replace(/^-+|-+$/g, '') || 'section';
            var base = s, n = 2; while (used[s] || doc.getElementById(s)) s = base + '-' + n++;
            used[s] = 1; return s;
        };
        var hs = Array.prototype.filter.call(body.querySelectorAll('h2, h3'), function (h) { return h.textContent.replace(/ /g, ' ').trim().length > 0; });
        if (hs.length >= 2) {
            hs.forEach(function (h) {
                if (!h.id) h.id = slug(h.textContent.trim());
                var li = doc.createElement('li'); if (h.tagName === 'H3') li.className = 'is-sub';
                var a = doc.createElement('a'); a.href = '#' + h.id; a.textContent = h.textContent.replace(/ /g, ' ').trim();
                li.appendChild(a); list.appendChild(li);
            });
            toc.hidden = false;
        }
        body.querySelectorAll('img').forEach(function (img) { if (!img.getAttribute('loading')) img.setAttribute('loading', 'lazy'); });

        /* Desktop: TOC lives at the top of the sticky sidebar. Phones: collapsible, closed when long. */
        var aside = doc.querySelector('.lb-post-aside'), wide = window.matchMedia('(min-width: 1040px)');
        var placeToc = function () {
            if (!aside || toc.hidden) return;
            if (wide.matches && toc.parentNode !== aside) aside.insertBefore(toc, aside.firstChild);
            else if (!wide.matches && toc.parentNode === aside) body.parentNode.insertBefore(toc, body);
        };
        placeToc();
        if (wide.addEventListener) wide.addEventListener('change', placeToc);
        var tocBtn = doc.getElementById('lb-toc-title');
        if (tocBtn) {
            if (window.matchMedia('(max-width: 760px)').matches && hs.length > 5) tocBtn.setAttribute('aria-expanded', 'false');
            tocBtn.addEventListener('click', function () { tocBtn.setAttribute('aria-expanded', tocBtn.getAttribute('aria-expanded') === 'true' ? 'false' : 'true'); });
        }

        /* Highlight the section being read */
        if ('IntersectionObserver' in window && hs.length >= 2) {
            var links = {};
            list.querySelectorAll('a').forEach(function (a) { links[a.getAttribute('href').slice(1)] = a.parentNode; });
            var io = new IntersectionObserver(function (es) {
                es.forEach(function (e) {
                    if (!e.isIntersecting) return;
                    list.querySelectorAll('li.is-on').forEach(function (li) { li.classList.remove('is-on'); });
                    if (links[e.target.id]) links[e.target.id].classList.add('is-on');
                });
            }, { rootMargin: '-80px 0px -70% 0px' });
            hs.forEach(function (h) { io.observe(h); });
        }

        /* FAQ section (h2 "FAQ"/"Frequently asked"/"सवाल", then h3 questions) → accordion */
        var faqH2 = Array.prototype.find.call(body.querySelectorAll('h2'), function (h) { return /faq|frequently asked|सवाल|प्रश्न/i.test(h.textContent); });
        if (faqH2) {
            var node = faqH2.nextElementSibling, item = null, first = true;
            while (node && node.tagName !== 'H2') {
                var next = node.nextElementSibling;
                if (node.tagName === 'H3') {
                    item = doc.createElement('details'); item.className = 'lb-faq-item'; if (first) { item.open = true; first = false; }
                    var sum = doc.createElement('summary'); sum.textContent = node.textContent.trim(); sum.id = node.id;
                    var ans = doc.createElement('div'); item.appendChild(sum); item.appendChild(ans);
                    node.parentNode.replaceChild(item, node);
                } else if (item) {
                    item.lastChild.appendChild(node);
                }
                node = next;
            }
        }

        /* Mid-article call to action: before the 3rd main section (never inside the FAQ) */
        var tpl = doc.getElementById('lb-inline-cta'), h2s = body.querySelectorAll(':scope > h2');
        if (tpl && h2s.length >= 4 && h2s[2] !== faqH2) body.insertBefore(tpl.content.cloneNode(true), h2s[2]);
    }

    /* Reading progress + floating action bar */
    var bar = doc.getElementById('lb-progress-bar'), pct = doc.getElementById('lb-progress-pct'), ab = doc.querySelector('.lb-actionbar');
    var ticking = false;
    function onScroll() {
        var h = doc.documentElement, max = h.scrollHeight - h.clientHeight;
        var p = max > 0 ? Math.min(100, Math.round(h.scrollTop / max * 100)) : 0;
        if (bar) bar.style.width = p + '%';
        if (pct) pct.textContent = p + '%';
        if (ab) ab.classList.toggle('is-on', h.scrollTop > 400);
        ticking = false;
    }
    window.addEventListener('scroll', function () { if (!ticking) { ticking = true; requestAnimationFrame(onScroll); } }, { passive: true });
    onScroll();
    doc.querySelectorAll('[data-lb-top]').forEach(function (b) { b.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); }); });

    /* Save for later (this device only) */
    var key = 'lb-saved-articles', here = location.pathname;
    var getSaved = function () { try { return JSON.parse(localStorage.getItem(key) || '[]'); } catch (e) { return []; } };
    function paintSave() {
        var on = getSaved().indexOf(here) > -1;
        doc.querySelectorAll('[data-lb-save]').forEach(function (b) { b.setAttribute('aria-pressed', on ? 'true' : 'false'); b.setAttribute('aria-label', on ? 'Remove from saved' : 'Save article'); });
    }
    doc.querySelectorAll('[data-lb-save]').forEach(function (b) {
        b.addEventListener('click', function () {
            var s = getSaved(), i = s.indexOf(here);
            if (i > -1) { s.splice(i, 1); toast(T('Removed from saved')); } else { s.push(here); toast(T('Saved for later on this device')); }
            try { localStorage.setItem(key, JSON.stringify(s.slice(-50))); } catch (e) {}
            paintSave();
        });
    });
    paintSave();

    /* Share + copy link */
    function copy() {
        var url = location.href.split('#')[0];
        if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(url).then(function () { toast(T('Link copied')); }, function () { toast(url); });
        else { var t = doc.createElement('textarea'); t.value = url; doc.body.appendChild(t); t.select(); try { doc.execCommand('copy'); toast(T('Link copied')); } catch (e) { toast(url); } t.remove(); }
    }
    doc.querySelectorAll('[data-lb-share]').forEach(function (b) {
        b.addEventListener('click', function () {
            if (navigator.share) navigator.share({ title: doc.title, url: location.href.split('#')[0] }).catch(function () {});
            else copy();
        });
    });
    doc.querySelectorAll('[data-lb-copy]').forEach(function (b) { b.addEventListener('click', copy); });

    /* Was this helpful? (simple, this device only) */
    doc.querySelectorAll('[data-lb-helpful]').forEach(function (b) {
        b.addEventListener('click', function () {
            b.classList.add('is-on'); b.querySelector('span').textContent = T('Thanks!'); b.disabled = true;
        });
    });
})();
