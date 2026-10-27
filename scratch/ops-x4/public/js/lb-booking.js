/* Libraro — new booking drawer (#seatAllotmentModal, learner/popup.blade.php).
   The original selects/inputs stay in the form and keep every existing handler in learner/script.blade.php
   (seat rules, prices, validation, submit). This file only:
     - runs the 2 steps,
     - draws the seat / plan / shift choice cards that set those same selects,
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
    var generalOk = $m.attr('data-general-ok') === '1';

    /* ---------------- steps ---------------- */
    function go(step) {
        $m.attr('data-lbk-step', step);
        $m.find('.lbk-stepno').text('Step ' + step + ' of 2');
        var body = $m.find('.modal-body')[0]; if (body) body.scrollTop = 0;
        if (step === 2) { renderPlans(); renderShifts(); }
    }
    function step1Ok() {
        var errs = [];
        var name = ($('#name').val() || '').trim(), mobile = ($('#mobile').val() || '').trim();
        $('#name, #mobile').removeClass('is-invalid');
        $('#lbkSeatPick').removeClass('is-invalid');
        if (!name) { errs.push('Enter the full name.'); $('#name').addClass('is-invalid'); }
        if (!/^\d{10}$/.test(mobile)) { errs.push('Enter a valid 10-digit mobile number.'); $('#mobile').addClass('is-invalid'); }
        if ($('#general_seat').val() === 'no' && !($('#seat_id').val() || $('#seat_no').val())) { errs.push('Choose a seat.'); $('#lbkSeatPick').addClass('is-invalid'); }
        var $e = $('#lbkStep1Err');
        if (errs.length) { $e.text(errs.join(' ')).prop('hidden', false); return false; }
        $e.prop('hidden', true).text('');
        return true;
    }
    $('#lbkNext').on('click', function () { if (step1Ok()) go(2); });
    $('#lbkBack').on('click', function () { go(1); });
    $m.on('click', '.lbk-progress i:first-child', function () { go(1); });
    $m.on('click', '.lbk-progress i:last-child', function () { if ($m.attr('data-lbk-step') === '1' && step1Ok()) go(2); });
    document.addEventListener('submit', function (e) {
        if (e.target === form && $m.attr('data-lbk-step') === '1') {
            e.preventDefault(); e.stopImmediatePropagation();
            if (step1Ok()) go(2);
        }
    }, true);
    $('#bookSeatSubmitBtn').on('click', function () {
        setTimeout(function () { if ($m.find('.lbk-step[data-step="1"] .is-invalid').length) go(1); }, 30);
    });

    /* ---------------- rails (arrows + wheel) ---------------- */
    $m.on('click', '.lbk-arrows button', function () {
        var rail = $m.find($(this).closest('.lbk-arrows').attr('data-lbk-rail'))[0];
        if (rail) rail.scrollBy({ left: (+$(this).attr('data-dir') || 1) * Math.max(160, rail.clientWidth * .6), behavior: 'smooth' });
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
        seatReq = $.ajax({ url: $m.attr('data-seats-url'), type: 'GET', dataType: 'json', global: false })
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
        $rail.html(html);
        $('#lbkSeatEmpty').prop('hidden', seatList === null || shown > 0 || (!q && generalOk));
        var free = seatList ? seatList.length : 0;
        $('#lbkSeatHint').text(general ? 'General · any free seat' : (sel ? 'Seat ' + sel + ' selected' : free + ' free seats'));
        centerOn($rail[0]);
    }
    function applySeat(v) {
        v = String(v);
        if (window.seatChoices) { try { window.seatChoices.setChoiceByValue(v); } catch (e) {} }
        if (!$('#seat_id option[value="' + v.replace(/"/g, '') + '"]').length) $('#seat_id').append($('<option>').val(v).text(v));
        $('#seat_id').val(v).trigger('change');
        renderSeats();
    }
    $m.on('click', '.lbk-seat', function () {
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
            var t = $(this).text().trim();
            var sub = /month/i.test(t) ? 'Monthly billing' : (/week/i.test(t) ? 'Weekly plan' : (/day/i.test(t) ? 'Day pass' : (/year/i.test(t) ? 'Yearly plan' : 'Plan')));
            html += '<button type="button" class="lbk-plan' + (on ? ' is-on' : '') + '" data-v="' + esc(v) + '" role="option" aria-selected="' + on + '"><b>' + esc(t) + '</b><small>' + sub + '</small></button>';
        });
        $('#lbkPlans').html(html || '<span class="lbk-note">No plans yet. Add plans in Library settings.</span>');
        centerOn($('#lbkPlans')[0]);
    }
    var SHIFT_IC = { 1: 'sun', 2: 'sunrise', 3: 'sunset', 8: 'clock-3', 9: 'moon', 10: 'lock', 11: 'crown' };
    function renderShifts() {
        var val = String($('#plan_type_id').val() || ''), html = '', n = 0, placeholder = '';
        var isGen = $('#general_seat').val() === 'yes' && !fixedMode();
        $('#plan_type_id option').each(function () {
            var v = String(this.value || ''), t = $(this).text().trim();
            if (!v) { placeholder = t; return; }
            n++;
            var on = v === val, meta = SHIFTS[v] || {};
            html += '<button type="button" class="lbk-shift' + (on ? ' is-on' : '') + '" data-v="' + esc(v) + '" role="option" aria-selected="' + on + '">' +
                '<span class="lbk-shift-r1">' + ic(SHIFT_IC[meta.day] || 'clock') + '<b>' + esc(t) + '</b>' + ic(on ? 'circle-check' : 'circle') + '</span>' +
                '<small>' + esc(meta.time || 'Timing as per library') + '</small>' +
                '<span class="lbk-shift-r3"><span>' + (on && num($('#plan_price_id').val()) ? money(num($('#plan_price_id').val())) : '') + '</span><em>' + (isGen ? 'Available' : 'Seat free') + '</em></span></button>';
        });
        if (!n) html = '<span class="lbk-note">' + esc(/loading/i.test(placeholder) ? 'Loading shifts…' : (placeholder && !/choose/i.test(placeholder) ? placeholder : ($('#general_seat').val() === 'no' && !curSeat() ? 'Choose a seat first' : 'No shifts available'))) + '</span>';
        $('#lbkShifts').html(html);
        $('#lbkShiftHint').text($('#general_seat').val() === 'no' || fixedMode() ? '· free on your seat' : '· general seat');
        centerOn($('#lbkShifts')[0]);
    }
    $m.on('click', '.lbk-plan', function () { $('#plan_id3').val($(this).attr('data-v')).trigger('change'); renderPlans(); });
    $m.on('click', '.lbk-shift', function () { $('#plan_type_id').val($(this).attr('data-v')).trigger('change'); renderShifts(); });
    $(document).on('change', '#plan_id3', function () { if ($m.hasClass('show')) renderPlans(); });
    $(document).on('change', '#plan_type_id', function () { if ($m.hasClass('show')) renderShifts(); });
    var shiftSel = document.getElementById('plan_type_id');
    if (shiftSel && window.MutationObserver) new MutationObserver(function () { if ($m.hasClass('show')) renderShifts(); }).observe(shiftSel, { childList: true });

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
        var lines = '<div class="lbk-pop-l"><span>' + esc($('#plan_id3 option:selected').text() && $('#plan_id3').val() ? $('#plan_id3 option:selected').text() : 'Plan fee') + '<small>' + esc($('#plan_type_id').val() ? $('#plan_type_id option:selected').text() : 'Shift') + '</small></span><b>' + money(b.plan) + '</b></div>';
        if (b.lockOn) lines += '<div class="lbk-pop-l"><span>Locker</span><b>' + money(b.locker) + '</b></div>';
        if (b.disc > 0) lines += '<div class="lbk-pop-l is-ok"><span>Discount<small>' + (b.dType === 'percentage' ? b.dRaw + '%' : 'Flat') + '</small></span><b>− ' + money(b.disc) + '</b></div>';
        if (paid && Math.abs(paid - b.total) > .009) lines += '<div class="lbk-pop-l"><span>Paying now</span><b>' + money(paid) + '</b></div>';
        $('#lbkPopLines').html(lines);
        $('#lbkAddSub').text((b.lockOn ? 'Locker added' : 'No locker') + ' · ' + (b.disc > 0 ? money(b.disc) + ' discount' : 'no extra discount'));
        var msg = $('#sended_message_type option:selected'), nx = $('#no_expiry').val() === '1';
        $('#lbkPrefSub').text((msg.val() ? msg.text() : 'Alerts as per settings') + ' · ' + (nx ? 'No expiry' : 'Ends on the valid-till date'));
        var pend = ($('#pending_amt').text() || '').trim();
        $('#pending_amt').toggleClass('is-ok', !pend && paid > 0).attr('data-ok', !pend && paid > 0 ? 'Fully paid · no due' : '');
    }
    $(document).on('input change', '#paid_amount, #plan_price_id, #toggleFieldCheckbox2, #locker_amount_book, #discountType, #discount_amount, #sended_message_type, #no_expiry, #plan_id3, #plan_type_id', function () { if ($m.hasClass('show')) { paintTotals(); paintEnd(); } });
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
        paintSeatMode(); paintTotals(); paintEnd();
        clearInterval(ticker); ticker = setInterval(function () { paintTotals(); }, 600);
        $('#lbkStep1Err').prop('hidden', true);
    });
    $m.on('hidden.bs.modal', function () {
        clearInterval(ticker); go(1);
        $('#lbkStep1Err').prop('hidden', true);
        $m.find('.lbk-acc').each(function () { setAcc($(this), false, true); });
        seatList = null; pendingSeat = null;
        $('#lbkSeatPick').removeClass('is-invalid');
        paintTotals();
    });
    $('#bookingAvatarTrigger').on('keydown', function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); $(this).trigger('click'); } });
})(window.jQuery);
