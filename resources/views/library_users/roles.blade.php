@extends('layouts.library')

@section('title', 'Roles')

@push('lp-styles')
<link rel="stylesheet" href="{{ asset('public/css/lb-pages.css') }}?v={{ @filemtime(public_path('css/lb-pages.css')) }}">
<link rel="stylesheet" href="{{ asset('public/css/lb-staff.css') }}?v={{ @filemtime(public_path('css/lb-staff.css')) }}">
@endpush

@section('content')
<div class="lpg lst">
    <header class="lpg-head">
        <div class="lpg-head-t">
            <div class="lpg-crumb"><a href="{{ route('library-users.index') }}">Staff</a><x-lb-icon name="chevron-right" /><span>Role</span></div>
            <h1>Role</h1>
            {{-- kept visible (has an id): lb-clean.css hides plain page subtitles --}}
            <p id="roleNote">Predefined · view only</p>
        </div>
    </header>

    <div class="lst-ro-grid">
        @foreach($roles as $role)
            @php
                [$label, $desc, $icon] = $roleInfo[$role->name] ?? [ucwords(str_replace('_', ' ', $role->name)), '', 'user-round'];
                $people = $byRole->get($role->name, collect());
            @endphp
            <section class="lpg-card lst-ro">
                <div class="lpg-card-h">
                    <span class="lpg-ic"><x-lb-icon :name="$icon" /></span>
                    <div><h2>{{ $label }}</h2>@if($desc)<p>{{ $desc }}</p>@endif</div>
                    <span class="lpg-tag">{{ $people->count() }} staff</span>
                </div>
                @if($people->isNotEmpty())
                    <div class="lst-ro-people">
                        @foreach($people as $u)
                            <span class="{{ $u->status ? '' : 'is-off' }}">{{ $u->name }}</span>
                        @endforeach
                    </div>
                @else
                    <p class="lst-ro-empty">No staff with this role yet.</p>
                @endif
            </section>
        @endforeach
    </div>
</div>
@endsection
