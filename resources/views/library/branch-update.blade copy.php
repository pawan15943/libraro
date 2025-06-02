@extends('layouts.library')

@section('title', 'Admin Dashboard')

@section('content')
@if($iscomp==false && !$is_expire)

<div class="row">
    <div class="col-lg-12">
        <div class="steps">
            <ul>
                <li>
                    <a href="{{ ($checkSub) ? '#' : route('subscriptions.choosePlan')  }}">Pick Your Perfect Plan</a>
                </li>

                <li>
                    <a href="{{ ($ispaid) ? route('subscriptions.payment')  : '#' }}">Make Payment</a>
                </li>
                <li class="active">
                    <a href="{{ ($ispaid ) ? route('profile') : '#' }}">Update Profile</a>
                </li>
                <li>
                    <a href="{{ ($checkSub && $ispaid && $isProfile) ? route('library.master') : '#' }}">Configure Library</a>
                </li>
            </ul>


        </div>
    </div>
</div>
<div class="row  mb-4">
    <div class="col-lg-12">
        <h2 class="text-center typing-text">A few details to make it yours!</h2>
    </div>
</div>
@endif
<!-- Content -->



<form action="{{ route('library.profile.update') }}" class="validateForm profile" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="row mb-4 g-4">
        <div class="col-lg-8">
            <div class="card">
                <h4 class="mb-4">Library Details</h4>
                <div class="row g-4">

                    <!-- Library Name -->
                    <div class="col-lg-6">
                        <label for="">Library Name <span>*</span></label>
                        <input type="text" class="form-control @error('library_name') is-invalid @enderror" name="library_name"
                            value="{{ old('library_name', $library->library_name ?? '') }}" placeholder="Enter library name" readonly>
                        @error('library_name')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>

                    <!-- Library Email -->
                    <div class="col-lg-6">
                        <label for="">Library Email Id <span>*</span></label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" name="email"
                            value="{{ old('email', $library->email ?? '') }}" readonly>
                        @error('email')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>

                    <!-- Library Contact -->
                    <div class="col-lg-6">
                        <label for="">Library Contact No (WhatsApp No.) <span>*</span></label>
                        <input type="text" class="form-control digit-only @error('library_mobile') is-invalid @enderror" name="library_mobile" maxlength="10"
                            value="{{ old('library_mobile', $library->library_mobile ?? '') }}" readonly>
                        @error('library_mobile')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <!-- Library Working Days -->
                    <div class="col-lg-6">
                        <label for="working_days">Library Working Days <span>*</span></label>
                        <textarea type="text" class="form-control @error('working_days') is-invalid @enderror"
                            name="working_days"

                            placeholder="Working Days">{{ old('working_days', $library->working_days ?? 'Monday, Tuesday, Wednesday, Thursday, Friday, Saturday, Sunday') }}</textarea>

                        @error('working_days')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                        <span class="text-info">You can edit this according to your Library</span>
                    </div>
                    <!-- Library Description -->
                    <div class="col-lg-12">
                        <label for="">Library Description <span>*</span></label>
                        <textarea class="form-control  @error('description') is-invalid @enderror" name="description" rows="5"
                            value="{{ old('description', $library->description ?? '') }}" placeholder="Enter Library Description">{{ $library->description }}</textarea>
                        @error('description')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>



                    <!-- Category -->
                    <div class="col-lg-12">
                        <label for="library_category">Library Category <span>*</span></label>
                        <select name="library_category" id="library_category" class="form-select @error('library_category') is-invalid @enderror">
                            <option value="">Select Category</option>
                            <option value="Public" {{ old('library_category', $library->library_category ?? '') == 'Public' ? 'selected' : '' }}>Public</option>
                            <option value="Private" {{ old('library_category', $library->library_category ?? '') == 'Private' ? 'selected' : '' }}>Private</option>
                        </select>
                        @error('library_category')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                </div>

            </div>

            <div class="card mt-5">
                <h4 class="mb-4">Library Address & Location</h4>
                <div class="row g-4">
                    <div class="col-lg-12">
                        <label for="">Library Address (Library Full Address with Landmark)<span>*</span></label>
                        <textarea rows="5" class="form-control @error('library_address') is-invalid @enderror" name="library_address"
                            style="height:auto !important; ">{{ old('library_address', $library->library_address ?? '') }}</textarea>
                        @error('library_address')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>

                    <div class="col-lg-4">
                        <label for="">State <span>*</span></label>
                        <select name="state_id" id="stateid" class="form-select @error('state_id') is-invalid @enderror">
                            <option value="">Select State</option>
                            @foreach($states as $value)
                            <option value="{{ $value->id }}"
                                {{ old('state_id', $library->state_id ?? '') == $value->id ? 'selected' : '' }}>
                                {{ $value->state_name }}
                            </option>
                            @endforeach
                        </select>
                        @error('state_id')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>

                    <div class="col-lg-4">
                        <label for="">City <span>*</span></label>
                        <select name="city_id" id="cityid" class="form-select @error('city_id') is-invalid @enderror">
                            <option value="">Select City</option>
                            @php if($library->city_id != "") @endphp
                            @foreach($citis as $value)
                            <option value="{{ $value->id }}"
                                {{ old('city_id', $library->city_id ?? '') == $value->id ? 'selected' : '' }}>
                                {{ $value->city_name }}
                            </option>
                            @endforeach
                        </select>
                        @error('city_id')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>

                    <div class="col-lg-4">
                        <label for="">Library ZIP Code <span>*</span></label>
                        <input type="text" class="form-control digit-only @error('library_zip') is-invalid @enderror" name="library_zip" maxlength="6"
                            value="{{ old('library_zip', $library->library_zip ?? '') }}">
                        @error('library_zip')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>

                    <div class="col-lg-12">
                        <label for="google_map">Google Map Embed URL</label>
                        <textarea name="google_map" id="google_map" class="form-control no-validate" rows="5" placeholder="Paste Google Map Embed Code here">{{$library->google_map}}</textarea>
                        <span class="text-info"><b>Note</b> : Your provided library address will be shown to visitors on your listing, so please mention it correctly (Put Map Embed Code).</span>
                    </div>

                    <div class="col-lg-6">
                        <label for="">Library Location Longitude </label>
                        <input type="text" class="form-control  @error('longitude') is-invalid @enderror no-validate" name="longitude"
                            value="{{ old('longitude', $library->longitude ?? '') }}">
                        @error('longitude')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>

                    <div class="col-lg-6">
                        <label for="">Library Location Latitude </label>
                        <input type="text" class="form-control  @error('latitude') is-invalid @enderror no-validate" name="latitude"
                            value="{{ old('latitude', $library->latitude ?? '') }}">
                        @error('latitude')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                </div>

            </div>

            <div class="card mt-5">
                <h4 class="mb-4">Library Owner Details</h4>
                <div class="row g-4">
                    <div class="col-lg-12">
                        <label for="">Owner Name <span>*</span></label>
                        <input type="text" class="form-control char-only @error('library_owner') is-invalid @enderror" name="library_owner" value="{{ old('library_owner', $library->library_owner ?? '') }}">
                        @error('library_owner')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <div class="col-lg-6">
                        <label for="">Owner Email Id <span>*</span></label>
                        <input type="email" class="form-control @error('library_owner_email') is-invalid @enderror" name="library_owner_email" value="{{ old('library_owner_email', $library->library_owner_email ?? '') }}">
                        @error('library_owner_email')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <div class="col-lg-6">
                        <label for="">Owner Contact Number (WhatsApp) <span>*</span></label>
                        <input type="text" class="form-control digit-only @error('library_owner_contact') is-invalid @enderror" name="library_owner_contact" maxlength="10" value="{{ old('library_owner_contact', $library->library_owner_contact ?? '') }}">
                        @error('library_owner_contact')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                </div>
            </div>


            <div class="card mt-5">
                <h4 class="mb-4">Library Features</h4>
                <div class="row g-4">
                    <div class="col-lg-12">
                        @php
                        $selectedFeatures = $library->features ? json_decode($library->features, true) : [];
                        @endphp
                        <ul class="libraryFeatures">
                            @foreach ($features as $feature)
                            <li>
                                <img src="{{ asset('public/'.$feature->image) }}" alt="Image" width="50">
                                <label class="permission">
                                    <input
                                        type="checkbox"
                                        name="features[]"
                                        value="{{ $feature->id }}"
                                        {{ in_array($feature->id, old('features', $selectedFeatures ?? [])) ? 'checked' : '' }} class="no-validate">
                                    {{ $feature->name }}
                                </label>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Library Images -->

            <div class="card mt-5">
                <h4 class="mb-4">Library Gallery</h4>
                <div class="row g-4">
                    <div class="col-lg-12">
                        <label class="libraryImage">Drag and Drop Library Inner Images
                            <input type="file" class="form-control no-validate d-none @error('library_images') is-invalid @enderror" name="library_images[]" id="libraryImages" multiple accept="image/*">
                            @error('library_images')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                        </label>
                        @enderror
                    </div>
                    <small class="text-info d-block">Multiple images upload and must be in one of the following formats: JPG, JPEG, PNG, SVG, or WEBP. Image Size must be in 1024 * 1024 px</small>
                    <div id="imagePreview" style="display: flex; gap: 10px; flex-wrap: wrap;"></div>
                </div>
            </div>

        </div>
        <div class="col-lg-4">
            <div class="card stick">
                <h4 class="mb-4">Library Logo</h4>
                <div class="row g-4">
                    <div class="col-lg-12">
                    <div class="preview" id="preview">
                        @if(old('library_logo'))
                            <img src="{{ asset('public/' . old('library_logo')) }}" class="img-thumbnail rounded shadow preview" style="max-width: 250px;">
                        @elseif(isset($library) && $library->library_logo)
                            <img src="{{ asset('public/' . $library->library_logo) }}" class="img-thumbnail rounded shadow preview" style="max-width: 250px;">
                        @else
                            <!-- Show empty preview or placeholder -->
                            <p class="text-muted">No logo uploaded</p>
                        @endif
                    </div>
                        <div class="progress">
                            <div class="progress-bar" id="progressBar"></div>
                        </div>
                        <p class="status-message" id="statusMessage"></p>
                        <label class="upload-lable">Library Logo (Optional)
                            <input type="file" class="form-control d-none no-validate @error('library_logo') is-invalid @enderror" name="library_logo" id="fileInput" accept="image/jpeg, image/png, image/webp">
                            @error('library_logo')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </label>
                        <small class="text-info d-block">The logo should be 250px wide and 250px high and must be in one of the following formats: JPG, JPEG, PNG, SVG, or WEBP.</small>
                    </div>

                    <div class="col-lg-12">
                        <button type="submit" value="Login" placeholder="Email Id" class="btn btn-primary button">Update and Next</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>


<script>
    $(document).ready(function() {
        // Show existing images if available
        let existingImages = @json(json_decode($library -> library_images ?? '[]'));

        $.each(existingImages, function(index, image) {
            $("#imagePreview").append(
                `<div class="image-container" style="position: relative; display: inline-block;">
                    <img src="{{ asset('public') }}/${image}" width="100" style="margin: 5px; border: 1px solid #ddd; padding: 5px;">
                    <button type="button" class="btn btn-danger btn-sm remove-existing-image" data-image="${image}" 
                            style="position: absolute; top: 0; right: 0;">×</button>
                </div>`
            );
        });

        // Preview new images on selection
        $("#libraryImages").on("change", function(event) {
            $("#imagePreview1").html(""); // Clear previous previews

            let files = event.target.files;
            $.each(files, function(index, file) {
                let reader = new FileReader();
                reader.onload = function(e) {
                    $("#imagePreview").append(
                        `<img src="${e.target.result}" width="100" style="margin: 5px; border: 1px solid #ddd; padding: 5px;">`
                    );
                };
                reader.readAsDataURL(file);
            });
        });

        // Remove existing image
        $(document).on("click", ".remove-existing-image", function() {
            let image = $(this).data("image");
            $(this).parent().remove();

            // Add hidden input to mark image as deleted
            $("<input>").attr({
                type: "hidden",
                name: "deleted_images[]",
                value: image
            }).appendTo("form");
        });
    });
</script>

<script>
    $(document).ready(function() {
        $('#fileInput').on('change', function(event) {
            let file = event.target.files[0];
            let validTypes = ["image/jpeg", "image/png", "image/webp"];
            let maxSize = 500 * 1024;
            let statusMessage = $('#statusMessage');
            let preview = $('#preview');
            let progressBar = $('#progressBar');

            statusMessage.text('').removeClass('success error');
            preview.html('');
            progressBar.width('0');

            if (!file) return;
            if (!validTypes.includes(file.type)) {
                statusMessage.text('Invalid file format. Only JPG, PNG, JPEG, WEBP allowed.').addClass('error');
                return;
            }
            if (file.size > maxSize) {
                statusMessage.text('File size exceeds 150 KB.').addClass('error');
                return;
            }

            let reader = new FileReader();
            reader.onload = function(e) {
                preview.html(`<img src="${e.target.result}" alt="Preview" class="preview">`);

                let progress = 0;
                let interval = setInterval(() => {
                    progress += 20;
                    progressBar.width(progress + "%");
                    if (progress >= 100) {
                        clearInterval(interval);
                        statusMessage.text('Upload Successful!').addClass('success');
                    }
                }, 200);
            };
            reader.readAsDataURL(file);
        });
    });
</script>
@include('library.script')
@endsection