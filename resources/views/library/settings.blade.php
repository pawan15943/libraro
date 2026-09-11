@extends('layouts.library')

@section('title', 'Library Settings')

@section('content')

<link rel="stylesheet" href="{{ asset('public/css/library-settings.css') }}?v={{ time() }}">

<div class="library-settings-module">
    <div class="settings-compact-shell">
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-3" role="alert">
                <i class="fa-solid fa-circle-check fs-5"></i>
                <div>{{ session('success') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 mb-3" role="alert">
                <i class="fa-solid fa-circle-exclamation fs-5"></i>
                <div>{{ session('error') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <!-- Compact Settings Form (Label on top, Field below) -->
        <form action="{{ route('library.settings.store') }}" 
              class="validateForm settings-form" 
              method="POST" 
              enctype="multipart/form-data"
              id="librarySettingsForm">
            @csrf

            <!-- Field 1: Favicon Drag & Drop -->
            <div class="settings-form-group">
                <label class="form-group-label">
                    Library Favicon <span class="req-star">*</span>
                </label>

                @php
                    $currentFaviconUrl = '';
                    if (!empty($library->library_favicon)) {
                        if (file_exists(public_path('storage/' . $library->library_favicon))) {
                            $currentFaviconUrl = asset('storage/' . $library->library_favicon);
                        } elseif (file_exists(storage_path('app/public/' . $library->library_favicon))) {
                            $currentFaviconUrl = asset('storage/app/public/' . $library->library_favicon);
                        } else {
                            $currentFaviconUrl = asset('storage/' . $library->library_favicon);
                        }
                    }
                @endphp

                @if(!empty($currentFaviconUrl))
                <!-- Current Favicon Bar -->
                <div class="current-favicon-bar">
                    <div class="current-favicon-left">
                        <img src="{{ $currentFaviconUrl }}" alt="Favicon" class="current-favicon-img" onerror="this.style.display='none'">
                        <div>
                            <span class="current-favicon-badge">Current Favicon</span>
                            <span class="text-muted font-11 ms-2">Active on public library page</span>
                        </div>
                    </div>
                </div>
                @endif

                <div class="favicon-dropzone" id="faviconDropzone">
                    <input type="file" 
                           class="no-validate d-none @error('library_favicon') is-invalid @enderror" 
                           name="library_favicon" 
                           id="library_favicon"
                           accept=".jpg,.jpeg,.png,.ico">
                    
                    <!-- Idle State -->
                    <div class="dropzone-idle" id="dropzoneIdle">
                        <div class="dropzone-icon">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                        </div>
                        <div class="dropzone-text">
                            <span class="dropzone-primary-text">Drag &amp; drop favicon here, or <span class="dropzone-browse-text">Browse</span></span>
                            <span class="dropzone-hint-text">Recommended size: 64px × 64px (.ico, .png, .jpg up to 2MB)</span>
                        </div>
                    </div>

                    <!-- Selected Preview State -->
                    <div class="dropzone-preview" id="dropzonePreview" style="display: none;">
                        <div class="dropzone-preview-left">
                            <div class="dropzone-file-icon-box" id="dropzoneFileIconBox">
                                <i class="fa-solid fa-image text-primary"></i>
                            </div>
                            <div class="dropzone-file-info">
                                <span class="dropzone-file-name" id="dropzoneFileName">favicon.ico</span>
                                <span class="dropzone-file-size" id="dropzoneFileSize">0 KB</span>
                            </div>
                        </div>
                        <button type="button" class="dropzone-remove-btn" id="removeFaviconBtn" title="Remove file">
                            <i class="fa-solid fa-trash-can"></i>
                        </button>
                    </div>
                </div>
                @error('library_favicon')
                    <span class="field-error-text">{{ $message }}</span>
                @enderror
            </div>

            <!-- Field 2: Library Title (SEO) -->
            <div class="settings-form-group">
                <label class="form-group-label" for="libraryTitleInput">
                    Library Title (For SEO) <span class="req-star">*</span>
                </label>
                <input type="text" 
                       class="form-control-custom @error('library_title') is-invalid @enderror" 
                       name="library_title" 
                       id="libraryTitleInput"
                       value="{{ old('library_title', $library->library_title ?? '') }}" 
                       placeholder="e.g., Apex Study Library & Reading Hall" 
                       required>
                <span class="field-help-text">Appears in browser tabs and search engine results</span>
                @error('library_title')
                    <span class="field-error-text">{{ $message }}</span>
                @enderror
            </div>

            <!-- Field 3: Library Meta Description (SEO) -->
            <div class="settings-form-group">
                <label class="form-group-label" for="libraryMetaDescInput">
                    Library Meta Description (For SEO) <span class="req-star">*</span>
                </label>
                <textarea name="library_meta_description" 
                          id="libraryMetaDescInput"
                          rows="3"
                          class="form-control-custom @error('library_meta_description') is-invalid @enderror" 
                          placeholder="Brief description summarizing your library facilities, atmosphere, and services..." 
                          required>{{ old('library_meta_description', $library->library_meta_description ?? '') }}</textarea>
                <span class="field-help-text">Recommended length: 150-160 characters</span>
                @error('library_meta_description')
                    <span class="field-error-text">{{ $message }}</span>
                @enderror
            </div>

            <!-- Field 4: Primary Brand Color -->
            <div class="settings-form-group">
                <label class="form-group-label">
                    Library Primary Color <span class="req-star">*</span>
                </label>
                <div class="color-picker-wrapper">
                    <input type="color" 
                           id="libraryColorPicker"
                           name="library_primary_color" 
                           class="color-input-swatch @error('library_primary_color') is-invalid @enderror" 
                           value="{{ old('library_primary_color', $library->library_primary_color ?? '#18225f') }}"
                           required>
                    <input type="text" 
                           id="libraryColorHex" 
                           class="color-hex-input form-control-custom" 
                           value="{{ old('library_primary_color', $library->library_primary_color ?? '#18225f') }}"
                           maxlength="7"
                           placeholder="#18225F">
                </div>
                <!-- Quick Preset Swatches -->
                <div class="color-presets">
                    <span class="font-11 text-muted me-1">Presets:</span>
                    <button type="button" class="color-preset-btn" style="background:#18225f;" data-color="#18225f" title="Navy Blue"></button>
                    <button type="button" class="color-preset-btn" style="background:#1e1b4b;" data-color="#1e1b4b" title="Deep Indigo"></button>
                    <button type="button" class="color-preset-btn" style="background:#34939F;" data-color="#34939F" title="Teal"></button>
                    <button type="button" class="color-preset-btn" style="background:#065f46;" data-color="#065f46" title="Emerald"></button>
                    <button type="button" class="color-preset-btn" style="background:#991b1b;" data-color="#991b1b" title="Crimson"></button>
                </div>
                @error('library_primary_color')
                    <span class="field-error-text">{{ $message }}</span>
                @enderror
            </div>

            <!-- Field 5: Library Language -->
            <div class="settings-form-group">
                <label class="form-group-label" for="libraryLanguageSelect">
                    Library Language <span class="req-star">*</span>
                </label>
                <select name="library_language" id="libraryLanguageSelect" class="form-select-custom @error('library_language') is-invalid @enderror" required>
                    <option value="">Select Language</option>
                    <option value="English" {{ old('library_language', $library->library_language ?? 'English') == 'English' ? 'selected' : '' }}>English (Default)</option>
                    <option value="Hindi" {{ old('library_language', $library->library_language ?? '') == 'Hindi' ? 'selected' : '' }}>Hindi (Will be available soon!)</option>
                </select>
                @error('library_language')
                    <span class="field-error-text">{{ $message }}</span>
                @enderror
            </div>

            <!-- Field 6: Submit Button -->
            <div class="settings-form-group pt-2 mb-0">
                <button type="submit" class="btn-save-settings">
                    <i class="fa-solid fa-floppy-disk me-2"></i> Save Settings
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Favicon Drag and Drop Logic
        const dropzone = document.getElementById('faviconDropzone');
        const fileInput = document.getElementById('library_favicon');
        const dropzoneIdle = document.getElementById('dropzoneIdle');
        const dropzonePreview = document.getElementById('dropzonePreview');
        const dropzoneFileName = document.getElementById('dropzoneFileName');
        const dropzoneFileSize = document.getElementById('dropzoneFileSize');
        const dropzoneFileIconBox = document.getElementById('dropzoneFileIconBox');
        const removeBtn = document.getElementById('removeFaviconBtn');

        function formatFileSize(bytes) {
            if (bytes < 1024) return bytes + ' B';
            else if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
            else return (bytes / 1048576).toFixed(2) + ' MB';
        }

        function updateFaviconPreview(file) {
            if (!file) {
                dropzoneIdle.style.display = 'flex';
                dropzonePreview.style.display = 'none';
                return;
            }
            dropzoneFileName.textContent = file.name;
            dropzoneFileSize.textContent = formatFileSize(file.size);

            if (file.type && file.type.startsWith('image/')) {
                dropzoneFileIconBox.innerHTML = `<img src="${URL.createObjectURL(file)}" class="dropzone-thumb-preview" alt="Favicon Preview">`;
            } else {
                dropzoneFileIconBox.innerHTML = '<i class="fa-solid fa-image text-primary"></i>';
            }

            dropzoneIdle.style.display = 'none';
            dropzonePreview.style.display = 'flex';
        }

        if (dropzone && fileInput) {
            // Click to browse
            dropzone.addEventListener('click', function(e) {
                if (e.target.closest('#removeFaviconBtn')) return;
                fileInput.click();
            });

            fileInput.addEventListener('change', function() {
                if (this.files && this.files.length > 0) {
                    updateFaviconPreview(this.files[0]);
                } else {
                    updateFaviconPreview(null);
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
                        fileInput.files = newDt.files;
                    } catch (err) {
                        try {
                            fileInput.files = dt.files;
                        } catch (e2) {}
                    }
                    updateFaviconPreview(fileInput.files[0] || dt.files[0]);
                }
            });
        }

        if (removeBtn && fileInput) {
            removeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                fileInput.value = '';
                updateFaviconPreview(null);
            });
        }

        // Color Picker Synchronization Logic
        const colorPicker = document.getElementById('libraryColorPicker');
        const hexInput = document.getElementById('libraryColorHex');
        const presetBtns = document.querySelectorAll('.color-preset-btn');

        function syncColor(val) {
            if (!val) return;
            if (val.charAt(0) !== '#') val = '#' + val;
            if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
                if (colorPicker) colorPicker.value = val;
                if (hexInput) hexInput.value = val.toUpperCase();
            }
        }

        if (colorPicker) {
            colorPicker.addEventListener('input', function() {
                if (hexInput) hexInput.value = this.value.toUpperCase();
            });
        }

        if (hexInput) {
            hexInput.addEventListener('input', function() {
                syncColor(this.value);
            });
            hexInput.addEventListener('blur', function() {
                syncColor(this.value);
            });
        }

        presetBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const color = this.getAttribute('data-color');
                syncColor(color);
            });
        });
    });
</script>

@include('library.script')
@endsection