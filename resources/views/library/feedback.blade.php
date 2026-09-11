@extends('layouts.library')

@section('title', 'Library Feedback')

@section('content')

<link rel="stylesheet" href="{{ asset('public/css/library-feedback.css') }}?v={{ time() }}">

<div class="library-feedback-module">
    <div class="feedback-compact-shell">
        @if($is_feedback)
            <!-- Already Submitted State Alert -->
            <div class="feedback-submitted-alert">
                <div class="d-flex align-items-center gap-3">
                    <i class="fa-solid fa-circle-check text-success fs-3"></i>
                    <div>
                        <h6 class="mb-1 fw-bold text-success">Feedback Already Submitted</h6>
                        <p class="mb-0 text-muted font-12">Thank you! Your feedback for this library account has been recorded.</p>
                    </div>
                </div>
                <a href="{{ route('library.home') }}" class="btn-back-link">
                    <i class="fa-solid fa-arrow-left"></i> Dashboard
                </a>
            </div>
        @else   
            <!-- Compact Feedback Form (Label above Field format) -->
            <form action="{{ route('library.feedback.store') }}" 
                  class="validateForm feedback-form" 
                  method="POST" 
                  enctype="multipart/form-data" 
                  id="libraryFeedbackForm">
                @csrf

                <!-- Field 1: Feature -->
                <div class="feedback-form-group">
                    <label class="form-group-label" for="feedbackFeatureSelect">
                        Feature <span class="req-star">*</span>
                    </label>
                    <select name="feedback_feature_id" id="feedbackFeatureSelect" class="form-select-custom @error('feedback_feature_id') is-invalid @enderror" required>
                        <option value="">Choose Feature</option>
                        @foreach($feedback_features as $feedback_feature)
                        <option value="{{ $feedback_feature->id }}" {{ old('feedback_feature_id') == $feedback_feature->id ? 'selected' : '' }}>
                            {{ $feedback_feature->name }}
                        </option>
                        @endforeach
                    </select>
                    @error('feedback_feature_id')
                        <span class="field-error-text">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Field 2: Rating -->
                <div class="feedback-form-group">
                    <label class="form-group-label">
                        Rating <span class="req-star">*</span>
                    </label>
                    <input type="hidden" name="rating" id="ratingValueInput" value="{{ old('rating', '5') }}" required>
                    <div class="feedback-stars-box" id="starRatingBox">
                        <button type="button" class="star-btn star-active" data-val="1" title="1 Star"><i class="fa-solid fa-star"></i></button>
                        <button type="button" class="star-btn star-active" data-val="2" title="2 Stars"><i class="fa-solid fa-star"></i></button>
                        <button type="button" class="star-btn star-active" data-val="3" title="3 Stars"><i class="fa-solid fa-star"></i></button>
                        <button type="button" class="star-btn star-active" data-val="4" title="4 Stars"><i class="fa-solid fa-star"></i></button>
                        <button type="button" class="star-btn star-active" data-val="5" title="5 Stars"><i class="fa-solid fa-star"></i></button>
                        <span class="rating-text-hint" id="ratingTextHint">5 / 5 - Excellent</span>
                    </div>
                    @error('rating')
                        <span class="field-error-text">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Field 3: Description -->
                <div class="feedback-form-group">
                    <label class="form-group-label" for="feedbackDescriptionText">
                        Feedback Description <span class="req-star">*</span>
                    </label>
                    <textarea name="description" 
                              id="feedbackDescriptionText"
                              rows="3"
                              class="form-control-custom @error('description') is-invalid @enderror" 
                              placeholder="Share your experience, thoughts, or suggestions..." 
                              required>{{ old('description') }}</textarea>
                    @error('description')
                        <span class="field-error-text">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Field 4: Recommend -->
                <div class="feedback-form-group">
                    <label class="form-group-label">
                        Would You Recommend Us? <span class="req-star">*</span>
                    </label>
                    <div class="recommend-toggle-group">
                        <label class="toggle-pill-label">
                            <input type="radio" name="recommend" value="Yes" {{ old('recommend', 'Yes') == 'Yes' ? 'checked' : '' }}>
                            <span class="toggle-pill-btn"><i class="fa-solid fa-thumbs-up text-success"></i> Yes</span>
                        </label>
                        <label class="toggle-pill-label">
                            <input type="radio" name="recommend" value="No" {{ old('recommend') == 'No' ? 'checked' : '' }}>
                            <span class="toggle-pill-btn"><i class="fa-solid fa-thumbs-down text-danger"></i> No</span>
                        </label>
                    </div>
                    @error('recommend')
                        <span class="field-error-text">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Field 5: Drag & Drop Screenshot / Attachment -->
                <div class="feedback-form-group">
                    <label class="form-group-label">
                        Screenshot or Attachment <span class="text-muted fw-normal font-11">(Optional)</span>
                    </label>
                    <div class="feedback-dropzone" id="feedbackDropzone">
                        <input type="file" 
                               name="attachment" 
                               id="attachmentInput" 
                               accept=".jpg,.jpeg,.png,.pdf" 
                               class="no-validate d-none">
                        
                        <!-- Idle State -->
                        <div class="dropzone-idle" id="dropzoneIdle">
                            <div class="dropzone-icon">
                                <i class="fa-solid fa-cloud-arrow-up"></i>
                            </div>
                            <div class="dropzone-text">
                                <span class="dropzone-primary-text">Drag &amp; drop screenshot here, or <span class="dropzone-browse-text">Browse</span></span>
                                <span class="dropzone-hint-text">Supports JPG, PNG, PDF (Max: 2MB)</span>
                            </div>
                        </div>

                        <!-- Selected Preview State -->
                        <div class="dropzone-preview" id="dropzonePreview" style="display: none;">
                            <div class="dropzone-preview-left">
                                <div class="dropzone-file-icon-box" id="dropzoneFileIconBox">
                                    <i class="fa-solid fa-file-image"></i>
                                </div>
                                <div class="dropzone-file-info">
                                    <span class="dropzone-file-name" id="dropzoneFileName">filename.png</span>
                                    <span class="dropzone-file-size" id="dropzoneFileSize">0 KB</span>
                                </div>
                            </div>
                            <button type="button" class="dropzone-remove-btn" id="removeAttachmentBtn" title="Remove attachment">
                                <i class="fa-solid fa-trash-can"></i>
                            </button>
                        </div>
                    </div>
                    @error('attachment')
                        <span class="field-error-text">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Field 6: Submit Button -->
                <div class="feedback-form-group pt-1 mb-0">
                    <button type="submit" class="btn-submit-feedback">
                        <i class="fa-solid fa-paper-plane me-2"></i> Submit Feedback
                    </button>
                </div>
            </form>

            @if($errors->any())
            <div class="mt-3">
                @foreach ($errors->all() as $error)
                    <span class="text-danger font-12 d-block">{{ $error }}</span>
                @endforeach
            </div>
            @endif
        @endif
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Rating Text Dictionary
        const ratingTexts = {
            1: '1 / 5 - Poor',
            2: '2 / 5 - Fair',
            3: '3 / 5 - Good',
            4: '4 / 5 - Very Good',
            5: '5 / 5 - Excellent'
        };

        // Star Rating Logic
        const ratingInput = document.getElementById('ratingValueInput');
        const stars = document.querySelectorAll('.star-btn');
        const hint = document.getElementById('ratingTextHint');

        function setStars(val) {
            val = parseInt(val) || 5;
            if (ratingInput) ratingInput.value = val;
            if (hint) hint.textContent = ratingTexts[val] || (val + ' / 5');
            stars.forEach(s => {
                const sVal = parseInt(s.getAttribute('data-val'));
                if (sVal <= val) {
                    s.classList.add('star-active');
                } else {
                    s.classList.remove('star-active');
                }
            });
        }

        stars.forEach(s => {
            s.addEventListener('click', function(e) {
                e.preventDefault();
                setStars(this.getAttribute('data-val'));
            });
        });

        if (ratingInput && ratingInput.value) {
            setStars(ratingInput.value);
        }

        // Drag and Drop Screenshot / Attachment Logic
        const dropzone = document.getElementById('feedbackDropzone');
        const attachInput = document.getElementById('attachmentInput');
        const dropzoneIdle = document.getElementById('dropzoneIdle');
        const dropzonePreview = document.getElementById('dropzonePreview');
        const dropzoneFileName = document.getElementById('dropzoneFileName');
        const dropzoneFileSize = document.getElementById('dropzoneFileSize');
        const dropzoneFileIconBox = document.getElementById('dropzoneFileIconBox');
        const removeBtn = document.getElementById('removeAttachmentBtn');

        function formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            else if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
            else return (bytes / 1048576).toFixed(2) + ' MB';
        }

        function updateFilePreview(file) {
            if (!file) {
                dropzoneIdle.style.display = 'flex';
                dropzonePreview.style.display = 'none';
                return;
            }
            dropzoneFileName.textContent = file.name;
            dropzoneFileSize.textContent = formatFileSize(file.size);

            // Icon or image preview
            if (file.type && file.type.startsWith('image/')) {
                dropzoneFileIconBox.innerHTML = `<img src="${URL.createObjectURL(file)}" class="dropzone-thumb-preview" alt="Preview">`;
            } else if (file.type && file.type.includes('pdf')) {
                dropzoneFileIconBox.innerHTML = '<i class="fa-solid fa-file-pdf text-danger"></i>';
            } else {
                dropzoneFileIconBox.innerHTML = '<i class="fa-solid fa-file-image text-primary"></i>';
            }

            dropzoneIdle.style.display = 'none';
            dropzonePreview.style.display = 'flex';
        }

        if (dropzone && attachInput) {
            // Click to browse
            dropzone.addEventListener('click', function(e) {
                if (e.target.closest('#removeAttachmentBtn')) return;
                attachInput.click();
            });

            // File input changed
            attachInput.addEventListener('change', function() {
                if (this.files && this.files.length > 0) {
                    updateFilePreview(this.files[0]);
                } else {
                    updateFilePreview(null);
                }
            });

            // Drag and Drop Events
            ['dragenter', 'dragover'].forEach(eventName => {
                dropzone.addEventListener(eventName, function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.add('drag-over');
                }, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropzone.addEventListener(eventName, function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.remove('drag-over');
                }, false);
            });

            dropzone.addEventListener('drop', function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropzone.classList.remove('drag-over');
                const dt = e.dataTransfer;
                if (dt && dt.files && dt.files.length > 0) {
                    try {
                        const newDt = new DataTransfer();
                        newDt.items.add(dt.files[0]);
                        attachInput.files = newDt.files;
                    } catch (err) {
                        try {
                            attachInput.files = dt.files;
                        } catch (e2) {}
                    }
                    updateFilePreview(attachInput.files[0] || dt.files[0]);
                }
            });
        }

        if (removeBtn && attachInput) {
            removeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                attachInput.value = '';
                updateFilePreview(null);
            });
        }
    });
</script>

@include('library.script')
@endsection