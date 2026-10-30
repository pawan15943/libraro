@extends('layouts.library')

@section('title', 'New demo booking')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-opform.css') }}?v={{ @filemtime(public_path('css/lb-opform.css')) }}">
<link rel="stylesheet" href="{{ asset('public/css/lb-pages.css') }}?v={{ @filemtime(public_path('css/lb-pages.css')) }}">
<link rel="stylesheet" href="{{ asset('public/css/lb-demo.css') }}?v={{ @filemtime(public_path('css/lb-demo.css')) }}">
@endpush

@section('content')

@php
    $hidden = toggleHideField();
    $showDob = !in_array('2', $hidden);
    $showEmail = !in_array('1', $hidden);
    $showMore = !in_array('7', $hidden);
    $showProof = $showMore && !in_array('5', $hidden);
    $showAddress = $showMore && !in_array('32', $hidden);
    // "yes" = general seat (no number), "no" = a numbered seat; names kept for the booking service
    $seatMode = old('general_seat', 'yes') === 'no' ? 'no' : 'yes';
    $payMode = old('payment_mode', 'paylater');
    $moreOpen = $errors->hasAny(['id_proof_name', 'id_proof_number', 'id_proof_file', 'address']) || old('id_proof_name') || old('id_proof_number') || old('address');
    $bad = fn ($key) => $errors->has($key) ? ' is-bad' : '';
@endphp

<div class="lpg lpg--narrow ldm">
    <header class="lpg-head">
        <div class="lpg-head-t">
            <div class="lpg-crumb">
                <a href="{{ route('library.home') }}">Dashboard</a><x-lb-icon name="chevron-right" />
                <a href="{{ route('demo-users.index') }}">Demo inquiries</a><x-lb-icon name="chevron-right" />
                <span>New</span>
            </div>
            <h1>New demo booking</h1>
            <p>Register a trial learner and hold a seat for them. They can pay now or later.</p>
        </div>
    </header>

    @if($errors->any())
        <div class="ldm-alert" role="alert">
            <x-lb-icon name="circle-alert" />
            <div>
                <b>{{ $errors->count() === 1 ? '1 field needs attention' : $errors->count() . ' fields need attention' }}</b>
                <p>{{ $errors->first() }}</p>
            </div>
        </div>
    @endif

    <form action="{{ route('demo-users.store') }}" method="POST" enctype="multipart/form-data" id="demoInquiryForm" novalidate>
        @csrf
        <input type="hidden" id="branch_id" value="{{ $branch->id }}">

        {{-- 1. Learner --}}
        <section class="lef-sec" id="sec-learner">
            <div class="lef-sec-h">
                <span class="lef-sec-ic is-blue"><x-lb-icon name="user-round" /></span>
                <div><h4>Learner</h4><p>Who is coming for the demo.</p></div>
            </div>

            <div class="lef-photo ldm-photo">
                <button type="button" class="lef-photo-ava" id="avatarUploadTrigger" aria-label="Add profile photo">
                    <span class="lef-photo-circle">
                        <span class="lef-photo-ph" id="avatarDefaultIcon"><x-lb-icon name="user-round" /></span>
                        <img id="avatarCroppedPreview" src="" alt="Profile photo" style="display:none;">
                    </span>
                    <span class="lef-photo-cam"><x-lb-icon name="camera" /></span>
                </button>
                <div class="lef-photo-t">
                    <strong id="avatarStatusTitle">Profile photo</strong>
                    <p id="avatarSubtext">Optional. A clear face photo for the ID card.</p>
                </div>
                <input type="file" class="d-none image-cropper" name="profile_picture" id="dm_photo" autocomplete="off" accept=".jpeg, .jpg, .png, .webp">
                <img class="preview-img" alt="" style="display:none !important;">
            </div>
            @error('profile_picture')<p class="lef-err">{{ $message }}</p>@enderror

            <div class="lef-grid">
                <div class="lef-f{{ $bad('name') }}">
                    <label for="dm_name">Full name <i>*</i></label>
                    <div class="lef-in"><x-lb-icon name="user-round" /><input type="text" class="char-only" name="name" id="dm_name" value="{{ old('name') }}" placeholder="e.g. Priya Sharma" autocomplete="off" maxlength="191" required></div>
                    @error('name')<p class="lef-err">{{ $message }}</p>@enderror
                </div>
                <div class="lef-f{{ $bad('mobile') }}">
                    <label for="dm_mobile">WhatsApp number <i>*</i><span id="mobCount"></span></label>
                    <div class="lef-in"><span class="lef-pre">+91</span><input type="text" class="digit-only" name="mobile" id="dm_mobile" value="{{ old('mobile') }}" placeholder="10 digit number" inputmode="numeric" maxlength="10" autocomplete="off" required></div>
                    @error('mobile')<p class="lef-err">{{ $message }}</p>@enderror
                </div>
                @if($showDob)
                <div class="lef-f{{ $bad('dob') }}">
                    <label for="dm_dob">Date of birth <span>Optional</span></label>
                    <div class="lef-in"><x-lb-icon name="cake" /><input type="date" name="dob" id="dm_dob" value="{{ old('dob') }}" max="{{ date('Y-m-d', strtotime('-10 years')) }}"></div>
                    @error('dob')<p class="lef-err">{{ $message }}</p>@enderror
                </div>
                @endif
                @if($showEmail)
                <div class="lef-f{{ $bad('email') }}">
                    <label for="dm_email">Email <span>Optional</span></label>
                    <div class="lef-in"><x-lb-icon name="mail" /><input type="email" name="email" id="dm_email" value="{{ old('email') }}" placeholder="name@example.com" inputmode="email" autocomplete="off"></div>
                    @error('email')<p class="lef-err">{{ $message }}</p>@enderror
                </div>
                @endif
            </div>
        </section>

        {{-- 2. Seat & plan --}}
        <section class="lef-sec" id="sec-plan">
            <div class="lef-sec-h">
                <span class="lef-sec-ic is-green"><x-lb-icon name="armchair" /></span>
                <div><h4>Seat &amp; plan</h4><p>Where they sit and for how long.</p></div>
            </div>

            <div class="lef-f">
                <label>Seat</label>
                <div class="ldm-seg" role="radiogroup" aria-label="Seat type">
                    <label><input type="radio" name="general_seat" value="yes" @checked($seatMode === 'yes')><span><x-lb-icon name="users" />General seat</span></label>
                    <label><input type="radio" name="general_seat" value="no" @checked($seatMode === 'no')><span><x-lb-icon name="hash" />Seat number</span></label>
                </div>
            </div>

            <div class="lef-grid ldm-grid">
                <div class="lef-f{{ $bad('seat_no') }}" id="seatField" @if($seatMode === 'yes') hidden @endif>
                    <label for="seat_id2">Seat number <i>*</i><span>{{ $newAvailableSeat->count() }} free</span></label>
                    <div class="lef-in is-select"><x-lb-icon name="armchair" />
                        <select name="seat_no" id="seat_id2" @if($seatMode === 'yes') disabled @endif>
                            <option value="">Choose seat</option>
                            @foreach($newAvailableSeat as $value)
                                <option value="{{ $value['main'] }}" @selected(old('seat_no') == $value['main'])>Seat {{ $value['display'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    @error('seat_no')<p class="lef-err">{{ $message }}</p>@else<p class="lef-hint">Only seats with a free shift are listed.</p>@enderror
                </div>

                <div class="lef-f{{ $bad('plan_type_id') }}">
                    <label for="temp_plan_type_id">Shift <i>*</i></label>
                    <div class="lef-in is-select"><x-lb-icon name="clock" />
                        <select name="plan_type_id" id="temp_plan_type_id">
                            <option value="">Choose shift</option>
                        </select>
                    </div>
                    @error('plan_type_id')<p class="lef-err">{{ $message }}</p>@else<p class="lef-hint" id="shiftHint"></p>@enderror
                </div>

                <div class="lef-f{{ $bad('plan_id') }}">
                    <label for="plan_id4">Plan <i>*</i></label>
                    <div class="lef-in is-select"><x-lb-icon name="calendar-range" />
                        <select name="plan_id" id="plan_id4">
                            <option value="">Choose plan</option>
                            @foreach($plans as $value)
                                <option value="{{ $value->id }}" @selected(old('plan_id') == $value->id)>{{ $value->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @error('plan_id')<p class="lef-err">{{ $message }}</p>@enderror
                </div>

                <div class="lef-f{{ $bad('plan_start_date') }}">
                    <label for="dm_start">Starts on <i>*</i></label>
                    <div class="lef-in"><x-lb-icon name="calendar-days" /><input type="date" name="plan_start_date" id="dm_start" value="{{ old('plan_start_date', now()->format('Y-m-d')) }}" required></div>
                    @error('plan_start_date')<p class="lef-err">{{ $message }}</p>@enderror
                </div>
            </div>
        </section>

        {{-- 3. Payment --}}
        <section class="lef-sec" id="sec-pay">
            <div class="lef-sec-h">
                <span class="lef-sec-ic is-amber"><x-lb-icon name="indian-rupee" /></span>
                <div><h4>Payment</h4><p>Fee comes from your plan prices.</p></div>
            </div>

            <div class="ldm-amount{{ $bad('plan_price_id') }}" id="amountBox" aria-live="polite">
                <div>
                    <span class="ldm-amount-k">Payable amount</span>
                    <strong class="ldm-amount-v"><i>₹</i><span id="amountText">{{ old('plan_price_id') !== null && old('plan_price_id') !== '' ? number_format((float) old('plan_price_id')) : '0' }}</span></strong>
                    <span class="ldm-amount-n" id="chargeable_day_book">{{ $errors->first('plan_price_id') ?: 'Choose a shift and plan to see the fee.' }}</span>
                </div>
                <x-lb-icon name="receipt-text" />
                <input type="hidden" name="plan_price_id" id="plan_price" value="{{ old('plan_price_id') }}">
            </div>

            <div class="lef-f{{ $bad('payment_mode') }}">
                <label>Payment mode <i>*</i></label>
                <div class="ldm-seg ldm-seg--3" role="radiogroup" aria-label="Payment mode">
                    <label><input type="radio" name="payment_mode" value="paylater" @checked($payMode === 'paylater')><span><x-lb-icon name="clock" />Pay later</span></label>
                    <label><input type="radio" name="payment_mode" value="offline" @checked($payMode === 'offline')><span><x-lb-icon name="banknote" />Cash / UPI</span></label>
                    <label><input type="radio" name="payment_mode" value="online" @checked($payMode === 'online')><span><x-lb-icon name="credit-card" />Online</span></label>
                </div>
                @error('payment_mode')<p class="lef-err">{{ $message }}</p>@else<p class="lef-hint" id="payHint"></p>@enderror
            </div>
        </section>

        {{-- 4. More details (optional, folded) --}}
        @if($showProof || $showAddress)
        <details class="lef-sec ldm-more" id="sec-more" @if($moreOpen) open @endif>
            <summary class="lef-sec-h">
                <span class="lef-sec-ic is-violet"><x-lb-icon name="id-card" /></span>
                <div><h4>More details</h4><p>ID proof and address. Optional.</p></div>
                <x-lb-icon name="chevron-down" class="ldm-more-chev" />
            </summary>

            <div class="lef-grid">
                @if($showProof)
                <div class="lef-f{{ $bad('id_proof_name') }}">
                    <label for="demo_id_proof_name">ID proof type</label>
                    <div class="lef-in is-select"><x-lb-icon name="id-card" />
                        <select name="id_proof_name" id="demo_id_proof_name">
                            <option value="">Select ID proof</option>
                            @foreach(['1' => 'Aadhar Card', '2' => 'Driving License', '4' => 'Pan Card', '5' => 'Voter Id', '3' => 'Other'] as $k => $v)
                                <option value="{{ $k }}" @selected((string) old('id_proof_name') === (string) $k)>{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                    @error('id_proof_name')<p class="lef-err">{{ $message }}</p>@enderror
                </div>
                <div class="lef-f{{ $bad('id_proof_number') }}">
                    <label for="demo_id_proof_number">ID proof number</label>
                    <div class="lef-in"><x-lb-icon name="hash" /><input type="text" name="id_proof_number" id="demo_id_proof_number" value="{{ old('id_proof_number') }}" placeholder="ID number" maxlength="12" autocomplete="off"></div>
                    @error('id_proof_number')<p class="lef-err">{{ $message }}</p>@enderror
                </div>
                <div class="lef-f is-full{{ $bad('id_proof_file') }}">
                    <label>ID proof scan <span>JPG, PNG, WEBP or PDF</span></label>
                    <div class="lef-drop doc-dropzone disabled" id="docDropzone" role="button" tabindex="0" aria-describedby="docDropzoneHint">
                        <div class="lef-drop-in" id="docDropzoneContent">
                            <span class="lef-drop-ic"><x-lb-icon name="upload" class="lef-ic-up" /><x-lb-icon name="lock" class="lef-ic-lock" /></span>
                            <div>
                                <strong class="lef-drop-t-on">Drop the file here or <u>browse</u></strong>
                                <strong class="lef-drop-t-off">Choose the ID proof type first</strong>
                                <small id="docDropzoneHint">Select ID proof type above to enable upload</small>
                            </div>
                        </div>
                        <div class="lef-drop-file" id="docDropzonePreview" style="display:none;">
                            <span class="lef-drop-thumb" id="docThumb"><x-lb-icon name="file-text" /></span>
                            <div class="lef-drop-meta">
                                <strong id="docFileName"></strong>
                                <small id="docFileMeta"></small>
                            </div>
                            <div class="lef-drop-acts">
                                <button type="button" class="lef-chip-btn" id="removeDocFile"><x-lb-icon name="trash" /><span>Remove</span></button>
                            </div>
                        </div>
                    </div>
                    <input type="file" class="d-none id_proof_file image-cropper" name="id_proof_file" id="demo_id_proof_file" disabled autocomplete="off" accept=".jpeg, .jpg, .png, .webp, .pdf">
                    <img class="preview-img one" alt="" style="display:none !important;">
                    @error('id_proof_file')<p class="lef-err">{{ $message }}</p>@enderror
                </div>
                @endif
                @if($showAddress)
                <div class="lef-f is-full{{ $bad('address') }}">
                    <label for="dm_address">Address</label>
                    <div class="lef-in is-area"><textarea name="address" id="dm_address" rows="2" maxlength="500" placeholder="House, street, city">{{ old('address') }}</textarea></div>
                    @error('address')<p class="lef-err">{{ $message }}</p>@enderror
                </div>
                @endif
            </div>
        </details>
        @endif

        <div class="lpg-save ldm-save">
            <div class="ldm-sum" id="saveSummary">
                <strong id="sumAmount">₹0</strong>
                <span id="sumText">Fill the plan to see the fee</span>
            </div>
            <button type="button" id="btnResetInquiry" class="lp-btn lp-btn--ghost" title="Clear the form"><x-lb-icon name="rotate-ccw" /><span class="ldm-hide-sm">Clear</span></button>
            <button type="submit" class="lp-btn"><x-lb-icon name="circle-check" />Book demo</button>
        </div>
    </form>
</div>

<script>
$(function () {
    var form = document.getElementById('demoInquiryForm');
    var branchId = $('#branch_id').val();
    var oldPlanTypeId = @json(old('plan_type_id'));
    var priceReq = null;

    function seatMode() { return $('input[name="general_seat"]:checked').val(); }
    function money(n) { n = parseFloat(n); return isNaN(n) ? '0' : n.toLocaleString('en-IN', { maximumFractionDigits: 2 }); }

    /* ---------- photo (shared layout cropper: .image-cropper + sibling .preview-img) ---------- */
    function setAvatar(src, title) {
        if (!src) return;
        $('#avatarCroppedPreview').attr('src', src).show();
        $('#avatarDefaultIcon').hide();
        $('#avatarStatusTitle').text(title || 'Photo selected');
        $('#avatarSubtext').text('Tap the photo to change it.');
    }
    $('#avatarUploadTrigger').on('click', function (e) { e.preventDefault(); $('#dm_photo').trigger('click'); });
    $('#dm_photo').on('change', function () {
        var file = this.files && this.files[0];
        if (!file) return;
        var r = new FileReader();
        r.onload = function (e) { setAvatar(e.target.result, 'Photo selected'); };
        r.readAsDataURL(file);
    });
    var pPrev = document.querySelector('#dm_photo ~ .preview-img');
    if (pPrev) {
        new MutationObserver(function () {
            pPrev.style.setProperty('display', 'none', 'important');
            var src = pPrev.getAttribute('src');
            if (src && src.indexOf('blob:') === 0) setAvatar(src, 'Photo ready');
        }).observe(pPrev, { attributes: true, attributeFilter: ['src', 'style'] });
    }

    /* ---------- seat + shift ---------- */
    function fillShifts(list) {
        var $s = $('#temp_plan_type_id'), keep = oldPlanTypeId || $s.val();
        $s.empty().append('<option value="">' + (list.length ? 'Choose shift' : 'No shift free') + '</option>');
        list.forEach(function (t) {
            $('<option>').val(t.id).text(t.name).prop('selected', String(t.id) === String(keep)).appendTo($s);
        });
        oldPlanTypeId = null;
        $('#shiftHint').text(list.length ? list.length + ' shift' + (list.length > 1 ? 's' : '') + ' available' : (seatMode() === 'no' ? 'This seat has no free shift. Pick another seat.' : 'No shift is set up yet.'));
        updatePrice();
    }
    function loadShifts() {
        var general = seatMode() === 'yes', seat = $('#seat_id2').val();
        $('#seatField').prop('hidden', general);
        $('#seat_id2').prop('disabled', general);
        if (!general && !seat) {
            $('#temp_plan_type_id').html('<option value="">Choose a seat first</option>');
            $('#shiftHint').text('');
            updatePrice();
            return;
        }
        $('#temp_plan_type_id').html('<option value="">Loading shifts…</option>');
        $.getJSON("{{ route('getPlantypeSeatwise') }}", { seatNo: general ? '' : seat, branchId: branchId })
            .done(function (data) { fillShifts($.isArray(data) ? data : []); })
            .fail(function () { $('#temp_plan_type_id').html('<option value="">Couldn\'t load shifts</option>'); });
    }
    $(form).on('change', 'input[name="general_seat"]', function () { $('#seat_id2').val(''); loadShifts(); });
    $('#seat_id2').on('change', loadShifts);
    loadShifts();

    /* ---------- price ---------- */
    function setAmount(v, note) {
        $('#plan_price').val(v === null ? '' : v);
        $('#amountText').text(money(v || 0));
        if (note !== undefined) $('#chargeable_day_book').text(note);
        $('#amountBox').toggleClass('has-value', v !== null && v !== '');
        summary();
    }
    function updatePrice() {
        var plan = $('#plan_id4').val(), shift = $('#temp_plan_type_id').val(), start = $('#dm_start').val();
        if (priceReq) priceReq.abort();
        if (!plan || !shift || !start) { setAmount(null, 'Choose a shift and plan to see the fee.'); return; }
        $('#amountBox').addClass('is-loading');
        $('#chargeable_day_book').text('Calculating…');
        var days = $.getJSON("{{ route('getChargeableDays') }}", { plan_id: plan, plan_start_date: start, branch_id: branchId });
        priceReq = $.post("{{ route('get.plan.price') }}", { _token: "{{ csrf_token() }}", plan_id: plan, plan_type_id: shift, branch_id: branchId, plan_start_date: start });
        priceReq.done(function (res) {
            if (res && res.success) {
                setAmount(res.price, parseFloat(res.price) > 0
                    ? 'For ' + $('#plan_id4 option:selected').text() + ', ' + $('#temp_plan_type_id option:selected').text()
                    : 'No price is set for this plan and shift yet.');
                days.done(function (d) { if (d && d.fixedBillingDate == 'true') $('#chargeable_day_book').text('Billed for ' + d.chargeable_days + ' days (fixed billing date)'); });
            } else {
                setAmount(null, (res && res.message) || 'No price is set for this plan and shift.');
            }
        }).fail(function (x, s) { if (s !== 'abort') setAmount(null, 'Couldn\'t get the price. Try again.'); })
          .always(function () { $('#amountBox').removeClass('is-loading'); });
    }
    $('#plan_id4, #temp_plan_type_id, #dm_start').on('change', updatePrice);

    /* ---------- payment mode hint + summary ---------- */
    var PAY = { paylater: 'Booking is saved now; collect the fee later.', offline: 'You received cash or UPI at the desk.', online: 'The learner pays online.' };
    function summary() {
        var mode = $('input[name="payment_mode"]:checked').val();
        $('#payHint').text(PAY[mode] || '');
        var v = $('#plan_price').val();
        $('#sumAmount').text('₹' + money(v || 0));
        var parts = [$('#temp_plan_type_id').val() && $('#temp_plan_type_id option:selected').text(), $('#plan_id4').val() && $('#plan_id4 option:selected').text(), { paylater: 'Pay later', offline: 'Cash / UPI', online: 'Online' }[mode]].filter(Boolean);
        $('#sumText').text(v ? parts.join(' · ') : 'Fill the plan to see the fee');
    }
    $(form).on('change', 'input[name="payment_mode"]', summary);
    summary();

    /* ---------- mobile digits left ---------- */
    $('#dm_mobile').on('input', function () {
        var l = this.value.length;
        $('#mobCount').text(l && l < 10 ? (10 - l) + ' more' : '');
    });

    /* ---------- ID proof upload (enabled once a type is chosen) ---------- */
    var proofUrl = null;
    function fmtSize(b) { return b > 1048576 ? (b / 1048576).toFixed(1) + ' MB' : Math.max(1, Math.round(b / 1024)) + ' KB'; }
    function showProof(name, meta, thumb) {
        var $t = $('#docThumb').empty();
        if (thumb) $('<img alt="ID proof">').attr('src', thumb).appendTo($t);
        else $t.html('<svg class="lb-i" aria-hidden="true"><use href="{{ asset('public/img/lb-icons.svg') }}?v={{ @filemtime(public_path('img/lb-icons.svg')) }}#i-file-text"></use></svg>');
        $('#docFileName').text(name); $('#docFileMeta').text(meta);
        $('#docDropzoneContent').hide(); $('#docDropzonePreview').css('display', 'flex');
        $('#docDropzone').addClass('has-file');
    }
    function clearProof() {
        $('#demo_id_proof_file').val('');
        $('#docDropzonePreview').hide(); $('#docDropzoneContent').show();
        $('#docDropzone').removeClass('has-file');
    }
    function proofState() {
        var on = !!$('#demo_id_proof_name').val(), $dz = $('#docDropzone');
        if (!$dz.length) return;
        $dz.toggleClass('disabled', !on).attr('aria-disabled', on ? 'false' : 'true');
        $('#demo_id_proof_file').prop('disabled', !on);
        $('#docDropzoneHint').text(on ? 'JPG, PNG, WEBP or PDF, up to 5 MB' : 'Pick Aadhar, PAN, Voter ID… above, then upload its scan');
        if (!on) clearProof();
    }
    function nudgeProof() {
        var $s = $('#demo_id_proof_name');
        $s.focus().closest('.lef-in').addClass('is-nudge');
        setTimeout(function () { $s.closest('.lef-in').removeClass('is-nudge'); }, 1400);
    }
    function openProof() { if ($('#docDropzone').hasClass('disabled')) { nudgeProof(); return; } $('#demo_id_proof_file').trigger('click'); }
    proofState();
    $('#demo_id_proof_name').on('change', proofState);
    $('#docDropzone').on('click', function (e) {
        if ($(e.target).closest('.lef-drop-acts').length || $(this).hasClass('has-file')) return;
        openProof();
    }).on('keydown', function (e) {
        if ((e.key === 'Enter' || e.key === ' ') && !$(this).hasClass('has-file')) { e.preventDefault(); openProof(); }
    });
    $('#removeDocFile').on('click', function (e) { e.preventDefault(); e.stopPropagation(); clearProof(); });
    $('#demo_id_proof_file').on('change', function () {
        var f = this.files && this.files[0];
        if (!f) return;
        if (proofUrl) URL.revokeObjectURL(proofUrl);
        proofUrl = URL.createObjectURL(f);
        var img = /^image\//i.test(f.type);
        showProof(f.name, (img ? 'Image' : 'PDF') + ' · ' + fmtSize(f.size), img ? proofUrl : null);
    });
    var dPrev = document.querySelector('.preview-img.one');
    if (dPrev) {
        new MutationObserver(function () {
            dPrev.style.setProperty('display', 'none', 'important');
            var src = dPrev.getAttribute('src'), inp = document.getElementById('demo_id_proof_file');
            if (src && src.indexOf('blob:') === 0 && inp.files && inp.files[0]) showProof($('#docFileName').text() || 'ID proof', 'Cropped image · ' + fmtSize(inp.files[0].size), src);
        }).observe(dPrev, { attributes: true, attributeFilter: ['src', 'style'] });
    }
    var dz = document.getElementById('docDropzone');
    if (dz) {
        ['dragenter', 'dragover'].forEach(function (ev) { dz.addEventListener(ev, function (e) { e.preventDefault(); if (!dz.classList.contains('disabled')) dz.classList.add('dragover'); }); });
        ['dragleave', 'dragend'].forEach(function (ev) { dz.addEventListener(ev, function (e) { e.preventDefault(); dz.classList.remove('dragover'); }); });
        dz.addEventListener('drop', function (e) {
            e.preventDefault(); dz.classList.remove('dragover');
            if (dz.classList.contains('disabled')) { nudgeProof(); return; }
            var f = e.dataTransfer && e.dataTransfer.files[0];
            if (!f) return;
            if (!/^(image\/(jpe?g|png|webp)|application\/pdf)$/i.test(f.type)) { if (window.lpToast) lpToast('Use a JPG, PNG, WEBP or PDF file', 'error'); return; }
            var inp = document.getElementById('demo_id_proof_file'), t = new DataTransfer();
            t.items.add(f); inp.files = t.files;
            inp.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }

    /* ---------- clear ---------- */
    $('#btnResetInquiry').on('click', function () {
        form.reset();
        $('#avatarCroppedPreview').attr('src', '').hide();
        $('#avatarDefaultIcon').show();
        $('#avatarStatusTitle').text('Profile photo');
        $('#avatarSubtext').text('Optional. A clear face photo for the ID card.');
        $('.preview-img').removeAttr('src');
        $('.lef-f.is-bad').removeClass('is-bad').find('.lef-err').remove();
        $('.ldm-alert').remove();
        $('#mobCount').text('');
        proofState();
        loadShifts();
    });

    /* ---------- check before sending ---------- */
    function flag(el, msg) {
        var f = $(el).closest('.lef-f');
        f.addClass('is-bad').find('.lef-err').remove();
        f.find('.lef-hint').first().before($('<p class="lef-err">').text(msg));
        if (!f.find('.lef-hint').length) f.append($('<p class="lef-err">').text(msg));
        return f[0];
    }
    $(form).on('input change', '.lef-f.is-bad input, .lef-f.is-bad select', function () {
        $(this).closest('.lef-f').removeClass('is-bad').find('.lef-err').remove();
    });
    form.addEventListener('submit', function (e) {
        var first = null, pick = function (el) { first = first || el; };
        if (!$('#dm_name').val().trim()) pick(flag('#dm_name', 'Enter the learner\'s name.'));
        var m = $('#dm_mobile').val();
        if (m.length < 8) pick(flag('#dm_mobile', 'Enter a valid WhatsApp number.'));
        if ($('#dm_email').length && $('#dm_email').val() && !/^\S+@\S+\.\S+$/.test($('#dm_email').val())) pick(flag('#dm_email', 'Enter a valid email or leave it empty.'));
        if (seatMode() === 'no' && !$('#seat_id2').val()) pick(flag('#seat_id2', 'Choose a seat number.'));
        if (!$('#temp_plan_type_id').val()) pick(flag('#temp_plan_type_id', 'Choose a shift.'));
        if (!$('#plan_id4').val()) pick(flag('#plan_id4', 'Choose a plan.'));
        if (!$('#dm_start').val()) pick(flag('#dm_start', 'Pick a start date.'));
        if (!first && !$('#plan_price').val()) {
            first = document.getElementById('amountBox');
            $('#amountBox').addClass('is-bad');
            $('#chargeable_day_book').text('No fee found for this plan and shift. Set a price in Plan prices first.');
        }
        if (first) {
            e.preventDefault();
            first.scrollIntoView({ behavior: 'smooth', block: 'center' });
            var inp = $(first).find('input:not([type=hidden]), select').get(0);
            if (inp) setTimeout(function () { inp.focus({ preventScroll: true }); }, 300);
            return;
        }
        form.querySelector('button[type="submit"]').classList.add('is-busy');
    });
});
</script>
@endsection
