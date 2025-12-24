@extends('applayout.layout')

@section('content')

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

        <h6 class="mt-3 mb-3 text-center">Learner ID Card <br>{{ $learner->learner_no ?? '-' }} </h6>

        <div class="id-wrapper">
            <div class="id-card position-relative">

                <!-- FRONT -->
                <div class="id-front">

                    <div class="d-flex justify-content-between">
                        <strong>
                            {{ $detail->library_name ?? 'Library' }}
                        </strong>

                        
                        {!! getStatusFromBranch($detail->plan_end_date,$learner->id,$detail->branch_id) !!}
                    </div>

                    <h6 class="mt-3">
                        Seat No :
                        {{ $detail->seat_no ?? 'GEN' }}
                        {{ $learner->name ?? '-' }}
                    </h6>

                    <small>
                        Father :
                        {{ $learner->father_name ?? 'Not Updated' }}
                    </small>

                    <div class="mt-3 row">

                        <div class="col-6 mb-2">
                            <small>Plan Starts</small>
                            <div>
                                {{ $detail?->plan_start_date
                                    ? \Carbon\Carbon::parse($detail->plan_start_date)->format('d M Y')
                                    : '-' }}
                            </div>
                        </div>

                        <div class="col-6 mb-2">
                            <small>Plan Ends</small>
                            <div>
                                {{ $detail?->plan_end_date
                                    ? \Carbon\Carbon::parse($detail->plan_end_date)->format('d M Y')
                                    : '-' }}
                            </div>
                        </div>

                        <div class="col-12 mb-2">
                            <small>Plan Name</small>
                            <div>
                                {{ $detail->plan->name ?? '-' }}
                            </div>
                        </div>

                        <div class="col-12">
                            <small>Shift Timing</small>
                            <div>
                                {{ $detail->planType->name ?? '-' }}
                                @if($detail && $detail->planType)
                                    :
                                    {{ $detail->planType->start_time }}
                                    to
                                    {{ $detail->planType->end_time }}
                                @endif
                            </div>
                        </div>

                    </div>
                </div>

                <!-- BACK -->
                <div class="id-back d-flex flex-column justify-content-center align-items-center">

                    @if($learner)
                    
                        {!! QrCode::size(150)->generate($learner->learner_no) !!}
                    @else
                        <span>No QR</span>
                    @endif

                    <small class="mt-2">Scan for Attendance</small>
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

            <div class="profile-item">
            <i data-lucide="user"></i>
            <div>
                <strong>Profile Details</strong>
                <small>View your learner information</small>
            </div>
            </div>

            <div class="profile-item">
            <i data-lucide="credit-card"></i>
            <div>
                <strong>ID Card</strong>
                <small>View & download your ID card</small>
            </div>
            </div>

            <div class="profile-item">
            <i data-lucide="building"></i>
            <div>
                <strong>Library Details</strong>
                <small>Library contact & address</small>
            </div>
            </div>

            <div class="profile-item">
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
            </div>

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
                <dotlottie-wc src="https://lottie.host/79d3a6d1-4651-47a2-8204-6780dff68b52/BS5YmTvc3K.lottie" style="width: 300px; margin: 0 auto;"  autoplay loop></dotlottie-wc>
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
        <div class="app-card">
        <h6>Support</h6>
        <p>📍 ABC Library, Delhi</p>
        <p>📞 +91 9876543210</p>
        <p>✉ support@abclibrary.com</p>
        </div>
    </section>
</div>
@endsection
