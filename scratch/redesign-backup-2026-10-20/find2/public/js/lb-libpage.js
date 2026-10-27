/* ==========================================================================
   Libraro — library details page
   Photos (mosaic, all-photos grid, lightbox), section tabs, share, live seat map
   and the seat booking modal. Booking reuses the existing QR booking backend:
     price   POST LB_SEATS.priceUrl (route get.plan.price)
     booking POST LB_SEATS.storeUrl (route booking.store) → signed payment QR / thank-you page
   ========================================================================== */
(function () {
    'use strict';
    var doc = document, C = window.LB_DIR || {}, S = window.LB_SEATS || {};
    var $ = function (id) { return doc.getElementById(id); };
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
    function icon(name, cls) { return '<svg class="lb-i ' + (cls || '') + '" aria-hidden="true"><use href="' + C.icons + '#i-' + name + '"></use></svg>'; }
    function inr(n) { return '₹' + Number(n).toLocaleString('en-IN', { maximumFractionDigits: 0 }); }
    function toast(m) { if (window.LB && window.LB.toast) window.LB.toast(m); }
    function openDlg(d) { if (!d) return; if (d.showModal) d.showModal(); else d.setAttribute('open', ''); }
    function closeDlg(d) { if (!d) return; if (d.close) d.close(); else d.removeAttribute('open'); }
    function navOffset() { var n = doc.querySelector('.lb-nav'); var t = doc.querySelector('.dp-tabs'); return (n ? n.offsetHeight : 64) + (t ? t.offsetHeight : 0) + 10; }
    function scrollToEl(el) { if (!el) return; window.scrollTo({ top: el.getBoundingClientRect().top + window.scrollY - navOffset() + 4, behavior: reduce ? 'auto' : 'smooth' }); }

    /* ---------- Dialogs (enquiry, review) ---------- */
    doc.addEventListener('click', function (e) {
        var t = e.target.closest('[data-dialog]');
        if (t) {
            e.preventDefault();
            var d = $(t.getAttribute('data-dialog'));
            openDlg(d);
            var shift = t.getAttribute('data-shift'); var sel = $('enq_shift');
            if (shift && sel) sel.value = shift;
            var f = d && d.querySelector('input:not([type=hidden]), textarea');
            if (f && window.innerWidth > 760) setTimeout(function () { f.focus(); }, 60);
            return;
        }
        var dl = e.target.closest('dialog.lb-dialog');
        if (dl && (e.target === dl || e.target.closest('.lb-dialog-close'))) closeDlg(dl);
    });

    /* ---------- Photos ---------- */
    var gal = $('dp-gallery'), gcount = $('dp-gallery-count');
    var photos = $('dp-photos'), lbx = $('dp-lightbox'), track = $('dp-lb-track'), lcount = $('dp-lb-count'), thumbs = $('dp-lb-thumbs');
    var total = track ? track.children.length : 0;
    function cur() { return track ? Math.round(track.scrollLeft / Math.max(1, track.clientWidth)) : 0; }
    function paintLb() {
        var i = cur();
        if (lcount) lcount.textContent = (i + 1) + ' / ' + total;
        if (thumbs) thumbs.querySelectorAll('button').forEach(function (b, k) { b.classList.toggle('is-on', k === i); if (k === i && thumbs.scrollWidth > thumbs.clientWidth) thumbs.scrollTo({ left: b.offsetLeft - thumbs.clientWidth / 2 + b.offsetWidth / 2, behavior: 'smooth' }); });
    }
    function goLb(i, instant) { i = Math.max(0, Math.min(total - 1, i)); track.scrollTo({ left: i * track.clientWidth, behavior: instant || reduce ? 'auto' : 'smooth' }); if (instant) paintLb(); }
    function openLb(i) { openDlg(lbx); requestAnimationFrame(function () { goLb(i, true); }); }
    doc.addEventListener('click', function (e) {
        var p = e.target.closest('[data-photo]');
        if (p && lbx) { openLb(+p.getAttribute('data-photo')); return; }
        if (e.target.closest('[data-allphotos]') && photos) { openDlg(photos); photos.scrollTop = 0; return; }
        if (e.target.closest('[data-photos-close]')) { closeDlg(photos); return; }
        if (e.target.closest('[data-lb-close]')) { closeDlg(lbx); return; }
        var d = e.target.closest('[data-lb-dir]'); if (d) { goLb(cur() + (+d.getAttribute('data-lb-dir'))); return; }
        var g = e.target.closest('[data-lb-go]'); if (g) { goLb(+g.getAttribute('data-lb-go')); return; }
    });
    if (track) {
        var raf = 0;
        track.addEventListener('scroll', function () { cancelAnimationFrame(raf); raf = requestAnimationFrame(paintLb); }, { passive: true });
        lbx.addEventListener('keydown', function (e) { if (e.key === 'ArrowRight') goLb(cur() + 1); if (e.key === 'ArrowLeft') goLb(cur() - 1); });
        lbx.addEventListener('click', function (e) { if (e.target === lbx || e.target.tagName === 'FIGURE') closeDlg(lbx); });
    }
    if (photos) photos.addEventListener('click', function (e) { if (e.target === photos) closeDlg(photos); });
    if (gal && gcount) {
        gal.addEventListener('scroll', function () {
            var n = gal.querySelectorAll('.dp-ph').length;
            gcount.textContent = Math.min(n, Math.round(gal.scrollLeft / Math.max(1, gal.clientWidth)) + 1) + ' / ' + total;
        }, { passive: true });
    }

    /* ---------- Share ---------- */
    doc.querySelectorAll('[data-share]').forEach(function (b) {
        b.addEventListener('click', function () {
            var data = { title: S.name || doc.title, text: 'Check out ' + (S.name || 'this library') + ' on Libraro', url: window.location.href.split('#')[0] };
            if (navigator.share) { navigator.share(data).catch(function () {}); return; }
            (navigator.clipboard ? navigator.clipboard.writeText(data.url) : Promise.reject()).then(function () { toast('Link copied'); }, function () { toast(data.url); });
        });
    });

    /* ---------- About: read more ---------- */
    doc.querySelectorAll('[data-more]').forEach(function (b) {
        var t = $(b.getAttribute('data-more')); if (!t) return;
        if (t.scrollHeight > t.clientHeight + 4) b.hidden = false;
        b.addEventListener('click', function () { var open = t.classList.toggle('is-open'); b.textContent = open ? 'Show less' : 'Read more'; });
    });

    /* ---------- Section tabs (scroll-spy) ---------- */
    var tabs = doc.querySelector('.dp-tabs-track');
    if (tabs) {
        var links = Array.prototype.slice.call(tabs.querySelectorAll('a'));
        links.forEach(function (a) { a.addEventListener('click', function (e) { var el = doc.querySelector(a.getAttribute('href')); if (el) { e.preventDefault(); scrollToEl(el); } }); });
        var secs = links.map(function (a) { return doc.querySelector(a.getAttribute('href')); });
        var spy = function () {
            var y = navOffset() + 30, active = 0;
            secs.forEach(function (s, i) { if (s && s.getBoundingClientRect().top <= y) active = i; });
            links.forEach(function (a, i) { a.classList.toggle('is-active', i === active); });
            var on = links[active];
            if (on && tabs.scrollWidth > tabs.clientWidth) { var want = on.offsetLeft - 16; if (Math.abs(tabs.scrollLeft - want) > 40) tabs.scrollTo({ left: want, behavior: 'smooth' }); }
        };
        var t2 = 0; window.addEventListener('scroll', function () { cancelAnimationFrame(t2); t2 = requestAnimationFrame(spy); }, { passive: true });
        spy();
    }

    /* ---------- Nearby libraries ---------- */
    var near = C.nearby, nsec = $('lb-nearby'), ngrid = $('lb-nearby-grid');
    if (near && nsec && ngrid && C.listUrl) {
        var loadNear = function () {
            fetch(C.listUrl + '?query=&category=all&city=' + encodeURIComponent(near.city || ''), { headers: { Accept: 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (list) {
                    if (!Array.isArray(list) || !window.LBFindCard) return;
                    list = list.filter(function (l) { return String(l.id) !== String(near.self); }).slice(0, 8);
                    if (!list.length) return;
                    ngrid.innerHTML = list.map(window.LBFindCard).join('');
                    nsec.hidden = false;
                }).catch(function () {});
        };
        if ('IntersectionObserver' in window) {
            var nio = new IntersectionObserver(function (en) { if (en[0].isIntersecting) { loadNear(); nio.disconnect(); } }, { rootMargin: '500px' });
            nio.observe($('reviews') || nsec);
        } else loadNear();
        doc.querySelectorAll('[data-dp-prev],[data-dp-next]').forEach(function (b) {
            b.addEventListener('click', function () { var c = ngrid.firstElementChild, step = c ? c.offsetWidth + 18 : 320; ngrid.scrollBy({ left: b.hasAttribute('data-dp-prev') ? -step : step, behavior: 'smooth' }); });
        });
    }

    /* ======================================================================
       Seat map
       ====================================================================== */
    var grid = $('sm-grid');
    var TYPES = S.types || [], SEATS = S.seats || [];
    var typeById = {}; TYPES.forEach(function (t) { typeById[t.id] = t; });
    var seatByN = {}; SEATS.forEach(function (s) { seatByN[s.n] = s; });
    var multiFloor = SEATS.some(function (s) { return s.g; }) && new Set(SEATS.map(function (s) { return s.g; })).size > 1;
    var sm = { shift: '', floor: '', onlyFree: false, find: '', all: false };

    function seatLabel(s) { return 'Seat ' + s.l + (multiFloor && s.g ? ' · ' + s.g : ''); }
    function timeOf(t) { return t.from && t.to ? t.from + ' – ' + t.to : (t.h ? t.h + ' hrs' : ''); }
    function seatState(s) { var n = s.a.length; return !n ? 'is-full' : (n >= TYPES.length ? 'is-free' : 'is-part'); }

    function seatHtml(s) {
        var st = seatState(s), free = s.a.length;
        var slides = TYPES.map(function (t, i) {
            var ok = s.a.indexOf(t.id) > -1;
            var inner = '<span class="sm-ic">' + icon(t.icon) + '</span><b>' + esc(t.name) + '</b><small>' + esc(timeOf(t)) + '</small>';
            if (ok && S.canBook) return '<button type="button" class="sm-slide is-free" data-book="' + s.n + '" data-type="' + t.id + '" data-i="' + i + '" aria-label="Book ' + esc(seatLabel(s)) + ', ' + esc(t.name) + '">' + inner + '<span class="sm-cta">Book' + icon('arrow-right') + '</span></button>';
            if (ok) return '<button type="button" class="sm-slide is-free" data-dialog="lb-enquiry-dialog" data-shift="' + t.id + '" data-i="' + i + '">' + inner + '<span class="sm-cta">Ask</span></button>';
            return '<div class="sm-slide is-booked" data-i="' + i + '" aria-disabled="true">' + inner + '<span class="sm-cta">Booked</span></div>';
        }).join('');
        var dots = TYPES.length > 1 ? '<div class="sm-dots" aria-hidden="true">' + TYPES.map(function (t, i) { return '<i class="' + (s.a.indexOf(t.id) > -1 ? 'is-ok' : '') + (i === 0 ? ' is-on' : '') + '" data-dot="' + i + '"></i>'; }).join('') + '</div>' : '';
        return '<div class="sm-seat ' + st + '" data-n="' + s.n + '">'
            + '<div class="sm-top"><span class="sm-no"><small>' + (multiFloor && s.g ? esc(s.g) : 'Seat') + '</small>' + esc(s.l) + '</span>'
            + '<span class="sm-st">' + (free ? free + '/' + TYPES.length + ' free' : 'Full') + '</span></div>'
            + '<div class="sm-slides" tabindex="-1">' + slides + '</div>' + dots + '</div>';
    }

    function visibleSeats() {
        var sh = sm.shift ? +sm.shift : 0, q = sm.find;
        return SEATS.filter(function (s) {
            if (sm.floor && s.g !== sm.floor) return false;
            if (q && String(s.l) !== q && String(s.n) !== q) return false;
            if (sm.onlyFree) { if (sh ? s.a.indexOf(sh) < 0 : !s.a.length) return false; }
            return true;
        });
    }
    function slideTo(card, idx, instant) {
        var box = card.querySelector('.sm-slides'); if (!box) return;
        var sl = box.children[idx]; if (!sl) return;
        box.scrollTo({ left: sl.offsetLeft - box.offsetLeft, behavior: instant ? 'auto' : 'smooth' });
    }
    function paintDots(card) {
        var box = card.querySelector('.sm-slides'), dots = card.querySelectorAll('.sm-dots i');
        if (!box || !dots.length) return;
        var i = Math.round(box.scrollLeft / Math.max(1, box.clientWidth));
        dots.forEach(function (d, k) { d.classList.toggle('is-on', k === i); });
    }
    function renderSeats() {
        if (!grid) return;
        var list = visibleSeats(), sh = sm.shift ? +sm.shift : 0;
        var show = sm.all || sm.find ? list : list.slice(0, S.pageSize || 24);
        if (!list.length) {
            grid.innerHTML = '<div class="sm-empty">' + icon('armchair') + '<b>No seats match</b><span>' + (sm.find ? 'Seat ' + esc(sm.find) + ' was not found.' : 'Try another shift or floor.') + '</span></div>';
        } else {
            grid.innerHTML = show.map(seatHtml).join('');
        }
        if (!sh) {
            // Show each seat's first free shift first
            grid.querySelectorAll('.sm-seat').forEach(function (c) {
                var s = seatByN[c.getAttribute('data-n')];
                var first = TYPES.findIndex(function (t) { return s.a.indexOf(t.id) > -1; });
                if (first > 0) { slideTo(c, first, true); paintDots(c); }
            });
        }
        if (sh) {
            var idx = TYPES.findIndex(function (t) { return t.id === sh; });
            grid.querySelectorAll('.sm-seat').forEach(function (c) {
                var s = seatByN[c.getAttribute('data-n')];
                c.classList.toggle('is-dim', s.a.indexOf(sh) < 0);
                slideTo(c, idx, true); paintDots(c);
            });
        }
        var more = $('sm-more'), cnt = $('sm-count');
        more.hidden = sm.all || !!sm.find || list.length <= show.length;
        more.textContent = 'Show all ' + list.length + ' seats';
        var freeFor = sh ? SEATS.filter(function (s) { return s.a.indexOf(sh) > -1 && (!sm.floor || s.g === sm.floor); }).length : SEATS.filter(function (s) { return s.a.length && (!sm.floor || s.g === sm.floor); }).length;
        cnt.innerHTML = '<b>' + freeFor + '</b> ' + (sh ? 'seats free for ' + esc(typeById[sh].name) : 'seats with at least one free shift') + (sm.floor ? ' on ' + esc(sm.floor) : '') + ' · showing ' + show.length + ' of ' + list.length;
    }
    if (grid) {
        // free counts on shift chips
        TYPES.forEach(function (t) { var el = doc.querySelector('[data-sm-free="' + t.id + '"]'); if (el) el.textContent = SEATS.filter(function (s) { return s.a.indexOf(t.id) > -1; }).length + ' free'; });
        doc.querySelectorAll('[data-sm-shift]').forEach(function (b) {
            b.addEventListener('click', function () {
                sm.shift = b.getAttribute('data-sm-shift');
                doc.querySelectorAll('[data-sm-shift]').forEach(function (x) { x.setAttribute('aria-pressed', x === b ? 'true' : 'false'); });
                renderSeats();
            });
        });
        doc.querySelectorAll('[data-sm-floor]').forEach(function (b) {
            b.addEventListener('click', function () {
                sm.floor = b.getAttribute('data-sm-floor');
                doc.querySelectorAll('[data-sm-floor]').forEach(function (x) { x.setAttribute('aria-pressed', x === b ? 'true' : 'false'); });
                renderSeats();
            });
        });
        var onlyFree = $('sm-onlyfree'); if (onlyFree) onlyFree.addEventListener('change', function () { sm.onlyFree = onlyFree.checked; renderSeats(); });
        var find = $('sm-find'), ft = 0; if (find) find.addEventListener('input', function () { clearTimeout(ft); ft = setTimeout(function () { sm.find = find.value.trim(); renderSeats(); }, 200); });
        $('sm-more').addEventListener('click', function () { sm.all = true; renderSeats(); });
        grid.addEventListener('scroll', function (e) { var c = e.target.closest && e.target.closest('.sm-seat'); if (c) paintDots(c); }, true);
        renderSeats();
    }
    // Jump to the seat map (optionally for one shift)
    doc.addEventListener('click', function (e) {
        var j = e.target.closest('[data-sm-jump]');
        if (!j) return;
        var sec = $('seats'); if (!sec) return;
        e.preventDefault();
        var id = j.getAttribute('data-sm-jump');
        if (id) { var b = doc.querySelector('[data-sm-shift="' + id + '"]'); if (b) b.click(); }
        scrollToEl(sec);
    });
    if (location.hash === '#seats' && $('seats')) setTimeout(function () { scrollToEl($('seats')); }, 300);

    /* ======================================================================
       Booking modal
       ====================================================================== */
    var dlg = $('bk');
    if (!dlg || !S.canBook) return;
    var form = $('bk-form'), nextBtn = $('bk-next'), backBtn = $('bk-back'), foot = $('bk-foot');
    var PRICES = S.prices || {}, PLANS = S.plans || [];
    var bk = { seat: null, type: 0, plan: 0, start: '', price: null, step: 1, busy: false, result: null, priceReq: 0 };
    function iso(d) { var m = d.getMonth() + 1, day = d.getDate(); return d.getFullYear() + '-' + (m < 10 ? '0' : '') + m + '-' + (day < 10 ? '0' : '') + day; }
    function niceDate(s) { try { var d = new Date(s + 'T00:00:00'); return d.toLocaleDateString('en-IN', { weekday: 'short', day: 'numeric', month: 'short' }); } catch (e) { return s; } }
    function setText(key, val) { dlg.querySelectorAll('[data-bk="' + key + '"]').forEach(function (el) { el.textContent = val; }); }
    function planName(id) { var p = PLANS.find(function (x) { return x.id === +id; }); return p ? p.name : ''; }
    function localPrice() { var row = PRICES[bk.plan] || PRICES[String(bk.plan)]; return row ? (row[bk.type] || row[String(bk.type)] || null) : null; }

    var today = new Date(), tomorrow = new Date(Date.now() + 864e5);
    dlg.querySelector('[data-bk-day="0"]').textContent = today.toLocaleDateString('en-IN', { day: 'numeric', month: 'short' });
    dlg.querySelector('[data-bk-day="1"]').textContent = tomorrow.toLocaleDateString('en-IN', { day: 'numeric', month: 'short' });
    var dateIn = $('bk-date'); dateIn.min = iso(today);

    function renderShifts() {
        var box = $('bk-shifts');
        box.innerHTML = TYPES.filter(function (t) { return bk.seat.a.indexOf(t.id) > -1; }).map(function (t) {
            return '<button type="button" role="radio" aria-checked="' + (t.id === bk.type) + '" data-t="' + t.id + '"><i>' + icon(t.icon) + '</i><span><b>' + esc(t.name) + '</b><small>' + esc(timeOf(t)) + (t.h ? ' · ' + t.h + ' hrs' : '') + '</small></span></button>';
        }).join('');
    }
    function renderPlans() {
        var box = $('bk-plans');
        var avail = PLANS.filter(function (p) { var r = PRICES[p.id] || PRICES[String(p.id)]; return r && (r[bk.type] || r[String(bk.type)]); });
        if (!avail.some(function (p) { return p.id === bk.plan; })) bk.plan = avail.length ? avail[0].id : 0;
        box.innerHTML = avail.length ? avail.map(function (p) {
            var pr = (PRICES[p.id] || PRICES[String(p.id)]); pr = pr[bk.type] || pr[String(bk.type)];
            return '<button type="button" role="radio" aria-checked="' + (p.id === bk.plan) + '" data-p="' + p.id + '"><b>' + esc(p.name) + '</b><span>' + inr(pr) + '</span></button>';
        }).join('') : '<p class="bk-hint">No plan is priced for this shift yet. Please ask the library.</p>';
    }
    function refreshPrice() {
        var lp = localPrice();
        bk.price = lp; paintSummary();
        if (!bk.plan || !bk.type || !bk.start) return;
        var my = ++bk.priceReq, fd = new FormData();
        fd.append('_token', S.csrf); fd.append('plan_id', bk.plan); fd.append('plan_type_id', bk.type); fd.append('branch_id', S.branchId); fd.append('plan_start_date', bk.start);
        setText('amount', '…');
        fetch(S.priceUrl, { method: 'POST', body: fd, headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (j) { if (my !== bk.priceReq) return; if (j && j.success && Number(j.price) > 0) bk.price = Number(j.price); paintSummary(); })
            .catch(function () { if (my === bk.priceReq) paintSummary(); });
    }
    function paintSummary() {
        setText('amount', bk.price ? inr(bk.price) : '—');
        var t = typeById[bk.type];
        setText('summary', [t ? t.name : '', planName(bk.plan), bk.start ? 'from ' + niceDate(bk.start) : ''].filter(Boolean).join(' · '));
    }
    function setStep(n) {
        bk.step = n;
        form.querySelectorAll('[data-step]').forEach(function (s) { s.hidden = +s.getAttribute('data-step') !== n; });
        dlg.querySelectorAll('[data-step-dot]').forEach(function (d) { var k = +d.getAttribute('data-step-dot'); d.classList.toggle('is-on', k === n); d.classList.toggle('is-done', k < n); });
        backBtn.hidden = n === 1;
        var label = nextBtn.querySelector('span');
        if (n < 3) label.textContent = 'Continue';
        else label.textContent = (form.querySelector('[name=payment_mode]:checked') || {}).value === 'online' ? 'Continue to pay' : 'Reserve my seat';
        var body = dlg.querySelector('.bk-body'); if (body) body.scrollTop = 0;
        var step = form.querySelector('[data-step="' + n + '"]');
        if (step && !reduce && step.animate) step.animate([{ opacity: 0, transform: 'translateX(14px)' }, { opacity: 1, transform: 'none' }], { duration: 320, easing: 'cubic-bezier(.22,.61,.36,1)' });
    }
    function showScreen(name) {
        form.hidden = !!name;
        dlg.querySelector('.bk-steps').hidden = name === 'done';
        dlg.querySelectorAll('[data-screen]').forEach(function (s) { s.hidden = s.getAttribute('data-screen') !== name; });
        foot.hidden = name === 'done';
        if (name === 'pay') { backBtn.hidden = true; nextBtn.querySelector('span').textContent = 'I have paid — send screenshot'; dlg.querySelectorAll('[data-step-dot]').forEach(function (d) { d.classList.add('is-done'); d.classList.remove('is-on'); }); }
        if (name === 'done') { var c = dlg.querySelector('.bk-check'); if (c) { c.classList.remove('is-anim'); void c.offsetWidth; c.classList.add('is-anim'); } }
        var sheet = dlg.querySelector('.bk-sheet'); if (sheet) sheet.scrollTop = 0;
    }
    function err(key, msg) { var el = form.querySelector('[data-err="' + key + '"]'); if (!el) return; el.textContent = msg || ''; el.hidden = !msg; var box = el.previousElementSibling; if (box && box.classList.contains('lb-input')) box.classList.toggle('is-invalid', !!msg); }
    function alertBox(id, msg) { var a = $(id); a.textContent = msg || ''; a.hidden = !msg; }

    function openBooking(seatN, typeId) {
        var s = seatByN[seatN]; if (!s) return;
        bk.seat = s; bk.type = +typeId; bk.start = iso(today); bk.result = null; bk.busy = false;
        setText('seat', seatLabel(s));
        dlg.querySelectorAll('.bk-dates [data-date]').forEach(function (b) { b.setAttribute('aria-pressed', b.getAttribute('data-date') === '0' ? 'true' : 'false'); });
        dateIn.value = ''; dateIn.parentElement.classList.remove('is-on');
        ['name', 'mobile', 'email', 'agree'].forEach(function (k) { err(k, ''); });
        alertBox('bk-alert', ''); alertBox('bk-pay-alert', '');
        $('bk-agree').checked = false;
        renderShifts(); renderPlans(); refreshPrice();
        showScreen(''); setStep(1); foot.hidden = false;
        openDlg(dlg);
    }
    function closeBooking() {
        if (bk.busy) return;
        if (!reduce && dlg.animate) {
            var sh = dlg.querySelector('.bk-sheet');
            var a = sh.animate([{ opacity: 1, transform: 'none' }, { opacity: 0, transform: window.innerWidth < 640 ? 'translateY(40px)' : 'translateY(12px) scale(.98)' }], { duration: 220, easing: 'ease-in' });
            a.onfinish = function () { closeDlg(dlg); };
        } else closeDlg(dlg);
    }

    doc.addEventListener('click', function (e) {
        var b = e.target.closest('[data-book]');
        if (b) { openBooking(+b.getAttribute('data-book'), +b.getAttribute('data-type')); }
    });
    dlg.addEventListener('click', function (e) {
        if (e.target === dlg || e.target.closest('[data-bk-close]')) { closeBooking(); return; }
        var t = e.target.closest('#bk-shifts [data-t]'); if (t) { bk.type = +t.getAttribute('data-t'); renderShifts(); renderPlans(); refreshPrice(); return; }
        var p = e.target.closest('#bk-plans [data-p]'); if (p) { bk.plan = +p.getAttribute('data-p'); renderPlans(); refreshPrice(); return; }
        var d = e.target.closest('.bk-dates [data-date]');
        if (d) {
            var add = +d.getAttribute('data-date'); bk.start = iso(add ? tomorrow : today);
            dlg.querySelectorAll('.bk-dates [data-date]').forEach(function (x) { x.setAttribute('aria-pressed', x === d ? 'true' : 'false'); });
            dateIn.value = ''; dateIn.parentElement.classList.remove('is-on'); refreshPrice();
        }
    });
    dlg.addEventListener('cancel', function (e) { e.preventDefault(); closeBooking(); });
    dateIn.addEventListener('change', function () {
        if (!dateIn.value) return;
        if (dateIn.value < iso(today)) { dateIn.value = iso(today); }
        bk.start = dateIn.value;
        dlg.querySelectorAll('.bk-dates [data-date]').forEach(function (x) { x.setAttribute('aria-pressed', 'false'); });
        dateIn.parentElement.classList.add('is-on'); refreshPrice();
    });
    form.addEventListener('change', function (e) { if (e.target.name === 'payment_mode') setStep(3); });
    // Keep only digits in the mobile field (paste friendly)
    var mob = $('bk-mobile');
    mob.addEventListener('input', function () { var d = mob.value.replace(/\D/g, ''); if (d.length > 10) d = d.slice(-10); if (mob.value !== d) mob.value = d; });

    function validStep(n) {
        if (n === 1) {
            if (!bk.type || !bk.plan) { toast('Please choose a shift and a plan.'); return false; }
            if (!bk.start) { toast('Please choose a start date.'); return false; }
            return true;
        }
        if (n === 2) {
            var ok = true, name = $('bk-name').value.trim(), m = mob.value.trim(), em = $('bk-email').value.trim();
            if (name.length < 2) { err('name', 'Please enter your full name.'); ok = false; } else err('name', '');
            if (!/^[6-9]\d{9}$/.test(m)) { err('mobile', 'Enter a valid 10-digit mobile number.'); ok = false; } else err('mobile', '');
            if (em && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(em)) { err('email', 'Enter a valid email, or leave it empty.'); ok = false; } else err('email', '');
            if (!ok) { var f = form.querySelector('.lb-input.is-invalid input'); if (f) f.focus(); }
            return ok;
        }
        if (n === 3) {
            if (!$('bk-agree').checked) { err('agree', 'Please accept the terms to continue.'); return false; }
            err('agree', ''); return true;
        }
        return true;
    }

    function busy(on, label) {
        bk.busy = on; nextBtn.disabled = on; nextBtn.classList.toggle('is-loading', on);
        if (label) nextBtn.querySelector('span').textContent = label;
    }

    function submitBooking() {
        var mode = (form.querySelector('[name=payment_mode]:checked') || {}).value || 'offline';
        var fd = new FormData();
        fd.append('_token', S.csrf);
        fd.append('general_seat', 'no');
        fd.append('seat_no', bk.seat.n);
        fd.append('name', $('bk-name').value.trim());
        fd.append('mobile', mob.value.trim());
        fd.append('email', $('bk-email').value.trim());
        fd.append('dob', ''); // the booking endpoint reads this key even when empty
        fd.append('plan_id', bk.plan);
        fd.append('plan_type_id', bk.type);
        fd.append('plan_price_id', bk.price || localPrice() || '');
        fd.append('plan_start_date', bk.start);
        fd.append('payment_mode', mode);
        busy(true, mode === 'online' ? 'Preparing payment…' : 'Reserving your seat…');
        alertBox('bk-alert', '');
        fetch(S.storeUrl, { method: 'POST', body: fd, credentials: 'same-origin', redirect: 'follow', headers: { Accept: 'text/html' } })
            .then(function (r) { return r.text().then(function (html) { return { url: r.url, ok: r.ok, html: html }; }); })
            .then(function (res) {
                busy(false);
                var u = res.url || '';
                var isPay = /\/booking\/\d+\/payment-qr/.test(u), isDone = /\/booking\/\d+\/thank-you/.test(u);
                if (!isPay && !isDone) { setStep(3); alertBox('bk-alert', readError(res.html)); return; }
                // The booking exists once we land on either signed page.
                bk.result = { mode: isPay ? 'online' : 'offline', url: u };
                if (isPay && res.ok) showPay(res.html);
                else if (isPay) showDone('online-link');
                else showDone(mode === 'online' ? 'fallback' : 'offline');
            })
            .catch(function () { busy(false); setStep(3); alertBox('bk-alert', 'No internet connection. Please check and try again.'); });
    }
    function readError(html) {
        try {
            var d = new DOMParser().parseFromString(html || '', 'text/html');
            var el = d.querySelector('.invalid-feedback, .alert-danger, .alert.alert-danger, .text-danger, .error');
            var t = el && el.textContent.trim();
            if (t && t.length < 200) return t;
        } catch (e) {}
        return 'This seat could not be booked for the chosen shift. It may have just been taken — please pick another seat or shift.';
    }
    function showPay(html) {
        var d = new DOMParser().parseFromString(html, 'text/html');
        var img = d.querySelector('a[href^="upi:"] img, img[src^="data:image"]');
        var link = d.querySelector('a[href^="upi:"]');
        var formEl = d.querySelector('form[action*="upload-screenshot"]');
        bk.result.upload = formEl ? formEl.getAttribute('action') : null;
        var qr = $('bk-qrbox');
        qr.innerHTML = img ? '<img src="' + img.getAttribute('src') + '" alt="UPI QR code to pay ' + esc(S.name) + '">' : '<p class="bk-hint">Scan the QR at the library or use the UPI app button.</p>';
        var up = $('bk-upi');
        if (link) { up.href = link.getAttribute('href'); up.hidden = false; } else up.hidden = true;
        setText('amount', bk.price ? inr(bk.price) : '—');
        $('bk-shot').value = ''; $('bk-shot-name').textContent = 'Choose screenshot';
        showScreen('pay');
    }
    $('bk-shot').addEventListener('change', function () { var f = this.files && this.files[0]; $('bk-shot-name').textContent = f ? f.name : 'Choose screenshot'; alertBox('bk-pay-alert', ''); });
    function sendShot() {
        var f = $('bk-shot').files && $('bk-shot').files[0];
        if (!f) { alertBox('bk-pay-alert', 'Please choose your payment screenshot.'); return; }
        if (!/^image\/(png|jpe?g|webp)$/.test(f.type)) { alertBox('bk-pay-alert', 'Please choose a PNG or JPG image.'); return; }
        if (f.size > 2 * 1024 * 1024) { alertBox('bk-pay-alert', 'The image is larger than 2 MB. Please choose a smaller screenshot.'); return; }
        if (!bk.result || !bk.result.upload) { showDone('online-noproof'); return; }
        var fd = new FormData(); fd.append('_token', S.csrf); fd.append('payment_screenshot', f);
        busy(true, 'Sending…');
        fetch(bk.result.upload, { method: 'POST', body: fd, credentials: 'same-origin', redirect: 'follow' })
            .then(function (r) { busy(false); if (r.ok && /thank-you/.test(r.url)) showDone('online'); else alertBox('bk-pay-alert', 'The screenshot could not be uploaded. Please try again, or show it at the library.'); })
            .catch(function () { busy(false); alertBox('bk-pay-alert', 'No internet connection. Please try again.'); });
    }
    function showDone(kind) {
        var t = typeById[bk.type];
        setText('seat', seatLabel(bk.seat));
        setText('shift', t ? t.name + (timeOf(t) ? ' (' + timeOf(t) + ')' : '') : '');
        setText('plan', planName(bk.plan));
        setText('start', niceDate(bk.start));
        setText('amount', bk.price ? inr(bk.price) : '—');
        setText('pay', kind === 'online' ? 'Paid online · proof sent' : (kind === 'online-noproof' ? 'Online · show proof at desk' : (kind === 'online-link' ? 'Online (UPI)' : 'Pay at the library')));
        var payLink = $('bk-paylink');
        if (payLink) { payLink.hidden = kind !== 'online-link'; if (kind === 'online-link') payLink.href = bk.result.url; }
        $('bk-done-title').textContent = kind === 'online' ? 'Payment proof sent!' : 'Seat request sent!';
        $('bk-done-sub').textContent = kind === 'online-link'
            ? 'Your seat is requested. Open the payment page to pay by UPI and upload the screenshot.'
            : kind === 'fallback'
            ? 'Online payment isn\'t set up for this library yet, so please pay at the library when you visit.'
            : 'The library has your request. Show this at the desk on your start date.';
        var wa = $('bk-wa');
        if (wa && S.phone) {
            var msg = 'Hi, I just booked on Libraro: ' + seatLabel(bk.seat) + ', ' + (t ? t.name : '') + ', ' + planName(bk.plan) + ', starting ' + niceDate(bk.start) + '. Name: ' + $('bk-name').value.trim() + '.';
            wa.href = 'https://wa.me/91' + S.phone + '?text=' + encodeURIComponent(msg);
        }
        // Mark the seat/shift as requested on the map (local only)
        var card = grid && grid.querySelector('.sm-seat[data-n="' + bk.seat.n + '"] .sm-slide[data-type="' + bk.type + '"]');
        if (card) { card.classList.add('is-mine'); card.querySelector('.sm-cta').innerHTML = 'Requested'; card.removeAttribute('data-book'); }
        showScreen('done');
    }

    nextBtn.addEventListener('click', function () {
        if (bk.busy) return;
        if (!dlg.querySelector('[data-screen="pay"]').hidden) { sendShot(); return; }
        if (!validStep(bk.step)) return;
        if (bk.step < 3) { setStep(bk.step + 1); if (bk.step === 2 && window.innerWidth > 640) setTimeout(function () { $('bk-name').focus(); }, 80); return; }
        submitBooking();
    });
    backBtn.addEventListener('click', function () { if (bk.step > 1 && !bk.busy) setStep(bk.step - 1); });
})();
