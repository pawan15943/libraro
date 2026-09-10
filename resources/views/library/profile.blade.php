@extends('layouts.library')

@section('title', 'Library Profile')

@section('content')
<link rel="stylesheet" href="{{ asset('public/css/library-profile.css') }}?v={{ time() }}">

<div class="library-profile-module">
    @if($iscomp==false && !$is_expire)
    <div class="row steps-row">
        <div class="col-lg-12">
            <ul class="onboarding-steps">
                <li>
                    <a href="{{ ($checkSub) ? '#' : route('subscriptions.choosePlan')  }}">Pick Your Perfect Plan</a>
                </li>
                <li class="active">
                    <a href="{{ ($ispaid ) ? route('branch.create') : '#' }}">Branch</a>
                </li>
                <li>
                    <a href="{{ ($checkSub && $ispaid && $isProfile) ? route('library.master') : '#' }}">Configure Library</a>
                </li>
            </ul>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
            <h2 class="typing-banner">A few details to make it yours!</h2>
        </div>
    </div>
    @endif



    <form action="{{ route('library.profile.update') }}" class="validateForm profile" method="POST">
        @csrf

        <div class="profile-layout-grid">
            <!-- Left Column: Sidebar Summary Card -->
            <div class="profile-sidebar-col">
                <div class="profile-sidebar-card">
                    <div class="avatar-wrapper">
                        <div class="avatar-circle">
                            <i class="fa-solid fa-building-columns"></i>
                        </div>
                        <div class="avatar-badge-status" title="Active Account">
                            <i class="fa-solid fa-check"></i>
                        </div>
                    </div>

                    <h3 class="sidebar-lib-name">{{ $library->library_name ?? 'Library Profile' }}</h3>
                    @if(!empty($library->library_no))
                        <div class="sidebar-lib-code">
                            <i class="fa-solid fa-id-badge"></i> {{ $library->library_no }}
                        </div>
                    @endif

                    <div>
                        <span class="badge-verified-chip">
                            <i class="fa-solid fa-circle-check"></i> Verified Account
                        </span>
                    </div>

                    <hr class="sidebar-divider">

                    <ul class="sidebar-meta-list">
                        <li class="sidebar-meta-item">
                            <i class="fa-solid fa-envelope"></i>
                            <div class="sidebar-meta-info">
                                <div class="sidebar-meta-label">Email Address</div>
                                <div class="sidebar-meta-value">{{ $library->email ?? 'N/A' }}</div>
                            </div>
                        </li>
                        <li class="sidebar-meta-item">
                            <i class="fa-solid fa-phone"></i>
                            <div class="sidebar-meta-info">
                                <div class="sidebar-meta-label">WhatsApp Contact</div>
                                <div class="sidebar-meta-value">{{ $library->library_mobile ?? 'N/A' }}</div>
                            </div>
                        </li>
                        <li class="sidebar-meta-item">
                            <i class="fa-solid fa-user-shield"></i>
                            <div class="sidebar-meta-info">
                                <div class="sidebar-meta-label">Account Owner</div>
                                <div class="sidebar-meta-value">{{ $library->library_owner ?? 'Not configured yet' }}</div>
                            </div>
                        </li>
                    </ul>
                </div>

                <div class="sidebar-notice-card">
                    <i class="fa-solid fa-shield-halved"></i>
                    <div>
                        <div class="notice-card-title">Verified Security Notice</div>
                        <div class="notice-card-text">
                            Key credentials such as your registered Library Name, Email, and WhatsApp number are verified and protected. To change them, please contact Libraro support.
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Form Cards -->
            <div class="profile-main-col">
                <!-- Card 1: Library Details (Readonly) -->
                <div class="form-section-card">
                    <div class="section-header-strip">
                        <div class="section-header-left">
                            <div class="section-header-badge badge-blue">
                                <i class="fa-solid fa-landmark"></i>
                            </div>
                            <div>
                                <h4 class="section-header-title">Verified Library Information</h4>
                                <p class="section-header-sub">Official registration credentials associated with your account</p>
                            </div>
                        </div>
                        <span class="badge-verified-chip">
                            <i class="fa-solid fa-lock"></i> Locked &amp; Verified
                        </span>
                    </div>

                    <div class="row g-3">
                        <!-- Library Name -->
                        <div class="col-md-12">
                            <div class="form-group-wrap">
                                <label class="form-label-custom">Library Name <span>*</span></label>
                                <div class="input-with-icon">
                                    <i class="fa-solid fa-school input-icon"></i>
                                    <input type="text" class="form-control-custom is-readonly" name="library_name"
                                        value="{{ old('library_name', $library->library_name ?? '') }}" placeholder="Enter library name" readonly>
                                    <i class="fa-solid fa-lock lock-icon" title="Locked by system"></i>
                                </div>
                                <span class="helper-note-text"><i class="fa-solid fa-circle-info"></i> Registered library name cannot be edited directly.</span>
                            </div>
                        </div>

                        <!-- Library Email -->
                        <div class="col-md-6">
                            <div class="form-group-wrap">
                                <label class="form-label-custom">Official Email Address <span>*</span></label>
                                <div class="input-with-icon">
                                    <i class="fa-solid fa-envelope input-icon"></i>
                                    <input type="email" class="form-control-custom is-readonly" name="email"
                                        value="{{ old('email', $library->email ?? '') }}" readonly>
                                    <i class="fa-solid fa-lock lock-icon" title="Locked by system"></i>
                                </div>
                            </div>
                        </div>

                        <!-- Library Contact -->
                        <div class="col-md-6">
                            <div class="form-group-wrap">
                                <label class="form-label-custom">WhatsApp Contact No. <span>*</span></label>
                                <div class="input-with-icon">
                                    <i class="fa-brands fa-whatsapp input-icon" style="color: #25D366;"></i>
                                    <input type="text" class="form-control-custom is-readonly digit-only" name="library_mobile" maxlength="10"
                                        value="{{ old('library_mobile', $library->library_mobile ?? '') }}" readonly>
                                    <i class="fa-solid fa-lock lock-icon" title="Locked by system"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Owner Details (Editable) -->
                <div class="form-section-card">
                    <div class="section-header-strip">
                        <div class="section-header-left">
                            <div class="section-header-badge badge-purple">
                                <i class="fa-solid fa-user-gear"></i>
                            </div>
                            <div>
                                <h4 class="section-header-title">Library Owner Details</h4>
                                <p class="section-header-sub">Manage the designated primary contact person and account owner</p>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-12">
                            <div class="form-group-wrap">
                                <label class="form-label-custom" for="library_owner">Owner Full Name <span>*</span></label>
                                <div class="input-with-icon">
                                    <i class="fa-solid fa-user-pen input-icon"></i>
                                    <input type="text" class="form-control-custom char-only @error('library_owner') is-invalid @enderror" 
                                        name="library_owner" 
                                        id="library_owner"
                                        value="{{ old('library_owner', $library->library_owner ?? '') }}"
                                        placeholder="Enter library owner's full name"
                                        required>
                                </div>
                                @error('library_owner')
                                <span class="text-danger d-block mt-1 font-12" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                                <span class="helper-note-text"><i class="fa-solid fa-circle-check" style="color: #16a34a;"></i> This name will appear on official library invoices, receipts, and system correspondence.</span>
                            </div>
                        </div>
                    </div>

                    <div class="profile-action-footer">
                        <button type="submit" class="btn-save-profile">
                            <i class="fa-solid fa-floppy-disk"></i> Update Profile
                        </button>
                        <a href="{{ route('library.master') }}" class="btn-cancel-profile">
                            Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

@include('library.script')
@endsection