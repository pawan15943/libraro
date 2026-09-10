@extends('layouts.library')

@section('content')
@php
$planDetails = getPlanStatusDetails($customer->plan_end_date);
$class = $planDetails['class'];
@endphp

<link rel="stylesheet" href="{{ asset('public/css/learner-swap.css') }}?v={{ time() }}" />

<input id="swap_plan_type_id" type="hidden" name="plan_type_id" value="{{ $customer->plan_type_id }}">

<div class="learner-swap-module">
    <div class="learner-swap-wrapper">

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- SEAT NO TOP HEADER HERO CARD (OUTSIDE FORM CARDS) --}}
        <div class="learner-seat-header-card">
            <div class="seat-header-actions">
                <a href="{{ route('learners') }}" class="btn-seat-back">
                    <i class="fa-solid fa-arrow-left"></i> Go Back
                </a>
            </div>
            <div class="seat-header-main">
                <div class="seat-header-avatar-box">
                    @if($customer->profile_picture)
                        <img id="topSeatAvatarImg" src="{{ asset($customer->profile_picture) }}" alt="{{ $customer->name }}" class="avatar-user-photo">
                    @else
                        <img id="topSeatAvatarImg" src="{{ asset($customer->image) }}" alt="Seat" class="avatar-seat-chair {{ $class }}">
                    @endif
                </div>
                <div class="seat-header-info">
                    <div class="seat-badge-row">
                        <span class="seat-tag-pill">
                            <i class="fa-solid fa-chair"></i> Allocated Seat
                        </span>
                        <span class="seat-status-badge">
                            {{ $planDetails['status'] ?? 'Allocated' }}
                        </span>
                    </div>
                    <h3 class="seat-title">
                        @if($customer->seat_no)
                            Seat No : {{ getSeatDisplayShortFloorName($customer->seat_no) }} : {{ $customer->name }}
                        @else
                            General Seat : {{ $customer->name }}
                        @endif
                    </h3>
                    <div class="seat-meta-row">
                        <span class="seat-meta-item">
                            <i class="fa-regular fa-clock"></i> <strong>Shift / Plan:</strong> {{ $customer->plan_type_name }}
                        </span>
                        @if($customer->plan_end_date)
                        <span class="seat-meta-item">
                            <i class="fa-regular fa-calendar-check"></i> <strong>Valid Till:</strong> {{ \Carbon\Carbon::parse($customer->plan_end_date)->format('d M, Y') }}
                        </span>
                        @endif
                        @if($customer->learner_no ?? $customer->id)
                        <span class="seat-meta-item">
                            <i class="fa-regular fa-id-badge"></i> <strong>Learner UID:</strong> {{ $customer->learner_no ?? '#' . $customer->id }}
                        </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- SWAP SEAT FORM CARD --}}
        <form action="{{ route('learners.swap-seat', $customer->id) }}" method="POST" enctype="multipart/form-data" id="swapseat">
            @csrf
            @method('PUT')
            <input id="user_id" type="hidden" name="learner_id" value="{{ $customer->id }}">
            <input type="hidden" value="{{ $customer->seat_no }}" id="swap_old_value">

            <div class="swap-card">
                <div class="swap-card-header header-green">
                    <div class="swap-header-left">
                        <div class="swap-header-icon">
                            <i class="fa-solid fa-repeat"></i>
                        </div>
                        <div>
                            <h4 class="swap-header-title">Swap Seat</h4>
                            <p class="swap-header-subtitle">Change the current seat allotment to an available seat.</p>
                        </div>
                    </div>
                </div>
                <div class="swap-card-body">
                    <div class="swap-tip-box">
                        <i class="fa-solid fa-gem"></i>
                        <div>
                            <strong>Note :</strong> You can swap your seat with any other seat that has the same plan available for booking.
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6 form-group">
                            <label class="form-label">Old Seat Number</label>
                            <input class="form-control" value="{{ getSeatDisplayShortFloorName($customer->seat_no) ?? 'Gen' }} - {{ $customer->plan_type_name }}" readonly>
                            <input class="form-control" value="{{ $customer->seat_no ?? 'Gen' }} - {{ $customer->plan_type_name }}" type="hidden">
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="form-label" for="new_seat_id">New Seat Number</label>
                            <select name="seat_id" id="new_seat_id" class="form-select @error('seat_id') is-invalid @enderror">
                                <option value="">Select Seat</option>
                                <option value="">General</option>
                                @foreach($newAvailableSeats as $key => $value)
                                <option value="{{ $value['main'] }}">{{ $value['display'] }}</option>
                                @endforeach
                            </select>
                            @error('seat_id')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        <div class="col-12 form-group">
                            <label class="form-label">Current Seat Status</label>
                            <div id="swap_status"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ACTION BUTTON BAR (OUTSIDE BOX) --}}
            <div class="form-action-bar">
                <a href="{{ route('learners') }}" class="btn-back-form">
                    <i class="fa-solid fa-arrow-left"></i> Cancel
                </a>
                <button type="submit" class="btn-submit-swap" id="swapsubmit">
                    <i class="fa-solid fa-right-left"></i> Swap Seat
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.getElementById("swapsubmit").disabled = true;

    document.addEventListener('DOMContentLoaded', function() {
        handleFormChanges('swapseat', "{{ $customer->id }}");
    });
</script>

@endsection