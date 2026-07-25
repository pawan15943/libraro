@extends('layouts.admin')

@section('content')
<link rel="stylesheet" href="{{ asset('public/css/library-style.css') }}">

<div class="actions mb-4">
    <div class="upper-box">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="mb-3">{{ $library->library_name }} — Branches</h4>
            <a href="javascript:void(0);" class="go-back" onclick="window.history.back();">Go Back <i class="fa-solid fa-backward pl-2"></i></a>
        </div>
    </div>
</div>

@if($branches->isEmpty())
<div class="no-data-found">
    <h4>No branches added for this library yet.</h4>
</div>
@else
<div class="row g-4">
    @foreach($branches as $key => $branch)
    <div class="col-lg-4 col-md-6">
        <div class="planBox">
            <div class="heading d-flex justify-content-between align-items-center">
                <h4 class="m-0">Branch {{ $key + 1 }}</h4>
                @if($branch->status)
                <span class="active">Active</span>
                @else
                <span class="inactive text-danger">Inactive</span>
                @endif
            </div>

            <div class="plan border-top">
                <div class="branchInfo">
                    <h4>{{ $branch->display_name ?? $branch->name }}</h4>
                    <span>Address : {{ $branch->library_address ?? 'Not updated yet' }}</span>
                </div>
                <ul>
                    <li><span>Contact</span><p class="m-0">+91-{{ $branch->mobile ?? 'Not updated yet' }}</p></li>
                    <li class="w-100"><span>Email ID</span><p class="m-0">{{ $branch->email ?? 'Not updated yet' }}</p></li>
                </ul>
            </div>

            @if($branch->uuid)
            <div class="p-3 border bg-light text-center">
                {!! QrCode::size(100)->generate(route('qr.branch', $branch->uuid)) !!}
            </div>
            @endif

            <ul class="actionalbles">
                <li><a href="{{ route('library.branch.seatHour', $branch->id) }}" title="Seats & Operating Hours"><i class="fa-solid fa-chair"></i></a></li>
                <li><a href="{{ route('library.branch.plans', $branch->id) }}" title="Plans"><i class="fa-solid fa-layer-group"></i></a></li>
                <li><a href="{{ route('library.branch.plantypes', $branch->id) }}" title="Plan Types / Shifts"><i class="fa-solid fa-clock"></i></a></li>
                <li><a href="{{ route('library.branch.prices', $branch->id) }}" title="Plan Prices"><i class="fa-solid fa-tags"></i></a></li>
                <li><a href="{{ route('library.branch.edit', $branch->id) }}" title="Edit Branch"><i class="fas fa-edit"></i></a></li>
            </ul>
        </div>
    </div>
    @endforeach
</div>
@endif
@endsection
