@extends('applayout.layout')

@section('content')
<style>
    .profile-item img,
    svg {
        vertical-align: middle;
        margin-top: .5rem;
    }

    .id-front small {
        color: #8c8c8c;
    }

    .id-back{
        background: #fff !important;
    }

</style>

<!-- HEADER -->
<div class="app-header">
    <div class="flex">
        <div class="profile">

            <img src="{{ asset('public/img/logo-whitw.png') }}" alt="logo" class="logo">

        </div>
        <i data-lucide="menu" onclick="toggleSidebar()"></i>
    </div>
    <div class="profile-list w-100">
        <div class="profile">
            <img src="https://i.pravatar.cc/100?img=12" class="picture">
            <div class="libr_info">
                <strong>{{ $detail->library_name ?? 'Library' }}</strong>
                <small>QR Attendance</small>
            </div>
        </div>
    </div>
</div>

<div class="container mt-4">
    <!-- ================= DASHBOARD ================= -->
    <section id="dashboard" class="section active">

        <h6 class="mt-3 mb-3 text-center">Learner ID Card </h6>

        <div class="id-wrapper">
            <div class="id-card position-relative">

                <!-- FRONT -->
                <div class="id-front">
                    <div class="d-flex justify-content-between">
                        <h4 class="uppercase">
                            {{ $detail->library_name ?? 'Library' }}
                        </h4>
                       
                    </div>

                    <h6 class="mt-3">
                        Seat No : {{ $detail->seat_no ?? 'GEN' }}

                    </h6>

                    <div class="mt-3 row">

                        <div class="col-6 mb-3">
                            <small>Learner No.</small>
                            <div>
                                {{ $learner->learner_no ?? '-' }}
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <small>Full Name</small>
                            <div class="uppercase">
                                {{ $learner->name ?? '-' }}
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <small>Plan</small>
                            <div>
                                {{ $detail->plan->name ?? '-' }}
                            </div>
                        </div>
                        <div class="col-6 mb-3">
                            <small>Plan Type / Shift</small>
                            <div>
                                {{ $detail->planType->name ?? '-' }}

                            </div>
                        </div>
                       <div class="col-12 mb-3">
                            <small>Shift Timing</small>
                            <div>
                                @if($detail && $detail->planType)
                                    {{ \Carbon\Carbon::parse($detail->planType->start_time)->format('h:i A') }}
                                    to
                                    {{ \Carbon\Carbon::parse($detail->planType->end_time)->format('h:i A') }}
                                @endif
                            </div>
                        </div>

                        <div class="col-6 mb-3">
                            <small>Plan Starts</small>
                            <div>
                                {{ $detail?->plan_start_date
                                    ? \Carbon\Carbon::parse($detail->plan_start_date)->format('d M Y')
                                    : '-' }}
                            </div>
                        </div>

                        <div class="col-6 mb-4">
                            <small>Plan Ends</small>
                            <div>
                                {{ $detail?->plan_end_date
                                    ? \Carbon\Carbon::parse($detail->plan_end_date)->format('d M Y')
                                    : '-' }}
                            </div>
                        </div>
                        <div class="col-12 m-0 text-center">
                             {!! getStatusFromBranch($detail->plan_end_date,$learner->id,$detail->branch_id) !!}
                        </div>

                    </div>
                </div>

                <!-- BACK -->
                <div class="id-back bg-white d-flex flex-column justify-content-center align-items-center">

                    @if($learner)

                    {!! QrCode::size(250)->generate($learner->learner_no) !!}
                    @else
                    <span>No QR</span>
                    @endif

                    <small class="mt-4">Scan for Attendance</small>
                </div>

            </div>
        </div>
    </section>

    <!-- ================= PROFILE ================= -->
    <section id="profile" class="section">

        <div class="profile-header">
            <img src="https://i.pravatar.cc/100?u={{ $learner->id ?? 1 }}">

            <div>
                <strong>{{ $learner->name ?? '-' }}</strong>
                <small>
                    {{$learner->learner_no}}
                </small>
            </div>
        </div>

        <div class="profile-list">

                {{-- Date of Birth --}}
            <div class="profile-item">
                <i data-lucide="calendar-heart"></i>
                <div>
                    <small>Date Of Birth</small>
                    <strong>
                        {{ $learner->dob 
                            ? \Carbon\Carbon::parse($learner->dob)->format('d-m-Y') 
                            : 'Not Updated' 
                        }}
                    </strong>
                </div>
            </div>

            {{-- Mobile Number --}}
            <div class="profile-item">
                <i data-lucide="phone-forwarded"></i>
                <div>
                    <small>Mobile Number</small>
                    <strong>
                        {{ !empty($learner->mobile) 
                            ? $learner->mobile 
                            : 'Not Updated' 
                        }}
                    </strong>
                </div>
            </div>

            {{-- Alternate Mobile --}}
            <div class="profile-item">
                <i data-lucide="phone"></i>
                <div>
                    <small>Alternate Number</small>
                    <strong>
                        {{ !empty($learner->alternate_mobile) 
                            ? $learner->alternate_mobile 
                            : 'Not Updated' 
                        }}
                    </strong>
                    </strong>
                </div>
            </div>
          
            {{-- Email --}}
            <div class="profile-item">
                <i data-lucide="mail"></i>
                <div>
                    <small>Email ID</small>
                    <strong>{{ $learner->email ?? 'Not Updated' }}
                    </strong>
                </div>
            </div>

            {{-- Father Name --}}
            <div class="profile-item">
                <i data-lucide="user"></i>
                <div>
                    <small>Father Name</small>
                    <strong>{{ $learner->father_name ?? 'Not Updated' }}</strong>
                </div>
            </div>

            {{-- <div class="profile-item">
            <i data-lucide="smartphone"></i>
            <div>
                <strong>Device & Login</strong>
                <small>Manage active sessions</small>
            </div>
            </div>

            <div class="profile-item text-danger">
            <i data-lucide="trash"></i>
            <div>
                <strong>Delete My Account</strong>
                <small>Permanent account deletion</small>
            </div>
            </div> --}}

        </div>
    </section>

    <!-- ================= SCAN ================= -->
    <section id="scan" class="section">
        <div class="app-card">
            <h6>Scan QR Code</h6>
            <div id="scanner-wrapper">
                <div id="reader"></div>
            </div>
            <div id="successAnimation" style="display:none; text-align:center;" class="mb-4">
                <dotlottie-wc src="https://lottie.host/79d3a6d1-4651-47a2-8204-6780dff68b52/BS5YmTvc3K.lottie" style="width: 300px; margin: 0 auto;" autoplay loop></dotlottie-wc>
            </div>
            <div id="failedAnimation" style="display:none; text-align:center;" class="mb-4">
                <dotlottie-wc src="https://lottie.host/b8f1b3ee-de1b-4b39-ba80-4f3bc61b8f6b/GWE4EE0dMM.lottie" style="width: 300px; margin: 0 auto;" autoplay loop></dotlottie-wc>
            </div>
            <div id="errorAnimation" style="display:none; text-align:center;" class="mb-4">
                <dotlottie-wc src="https://lottie.host/767cd45c-30a6-4317-b53b-e756f423efd8/7B9WsqgVFT.lottie" style="width: 300px; margin: 0 auto;" autoplay loop></dotlottie-wc>
            </div>
            <small id="scanResult" class="text-muted">
                Waiting for scan...
            </small>
        </div>
    </section>

    <!-- SUPPORT -->
    <section id="support" class="section">
        <h6 class="py-2">Library Contact Info</h6>
        <div class="profile-list">

            <div class="profile-item">
                <i data-lucide="map-pin-house"></i>
                <div>
                    <small>Library Address</small>
                    <strong>{{ $detail->library_address ?? 'Not Updated' }}</strong>
                </div>
            </div>
            <div class="profile-item">
                <i data-lucide="phone"></i>
                <div>
                    <small>Library Contact No</small>
                        <strong>
                         {{ !empty($detail->library_mobile)
                            ? '+91-' . $detail->library_mobile
                            : 'Not Updated'
                        }}
                    </strong>
                </div>
            </div>


            <div class="profile-item">
                <i data-lucide="mail"></i>
                <div>
                    <small>Library Email ID</small>
                    <strong>{{ $detail->library_email ?? 'Not Updated' }}</strong>
                </div>
            </div>

        </div>

    </section>
</div>
@endsection
