@extends('layouts.library')

@section('title', 'Permissions')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-pages.css') }}?v={{ @filemtime(public_path('css/lb-pages.css')) }}">
<link rel="stylesheet" href="{{ asset('public/css/lb-staff.css') }}?v={{ @filemtime(public_path('css/lb-staff.css')) }}">
@endpush

@section('content')
<div class="lpg lst">
    <header class="lpg-head">
        <div class="lpg-head-t">
            <div class="lpg-crumb"><a href="{{ route('library-users.index') }}">Staff</a><x-lb-icon name="chevron-right" /><span>Permission</span></div>
            <h1>Permission</h1>
            {{-- kept visible (has an id): lb-clean.css hides plain page subtitles --}}
            <p id="permNote">Predefined · view only · {{ $total }} in your plan</p>
        </div>
        <div class="lpg-head-r">
            <a href="{{ route('library-users.index') }}" class="lp-btn lp-btn--ghost"><x-lb-icon name="users-round" />Staff</a>
        </div>
    </header>

    @if($grouped->isEmpty())
        <section class="lpg-card lst-ro-empty">Your plan doesn't include staff permissions. Upgrade your plan to give staff permission.</section>
    @else
        <label class="lpg-search lst-ro-search">
            <x-lb-icon name="search" />
            <input type="search" id="permSearch" placeholder="Search permissions" autocomplete="off">
        </label>
        <div class="lst-ro-grid" id="permGrid">
            @foreach($grouped as $catId => $perms)
                <section class="lpg-card lst-ro" data-cat>
                    <div class="lpg-card-h">
                        <span class="lpg-ic"><x-lb-icon name="key-round" /></span>
                        <div><h2>{{ $categoryNames[$catId] ?? 'Other' }}</h2></div>
                        <span class="lpg-tag">{{ $perms->count() }}</span>
                    </div>
                    <ul class="lst-ro-list">
                        @foreach($perms as $perm)
                            <li data-name="{{ mb_strtolower($perm->name) }}"><x-lb-icon name="check" />{{ $perm->name }}</li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </div>
        <div class="lpg-empty" id="permNoMatch" hidden><b>No permission matches</b></div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var input = document.getElementById('permSearch');
    if (!input) return;
    input.addEventListener('input', function () {
        var q = input.value.trim().toLowerCase(), any = false;
        document.querySelectorAll('#permGrid [data-cat]').forEach(function (card) {
            var shown = 0;
            card.querySelectorAll('li[data-name]').forEach(function (li) { var ok = !q || li.dataset.name.indexOf(q) !== -1; li.hidden = !ok; shown += ok ? 1 : 0; });
            card.hidden = shown === 0;
            any = any || shown > 0;
        });
        document.getElementById('permNoMatch').hidden = any;
    });
});
</script>
@endsection
