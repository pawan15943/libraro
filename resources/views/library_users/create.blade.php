@extends('layouts.library')

@section('title', isset($editUser) ? 'Edit staff' : 'Add staff')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-opform.css') }}?v={{ @filemtime(public_path('css/lb-opform.css')) }}">
<link rel="stylesheet" href="{{ asset('public/css/lb-pages.css') }}?v={{ @filemtime(public_path('css/lb-pages.css')) }}">
<link rel="stylesheet" href="{{ asset('public/css/lb-staff.css') }}?v={{ @filemtime(public_path('css/lb-staff.css')) }}">
@endpush

@section('content')

@php
    $isEdit = isset($editUser);
    $roleInfo = [
        'admin_user' => ['Admin', 'Runs the desk day to day', 'crown'],
        'library_manager' => ['Manager', 'Looks after learners and fees', 'user-cog'],
        'swapper' => ['Seat swapper', 'Moves learners between seats', 'arrow-left-right'],
    ];
    $currentRole = old('role', $isEdit ? optional($editUser->roles->first())->name : '');
    $activeBranches = $branches->where('status', 1);
    $selectedBranches = array_map('strval', (array) old('branch', $isEdit ? ($editUser->branch_id ?? []) : ($activeBranches->count() === 1 ? [$activeBranches->first()->id] : [])));
    $photo = $isEdit && $editUser->profile_picture ? asset('storage/app/public/' . $editUser->profile_picture) : null;
@endphp

<div class="lpg lpg--narrow lst-form">
    <header class="lpg-head">
        <div class="lpg-head-t">
            <div class="lpg-crumb">
                <a href="{{ route('library.home') }}">Dashboard</a><x-lb-icon name="chevron-right" />
                <a href="{{ route('library-users.index') }}">Staff</a><x-lb-icon name="chevron-right" />
                <span>{{ $isEdit ? 'Edit' : 'Add' }}</span>
            </div>
            <h1>{{ $isEdit ? 'Edit ' . $editUser->name : 'Add staff' }}</h1>
            <p>{{ $isEdit ? 'Update their details, branches or password.' : 'They get their own sign in. You choose what they can do on the next step.' }}</p>
        </div>
    </header>

    @unless($isEdit)
        <ol class="lpg-steps lst-flow" aria-label="Steps">
            <li class="is-on"><span><i>1</i>Details</span></li>
            <li><span><i>2</i>Access</span></li>
            <li><span><i>3</i>Share sign in</span></li>
        </ol>
    @endunless

    <form id="userSubmitForm" novalidate>
        @csrf
        <input type="hidden" name="id" value="{{ $editUser->id ?? '' }}">

        {{-- Person --}}
        <section class="lef-sec">
            <div class="lef-sec-h">
                <span class="lef-sec-ic is-blue"><x-lb-icon name="user-round" /></span>
                <div><h4>Who they are</h4><p>Shown in your staff list and on what they record.</p></div>
            </div>

            <div class="lef-photo lst-photo">
                <button type="button" class="lef-photo-ava" id="photoPick" aria-label="Add photo">
                    <span class="lef-photo-circle">
                        <span class="lef-photo-ph" id="photoPh" @if($photo) style="display:none" @endif><x-lb-icon name="user-round" /></span>
                        <img id="photoImg" src="{{ $photo ?? '' }}" alt="" @unless($photo) style="display:none" @endunless onerror="this.style.display='none';document.getElementById('photoPh').style.display='';">
                    </span>
                    <span class="lef-photo-cam"><x-lb-icon name="camera" /></span>
                </button>
                <div class="lef-photo-t">
                    <strong id="photoTitle">{{ $photo ? 'Photo on file' : 'Photo' }}</strong>
                    <p>Optional. A clear face photo, JPG, PNG or WEBP.</p>
                </div>
                <input type="file" class="d-none image-cropper" name="profile_picture" id="staffPhoto" accept=".jpg,.jpeg,.png,.webp">
                <img class="preview-img" alt="" style="display:none !important;">
            </div>
            <p class="lef-err" data-err="profile_picture" hidden></p>

            <div class="lef-grid">
                <div class="lef-f is-full" data-field="name">
                    <label for="st_name">Full name <i>*</i></label>
                    <div class="lef-in"><x-lb-icon name="user-round" /><input type="text" class="char-only" name="name" id="st_name" value="{{ old('name', $editUser->name ?? '') }}" placeholder="e.g. Rahul Verma" maxlength="255" autocomplete="off"></div>
                    <p class="lef-err" data-err="name" hidden></p>
                </div>
                <div class="lef-f" data-field="mobile">
                    <label for="st_mobile">Mobile (WhatsApp) <i>*</i></label>
                    <div class="lef-in"><span class="lef-pre">+91</span><input type="text" class="digit-only" name="mobile" id="st_mobile" value="{{ old('mobile', $editUser->mobile ?? '') }}" placeholder="10 digit number" maxlength="10" inputmode="numeric" autocomplete="off"></div>
                    <p class="lef-err" data-err="mobile" hidden></p>
                </div>
                <div class="lef-f" data-field="email">
                    <label for="st_email">Email <i>*</i><span>Used to sign in</span></label>
                    <div class="lef-in"><x-lb-icon name="mail" /><input type="email" name="email" id="st_email" value="{{ old('email', $editUser->email ?? '') }}" placeholder="name@example.com" inputmode="email" autocomplete="off" autocapitalize="off"></div>
                    <p class="lef-err" data-err="email" hidden></p>
                </div>
            </div>
        </section>

        {{-- Role + branches --}}
        <section class="lef-sec">
            <div class="lef-sec-h">
                <span class="lef-sec-ic is-green"><x-lb-icon name="building" /></span>
                <div><h4>Role &amp; branches</h4><p>Where they work and what they're called in your team.</p></div>
            </div>

            <div class="lef-f" data-field="role">
                <label>Role <i>*</i></label>
                <div class="lst-pick lst-pick--roles" role="radiogroup" aria-label="Role">
                    @foreach($roles as $role)
                        @php [$label, $desc, $icon] = $roleInfo[$role->name] ?? [ucwords(str_replace('_', ' ', $role->name)), '', 'user-round']; @endphp
                        <label class="lst-opt">
                            <input type="radio" name="role" value="{{ $role->name }}" @checked($currentRole === $role->name)>
                            <span class="lst-opt-b">
                                <span class="lst-opt-ic"><x-lb-icon name="{{ $icon }}" /></span>
                                <span class="lst-opt-t"><b>{{ $label }}</b>@if($desc)<small>{{ $desc }}</small>@endif</span>
                                <i class="lst-opt-tick"><x-lb-icon name="check" /></i>
                            </span>
                        </label>
                    @endforeach
                </div>
                <p class="lef-hint">The role is a label. What they can actually do is chosen on the access step.</p>
                <p class="lef-err" data-err="role" hidden></p>
            </div>

            <div class="lef-f" data-field="branch">
                <label>Branches <i>*</i>@if($activeBranches->count() > 1)<button type="button" class="lst-link" id="allBranches">Select all</button>@endif</label>
                <div class="lst-pick lst-pick--chips">
                    @foreach($branches as $branch)
                        <label class="lst-chip {{ $branch->status ? '' : 'is-off' }}">
                            <input type="checkbox" name="branch[]" value="{{ $branch->id }}" @checked(in_array((string) $branch->id, $selectedBranches, true)) @disabled(!$branch->status)>
                            <span><x-lb-icon name="check" />{{ $branch->display_name ?: $branch->name }}@unless($branch->status)<em>Inactive</em>@endunless</span>
                        </label>
                    @endforeach
                </div>
                <p class="lef-err" data-err="branch" hidden></p>
            </div>
        </section>

        {{-- Password --}}
        <section class="lef-sec">
            <div class="lef-sec-h">
                <span class="lef-sec-ic is-amber"><x-lb-icon name="key-round" /></span>
                <div><h4>Password</h4><p>{{ $isEdit ? 'Leave empty to keep their current password.' : 'They sign in with their email and this password.' }}</p></div>
                <button type="button" class="lef-btn-soft" id="genPass"><x-lb-icon name="sparkles" />Suggest</button>
            </div>
            <div class="lef-grid">
                <div class="lef-f" data-field="password">
                    <label for="st_pass">{{ $isEdit ? 'New password' : 'Password' }} @unless($isEdit)<i>*</i>@endunless</label>
                    <div class="lef-in"><x-lb-icon name="lock" /><input type="password" name="password" id="st_pass" autocomplete="new-password" placeholder="At least 6 characters">
                        <button type="button" class="lst-eye" data-eye="st_pass" aria-label="Show password"><x-lb-icon name="eye" /></button></div>
                    <p class="lef-err" data-err="password" hidden></p>
                </div>
                <div class="lef-f" data-field="password_confirmation">
                    <label for="st_pass2">Type it again @unless($isEdit)<i>*</i>@endunless</label>
                    <div class="lef-in"><x-lb-icon name="lock" /><input type="password" name="password_confirmation" id="st_pass2" autocomplete="new-password" placeholder="Same password">
                        <button type="button" class="lst-eye" data-eye="st_pass2" aria-label="Show password"><x-lb-icon name="eye" /></button></div>
                    <p class="lef-err" data-err="password_confirmation" hidden></p>
                </div>
            </div>
            <p class="lef-hint" id="passHint"></p>
        </section>

        <div class="lpg-save">
            <p>{{ $isEdit ? 'Changes apply the next time they open a page.' : 'Next: choose what they can do.' }}</p>
            <a href="{{ route('library-users.index') }}" class="lp-btn lp-btn--ghost">Cancel</a>
            <button type="submit" class="lp-btn" id="submit_id"><x-lb-icon name="{{ $isEdit ? 'circle-check' : 'arrow-right' }}" />{{ $isEdit ? 'Save changes' : 'Save & choose access' }}</button>
        </div>
    </form>
</div>

<script>
$(function () {
    var form = document.getElementById('userSubmitForm');
    var isEdit = {{ $isEdit ? 'true' : 'false' }};

    /* ---------- photo (shared layout cropper: .image-cropper + sibling .preview-img) ---------- */
    function showPhoto(src, title) {
        $('#photoImg').attr('src', src).show();
        $('#photoPh').hide();
        $('#photoTitle').text(title);
    }
    $('#photoPick').on('click', function () { $('#staffPhoto').trigger('click'); });
    $('#staffPhoto').on('change', function () {
        var f = this.files && this.files[0];
        if (f && /^image\//.test(f.type)) showPhoto(URL.createObjectURL(f), 'New photo selected');
    });
    var prev = document.querySelector('#staffPhoto ~ .preview-img');
    if (prev) {
        new MutationObserver(function () {
            prev.style.setProperty('display', 'none', 'important');
            var src = prev.getAttribute('src');
            if (src && src.indexOf('blob:') === 0) showPhoto(src, 'New photo ready');
        }).observe(prev, { attributes: true, attributeFilter: ['src', 'style'] });
    }

    /* ---------- branches ---------- */
    $('#allBranches').on('click', function () {
        var boxes = $('input[name="branch[]"]:enabled'), all = boxes.length === boxes.filter(':checked').length;
        boxes.prop('checked', !all).trigger('change');
        $(this).text(all ? 'Select all' : 'Clear');
    });

    /* ---------- password: show / suggest ---------- */
    $('.lst-eye').on('click', function () {
        var inp = document.getElementById($(this).data('eye')), show = inp.type === 'password';
        inp.type = show ? 'text' : 'password';
        $(this).attr('aria-label', show ? 'Hide password' : 'Show password').toggleClass('is-on', show);
    });
    $('#genPass').on('click', function () {
        var chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789', p = '';
        var rnd = window.crypto && crypto.getRandomValues ? crypto.getRandomValues(new Uint32Array(10)) : null;
        for (var i = 0; i < 10; i++) p += chars[(rnd ? rnd[i] : Math.floor(Math.random() * 1e9)) % chars.length];
        $('#st_pass, #st_pass2').val(p).attr('type', 'text').trigger('input');
        $('.lst-eye').addClass('is-on');
        $('#passHint').text('Note this password down and share it with them. It won\'t be shown again.').addClass('is-warn');
    });

    /* ---------- errors ---------- */
    function clearErr(key) {
        var $f = $('[data-field="' + key + '"]');
        $f.removeClass('is-bad');
        $('[data-err="' + key + '"]').prop('hidden', true).text('');
    }
    function showErr(key, msg) {
        $('[data-field="' + key + '"]').addClass('is-bad');
        $('[data-err="' + key + '"]').text(msg).prop('hidden', false);
    }
    $(form).on('input change', 'input', function () {
        var key = (this.name || '').replace('[]', '');
        if (key) clearErr(key);
    });
    function firstErr() {
        var el = form.querySelector('.is-bad, .lef-err:not([hidden])');
        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
    function check() {
        var ok = true, v = function (n) { return ($.trim($('[name="' + n + '"]').val() || '')); };
        $('.lef-err').prop('hidden', true); $('.is-bad').removeClass('is-bad');
        if (!v('name')) { showErr('name', 'Enter their name.'); ok = false; }
        if (!/^\d{10}$/.test(v('mobile'))) { showErr('mobile', 'Enter a 10 digit mobile number.'); ok = false; }
        if (!/^\S+@\S+\.\S+$/.test(v('email'))) { showErr('email', 'Enter a valid email. They sign in with it.'); ok = false; }
        if (!$('input[name="role"]:checked').length) { showErr('role', 'Choose a role.'); ok = false; }
        if (!$('input[name="branch[]"]:checked').length) { showErr('branch', 'Choose at least one branch.'); ok = false; }
        var p = $('#st_pass').val(), p2 = $('#st_pass2').val();
        if (!isEdit || p || p2) {
            if (p.length < 6) { showErr('password', 'Use at least 6 characters.'); ok = false; }
            else if (p !== p2) { showErr('password_confirmation', 'The two passwords don\'t match.'); ok = false; }
        }
        return ok;
    }

    /* ---------- save ---------- */
    $(form).on('submit', function (e) {
        e.preventDefault();
        if (!check()) { firstErr(); return; }
        var $btn = $('#submit_id').addClass('is-busy');
        $.ajax({ url: "{{ route('library-users.store') }}", method: 'POST', data: new FormData(form), contentType: false, processData: false })
            .done(function (res) {
                if (res.success) {
                    try { sessionStorage.setItem('flash_message', res.message); sessionStorage.setItem('flash_type', 'success'); } catch (x) {}
                    window.location.href = res.redirect;
                } else {
                    $btn.removeClass('is-busy');
                    if (window.lpToast) lpToast(res.message || 'Couldn\'t save.', 'error');
                }
            })
            .fail(function (xhr) {
                $btn.removeClass('is-busy');
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    $.each(xhr.responseJSON.errors, function (key, msgs) { showErr(key.split('.')[0], msgs[0]); });
                    firstErr();
                } else if (window.lpToast) {
                    lpToast((xhr.responseJSON && xhr.responseJSON.message) || 'Something went wrong. Please try again.', 'error');
                }
            });
    });
});
</script>
@endsection
