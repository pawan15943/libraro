@extends('layouts.library')

@section('content')
<link href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css" rel="stylesheet" />
<link rel="stylesheet" href="{{ asset('public/css/demo-inquiry.css') }}?v={{ time() }}" />

<style>
/* Scoped fallback styles for .demo-inquiry-module */
.demo-inquiry-module {
    font-family: 'Outfit', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    color: #1e293b;
    padding-bottom: 2rem;
}
.demo-inquiry-module .demo-inquiry-wrapper {
    max-width: 740px;
    width: 100%;
    margin: 1.25rem auto 2.5rem auto;
}
.demo-inquiry-module .inquiry-card {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    margin-bottom: 1.15rem;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}
.demo-inquiry-module .inquiry-card:hover {
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.04);
}
.demo-inquiry-module .inquiry-card-header {
    padding: 0.8rem 1.25rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid transparent;
}
.demo-inquiry-module .inquiry-card-header.header-purple {
    background-color: #f5f3ff;
    border-bottom-color: #ede9fe;
}
.demo-inquiry-module .inquiry-card-header.header-blue {
    background-color: #eff6ff;
    border-bottom-color: #dbeafe;
}
.demo-inquiry-module .inquiry-card-header.header-green {
    background-color: #ecfdf5;
    border-bottom-color: #d1fae5;
}
.demo-inquiry-module .inquiry-card-header.header-amber {
    background-color: #fffbeb;
    border-bottom-color: #fef3c7;
    cursor: pointer;
    user-select: none;
    transition: background-color 0.15s ease;
}
.demo-inquiry-module .inquiry-card-header.header-amber:hover {
    background-color: #fef3c7;
}
.demo-inquiry-module .inquiry-header-left {
    display: flex;
    align-items: center;
    gap: 0.75rem;
}
.demo-inquiry-module .inquiry-header-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.05rem;
    flex-shrink: 0;
}
.demo-inquiry-module .header-purple .inquiry-header-icon {
    background-color: #ede9fe;
    color: #7c3aed;
}
.demo-inquiry-module .header-blue .inquiry-header-icon {
    background-color: #dbeafe;
    color: #2563eb;
}
.demo-inquiry-module .header-green .inquiry-header-icon {
    background-color: #d1fae5;
    color: #16a34a;
}
.demo-inquiry-module .header-amber .inquiry-header-icon {
    background-color: #fef3c7;
    color: #d97706;
}
.demo-inquiry-module .inquiry-header-title {
    font-size: 0.96rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0;
    line-height: 1.3;
    font-family: 'Outfit', sans-serif;
}
.demo-inquiry-module .inquiry-header-subtitle {
    font-size: 0.78rem;
    color: #64748b;
    margin: 0;
    line-height: 1.3;
    font-weight: 400;
}
.demo-inquiry-module .inquiry-header-badge {
    font-size: 0.78rem;
    font-weight: 500;
    color: #64748b;
}
.demo-inquiry-module .inquiry-header-toggle {
    font-size: 0.95rem;
    color: #18225f;
    transition: transform 0.25s ease;
}
.demo-inquiry-module .inquiry-header-toggle.expanded {
    transform: rotate(180deg);
}
.demo-inquiry-module .inquiry-card-body {
    padding: 1.25rem 1.35rem;
}
.demo-inquiry-module .upload-photo-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 0.6rem 0;
    text-align: center;
}
.demo-inquiry-module .upload-avatar-wrapper {
    position: relative;
    display: inline-block;
    cursor: pointer;
    margin-bottom: 0.6rem;
}
.demo-inquiry-module .upload-avatar-circle {
    width: 88px;
    height: 88px;
    border-radius: 50%;
    background: #f1f5f9;
    border: 3px solid #ede9fe;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
    margin: 0 auto;
    transition: all 0.22s ease;
    box-shadow: 0 2px 8px rgba(124, 58, 237, 0.1);
}
.demo-inquiry-module .upload-avatar-wrapper:hover .upload-avatar-circle {
    border-color: #7c3aed;
    transform: scale(1.04);
    box-shadow: 0 4px 14px rgba(124, 58, 237, 0.2);
}
.demo-inquiry-module .upload-avatar-circle i.avatar-icon-placeholder {
    font-size: 2.6rem;
    color: #94a3b8;
    transition: opacity 0.2s ease;
}
.demo-inquiry-module .upload-avatar-circle img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100% !important;
    height: 100% !important;
    object-fit: cover !important;
    border-radius: 50% !important;
    z-index: 2;
}
.demo-inquiry-module .avatar-hover-overlay {
    position: absolute;
    inset: 0;
    background: rgba(15, 23, 42, 0.45);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #ffffff;
    opacity: 0;
    transition: opacity 0.2s ease;
    font-size: 0.72rem;
    font-weight: 500;
    gap: 2px;
    z-index: 3;
    border-radius: 50%;
}
.demo-inquiry-module .upload-avatar-wrapper:hover .avatar-hover-overlay {
    opacity: 1;
}
.demo-inquiry-module .avatar-hover-overlay i {
    font-size: 1.15rem;
}
.demo-inquiry-module .avatar-camera-badge {
    position: absolute;
    bottom: 2px;
    right: 2px;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #7c3aed;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    border: 2px solid #ffffff;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.18);
    transition: background-color 0.2s ease, transform 0.2s ease;
    z-index: 4;
}
.demo-inquiry-module .upload-avatar-wrapper:hover .avatar-camera-badge {
    background: #6d28d9;
    transform: scale(1.08);
}
.demo-inquiry-module .upload-avatar-title {
    font-weight: 600;
    font-size: 0.88rem;
    color: #1e293b;
    margin-bottom: 0.15rem;
    transition: color 0.15s ease;
}
.demo-inquiry-module .upload-avatar-wrapper:hover ~ div .upload-avatar-title {
    color: #7c3aed;
}
.demo-inquiry-module .upload-avatar-subtext {
    font-size: 0.76rem;
    color: #64748b;
    margin: 0;
}
.demo-inquiry-module .form-group {
    margin-bottom: 0.25rem;
}
.demo-inquiry-module .form-label {
    font-size: 0.85rem;
    font-weight: 600;
    color: #1e293b;
    margin-bottom: 0.35rem;
    display: block;
    font-family: 'Outfit', sans-serif;
}
.demo-inquiry-module .form-label .required-star {
    color: #ef4444;
    margin-left: 2px;
}
.demo-inquiry-module .form-hint {
    font-size: 0.74rem;
    color: #64748b;
    margin-bottom: 0.4rem;
    display: block;
    line-height: 1.25;
}
.demo-inquiry-module .form-control,
.demo-inquiry-module .form-select {
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    padding: 0.52rem 0.85rem;
    font-size: 0.88rem;
    color: #1e293b;
    background-color: #ffffff;
    min-height: 40px;
    font-family: 'Outfit', sans-serif;
    box-shadow: none;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}
.demo-inquiry-module .form-control:focus,
.demo-inquiry-module .form-select:focus {
    border-color: #18225f;
    box-shadow: 0 0 0 3px rgba(24, 34, 95, 0.08);
    outline: none;
}
.demo-inquiry-module .form-control[readonly],
.demo-inquiry-module .form-select[disabled] {
    background-color: #f8fafc;
    color: #64748b;
    cursor: default;
}
/* Document Drag and Drop Zone */
.demo-inquiry-module .doc-dropzone {
    border: 2px dashed #94a3b8;
    border-radius: 10px;
    padding: 1.25rem 1rem;
    text-align: center;
    background: #f8fafc;
    cursor: pointer;
    min-height: 100px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    user-select: none;
    position: relative;
}
.demo-inquiry-module .doc-dropzone:hover:not(.disabled) {
    border-color: #18225f;
    background: #f0f4ff;
}
.demo-inquiry-module .doc-dropzone.dragover {
    border-color: #18225f;
    background: #dbeafe;
    border-style: solid;
}
.demo-inquiry-module .doc-dropzone.disabled {
    opacity: 0.65;
    background: #f1f5f9;
    border: 2px dashed #cbd5e1;
    cursor: not-allowed;
}
.demo-inquiry-module .doc-dropzone.disabled .doc-dropzone-icon {
    color: #94a3b8;
}
.demo-inquiry-module .doc-dropzone.disabled .doc-dropzone-text {
    color: #64748b;
}
.demo-inquiry-module .doc-dropzone.disabled .browse-link {
    color: #64748b;
    text-decoration: none;
}
.demo-inquiry-module .doc-dropzone-content {
    pointer-events: none;
}
.demo-inquiry-module .doc-dropzone-icon {
    font-size: 1.8rem;
    color: #2563eb;
    margin-bottom: 0.35rem;
    transition: color 0.15s ease;
}
.demo-inquiry-module .doc-dropzone-text {
    font-size: 0.88rem;
    font-weight: 600;
    color: #1e293b;
    line-height: 1.3;
}
.demo-inquiry-module .doc-dropzone-text .browse-link {
    color: #18225f;
    text-decoration: underline;
    font-weight: 700;
}
.demo-inquiry-module .doc-dropzone-hint {
    font-size: 0.76rem;
    color: #64748b;
    margin-top: 3px;
}
.demo-inquiry-module .doc-dropzone-preview {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 0.84rem;
    padding: 0.45rem 1rem;
    background: #ffffff;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
}
.demo-inquiry-module .doc-file-info {
    display: flex;
    flex-direction: column;
    text-align: left;
}
.demo-inquiry-module .doc-file-name {
    font-weight: 600;
    color: #1e293b;
    max-width: 320px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    font-size: 0.86rem;
}
.demo-inquiry-module .doc-file-action {
    cursor: pointer;
    font-weight: 500;
    font-size: 0.76rem;
    pointer-events: auto;
}
.demo-inquiry-module .doc-file-action:hover {
    text-decoration: underline;
}
.demo-inquiry-module .form-action-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 1.25rem;
    padding-top: 0.4rem;
}
.demo-inquiry-module .btn-clear-form {
    background: #ffffff;
    border: 1px solid #cbd5e1;
    color: #334155;
    font-size: 0.88rem;
    font-weight: 500;
    padding: 0.55rem 1.6rem;
    border-radius: 8px;
    transition: all 0.15s ease;
    font-family: 'Outfit', sans-serif;
}
.demo-inquiry-module .btn-clear-form:hover {
    background: #f1f5f9;
    border-color: #94a3b8;
    color: #0f172a;
}
.demo-inquiry-module .btn-submit-inquiry {
    background: #18225f !important;
    border: 1px solid #18225f;
    color: #ffffff !important;
    font-size: 0.92rem;
    font-weight: 600;
    padding: 0.55rem 2.25rem;
    min-width: 160px;
    border-radius: 8px;
    transition: all 0.15s ease;
    font-family: 'Outfit', sans-serif;
    box-shadow: 0 2px 4px rgba(24, 34, 95, 0.15);
}
.demo-inquiry-module .btn-submit-inquiry:hover {
    background: #0f172a !important;
    border-color: #0f172a;
}
</style>

<div class="demo-inquiry-module">
    <div class="demo-inquiry-wrapper">

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

        <form action="{{ route('demo-users.store') }}" method="POST" enctype="multipart/form-data" id="demoInquiryForm">
            @csrf
            <input type="hidden" id="branch_id" value="{{ $branch->id }}">

            {{-- 1. UPLOAD PROFILE PHOTO CARD (CLICKABLE CIRCLE) --}}
            <div class="inquiry-card">
                <div class="inquiry-card-header header-purple">
                    <div class="inquiry-header-left">
                        <div class="inquiry-header-icon">
                            <i class="fa-solid fa-file-lines"></i>
                        </div>
                        <div>
                            <h4 class="inquiry-header-title">Upload Profile Photo</h4>
                            <p class="inquiry-header-subtitle">Upload learner's profile photo (optional).</p>
                        </div>
                    </div>
                    <span class="inquiry-header-badge">JPG, PNG (Max 5 MB)</span>
                </div>
                <div class="inquiry-card-body">
                    <div class="upload-photo-container">
                        <div class="upload-avatar-wrapper" id="avatarUploadTrigger" title="Click to upload profile photo">
                            <div class="upload-avatar-circle" id="avatarCircle">
                                <i class="fa-solid fa-user avatar-icon-placeholder" id="avatarDefaultIcon"></i>
                                <img id="avatarCroppedPreview" src="" alt="Profile Photo" style="display:none;">
                                <div class="avatar-hover-overlay">
                                    <i class="fa-solid fa-camera"></i>
                                    <span>Upload</span>
                                </div>
                            </div>
                            <div class="avatar-camera-badge">
                                <i class="fa-solid fa-camera"></i>
                            </div>
                        </div>
                        <div>
                            <div class="upload-avatar-title" id="avatarStatusTitle">Click avatar to upload photo</div>
                            <p class="upload-avatar-subtext" id="avatarSubtext">Upload a clear photo for identification (optional).</p>
                        </div>

                        {{-- Hidden File Input --}}
                        <input
                            type="file"
                            class="d-none image-cropper"
                            name="profile_picture"
                            id="profile_picture"
                            autocomplete="off"
                            accept=".jpeg, .jpg, .png, .webp" />
                        <img class="preview-img" alt="Preview" style="display:none;">
                    </div>
                    @error('profile_picture')
                        <div class="text-danger small mt-1 text-center">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            {{-- 2. BASIC INFORMATION CARD --}}
            <div class="inquiry-card">
                <div class="inquiry-card-header header-blue">
                    <div class="inquiry-header-left">
                        <div class="inquiry-header-icon">
                            <i class="fa-regular fa-user"></i>
                        </div>
                        <div>
                            <h4 class="inquiry-header-title">Basic Information</h4>
                            <p class="inquiry-header-subtitle">Add the basic details of the inquiry.</p>
                        </div>
                    </div>
                </div>
                <div class="inquiry-card-body">
                    <div class="row g-3">
                        {{-- Assign Seat No? --}}
                        <div class="col-md-6 form-group">
                            <label for="general_seat2" class="form-label">Assign Seat No?</label>
                            <select name="general_seat" id="general_seat2" class="form-select @error('general_seat') is-invalid @enderror">
                                <option value="yes" {{ old('general_seat', 'yes') == 'yes' ? 'selected' : '' }}>No</option>
                                <option value="no" {{ old('general_seat') == 'no' ? 'selected' : '' }}>Yes, Allot a Seat No.</option>
                            </select>
                            @error('general_seat') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Choose Seat No --}}
                        <div class="col-md-6 form-group">
                            <label for="seat_id2" class="form-label">Choose Seat No. <span class="required-star">*</span></label>
                            <select name="seat_no" class="form-select @error('seat_no') is-invalid @enderror" id="seat_id2" disabled>
                                <option value="" selected>Choose Seat No</option>
                                @foreach($newAvailableSeat as $key => $value)
                                    <option value="{{ $value['main'] }}" {{ old('seat_no') == $value['main'] ? 'selected' : '' }}>
                                        {{ $value['display'] }}
                                    </option>
                                @endforeach
                            </select>
                            @error('seat_no') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Name --}}
                        <div class="col-md-6 form-group">
                            <label for="name" class="form-label">Name <span class="required-star">*</span></label>
                            <input
                                type="text"
                                name="name"
                                id="name"
                                value="{{ old('name') }}"
                                placeholder="Enter name"
                                class="form-control char-only @error('name') is-invalid @enderror">
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Mobile (WhatsApp No) - No +91 prefix --}}
                        <div class="col-md-6 form-group">
                            <label for="mobile" class="form-label">Mobile (WhatsApp No) <span class="required-star">*</span></label>
                            <input
                                type="text"
                                name="mobile"
                                id="mobile"
                                value="{{ old('mobile') }}"
                                placeholder="Enter mobile number"
                                class="form-control digit-only @error('mobile') is-invalid @enderror"
                                maxlength="10"
                                minlength="8">
                            @error('mobile') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>

                        {{-- DOB (optional) --}}
                        @if(!in_array('2', toggleHideField()))
                        <div class="col-md-6 form-group">
                            <label for="dob" class="form-label">DOB (optional)</label>
                            <input
                                type="date"
                                class="form-control dob"
                                value="{{ old('dob') }}"
                                name="dob"
                                id="dob"
                                max="{{ date('Y-m-d', strtotime('-10 years')) }}">
                            @error('dob') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        @endif

                        {{-- Email Id (optional) --}}
                        @if(!in_array('1', toggleHideField()))
                        <div class="col-md-6 form-group">
                            <label for="email" class="form-label">Email Id (optional)</label>
                            <input
                                type="email"
                                class="form-control @error('email') is-invalid @enderror"
                                name="email"
                                value="{{ old('email') }}"
                                id="email"
                                placeholder="Enter email id">
                            <span class="text-danger small" id="email-error"></span>
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- 3. PLAN DETAILS CARD --}}
            <div class="inquiry-card">
                <div class="inquiry-card-header header-green">
                    <div class="inquiry-header-left">
                        <div class="inquiry-header-icon">
                            <i class="fa-solid fa-book-open"></i>
                        </div>
                        <div>
                            <h4 class="inquiry-header-title">Plan Details</h4>
                            <p class="inquiry-header-subtitle">Select the plan and shift details for the inquiry.</p>
                        </div>
                    </div>
                </div>
                <div class="inquiry-card-body">
                    <div class="row g-3">
                        {{-- Plan --}}
                        <div class="col-md-6 form-group">
                            <label for="plan_id4" class="form-label">Plan <span class="required-star">*</span></label>
                            <select name="plan_id" id="plan_id4" class="form-select @error('plan_id') is-invalid @enderror">
                                <option value="">Choose</option>
                                @foreach($plans as $key => $value)
                                    <option value="{{ $value->id }}" {{ old('plan_id') == $value->id ? 'selected' : '' }}>
                                        {{ $value->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('plan_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Plan Type / Shift --}}
                        <div class="col-md-6 form-group">
                            <label for="temp_plan_type_id" class="form-label">Plan Type / Shift <span class="required-star">*</span></label>
                            <select name="plan_type_id" id="temp_plan_type_id" class="form-select @error('plan_type_id') is-invalid @enderror">
                                <option value="">Choose Shift</option>
                            </select>
                            @error('plan_type_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Plan Starts On --}}
                        <div class="col-md-6 form-group">
                            <label for="plan_start_date" class="form-label">Plan Starts On <span class="required-star">*</span></label>
                            <input
                                type="date"
                                class="form-control datepicker @error('plan_start_date') is-invalid @enderror"
                                name="plan_start_date"
                                id="plan_start_date"
                                value="{{ old('plan_start_date', now()->format('Y-m-d')) }}">
                            @error('plan_start_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Final Payable Amount (INR) --}}
                        <div class="col-md-6 form-group">
                            <label for="plan_price" class="form-label">Final Payable Amount (INR) <span class="required-star">*</span></label>
                            <input
                                id="plan_price"
                                type="text"
                                class="form-control digit-only @error('plan_price_id') is-invalid @enderror"
                                name="plan_price_id"
                                placeholder="Example : 00"
                                value="{{ old('plan_price_id') }}"
                                readonly>
                            @error('plan_price_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <span id="chargeable_day_book" class="text-info small mt-1 d-block"></span>
                        </div>

                        {{-- Payment Mode --}}
                        <div class="col-md-6 form-group">
                            <label for="payment_mode" class="form-label">Payment Mode <span class="required-star">*</span></label>
                            <select name="payment_mode" id="payment_mode" class="form-select @error('payment_mode') is-invalid @enderror">
                                <option value="">Select Payment Mode</option>
                                <option value="paylater" {{ old('payment_mode', 'paylater') == 'paylater' ? 'selected' : '' }}>Pay Later</option>
                                <option value="offline" {{ old('payment_mode') == 'offline' ? 'selected' : '' }}>Offline (Cash / UPI)</option>
                                <option value="online" {{ old('payment_mode') == 'online' ? 'selected' : '' }}>Online</option>
                            </select>
                            @error('payment_mode') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- 4. OTHER OPTIONAL FIELDS CARD (COLLAPSIBLE) --}}
            @if(!in_array('7', toggleHideField()))
            <div class="inquiry-card">
                <div class="inquiry-card-header header-amber" id="optionalFieldsToggle">
                    <div class="inquiry-header-left">
                        <div class="inquiry-header-icon">
                            <i class="fa-solid fa-file-contract"></i>
                        </div>
                        <div>
                            <h4 class="inquiry-header-title">Other Optional Fields</h4>
                            <p class="inquiry-header-subtitle">Add additional details if available (ID proof and address).</p>
                        </div>
                    </div>
                    <i class="fa-solid fa-chevron-down inquiry-header-toggle" id="optionalToggleChevron"></i>
                </div>
                <div class="inquiry-card-body" id="optionalFieldsContent" style="display: none;">
                    <div class="row g-3">
                        @if(!in_array('5', toggleHideField()))
                        {{-- Id Proof Received --}}
                        <div class="col-md-6 form-group">
                            <label for="demo_id_proof_name" class="form-label">Id Proof Received</label>
                            <select class="form-select" name="id_proof_name" id="demo_id_proof_name">
                                <option value="">Select Id Proof</option>
                                <option value="1" {{ (old('id_proof_name') ?? '') == '1' ? 'selected' : '' }}>Aadhar Card</option>
                                <option value="2" {{ (old('id_proof_name') ?? '') == '2' ? 'selected' : '' }}>Driving License</option>
                                <option value="4" {{ (old('id_proof_name') ?? '') == '4' ? 'selected' : '' }}>Pan Card</option>
                                <option value="5" {{ (old('id_proof_name') ?? '') == '5' ? 'selected' : '' }}>Voter Id</option>
                                <option value="3" {{ (old('id_proof_name') ?? '') == '3' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>

                        {{-- ID Proof No. --}}
                        <div class="col-md-6 form-group">
                            <label for="demo_id_proof_number" class="form-label">ID Proof No.</label>
                            <input
                                type="text"
                                class="form-control @error('id_proof_number') is-invalid @enderror"
                                name="id_proof_number"
                                id="demo_id_proof_number"
                                placeholder="Enter ID proof no."
                                maxlength="12"
                                value="{{ old('id_proof_number') }}">
                            @error('id_proof_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        {{-- Upload Scan Copy of Proof (Separate Row) --}}
                        <div class="col-12 form-group">
                            <label for="demo_id_proof_file" class="form-label">Upload Scan Copy of Proof</label>
                            <div class="doc-dropzone disabled" id="docDropzone" title="Select Id Proof type above to enable upload">
                                <div class="doc-dropzone-content" id="docDropzoneContent">
                                    <i class="fa-solid fa-cloud-arrow-up doc-dropzone-icon"></i>
                                    <div class="doc-dropzone-text">Drag & drop file here or <span class="browse-link">browse</span></div>
                                    <div class="doc-dropzone-hint" id="docDropzoneHint">Select ID proof type above to enable upload</div>
                                </div>

                                <div class="doc-dropzone-preview" id="docDropzonePreview" style="display: none;">
                                    <i class="fa-solid fa-file-circle-check text-success fs-3"></i>
                                    <div class="doc-file-info">
                                        <span class="doc-file-name" id="docFileName"></span>
                                        <span class="doc-file-action text-danger" id="removeDocFile" title="Remove file"><i class="fa-solid fa-trash-can"></i> Remove</span>
                                    </div>
                                </div>
                            </div>
                            <input
                                type="file"
                                class="d-none id_proof_file image-cropper @error('id_proof_file') is-invalid @enderror"
                                name="id_proof_file"
                                id="demo_id_proof_file"
                                disabled
                                autocomplete="off"
                                accept=".jpeg, .jpg, .png, .webp, .pdf">
                            <img class="preview-img one d-none" data-src="" style="max-width:180px; margin-top:0.5rem; border-radius:6px;">
                            @error('id_proof_file') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                        @endif

                        {{-- Address --}}
                        @if(!in_array('32', toggleHideField()))
                        <div class="col-12 form-group">
                            <label for="address" class="form-label">Address</label>
                            <textarea
                                class="form-control"
                                name="address"
                                id="address"
                                rows="3"
                                placeholder="Enter address">{{ old('address') }}</textarea>
                            @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            {{-- 5. FOOTER ACTIONS BAR --}}
            <div class="form-action-bar">
                <button type="button" id="btnResetInquiry" class="btn btn-clear-form">Clear</button>
                <button type="submit" class="btn btn-submit-inquiry">Submit</button>
            </div>

        </form>
    </div>
</div>

<script>
$(document).ready(function () {
    // 1. Toggle Collapsible Optional Fields
    $('#optionalFieldsToggle').on('click', function () {
        $('#optionalFieldsContent').slideToggle(200);
        $('#optionalToggleChevron').toggleClass('expanded');
    });

    // Helper to update avatar preview inside circle
    function setAvatarPreview(src, title) {
        if (!src) return;
        const $img = $('#avatarCroppedPreview');
        const $icon = $('#avatarDefaultIcon');
        $img.attr('src', src).css('display', 'block');
        $icon.css('display', 'none');
        if (title) {
            $('#avatarStatusTitle').text(title);
        }
        $('#avatarSubtext').text('Click avatar to change photo.');
    }

    // 2. Click Avatar Circle to Trigger File Input
    $('#avatarUploadTrigger').on('click', function (e) {
        e.preventDefault();
        $('#profile_picture').trigger('click');
    });

    // 2b. Show preview in circle immediately when photo is chosen
    $('#profile_picture').on('change', function () {
        const file = this.files && this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                setAvatarPreview(e.target.result, 'Photo selected');
            };
            reader.readAsDataURL(file);
        }
    });

    // 2c. Observer for Cropped Image (updates whenever cropper modifies sibling preview)
    const profileSibling = document.querySelector('#profile_picture ~ .preview-img');
    if (profileSibling) {
        const profileObserver = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'src') {
                    const src = profileSibling.getAttribute('src') || profileSibling.src;
                    if (src && src !== window.location.href) {
                        setAvatarPreview(src, 'Photo uploaded');
                    }
                }
            });
        });
        profileObserver.observe(profileSibling, { attributes: true });
    }

    // 2d. Observer for ID Proof Cropped Image
    const docSibling = document.querySelector('.preview-img.one');
    if (docSibling) {
        const docObserver = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                if (mutation.type === 'attributes' && mutation.attributeName === 'src') {
                    const src = docSibling.getAttribute('src') || docSibling.src;
                    if (src && src !== window.location.href) {
                        $('#docFileName').text('Document Attached');
                        $('#docDropzoneContent').hide();
                        $('#docDropzonePreview').show();
                    }
                }
            });
        });
        docObserver.observe(docSibling, { attributes: true });
    }

    // 2e. Crop button click listener (fallback / fast update)
    $(document).on('click', '.cropbtn', function () {
        setTimeout(function () {
            const pImg = document.querySelector('#profile_picture ~ .preview-img');
            if (pImg && pImg.src && pImg.src !== window.location.href) {
                setAvatarPreview(pImg.src, 'Photo uploaded');
            }
            const dImg = document.querySelector('.preview-img.one');
            if (dImg && dImg.src && dImg.src !== window.location.href) {
                $('#docFileName').text('Document Attached');
                $('#docDropzoneContent').hide();
                $('#docDropzonePreview').show();
            }
        }, 120);
    });

    // 3. Clear Button Handler
    $('#btnResetInquiry').on('click', function () {
        $('#demoInquiryForm')[0].reset();
        $('#seat_id2').prop('disabled', true).val('');
        $('#temp_plan_type_id').html('<option value="">Choose Shift</option>');
        $('#plan_price').val('');
        $('#chargeable_day_book').text('');
        
        // Reset Avatar Preview
        $('#avatarCroppedPreview').attr('src', '').css('display', 'none');
        $('#avatarDefaultIcon').css('display', 'inline-block');
        $('#avatarStatusTitle').text('Click avatar to upload photo');
        $('#avatarSubtext').text('Upload a clear photo for identification (optional).');

        // Reset any hidden previews
        $('.preview-img').removeAttr('src');

        // Reset ID proof dropzone state
        updateIdProofUploadState();
    });

    // 3b. ID Proof Document Drag & Drop and Enable/Disable Logic
    function updateIdProofUploadState() {
        const proofSelected = $('#demo_id_proof_name').val() || $('#demoInquiryForm select[name="id_proof_name"]').val();
        const $dropzone = $('#docDropzone');
        const $fileInput = $('#demo_id_proof_file');
        const $hint = $('#docDropzoneHint');

        if (proofSelected && proofSelected !== '') {
            $dropzone.removeClass('disabled').attr('title', 'Click or drag file to upload');
            $fileInput.prop('disabled', false);
            $hint.text('Supports JPG, PNG, WEBP, PDF (Max 5 MB)');
        } else {
            $dropzone.addClass('disabled').attr('title', 'Select Id Proof type above to enable upload');
            $fileInput.prop('disabled', true).val('');
            $hint.text('Select ID proof type above to enable upload');
            $('#docDropzoneContent').show();
            $('#docDropzonePreview').hide();
            $('.preview-img.one').hide().removeAttr('src');
        }
    }

    updateIdProofUploadState();
    $(document).on('change input', '#demo_id_proof_name, #demoInquiryForm select[name="id_proof_name"]', function () {
        updateIdProofUploadState();
    });

    // Document dropzone click
    $('#docDropzone').on('click', function (e) {
        if ($(e.target).closest('#removeDocFile').length) {
            return;
        }

        if ($(this).hasClass('disabled')) {
            $('#demo_id_proof_name').focus().addClass('is-invalid');
            setTimeout(function() {
                $('#demo_id_proof_name').removeClass('is-invalid');
            }, 1500);
            return;
        }

        $('#demo_id_proof_file').trigger('click');
    });

    // Remove document button
    $('#removeDocFile').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $('#demo_id_proof_file').val('');
        $('#docDropzoneContent').show();
        $('#docDropzonePreview').hide();
        $('.preview-img.one').hide().removeAttr('src');
    });

    // Direct change event on id_proof_file
    $(document).on('change', '#demo_id_proof_file', function () {
        const file = this.files && this.files[0];
        if (file) {
            $('#docFileName').text(file.name);
            $('#docDropzoneContent').hide();
            $('#docDropzonePreview').css('display', 'flex');
        }
    });

    // Drag and drop handlers for docDropzone
    const docDropzoneEl = document.getElementById('docDropzone');
    if (docDropzoneEl) {
        ['dragenter', 'dragover'].forEach(eventName => {
            docDropzoneEl.addEventListener(eventName, function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (!$('#docDropzone').hasClass('disabled')) {
                    $('#docDropzone').addClass('dragover');
                }
            }, false);
        });

        ['dragleave', 'dragend'].forEach(eventName => {
            docDropzoneEl.addEventListener(eventName, function(e) {
                e.preventDefault();
                e.stopPropagation();
                $('#docDropzone').removeClass('dragover');
            }, false);
        });

        docDropzoneEl.addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            $('#docDropzone').removeClass('dragover');

            if ($('#docDropzone').hasClass('disabled')) {
                $('#demo_id_proof_name').focus().addClass('is-invalid');
                setTimeout(function() {
                    $('#demo_id_proof_name').removeClass('is-invalid');
                }, 1500);
                return;
            }

            const dt = e.dataTransfer;
            const files = dt.files;
            if (files && files.length > 0) {
                const fileInput = document.getElementById('demo_id_proof_file');
                if (fileInput) {
                    const transfer = new DataTransfer();
                    transfer.items.add(files[0]);
                    fileInput.files = transfer.files;
                    $(fileInput).trigger('change');
                }
            }
        }, false);
    }

    // 4. Plan & Shift Logic
    let oldPlanTypeId = "{{ old('plan_type_id') }}";

    function loadPlanTypes() {
        const generalSeat = $('#general_seat2').val();
        const seatId = $('#seat_id2').val();
        const branch_id = $('#branch_id').val();

        if (generalSeat === 'yes') {
            // General seat → no seat-wise filter
            $('#seat_id2').prop('disabled', true).val('');
            getTypeSeatwise('', branch_id);
        } else if (generalSeat === 'no') {
            // Specific seat → enable seat selection
            $('#seat_id2').prop('disabled', false);

            if (seatId) {
                getTypeSeatwise(seatId, branch_id);
            } else {
                $('#temp_plan_type_id').html('<option value="">Choose Shift</option>');
            }
        } else {
            $('#seat_id2').prop('disabled', true).val('');
            $('#temp_plan_type_id').html('<option value="">Choose Shift</option>');
        }
    }

    loadPlanTypes();

    $('#general_seat2').on('change', function() {
        loadPlanTypes();
    });

    $('#seat_id2').on('change', function() {
        const generalSeat = $('#general_seat2').val();
        if (generalSeat === 'no') {
            const seatId = $(this).val();
            const branch_id = $('#branch_id').val();
            if (seatId) {
                getTypeSeatwise(seatId, branch_id);
            } else {
                $('#temp_plan_type_id').html('<option value="">Choose Shift</option>');
            }
        }
    });

    // 5. Plan Price Calculation
    $('#plan_id4, #temp_plan_type_id, #plan_start_date').on('change', function() {
        let plan_id = $('#plan_id4').val();
        let plan_type_id = $('#temp_plan_type_id').val();
        let branch_id = $('#branch_id').val();
        let plan_start_date = $('#plan_start_date').val();

        if (plan_id && plan_type_id && branch_id && plan_start_date) {
            $.ajax({
                url: "{{ route('get.plan.price') }}",
                type: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    plan_id: plan_id,
                    plan_type_id: plan_type_id,
                    branch_id: branch_id,
                    plan_start_date: plan_start_date
                },
                success: function(response) {
                    if (response.success) {
                        $('#plan_price').val(response.price);
                    } else {
                        $('#plan_price').val('');
                    }
                }
            });
        }

        if (plan_id && plan_start_date) {
            $.ajax({
                url: "{{ route('getChargeableDays') }}",
                type: "GET",
                data: {
                    plan_id: plan_id,
                    plan_start_date: plan_start_date,
                    branch_id: branch_id
                },
                success: function (res) {
                    if (res.fixedBillingDate == 'true') {
                        $('#chargeable_day_book').text('Billed for ' + res.chargeable_days + ' Days');
                    } else {
                        $('#chargeable_day_book').text('');
                    }
                }
            });
        }
    });

    function getTypeSeatwise(seatId, branchId) {
        $('#temp_plan_type_id').empty().append('<option value="">Choose Shift</option>');
        $.ajax({
            url: '{{ route('getPlantypeSeatwise') }}',
            type: 'GET',
            data: {
                "_token": "{{ csrf_token() }}",
                "seatNo": seatId,
                "branchId": branchId
            },
            dataType: 'json',
            success: function(html) {
                if (html) {
                    if (html.length === 0) {
                        $("#temp_plan_type_id").empty().append(
                            '<option value="">No added plan type</option>'
                        );
                        return;
                    }
                    let selectedValue = oldPlanTypeId 
                        ? oldPlanTypeId 
                        : $("#temp_plan_type_id").find("option:selected").val();

                    $("#temp_plan_type_id").empty();
                    $("#temp_plan_type_id").append('<option value="">Choose Shift</option>');

                    if (selectedValue) {
                        let selectedText = '';
                        $.each(html, function(index, planType) {
                            if (planType.id == selectedValue) {
                                selectedText = planType.name;
                            }
                        });

                        $("#temp_plan_type_id").append(
                            '<option value="' + selectedValue + '" selected>' +
                            selectedText +
                            '</option>'
                        );
                    }

                    $.each(html, function(index, planType) {
                        if (planType.id != selectedValue) {
                            $("#temp_plan_type_id").append(
                                '<option value="' + planType.id + '">' +
                                planType.name +
                                '</option>'
                            );
                        }
                    });

                    oldPlanTypeId = null;
                } else {
                    $("#temp_plan_type_id").empty();
                    $("#temp_plan_type_id").append('<option value="">Select Plan Type</option>');
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX error:", status, error);
            }
        });
    }
});
</script>
@endsection
