/* Libraro — new booking popup (#seatAllotmentModal, learner/popup.blade.php).
   Step 1: seat, shift, plan, dates, locker/discount and the price.  Step 2: learner, payment and optional details.
   The original selects/inputs stay in the form and keep every existing handler in learner/script.blade.php
   (seat rules, prices, validation, submit). This file only:
     - runs the 2 steps,
     - draws the seat / shift / plan / payment-mode / ID-type choices that set those same selects,
     - mirrors totals, "valid till" and the add-ons summary,
     - animates the accordions. */
(function ($) {
    'use strict';
    if (!$) return;
    var $m = $('#seatAllotmentModal.lbk');
    if (!$m.length) return;
    var form = document.getElementById('seatAllotmentForm');
    var SPRITE = (function () { var u = document.querySelector('use[href*="lb-icons.svg"]'); return u ? u.getAttribute('href').split('#')[0] : ''; })();
    function ic(n) { return '<svg class="lb-i" aria-hidden="true"><use href="' + SPRITE + '#i-' + n + '"></use></svg>'; }
    function esc(v) { return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
    function num(v) { var n = parseFloat(String(v == null ? '' : v).replace(/[^0-9.\-]/g, '')); return isNaN(n) ? 0 : n; }
    function money(n) { return '₹' + (Math.round(n * 100) / 100).toLocaleString('en-IN', { maximumFractionDigits: 2 }); }
    var SHIFTS = {}; try { SHIFTS = JSON.parse($m.attr('data-shifts') || '{}') || {}; } catch (e) {}
    var xPicked = [], xPrice = {}; // extra shifts picked on the shift cards (see "more shifts on the same seat")
    var generalOk = $m.attr('data-general-ok') === '1';

    /* ---------------- steps ---------------- */
    function go(step) {
        $m.attr('data-lbk-step', step);
        var body = $m.find('.modal-body')[0]; if (body) body.scrollTop = 0;
        if (step === 1) { renderShifts(); renderPlans(); }
        else { renderModes(); renderIdChips(); paintRecap(); }
        paintTotals();
    }
    function showErr($e, errs) {
        if (errs.length) { $e.text(errs.join(' ')).prop('hidden', false); return false; }
        $e.prop('hidden', true).text('');
        return true;
    }
    function step1Ok() {
        var errs = [];
        $('#lbkSeatPick, #lbkShiftBox, #lbkPlans').removeClass('is-invalid');
        $('#plan_start_date').removeClass('is-invalid');
        if ($('#general_seat').val() === 'no' && !($('#seat_id').val() || $('#seat_no').val())) { errs.push('Choose a seat.'); $('#lbkSeatPick').addClass('is-invalid'); }
        if (!$('#plan_type_id').val()) { errs.push('Select a shift.'); $('#lbkShiftBox').addClass('is-invalid'); }
        var timed = timedShift();
        $('#lbkTimingBox').removeClass('is-invalid');
        if (timed && (!$('#shift_start_time').val() || !$('#shift_end_time').val())) { errs.push('Enter the shift timing.'); $('#lbkTimingBox').addClass('is-invalid'); }
        if (timed && timed.mode === 'free' && !(num($('#lbkFreePrice').val()) > 0)) { errs.push('Enter the price.'); $('#lbkTimingBox').addClass('is-invalid'); }
        extrasOk(errs);
        if (!$('#plan_id3').val()) { errs.push('Pick a plan.'); $('#lbkPlans').addClass('is-invalid'); }
        if (!$('#plan_start_date').val()) { errs.push('Choose the start date.'); $('#plan_start_date').addClass('is-invalid'); }
        return showErr($('#lbkStep1Err'), errs);
    }
    function step2Ok() {
        var errs = [];
        var name = ($('#name').val() || '').trim(), mobile = ($('#mobile').val() || '').trim();
        $('#name, #mobile').removeClass('is-invalid');
        $('#lbkModes').removeClass('is-invalid');
        if (!name) { errs.push('Enter the full name.'); $('#name').addClass('is-invalid'); }
        if (!/^\d{10}$/.test(mobile)) { errs.push('Enter a valid 10-digit mobile number.'); $('#mobile').addClass('is-invalid'); }
        if (!$(form).find('select[name="payment_mode"]').val()) { errs.push('Choose how they are paying.'); $('#lbkModes').addClass('is-invalid'); }
        return showErr($('#lbkStep2Err'), errs);
    }
    $('#lbkNext').on('click', function () { if (step1Ok()) go(2); });
    $('#lbkBack').on('click', function () { go(1); });
    $m.on('click', '.lbk-progress i:first-child, .lbk-steps span:first-child, [data-lbk-edit]', function () { go(1); });
    $m.on('click', '.lbk-progress i:last-child, .lbk-steps span:last-child', function () { if ($m.attr('data-lbk-step') === '1' && step1Ok()) go(2); });
    document.addEventListener('submit', function (e) {
        if (e.target !== form) return;
        if ($m.attr('data-lbk-step') === '1') {
            e.preventDefault(); e.stopImmediatePropagation();
            if (step1Ok()) go(2);
        } else if (!step2Ok()) {
            e.preventDefault(); e.stopImmediatePropagation();
            var first = $m.find('.lbk-step[data-step="2"] .is-invalid')[0];
            if (first && first.scrollIntoView) first.scrollIntoView({ block: 'center', behavior: 'smooth' });
        }
    }, true);
    $('#bookSeatSubmitBtn').on('click', function () {
        setTimeout(function () { if ($m.find('.lbk-step[data-step="1"] .is-invalid').length) go(1); }, 30);
    });

    /* ---------------- rails (arrows + wheel) ---------------- */
    $m.on('click', '.lbk-arrows button', function () {
        var $a = $(this).closest('.lbk-arrows'), dir = +$(this).attr('data-dir') || 1;
        var car = $m.find($a.attr('data-lbk-car'))[0];
        if (car) { car.scrollBy({ left: dir * car.clientWidth, behavior: 'smooth' }); return; }
        var rail = $m.find($a.attr('data-lbk-rail'))[0];
        if (rail) rail.scrollBy({ left: dir * Math.max(160, rail.clientWidth * .6), behavior: 'smooth' });
    });
    $m.on('wheel', '.lbk-rail', function (e) {
        var dy = e.originalEvent.deltaY;
        if (dy && Math.abs(dy) > Math.abs(e.originalEvent.deltaX) && this.scrollWidth > this.clientWidth) { e.preventDefault(); this.scrollLeft += dy; }
    });
    function centerOn(rail) {
        var on = $(rail).find('.is-on')[0];
        if (on && rail) rail.scrollTo({ left: Math.max(0, on.offsetLeft - 18), behavior: 'auto' });
    }

    /* ---------------- seats ---------------- */
    var seatList = null, seatReq = null, pendingSeat = null;
    function fixedMode() { return $('#general_seat').closest('.col-lg-6').css('display') === 'none'; }
    function curSeat() { return String($('#seat_id').val() || $('#seat_no').val() || ''); }
    function loadSeats() {
        if (seatReq) return seatReq;
        seatReq = $.ajax({ url: $m.attr('data-seats-url'), type: 'GET', data: { with_templates: 1 }, dataType: 'json', global: false })
            .done(function (r) { seatList = Array.isArray(r) ? r : []; renderSeats(); })
            .fail(function () { seatList = []; renderSeats(); })
            .always(function () { seatReq = null; });
        return seatReq;
    }
    function seatLabel(s) {
        var main = String(s.main), disp = String(s.display == null ? main : s.display);
        var code = '';
        if (disp !== main) { var m = disp.match(/\(([^)]+)\)/); code = m ? m[1] : disp.replace(main, '').replace(/[-–\s]+$/, '').trim(); }
        if (/^seat\s*no\.?$/i.test(code)) code = '';
        return { main: main, name: 'Seat ' + (disp !== main && /^[A-Z]{1,3}\d*[-–]?\d+/.test(disp) ? disp.split(' ')[0] : main), code: code };
    }
    function renderSeats() {
        var $rail = $('#lbkSeats');
        if (fixedMode()) return;
        var q = ($('#lbkSeatQ').val() || '').trim().toLowerCase().replace(/^seat\s*/, '');
        $('#lbkSeatQx').prop('hidden', !q);
        var general = $('#general_seat').val() === 'yes';
        var sel = curSeat();
        var html = '';
        if (generalOk && !q) {
            html += '<button type="button" class="lbk-seat is-general' + (general ? ' is-on' : '') + '" data-seat="" role="option" aria-selected="' + general + '">' + ic('users-round') + '<b>General</b><small>(Any)</small></button>';
        }
        var shown = 0;
        if (seatList === null) {
            html += '<span class="lbk-rail-load"><i></i><i></i><i></i><i></i></span>';
        } else {
            seatList.forEach(function (s) {
                var L = seatLabel(s);
                var hay = (L.main + ' ' + L.name + ' ' + L.code + ' ' + (s.display || '')).toLowerCase();
                if (q && hay.indexOf(q) === -1) return;
                shown++;
                var on = !general && sel === L.main;
                html += '<button type="button" class="lbk-seat' + (on ? ' is-on' : '') + '" data-seat="' + esc(L.main) + '" role="option" aria-selected="' + on + '">' + ic('armchair') + '<b>' + esc(L.name) + '</b>' + (L.code ? '<small>(' + esc(L.code) + ')</small>' : '') + '</button>';
            });
        }
        var keep = $rail[0] ? $rail[0].scrollLeft : 0, first = !$rail.children('.lbk-seat').length;
        $rail.html(html);
        $('#lbkSeatEmpty').prop('hidden', seatList === null || shown > 0 || (!q && generalOk));
        var free = seatList ? seatList.length : 0;
        $('#lbkSeatHint').text(general ? 'General · any free seat' : (sel ? 'Seat ' + sel + ' selected' : free + ' free seats'));
        if (first || q) centerOn($rail[0]); else if ($rail[0]) $rail[0].scrollLeft = keep;
    }
    function applySeat(v) {
        v = String(v);
        if (window.seatChoices) { try { window.seatChoices.setChoiceByValue(v); } catch (e) {} }
        if (!$('#seat_id option[value="' + v.replace(/"/g, '') + '"]').length) $('#seat_id').append($('<option>').val(v).text(v));
        $('#seat_id').val(v).trigger('change');
        renderSeats();
    }
    function holdScroll() {
        var body = $m.find('.modal-body')[0], rail = document.getElementById('lbkSeats');
        if (!body) return;
        var top = body.scrollTop, left = rail ? rail.scrollLeft : 0, until = Date.now() + 1200;
        (function pin() {
            if (body.scrollTop !== top) body.scrollTop = top;
            if (rail && rail.scrollLeft !== left && !$('#lbkSeatQ').val()) rail.scrollLeft = left;
            if (Date.now() < until) requestAnimationFrame(pin);
        })();
    }
    $m.on('click', '.lbk-seat', function () {
        holdScroll();
        if (document.activeElement && document.activeElement !== this) this.focus({ preventScroll: true });
        var v = String($(this).attr('data-seat') || '');
        $('#lbkSeatPick').removeClass('is-invalid');
        if (!v) {
            if ($('#general_seat').val() !== 'yes') $('#general_seat').val('yes').trigger('change');
            pendingSeat = null;
            renderSeats();
            return;
        }
        if ($('#general_seat').val() !== 'no') {
            pendingSeat = v;
            $('#general_seat').val('no').trigger('change');   // script refreshes the seat list, then we apply
            setTimeout(function () { if (pendingSeat === v) { pendingSeat = null; applySeat(v); } }, 900);
        } else {
            applySeat(v);
        }
    });
    $(document).ajaxComplete(function (e, xhr, settings) {
        if (pendingSeat && String(settings.url || '').indexOf($m.attr('data-seats-url')) === 0) {
            var v = pendingSeat; pendingSeat = null;
            setTimeout(function () { applySeat(v); }, 0);
        }
    });
    $('#lbkSeatQ').on('input', renderSeats);
    $('#lbkSeatQx').on('click', function () { $('#lbkSeatQ').val('').trigger('input').focus(); });
    $(document).on('change', '#general_seat, #seat_id', function () { if ($m.hasClass('show')) setTimeout(renderSeats, 0); });

    function paintSeatMode() {
        var fixed = fixedMode();
        $('#lbkSeatPick').prop('hidden', fixed);
        $m.find('#lbkSeatSec .lbk-arrows').prop('hidden', fixed);
        $('#lbkFixedSeat').prop('hidden', !fixed);
        if (fixed) {
            var t = String($('#seat_no_head').text() || '').replace(/^Book Seat No\.?:?\s*/i, '');
            $('#lbkFixedName').text('Seat ' + (t || $('#seat_no').val()));
            $('#lbkSeatHint').text('Seat ' + (t || $('#seat_no').val()) + ' selected');
        } else {
            renderSeats(); loadSeats();
        }
    }

    /* ---------------- plans + shifts ---------------- */
    function renderPlans() {
        var val = String($('#plan_id3').val() || ''), html = '';
        $('#plan_id3 option').each(function () {
            var v = String(this.value || ''); if (!v) return;
            var on = v === val;
            html += '<button type="button" class="lbk-chip' + (on ? ' is-on' : '') + '" data-v="' + esc(v) + '" role="option" aria-selected="' + on + '">' + (on ? ic('check') : '') + esc($(this).text().trim()) + '</button>';
        });
        $('#lbkPlans').html(html || '<span class="lbk-note">No plans set up yet</span>');
        $('#lbkPlanHint').text(val && num($('#plan_price_id').val()) ? money(num($('#plan_price_id').val())) : '');
    }
    $m.on('click', '.lbk-chip', function () {
        $('#lbkPlans').removeClass('is-invalid');
        $('#plan_id3').val($(this).attr('data-v')).trigger('change');
        renderPlans();
    });

    var SHIFT_IC = { 1: 'sun', 2: 'sunrise', 3: 'sunset', 8: 'clock-3', 9: 'moon', 10: 'lock', 11: 'crown' };
    function shiftCount() { return $('#lbkShifts .lbk-shift').length; }
    function paintDots() {
        var car = document.getElementById('lbkShifts'), n = shiftCount();
        if (!car || n < 2) { $('#lbkShiftDots').empty(); $m.find('#lbkShiftBox .lbk-arrows').prop('hidden', true); return; }
        $m.find('#lbkShiftBox .lbk-arrows').prop('hidden', false);
        var idx = Math.round(car.scrollLeft / Math.max(1, car.clientWidth));
        var h = '';
        for (var i = 0; i < n; i++) h += '<i class="' + (i === idx ? 'is-on' : '') + '" data-i="' + i + '"></i>';
        $('#lbkShiftDots').html(h);
        $m.find('#lbkShiftBox .lbk-arrows button[data-dir="-1"]').prop('disabled', idx <= 0);
        $m.find('#lbkShiftBox .lbk-arrows button[data-dir="1"]').prop('disabled', idx >= n - 1);
    }
    function renderShifts() {
        xPrune();
        var val = String($('#plan_type_id').val() || ''), html = '', n = 0, placeholder = '', onIdx = 0;
        var isGen = $('#general_seat').val() === 'yes' && !fixedMode();
        var price = num($('#plan_price_id').attr('data-base') !== undefined ? $('#plan_price_id').attr('data-base') : $('#plan_price_id').val());
        var multi = multiOk();
        $('#plan_type_id option').each(function () {
            var v = String(this.value || ''), t = $(this).text().trim();
            if (!v) { placeholder = t; return; }
            var on = v === val, extra = xPicked.indexOf(v) >= 0, meta = SHIFTS[v] || {};
            var mode = String($(this).attr('data-mode') || 'fixed'), hrs = num($(this).attr('data-hours'));
            var timeTxt = mode === 'duration' ? hrs + ' hr · time chosen below'
                : mode === 'free' ? 'Time & price chosen below'
                : mode === 'flexible' ? hrs + ' hr · any time' + (meta.time ? ' (' + meta.time + ')' : '')
                : (meta.time || 'Timing as per library');
            // With a fixed main shift on a numbered seat, more non-overlapping fixed shifts can be added
            var blocker = multi && !on && !extra ? (mode === 'fixed' ? blockerOf(v) : null) : null;
            var off = multi && !on && !extra && (!!blocker || (mode !== 'fixed' && xPicked.length > 0));
            var btn = on ? 'Selected' : extra ? 'Added' : off ? (blocker ? 'Overlaps' : 'Single only') : (multi && mode === 'fixed' ? 'Add' : 'Select');
            var okTxt = off ? (blocker ? 'Overlaps ' + optName(blocker) : 'Book on its own') : (isGen ? 'Available' : 'Free on this seat');
            var cost = on ? price : extra ? num(xPrice[v]) : 0;
            // Fixed shifts on a numbered seat get a checkbox: several can be booked together
            var cb = numberedSeat() && mode === 'fixed';
            if ((on || extra) && !onIdx && !on) onIdx = n;
            if (on) onIdx = n;
            n++;
            html += '<div class="lbk-shift' + (on ? ' is-on' : '') + (extra ? ' is-on is-extra' : '') + (off ? ' is-off' : '') + '" data-v="' + esc(v) + '" role="option" aria-selected="' + (on || extra) + '"' + (off ? ' aria-disabled="true"' : '') + '>' +
                (cb ? '<span class="lbk-shift-cb' + (on || extra ? ' is-on' : '') + '" aria-hidden="true">' + (on || extra ? ic('check') : '') + '</span>' : '') +
                '<span class="lbk-shift-ic">' + ic(SHIFT_IC[meta.day] || 'clock') + '</span>' +
                '<span class="lbk-shift-t"><b>' + esc(t) + (on ? '<em>Selected</em>' : extra ? '<em>Added</em>' : '') + '</b>' +
                '<small>' + esc(timeTxt) + (cost ? ' · <strong>' + money(cost) + '</strong>' : '') + '</small>' +
                '<small class="lbk-shift-ok">' + ic(off ? 'circle-x' : 'circle-check') + esc(okTxt) + '</small></span>' +
                '<button type="button" class="lbk-shift-btn"' + (off ? ' disabled' : '') + '>' + btn + '</button></div>';
        });
        if (!n) html = '<span class="lbk-note lbk-shift-empty">' + esc(/loading/i.test(placeholder) ? 'Loading shifts…' : (placeholder && !/choose/i.test(placeholder) ? placeholder : ($('#general_seat').val() === 'no' && !curSeat() ? 'Choose a seat first' : 'No shifts available'))) + '</span>';
        var car = document.getElementById('lbkShifts');
        $('#lbkShifts').html(html);
        var seatTxt = fixedMode() ? ($('#lbkFixedName').text() || '') : (curSeat() && $('#general_seat').val() === 'no' ? 'Seat ' + curSeat() : 'General seat');
        var picked = (val ? 1 : 0) + xPicked.length;
        $('#lbkShiftHead').text((seatTxt ? seatTxt + ' · ' : '') + (picked > 1 ? picked + ' shifts selected' : (n ? n + ' ' + (n === 1 ? 'shift' : 'shifts') : 'Shifts')));
        var canMulti = numberedSeat() && $('#plan_type_id option').filter(function () { return this.value && optMode(this.value) === 'fixed'; }).length > 1;
        $('#lbkShiftNote').text(canMulti ? 'Tick more than one shift to book them together on this seat. Overlapping shifts are disabled.' : '').prop('hidden', !canMulti);
        paintPicks();
        if (car) {
            car.scrollLeft = onIdx * car.clientWidth;
            requestAnimationFrame(function () { car.scrollLeft = onIdx * car.clientWidth; paintDots(); });
        }
        paintDots();
    }
    // Selected shifts as chips under the cards (main first); × removes one
    function paintPicks() {
        var main = String($('#plan_type_id').val() || ''), all = main ? [main].concat(xPicked) : [];
        var show = numberedSeat() && all.length > 0, html = '';
        all.forEach(function (v, i) {
            var cost = i === 0 ? num($('#plan_price_id').attr('data-base')) : num(xPrice[v]);
            html += '<span class="lbk-pick">' + ic('check') +
                '<span><b>' + esc(optName(v)) + '</b><small>' + esc([(SHIFTS[v] || {}).time, cost ? money(cost) : ''].filter(Boolean).join(' · ')) + '</small></span>' +
                '<button type="button" class="lbk-pick-x" data-v="' + esc(v) + '" aria-label="Remove ' + esc(optName(v)) + '">' + ic('x') + '</button></span>';
        });
        $('#lbkPicks').html(show ? html : '').prop('hidden', !show);
    }
    $m.on('click', '.lbk-pick-x', function () { toggleShift(String($(this).attr('data-v') || '')); });
    $m.on('click', '.lbk-shift', function () {
        if ($(this).hasClass('is-off')) return;
        holdScroll();
        toggleShift(String($(this).attr('data-v') || ''));
    });
    // Select, add or remove a shift: the first picked is the main shift, the rest are extra shifts
    function toggleShift(v) {
        var main = String($('#plan_type_id').val() || '');
        $('#lbkShiftBox').removeClass('is-invalid');
        if (v === main) {
            // Unselect the main shift on a numbered seat; the next added shift takes its place
            if (!numberedSeat()) return;
            var next = xPicked.shift() || '';
            if (next) delete xPrice[next];
            $('#plan_type_id').val(next).trigger('change');
            xWrite();
        } else if (xPicked.indexOf(v) >= 0) {
            xPicked.splice(xPicked.indexOf(v), 1);
            delete xPrice[v];
            xWrite();
        } else if (main && multiOk() && optMode(v) === 'fixed') {
            xPicked.push(v);
            xLoadPrice(v);
        } else {
            xClear();
            $('#plan_type_id').val(v).trigger('change');
        }
        renderShifts();
    }
    $m.on('click', '#lbkShiftDots i', function () {
        var car = document.getElementById('lbkShifts');
        if (car) car.scrollTo({ left: (+$(this).attr('data-i') || 0) * car.clientWidth, behavior: 'smooth' });
    });
    var dotT = null;
    $('#lbkShifts').on('scroll', function () { clearTimeout(dotT); dotT = setTimeout(paintDots, 60); });
    $(document).on('change', '#plan_id3', function () { if ($m.hasClass('show')) renderPlans(); });
    $(document).on('change', '#plan_type_id', function () { if ($m.hasClass('show')) renderShifts(); });
    var shiftSel = document.getElementById('plan_type_id');
    if (shiftSel && window.MutationObserver) new MutationObserver(function () { if ($m.hasClass('show')) renderShifts(); }).observe(shiftSel, { childList: true });

    /* ---------------- timing for "fixed hours" / "free" shifts ---------------- */
    // The template is booked as-is; the server turns it into a timing row (docs/ADVANCED_SHIFTS.md).
    var $tBox = $('#lbkTimingBox'), timingReq = null;
    function pad(n) { return (n < 10 ? '0' : '') + n; }
    function toMin(t) { var x = /^(\d{1,2}):(\d{2})/.exec(t || ''); return x ? (+x[1]) * 60 + (+x[2]) : null; }
    function fromMin(m) { m = ((m % 1440) + 1440) % 1440; return pad(Math.floor(m / 60)) + ':' + pad(m % 60); }
    function tLabel(t) {
        var m = toMin(t); if (m === null) return '';
        var h = Math.floor(m / 60) % 24, mm = m % 60, h12 = h % 12 || 12;
        return h12 + (mm ? ':' + pad(mm) : '') + ' ' + (h < 12 ? 'AM' : 'PM');
    }
    function timedShift() {
        var o = $('#plan_type_id option:selected'), mode = String(o.attr('data-mode') || 'fixed');
        return (mode === 'duration' || mode === 'free') ? { mode: mode, hours: num(o.attr('data-hours')) } : null;
    }
    function syncEnd() {
        var s = timedShift(), start = toMin($('#shift_start_time').val());
        if (s && s.mode === 'duration') $('#shift_end_time').val(start === null ? '' : fromMin(start + Math.round(s.hours * 60)));
    }
    // #plan_price_id is the booking total the existing totals/paid/discount code reads:
    // the main shift's price (data-base, or the typed price of a free shift) plus the extra shifts.
    function basePrice() {
        var s = timedShift(), typed = String($('#lbkFreePrice').val() || '').trim();
        if (s && s.mode === 'free' && typed !== '') return num(typed);
        return num($('#plan_price_id').attr('data-base'));
    }
    function syncPrice() {
        var s = timedShift();
        if (s && s.mode === 'free' && String($('#lbkFreePrice').val() || '').trim() === '') $('#lbkFreePrice').val($('#plan_price_id').attr('data-base') || '');
        if (!$('#plan_type_id').val()) return;
        var extra = extrasTotal(), base = basePrice();
        if (!extra && !(s && s.mode === 'free') && $('#plan_price_id').attr('data-base') === undefined) return;
        var next = String(Math.round((base + extra) * 100) / 100);
        if ($('#plan_price_id').val() !== next) {
            $('#plan_price_id').val(next);
            if (typeof window.autoCalculatePaidAmount === 'function') window.autoCalculatePaidAmount();
        }
        paintTotals();
        paintPicks();
    }
    window.lbkPriceHook = syncPrice;
    function loadWindows() {
        var s = timedShift();
        if (!s) return;
        if (timingReq) timingReq.abort();
        $('#lbkTimingNote').text('Checking free time on this seat…');
        $('#lbkTimingGaps').empty();
        timingReq = $.ajax({
            url: $tBox.attr('data-url'), type: 'GET', dataType: 'json', global: false,
            data: { plan_type_id: $('#plan_type_id').val(), seat_no: curSeat(), plan_id: $('#plan_id3').val(), plan_start_date: $('#plan_start_date').val() }
        }).done(function (r) {
            if (!r || !r.success) { $('#lbkTimingNote').text(''); return; }
            var note = 'Can be booked between ' + tLabel(r.window.start) + ' – ' + tLabel(r.window.end) + '.';
            if (r.booked && r.booked.length) note += ' Already booked on this seat: ' + r.booked.map(function (b) { return tLabel(b.start) + ' – ' + tLabel(b.end); }).join(', ') + '.';
            $('#lbkTimingNote').text(note);
            var need = s.mode === 'duration' ? Math.round(s.hours * 60) : 30, html = '';
            (r.gaps || []).forEach(function (g) {
                if (g.minutes < need) return;
                html += '<button type="button" class="lbk-gap" data-start="' + esc(g.start) + '" data-end="' + esc(g.end) + '">' + esc(tLabel(g.start) + ' – ' + tLabel(g.end)) + '</button>';
            });
            $('#lbkTimingGaps').html(html ? '<span class="lbk-gaps-k">Free on this seat</span>' + html : '<span class="lbk-gaps-k">No free slot long enough on this seat for these dates.</span>');
        }).fail(function (x) { if (x.statusText !== 'abort') $('#lbkTimingNote').text(''); })
          .always(function () { timingReq = null; });
    }
    function paintTiming() {
        var s = timedShift();
        $tBox.prop('hidden', !s);
        $('#shift_start_time, #shift_end_time').prop('disabled', !s);
        if (!s) { $('#shift_start_time, #shift_end_time, #lbkFreePrice').val(''); $('#lbkTimingGaps').empty(); return; }
        $tBox.find('.lbk-free-only').prop('hidden', s.mode !== 'free');
        $('#shift_end_time').prop('readonly', s.mode === 'duration');
        $('#lbkTimingHead').text(s.mode === 'duration' ? 'Shift timing · ' + s.hours + ' hr' : 'Shift timing · your own time & price');
        syncEnd();
        setTimeout(syncPrice, 0);
        loadWindows();
    }
    $(document).on('change', '#plan_type_id', function () { $('#lbkFreePrice').val(''); paintTiming(); });
    $(document).on('change', '#plan_id3, #plan_start_date', function () { if (timedShift()) { $('#lbkFreePrice').val(''); setTimeout(syncPrice, 0); loadWindows(); } });
    $(document).on('input change', '#shift_start_time', function () { $('#lbkTimingBox').removeClass('is-invalid'); syncEnd(); });
    $(document).on('input', '#lbkFreePrice', function () { this.value = this.value.replace(/[^0-9.]/g, ''); syncPrice(); });
    $m.on('click', '.lbk-gap', function () { $('#shift_start_time').val($(this).attr('data-start')).trigger('change'); });
    if (form) form.addEventListener('submit', syncPrice, true);
    $m.on('hidden.bs.modal', function () { $tBox.prop('hidden', true); $('#shift_start_time, #shift_end_time').prop('disabled', true).val(''); $('#lbkFreePrice').val(''); $('#plan_price_id').removeAttr('data-base'); });

    /* ---------------- more shifts on the same seat ---------------- */
    // On a numbered seat the owner can pick several fixed-time shifts on the shift cards. The first
    // is the main shift (#plan_type_id); the others post as extra_shifts[k][seat_no|plan_type_id|price]
    // and are booked under the same booking and transaction (docs/ADVANCED_SHIFTS.md). A shift whose
    // time overlaps a picked one is disabled. Other seats and timed shifts stay single for now.
    var $xList = $('#lbkExtras');
    function numberedSeat() { return fixedMode() || ($('#general_seat').val() === 'no' && !!curSeat()); }
    function optMode(v) { return String($('#plan_type_id option[value="' + v + '"]').attr('data-mode') || 'fixed'); }
    function optName(v) { return $('#plan_type_id option[value="' + v + '"]').text().trim(); }
    function multiOk() { var main = String($('#plan_type_id').val() || ''); return numberedSeat() && !!main && optMode(main) === 'fixed'; }
    function span(v) {
        var meta = SHIFTS[v] || {}, s = toMin(meta.s), e = toMin(meta.e);
        if (s === null || e === null) return null;
        return [s, e <= s ? e + 1440 : e];
    }
    function clash(a, b) {
        var x = span(a), y = span(b);
        if (!x || !y) return true;
        for (var o = -1440; o <= 1440; o += 1440) if (x[0] < y[1] + o && x[1] > y[0] + o) return true;
        return false;
    }
    function blockerOf(v) {
        var main = String($('#plan_type_id').val() || ''), all = main ? [main].concat(xPicked) : xPicked;
        for (var i = 0; i < all.length; i++) if (all[i] !== v && clash(all[i], v)) return all[i];
        return null;
    }
    function extrasTotal() { var t = 0; xPicked.forEach(function (v) { t += num(xPrice[v]); }); return t; }
    // Price breakdown with several shifts: the main shift and each added one on its own line,
    // named alike, with the seat, time and plan (small) or the seat (inline)
    function shiftLines(cls, small, mainCost) {
        var main = String($('#plan_type_id').val() || ''), seat = curSeat() ? 'Seat ' + curSeat() : '';
        var plan = $('#plan_id3').val() ? $('#plan_id3 option:selected').text().trim() : '';
        return [main].concat(xPicked).map(function (v, i) {
            var cost = i === 0 ? mainCost : num(xPrice[v]);
            var sub = small ? '<small>' + esc([seat, (SHIFTS[v] || {}).time, plan].filter(Boolean).join(' · ')) + '</small>' : (seat ? ' · ' + esc(seat) : '');
            return '<div class="' + cls + '"><span>' + esc(optName(v)) + sub + '</span><b>' + money(cost) + '</b></div>';
        }).join('');
    }
    function xWrite() {
        var seat = curSeat(), h = '';
        xPicked.forEach(function (v, k) {
            h += '<input type="hidden" name="extra_shifts[' + k + '][seat_no]" value="' + esc(seat) + '">' +
                 '<input type="hidden" name="extra_shifts[' + k + '][plan_type_id]" value="' + esc(v) + '">' +
                 '<input type="hidden" name="extra_shifts[' + k + '][price]" value="' + esc(xPrice[v] == null ? '' : xPrice[v]) + '">';
        });
        if ($xList) $xList.html(h);
        syncPrice();
    }
    function xLoadPrice(v) {
        var plan = $('#plan_id3').val();
        if (!plan) { xPrice[v] = ''; xWrite(); return; }
        $.ajax({ url: $xList.attr('data-price-url'), type: 'GET', dataType: 'json', global: false,
            data: { plan_type_id: v, plan_id: plan, plan_start_date: $('#plan_start_date').val() } })
            .done(function (p) { xPrice[v] = p === null ? '' : p; })
            .fail(function () { xPrice[v] = ''; })
            .always(function () { if (xPicked.indexOf(v) >= 0) { xWrite(); renderShifts(); } });
    }
    function xClear() { xPicked = []; xPrice = {}; if ($xList) $xList.empty(); syncPrice(); }
    // Keep only extra shifts still offered on this seat that fit with the main shift and each other
    function xPrune() {
        if (!xPicked.length) return;
        if (!multiOk()) { xClear(); return; }
        var main = String($('#plan_type_id').val() || ''), kept = [];
        xPicked.forEach(function (v) {
            if (v === main || !$('#plan_type_id option[value="' + v + '"]').length || optMode(v) !== 'fixed' || clash(main, v)) return;
            if (kept.some(function (k) { return clash(k, v); })) return;
            kept.push(v);
        });
        if (kept.length !== xPicked.length) { xPicked = kept; xWrite(); }
    }
    $(document).on('change', '#plan_id3, #plan_start_date', function () { xPicked.forEach(xLoadPrice); });
    $(document).on('change', '#general_seat, #seat_id', function () { if (xPicked.length) xClear(); });
    function extrasOk(errs) {
        if ($('#plan_id3').val() && xPicked.some(function (v) { return xPrice[v] === '' || xPrice[v] == null; })) errs.push('Price is missing for an added shift.');
    }
    $m.on('hidden.bs.modal', xClear);

    /* ---------------- payment mode + ID type ---------------- */
    var MODE = { 1: ['smartphone', 'Online', 'UPI, card or netbanking'], 2: ['banknote', 'Cash / offline', 'Paid at the desk'], 3: ['clock', 'Pay later', 'Collect by the due date'] };
    function modeSel() { return $(form).find('select[name="payment_mode"]'); }
    function renderModes() {
        var val = String(modeSel().val() || ''), html = '';
        modeSel().find('option').each(function () {
            var v = String(this.value || ''); if (!v) return;
            var on = v === val, d = MODE[v] || ['wallet', $(this).text().trim(), ''];
            html += '<button type="button" class="lbk-mode' + (on ? ' is-on' : '') + '" data-v="' + esc(v) + '" role="radio" aria-checked="' + on + '">' +
                '<i class="lbk-radio"></i>' + ic(d[0]) + '<span><b>' + esc(d[1]) + '</b><small>' + esc(d[2]) + '</small></span></button>';
        });
        $('#lbkModes').html(html);
    }
    $m.on('click', '.lbk-mode', function () {
        $('#lbkModes').removeClass('is-invalid');
        modeSel().val($(this).attr('data-v')).trigger('change');
        renderModes();
    });
    $(document).on('change', '#payment_mode', function () { if ($m.hasClass('show')) renderModes(); });

    var ID_SHORT = { 1: 'Aadhaar', 2: 'Driving licence', 4: 'PAN', 5: 'Voter ID', 3: 'Other' };
    function renderIdChips() {
        var $sel = $('#id_proof_name'); if (!$sel.length) return;
        var val = String($sel.val() || ''), html = '';
        $sel.find('option').each(function () {
            var v = String(this.value || ''); if (!v) return;
            html += '<button type="button" class="lbk-idchip' + (v === val ? ' is-on' : '') + '" data-v="' + esc(v) + '" role="radio" aria-checked="' + (v === val) + '">' + esc(ID_SHORT[v] || $(this).text().trim()) + '</button>';
        });
        $('#lbkIdChips').html(html);
        $('#lbkDropTitle').text('Upload ' + (val ? (ID_SHORT[val] || 'document') : 'ID document'));
    }
    $m.on('click', '.lbk-idchip', function () {
        var v = $(this).attr('data-v');
        $('#id_proof_name').val($('#id_proof_name').val() === v ? '' : v).trigger('change');
        renderIdChips();
    });

    /* ---------------- step 2 recap ---------------- */
    function paintRecap() {
        var seat = fixedMode() ? $('#lbkFixedName').text() : ($('#general_seat').val() === 'no' && curSeat() ? 'Seat ' + curSeat() : 'General seat');
        var shift = $('#plan_type_id').val() ? $('#plan_type_id option:selected').text().trim() : '';
        var plan = $('#plan_id3').val() ? $('#plan_id3 option:selected').text().trim() : '';
        var till = $('#lbkValidTill').hasClass('is-empty') ? '' : $('#lbkValidTill').text();
        $('#lbkRecap').html(
            '<span class="lbk-recap-ic">' + ic('armchair') + '</span>' +
            '<span class="lbk-recap-t"><b>' + esc(seat) + (shift ? ' · ' + esc(shift) : '') + (xPicked.length ? ' + ' + xPicked.map(optName).join(' + ') : '') + '</b><small>' + esc([plan, till ? 'till ' + till : ''].filter(Boolean).join(' · ')) + '</small></span>' +
            '<button type="button" class="lbk-recap-edit" data-lbk-edit>Change</button>');
    }

    /* ---------------- valid till, totals, add-ons summary ---------------- */
    var endEl = document.getElementById('end_date_show');
    function paintEnd() {
        var t = (endEl && endEl.textContent || '').replace(/^\s*end date\s*/i, '').trim();
        var m = t.match(/^(\d{4})-(\d{2})-(\d{2})$/) || null, m2 = m ? null : t.match(/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})$/);
        if (m) t = new Date(+m[1], +m[2] - 1, +m[3]).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });
        else if (m2) t = new Date(+m2[3], +m2[2] - 1, +m2[1]).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });
        if ($('#no_expiry').val() === '1') t = 'No expiry';
        $('#lbkValidTill').text(t || 'Pick a plan and start date').toggleClass('is-empty', !t);
    }
    if (endEl && window.MutationObserver) new MutationObserver(paintEnd).observe(endEl, { childList: true, characterData: true, subtree: true });

    function breakdown() {
        var plan = num($('#plan_price_id').val());
        var lockOn = $('#toggleFieldCheckbox2').val() === 'yes';
        var locker = lockOn ? num($('#locker_amount_book').val()) : 0;
        var dType = $('#discountType').val(), dRaw = num($('#discount_amount').val());
        var disc = dType === 'percentage' ? (plan + locker) * dRaw / 100 : (dType === 'amount' ? dRaw : 0);
        var total = Math.max(0, plan + locker - disc);
        return { plan: plan, locker: locker, lockOn: lockOn, disc: disc, dType: dType, dRaw: dRaw, total: total };
    }
    function paintTotals() {
        var b = breakdown();
        var paid = num($('#paid_amount').val());
        var show = b.total || paid;
        $('#lbkTotal, #lbkTotal2, #lbkPopTotal').text(money(show));
        var xTotal = extrasTotal();
        // Several shifts: one line per shift (name, then seat, time and plan)
        var lines = xPicked.length ? shiftLines('lbk-pop-l', true, b.plan - xTotal) : '<div class="lbk-pop-l"><span>' + esc($('#plan_id3 option:selected').text() && $('#plan_id3').val() ? $('#plan_id3 option:selected').text() : 'Plan fee') + '<small>' + esc($('#plan_type_id').val() ? $('#plan_type_id option:selected').text() : 'Shift') + '</small></span><b>' + money(b.plan - xTotal) + '</b></div>';
        if (b.lockOn) lines += '<div class="lbk-pop-l"><span>Locker</span><b>' + money(b.locker) + '</b></div>';
        if (b.disc > 0) lines += '<div class="lbk-pop-l is-ok"><span>Discount<small>' + (b.dType === 'percentage' ? b.dRaw + '%' : 'Flat') + '</small></span><b>− ' + money(b.disc) + '</b></div>';
        if (paid && Math.abs(paid - b.total) > .009) lines += '<div class="lbk-pop-l"><span>Paying now</span><b>' + money(paid) + '</b></div>';
        $('#lbkPopLines').html(lines);
        // step 1 summary + button amounts
        var shiftName = $('#plan_type_id').val() ? $('#plan_type_id option:selected').text().trim() : '';
        var planName = $('#plan_id3').val() ? $('#plan_id3 option:selected').text().trim() : '';
        var sum = '';
        if (b.plan) sum += xPicked.length ? shiftLines('lbk-sum-l', false, b.plan - xTotal) : '<div class="lbk-sum-l"><span>' + esc([shiftName, planName].filter(Boolean).join(' · ') || 'Plan fee') + '</span><b>' + money(b.plan - xTotal) + '</b></div>';
        if (b.lockOn && b.locker) sum += '<div class="lbk-sum-l"><span>Locker</span><b>' + money(b.locker) + '</b></div>';
        if (b.disc > 0) sum += '<div class="lbk-sum-l is-ok"><span>Discount' + (b.dType === 'percentage' ? ' · ' + b.dRaw + '%' : '') + '</span><b>− ' + money(b.disc) + '</b></div>';
        if (!sum) sum = '<div class="lbk-sum-l is-mut"><span>Pick a shift and plan to see the price</span><b></b></div>';
        $('#lbkSumLines').html(sum);
        $('#lbkSumTotal').text(money(b.total));
        $('#lbkNextAmt, #lbkBookAmt').text(b.total ? money(b.total) : '');
        var seatCode = fixedMode() ? String($('#lbkFixedName').text() || '').replace(/^Seat\s*/i, '') : ($('#general_seat').val() === 'no' ? curSeat() : 'GEN');
        $('#lbkHeadCode').text(seatCode || '');
        $('#lbkHeadTitle').text(seatCode && seatCode !== 'GEN' ? 'Seat ' + seatCode : (seatCode === 'GEN' ? 'General seat' : 'New booking'));
        $('#lbkAddSub').text((b.lockOn ? 'Locker added' : 'No locker') + ' · ' + (b.disc > 0 ? money(b.disc) + ' discount' : 'no extra discount'));
        var msg = $('#sended_message_type option:selected'), nx = $('#no_expiry').val() === '1';
        $('#lbkPrefSub').text((msg.val() ? msg.text() : 'Alerts as per settings') + ' · ' + (nx ? 'No expiry' : 'Ends on the valid-till date'));
        var pend = ($('#pending_amt').text() || '').trim();
        $('#pending_amt').toggleClass('is-ok', !pend && paid > 0).attr('data-ok', !pend && paid > 0 ? 'Fully paid · no due' : '');
    }
    $(document).on('input change', '#plan_start_date, #paid_amount, #plan_price_id, #toggleFieldCheckbox2, #locker_amount_book, #discountType, #discount_amount, #sended_message_type, #no_expiry, #plan_id3, #plan_type_id', function () { if ($m.hasClass('show')) { paintTotals(); paintEnd(); } });
    var ticker = null;

    $('#lbkPopBtn').on('click', function (e) {
        e.stopPropagation();
        var open = $('#lbkPop').prop('hidden');
        paintTotals();
        $('#lbkPop').prop('hidden', !open);
        $(this).attr('aria-expanded', String(open));
    });
    $(document).on('click', function (e) { if (!$(e.target).closest('#lbkPop, #lbkPopBtn').length) { $('#lbkPop').prop('hidden', true); $('#lbkPopBtn').attr('aria-expanded', 'false'); } });

    /* ---------------- accordions ---------------- */
    function setAcc($acc, open, instant) {
        var $b = $acc.find('.lbk-acc-b'), el = $b[0];
        $acc.toggleClass('is-open', open).find('.lbk-acc-h').attr('aria-expanded', String(open));
        if (!el) return;
        if (instant) { el.style.height = ''; $b.css('display', open ? 'block' : 'none'); return; }
        if (open) {
            $b.css({ display: 'block', height: 0, overflow: 'hidden' });
            var h = el.scrollHeight;
            requestAnimationFrame(function () { $b.css({ height: h }); });
            setTimeout(function () { if ($acc.hasClass('is-open')) $b.css({ height: '', overflow: '' }); }, 340);
        } else {
            $b.css({ height: el.scrollHeight, overflow: 'hidden' });
            requestAnimationFrame(function () { $b.css({ height: 0 }); });
            setTimeout(function () { if (!$acc.hasClass('is-open')) $b.css({ display: 'none', height: '', overflow: '' }); }, 340);
        }
    }
    $m.on('click', '.lbk-acc-h', function () { var $a = $(this).closest('.lbk-acc'); setAcc($a, !$a.hasClass('is-open')); });

    /* ---------------- open / close ---------------- */
    $m.on('show.bs.modal', function () {
        go(1);
        $('#lbkSeatQ').val('');
        $('#lbkPop').prop('hidden', true);
        // the opener script sets the seat mode just before showing the modal
        setTimeout(paintSeatMode, 0);
    });
    $m.on('shown.bs.modal', function () {
        paintSeatMode(); renderShifts(); renderPlans(); paintTotals(); paintEnd();
        clearInterval(ticker); ticker = setInterval(function () { paintTotals(); }, 600);
        $('#lbkStep1Err').prop('hidden', true);
    });
    $m.on('hidden.bs.modal', function () {
        clearInterval(ticker); go(1);
        $('#lbkStep1Err, #lbkStep2Err').prop('hidden', true);
        $('#lbkShiftBox, #lbkPlans, #lbkModes').removeClass('is-invalid');
        $m.find('.lbk-acc').each(function () { setAcc($(this), false, true); });
        seatList = null; pendingSeat = null;
        $('#lbkSeatPick').removeClass('is-invalid');
        paintTotals();
    });

    // tap anywhere on a date field to open the calendar
    $m.on('click', '.booking-date-group input[type="date"]', function () {
        if (this.disabled || this.readOnly || typeof this.showPicker !== 'function') return;
        try { this.showPicker(); } catch (e) {}
    });

    $('#bookingAvatarTrigger').on('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); $(this).trigger('click'); } });
})(window.jQuery);
