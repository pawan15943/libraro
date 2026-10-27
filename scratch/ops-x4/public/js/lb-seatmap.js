/* Libraro seat map (learner/seat.blade.php).
   - Each card is a little carousel: one slide per shift, arrows + dots, the footer shows the shift.
   - Shift / floor / status / text filters run in the browser (no reload).
   - window.lsmRefresh() reloads only the seat grids + counts over AJAX (used after a booking/operation).
   The card buttons .first_popup / .second_popup / .second_popup_without_seat are untouched: the card
   click just presses the button of the visible slide, so learner/script.blade.php keeps working. */
(function ($) {
    'use strict';
    if (!$) return;

    var state = { status: 'all', floor: '' };
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    /* ---------- one card ---------- */
    function go(card, idx, instant) {
        var $card = $(card), $slides = $card.find('.lsm-slide');
        var n = $slides.length;
        if (!n) return;
        idx = ((idx % n) + n) % n;
        var $track = $card.find('.lsm-track');
        if (instant) $track.addClass('is-instant');
        $track.css('transform', 'translate3d(' + (-idx * 100) + '%,0,0)');
        if (instant) { $track[0] && $track[0].offsetWidth; $track.removeClass('is-instant'); }
        $slides.removeClass('active-slide').attr('aria-hidden', 'true');
        var $cur = $slides.eq(idx).addClass('active-slide').removeAttr('aria-hidden');
        $card.attr('data-cur', $cur.attr('data-tone') || 'booked').attr('data-cur-bday', $cur.attr('data-bday') || '0');
        $card.find('.current-shift-num').text(idx + 1);
        $card.find('.lsm-d').removeClass('active-dot').eq(idx).addClass('active-dot');
        var name = $cur.attr('data-sname'), time = $cur.attr('data-stime') || '', col = $cur.attr('data-scolor');
        if (name !== undefined) {
            var $s = $card.find('.lsm-foot .lsm-shift');
            $s.attr('title', name + (time ? ' · ' + time : ''));
            $s.find('.lsm-shift-n span').text(name);
            $s.find('.lsm-shift-n i').css('background', col);
            $s.find('small').text(time);
        }
    }
    function curIdx(card) { return $(card).find('.lsm-slide.active-slide').index(); }

    $(document).on('click', '.lsm .shift-next-btn, .lsm .shift-prev-btn', function (e) {
        e.preventDefault(); e.stopPropagation();
        var card = $(this).closest('.seat-card-item')[0];
        go(card, curIdx(card) + ($(this).hasClass('shift-next-btn') ? 1 : -1));
    });
    $(document).on('click', '.lsm .shift-dot', function (e) {
        e.preventDefault(); e.stopPropagation();
        go($(this).closest('.seat-card-item')[0], parseInt($(this).attr('data-shift-idx'), 10) || 0);
    });

    // Swipe on touch screens
    var sx = null, sy = null;
    $(document).on('touchstart', '.lsm .lsm-view', function (e) { var t = e.originalEvent.touches[0]; sx = t.clientX; sy = t.clientY; });
    $(document).on('touchend', '.lsm .lsm-view', function (e) {
        if (sx === null) return;
        var t = e.originalEvent.changedTouches[0], dx = t.clientX - sx, dy = t.clientY - sy; sx = null;
        if (Math.abs(dx) > 36 && Math.abs(dx) > Math.abs(dy) * 1.4) {
            var card = $(this).closest('.seat-card-item')[0];
            if ($(card).find('.lsm-slide').length > 1) { go(card, curIdx(card) + (dx < 0 ? 1 : -1)); $(card).data('lsmSwiped', 1); }
        }
    });

    // Tap anywhere on a card = open the visible slide (Book for a free shift, details for a booked one)
    $(document).on('click', '.lsm .seat-card-item', function (e) {
        if ($(this).data('lsmSwiped')) { $(this).removeData('lsmSwiped'); return; }
        if ($(e.target).closest('button, a, .shift-dot, .view-image').length) return;
        var $btn = $(this).find('.lsm-slide.active-slide').find('.first_popup, .second_popup, .second_popup_without_seat');
        if (!$btn.length) $btn = $(this).find('.first_popup, .second_popup, .second_popup_without_seat');
        if (!$btn.length) return;
        var $proxy = $('#lsmBookProxy');
        if ($btn.hasClass('first_popup') && $proxy.length) {
            // the booking script binds .first_popup once on page load, so use the stable proxy button
            $proxy.removeData().attr({
                'data-id': $btn.attr('data-id') || '', 'data-seat_no': $btn.attr('data-seat_no') || '',
                'data-plan_type_id': $btn.attr('data-plan_type_id') || '', 'data-day_type_id': $btn.attr('data-day_type_id') || ''
            });
            $proxy[0].click();
            return;
        }
        $btn[0].click();
    });
    $(document).on('keydown', '.lsm .seat-card-item', function (e) {
        if ((e.key === 'Enter' || e.key === ' ') && e.target === this) { e.preventDefault(); $(this).trigger('click'); }
        if (e.key === 'ArrowRight' && e.target === this) { go(this, curIdx(this) + 1); }
        if (e.key === 'ArrowLeft' && e.target === this) { go(this, curIdx(this) - 1); }
    });

    /* ---------- filters ---------- */
    function slideMatches($slide, f) {
        var t = $slide.attr('data-tone');
        if (f === 'available') return t === 'free';
        if (f === 'booked') return t && t !== 'free' && t !== 'future';
        if (f === 'due') return t === 'due';
        if (f === 'expiring') return t === 'exp';
        if (f === 'extended') return t === 'ext';
        if (f === 'future') return t === 'future';
        if (f === 'non_expired') return t === 'nonexp';
        if (f === 'bday') return $slide.attr('data-bday') === '1';
        return true;
    }
    function cardMatches(d, f) {
        if (f === 'available') return d.hasAvailable === '1';
        if (f === 'booked') return d.hasBooked === '1';
        if (f === 'due') return d.hasDue === '1';
        if (f === 'expiring') return d.hasExpiring === '1';
        if (f === 'extended') return d.hasExtended === '1';
        if (f === 'future') return d.hasFuture === '1';
        if (f === 'non_expired') return d.hasNonExpired === '1';
        if (f === 'bday') return d.hasBday === '1';
        return true;
    }

    function filter() {
        var q = ($('#seatSearchInput').val() || '').trim().toLowerCase();
        var sq = ($('#seatShiftFilterSelect').val() || '').toLowerCase();
        var f = state.status;

        $('.lsm .seat-card-item').each(function () {
            var d = this.dataset, $card = $(this);
            var seat = String(d.seatNo || '').toLowerCase();
            var ok = true;
            if (q) {
                ok = seat.indexOf(q) > -1 || ('seat ' + seat).indexOf(q) > -1 || ('seat ' + (seat.length === 1 ? '0' + seat : seat)).indexOf(q) > -1 || String(d.studentNames || '').indexOf(q) > -1;
            }
            if (ok && sq) ok = String(d.shifts || '').indexOf(sq) > -1;
            if (ok && f !== 'all') ok = cardMatches(d, f);
            $card.toggleClass('d-none', !ok);
            if (!ok) return;

            // Show the slide that explains why the card matched
            var $slides = $card.find('.lsm-slide'), target = -1;
            if (sq) $slides.each(function (i) { if (target < 0 && String($(this).attr('data-sname') || $(this).attr('data-shift-name') || '').toLowerCase().indexOf(sq) > -1) target = i; });
            if (target < 0 && f !== 'all') $slides.each(function (i) { if (target < 0 && slideMatches($(this), f)) target = i; });
            if (target < 0 && q) $slides.each(function (i) { if (target < 0 && String($(this).attr('data-name') || '').toLowerCase().indexOf(q) > -1) target = i; });
            if (target < 0 && (sq || f !== 'all' || q)) target = 0;
            if (target > -1 && target !== curIdx(this)) go(this, target);
        });

        $('.lsm .floor-collapsible-card').each(function () {
            var $f = $(this), off = !!state.floor && $f.closest('#pills-home').length && $f.attr('data-floor') !== state.floor;
            $f.toggleClass('lsm-floor-off', !!off);
            $f.toggleClass('d-none', !$f.find('.seat-card-item:not(.d-none)').length && !$f.find('.lsm-empty-gen').length);
        });
        var anyLib = $('#pills-home .lsm-floor:not(.d-none):not(.lsm-floor-off)').length;
        $('#pills-home .lsm-nomatch').prop('hidden', !!anyLib || !$('#pills-home .seat-card-item').length);

        var active = !!(q || sq || f !== 'all' || state.floor);
        $('#resetSeatFilterBtn').prop('hidden', !active);
        $('#activeFilterBadge').toggleClass('d-none', !active);
    }

    function counts() {
        var $cards = $('#pills-home .seat-card-item');
        var c = { all: $cards.length, booked: 0, available: 0, due: 0, expiring: 0, extended: 0, future: 0, non_expired: 0, bday: 0 };
        $cards.each(function () {
            var d = this.dataset;
            if (d.hasBooked === '1') c.booked++;
            if (d.hasAvailable === '1') c.available++;
            if (d.hasDue === '1') c.due++;
            if (d.hasExpiring === '1') c.expiring++;
            if (d.hasExtended === '1') c.extended++;
            if (d.hasFuture === '1') c.future++;
            if (d.hasNonExpired === '1') c.non_expired++;
            if (d.hasBday === '1') c.bday++;
        });
        $('#pills-profile .seat-card-item').each(function () { if (this.dataset.hasBday === '1') c.bday++; });
        $.each(c, function (k, v) { $('.lsm-stat [data-count="' + k + '"]').text(v); });
        $('.lsm-stat').each(function () {
            var k = $(this).data('filter');
            var hide = k !== 'booked' && k !== 'available' && k !== 'due' && k !== 'all' && !c[k] && state.status !== k;
            $(this).toggleClass('is-zero', hide);
        });
        $('#pills-home .lsm-floor').each(function () {
            var $f = $(this);
            var free = $f.find('.seat-card-item[data-has-available="1"]').length;
            var due = $f.find('.seat-card-item[data-has-due="1"]').length;
            $f.find('[data-floor-free]').text(free + ' free');
            $f.find('[data-floor-due]').text(due + ' due').prop('hidden', !due);
        });
    }

    function paintShift() {
        var v = $('#seatShiftFilterSelect').val() || '';
        $('#lsmShiftSeg button').each(function () { $(this).toggleClass('is-on', String($(this).data('shift') || '') === v); });
    }

    $(document).on('input', '#seatSearchInput', filter);
    $(document).on('change', '#seatShiftFilterSelect', function () { paintShift(); filter(); });
    $(document).on('click', '#lsmShiftSeg button', function () {
        $('#seatShiftFilterSelect').val(String($(this).data('shift') || '')).trigger('change');
    });
    $(document).on('click', '#lsmFloorSeg button', function () {
        state.floor = String($(this).data('floor') || '');
        $('#lsmFloorSeg button').removeClass('is-on');
        $(this).addClass('is-on');
        filter();
    });
    $(document).on('click', '.lsm .seat-status-filter-btn', function () {
        var k = String($(this).data('filter'));
        state.status = (state.status === k) ? 'all' : k;
        $('.seat-status-filter-btn').removeClass('active');
        $('.seat-status-filter-btn[data-filter="' + state.status + '"]').addClass('active');
        filter();
    });
    $(document).on('click', '#resetSeatFilterBtn', function (e) {
        e.preventDefault();
        $('#seatSearchInput').val('');
        $('#seatShiftFilterSelect').val('');
        state.status = 'all'; state.floor = '';
        $('.seat-status-filter-btn').removeClass('active');
        $('.seat-status-filter-btn[data-filter="all"]').addClass('active');
        $('#lsmFloorSeg button').removeClass('is-on').filter('[data-floor=""]').addClass('is-on');
        paintShift();
        $('.lsm .seat-card-item').each(function () { go(this, 0, true); });
        filter();
    });

    // Floor header chevron state follows Bootstrap collapse
    $(document).on('show.bs.collapse hide.bs.collapse', '.lsm .floor-seat-collapse', function (e) {
        if (e.target !== this) return;
        $(this).closest('.lsm-floor').toggleClass('is-closed', e.type === 'hide');
    });

    /* ---------- photo preview ---------- */
    function openPhoto(url) {
        var $m = $('#imageViewModal');
        if (!$m.length) return;
        $('#modalImage').attr('src', url);
        $m.attr('aria-hidden', 'false').css({ display: 'flex' });
        requestAnimationFrame(function () { $m.addClass('is-open'); $m.css('opacity', 1); });
    }
    function closePhoto() {
        var $m = $('#imageViewModal');
        $m.removeClass('is-open').css('opacity', 0);
        setTimeout(function () { $m.css({ display: 'none' }).attr('aria-hidden', 'true'); $('#modalImage').attr('src', ''); }, reduce ? 0 : 180);
    }
    $(document).on('click', '.lsm a.view-image', function (e) {
        var u = $(this).attr('href');
        if (!u || !/\.(jpe?g|png|webp|gif)(\?|#|$)/i.test(u)) return;
        e.preventDefault(); e.stopPropagation(); openPhoto(u);
    });
    $(document).on('click', '#imageViewModal', function (e) {
        if ($(e.target).is('#imageViewModal, .image-modal-content, .close-modal')) closePhoto();
    });
    $(document).on('keydown', function (e) { if (e.key === 'Escape' && $('#imageViewModal').css('display') === 'flex') closePhoto(); });

    /* ---------- details modal: actions rail scroll ---------- */
    $(document).on('wheel', '#modalOpContainer', function (e) {
        var dy = e.originalEvent.deltaY;
        if (dy && this.scrollWidth > this.clientWidth) { e.preventDefault(); this.scrollLeft += dy; }
    });

    /* ---------- AJAX refresh of the grids ---------- */
    var refreshing = null;
    window.lsmRefresh = function () {
        if (refreshing) return refreshing;
        var $root = $('#lsmRoot').addClass('is-refreshing');
        var slides = {};
        $('#pills-home .seat-card-item').each(function () { slides[this.id] = curIdx(this); });
        refreshing = $.ajax({ url: window.location.href, type: 'GET', dataType: 'html', headers: { 'X-Lsm-Refresh': '1', 'Cache-Control': 'no-cache' } })
            .done(function (html) {
                var doc = new DOMParser().parseFromString(html, 'text/html');
                ['#pills-home', '#pills-profile'].forEach(function (sel) {
                    var fresh = doc.querySelector(sel), cur = document.querySelector(sel);
                    if (fresh && cur) cur.innerHTML = fresh.innerHTML;
                });
                ['[data-lsm-total]', '[data-lsm-gen]'].forEach(function (sel) {
                    var fresh = doc.querySelector(sel), cur = document.querySelector(sel);
                    if (fresh && cur) cur.textContent = fresh.textContent;
                });
                $('#pills-home .seat-card-item').each(function () { if (slides[this.id] > 0) go(this, slides[this.id], true); });
                counts(); filter();
                $(document).trigger('lsm:refreshed');
            })
            .fail(function () { window.location.reload(); })
            .always(function () { $root.removeClass('is-refreshing'); refreshing = null; });
        return refreshing;
    };
    window.lpRefresh = window.lsmRefresh;

    $(function () {
        $('.lsm .seat-card-item').each(function () { if ($(this).find('.lsm-slide').length) go(this, 0, true); });
        counts();
        paintShift();
        filter();
    });
})(window.jQuery);
