/* Libraro — seat / learner details drawer (#seatAllotmentModal2).
   learner/script.blade.php still opens the modal, calls route('learners.show') and fills the legacy fields
   and the actions (#modalOpContainer). This file listens to that same AJAX response and paints the new
   design from it, so no extra request is made and no backend behaviour changes. */
(function ($) {
    'use strict';
    if (!$) return;
    var $m = $('#seatAllotmentModal2.lsd');
    if (!$m.length) return;

    var SPRITE = (function () {
        var u = document.querySelector('use[href*="lb-icons.svg"]');
        return u ? u.getAttribute('href').split('#')[0] : '';
    })();
    function ic(name, cls) { return '<svg class="lb-i ' + (cls || '') + '" aria-hidden="true"><use href="' + SPRITE + '#i-' + name + '"></use></svg>'; }
    function brand(name) { return '<svg class="lb-ib" aria-hidden="true"><use href="' + SPRITE + '#b-' + name + '"></use></svg>'; }
    function esc(v) { return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; }); }
    function has(v) { return v !== null && v !== undefined && v !== '' && v !== false && v !== 'null' && v !== 'NA'; }
    function money(n) { n = parseFloat(n); if (isNaN(n)) return '—'; return '₹' + n.toLocaleString('en-IN', { maximumFractionDigits: 2 }); }
    function dateOf(s) {
        if (!has(s) || s === '0000-00-00') return null;
        var m = String(s).match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (m) return new Date(+m[1], +m[2] - 1, +m[3]);
        m = String(s).match(/^(\d{1,2})[-\/](\d{1,2})[-\/](\d{4})/);
        if (m) return new Date(+m[3], +m[2] - 1, +m[1]);
        var d = new Date(s); return isNaN(d) ? null : d;
    }
    function fmtDate(s) { var d = dateOf(s); return d ? d.toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' }) : '—'; }
    function initials(n) { return String(n || '').trim().split(/\s+/).filter(Boolean).slice(0, 2).map(function (w) { return w.charAt(0).toUpperCase(); }).join('') || '?'; }
    function strip(h) { var d = document.createElement('div'); d.innerHTML = h || ''; return (d.textContent || '').trim(); }
    function asset(p) {
        if (!has(p)) return '';
        if (/^https?:\/\//.test(p)) return p;
        var base = (document.querySelector('meta[name="lp-base"]') || {}).content;
        if (!base) { var a = document.querySelector('link[href*="/public/css/"]'); base = a ? a.href.split('/public/css/')[0] : ''; }
        return base + '/' + String(p).replace(/^\/+/, '');
    }
    function cleanMobile(v) { var d = String(v || '').replace(/\D/g, ''); if (d.length > 10) d = d.slice(-10); return d.length === 10 ? d : ''; }

    /* ---------- painting ---------- */
    var pendingId = null;
    function loading(on) { $m.toggleClass('is-loading', !!on); }
    function reset() {
        loading(true);
        $m.find('[data-lsd="title"]').text('Seat');
        $m.find('[data-lsd="kind"]').html('&nbsp;');
        $m.find('[data-lsd="bday"], [data-lsd="docsWrap"], [data-lsd="txWrap"], [data-lsd="wa"], [data-lsd="alltx"], [data-lsd="full"], [data-lsd="duebar"]').prop('hidden', true);
        $m.find('[data-lsd="groups"], [data-lsd="pills"], [data-lsd="docs"], [data-lsd="tx"]').empty();
        var b = $m.find('.lsd-body')[0]; if (b) b.scrollTop = 0;
    }

    function status(d) {
        var raw = strip(d.seat_status), cls = String(d.seat_status || '');
        var tone = /danger|expired/i.test(cls + raw) ? 'bad' : (/warning|extend|extension/i.test(cls + raw) ? 'warn' : 'ok');
        if (+d.frozen_status === 1) return { text: 'Frozen', tone: 'info' };
        if (+d.no_expiry === 1) return { text: 'Non-expiry', tone: 'pink' };
        if (+d.status === 0) return { text: 'Inactive', tone: 'mut' };
        if (!raw) raw = 'Active';
        return { text: raw, tone: tone };
    }

    function paint(d) {
        var hideDob = $m.attr('data-hide-dob') === '1';
        var seatShort = has(d.floor_seat_no) ? d.floor_seat_no : d.seat_no;
        var general = !has(d.seat_no) || d.seat_no === 'GEN' || d.seat_no === 0 || d.seat_no === '0';
        var first = String(d.name || '').trim().split(/\s+/)[0] || '';
        var mob = cleanMobile(d.mobile);
        var due = parseFloat(d.pending_amount_num) || 0;
        // advance: paid over the bills, used on the next renewal (docs/LEARNER_ADVANCE.md)
        var adv = due > 0 ? 0 : (parseFloat(d.advance_amount_num) || 0);
        var st = status(d);
        var ext = st.tone === 'warn';
        var nonExp = +d.no_expiry === 1;

        // due amount bar above the photo
        var $dueBar = $m.find('[data-lsd="duebar"]').prop('hidden', !(due > 0));
        if (due > 0) {
            $dueBar.find('[data-lsd="dueAmt"]').text(money(due));
            $dueBar.find('[data-lsd="dueWhen"]').text(has(d.due_date) ? ' · due ' + fmtDate(d.due_date) : '');
            $dueBar.find('[data-lsd="dueBtn"]').attr({ 'data-id': d.learner_id || d.id || '', 'data-learnerdetail': d.learner_detail_id || '' });
        }

        $m.find('[data-lsd="title"]').text(general ? 'General seat' : 'Seat ' + (has(d.multi_seats) ? d.multi_seats : seatShort));
        // A multi-shift booking lists all its shifts
        $m.find('[data-lsd="kind"]').text(has(d.multi_shifts) ? d.multi_count + ' shifts · ' + d.multi_shifts : ([d.plan_type_name, (d.start_time && d.end_time) ? d.start_time + ' – ' + d.end_time : ''].filter(has).join(' · ') || ($m.attr('data-branch') || '')));

        // birthday
        var dob = dateOf(d.dob), today = new Date();
        var bday = !hideDob && dob && dob.getDate() === today.getDate() && dob.getMonth() === today.getMonth();
        if (bday) {
            var msg = 'Happy birthday ' + first + '! Wishing you a great year and success in your studies. — Team ' + ($m.attr('data-branch') || 'Libraro');
            $m.find('[data-lsd="bdayTitle"]').text(first + "'s birthday today!");
            $m.find('[data-lsd="bdayLink"]').attr('href', 'https://wa.me/91' + mob + '?text=' + encodeURIComponent(msg)).toggleClass('is-off', !mob);
            $m.find('[data-lsd="bday"]').prop('hidden', false);
        }

        // member card
        var photo = asset(d.profile_picture);
        var $av = $m.find('[data-lsd="avatar"]').removeClass('is-due is-ext').addClass(due > 0 ? 'is-due' : (ext ? 'is-ext' : ''));
        $av.html(photo ? '<a href="' + esc(photo) + '" class="view-image" aria-label="View photo"><img src="' + esc(photo) + '" alt=""></a>' : '<span>' + esc(initials(d.name)) + '</span>');
        $m.find('[data-lsd="name"]').text(d.name || '—');
        $m.find('[data-lsd="uid"]').text(d.learner_no || '—');
        var pills = '<span class="lsd-pill is-' + st.tone + '">' + esc(st.text) + '</span>';
        if (due > 0) pills += '<span class="lsd-pill is-bad"><i class="lsd-blink"></i>Fee due ' + money(due) + '</span>';
        if (adv > 0) pills += '<span class="lsd-pill is-adv">' + ic('wallet') + 'Advance ' + money(adv) + '</span>';
        if (d.overdue) pills += '<span class="lsd-pill is-bad">' + esc(d.overdue) + '</span>';
        if (ext && st.text.toLowerCase().indexOf('extension') === -1) pills += '<span class="lsd-pill is-warn"><i class="lsd-blink lsd-blink--amb"></i>Extension due</span>';
        $m.find('[data-lsd="pills"]').html(pills);
        if (mob) $m.find('[data-lsd="wa"]').attr('href', 'https://wa.me/91' + mob).prop('hidden', false);

        // plan used
        var left = parseInt(d.diff_in_days, 10), used = Math.max(0, parseInt(d.days_since_start, 10) || 0);
        var pct = 0, leftTxt = '—';
        if (nonExp) { pct = 100; leftTxt = 'No expiry'; }
        else if (!isNaN(left) && left !== 999) {
            var total = used + Math.max(0, left);
            pct = total > 0 ? Math.min(100, Math.round(used / total * 100)) : 100;
            leftTxt = left > 0 ? left + (left === 1 ? ' day left' : ' days left') : (left === 0 ? 'Ends today' : 'Ended ' + Math.abs(left) + ' days ago');
            if (left < 0) pct = 100;
        }
        $m.find('[data-lsd="left"]').text(leftTxt);
        $m.find('[data-lsd="bar"]').css('width', '0%').toggleClass('is-ext', ext || left < 0);
        requestAnimationFrame(function () { requestAnimationFrame(function () { $m.find('[data-lsd="bar"]').css('width', pct + '%'); }); });

        // detail groups
        var mode = +d.payment_mode === 1 ? 'Online' : (+d.payment_mode === 2 ? 'Offline' : 'Pay later');
        var fee = parseFloat(d.plan_price_id);
        var paidTxt = +d.is_paid === 1 ? 'Paid' : 'Not paid';
        var lt = d.latest_transaction || null;
        // Multi-shift booking: every shift (seat, timing, fee); see docs/ADVANCED_SHIFTS.md
        var shifts = Array.isArray(d.shifts) && d.shifts.length > 1 ? d.shifts : null;
        var seatsTxt = shifts && has(d.multi_seats) ? d.multi_seats : seatShort;
        var shiftRows = shifts ? shifts.map(function (s, i) { return ['Shift ' + (i + 1), [s.shift, s.time, 'Seat ' + s.seat, money(s.price)].filter(has).join(' · ')]; }) : null;
        var groups = [
            {
                key: 'b', title: 'Booking', icon: 'armchair', tone: 'acc',
                sub: [general ? 'General seat' : 'Seat ' + seatsTxt, shifts ? shifts.length + ' shifts' : d.plan_type_name, d.plan_name].filter(has).join(' · '),
                rows: [
                    ['Seat', general ? 'General (no fixed seat)' : 'Seat ' + seatsTxt]
                ].concat(shiftRows || [
                    ['Shift', d.plan_type_name],
                    ['Timing', (d.start_time && d.end_time) ? d.start_time + ' – ' + d.end_time + (has(d.hours) ? ' · ' + d.hours + ' hrs' : '') : (has(d.hours) ? d.hours + ' hrs' : '')]
                ]).concat([
                    ['Plan', d.plan_name],
                    ['Start date', fmtDate(d.plan_start_date)],
                    ['Valid till', +d.frozen_status === 1 ? 'Frozen' : (nonExp ? 'No expiry' : fmtDate(d.plan_end_date))],
                    ['Joined on', fmtDate(d.join_date)]
                ])
            },
            {
                key: 'p', title: 'Payment', icon: 'wallet', tone: 'ok',
                sub: due > 0 ? money(due) + ' due' + (isNaN(fee) ? '' : ' of ' + money(fee)) : (adv > 0 ? money(adv) + ' advance · ' : '') + (isNaN(fee) ? paidTxt : (+d.is_paid === 1 ? 'Fully paid · ' : '') + money(fee) + ' · ' + mode),
                rows: [
                    ['Plan fee', isNaN(fee) ? '—' : money(fee)],
                    ['Payment mode', mode],
                    ['Status', paidTxt, +d.is_paid === 1 ? 'ok' : 'bad'],
                    due > 0 ? ['Due', money(due), 'bad'] : (adv > 0 ? ['Advance', money(adv) + ' · next renewal', 'adv'] : ['Balance', +d.is_paid === 1 ? 'All paid' : '—', +d.is_paid === 1 ? 'ok' : '']),
                    lt && has(lt.paid_date) ? ['Last paid on', fmtDate(lt.paid_date)] : null,
                    lt && has(lt.transaction_id) ? ['Transaction ID', lt.transaction_id] : null
                ]
            }
            // the learner's name, ID and mobile are already in the head of this drawer
        ];
        var gh = '';
        groups.forEach(function (g) {
            var rows = g.rows.filter(Boolean).map(function (r) {
                return '<div class="lsd-row"><span>' + esc(r[0]) + '</span><b' + (r[2] ? ' class="is-' + r[2] + '"' : '') + '>' + esc(has(r[1]) ? r[1] : '—') + '</b></div>';
            }).join('');
            gh += '<div class="lsd-group" data-g="' + g.key + '">' +
                '<button type="button" class="lsd-group-h" aria-expanded="false">' +
                '<span class="lsd-gic is-' + g.tone + '">' + ic(g.icon) + '</span>' +
                '<span class="lsd-gt"><b>' + g.title + '</b><small>' + esc(g.sub || '—') + '</small></span>' +
                '<span class="lsd-gchev">' + ic('chevron-down') + '</span></button>' +
                '<div class="lsd-group-b"><div class="lsd-group-in">' + rows + '</div></div></div>';
        });
        $m.find('[data-lsd="groups"]').html(gh);

        // documents (only what the learner record already has)
        var docs = [];
        if (has(d.id_proof_file)) {
            var proofName = +d.id_proof_name === 1 ? 'Aadhaar card' : (+d.id_proof_name === 2 ? 'Driving licence' : 'ID proof');
            docs.push({ name: proofName, url: asset(d.id_proof_file) });
        }
        if (docs.length) {
            $m.find('[data-lsd="docsCount"]').text('· ' + docs.length + (docs.length === 1 ? ' file' : ' files'));
            $m.find('[data-lsd="docs"]').html(docs.map(function (x) {
                var ext2 = (String(x.url).split('?')[0].split('.').pop() || '').toUpperCase().slice(0, 4);
                var img = /JPE?G|PNG|WEBP|GIF/.test(ext2);
                return '<div class="lsd-doc"><span class="lsd-doc-ic ' + (img ? 'is-img' : '') + '">' + ic(img ? 'image' : 'file-text') + '</span>' +
                    '<span class="lsd-doc-t"><b>' + esc(x.name) + '</b><small>Learner document</small></span>' +
                    '<span class="lsd-doc-f"><em>' + esc(ext2 || 'FILE') + '</em><a href="' + esc(x.url) + '" target="_blank" rel="noopener" aria-label="View">' + ic('eye') + '</a>' +
                    '<a href="' + esc(x.url) + '" download aria-label="Download">' + ic('download') + '</a></span></div>';
            }).join(''));
            $m.find('[data-lsd="docsWrap"]').prop('hidden', false);
        }

        // last transaction
        if (lt && (has(lt.total_amount) || has(lt.paid_amount))) {
            var pend = parseFloat(lt.pending_amount) > 0 || +lt.is_paid === 0;
            var amt = has(lt.paid_amount) && +lt.paid_amount > 0 ? lt.paid_amount : lt.total_amount;
            $m.find('[data-lsd="tx"]').html('<div class="lsd-txc' + (pend ? ' is-pend' : '') + '"><span class="lsd-txc-top"><span class="lsd-txc-ic">' + ic(pend ? 'clock-alert' : 'indian-rupee') + '</span><em>' + (pend ? 'Pending' : 'Paid') + '</em></span>' +
                '<b>' + money(amt) + '</b><span>' + esc(d.plan_name || 'Plan fee') + '</span><small>' + esc(has(lt.paid_date) ? fmtDate(lt.paid_date) : 'Not paid yet') + ' · ' + esc(mode) + '</small></div>');
            $m.find('[data-lsd="txWrap"]').prop('hidden', false);
        }

        // members-list variant
        $m.find('[data-lsd="mavatar"]').attr('class', $av.attr('class') + ' lsd-av--xl').html($av.html());
        $m.find('[data-lsd="mname"]').text(d.name || '—');
        $m.find('[data-lsd="muid"]').text(d.learner_no || '—');
        var mp = '<span class="lsd-pill is-' + st.tone + '">' + esc(+d.frozen_status === 1 || nonExp ? st.text : (st.tone === 'ok' ? 'Active' : st.text)) + '</span>';
        if (!nonExp && +d.frozen_status !== 1 && has(d.plan_end_date)) mp += '<span class="lsd-pill is-mut">' + ic('alarm-clock') + esc(strip(d.seat_status) || leftTxt) + ' · ' + fmtDate(d.plan_end_date) + '</span>';
        if (due > 0) mp += '<span class="lsd-pill is-bad"><i class="lsd-blink"></i>Fee due ' + money(due) + '</span>';
        if (adv > 0) mp += '<span class="lsd-pill is-adv">' + ic('wallet') + 'Advance ' + money(adv) + '</span>';
        $m.find('[data-lsd="mpills"]').html(mp);
        var tiles = [
            ['Seat', general ? 'General' : seatsTxt], ['Shift', shifts ? shifts.length + ' shifts' : d.plan_type_name], ['Plan', d.plan_name],
            ['Valid till', +d.frozen_status === 1 ? 'Frozen' : (nonExp ? 'No expiry' : fmtDate(d.plan_end_date))],
            ['Plan fee', isNaN(fee) ? '—' : money(fee)], due > 0 ? ['Balance', money(due) + ' due', 'bad'] : (adv > 0 ? ['Advance', money(adv), 'adv'] : ['Balance', +d.is_paid === 1 ? 'All paid' : '—', +d.is_paid === 1 ? 'ok' : ''])
        ];
        $m.find('[data-lsd="tiles"]').html(tiles.map(function (x) { return '<div class="lsd-tile"><span>' + esc(x[0]) + '</span><b' + (x[2] ? ' class="is-' + x[2] + '"' : '') + '>' + esc(has(x[1]) ? x[1] : '—') + '</b></div>'; }).join(''));
        $m.find('[data-lsd="mshiftsCount"]').text(shifts ? '· ' + shifts.length : '');
        $m.find('[data-lsd="mshifts"]').html(shifts ? shifts.map(function (s, i) {
            return '<div class="lsd-shiftrow"><span class="lsd-shiftno">' + (i + 1) + '</span>' +
                '<span class="lsd-shiftt"><b>' + esc(s.shift) + '</b><small>' + esc(['Seat ' + s.seat, s.time].filter(has).join(' · ')) + '</small></span>' +
                '<b>' + money(s.price) + '</b></div>';
        }).join('') : '');
        $m.find('[data-lsd="mshiftsWrap"]').prop('hidden', !shifts);
        $m.find('[data-lsd="mleft"]').text(leftTxt);
        $m.find('[data-lsd="mbar"]').css('width', '0%').toggleClass('is-ext', ext || left < 0);
        requestAnimationFrame(function () { requestAnimationFrame(function () { $m.find('[data-lsd="mbar"]').css('width', pct + '%'); }); });
        var payHtml = '';
        if (lt && (has(lt.paid_amount) || has(lt.total_amount))) {
            var pend2 = parseFloat(lt.pending_amount) > 0 || +lt.is_paid === 0;
            var amt2 = has(lt.paid_amount) && +lt.paid_amount > 0 ? lt.paid_amount : lt.total_amount;
            var how = +lt.payment_mode === 1 || +d.payment_mode === 1 ? 'Paid online' : (+lt.payment_mode === 2 || +d.payment_mode === 2 ? 'Paid in cash' : 'Pay later');
            payHtml += '<div class="lsd-payrow"><span class="lsd-payic ' + (pend2 ? 'is-bad' : '') + '">' + ic(pend2 ? 'clock-alert' : (/online/i.test(how) ? 'smartphone' : 'banknote')) + '</span>' +
                '<span class="lsd-payt"><b>' + (pend2 ? 'Payment pending' : how) + '</b><small>' + esc(has(lt.paid_date) ? fmtDate(lt.paid_date) : 'Not paid yet') + (has(lt.transaction_id) ? ' · #' + esc(lt.transaction_id) : '') + '</small></span><b>' + money(amt2) + '</b></div>';
        }
        $m.find('[data-lsd="pay"]').html(payHtml || '<div class="lsd-payempty">No payments recorded yet.</div>');
        $m.find('[data-lsd="full"]').attr('href', $m.attr('data-profile-url').replace('__ID__', d.id || d.learner_id)).prop('hidden', false);

        loading(false);
    }

    // Which learner is the drawer waiting for?
    $(document).on('click', '.second_popup, .second_popup_without_seat', function () {
        if ($(this).closest('.lsm').length) $m.removeClass('lsd--member');
        pendingId = String($(this).data('userid') || '');
        reset();
    });
    $(document).on('click.lsdopen', '[data-lsd-open]', function (e) {
        // members list / anywhere: open the same drawer for a learner id
        e.preventDefault();
        var $b = $('<button type="button" class="second_popup d-none"></button>').attr('data-userid', $(this).attr('data-lsd-open')).attr('data-seat_no', $(this).attr('data-seat') || '');
        $('body').append($b); $b.trigger('click'); $b.remove();
    });

    $(document).ajaxSuccess(function (e, xhr, settings) {
        if (!pendingId || !settings || String(settings.type || 'GET').toUpperCase() !== 'GET') return;
        if (String(settings.url || '').indexOf('booking-info') === -1) return;
        var d = xhr.responseJSON;
        if (!d || typeof d !== 'object') { try { d = JSON.parse(xhr.responseText); } catch (x) { return; } }
        if (String(d.id) !== pendingId && String(d.learner_id) !== pendingId) return;
        pendingId = null;
        try { paint(d); } catch (err) { if (window.console) console.error(err); loading(false); }
        setTimeout(function () {
            window.lpStyleOps($('#modalOpContainer')); paintArrows();
            var tx = $('#modalOpContainer #modalBtnTransactions').attr('href');
            $m.find('[data-lsd="alltx"]').attr('href', tx || '#').prop('hidden', !tx);
        }, 0);
    });
    $(document).ajaxError(function (e, xhr, settings) {
        if (pendingId && String(settings.url || '').indexOf('booking-info') > -1) {
            pendingId = null; loading(false);
            $('#modalOpContainer').html('<div class="lsd-ops-loading">Could not load this learner. Please try again.</div>');
        }
    });

    // Accordions (smooth height)
    $m.on('click', '.lsd-group-h', function () {
        var $g = $(this).closest('.lsd-group'), open = !$g.hasClass('is-open');
        var body = $g.find('.lsd-group-b')[0], inner = $g.find('.lsd-group-in')[0];
        $(this).attr('aria-expanded', open ? 'true' : 'false');
        if (open) {
            $g.addClass('is-open');
            body.style.height = '0px';
            requestAnimationFrame(function () { body.style.height = inner.offsetHeight + 'px'; });
            body.addEventListener('transitionend', function te() { if ($g.hasClass('is-open')) body.style.height = 'auto'; body.removeEventListener('transitionend', te); });
        } else {
            body.style.height = inner.offsetHeight + 'px';
            requestAnimationFrame(function () { $g.removeClass('is-open'); body.style.height = '0px'; });
        }
    });

    // Actions rail arrows (the old handlers animate scrollLeft; we only keep their enabled state right)
    function paintArrows() {
        var el = document.getElementById('modalOpContainer');
        if (!el) return;
        var max = el.scrollWidth - el.clientWidth - 2;
        $('#opScrollLeftBtn').prop('disabled', el.scrollLeft <= 2);
        $('#opScrollRightBtn').prop('disabled', el.scrollLeft >= max);
    }
    $('#modalOpContainer').on('scroll', function () { window.requestAnimationFrame(paintArrows); });
    $(window).on('resize', paintArrows);

    // Photo in the member card
    $m.on('click', '[data-lsd="avatar"] a.view-image', function (e) {
        var u = $(this).attr('href'), $iv = $('#imageViewModal');
        if (!$iv.length) return;
        e.preventDefault();
        $('#modalImage').attr('src', u);
        $iv.attr('aria-hidden', 'false').css({ display: 'flex', opacity: 1 });
    });

    $m.on('hidden.bs.modal', function () { pendingId = null; });
})(window.jQuery);
