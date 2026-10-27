/* ==========================================================================
   Libraro — Find my library (student directory) + shared library card
   Endpoints (unchanged backend): LB_DIR.listUrl = route('get-libraries')
                                  LB_DIR.mapUrl  = route('getLibrariesLocations')
   LB_DIR.extra = { branchId: { f: feesFrom, s: [dayTypeIds], b: 1 (online booking) } } (read-only)
   Only real data is shown — no invented ratings or tags.
   ========================================================================== */
(function () {
    'use strict';
    var C = window.LB_DIR || {};
    var doc = document;
    var $ = function (id) { return doc.getElementById(id); };
    var EXTRA = C.extra || {};

    /* ---------- helpers ---------- */
    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
    function icon(name, cls) { return '<svg class="lb-i ' + (cls || '') + '" aria-hidden="true"><use href="' + C.icons + '#i-' + name + '"></use></svg>'; }
    function brand(name) { return '<svg class="lb-ib" aria-hidden="true"><use href="' + C.icons + '#b-' + name + '"></use></svg>'; }
    function parseArr(v) { if (!v) return []; if (Array.isArray(v)) return v; try { var a = JSON.parse(v); return Array.isArray(a) ? a : []; } catch (e) { return []; } }
    function assetUrl(p) {
        if (!p) return '';
        if (/^https?:\/\//i.test(p)) return p;
        p = String(p).replace(/^\/+/, '').replace(/^public\//, '');
        return C.assetBase + '/' + p;
    }
    function phone10(m) { var d = String(m || '').replace(/\D/g, ''); return d.length >= 10 ? d.slice(-10) : ''; }
    function initials(n) { return String(n || 'L').trim().split(/\s+/).slice(0, 2).map(function (w) { return w.charAt(0).toUpperCase(); }).join(''); }
    function inr(n) { return '₹' + Number(n).toLocaleString('en-IN', { maximumFractionDigits: 0 }); }
    function km(a, b) {
        var R = 6371, dLat = (b[0] - a[0]) * Math.PI / 180, dLng = (b[1] - a[1]) * Math.PI / 180;
        var x = Math.sin(dLat / 2) * Math.sin(dLat / 2) + Math.cos(a[0] * Math.PI / 180) * Math.cos(b[0] * Math.PI / 180) * Math.sin(dLng / 2) * Math.sin(dLng / 2);
        return R * 2 * Math.atan2(Math.sqrt(x), Math.sqrt(1 - x));
    }
    function toast(m) { if (window.LB && window.LB.toast) window.LB.toast(m); }

    // day_type_id → student-friendly shift bucket
    var SHIFT_OF = { 1: 'fullday', 2: 'morning', 3: 'evening', 9: 'night', 8: '24x7', 10: '24x7', 11: '24x7' };
    var SHIFT_META = { morning: ['sunrise', 'Morning'], evening: ['sunset', 'Evening'], fullday: ['sun', 'Full day'], night: ['moon', 'Night'], '24x7': ['clock-3', '24×7'] };
    function info(l) {
        var x = EXTRA[l.id] || EXTRA[String(l.id)] || {};
        var shifts = []; (x.s || []).forEach(function (d) { var b = SHIFT_OF[d]; if (b && shifts.indexOf(b) < 0) shifts.push(b); });
        var feats = parseArr(l.features).map(String).map(function (id) { return C.features && C.features[id]; }).filter(Boolean);
        return { from: x.f || null, shifts: shifts, book: !!x.b, feats: feats, imgs: parseArr(l.library_images), paid: Number(l.is_paid) === 1, seats: Number(l.seats) || 0 };
    }

    /* ---------- cards ---------- */
    // A calm colour pair per library (stable: based on the name) for libraries without photos
    function palette(name) { var h = 0; String(name).split('').forEach(function (c) { h = (h * 31 + c.charCodeAt(0)) >>> 0; }); return (h % 6) + 1; }
    function media(name, imgs, url, w, h) {
        var pal = palette(name);
        if (!imgs.length) {
            return '<a class="fd-media fd-noimg fd-pal-' + pal + '" href="' + url + '" tabindex="-1" aria-hidden="true">'
                + '<span class="fd-noimg-ic">' + icon('library-big') + '</span><b>' + esc(initials(name)) + '</b><small>Photos coming soon</small></a>';
        }
        var list = imgs.slice(0, 8);
        var slides = list.map(function (p, k) {
            return '<a class="fd-sl-item" href="' + url + '" tabindex="-1" aria-hidden="true"><img src="' + esc(assetUrl(p)) + '" alt="" ' + (k ? 'loading="lazy" ' : 'loading="lazy" ') + 'decoding="async" width="' + w + '" height="' + h + '" onerror="this.parentNode.classList.add(\'is-broken\');this.remove()"></a>';
        }).join('');
        var nav = '';
        if (list.length > 1) {
            nav = '<button type="button" class="fd-sl-btn fd-sl-prev" data-sl="-1" aria-label="Previous photo" disabled>' + icon('chevron-left') + '</button>'
                + '<button type="button" class="fd-sl-btn fd-sl-next" data-sl="1" aria-label="Next photo">' + icon('chevron-right') + '</button>'
                + '<div class="fd-sl-dots" aria-hidden="true">' + list.map(function (_, k) { return '<i' + (k ? '' : ' class="is-on"') + '></i>'; }).join('') + '</div>';
        }
        return '<div class="fd-media fd-pal-' + pal + '" data-fd-slider><div class="fd-sl-track">' + slides + '</div>' + nav + '</div>';
    }
    function card(l) {
        var name = l.display_name || l.library_name || 'Library';
        var url = l.slug ? C.base + '/' + encodeURIComponent(l.slug) : '#';
        var i = info(l);
        var tags = (i.paid ? '<span class="fd-tag fd-tag--ok">' + icon('badge-check') + 'Verified</span>' : '')
            + (i.book ? '<span class="fd-tag fd-tag--book">' + icon('armchair') + 'Online booking</span>' : '');
        var meta = [];
        if (i.seats) meta.push('<span>' + icon('armchair') + i.seats + ' seats</span>');
        if (l._km != null) meta.push('<span>' + icon('locate-fixed') + (l._km < 1 ? Math.round(l._km * 1000) + ' m' : l._km.toFixed(1) + ' km') + '</span>');
        if (l.city_name) meta.push('<span>' + icon('map-pin') + esc(l.city_name) + '</span>');
        var shifts = i.shifts.map(function (s) { return '<span title="' + SHIFT_META[s][1] + ' shift">' + icon(SHIFT_META[s][0]) + SHIFT_META[s][1] + '</span>'; }).join('');
        var facs = i.feats.slice(0, 5).map(function (f) { return '<span title="' + esc(f.n) + '" aria-label="' + esc(f.n) + '">' + icon(f.i) + '</span>'; }).join('')
            + (i.feats.length > 5 ? '<span class="fd-more-f" title="' + esc(i.feats.slice(5).map(function (f) { return f.n; }).join(', ')) + '">+' + (i.feats.length - 5) + '</span>' : '');
        var ph = phone10(l.mobile), contact = '';
        if (ph) {
            var wa = encodeURIComponent('Hi, I found ' + name + ' on Libraro. Is a study seat available?');
            contact = '<a class="fd-round" href="tel:+91' + ph + '" aria-label="Call ' + esc(name) + '">' + icon('phone') + '</a>'
                + '<a class="fd-round fd-round--wa" href="https://wa.me/91' + ph + '?text=' + wa + '" target="_blank" rel="noopener" aria-label="WhatsApp ' + esc(name) + '">' + brand('whatsapp') + '</a>';
        }
        return '<article class="fd-card">'
            + '<div class="fd-card-media">' + media(name, i.imgs, url, 480, 300)
            + (tags ? '<div class="fd-card-tags">' + tags + '</div>' : '')
            + (i.from ? '<div class="fd-card-price"><small>from</small><b>' + inr(i.from) + '</b><small>/mo</small></div>' : '') + '</div>'
            + '<div class="fd-card-body">'
            + '<a class="fd-card-name" href="' + url + '" title="' + esc(name) + '">' + esc(name) + '</a>'
            + '<p class="fd-card-addr" title="' + esc(l.library_address || '') + '">' + esc(l.library_address || l.city_name || 'Address not added yet') + '</p>'
            + '<div class="fd-card-meta">' + (meta.length ? meta.join('') : '<span class="is-muted">Seats not listed</span>') + '</div>'
            + '<div class="fd-card-shifts">' + (shifts || '<span class="is-muted">' + icon('clock-3') + 'Shifts not listed</span>') + '</div>'
            + '<div class="fd-card-facs" aria-label="Facilities">' + (facs || '<span class="is-muted">Facilities not listed</span>') + '</div>'
            + '<div class="fd-card-foot">' + contact
            + '<a class="lb-btn lb-btn--sm fd-card-cta' + (i.book ? '' : ' fd-card-cta--soft') + '" href="' + url + (i.book ? '#seats' : '') + '">' + (i.book ? icon('armchair') + 'Book a seat' : 'View library' + icon('arrow-right')) + '</a>'
            + '</div></div></article>';
    }
    function premiumCard(l) {
        var name = l.display_name || l.library_name || 'Library';
        var url = l.slug ? C.base + '/' + encodeURIComponent(l.slug) : '#';
        var i = info(l);
        var chips = [];
        if (i.from) chips.push('<span>' + icon('indian-rupee') + 'From ' + inr(i.from) + '/mo</span>');
        if (i.seats) chips.push('<span>' + icon('armchair') + i.seats + ' seats</span>');
        if (i.shifts.length) chips.push('<span>' + icon('clock-3') + i.shifts.length + (i.shifts.length > 1 ? ' shifts' : ' shift') + '</span>');
        return '<article class="fd-pcard">'
            + '<div class="fd-pcard-img">' + media(name, i.imgs, url, 720, 460) + '</div>'
            + '<div class="fd-pcard-shade"></div>'
            + '<div class="fd-pcard-top"><span class="fd-tag fd-tag--gold">' + icon('star') + 'Premium</span>' + (i.book ? '<span class="fd-tag fd-tag--glass">' + icon('armchair') + 'Book online</span>' : '') + '</div>'
            + '<div class="fd-pcard-body">'
            + '<h3><a href="' + url + '" title="' + esc(name) + '">' + esc(name) + '</a></h3>'
            + '<p>' + icon('map-pin') + '<span>' + esc([l.library_address, l.city_name].filter(Boolean).join(', ') || 'India') + '</span></p>'
            + (chips.length ? '<div class="fd-pcard-chips">' + chips.join('') + '</div>' : '')
            + '<a class="lb-btn lb-btn--light" href="' + url + (i.book ? '#seats' : '') + '">' + (i.book ? 'See free seats' : 'View library') + icon('arrow-right') + '</a>'
            + '</div></article>';
    }

    /* ---------- photo slider inside each card (delegated, works for cards added later) ---------- */
    function slPaint(sl) {
        var tr = sl.querySelector('.fd-sl-track'); if (!tr) return;
        var n = tr.children.length, i = Math.round(tr.scrollLeft / Math.max(1, tr.clientWidth));
        sl.querySelectorAll('.fd-sl-dots i').forEach(function (d, k) { d.classList.toggle('is-on', k === i); });
        var pv = sl.querySelector('.fd-sl-prev'), nx = sl.querySelector('.fd-sl-next');
        if (pv) pv.disabled = i <= 0; if (nx) nx.disabled = i >= n - 1;
    }
    doc.addEventListener('click', function (e) {
        var b = e.target.closest('[data-sl]'); if (!b) return;
        e.preventDefault(); e.stopPropagation();
        var sl = b.closest('[data-fd-slider]'), tr = sl && sl.querySelector('.fd-sl-track'); if (!tr) return;
        tr.scrollBy({ left: (+b.getAttribute('data-sl')) * tr.clientWidth, behavior: 'smooth' });
    });
    doc.addEventListener('scroll', function (e) {
        var t = e.target; if (!t.classList || !t.classList.contains('fd-sl-track')) return;
        var sl = t.closest('[data-fd-slider]'); if (!sl) return;
        cancelAnimationFrame(sl._raf); sl._raf = requestAnimationFrame(function () { slPaint(sl); });
    }, true);
    /* ---------- promo banner carousels ---------- */
    doc.querySelectorAll('[data-lb-promo]').forEach(function (pr) {
        var tr = pr.querySelector('[data-pr-track]'); if (!tr) return;
        var n = tr.children.length, paused = false;
        var idx = function () { return Math.round(tr.scrollLeft / Math.max(1, tr.clientWidth)); };
        var go = function (i) { i = (i + n) % n; tr.scrollTo({ left: i * tr.clientWidth, behavior: 'smooth' }); };
        var paint = function () { var i = idx(); pr.querySelectorAll('[data-pr-go]').forEach(function (d, k) { d.setAttribute('aria-current', k === i ? 'true' : 'false'); }); };
        pr.addEventListener('click', function (e) {
            var b = e.target.closest('[data-pr]'); if (b) { go(idx() + (+b.getAttribute('data-pr'))); return; }
            var d = e.target.closest('[data-pr-go]'); if (d) go(+d.getAttribute('data-pr-go'));
        });
        var t; tr.addEventListener('scroll', function () { clearTimeout(t); t = setTimeout(paint, 60); }, { passive: true });
        ['mouseenter', 'focusin', 'touchstart'].forEach(function (ev) { pr.addEventListener(ev, function () { paused = true; }, { passive: true }); });
        ['mouseleave', 'focusout'].forEach(function (ev) { pr.addEventListener(ev, function () { paused = false; }); });
        if (n > 1 && !(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches)) {
            setInterval(function () {
                if (paused || doc.hidden) return;
                var r = pr.getBoundingClientRect(); if (r.bottom < 0 || r.top > window.innerHeight) return;
                go(idx() + 1);
            }, 5500);
        }
    });

    window.LBFindCard = card;

    var grid = $('lb-results');
    if (!grid) return;

    /* ---------- elements & state ---------- */
    var citySel = $('city-item'), input = $('library-search'), clearBtn = $('clear-search-btn');
    var form = $('lb-search'), sug = $('lb-suggest'), sub = $('lb-results-sub');
    var moreBtn = $('lb-load-more'), nearBtn = $('lb-near'), sortSel = $('fd-sort');
    var panel = $('fd-filters'), scrim = doc.querySelector('.fd-scrim'), activeBox = $('fd-active');
    var premSec = $('fd-premium'), premTrack = $('fd-premium-track');
    var state = { city: '', q: '', list: [], view: [], shown: 0, near: null, sort: 'rec',
        f: { budget: 0, shift: [], fac: [], size: '', book: false, verified: false, photos: false } };
    var locCache = null, reqId = 0;

    function skeleton(n) { var h = ''; for (var i = 0; i < n; i++) h += '<div class="fd-skel" aria-hidden="true"><div class="s-img"></div><div class="s-l"></div><div class="s-l w60"></div><div class="s-l w40"></div></div>'; return h; }
    function cityName() { var o = citySel && citySel.options[citySel.selectedIndex]; return o && o.value ? o.text : ''; }

    /* ---------- filtering ---------- */
    function passes(l) {
        var f = state.f, i = info(l);
        if (f.budget && !(i.from && i.from <= f.budget)) return false;
        if (f.shift.length && !f.shift.some(function (s) { return i.shifts.indexOf(s) > -1; })) return false;
        if (f.fac.length) { var have = i.feats.map(function (x) { return x.i; }); if (!f.fac.every(function (k) { return have.indexOf(k) > -1; })) return false; }
        if (f.size === 's' && !(i.seats && i.seats < 40)) return false;
        if (f.size === 'm' && !(i.seats >= 40 && i.seats <= 100)) return false;
        if (f.size === 'l' && !(i.seats > 100)) return false;
        if (f.book && !i.book) return false;
        if (f.verified && !i.paid) return false;
        if (f.photos && !i.imgs.length) return false;
        return true;
    }
    function sorted(list) {
        var a = list.slice();
        if (state.sort === 'price') a.sort(function (x, y) { var px = info(x).from || 1e9, py = info(y).from || 1e9; return px - py; });
        else if (state.sort === 'seats') a.sort(function (x, y) { return (Number(y.seats) || 0) - (Number(x.seats) || 0); });
        else if (state.sort === 'near') a.sort(function (x, y) { return (x._km == null ? 1e9 : x._km) - (y._km == null ? 1e9 : y._km); });
        else a.sort(function (x, y) { // recommended: bookable + verified + photos first, keep server order otherwise
            var sx = (info(x).book ? 4 : 0) + (info(x).paid ? 2 : 0) + (info(x).imgs.length ? 1 : 0);
            var sy = (info(y).book ? 4 : 0) + (info(y).paid ? 2 : 0) + (info(y).imgs.length ? 1 : 0);
            return sy - sx;
        });
        return a;
    }
    function activeCount() { var f = state.f; return (f.budget ? 1 : 0) + f.shift.length + f.fac.length + (f.size ? 1 : 0) + (f.book ? 1 : 0) + (f.verified ? 1 : 0) + (f.photos ? 1 : 0); }

    function paintControls() {
        var f = state.f;
        doc.querySelectorAll('[data-fd-group]').forEach(function (g) {
            var key = g.getAttribute('data-fd-group');
            g.querySelectorAll('button[data-v]').forEach(function (b) {
                var v = b.getAttribute('data-v'), on;
                if (key === 'budget') on = String(f.budget) === v;
                else if (key === 'size') on = f.size === v;
                else on = f[key].indexOf(v) > -1;
                b.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
        });
        doc.querySelectorAll('[data-fd-flag]').forEach(function (c) { c.checked = !!f[c.getAttribute('data-fd-flag')]; });
        doc.querySelectorAll('.fd-need[data-need]').forEach(function (b) {
            var n = b.getAttribute('data-need').split(':'), on = false;
            if (n[0] === 'book' || n[0] === 'verified') on = f[n[0]];
            else if (n[0] === 'budget') on = String(f.budget) === n[1];
            else if (n[0] === 'shift') on = f.shift.indexOf(n[1]) > -1;
            else if (n[0] === 'fac') on = f.fac.indexOf(n[1]) > -1;
            b.classList.toggle('is-on', on); b.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
        var n = activeCount();
        doc.querySelectorAll('[data-fd-active]').forEach(function (el) { el.textContent = n; el.hidden = !n; });
        // active pills
        var pills = [];
        if (f.budget) pills.push(['budget', '', 'Under ' + inr(f.budget)]);
        f.shift.forEach(function (s) { pills.push(['shift', s, SHIFT_META[s][1] + ' shift']); });
        f.fac.forEach(function (k) { var b = doc.querySelector('[data-fd-group="fac"] [data-v="' + k + '"]'); pills.push(['fac', k, b ? b.textContent.trim() : k]); });
        if (f.size) pills.push(['size', '', { s: 'Under 40 seats', m: '40–100 seats', l: '100+ seats' }[f.size]]);
        if (f.book) pills.push(['book', '', 'Online booking']);
        if (f.verified) pills.push(['verified', '', 'Verified']);
        if (f.photos) pills.push(['photos', '', 'With photos']);
        activeBox.hidden = !pills.length;
        activeBox.innerHTML = pills.map(function (p) { return '<button type="button" class="fd-apill" data-k="' + p[0] + '" data-v="' + esc(p[1]) + '">' + esc(p[2]) + icon('x') + '</button>'; }).join('')
            + (pills.length ? '<button type="button" class="fd-link" data-fd-clear>Clear all</button>' : '');
    }

    function apply(scrollTop) {
        state.view = sorted(state.list.filter(passes));
        state.shown = 0;
        grid.innerHTML = '';
        grid.setAttribute('aria-busy', 'false');
        paintControls();
        doc.querySelectorAll('[data-fd-count]').forEach(function (el) { el.textContent = state.view.length; });
        doc.querySelectorAll('[data-fd-noun]').forEach(function (el) { el.textContent = state.view.length === 1 ? 'library' : 'libraries'; });
        if (!state.view.length) {
            var filtered = activeCount() > 0 && state.list.length;
            grid.innerHTML = '<div class="fd-empty"><i>' + icon(filtered ? 'list-tree' : 'search') + '</i>'
                + '<h3>' + (filtered ? 'No library matches all your filters' : 'No libraries found') + '</h3>'
                + '<p>' + (filtered ? 'Try removing a filter or two. ' + state.list.length + ' libraries are available here.' : 'Try another area or city, or clear your search to see all libraries.') + '</p>'
                + '<div class="lb-actions"><button type="button" class="lb-btn" ' + (filtered ? 'data-fd-clear' : 'data-reset') + '>' + (filtered ? 'Clear filters' : 'Show all libraries') + '</button></div></div>';
            moreBtn.hidden = true;
        } else {
            renderMore();
        }
        var where = state.near ? ' near you' : (cityName() ? ' in ' + cityName() : '');
        sub.textContent = state.view.length + (state.view.length === 1 ? ' library' : ' libraries') + where + (state.q ? ' for "' + state.q + '"' : '') + (activeCount() ? ' · ' + activeCount() + ' filter' + (activeCount() > 1 ? 's' : '') + ' on' : '');
        if (scrollTop) { var top = $('directory-explore').getBoundingClientRect().top + window.scrollY - 80; if (window.scrollY > top) window.scrollTo({ top: top, behavior: 'smooth' }); }
    }
    function renderMore() {
        var next = state.view.slice(state.shown, state.shown + C.perPage);
        grid.insertAdjacentHTML('beforeend', next.map(card).join(''));
        state.shown += next.length;
        moreBtn.hidden = state.shown >= state.view.length;
    }
    function renderPremium() {
        var prem = state.list.filter(function (l) { return Number(l.is_paid) === 1; });
        prem.sort(function (a, b) { return (info(b).book ? 2 : 0) + (info(b).imgs.length ? 1 : 0) - (info(a).book ? 2 : 0) - (info(a).imgs.length ? 1 : 0); });
        prem = prem.slice(0, 10);
        premSec.hidden = !prem.length;
        premTrack.innerHTML = prem.map(premiumCard).join('');
        premTrack.scrollLeft = 0;
    }

    function syncUrl() {
        try {
            var u = new URL(window.location.href);
            ['city', 'q'].forEach(function (k) { u.searchParams.delete(k); });
            if (state.city) u.searchParams.set('city', state.city);
            if (state.q) u.searchParams.set('q', state.q);
            history.replaceState(null, '', u.toString());
        } catch (e) {}
    }
    function loadLocations() {
        if (locCache) return Promise.resolve(locCache);
        return fetch(C.mapUrl, { headers: { Accept: 'application/json' } }).then(function (r) { return r.json(); }).then(function (d) { locCache = d || []; return locCache; });
    }

    function search(opts) {
        opts = opts || {};
        var my = ++reqId;
        grid.setAttribute('aria-busy', 'true');
        grid.innerHTML = skeleton(6);
        moreBtn.hidden = true;
        sub.textContent = 'Searching…';
        var url = C.listUrl + '?query=' + encodeURIComponent(state.q) + '&city=' + encodeURIComponent(state.city) + '&category=all';
        var p = fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }).then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); });
        var withNear = state.near ? Promise.all([p, loadLocations()]) : p.then(function (l) { return [l, null]; });
        withNear.then(function (res) {
            if (my !== reqId) return;
            var list = Array.isArray(res[0]) ? res[0] : [];
            if (state.near && res[1]) {
                var pos = {}; res[1].forEach(function (x) { pos[x.id] = [Number(x.latitude), Number(x.longitude)]; });
                list.forEach(function (l) { l._km = pos[l.id] ? km(state.near, pos[l.id]) : null; });
            }
            state.list = list;
            renderPremium();
            apply(opts.scroll);
            syncUrl();
        }).catch(function () {
            if (my !== reqId) return;
            grid.innerHTML = '<div class="fd-empty"><i>' + icon('circle-alert') + '</i><h3>Could not load libraries</h3><p>Please check your internet connection and try again.</p><div class="lb-actions"><button type="button" class="lb-btn" data-retry>Try again</button></div></div>';
            sub.textContent = '';
        });
    }

    /* ---------- filter controls ---------- */
    doc.querySelectorAll('[data-fd-group]').forEach(function (g) {
        var key = g.getAttribute('data-fd-group');
        g.addEventListener('click', function (e) {
            var b = e.target.closest('button[data-v]'); if (!b) return;
            var v = b.getAttribute('data-v');
            if (key === 'budget') state.f.budget = Number(v) || 0;
            else if (key === 'size') state.f.size = v;
            else { var arr = state.f[key], i = arr.indexOf(v); if (i > -1) arr.splice(i, 1); else arr.push(v); }
            apply();
        });
    });
    doc.querySelectorAll('[data-fd-flag]').forEach(function (c) { c.addEventListener('change', function () { state.f[c.getAttribute('data-fd-flag')] = c.checked; apply(); }); });
    doc.querySelectorAll('.fd-need[data-need]').forEach(function (b) {
        b.addEventListener('click', function () {
            var n = b.getAttribute('data-need').split(':'), f = state.f;
            if (n[0] === 'book' || n[0] === 'verified') f[n[0]] = !f[n[0]];
            else if (n[0] === 'budget') f.budget = String(f.budget) === n[1] ? 0 : Number(n[1]);
            else { var arr = f[n[0]], i = arr.indexOf(n[1]); if (i > -1) arr.splice(i, 1); else arr.push(n[1]); }
            apply(true);
        });
    });
    function clearAll() { state.f = { budget: 0, shift: [], fac: [], size: '', book: false, verified: false, photos: false }; apply(); }
    doc.addEventListener('click', function (e) {
        if (e.target.closest('[data-fd-clear]')) { clearAll(); return; }
        var p = e.target.closest('.fd-apill');
        if (p) {
            var k = p.getAttribute('data-k'), v = p.getAttribute('data-v');
            if (k === 'budget') state.f.budget = 0; else if (k === 'size') state.f.size = '';
            else if (k === 'shift' || k === 'fac') state.f[k] = state.f[k].filter(function (x) { return x !== v; });
            else state.f[k] = false;
            apply();
        }
    });
    sortSel.addEventListener('change', function () { state.sort = sortSel.value; apply(); });

    // Mobile filter sheet
    function sheet(open) {
        panel.classList.toggle('is-open', open);
        if (scrim) scrim.hidden = !open;
        doc.documentElement.classList.toggle('fd-lock', open);
        if (open) { var c = panel.querySelector('.fd-filters-body'); if (c) c.scrollTop = 0; }
    }
    doc.querySelectorAll('[data-fd-filters-open]').forEach(function (b) { b.addEventListener('click', function () { sheet(true); }); });
    doc.querySelectorAll('[data-fd-filters-close]').forEach(function (b) { b.addEventListener('click', function () { sheet(false); }); });
    doc.addEventListener('keydown', function (e) { if (e.key === 'Escape' && panel.classList.contains('is-open')) sheet(false); });

    // Carousel arrows
    doc.querySelectorAll('[data-fd-prev],[data-fd-next]').forEach(function (b) {
        b.addEventListener('click', function () {
            var t = doc.querySelector('[data-fd-track="' + (b.getAttribute('data-fd-prev') || b.getAttribute('data-fd-next')) + '"]');
            if (!t) return; var c = t.firstElementChild, step = c ? c.offsetWidth + 18 : 360;
            t.scrollBy({ left: b.hasAttribute('data-fd-prev') ? -step : step, behavior: 'smooth' });
        });
    });

    /* ---------- suggestions ---------- */
    var sugTimer = null, sugItems = [], sugIdx = -1;
    function hideSug() { sug.hidden = true; input.setAttribute('aria-expanded', 'false'); sugIdx = -1; }
    function showSug(items, q) {
        sugItems = items.slice(0, 6);
        if (!sugItems.length) sug.innerHTML = '<li class="fd-suggest-empty">No library named "' + esc(q) + '". Press Search to look by area.</li>';
        else sug.innerHTML = sugItems.map(function (l, i) {
            var n = l.display_name || l.library_name;
            return '<li role="option" id="sug-' + i + '"><button type="button" data-i="' + i + '"><i>' + icon('library-big') + '</i><span><b>' + esc(n) + '</b><small>' + esc([l.library_address, l.city_name].filter(Boolean).join(', ')) + '</small></span>' + icon('arrow-right') + '</button></li>';
        }).join('');
        sug.hidden = false; input.setAttribute('aria-expanded', 'true');
    }
    function paintSug() { sug.querySelectorAll('button').forEach(function (b, i) { b.classList.toggle('is-active', i === sugIdx); }); }
    input.addEventListener('input', function () {
        clearBtn.hidden = !input.value;
        clearTimeout(sugTimer);
        var q = input.value.trim();
        if (q.length < 2) { hideSug(); return; }
        sugTimer = setTimeout(function () {
            fetch(C.listUrl + '?query=' + encodeURIComponent(q) + '&city=' + encodeURIComponent(citySel.value) + '&category=all', { headers: { Accept: 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (d) { if (input.value.trim() === q) showSug(Array.isArray(d) ? d : [], q); })
                .catch(hideSug);
        }, 250);
    });
    input.addEventListener('keydown', function (e) {
        if (sug.hidden) return;
        var n = sugItems.length;
        if (e.key === 'ArrowDown' && n) { sugIdx = (sugIdx + 1) % n; paintSug(); e.preventDefault(); }
        else if (e.key === 'ArrowUp' && n) { sugIdx = (sugIdx - 1 + n) % n; paintSug(); e.preventDefault(); }
        else if (e.key === 'Enter' && sugIdx > -1) { e.preventDefault(); openLib(sugItems[sugIdx]); }
        else if (e.key === 'Escape') hideSug();
    });
    function openLib(l) { if (l && l.slug) window.location.href = C.base + '/' + encodeURIComponent(l.slug); }
    sug.addEventListener('click', function (e) { var b = e.target.closest('button[data-i]'); if (b) openLib(sugItems[+b.getAttribute('data-i')]); });
    doc.addEventListener('click', function (e) { if (!e.target.closest('#lb-search')) hideSug(); });
    clearBtn.addEventListener('click', function () { input.value = ''; clearBtn.hidden = true; hideSug(); state.q = ''; input.focus(); search(); });

    /* ---------- search triggers ---------- */
    function nearOff() { state.near = null; nearBtn.classList.remove('is-on'); nearBtn.setAttribute('aria-pressed', 'false'); var o = sortSel.querySelector('[value="near"]'); o.hidden = true; if (state.sort === 'near') { state.sort = 'rec'; sortSel.value = 'rec'; } }
    form.addEventListener('submit', function (e) { e.preventDefault(); hideSug(); state.q = input.value.trim(); state.city = citySel.value; nearOff(); search({ scroll: true }); input.blur(); });
    citySel.addEventListener('change', function () { state.city = citySel.value; nearOff(); search(); });
    moreBtn.addEventListener('click', renderMore);
    grid.addEventListener('click', function (e) {
        if (e.target.closest('[data-reset]')) { state.q = ''; state.city = ''; input.value = ''; citySel.value = ''; clearBtn.hidden = true; nearOff(); clearAll(); search(); }
        if (e.target.closest('[data-retry]')) search();
    });

    /* ---------- near me ---------- */
    nearBtn.addEventListener('click', function () {
        if (state.near) { nearOff(); search(); return; }
        if (!navigator.geolocation) { toast('Location is not available on this device.'); return; }
        nearBtn.classList.add('is-on'); sub.textContent = 'Finding libraries near you…';
        navigator.geolocation.getCurrentPosition(function (pos) {
            state.near = [pos.coords.latitude, pos.coords.longitude];
            state.city = ''; citySel.value = '';
            var o = sortSel.querySelector('[value="near"]'); o.hidden = false; state.sort = 'near'; sortSel.value = 'near';
            nearBtn.setAttribute('aria-pressed', 'true');
            search({ scroll: true });
        }, function () {
            nearBtn.classList.remove('is-on');
            toast('Please allow location access, or choose your city.');
            citySel.focus();
        }, { enableHighAccuracy: false, timeout: 10000, maximumAge: 300000 });
    });

    /* ---------- initial state (from URL) ---------- */
    try {
        var params = new URLSearchParams(window.location.search);
        if (params.get('city')) { citySel.value = params.get('city'); state.city = citySel.value; }
        if (params.get('q')) { input.value = params.get('q'); state.q = params.get('q'); clearBtn.hidden = false; }
    } catch (e) {}
    search();

    /* ---------- map (Leaflet loaded only when the map scrolls into view) ---------- */
    var mapEl = $('lb-map');
    if (mapEl) {
        var started = false;
        var startMap = function () {
            if (started) return; started = true;
            var css = doc.createElement('link'); css.rel = 'stylesheet'; css.href = 'https://unpkg.com/leaflet@1.7.1/dist/leaflet.css'; doc.head.appendChild(css);
            var js = doc.createElement('script'); js.src = 'https://unpkg.com/leaflet@1.7.1/dist/leaflet.js'; js.async = true;
            js.onload = function () {
                var L = window.L;
                var map = L.map(mapEl, { scrollWheelZoom: false }).setView([23.5, 79], 5);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18, attribution: '&copy; OpenStreetMap' }).addTo(map);
                loadLocations().then(function (locs) {
                    var bounds = [];
                    locs.forEach(function (x) {
                        var ll = [Number(x.latitude), Number(x.longitude)]; if (!isFinite(ll[0]) || !isFinite(ll[1])) return;
                        bounds.push(ll);
                        L.marker(ll).addTo(map).bindPopup('<b>' + esc(x.library_name) + '</b><br>' + esc(x.library_address || '') + (x.slug ? '<br><a href="' + C.base + '/' + encodeURIComponent(x.slug) + '">View library →</a>' : ''));
                    });
                    if (bounds.length > 1) map.fitBounds(bounds, { padding: [30, 30], maxZoom: 12 });
                    var ph = $('lb-map-ph'); if (ph) ph.remove();
                }).catch(function () { var ph = $('lb-map-ph'); if (ph) ph.textContent = 'Map could not be loaded.'; });
            };
            js.onerror = function () { var ph = $('lb-map-ph'); if (ph) ph.textContent = 'Map could not be loaded.'; };
            doc.body.appendChild(js);
        };
        if ('IntersectionObserver' in window) {
            var io = new IntersectionObserver(function (en) { if (en[0].isIntersecting) { startMap(); io.disconnect(); } }, { rootMargin: '300px' });
            io.observe(mapEl);
        } else startMap();
    }
})();
