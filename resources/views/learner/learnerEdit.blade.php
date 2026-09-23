@extends('layouts.library')

@section('content')
@php
$current_route = Route::currentRouteName();
$planDetails = getPlanStatusDetails($customer->plan_end_date);
$class = $planDetails['class'];

$planEndDate = $customer->plan_end_date;
$today = \Carbon\Carbon::today();

if ($planEndDate) {
    $endDate = \Carbon\Carbon::parse($planEndDate);
    $diffInDays = $today->diffInDays($endDate, false);
    $extendDays = function_exists('getExtendDays') ? getExtendDays() : 0;
    $inextendDate = $endDate->copy()->addDays($extendDays);
    $diffExtendDay = $today->diffInDays($inextendDate, false);

    if ($diffInDays < 0 && $diffExtendDay < 0) {
        $statusText = 'Expired ' . abs($diffInDays) . ' days ago';
        $statusClass = 'status-expired';
        $statusIcon = 'fa-solid fa-circle-xmark';
    } elseif ($diffInDays < 0 && $diffExtendDay > 0) {
        $statusText = 'Extension: ' . abs($diffExtendDay) . ' days left';
        $statusClass = 'status-warning';
        $statusIcon = 'fa-solid fa-clock-rotate-left';
    } elseif ($diffInDays < 0 && $diffExtendDay == 0) {
        $statusText = 'Extension ends today';
        $statusClass = 'status-warning';
        $statusIcon = 'fa-solid fa-triangle-exclamation';
    } elseif ($diffInDays == 0) {
        $statusText = 'Expires today';
        $statusClass = 'status-warning';
        $statusIcon = 'fa-solid fa-triangle-exclamation';
    } elseif ($diffInDays == 1) {
        $statusText = 'Expires in 1 day';
        $statusClass = 'status-warning';
        $statusIcon = 'fa-solid fa-clock';
    } elseif ($diffInDays <= 5) {
        $statusText = 'Expires in ' . $diffInDays . ' days';
        $statusClass = 'status-warning';
        $statusIcon = 'fa-solid fa-clock';
    } else {
        $statusText = 'Active (Expires in ' . $diffInDays . ' days)';
        $statusClass = 'status-active';
        $statusIcon = 'fa-solid fa-circle-check';
    }
} else {
    $statusText = 'Active';
    $statusClass = 'status-active';
    $statusIcon = 'fa-solid fa-circle-check';
}

if ($customer->locker_no) {
    $locker_read = '';
} else {
    $locker_read = 'readonly';
}
@endphp

<link rel="stylesheet" href="{{ asset('public/css/learner-edit.css') }}?v={{ time() }}" />

{{-- Modal for Viewing Images --}}
<div id="imageViewModal" class="image-modal" style="display:none;">
    <div class="image-modal-content">
        <span class="close-modal">&times;</span>
        <img src="" id="modalImage" alt="Document Preview">
    </div>
</div>

<div class="learner-edit-module">
    <div class="learner-edit-wrapper">

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                <i class="fa-solid fa-circle-exclamation me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- SEAT NO TOP HEADER HERO CARD (GLASSMORPHISM - MATCHING SWAP SEAT & CHANGE PLAN) --}}
        <div class="learner-seat-header-card">
            <div class="seat-header-main">
                <div class="seat-header-identity">
                    <div class="seat-header-avatar-box">
                        @php
                            $learnerProfilePic = $customer->profile_picture ?? ($customer->learner->profile_picture ?? null);
                        @endphp
                        @if($learnerProfilePic && file_exists(public_path($learnerProfilePic)))
                            <img id="topSeatAvatarImg" src="{{ asset($learnerProfilePic) }}" alt="{{ $customer->name }}" class="avatar-user-photo">
                        @elseif(isset($customer->image) && $customer->image)
                            <img id="topSeatAvatarImg" src="{{ asset($customer->image) }}" alt="Seat" class="avatar-seat-chair {{ $class }}">
                        @else
                            <img id="topSeatAvatarImg" src="{{ asset('public/img/booked.png') }}" alt="Seat" class="avatar-seat-chair {{ $class }}">
                        @endif
                    </div>
                    <div class="seat-header-info">
                        <div class="seat-badge-row">
                            <span class="seat-status-badge {{ $statusClass }}">
                                <i class="{{ $statusIcon }} me-1"></i>{{ $statusText }}
                            </span>
                        </div>
                        <h3 class="seat-title text-uppercase">
                            {{ strtoupper($customer->name ?? ($customer->learner->name ?? 'Learner')) }}
                        </h3>
                        <p class="seat-subtitle">
                            <span>UID: <strong class="seat-uid-tag">{{ $customer->learner->learner_no ?? ($customer->learner_no ?? ('#' . $customer->id)) }}</strong></span>
                        </p>
                    </div>
                </div>
                <div class="seat-header-actions">
                    <a href="{{ route('learners') }}" class="btn-seat-back btn-back-desktop" title="Go Back">
                        <i class="fa-solid fa-arrow-left"></i> <span class="btn-back-text">Go Back</span>
                    </a>
                    {{-- Mobile Collapse/Expand Toggle Arrow (Closed by default on mobile) --}}
                    <button type="button" class="btn-seat-collapse is-collapsed" id="btnToggleDetails" title="Show / Hide Details" aria-expanded="false">
                        <i class="fa-solid fa-chevron-down toggle-icon"></i>
                    </button>
                </div>
            </div>

            @php
                $currentSeatNo = $customer->seat_no ?? ($customer->learner->seat_no ?? null);
                $currentBranchId = $customer->branch_id ?? ($customer->learner->branch_id ?? getCurrentBranch());
                $floorDisplay = 'Ground Floor';
                if ($currentSeatNo && is_numeric($currentSeatNo)) {
                    $floorObj = \App\Models\Floor::withoutGlobalScopes()
                        ->where('branch_id', $currentBranchId)
                        ->where('from_seat', '<=', (int)$currentSeatNo)
                        ->where('to_seat', '>=', (int)$currentSeatNo)
                        ->whereNull('deleted_at')
                        ->first();
                    if ($floorObj && !empty($floorObj->name)) {
                        $floorDisplay = str_ends_with(strtolower($floorObj->name), 'floor') ? $floorObj->name : ($floorObj->name . ' Floor');
                    } else {
                        $firstFloor = \App\Models\Floor::withoutGlobalScopes()
                            ->where('branch_id', $currentBranchId)
                            ->whereNull('deleted_at')
                            ->first();
                        if ($firstFloor && !empty($firstFloor->name)) {
                            $floorDisplay = str_ends_with(strtolower($firstFloor->name), 'floor') ? $firstFloor->name : ($firstFloor->name . ' Floor');
                        }
                    }
                }
            @endphp

            {{-- Engaging Mobile-only Seat No & Floor Strip --}}
            <div class="seat-header-mobile-meta">
                <div class="mobile-meta-pill pill-seat">
                    <span class="meta-pill-icon"><i class="fa-solid fa-chair"></i></span>
                    <div class="meta-pill-text">
                        <span class="meta-pill-label">Seat No</span>
                        <strong class="meta-pill-val">{{ $currentSeatNo ? ('#' . $currentSeatNo) : 'Not Assigned' }}</strong>
                    </div>
                </div>
                <div class="mobile-meta-divider"></div>
                <div class="mobile-meta-pill pill-floor">
                    <span class="meta-pill-icon"><i class="fa-solid fa-layer-group"></i></span>
                    <div class="meta-pill-text">
                        <span class="meta-pill-label">Floor</span>
                        <strong class="meta-pill-val">{{ $floorDisplay }}</strong>
                    </div>
                </div>
            </div>

            {{-- 4 GLASSMORPHIC DETAIL TILES (Closed by default on mobile) --}}
            <div class="glass-info-grid is-collapsed" id="glassInfoGrid">
                {{-- Tile 1: Plan Type (e.g. Monthly, Quarterly, Yearly) --}}
                <div class="glass-tile tile-plan">
                    <div class="glass-tile-icon">
                        <i class="fa-solid fa-calendar-days"></i>
                    </div>
                    <div class="glass-tile-content">
                        <div class="glass-tile-label">Current Plan</div>
                        <div class="glass-tile-value">{{ $customer->plan_name ?? 'Monthly' }}</div>
                    </div>
                </div>

                {{-- Tile 2: Shift / Plan --}}
                <div class="glass-tile tile-shift">
                    <div class="glass-tile-icon">
                        <i class="fa-regular fa-clock"></i>
                    </div>
                    <div class="glass-tile-content">
                        <div class="glass-tile-label">Shift / Plan</div>
                        <div class="glass-tile-value">{{ $customer->plan_type_name ?? 'N/A' }}</div>
                    </div>
                </div>

                {{-- Tile 3: Valid Till --}}
                <div class="glass-tile tile-date">
                    <div class="glass-tile-icon">
                        <i class="fa-regular fa-calendar-check"></i>
                    </div>
                    <div class="glass-tile-content">
                        <div class="glass-tile-label">Valid Till</div>
                        <div class="glass-tile-value">
                            {{ $customer->plan_end_date ? \Carbon\Carbon::parse($customer->plan_end_date)->format('d M, Y') : 'N/A' }}
                        </div>
                    </div>
                </div>

                {{-- Tile 4: Contact Mobile --}}
                <div class="glass-tile tile-contact">
                    <div class="glass-tile-icon">
                        <i class="fa-solid fa-phone"></i>
                    </div>
                    <div class="glass-tile-content">
                        <div class="glass-tile-label">Mobile</div>
                        <div class="glass-tile-value">
                            @if($customer->mobile)
                                <a href="tel:{{ $customer->mobile }}">{{ $customer->mobile }}</a>
                            @else
                                <span>Not Provided</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <form action="{{ route('learners.update', $customer->id) }}" method="POST" enctype="multipart/form-data" id="editLearnerForm">

            @csrf
            @method('PUT')
            <input id="edit_seat" type="hidden" name="seat_no" value="{{ old('seat_no', $customer->seat_no) }}">
            <input name="user_id" type="hidden" value="{{ $customer->id }}">
            <input name="learner_id" type="hidden" value="{{ $customer->id }}">
            <input name="plan_id" type="hidden" value="{{ $customer->plan_id }}" id="plan_id10">
            <input name="plan_type_id" type="hidden" value="{{ $customer->plan_type_id }}" id="plan_type_id10">
            <input type="hidden" name="payment_type" value="EDITLEARNER" id="payment_type_operation">

            {{-- 1. UPLOAD PROFILE PHOTO CARD --}}
            @if(!in_array('8', toggleHideField()))
            <div class="edit-card">
                <div class="edit-card-header header-purple">
                    <div class="edit-header-left">
                        <div class="edit-header-icon">
                            <i class="fa-solid fa-camera"></i>
                        </div>
                        <div>
                            <h4 class="edit-header-title">Upload Profile Photo</h4>
                            <p class="edit-header-subtitle">Update learner's profile photo (optional).</p>
                        </div>
                    </div>
                    <span class="edit-header-badge">JPG, PNG, WEBP (Max 5 MB)</span>
                </div>
                <div class="edit-card-body">
                    <div class="upload-photo-container">
                        <div class="upload-avatar-wrapper" id="avatarUploadTrigger" title="Click to upload/change profile photo">
                            <div class="upload-avatar-circle" id="avatarCircle">
                                @if($customer->profile_picture)
                                    <i class="fa-solid fa-user avatar-icon-placeholder" id="avatarDefaultIcon" style="display:none;"></i>
                                    <img id="avatarCroppedPreview" src="{{ asset($customer->profile_picture) }}" alt="Profile Photo">
                                @else
                                    <i class="fa-solid fa-user avatar-icon-placeholder" id="avatarDefaultIcon"></i>
                                    <img id="avatarCroppedPreview" src="" alt="Profile Photo" style="display:none;">
                                @endif
                                <div class="avatar-hover-overlay">
                                    <i class="fa-solid fa-camera"></i>
                                    <span>{{ $customer->profile_picture ? 'Change' : 'Upload' }}</span>
                                </div>
                            </div>
                            <div class="avatar-camera-badge">
                                <i class="fa-solid fa-camera"></i>
                            </div>
                        </div>

                        <div>
                            <div class="upload-avatar-title" id="avatarStatusTitle">
                                {{ $customer->profile_picture ? 'Photo uploaded (Click to change)' : 'Click avatar to upload photo' }}
                            </div>
                            <p class="upload-avatar-subtext" id="avatarSubtext">
                                {{ $customer->profile_picture ? 'Click on the avatar circle to select a new photo.' : 'Upload a clear photo for identification (optional).' }}
                            </p>
                            @if($customer->profile_picture)
                                <a href="{{ asset($customer->profile_picture) }}" class="avatar-view-existing view-image" id="viewExistingAvatar">
                                    <i class="fa-regular fa-eye"></i> View Current Photo
                                </a>
                            @endif
                        </div>

                        {{-- Hidden File Input --}}
                        <input
                            type="file"
                            class="d-none image-cropper @error('profile_picture_image') is-invalid @enderror"
                            name="profile_picture_image"
                            id="profile_picture_image"
                            autocomplete="off"
                            accept=".jpeg, .jpg, .png, .webp" />
                        <img class="preview-img d-none" style="display:none !important; visibility:hidden !important; position:absolute !important;" alt="Preview">
                    </div>

                    @error('profile_picture_image')
                        <div class="text-danger small mt-1 text-center">{{ $message }}</div>
                    @enderror
                </div>
            </div>
            @endif

            {{-- 2. BASIC INFORMATION CARD --}}
            <div class="edit-card">
                <div class="edit-card-header header-blue">
                    <div class="edit-header-left">
                        <div class="edit-header-icon">
                            <i class="fa-regular fa-user"></i>
                        </div>
                        <div>
                            <h4 class="edit-header-title">Basic Information</h4>
                            <p class="edit-header-subtitle">Learner's primary contact and personal details.</p>
                        </div>
                    </div>
                </div>
                <div class="edit-card-body">
                    <div class="row g-3">
                        {{-- Seat Owner Name --}}
                        <div class="col-md-6 form-group">
                            <label for="name" class="form-label">Seat Owner Name <span class="required-star">*</span></label>
                            <input
                                type="text"
                                class="form-control @error('name') is-invalid @enderror"
                                placeholder="Full Name"
                                name="name"
                                id="name"
                                value="{{ old('name', $customer->name) }}"
                                required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- DOB with Calendar Icon --}}
                        <div class="col-md-6 form-group">
                            <label for="dob" class="form-label">DOB <span class="required-star">*</span></label>
                            <div class="date-picker-input-wrap position-relative">
                                <input
                                    type="date"
                                    class="form-control @error('dob') is-invalid @enderror"
                                    placeholder="DOB"
                                    name="dob"
                                    id="dob"
                                    value="{{ old('dob', $customer->dob) }}"
                                    required>
                                <i class="fa-regular fa-calendar-days date-input-calendar-icon"></i>
                            </div>
                            @error('dob')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Mobile Number --}}
                        <div class="col-md-6 form-group">
                            <label for="mobile" class="form-label">Mobile Number <span class="required-star">*</span></label>
                            <input
                                type="text"
                                class="form-control @error('mobile') is-invalid @enderror digit-only"
                                maxlength="10"
                                minlength="10"
                                placeholder="Mobile Number"
                                name="mobile"
                                id="mobile"
                                value="{{ old('mobile', $customer->mobile) }}"
                                required>
                            @error('mobile')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Email Id --}}
                        <div class="col-md-6 form-group">
                            <label for="email" class="form-label">Email Id</label>
                            <input
                                type="email"
                                class="form-control @error('email') is-invalid @enderror"
                                placeholder="Email Id"
                                name="email"
                                id="email"
                                value="{{ old('email', $customer->email) }}">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- 3. REMINDER & EXPIRY SETTINGS CARD --}}
            <div class="edit-card">
                <div class="edit-card-header header-green">
                    <div class="edit-header-left">
                        <div class="edit-header-icon">
                            <i class="fa-solid fa-bell"></i>
                        </div>
                        <div>
                            <h4 class="edit-header-title">Reminder &amp; Expiry Settings</h4>
                            <p class="edit-header-subtitle">Configure message reminder channels and seat expiry options.</p>
                        </div>
                    </div>
                </div>
                <div class="edit-card-body">
                    <div class="row g-3">
                        {{-- Send Reminders Via --}}
                        <div class="col-md-6 form-group">
                            <label for="sended_message_type" class="form-label">Send Reminders Via (Optional)</label>
                            <select id="sended_message_type" class="form-select" name="sended_message_type">
                                <option value="">Select Type</option>
                                @if($hasFreeWaba ?? false)
                                <option value="whatsapp" {{ old('sended_message_type', $customer->sended_message_type) == 'whatsapp' ? 'selected' : '' }}>WhatsApp Message Only</option>
                                @endif
                                @if($hasFreeText ?? false)
                                <option value="text" {{ old('sended_message_type', $customer->sended_message_type) == 'text' ? 'selected' : '' }}>Text Message Only</option>
                                @endif
                                @if(($hasFreeWaba ?? false) && ($hasFreeText ?? false))
                                <option value="both" {{ old('sended_message_type', $customer->sended_message_type) == 'both' ? 'selected' : '' }}>Both (WhatsApp &amp; Text Message)</option>
                                @endif
                                <option value="no" {{ old('sended_message_type', $customer->sended_message_type) == 'no' ? 'selected' : '' }}>No</option>
                            </select>
                        </div>

                        {{-- No Expiry Seat --}}
                        <div class="col-md-6 form-group">
                            <label for="no_expiry" class="form-label">No Expiry Seat (Optional)</label>
                            <select name="no_expiry" id="no_expiry" class="form-select @error('no_expiry') is-invalid @enderror">
                                <option value="">Select Expiry Mode</option>
                                <option value="1" {{ old('no_expiry', $customer->no_expiry) == 1 ? 'selected' : '' }}>Yes, Make it non expired seat.</option>
                                <option value="0" {{ old('no_expiry', $customer->no_expiry) == 0 ? 'selected' : '' }}>No</option>
                            </select>
                            @error('no_expiry')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- 4. ADDITIONAL INFORMATION CARD (ALWAYS VISIBLE - NO COLLAPSE) --}}
            <div class="edit-card">
                <div class="edit-card-header header-amber">
                    <div class="edit-header-left">
                        <div class="edit-header-icon">
                            <i class="fa-regular fa-id-card"></i>
                        </div>
                        <div>
                            <h4 class="edit-header-title">Additional Information</h4>
                            <p class="edit-header-subtitle">Optional details such as alternate contact, ID proof, and address.</p>
                        </div>
                    </div>
                    <span class="edit-header-badge">Optional</span>
                </div>
                <div class="edit-card-body">

                    <div class="row g-3">
                        {{-- Alternate Mobile No. --}}
                        @if(!in_array('30', toggleHideField()))
                        <div class="col-md-6 form-group">
                            <label for="alternate_mobile" class="form-label">Alternate Mobile No.</label>
                            <input
                                type="text"
                                class="form-control @error('alternate_mobile') is-invalid @enderror digit-only"
                                name="alternate_mobile"
                                id="alternate_mobile"
                                maxlength="10"
                                minlength="10"
                                placeholder="Enter Alternate Mobile No."
                                value="{{ old('alternate_mobile', $customer->alternate_mobile) }}">
                            @error('alternate_mobile')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        @endif

                        {{-- Father Name --}}
                        @if(!in_array('29', toggleHideField()))
                        <div class="col-md-6 form-group">
                            <label for="father_name" class="form-label">Father Name</label>
                            <input
                                type="text"
                                class="form-control @error('father_name') is-invalid @enderror char-only"
                                name="father_name"
                                id="father_name"
                                placeholder="Enter Father name"
                                value="{{ old('father_name', $customer->father_name) }}">
                            @error('father_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        @endif

                        {{-- Prepare For --}}
                        @if(!in_array('4', toggleHideField()))
                        <div class="col-12 form-group">
                            <label for="exam_id" class="form-label">Prepare For</label>
                            <select name="exam_id" id="exam_id" class="form-select @error('exam_id') is-invalid @enderror">
                                <option value="">Learner is Prepare For Exam</option>
                                @foreach($exams as $key => $value)
                                <option value="{{ $value->id }}" {{ old('exam_id', $customer->exam_id) == $value->id ? 'selected' : '' }}>{{ $value->name }}</option>
                                @endforeach
                            </select>
                            @error('exam_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        @endif

                        {{-- ID Proof Details (Row 1: Dropdown & No, Row 2: Bordered Drag & Drop) --}}
                        @if(!in_array('5', toggleHideField()))
                        <div class="col-md-6 form-group">
                            <label for="edit_id_proof_name" class="form-label">ID Proof Name (Optional)</label>
                            <select class="form-select @error('id_proof_name') is-invalid @enderror" name="id_proof_name" id="edit_id_proof_name">
                                <option value="">Select Id Proof</option>
                                <option value="1" {{ old('id_proof_name', $customer->id_proof_name) == 1 ? 'selected' : '' }}>Aadhar Card</option>
                                <option value="2" {{ old('id_proof_name', $customer->id_proof_name) == 2 ? 'selected' : '' }}>Driving License</option>
                                <option value="4" {{ old('id_proof_name', $customer->id_proof_name) == 4 ? 'selected' : '' }}>Pan Card</option>
                                <option value="5" {{ old('id_proof_name', $customer->id_proof_name) == 5 ? 'selected' : '' }}>Voter Id</option>
                                <option value="3" {{ old('id_proof_name', $customer->id_proof_name) == 3 ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('id_proof_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 form-group">
                            <label for="edit_id_proof_number" class="form-label">ID Proof No.</label>
                            <input
                                type="text"
                                class="form-control @error('id_proof_number') is-invalid @enderror"
                                name="id_proof_number"
                                id="edit_id_proof_number"
                                placeholder="Enter ID proof no."
                                maxlength="12"
                                value="{{ old('id_proof_number', $customer->id_proof_number) }}">
                            @error('id_proof_number')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Row 2: Bordered Drag and Drop Document Upload --}}
                        <div class="col-12 form-group">
                            <label for="edit_id_proof_file" class="form-label">Upload Scan Copy of Proof (Optional)</label>
                            <div class="doc-dropzone disabled" id="docDropzone" title="Select Id Proof type above to enable upload">
                                {{-- Placeholder state --}}
                                <div class="doc-dropzone-content" id="docDropzoneContent" style="{{ $customer->id_proof_file ? 'display:none;' : '' }}">
                                    <i class="fa-solid fa-cloud-arrow-up doc-dropzone-icon"></i>
                                    <div class="doc-dropzone-text">Drag &amp; drop file here or <span class="browse-link">browse</span></div>
                                    <div class="doc-dropzone-hint" id="docDropzoneHint">Select ID proof type above to enable upload</div>
                                </div>

                                {{-- Preview state --}}
                                <div class="doc-dropzone-preview" id="docDropzonePreview" style="{{ $customer->id_proof_file ? 'display:flex;' : 'display:none;' }}">
                                    <i class="fa-solid fa-file-circle-check text-success fs-3"></i>
                                    <div class="doc-file-info">
                                        <span class="doc-file-name" id="docFileName">
                                            {{ $customer->id_proof_file ? basename($customer->id_proof_file) : '' }}
                                        </span>
                                        <div class="doc-file-actions-row">
                                            @if($customer->id_proof_file)
                                                <a href="{{ asset($customer->id_proof_file) }}" class="doc-file-action text-primary view-image" id="viewExistingDoc">
                                                    <i class="fa-regular fa-eye"></i> View File
                                                </a>
                                            @endif
                                            <span class="doc-file-action text-danger" id="removeDocFile" title="Change or remove file">
                                                <i class="fa-solid fa-arrow-rotate-left"></i> Change File
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <input
                                type="file"
                                class="d-none id_proof_file image-cropper @error('id_proof') is-invalid @enderror"
                                name="id_proof"
                                id="edit_id_proof_file"
                                autocomplete="off"
                                accept=".jpeg, .jpg, .png, .webp, .pdf">
                            <img class="preview-img one d-none" style="display:none !important; visibility:hidden !important; position:absolute !important;" alt="Doc Preview">
                            @error('id_proof')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                            <span class="form-hint text-danger mt-1">* Upload front side of document (JPG, PNG, WEBP, PDF).</span>
                        </div>
                        @endif

                        {{-- Address --}}
                        @if(!in_array('32', toggleHideField()))
                        <div class="col-12 form-group">
                            <label for="address" class="form-label">Address</label>
                            <textarea
                                class="form-control @error('address') is-invalid @enderror"
                                name="address"
                                id="address"
                                rows="3"
                                placeholder="Enter address">{{ old('address', $customer->address) }}</textarea>
                            @error('address')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        @endif

                        {{-- Remark --}}
                        @if(!in_array('31', toggleHideField()))
                        <div class="col-12 form-group">
                            <label for="remark" class="form-label">Remark</label>
                            <textarea
                                class="form-control @error('remark') is-invalid @enderror"
                                name="remark"
                                id="remark"
                                rows="3"
                                placeholder="Enter Remark">{{ old('remark', $customer->remark) }}</textarea>
                            @error('remark')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- 5. ACTION BUTTON BAR (CANCEL BUTTON REMOVED) --}}
            <div class="form-action-bar">
                <button type="submit" class="btn-submit-operation" id="editLearnerSubmit">
                    <i class="fa-solid fa-floppy-disk"></i> Update Seat Info
                </button>
            </div>

        </form>
    </div>
</div>

<script>
$(document).ready(function () {

    // 1. Profile Picture Avatar Upload Trigger & Preview
    $('#avatarUploadTrigger').on('click', function (e) {
        e.preventDefault();
        $('#profile_picture_image').trigger('click');
    });

    function setAvatarPreview(src, title) {
        if (src) {
            $('#avatarCroppedPreview').attr('src', src).show();
            $('#avatarDefaultIcon').hide();
            $('#avatarStatusTitle').text(title || 'Photo selected');
            $('#avatarSubtext').text('Click on avatar circle to change.');
            if ($('#topSeatAvatarImg').length) {
                $('#topSeatAvatarImg').attr('src', src).removeClass('avatar-seat-chair').addClass('avatar-user-photo');
            }
        }
    }

    // Live update student name in top seat header when name input changes
    $('#name').on('input', function () {
        const newName = $(this).val();
        $('.seat-title').text(newName ? newName.toUpperCase() : 'LEARNER');
    });

    // Direct change event on profile_picture_image
    $('#profile_picture_image').on('change', function () {
        const file = this.files && this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                setAvatarPreview(e.target.result, 'New photo selected');
            };
            reader.readAsDataURL(file);
        }
    });

    // Observer for Cropper image changes if cropper modifies sibling
    const profileSibling = document.querySelector('#profile_picture_image ~ .preview-img');
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
            profileSibling.style.setProperty('display', 'none', 'important');
        });
        profileObserver.observe(profileSibling, { attributes: true, attributeFilter: ['src', 'style'] });
    }

    // Fast update and hide on crop button click
    $(document).on('click', '.cropbtn', function () {
        setTimeout(function () {
            const pImg = document.querySelector('#profile_picture_image ~ .preview-img');
            if (pImg) {
                pImg.style.setProperty('display', 'none', 'important');
                if (pImg.src && pImg.src !== window.location.href) {
                    setAvatarPreview(pImg.src, 'Photo uploaded');
                }
            }
            const dImg = document.querySelector('.preview-img.one');
            if (dImg) {
                dImg.style.setProperty('display', 'none', 'important');
                if (dImg.src && dImg.src !== window.location.href) {
                    $('#docFileName').text('Document Attached');
                    $('#docDropzoneContent').hide();
                    $('#docDropzonePreview').css('display', 'flex');
                }
            }
        }, 100);
    });

    // 2. ID Proof Document Drag & Drop and Enable/Disable Logic
    function updateIdProofUploadState() {
        const proofSelected = $('#edit_id_proof_name').val();
        const $dropzone = $('#docDropzone');
        const $fileInput = $('#edit_id_proof_file');
        const $hint = $('#docDropzoneHint');

        if (proofSelected && proofSelected !== '') {
            $dropzone.removeClass('disabled').attr('title', 'Click or drag file to upload');
            $fileInput.prop('disabled', false);
            $hint.text('Supports JPG, PNG, WEBP, PDF (Max 5 MB)');
        } else {
            $dropzone.addClass('disabled').attr('title', 'Select Id Proof type above to enable upload');
            $fileInput.prop('disabled', true);
            $hint.text('Select ID proof type above to enable upload');
            
            @if(!$customer->id_proof_file)
                $('#docDropzoneContent').show();
                $('#docDropzonePreview').hide();
                $fileInput.val('');
            @endif
        }
    }

    updateIdProofUploadState();

    $(document).on('change', '#edit_id_proof_name', function () {
        updateIdProofUploadState();
    });

    $('#docDropzone').on('click', function (e) {
        if ($(e.target).closest('#removeDocFile').length || $(e.target).closest('#viewExistingDoc').length) {
            return;
        }

        if ($(this).hasClass('disabled')) {
            $('#edit_id_proof_name').focus().addClass('is-invalid');
            setTimeout(function () {
                $('#edit_id_proof_name').removeClass('is-invalid');
            }, 1500);
            return;
        }

        $('#edit_id_proof_file').trigger('click');
    });

    $('#removeDocFile').on('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $('#edit_id_proof_file').val('');
        $('#docDropzoneContent').show();
        $('#docDropzonePreview').hide();
        $('.preview-img.one').hide().removeAttr('src');
    });

    $(document).on('change', '#edit_id_proof_file', function () {
        const file = this.files && this.files[0];
        if (file) {
            $('#docFileName').text(file.name);
            $('#docDropzoneContent').hide();
            $('#docDropzonePreview').css('display', 'flex');
        }
    });

    const docDropzoneEl = document.getElementById('docDropzone');
    if (docDropzoneEl) {
        ['dragenter', 'dragover'].forEach(eventName => {
            docDropzoneEl.addEventListener(eventName, function (e) {
                e.preventDefault();
                e.stopPropagation();
                if (!$('#docDropzone').hasClass('disabled')) {
                    $('#docDropzone').addClass('dragover');
                }
            }, false);
        });

        ['dragleave', 'dragend'].forEach(eventName => {
            docDropzoneEl.addEventListener(eventName, function (e) {
                e.preventDefault();
                e.stopPropagation();
                $('#docDropzone').removeClass('dragover');
            }, false);
        });

        docDropzoneEl.addEventListener('drop', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $('#docDropzone').removeClass('dragover');

            if ($('#docDropzone').hasClass('disabled')) {
                $('#edit_id_proof_name').focus().addClass('is-invalid');
                setTimeout(function () {
                    $('#edit_id_proof_name').removeClass('is-invalid');
                }, 1500);
                return;
            }

            const dt = e.dataTransfer;
            const files = dt.files;
            if (files && files.length > 0) {
                const fileInput = document.getElementById('edit_id_proof_file');
                if (fileInput) {
                    const transfer = new DataTransfer();
                    transfer.items.add(files[0]);
                    fileInput.files = transfer.files;
                    $(fileInput).trigger('change');
                }
            }
        }, false);
    }

    // 3. Modal Image Viewer for view-image links
    $(document).on("click", 'a.view-image', function (e) {
        const imageUrl = $(this).attr("href");

        if (imageUrl.match(/\.pdf$/i)) {
            return true;
        }

        if (imageUrl.match(/\.(jpg|jpeg|png|webp)$/i)) {
            e.preventDefault();
            $("#modalImage").attr("src", imageUrl);
            $("#imageViewModal").fadeIn(200);
        }
    });

    $(".close-modal").on("click", function () {
        $("#imageViewModal").fadeOut(200);
        $("#modalImage").attr("src", "");
    });

    $("#imageViewModal").on("click", function (e) {
        if ($(e.target).is(this)) {
            $(this).fadeOut(200);
            $("#modalImage").attr("src", "");
        }
    });

    // Mobile info details collapse toggle
    const btnToggleDetails = document.getElementById('btnToggleDetails');
    const infoGrid = document.getElementById('glassInfoGrid');

    if (btnToggleDetails && infoGrid) {
        btnToggleDetails.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const isCurrentlyCollapsed = infoGrid.classList.contains('is-collapsed');

            if (isCurrentlyCollapsed) {
                infoGrid.classList.remove('is-collapsed');
                btnToggleDetails.classList.remove('is-collapsed');
                btnToggleDetails.setAttribute('aria-expanded', 'true');
            } else {
                infoGrid.classList.add('is-collapsed');
                btnToggleDetails.classList.add('is-collapsed');
                btnToggleDetails.setAttribute('aria-expanded', 'false');
            }
        });
    }

});
</script>

@endsection
