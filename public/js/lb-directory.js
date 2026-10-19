/* ==========================================================================
   Libraro — Find my library (student directory)
   Endpoints (unchanged backend): LB_DIR.listUrl  = route('get-libraries')
                                  LB_DIR.mapUrl   = route('getLibrariesLocations')
   Shows only real data from the API (no invented ratings or tags).
   ========================================================================== */
(function () {
    'use strict';
    var C = window.LB_DIR || {};
    var doc = document;
    var $ = function (id) { return doc.getElementById(id); };
    var citySel = $('city-item'), input = $('library-search'), clearBtn = $('clear-search-btn');
    var form = $('lb-search'), sug = $('lb-suggest'), grid = $('lb-results'), sub = $('lb-results-sub');
    var moreBtn = $('lb-load-more'), nearBtn = $('lb-near');
    var state = { city: '', q: '', cat: 'all', list: [], shown: 0, near: null };
    var locCache = null;
    var reqId = 0;

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
    function km(a, b) {
        var R = 6371, dLat = (b[0] - a[0]) * Math.PI / 180, dLng = (b[1] - a[1]) * Math.PI / 180;
        var x = Math.sin(dLat / 2) * Math.sin(dLat / 2) + Math.cos(a[0] * Math.PI / 180) * Math.cos(b[0] * Math.PI / 180) * Math.sin(dLng / 2) * Math.sin(dLng / 2);
        return R * 2 * Math.atan2(Math.sqrt(x), Math.sqrt(1 - x));
    }
    function toast(m) { if (window.LB && window.LB.toast) window.LB.toast(m); }

    /* ---------- card ---------- */
    function card(l) {
        var name = l.display_name || l.library_name || 'Library';
        var url = l.slug ? C.base + '/' + encodeURIComponent(l.slug) : '#';
        var imgs = parseArr(l.library_images);
        var cover = imgs.length ? assetUrl(imgs[0]) : '';
        var coverHtml = cover
            ? '<img src="' + esc(cover) + '" alt="" loading="lazy" decoding="async" width="400" height="250" onerror="this.parentNode.innerHTML=\'<div class=&quot;lb-lib-noimg&quot;>' + esc(initials(name)) + '</div>\'">'
            : '<div class="lb-lib-noimg" aria-hidden="true">' + esc(initials(name)) + '</div>';
        var badges = '<span>' + (Number(l.is_paid) === 1 ? '<span class="lb-badge lb-badge--ok">' + icon('badge-check') + 'Verified</span>' : '') + '</span>'
            + (l.city_name ? '<span class="lb-badge">' + icon('map-pin') + esc(l.city_name) + '</span>' : '');
        var feats = parseArr(l.features).map(String).map(function (id) { return C.features && C.features[id]; }).filter(Boolean);
        var amen = feats.slice(0, 3).map(function (f) { return '<span>' + icon(f.i) + esc(f.n) + '</span>'; }).join('');
        if (feats.length > 3) amen += '<span>+' + (feats.length - 3) + ' more</span>';
        var meta = [];
        if (Number(l.seats) > 0) meta.push('<span class="lb-lib-seats">' + icon('armchair') + Number(l.seats) + ' seats</span>');
        if (l._km != null) meta.push('<span class="lb-lib-seats">' + icon('locate-fixed') + (l._km < 1 ? Math.round(l._km * 1000) + ' m' : l._km.toFixed(1) + ' km') + ' away</span>');
        var ph = phone10(l.mobile), contact = '';
        if (ph) {
            var wa = encodeURIComponent('Hi, I found ' + name + ' on Libraro. Is a study seat available?');
            contact = '<a class="lb-round" href="tel:+91' + ph + '" aria-label="Call ' + esc(name) + '">' + icon('phone') + '</a>'
                + '<a class="lb-round lb-round--wa" href="https://wa.me/91' + ph + '?text=' + wa + '" target="_blank" rel="noopener" aria-label="WhatsApp ' + esc(name) + '">' + brand('whatsapp') + '</a>';
        }
        return '<article class="lb-lib">'
            + '<a class="lb-lib-cover" href="' + url + '" tabindex="-1" aria-hidden="true">' + coverHtml + '<div class="lb-badges">' + badges + '</div></a>'
            + '<div class="lb-lib-body">'
            + '<a class="lb-lib-name" href="' + url + '">' + esc(name) + '</a>'
            + (l.library_address ? '<div class="lb-lib-addr">' + icon('map-pin') + '<span>' + esc(l.library_address) + '</span></div>' : '')
            + (meta.length ? '<div class="lb-amen">' + meta.join('') + '</div>' : '')
            + (amen ? '<div class="lb-amen">' + amen + '</div>' : '')
            + '<div class="lb-lib-foot"><a class="lb-btn" href="' + url + '">View library</a>' + contact + '</div>'
            + '</div></article>';
    }

    window.LBCard = card;
    if (!grid) return;

    function skeleton(n) {
        var h = ''; for (var i = 0; i < n; i++) h += '<div class="lb-skel" aria-hidden="true"><div class="s-img"></div><div class="s-l"></div><div class="s-l w60"></div><div class="s-btn"></div></div>';
        return h;
    }

    function cityName() { var o = citySel && citySel.options[citySel.selectedIndex]; return o && o.value ? o.text : ''; }

    function render(reset) {
        var list = state.list;
        if (reset) { grid.innerHTML = ''; state.shown = 0; }
        grid.setAttribute('aria-busy', 'false');
        if (!list.length) {
            grid.innerHTML = '<div class="lb-empty-state">' + '<i>' + icon('search') + '</i>'
                + '<h3>No libraries found</h3><p>Try another area or city, or clear your search to see all libraries.</p>'
                + '<div class="lb-actions"><button type="button" class="lb-btn" data-reset>Show all libraries</button></div></div>';
            moreBtn.hidden = true;
            sub.textContent = 'No results' + (state.q ? ' for "' + state.q + '"' : '') + (cityName() ? ' in ' + cityName() : '');
            return;
        }
        var next = list.slice(state.shown, state.shown + C.perPage);
        grid.insertAdjacentHTML('beforeend', next.map(card).join(''));
        state.shown += next.length;
        moreBtn.hidden = state.shown >= list.length;
        var where = state.near ? ' nearest to you' : (cityName() ? ' in ' + cityName() : '');
        sub.textContent = list.length + (list.length === 1 ? ' library' : ' libraries') + where + (state.q ? ' matching "' + state.q + '"' : '');
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
        grid.innerHTML = skeleton(8);
        moreBtn.hidden = true;
        sub.textContent = 'Searching…';
        var url = C.listUrl + '?query=' + encodeURIComponent(state.q) + '&city=' + encodeURIComponent(state.city) + '&category=' + encodeURIComponent(state.cat);
        var p = fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' } }).then(function (r) { if (!r.ok) throw new Error(r.status); return r.json(); });
        var withNear = state.near ? Promise.all([p, loadLocations()]) : p.then(function (l) { return [l, null]; });
        withNear.then(function (res) {
            if (my !== reqId) return;
            var list = Array.isArray(res[0]) ? res[0] : [];
            if (state.near && res[1]) {
                var pos = {}; res[1].forEach(function (x) { pos[x.id] = [Number(x.latitude), Number(x.longitude)]; });
                list.forEach(function (l) { l._km = pos[l.id] ? km(state.near, pos[l.id]) : null; });
                list.sort(function (a, b) { return (a._km == null ? 1e9 : a._km) - (b._km == null ? 1e9 : b._km); });
            }
            state.list = list;
            render(true);
            syncUrl();
            if (opts.scroll) doc.getElementById('directory-explore').scrollIntoView({ behavior: 'smooth', block: 'start' });
        }).catch(function () {
            if (my !== reqId) return;
            grid.innerHTML = '<div class="lb-empty-state"><i>' + icon('circle-alert') + '</i><h3>Could not load libraries</h3><p>Please check your internet connection and try again.</p><div class="lb-actions"><button type="button" class="lb-btn" data-retry>Try again</button></div></div>';
            sub.textContent = '';
        });
    }

    /* ---------- suggestions ---------- */
    var sugTimer = null, sugItems = [], sugIdx = -1;
    function hideSug() { sug.hidden = true; input.setAttribute('aria-expanded', 'false'); sugIdx = -1; }
    function showSug(items, q) {
        sugItems = items.slice(0, 6);
        if (!sugItems.length) { sug.innerHTML = '<li class="lb-suggest-empty">No library named "' + esc(q) + '". Press Search to look by area.</li>'; }
        else sug.innerHTML = sugItems.map(function (l, i) {
            var n = l.display_name || l.library_name;
            return '<li role="option" id="sug-' + i + '"><button type="button" data-i="' + i + '"><i>' + icon('library-big') + '</i><span><b>' + esc(n) + '</b><small>' + esc([l.library_address, l.city_name].filter(Boolean).join(', ')) + '</small></span></button></li>';
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
    form.addEventListener('submit', function (e) {
        e.preventDefault(); hideSug();
        state.q = input.value.trim(); state.city = citySel.value; state.near = null; nearBtn.classList.remove('is-on');
        paintChips(); search({ scroll: true });
        input.blur();
    });
    citySel.addEventListener('change', function () { state.city = citySel.value; state.near = null; nearBtn.classList.remove('is-on'); paintChips(); search(); });

    function paintChips() { doc.querySelectorAll('.lb-quick [data-city]').forEach(function (c) { c.classList.toggle('is-on', c.getAttribute('data-city') === state.city); }); }
    doc.querySelectorAll('.lb-quick [data-city]').forEach(function (c) {
        c.addEventListener('click', function () {
            var id = c.getAttribute('data-city');
            state.city = state.city === id ? '' : id; citySel.value = state.city; state.near = null; nearBtn.classList.remove('is-on');
            paintChips(); search({ scroll: true });
        });
    });

    doc.querySelectorAll('[data-cat]').forEach(function (b) {
        b.addEventListener('click', function () {
            state.cat = b.getAttribute('data-cat');
            doc.querySelectorAll('[data-cat]').forEach(function (x) { x.setAttribute('aria-pressed', x === b ? 'true' : 'false'); });
            search();
        });
    });

    moreBtn.addEventListener('click', function () { render(false); });
    grid.addEventListener('click', function (e) {
        if (e.target.closest('[data-reset]')) { state.q = ''; state.city = ''; state.cat = 'all'; state.near = null; input.value = ''; citySel.value = ''; clearBtn.hidden = true; nearBtn.classList.remove('is-on'); paintChips(); doc.querySelectorAll('[data-cat]').forEach(function (x) { x.setAttribute('aria-pressed', x.getAttribute('data-cat') === 'all' ? 'true' : 'false'); }); search(); }
        if (e.target.closest('[data-retry]')) search();
    });

    /* ---------- near me ---------- */
    nearBtn.addEventListener('click', function () {
        if (state.near) { state.near = null; nearBtn.classList.remove('is-on'); search(); return; }
        if (!navigator.geolocation) { toast('Location is not available on this device.'); return; }
        nearBtn.classList.add('is-on'); sub.textContent = 'Finding libraries near you…';
        navigator.geolocation.getCurrentPosition(function (pos) {
            state.near = [pos.coords.latitude, pos.coords.longitude];
            state.city = ''; citySel.value = ''; paintChips();
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
    paintChips();
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
                        L.marker(ll).addTo(map).bindPopup('<b>' + esc(x.library_name) + '</b>' + esc(x.library_address || '') + (x.slug ? '<br><a href="' + C.base + '/' + encodeURIComponent(x.slug) + '">View library →</a>' : ''));
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
