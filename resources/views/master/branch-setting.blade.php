@extends('layouts.library')

@section('title', $setting['title'])

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-opform.css') }}?v={{ @filemtime(public_path('css/lb-opform.css')) }}">
<link rel="stylesheet" href="{{ asset('public/css/lb-pages.css') }}?v={{ @filemtime(public_path('css/lb-pages.css')) }}">
<link rel="stylesheet" href="{{ asset('public/css/lb-branch.css') }}?v={{ @filemtime(public_path('css/lb-branch.css')) }}">
@endpush

@section('content')
{{--
    One branch setting (seats, operating hours, grace days, locker fee, token money), from
    MasterController::branchSettingPage(). Saves through route('master.store') like before:
    databasemodel Hour (seats, by branch_id) or Branch (by id). Operating hours (open / close
    time) save through route('branch.hours.update').
--}}
@php
    $field = $key;
    $branchName = $branch->display_name ?: $branch->name;
    $value = old($field, $setting['value']);
@endphp

<div class="lpg lbr lbs">
    <header class="lpg-head lbs-head">
        <div class="lpg-head-t">
            <div class="lpg-crumb">
                <a href="{{ route('library.home') }}">Dashboard</a><x-lb-icon name="chevron-right" />
                <a href="{{ route('branch.list') }}">Branches</a><x-lb-icon name="chevron-right" />
                <span>{{ $setting['title'] }}</span>
            </div>
            <h1>{{ $setting['title'] }}</h1>
            {{-- id keeps it visible (lb-clean.css hides plain page subtitles) --}}
            <p id="lbsBranch"><x-lb-icon name="building" />{{ $branchName }}</p>
        </div>
    </header>

    <form class="lef-sec lbs-card" id="branchSettingForm" novalidate>
        @csrf
        <input type="hidden" name="databasemodel" value="{{ $setting['form'] }}">
        <input type="hidden" name="library_id" value="{{ getLibraryId() }}">
        <input type="hidden" name="redirect" value="{{ route('branch.list') }}">
        @if($setting['form'] === 'Hour')
            <input type="hidden" name="branch_id" value="{{ $branch->id }}">
        @else
            <input type="hidden" name="id" value="{{ $branch->id }}">
        @endif

        <div class="lbs-info">
            <span class="lbr-no"><x-lb-icon :name="$setting['icon']" /></span>
            <p id="lbsInfo">{{ $setting['info'] }}</p>
        </div>

        @if($setting['type'] === 'timing')
            {{-- open / close time, saved through route('branch.hours.update') --}}
            <div data-field="{{ $field }}">
                @include('lb.partials.open-hours', ['skin' => 'lef', 'open' => $setting['times'][0] ?? '', 'close' => $setting['times'][1] ?? '', 'allDay' => $setting['all_day'], 'canAllDay' => \Illuminate\Support\Facades\Gate::allows('has-permission', 'All Day'), 'note' => false])
                <p class="lef-err" data-err="{{ $field }}" hidden></p>
                @if($setting['hint'])<p class="lef-hint" id="lbsHint">{{ $setting['hint'] }}</p>@endif
            </div>
        @else
        <div class="lef-f" data-field="{{ $field }}">
            <label for="lbsValue">{{ $setting['title'] }} <i>*</i></label>
            @if($setting['type'] === 'hours')
                <div class="lbs-hours" role="radiogroup" aria-label="{{ $setting['title'] }}">
                    @for($h = 10; $h <= $setting['max']; $h++)
                        <label class="lbs-hour {{ $h < $setting['min'] ? 'is-off' : '' }}">
                            <input type="radio" name="{{ $field }}" value="{{ $h }}" @checked((string) $value === (string) $h) @disabled($h < $setting['min'])>
                            <span>{{ $h }} hrs</span>
                        </label>
                    @endfor
                </div>
            @else
                <div class="lef-in">
                    @isset($setting['prefix'])<span class="lef-pre">{{ $setting['prefix'] }}</span>@endisset
                    <input type="text" id="lbsValue" name="{{ $field }}" class="digit-only" inputmode="numeric" maxlength="{{ strlen((string) $setting['max']) }}"
                           value="{{ $value }}" data-min="{{ $setting['min'] }}" data-max="{{ $setting['max'] }}" autocomplete="off">
                    @isset($setting['suffix'])<span class="lbs-suf">{{ $setting['suffix'] }}</span>@endisset
                </div>
            @endif
            <p class="lef-err" data-err="{{ $field }}" hidden></p>
            @if($setting['hint'])<p class="lef-hint" id="lbsHint">{{ $setting['hint'] }}</p>@endif
        </div>
        @endif

        <div class="lbs-acts">
            <a href="{{ route('branch.list') }}" class="lp-btn lp-btn--ghost">Cancel</a>
            <button type="submit" class="lp-btn" id="lbsSave"><x-lb-icon name="circle-check" />Save</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('branchSettingForm');
    var field = @json($field), title = @json($setting['title']);
    var min = {{ (int) $setting['min'] }}, max = {{ (int) $setting['max'] }}, isHours = @json($setting['type'] === 'hours');
    var timing = @json($setting['type'] === 'timing') ? form.querySelector('[data-lboh]') : null;
    var errBox = form.querySelector('[data-err="' + field + '"]'), wrap = form.querySelector('[data-field="' + field + '"]');
    var btn = document.getElementById('lbsSave');

    function showErr(msg) {
        if (timing) { lbOpenHours(timing).showErr(msg); return; }
        errBox.textContent = msg; errBox.hidden = !msg; wrap.classList.toggle('is-bad', !!msg);
    }
    function value() {
        if (isHours) { var c = form.querySelector('input[name="' + field + '"]:checked'); return c ? c.value : ''; }
        return form.querySelector('#lbsValue').value.trim();
    }
    function check() {
        if (timing) return window.lbOpenHours ? lbOpenHours(timing).check() : '';
        var v = value();
        if (v === '') return isHours ? 'Choose how many hours the library is open.' : 'Enter the ' + title.toLowerCase() + '.';
        if (!/^\d+$/.test(v)) return 'Use numbers only.';
        var n = +v;
        if (field === 'seats' && n < min) return 'You can only add seats. It can\'t go below ' + min + '.';
        if (n < min) return title + ' can\'t be less than ' + min + '.';
        if (n > max) return title + ' can\'t be more than ' + max.toLocaleString('en-IN') + '.';
        return '';
    }
    form.addEventListener('input', function () { showErr(''); });
    form.addEventListener('change', function () { showErr(''); });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        if (btn.disabled) return;
        var msg = check();
        if (msg) { showErr(msg); return; }
        btn.disabled = true; btn.classList.add('is-busy');
        fetch(timing ? @json(route('branch.hours.update', $branch->id)) : @json(route('master.store')), {
            method: 'POST', body: new FormData(form), credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (r) { return r.json().catch(function () { return {}; }).then(function (j) { return { s: r.status, j: j }; }); })
            .then(function (res) {
                if (res.j.success) {
                    try { sessionStorage.setItem('lbs_saved', title + ' saved.'); } catch (x) {}
                    window.location.href = res.j.redirect || @json(route('branch.list'));
                    return;
                }
                btn.disabled = false; btn.classList.remove('is-busy');
                var errs = res.j.errors || {};
                if (timing) {
                    var k = Object.keys(errs)[0];
                    lbOpenHours(timing).showErr((k && errs[k][0]) || res.j.message || 'Couldn\'t save. Please try again.');
                    return;
                }
                showErr((errs[field] && errs[field][0]) || res.j.message || 'Couldn\'t save. Please try again.');
            })
            .catch(function () { btn.disabled = false; btn.classList.remove('is-busy'); showErr('No internet connection. Please try again.'); });
    });
});
</script>
@endsection
