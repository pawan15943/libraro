@extends('layouts.library')

@section('title', 'Staff access')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-opform.css') }}?v={{ @filemtime(public_path('css/lb-opform.css')) }}">
<link rel="stylesheet" href="{{ asset('public/css/lb-pages.css') }}?v={{ @filemtime(public_path('css/lb-pages.css')) }}">
<link rel="stylesheet" href="{{ asset('public/css/lb-staff.css') }}?v={{ @filemtime(public_path('css/lb-staff.css')) }}">
@endpush

@section('content')

@php
    $granted = $user->permissions->pluck('id')->map(fn ($id) => (int) $id)->all();
    $first = strtok(trim($user->name), ' ') ?: $user->name;
    $isNew = request()->boolean('new');
    $initials = collect(explode(' ', trim((string) $user->name)))->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->implode('') ?: 'S';
    $photo = $user->profile_picture ? asset('storage/app/public/' . $user->profile_picture) : null;
    $total = $groupedPermissions->flatten()->count();
    // Known categories first, in their own order; uncategorised last
    $groups = $groupedPermissions->sortBy(fn ($p, $cat) => $cat === '' || $cat === null ? PHP_INT_MAX : (int) $cat);
@endphp

<div class="lpg lpg--narrow lst-perm">
    <header class="lpg-head">
        <div class="lpg-head-t">
            <div class="lpg-crumb">
                <a href="{{ route('library.home') }}">Dashboard</a><x-lb-icon name="chevron-right" />
                <a href="{{ route('library-users.index') }}">Staff</a><x-lb-icon name="chevron-right" />
                <span>Access</span>
            </div>
            <h1>What can {{ $first }} do?</h1>
            <p>Turn on only what they need. Changes apply the next time they open a page.</p>
        </div>
    </header>

    @if($isNew)
        <ol class="lpg-steps lst-flow" aria-label="Steps">
            <li class="is-done"><span><i>1</i>Details</span></li>
            <li class="is-on"><span><i>2</i>Access</span></li>
            <li><span><i>3</i>Share sign in</span></li>
        </ol>
    @endif

    <form method="POST" action="{{ route('library-users.permissions.update', $user->id) }}" id="permissionsForm">
        @csrf

        <section class="lpg-card lst-who">
            <span class="lst-ava">
                @if($photo)<img src="{{ $photo }}" alt="" onerror="this.remove()">@endif
                <b>{{ $initials }}</b>
            </span>
            <div class="lst-who-t">
                <b>{{ $user->name }}</b>
                <small>{{ $user->email }}</small>
            </div>
            <div class="lst-who-n"><b id="permSelectedCount">0</b><small>of {{ $total }} on</small></div>
        </section>

        <div class="lst-quick">
            <label class="lpg-search">
                <x-lb-icon name="search" />
                <input type="search" id="permSearch" placeholder="Find an action, e.g. renew, attendance" autocomplete="off">
            </label>
            <div class="lst-quick-b">
                @if($teammates->isNotEmpty())
                    <label class="lst-copy">
                        <x-lb-icon name="copy" />
                        <select id="copyFrom" aria-label="Copy access from a teammate">
                            <option value="">Same as…</option>
                            @foreach($teammates as $t)
                                <option value="{{ $t['id'] }}" data-ids="{{ $t['ids']->implode(',') }}">{{ $t['name'] }} ({{ $t['ids']->count() }})</option>
                            @endforeach
                        </select>
                    </label>
                @endif
                <button type="button" class="lst-btn" id="checkAllPermissions" data-on="1"><x-lb-icon name="list-checks" />Everything</button>
                <button type="button" class="lst-btn" id="clearAll"><x-lb-icon name="x" />Nothing</button>
            </div>
        </div>

        <div class="lst-cats-list">
            @foreach($groups as $categoryId => $permissions)
                @php $categoryName = $categoryNames[$categoryId] ?? 'Other'; @endphp
                @php $catOpen = collect($permissions)->contains(fn ($id) => in_array((int) $id, $granted, true)) || (empty($granted) && $loop->first); @endphp
                <details class="lst-cat" data-perm-section @if($catOpen) open @endif>
                    <summary>
                        <span class="lst-cat-t">
                            <b>{{ preg_replace('/\s*Pe?r?m?issions?$/i', '', $categoryName) ?: $categoryName }}</b>
                            <small><span data-cat-on>0</span> of {{ count($permissions) }} on</small>
                        </span>
                        <label class="lst-sw lst-sw--all" title="Turn all on or off" onclick="event.stopPropagation()">
                            <input type="checkbox" data-perm-section-all aria-label="All in {{ $categoryName }}">
                            <i></i>
                        </label>
                        <x-lb-icon name="chevron-down" class="lst-cat-chev" />
                    </summary>
                    <div class="lst-items">
                        @foreach($permissions as $name => $id)
                            <label class="lst-item" data-name="{{ mb_strtolower($name) }}">
                                <span>{{ ucwords(strtolower(trim($name))) }}</span>
                                <span class="lst-sw">
                                    <input type="checkbox" name="permissions[]" value="{{ $id }}" class="permission" @checked(in_array((int) $id, $granted, true))>
                                    <i></i>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </details>
            @endforeach
        </div>

        <div class="lpg-empty" id="permNoMatch" hidden>
            <span><x-lb-icon name="search" /></span>
            <b>Nothing matches</b>
            <p>Try another word.</p>
        </div>

        @if($groups->isEmpty())
            <div class="lpg-empty">
                <span><x-lb-icon name="lock" /></span>
                <b>Your plan has no staff actions</b>
                <p>Upgrade your plan to give staff access.</p>
            </div>
        @endif

        <div class="lpg-save">
            <p id="saveText">Choose what {{ $first }} can do.</p>
            <a href="{{ route('library-users.index') }}" class="lp-btn lp-btn--ghost">{{ $isNew ? 'Later' : 'Cancel' }}</a>
            <button type="submit" class="lp-btn" id="permSaveBtn"><x-lb-icon name="circle-check" />Save access</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('permissionsForm');
    var boxes = Array.prototype.slice.call(form.querySelectorAll('.permission'));
    var sections = Array.prototype.slice.call(form.querySelectorAll('[data-perm-section]'));
    var first = @json($first), initial = boxes.map(function (b) { return b.checked; }).join();

    function sync() {
        var on = boxes.filter(function (b) { return b.checked; }).length;
        document.getElementById('permSelectedCount').textContent = on;
        sections.forEach(function (s) {
            var own = s.querySelectorAll('.permission'), n = s.querySelectorAll('.permission:checked').length, all = s.querySelector('[data-perm-section-all]');
            s.querySelector('[data-cat-on]').textContent = n;
            all.checked = n === own.length && own.length > 0;
            all.indeterminate = n > 0 && n < own.length;
            s.classList.toggle('has-on', n > 0);
        });
        var changed = boxes.map(function (b) { return b.checked; }).join() !== initial;
        document.getElementById('saveText').textContent = on === 0
            ? first + ' won\'t see anything yet.'
            : (changed ? 'Unsaved changes · ' : '') + first + ' can do ' + on + (on === 1 ? ' thing.' : ' things.');
    }
    function setAll(v) { boxes.forEach(function (b) { b.checked = v; }); sync(); }

    document.getElementById('checkAllPermissions').addEventListener('click', function () { setAll(true); });
    document.getElementById('clearAll').addEventListener('click', function () { setAll(false); });
    sections.forEach(function (s) {
        s.querySelector('[data-perm-section-all]').addEventListener('change', function () {
            var v = this.checked;
            s.querySelectorAll('.permission').forEach(function (b) { b.checked = v; });
            sync();
        });
    });
    boxes.forEach(function (b) { b.addEventListener('change', sync); });

    var copy = document.getElementById('copyFrom');
    if (copy) copy.addEventListener('change', function () {
        var opt = copy.selectedOptions[0];
        if (!opt || !opt.value) return;
        var ids = opt.getAttribute('data-ids').split(',');
        boxes.forEach(function (b) { b.checked = ids.indexOf(b.value) !== -1; });
        sync();
        if (window.lpToast) lpToast('Copied ' + opt.textContent.replace(/\s*\(\d+\)$/, '') + '\'s access. Review and save.', 'info');
        copy.value = '';
    });

    var search = document.getElementById('permSearch');
    search.addEventListener('input', function () {
        var q = search.value.trim().toLowerCase(), any = false;
        sections.forEach(function (s) {
            var hits = 0;
            s.querySelectorAll('.lst-item').forEach(function (it) {
                var ok = !q || it.getAttribute('data-name').indexOf(q) !== -1;
                it.hidden = !ok; hits += ok ? 1 : 0;
            });
            s.hidden = hits === 0;
            if (q && hits) s.open = true;
            any = any || hits > 0;
        });
        document.getElementById('permNoMatch').hidden = any || !boxes.length;
    });

    form.addEventListener('submit', function () { document.getElementById('permSaveBtn').classList.add('is-busy'); });
    sync();
});
</script>
@endsection
