/* Libraro — members list (learner/learner.blade.php).
   Filters, search, sort, status tiles and pagination load over AJAX (same GET URLs as before, so links,
   back/forward and refresh still work). Card / list layout, quick action icons, "View details" drawer. */
(function ($) {
    'use strict';
    if (!$) return;
    var root = document.getElementById('lmlRoot');
    if (!root) return;
    var form = document.getElementById('learnerFilterForm');
    var base = root.getAttribute('data-base');
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ---------------- layout: cards / list ---------------- */
    function view() { try { return localStorage.getItem('lml-view') || 'grid'; } catch (e) { return 'grid'; } }
    function applyView(v) {
        var list = document.getElementById('learnerCardsList');
        root.classList.toggle('is-list', v === 'list');
        if (list) list.classList.toggle('is-list', v === 'list');
        $(root).find('.lml-view button').each(function () { var on = this.getAttribute('data-view') === v; this.classList.toggle('is-on', on); this.setAttribute('aria-pressed', String(on)); });
        if (v === 'list') buildQuick();
    }
    $(root).on('click', '.lml-view button', function () {
        var v = this.getAttribute('data-view');
        try { localStorage.setItem('lml-view', v); } catch (e) {}
        applyView(v);
    });

    // List layout: a few of each member's menu actions become one-tap icons (same links, same handlers)
    var QUICK = [/^view$/i, /^edit$/i, /^delete$/i, /^receipt$/i, /^attendance$/i, /^swap$/i, /^other pay$/i, /^id card$/i];
    function buildQuick() {
        $('#learnerCardsList .lml-card').each(function () {
            var $q = $(this).find('.lml-quick');
            if ($q.data('built')) return;
            $q.data('built', 1);
            if (window.lpStyleOps) window.lpStyleOps(this);
            var $items = $(this).find('.lml-acts-grid .mobile-action-item');
            QUICK.forEach(function (re) {
                $items.each(function () {
                    var label = $.trim($(this).find('.mobile-action-label').text());
                    if (!re.test(label)) return;
                    var $a = $(this).find('.action-btn').first();
                    if (!$a.length) return false;
                    var $c = $a.clone(false).removeAttr('id').addClass('lml-qbtn').attr({ title: label, 'aria-label': label });
                    if ($(this).hasClass('is-danger')) $c.addClass('is-danger');
                    $q.append($c);
                    return false;
                });
            });
        });
    }

    /* ---------------- AJAX navigation ---------------- */
    var busy = null, lastUrl = location.href;
    function serialize() {
        var p = new URLSearchParams();
        $(form).serializeArray().forEach(function (f) { if (f.value !== '' && f.value != null) p.append(f.name, f.value); });
        var qs = p.toString();
        return base + (qs ? '?' + qs : '');
    }
    function swap(doc) {
        ['#lmlTop', '#lmlLive'].forEach(function (sel) {
            var fresh = doc.querySelector(sel), cur = document.querySelector(sel);
            if (fresh && cur) cur.innerHTML = fresh.innerHTML;
        });
        // keep the filter controls in step with the server (counts badge, selected values) without losing focus
        var fresh = doc.getElementById('learnerFilterForm');
        if (fresh) {
            ['status', 'plan_id', 'payment_filter', 'seat_no'].forEach(function (id) {
                var a = fresh.querySelector('#' + id), b = form.querySelector('#' + id);
                if (a && b && b !== document.activeElement) b.value = a.value;
            });
            var fb = fresh.querySelector('.lml-fbtn'), cb = form.querySelector('.lml-fbtn');
            if (fb && cb) cb.outerHTML = fb.outerHTML;
            var fp = fresh.querySelector('#lmlPdfLink'), cp = form.querySelector('#lmlPdfLink');
            if (fp && cp) cp.setAttribute('href', fp.getAttribute('href'));
        }
        var sync = doc.getElementById('btnSyncLearnerStatus');
        if (!sync) $('#btnSyncLearnerStatus').closest('.lml-head-r').remove();
        applyView(view());
        $('#learnerCardsList .lml-card').addClass('is-in');
        if (window.lpStyleOps) window.lpStyleOps($('#lmlLive'));
        $(document).trigger('lml:refreshed');
    }
    function load(url, push, keepScroll) {
        if (busy) busy.abort();
        root.classList.add('is-loading');
        busy = $.ajax({ url: url, type: 'GET', dataType: 'html', headers: { 'Cache-Control': 'no-cache' } })
            .done(function (html) {
                var doc = new DOMParser().parseFromString(html, 'text/html');
                if (!doc.getElementById('lmlLive')) { location.href = url; return; }
                swap(doc);
                if (push) { try { history.pushState({ lml: 1 }, '', url); } catch (e) {} }
                lastUrl = url;
                if (!keepScroll) {
                    var top = $('#learnerFilterForm').offset().top - 90;
                    if (window.scrollY > top) window.scrollTo({ top: top, behavior: reduce ? 'auto' : 'smooth' });
                }
            })
            .fail(function (x, st) { if (st !== 'abort') location.href = url; })
            .always(function () { busy = null; root.classList.remove('is-loading'); });
        return busy;
    }
    window.lmlRefresh = function () { return load(location.href, false, true); };
    window.lpRefresh = window.lmlRefresh;

    // Submit / change / typing
    $(form).on('submit', function (e) {
        e.preventDefault();
        $(form).find('.dropdown-toggle, [data-bs-toggle="dropdown"]').each(function () { var d = window.bootstrap && bootstrap.Dropdown.getInstance(this); if (d) d.hide(); });
        load(serialize(), true);
    });
    $(form).on('change', '#status', function () { load(serialize(), true); });
    $(form).on('change', '#lmlSort', function () {
        var v = this.value;
        $(form).find('input[name="sort_by"]').val(v ? 'seat_no' : '');
        $(form).find('input[name="sort_order"]').val(v);
        load(serialize(), true);
    });
    var t = null;
    $(form).on('input', '#search_learner_input', function () {
        clearTimeout(t);
        var v = this.value.trim();
        if (v.length === 1) return;          // wait for a useful query
        t = setTimeout(function () { load(serialize(), true, true); }, 380);
    });
    $(form).on('keydown', '#search_learner_input', function (e) { if (e.key === 'Enter') { e.preventDefault(); clearTimeout(t); load(serialize(), true, true); } });
    $(form).on('click', '#clearFilter', function (e) {
        e.preventDefault(); e.stopImmediatePropagation();
        $(form).find('select').each(function () { this.value = ''; });
        $(form).find('#search_learner_input').val('');
        $(form).find('input[name="sort_by"], input[name="sort_order"]').val('');
        load(base, true);
    });
    $(root).on('click', '.lml-empty-clear', function () { $('#clearFilter').trigger('click'); if (!$('#clearFilter').length) load(base, true); });

    // Tiles + pagination links
    $(root).on('click', 'a[data-lml-nav], .lml-pages a', function (e) {
        var href = this.getAttribute('href');
        if (!href || href === '#' || this.classList.contains('is-off')) { e.preventDefault(); return; }
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.button === 1) return;
        e.preventDefault();
        var u = new URL(href, location.href);
        // tiles carry status/payment; keep the search box text the user typed
        if (this.hasAttribute('data-lml-nav')) {
            ['status', 'payment_filter'].forEach(function (k) { var el = form.querySelector('[name="' + k + '"]'); if (el) el.value = u.searchParams.get(k) || ''; });
        }
        load(u.toString(), true);
    });
    window.addEventListener('popstate', function () { if (location.href !== lastUrl) load(location.href, false, true); });

    // Book from the empty state (the booking script binds .noseat_popup once, so use the header button)
    $(root).on('click', '.lml-book-first', function (e) { e.preventDefault(); var b = document.querySelector('.lml-add.noseat_popup'); if (b) b.click(); });

    // "View details" → drawer (falls back to the full profile page when the drawer isn't available)
    $(root).on('click', '[data-lsd-open]', function (e) {
        if (!document.getElementById('seatAllotmentModal2')) return;   // normal link
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.button === 1) return;
        e.preventDefault(); e.stopImmediatePropagation();
        var $b = $('<button type="button" class="second_popup d-none"></button>').attr('data-userid', this.getAttribute('data-lsd-open'));
        $('#seatAllotmentModal2').toggleClass('lsd--member', this.getAttribute('data-lsd-variant') === 'member');
        $('body').append($b); $b.trigger('click'); $b.remove();
    });

    // Menus: design icons for the action items when a menu opens
    $(root).on('show.bs.dropdown', '.lml-more', function () { if (window.lpStyleOps) window.lpStyleOps($(this)); });

    $(function () {
        applyView(view());
        $('#learnerCardsList .lml-card').addClass('is-in');
        if (window.lpStyleOps) window.lpStyleOps($('#lmlLive'));
        try { history.replaceState({ lml: 1 }, '', location.href); } catch (e) {}
    });
})(window.jQuery);
