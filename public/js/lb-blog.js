/* Libraro new design — blog article: TOC, reading progress, save, share, copy */
(function () {
    'use strict';
    var doc = document;
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
        // Make tables scroll on phones instead of breaking layout
        body.querySelectorAll('img').forEach(function (img) { if (!img.getAttribute('loading')) img.setAttribute('loading', 'lazy'); });
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
            if (i > -1) { s.splice(i, 1); toast('Removed from saved'); } else { s.push(here); toast('Saved for later on this device'); }
            try { localStorage.setItem(key, JSON.stringify(s.slice(-50))); } catch (e) {}
            paintSave();
        });
    });
    paintSave();

    /* Share + copy link */
    function copy() {
        var url = location.href.split('#')[0];
        if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(url).then(function () { toast('Link copied'); }, function () { toast(url); });
        else { var t = doc.createElement('textarea'); t.value = url; doc.body.appendChild(t); t.select(); try { doc.execCommand('copy'); toast('Link copied'); } catch (e) { toast(url); } t.remove(); }
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
            b.classList.add('is-on'); b.querySelector('span').textContent = 'Thanks!'; b.disabled = true;
        });
    });
})();
