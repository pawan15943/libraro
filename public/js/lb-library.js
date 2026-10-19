/* Libraro — library details page: dialogs, photo lightbox, gallery counter, nearby libraries */
(function () {
    'use strict';
    var doc = document, C = window.LB_DIR || {};

    /* ---------- Dialogs (enquiry, review) ---------- */
    function openDlg(d) { if (!d) return; if (d.showModal) d.showModal(); else d.setAttribute('open', ''); var f = d.querySelector('input:not([type=hidden]), textarea'); if (f && window.innerWidth > 760) setTimeout(function () { f.focus(); }, 50); }
    function closeDlg(d) { if (d.close) d.close(); else d.removeAttribute('open'); }
    doc.addEventListener('click', function (e) {
        var t = e.target.closest('[data-dialog]');
        if (t) { e.preventDefault(); openDlg(doc.getElementById(t.getAttribute('data-dialog'))); return; }
        var d = e.target.closest('dialog.lb-dialog');
        if (d && (e.target === d || e.target.closest('.lb-dialog-close'))) closeDlg(d);
    });

    /* ---------- Lightbox ---------- */
    var lb = doc.getElementById('lb-lightbox'), track = doc.getElementById('lb-lightbox-track'), count = doc.getElementById('lb-lightbox-count');
    if (lb && track) {
        var n = track.children.length;
        var cur = function () { return Math.round(track.scrollLeft / Math.max(1, track.clientWidth)); };
        var paint = function () { if (count) count.textContent = (cur() + 1) + ' / ' + n; };
        var go = function (i) { i = Math.max(0, Math.min(n - 1, i)); track.scrollTo({ left: i * track.clientWidth, behavior: 'smooth' }); };
        doc.querySelectorAll('[data-open]').forEach(function (b) {
            b.addEventListener('click', function () {
                var i = +b.getAttribute('data-open');
                if (lb.showModal) lb.showModal(); else lb.setAttribute('open', '');
                track.scrollLeft = i * track.clientWidth; paint();
            });
        });
        track.addEventListener('scroll', function () { window.requestAnimationFrame(paint); }, { passive: true });
        lb.querySelectorAll('.lb-lightbox-nav').forEach(function (b) { b.addEventListener('click', function () { go(cur() + (+b.getAttribute('data-dir'))); }); });
        lb.querySelector('.lb-lightbox-close').addEventListener('click', function () { lb.close ? lb.close() : lb.removeAttribute('open'); });
        lb.addEventListener('keydown', function (e) { if (e.key === 'ArrowRight') go(cur() + 1); if (e.key === 'ArrowLeft') go(cur() - 1); });
    }

    /* ---------- Mobile gallery counter (swipe) ---------- */
    var gal = doc.getElementById('lb-gallery'), gc = doc.getElementById('lb-gallery-count');
    if (gal && gc) {
        var total = gal.querySelectorAll('button[data-open]:not(.lb-gallery-all)').length;
        gal.addEventListener('scroll', function () { gc.textContent = (Math.round(gal.scrollLeft / Math.max(1, gal.clientWidth)) + 1) + ' / ' + total; }, { passive: true });
    }

    /* ---------- Nearby libraries (same endpoint as the directory) ---------- */
    var near = C.nearby, sec = doc.getElementById('lb-nearby'), grid = doc.getElementById('lb-nearby-grid');
    if (near && sec && grid && C.listUrl) {
        var load = function () {
            fetch(C.listUrl + '?query=&category=all&city=' + encodeURIComponent(near.city || ''), { headers: { Accept: 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (list) {
                    if (!Array.isArray(list) || !window.LBCard) return;
                    list = list.filter(function (l) { return String(l.id) !== String(near.self); }).slice(0, 4);
                    if (!list.length) return;
                    grid.innerHTML = list.map(window.LBCard).join('');
                    sec.hidden = false;
                }).catch(function () {});
        };
        if ('IntersectionObserver' in window) {
            var io = new IntersectionObserver(function (en) { if (en[0].isIntersecting) { load(); io.disconnect(); } }, { rootMargin: '400px' });
            io.observe(doc.getElementById('reviews') || sec);
        } else load();
    }
})();
