@extends('layouts.library')
@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}" />
<!-- Main content -->

<div id="success-message" class="alert alert-success" style="display:none;"></div>
<div id="error-message" class="alert alert-danger" style="display:none;"></div>

@if($errors->any())
<div class="alert alert-danger">
    <ul>
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

@if($data->isEmpty())
<div class="no-data-found">
    <script src="https://unpkg.com/@lottiefiles/dotlottie-wc@0.8.1/dist/dotlottie-wc.js" type="module"></script>

    <dotlottie-wc
        src="https://lottie.host/5d973bf9-2f1d-4dd5-925f-86da95dbd7b1/t7dXaWIroC.lottie"
        style="width: 200px;height: 200px"
        autoplay
        loop></dotlottie-wc>

    @if(getCurrentBranch() != 0)
        
        <h4>You haven’t added any Floor.</h4>
        <span>Start by creating your first Floor to manage it here.</span>
        @can('has-permission','Add Floor Master')
        <a href="{{ route('floor.create') }}" class="btn btn-primary export">
            <i class="fa-solid fa-plus"></i> Add Floor
        </a>
        @else
        <span class="text-danger">You don't have Permission to add Floor</span>
        @endcan
    @else
        <h4>To add Floor, first select your Branch.</h4>
        <span>Floors are branch-specific. Please choose a branch before adding Floor. (Choose Branch in Header Dropdown)</span>
    @endif
</div>
@else

@can('has-permission','Add Floor Master')
<div class="heading-list justify-content-end mb-1">
    @if(getCurrentBranch() != 0)
    <a href="{{ route('floor.create') }}" class="btn btn-primary export">
        <i class="fa-solid fa-plus "></i> Add Floor
    </a>
    @endif
</div>
@endcan


<div class="row g-4 mb-4">
    @foreach($data as $key => $value)
    <div class="col-lg-4 col-md-6">
        <div class="planBox">
            <div class="heading d-flex justify-content-between align-items-center">
                <h4>Floor {{ $value->floor_no }}</h4>
                <span class="active">{{ $value->total_seats }} Seats</span>
            </div>

            <div class="plan border-top">
                <ul>
                    <li>
                        <span>Floor Name</span>
                        <p class="m-0">{{ $value->name ?? '—' }}</p>
                    </li>
                     <li>
                        <span>Total Seats</span>
                        <p class="m-0">{{ $value->total_seats }}</p>
                    </li>
                    <li>
                        <span>From Seat</span>
                        <p class="m-0">{{ $value->from_seat }}</p>
                    </li>
                    <li>
                        <span>To Seat</span>
                        <p class="m-0">{{ $value->to_seat }}</p>
                    </li>
                   
                </ul>
            </div>

             <ul class="actionalbles">
                <li>
                    <a href="javascript:void(0)"
                        class="active-deactive"
                        data-id="{{ $value->id }}"
                        data-table="Floor"
                        title="Active/Deactive">
                        @if($value->deleted_at)
                        <i class="fas fa-ban"></i>
                        @else
                        <i class="fa fa-check"></i>
                        @endif
                    </a>
                </li>
                <li>
                    <a href="{{ route('floor.create', $value->id) }}" title="Edit">
                        <i class="fas fa-edit"></i>
                    </a>
                </li>
                <li>
                    <a href="javascript:void(0)"
                        class="delete-btn"
                        data-id="{{ $value->id }}"
                        data-table="Floor"
                        title="Delete">
                        <i class="fa fa-trash"></i>
                    </a>
                </li>
            </ul>
        </div>
    </div>
    @endforeach
</div>
@endif

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.concat.min.js"></script>

@include('master.script')
@endsection
