@extends('layouts.library')

@section('title', 'Staff')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-pages.css') }}?v={{ @filemtime(public_path('css/lb-pages.css')) }}">
<link rel="stylesheet" href="{{ asset('public/css/lb-staff.css') }}?v={{ @filemtime(public_path('css/lb-staff.css')) }}">
@endpush

@section('content')

@php
    $roleLabel = fn ($r) => ['admin_user' => 'Admin', 'library_manager' => 'Manager', 'swapper' => 'Seat swapper'][$r] ?? ucwords(str_replace('_', ' ', (string) $r));
    $initials = fn ($n) => collect(explode(' ', trim((string) $n)))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') ?: 'S';
    $canAdd = Gate::allows('has-permission', 'Add User Master');
    $loginUrl = route('login.library');
    $noAccess = $users->where('status', 1)->where('access_count', 0)->count();
@endphp

<div class="lpg lst">
    <header class="lpg-head">
        <div class="lpg-head-t">
            <div class="lpg-crumb"><a href="{{ route('library.home') }}">Dashboard</a><x-lb-icon name="chevron-right" /><span>Staff</span></div>
            <h1>Staff</h1>
            <p>People who help you run the library. Each one signs in with their own email and sees only what you allow.</p>
        </div>
        @if($canAdd && $users->isNotEmpty())
            <div class="lpg-head-r">
                <a href="{{ route('library-users.create') }}" class="lp-btn"><x-lb-icon name="user-plus" />Add staff</a>
            </div>
        @endif
    </header>

    @if($users->isEmpty())
        <section class="lpg-card lst-empty">
            <span class="lst-empty-ic"><x-lb-icon name="users-round" /></span>
            <h2>Add your first staff member</h2>
            <p>Give your team their own sign in. Choose which branches they work at and what they can do: book seats, collect fees, mark attendance and more.</p>
            <ol class="lst-steps">
                <li><i>1</i>Add their name, number and a password</li>
                <li><i>2</i>Choose what they can do</li>
                <li><i>3</i>Share the sign in link on WhatsApp</li>
            </ol>
            @if($canAdd)
                <a href="{{ route('library-users.create') }}" class="lp-btn"><x-lb-icon name="user-plus" />Add staff</a>
            @else
                <p class="lst-note">You don't have permission to add staff.</p>
            @endif
        </section>
    @else
        <div class="lpg-stats lst-stats">
            <div class="lpg-stat">
                <span class="lpg-stat-ic"><x-lb-icon name="users" /></span>
                <span>Staff</span><b>{{ $total_user }}</b>
            </div>
            <div class="lpg-stat is-ok">
                <span class="lpg-stat-ic"><x-lb-icon name="user-check" /></span>
                <span>Can sign in</span><b>{{ $active_user }}</b>
            </div>
            <div class="lpg-stat {{ $inactive_user ? 'is-bad' : '' }}">
                <span class="lpg-stat-ic"><x-lb-icon name="user-x" /></span>
                <span>Turned off</span><b>{{ $inactive_user }}</b>
            </div>
        </div>

        @if($noAccess)
            <div class="lst-warn" role="status">
                <x-lb-icon name="circle-alert" />
                <p><b>{{ $noAccess === 1 ? '1 staff member has' : $noAccess . ' staff members have' }} no access yet.</b> They can sign in but won't see anything until you choose what they can do.</p>
            </div>
        @endif

        <div class="lpg-tools lst-tools">
            <label class="lpg-search">
                <x-lb-icon name="search" />
                <input type="search" id="staffSearch" placeholder="Search name, number or email" autocomplete="off">
            </label>
            <ul class="lpg-seg" role="tablist" aria-label="Filter staff">
                <li><button type="button" class="lpg-seg-b is-on" data-filter="all">All<span class="lpg-n">{{ $total_user }}</span></button></li>
                <li><button type="button" class="lpg-seg-b" data-filter="1">Active<span class="lpg-n">{{ $active_user }}</span></button></li>
                <li><button type="button" class="lpg-seg-b" data-filter="0">Off<span class="lpg-n">{{ $inactive_user }}</span></button></li>
            </ul>
        </div>

        <div class="lst-grid" id="staffGrid">
            @foreach($users as $user)
                @php
                    $role = optional($user->roles->first())->name;
                    $pct = $plan_permission_count ? round($user->access_count / $plan_permission_count * 100) : 0;
                    $photo = $user->profile_picture ? asset('storage/app/public/' . $user->profile_picture) : null;
                    $share = "Hi {$user->name}, your Libraro staff login is ready.\nSign in: {$loginUrl}\nEmail: {$user->email}\nUse the password I gave you.";
                @endphp
                <article class="lst-card {{ $user->status ? '' : 'is-off' }}" data-status="{{ (int) $user->status }}"
                         data-search="{{ mb_strtolower($user->name . ' ' . $user->mobile . ' ' . $user->email . ' ' . $roleLabel($role)) }}">
                    <div class="lst-top">
                        <span class="lst-ava">
                            @if($photo)<img src="{{ $photo }}" alt="" onerror="this.remove()">@endif
                            <b>{{ $initials($user->name) }}</b>
                        </span>
                        <div class="lst-id">
                            <h3>{{ $user->name }}</h3>
                            <div class="lst-tags">
                                @if($role)<span class="lpg-tag is-acc">{{ $roleLabel($role) }}</span>@endif
                                <span class="lpg-tag lst-state {{ $user->status ? 'is-ok' : 'is-bad' }}">{{ $user->status ? 'Active' : 'Turned off' }}</span>
                            </div>
                        </div>
                    </div>

                    <dl class="lst-facts">
                        <div>
                            <dt><x-lb-icon name="phone" /></dt>
                            <dd>@if($user->mobile)<a href="tel:+91{{ $user->mobile }}">+91 {{ $user->mobile }}</a>@else — @endif</dd>
                        </div>
                        <div>
                            <dt><x-lb-icon name="mail" /></dt>
                            <dd class="lst-mail">{{ $user->email }}</dd>
                        </div>
                        <div>
                            <dt><x-lb-icon name="building" /></dt>
                            <dd class="lst-branches">
                                @forelse($user->branch_names as $b)
                                    <span>{{ $b }}</span>
                                @empty
                                    <em>No branch</em>
                                @endforelse
                            </dd>
                        </div>
                    </dl>

                    <div class="lst-access {{ $user->access_count ? '' : 'is-none' }}">
                        @if($user->access_count)
                            <div class="lst-access-h">
                                <span>Access</span>
                                <b>{{ $user->access_count }} of {{ $plan_permission_count }} actions</b>
                            </div>
                            <div class="lst-bar"><i style="width: {{ max(3, $pct) }}%"></i></div>
                            <details class="lst-cats">
                                <summary>See what they can do <x-lb-icon name="chevron-down" /></summary>
                                <ul>
                                    @foreach($user->access_by_category as $catId => $n)
                                        <li><span>{{ $categoryNames[$catId] ?? 'Other' }}</span><b>{{ $n }}</b></li>
                                    @endforeach
                                </ul>
                            </details>
                        @else
                            <x-lb-icon name="lock" />
                            <p><b>No access yet</b>They can't see or do anything until you choose.</p>
                        @endif
                    </div>

                    <div class="lst-acts">
                        <a href="{{ route('library-users.permissions', $user->id) }}" class="lst-btn {{ $user->access_count ? '' : 'is-primary' }}"><x-lb-icon name="key-round" />{{ $user->access_count ? 'Access' : 'Give access' }}</a>
                        <a href="{{ route('library-users.create', $user->id) }}" class="lst-btn"><x-lb-icon name="pencil" />Edit</a>
                        <a href="https://wa.me/91{{ $user->mobile }}?text={{ rawurlencode($share) }}" target="_blank" rel="noopener" class="lst-ibtn is-wa" title="Share sign in details on WhatsApp" aria-label="Share sign in details with {{ $user->name }} on WhatsApp"><x-lb-brand name="whatsapp" /></a>
                        <button type="button" class="lst-ibtn toggle-status" data-id="{{ $user->id }}" data-name="{{ $user->name }}"
                                title="{{ $user->status ? 'Turn off sign in' : 'Turn on sign in' }}" aria-label="{{ $user->status ? 'Turn off' : 'Turn on' }} sign in for {{ $user->name }}"><x-lb-icon name="power" /></button>
                        <button type="button" class="lst-ibtn is-danger user-delete-btn" data-id="{{ $user->id }}" data-name="{{ $user->name }}" data-table="LibraryUser"
                                title="Delete" aria-label="Delete {{ $user->name }}"><x-lb-icon name="trash" /></button>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="lpg-empty lst-nomatch" id="staffNoMatch" hidden>
            <span><x-lb-icon name="user-search" /></span>
            <b>No staff match</b>
            <p>Try another name or number, or clear the filter.</p>
        </div>

        <section class="lpg-card lst-login">
            <span class="lpg-ic is-blue"><x-lb-icon name="log-in" /></span>
            <div class="lst-login-t">
                <b>How staff sign in</b>
                <p>Open this link and sign in with the email and password you set for them.</p>
                <code id="loginUrl">{{ $loginUrl }}</code>
            </div>
            <button type="button" class="lst-btn" id="copyLogin"><x-lb-icon name="copy" />Copy link</button>
        </section>
    @endif
</div>

<script>
$(function () {
    function toast(msg, type) { if (window.lpToast) lpToast(msg, type); else if (window.toastr) toastr[type === 'error' ? 'error' : 'success'](msg); }

    // Message carried over from the add / edit page
    try {
        var flash = sessionStorage.getItem('flash_message');
        if (flash) { toast(flash, sessionStorage.getItem('flash_type') === 'error' ? 'error' : 'success'); }
        sessionStorage.removeItem('flash_message'); sessionStorage.removeItem('flash_type');
    } catch (e) {}

    /* ---------- search + filter ---------- */
    var filter = 'all';
    function apply() {
        var q = ($('#staffSearch').val() || '').trim().toLowerCase(), shown = 0;
        $('#staffGrid .lst-card').each(function () {
            var ok = (filter === 'all' || String($(this).data('status')) === filter) && (!q || String($(this).data('search')).indexOf(q) !== -1);
            this.hidden = !ok;
            shown += ok ? 1 : 0;
        });
        $('#staffNoMatch').prop('hidden', shown > 0);
    }
    $('#staffSearch').on('input', apply);
    $('.lst-tools [data-filter]').on('click', function () {
        filter = String($(this).data('filter'));
        $('.lst-tools [data-filter]').removeClass('is-on');
        $(this).addClass('is-on');
        apply();
    });

    /* ---------- turn sign in on / off ---------- */
    function paintStatus($card, on) {
        $card.toggleClass('is-off', !on).attr('data-status', on ? 1 : 0).data('status', on ? 1 : 0);
        $card.find('.lst-state').toggleClass('is-ok', on).toggleClass('is-bad', !on).text(on ? 'Active' : 'Turned off');
        $card.find('.toggle-status').attr('title', on ? 'Turn off sign in' : 'Turn on sign in');
        apply();
    }
    $('.toggle-status').on('click', function () {
        var $b = $(this), $card = $b.closest('.lst-card'), on = String($card.data('status')) === '1', name = $b.data('name');
        var go = function () {
            $b.prop('disabled', true);
            $.post("{{ url('library-users/toggle-status') }}/" + $b.data('id'), { _token: "{{ csrf_token() }}" })
                .done(function (res) { paintStatus($card, String(res.status) === '1'); toast(res.message, 'success'); })
                .fail(function (x) { toast((x.responseJSON && x.responseJSON.message) || 'Something went wrong.', 'error'); })
                .always(function () { $b.prop('disabled', false); });
        };
        if (!on) { go(); return; }
        Swal.fire({
            title: 'Turn off ' + name + '?',
            text: 'They will be signed out and can\'t sign in until you turn them on again. Nothing is deleted.',
            icon: 'warning', showCancelButton: true, reverseButtons: true,
            confirmButtonText: 'Turn off', cancelButtonText: 'Cancel', confirmButtonColor: '#c0362c'
        }).then(function (r) { if (r.isConfirmed) go(); });
    });

    /* ---------- delete ---------- */
    $('.user-delete-btn').on('click', function () {
        var $b = $(this), name = $b.data('name');
        Swal.fire({
            title: 'Delete ' + name + '?',
            text: 'They lose their sign in for good. To stop them for a while, turn them off instead.',
            icon: 'warning', showCancelButton: true, reverseButtons: true,
            confirmButtonText: 'Delete', cancelButtonText: 'Cancel', confirmButtonColor: '#c0362c'
        }).then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({
                url: '{{ route("master.delete", ":id") }}'.replace(':id', $b.data('id')),
                type: 'DELETE', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }, data: { table: $b.data('table') }
            }).done(function (res) {
                if (res.status === 'success') {
                    toast(name + ' is deleted.', 'success');
                    $b.closest('.lst-card').fadeOut(200, function () { $(this).remove(); apply(); });
                } else { toast(res.message || 'Couldn\'t delete.', 'error'); }
            }).fail(function (x) { toast((x.responseJSON && x.responseJSON.message) || 'Something went wrong.', 'error'); });
        });
    });

    /* ---------- copy sign in link ---------- */
    $('#copyLogin').on('click', function () {
        var url = $('#loginUrl').text().trim();
        var done = function () { toast('Sign in link copied', 'success'); };
        if (navigator.clipboard) navigator.clipboard.writeText(url).then(done, function () { window.prompt('Copy this link', url); });
        else window.prompt('Copy this link', url);
    });
});
</script>
@endsection
