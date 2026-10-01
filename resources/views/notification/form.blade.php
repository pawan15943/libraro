@extends('layouts.admin')

@section('title', $notificat ? 'Edit Notification' : 'Create Notification')

@section('content')
<link rel="stylesheet" href="{{ asset('public/css/admin-notification.css') }}?v={{ time() }}">

<div class="custom-notification-module">
    @if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-circle-exclamation me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif
    @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-circle-check me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @php
        $data = $notificat ? json_decode($notificat->data, true) : [];
        $isEdit = !empty($notificat);
        $actionRoute = $isEdit ? route('notifications.update') : route('notifications.send');
    @endphp

    <div class="row g-3 g-lg-4">
        {{-- Main Form Card --}}
        <div class="col-12 col-lg-8">
            <div class="form-card">
                <form action="{{ $actionRoute }}" method="POST" id="notificationForm">
                    @csrf
                    @if($isEdit)
                        @method('PUT')
                        <input type="hidden" name="batch_id" value="{{ $notificat->batch_id }}">
                    @endif

                    <div class="row g-3">
                        {{-- Target Audience / Guard --}}
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="guard">
                                Target Audience <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('guard') is-invalid @enderror" id="guard" name="guard" required>
                                <option value="">-- Choose Target --</option>
                                <option value="library" {{ old('guard', $notificat->guard ?? '') == 'library' ? 'selected' : '' }}>
                                    Library Owners (Library Panel)
                                </option>
                                <option value="web" {{ old('guard', $notificat->guard ?? '') == 'web' ? 'selected' : '' }}>
                                    Website & Admin
                                </option>
                                <option value="learner" {{ old('guard', $notificat->guard ?? '') == 'learner' ? 'selected' : '' }}>
                                    Learners
                                </option>
                            </select>
                            @error('guard')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted d-block mt-1">Select who will receive and see this notification.</small>
                        </div>

                        {{-- Notification Type --}}
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="notification_type">
                                Notification Category <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('notification_type') is-invalid @enderror" id="notification_type" name="notification_type" required>
                                <option value="">-- Select Category --</option>
                                <option value="important" {{ old('notification_type', $data['notification_type'] ?? '') == 'important' ? 'selected' : '' }}>Important Announcement</option>
                                <option value="offers" {{ old('notification_type', $data['notification_type'] ?? '') == 'offers' ? 'selected' : '' }}>Offers & Discounts</option>
                                <option value="maintenance" {{ old('notification_type', $data['notification_type'] ?? '') == 'maintenance' ? 'selected' : '' }}>System Maintenance</option>
                                <option value="wishes" {{ old('notification_type', $data['notification_type'] ?? '') == 'wishes' ? 'selected' : '' }}>Greetings & Wishes</option>
                            </select>
                            @error('notification_type')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Notification Title --}}
                        <div class="col-12">
                            <label class="form-label" for="title">
                                Notification Title <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title"
                                   placeholder="e.g. Scheduled System Upgrade on Sunday"
                                   value="{{ old('title', $data['title'] ?? '') }}" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Description --}}
                        <div class="col-12">
                            <label class="form-label" for="description">
                                Message Description <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                                      rows="4" placeholder="Enter clear, concise notification details..." required>{{ old('description', $data['description'] ?? '') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- External Link / CTA --}}
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="link">Action URL / Link (Optional)</label>
                            <input type="url" class="form-control @error('link') is-invalid @enderror" id="link" name="link"
                                   placeholder="https://libraro.com/..."
                                   value="{{ old('link', $data['link'] ?? '') }}">
                            @error('link')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Image URL --}}
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="image">Banner / Icon URL (Optional)</label>
                            <input type="url" class="form-control @error('image') is-invalid @enderror" id="image" name="image"
                                   placeholder="https://..."
                                   value="{{ old('image', $data['image'] ?? '') }}">
                            @error('image')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Start Date --}}
                        <div class="col-12 col-sm-6 col-md-4">
                            <label class="form-label" for="start_date">
                                Display Start Date <span class="text-danger">*</span>
                            </label>
                            @php
                                $formStartDate = old('start_date', ($notificat && $notificat->end_date < date('Y-m-d')) ? date('Y-m-d') : ($notificat->start_date ?? date('Y-m-d')));
                                $formEndDate = old('end_date', ($notificat && $notificat->end_date < date('Y-m-d')) ? date('Y-m-d', strtotime('+7 days')) : ($notificat->end_date ?? date('Y-m-d', strtotime('+7 days'))));
                            @endphp
                            <input type="date" class="form-control @error('start_date') is-invalid @enderror" id="start_date" name="start_date"
                                   value="{{ $formStartDate }}" required>
                            @error('start_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Expiry Date --}}
                        <div class="col-12 col-sm-6 col-md-4">
                            <label class="form-label" for="end_date">
                                Expiry Date <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control @error('end_date') is-invalid @enderror" id="end_date" name="end_date"
                                   value="{{ $formEndDate }}" required>
                            @error('end_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Status Option --}}
                        <div class="col-12 col-sm-12 col-md-4">
                            <label class="form-label" for="status">
                                Status <span class="text-danger">*</span>
                            </label>
                            <select class="form-select @error('status') is-invalid @enderror" id="status" name="status" required>
                                <option value="1" {{ (string)old('status', $notificat->status ?? 1) === '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ (string)old('status', $notificat->status ?? 1) === '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Submit Buttons --}}
                        <div class="col-12 mt-3 mt-md-4 form-action-btns">
                            <button type="submit" class="btn btn-primary button">
                                <i class="fa-solid fa-paper-plane me-1"></i>
                                {{ $isEdit ? 'Update Notification' : 'Send Notification' }}
                            </button>
                            <a href="{{ route('admin.notifications.index') }}" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Preview Column --}}
        <div class="col-12 col-lg-4">
            <div class="card-box preview-sticky-card">
                <h6 class="fw-bold mb-3" style="color: #18225f;">
                    <i class="fa-solid fa-eye me-1"></i> Live Banner Preview
                </h6>
                <p class="small text-muted mb-3">
                    This is how your announcement will appear to library owners and visitors right below the header:
                </p>

                <div id="previewBox" style="background: #18225f; color: #ffffff; border-radius: 10px; padding: 1rem; box-shadow: 0 4px 15px rgba(24, 34, 95, 0.15); transition: all 0.3s ease;">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span id="previewTypeBadge" class="badge" style="background: #34939F; font-size: 0.72rem; padding: 0.3rem 0.6rem;">
                            Important
                        </span>
                        <span id="previewStatusBadge" class="badge {{ (isset($notificat->status) && (string)$notificat->status === '0') ? 'bg-danger' : 'bg-success' }}" style="font-size: 0.7rem; padding: 0.25rem 0.55rem; font-weight: 600;">
                            {{ (isset($notificat->status) && (string)$notificat->status === '0') ? 'Inactive' : 'Active' }}
                        </span>
                    </div>
                    <div id="previewTitle" class="fw-bold mb-1" style="font-size: 0.95rem;">
                        {{ $data['title'] ?? 'Notification Title' }}
                    </div>
                    <div id="previewDesc" class="small text-white-50 mb-2" style="font-size: 0.8rem; line-height: 1.4;">
                        {{ $data['description'] ?? 'Your notification description will appear here in real-time as you type.' }}
                    </div>
                    <div id="previewLinkWrap" style="{{ !empty($data['link']) ? '' : 'display:none;' }}">
                        <span class="btn btn-sm btn-light py-1 px-3 fw-bold text-primary" style="font-size: 0.75rem; border-radius: 20px;">
                            Learn More <i class="fa-solid fa-arrow-right ms-1"></i>
                        </span>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top">
                    <h6 class="small fw-bold text-dark mb-2"><i class="fa-solid fa-circle-info text-primary me-1"></i> Where will it be shown?</h6>
                    <ul class="small text-muted ps-3 mb-0">
                        <li class="mb-1"><strong>Library Owners:</strong> Shown in Library Dashboard just below the top header, in the bell dropdown alert, and in the notification center.</li>
                        <li><strong>Website / Admin:</strong> Shown on the public website home and inner pages directly under the navigation bar.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const titleInput = document.getElementById('title');
        const descInput = document.getElementById('description');
        const linkInput = document.getElementById('link');
        const typeSelect = document.getElementById('notification_type');
        const statusSelect = document.getElementById('status');

        const previewTitle = document.getElementById('previewTitle');
        const previewDesc = document.getElementById('previewDesc');
        const previewTypeBadge = document.getElementById('previewTypeBadge');
        const previewStatusBadge = document.getElementById('previewStatusBadge');
        const previewLinkWrap = document.getElementById('previewLinkWrap');
        const previewBox = document.getElementById('previewBox');

        function updatePreview() {
            if (titleInput && previewTitle) {
                previewTitle.textContent = titleInput.value.trim() || 'Notification Title';
            }
            if (descInput && previewDesc) {
                previewDesc.textContent = descInput.value.trim() || 'Your notification description will appear here in real-time as you type.';
            }
            if (linkInput && previewLinkWrap) {
                previewLinkWrap.style.display = linkInput.value.trim() ? 'block' : 'none';
            }
            if (typeSelect && previewTypeBadge) {
                const val = typeSelect.value;
                previewTypeBadge.textContent = val ? val.charAt(0).toUpperCase() + val.slice(1) : 'Important';
                if (val === 'offers') {
                    previewTypeBadge.style.background = '#34939F';
                } else if (val === 'wishes') {
                    previewTypeBadge.style.background = '#16a34a';
                } else if (val === 'maintenance') {
                    previewTypeBadge.style.background = '#d97706';
                } else {
                    previewTypeBadge.style.background = '#dc3545';
                }
            }
            if (statusSelect && previewStatusBadge) {
                if (statusSelect.value === '0') {
                    previewStatusBadge.textContent = 'Inactive';
                    previewStatusBadge.className = 'badge bg-danger';
                    if (previewBox) previewBox.style.opacity = '0.65';
                } else {
                    previewStatusBadge.textContent = 'Active';
                    previewStatusBadge.className = 'badge bg-success';
                    if (previewBox) previewBox.style.opacity = '1';
                }
            }
        }

        if (titleInput) titleInput.addEventListener('input', updatePreview);
        if (descInput) descInput.addEventListener('input', updatePreview);
        if (linkInput) linkInput.addEventListener('input', updatePreview);
        if (typeSelect) typeSelect.addEventListener('change', updatePreview);
        if (statusSelect) statusSelect.addEventListener('change', updatePreview);
        updatePreview();
    });
</script>
@endsection
