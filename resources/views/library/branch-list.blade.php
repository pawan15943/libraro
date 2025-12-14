@extends('layouts.library')
@section('content')

<!-- Breadcrumb -->

@if($branches->isEmpty())
<div class="no-data-found">
    <script
        src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.8.1/dist/dotlottie-wc.js"
        type="module"></script>

    <dotlottie-wc
        src="https://lottie.host/5d973bf9-2f1d-4dd5-925f-86da95dbd7b1/t7dXaWIroC.lottie"
        style="width: 200px;height: 200px"
        autoplay
        loop></dotlottie-wc>
    <h4>You haven’t added any library branches yet.</h4>
    <span> Start by creating your first branch to manage it here.</span>
    @can('has-permission','Add Branch Master')
    <div class="heading-list justify-content-end mb-4">
        <a href="{{ route('branch.create') }}" class="btn btn-primary export m-0">
            <i class="fa-solid fa-plus "></i> Add Branch
        </a>
    </div>
    @else
    <span class="text-danger">You don't have Permission to add Branch</span>
    @endcan
</div>
@else

@can('has-permission','Add Branch Master')
<div class="heading-list justify-content-end mb-4">
    <a href="{{ route('branch.create') }}" class="btn btn-primary export m-0">
        <i class="fa-solid fa-plus "></i> Add Branch
    </a>
</div>
@endcan

<div class="row g-4 mb-4 ">
    @foreach($branches as $key => $value)
    <div class="col-lg-4 col-md-6">
        <div class="planBox">
            <div class="heading d-flex justify-content-between align-items-center">
                <h4>Branch {{ $key+1 }}</h4>
                <span class="active">Active</span>
            </div>

            <div class="plan border-top">
                <div class="branchInfo">
                    <h4>{{ $value->display_name ?? $value->name }}</h4>
                    <span>Address : {{ $value->library_address ?? 'Not updated yet' }}</span>
                </div>
                <ul>
                    <li>
                        <span>Contact</span>
                        <p class="m-0">+91-{{ $value->mobile ?? 'Not updated yet' }}</p>
                    </li>
                    <li class="w-100">
                        <span>Email ID</span>
                        <p class="m-0">{{ $value->email ?? 'Not updated yet' }}</p>
                    </li>
                </ul>

            </div>
            @if($value->uuid)
            <div class="p-3 border bg-light text-center">
                {!! QrCode::size(100)->generate(route('qr.branch', $value->uuid)) !!}
            </div>
            @endif

            <ul class="actionalbles">
                @if(getCurrentBranch() != 0)

                @can('has-permission','Add Library Seats')
                <li>
                    <a href="{{ route('seat.create', getCurrentBranch()) }}" title="Seat Update">
                        <i class="fa-solid fa-chair"></i>
                    </a>
                </li>
                @endcan
                
                @can('has-permission','Add Operating Hours')
                <li>
                    <a href="{{ route('hour.create', getCurrentBranch()) }}" title="Hour Update">
                        <i class="fa-solid fa-clock-rotate-left"></i>
                    </a>
                </li>
                @endcan

                @can('has-permission','Add Extend Days')
                <li>
                    <a href="{{ route('extendDay.create', $value->id) }}" title="Extend Day">
                        <i class="fa-solid fa-calendar-plus"></i>
                    </a>
                </li>
                @endcan

                @can('has-permission','Add Locker Amount')
                <li>
                    <a href="{{ route('lockeramount.create', $value->id) }}" title="Locker Amount">
                        <i class="fa-solid fa-lock"></i>
                    </a>
                </li>
                @endcan

                @can('has-permission','Add Token Money')
                <li>
                    <a href="{{ route('tokenAmount.create', $value->id) }}" title="Token Money">
                        <i class="fa-solid fa-credit-card"></i>
                    </a>
                </li>
                @endcan

                @endif
                @can('has-permission','Edit Branch')
                <li>
                    <a href="{{ route('branch.edit', $value->id) }}" title="Branch Profile Edit">
                        <i class="fas fa-edit"></i>
                    </a>
                </li>
                @endcan
            </ul>
        </div>
    </div>
    @endforeach
</div>
@endif

@include('library.script')

@endsection