@extends('layouts.library')

@section('title', 'Admin Dashboard')

@section('content')
<!-- Branch Selector -->

@if(session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif


  <form  action="{{ isset($branch) ? route('branch.update', $branch->id) : route('branch.store') }}" 
    method="POST"enctype="multipart/form-data"
>
    @csrf

    @if(isset($branch))
        @method('PUT')  <!-- Use PUT for update -->
    @endif


    <div class="row mb-4 g-4">
        <div class="col-lg-8">
            <div class="card">
                <h4 class="mb-4">Branch Details</h4>
                <div class="row g-4">
                   <!-- Branch Name -->
                    <div class="col-lg-6">
                        <label for="name"> Library Branch Name <span>*</span></label>
                        <input type="text" id="name"
                               class="form-control @error('name') is-invalid @enderror"
                               name="name"
                               value="{{ old('name', $branch->name ?? '') }}"
                               placeholder="Enter Branch name"
                               {{ isset($branch) ? 'readonly' : '' }}>
                        @error('name')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                     <div class="col-lg-6">
                        <label for="name"> Display Library Branch Name <span>*</span></label>
                        <input type="text" 
                               class="form-control @error('display_name') is-invalid @enderror"
                               name="display_name"
                               value="{{ old('display_name', $branch->display_name ?? '') }}"
                               placeholder="Enter Branch name"
                               >
                        @error('display_name')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>

                    <!-- Branch Email -->
                   <div class="col-lg-6">
                        <label for="email">Email Id <span>*</span></label>
                        <input type="email" id="email"
                               class="form-control @error('email') is-invalid @enderror"
                               name="email"
                               value="{{ old('email', $branch->email ?? '') }}">
                        @error('email')
                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <!-- Branch Contact -->
                    <div class="col-lg-6">
                        <label for="mobile">Contact No (WhatsApp No.) <span>*</span></label>
                        <input type="text" id="mobile"
                               class="form-control digit-only @error('mobile') is-invalid @enderror"
                               name="mobile" maxlength="10"
                               value="{{ old('mobile', $branch->mobile ?? '') }}">
                        @error('mobile')
                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>
            </div>
            @if(isset($branch))
            <div class="card mt-5">
                <h4 class="mb-4">Branch Profile</h4>
                <div class="row g-4">
                    <!-- Branch Working Days -->
                   <div class="col-lg-12">
                        <label for="working_days">Library Working Days <span>*</span></label>
                        <textarea id="working_days"
                                  class="form-control char-only @error('working_days') is-invalid @enderror"
                                  name="working_days"
                                  placeholder="Working Days">{{ old('working_days', $branch->working_days ?? 'Monday, Tuesday, Wednesday, Thursday, Friday, Saturday, Sunday') }}</textarea>
                        @error('working_days')
                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                        <span class="text-info">You can edit this according to your Library</span>
                    </div>

                    <!-- Branch Description -->
                     <div class="col-lg-12">
                        <label for="description">Library Description <span>*</span></label>
                        <textarea id="description"
                                  class="form-control  @error('description') is-invalid @enderror"
                                  name="description" rows="5"
                                  placeholder="Enter Library Description">{{ old('description', $branch->description ?? '') }}</textarea>
                        @error('description')
                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <!-- Library Category -->
                   <div class="col-lg-12">
                        <label for="library_category">Library Category <span>*</span></label>
                        <select name="library_category" id="library_category"
                                class="form-select @error('library_category') is-invalid @enderror">
                            <option value="">Select Category</option>
                            <option value="Public" {{ old('library_category', $branch->library_category ?? '') == 'Public' ? 'selected' : '' }}>Public</option>
                            <option value="Private" {{ old('library_category', $branch->library_category ?? '') == 'Private' ? 'selected' : '' }}>Private</option>
                        </select>
                        @error('library_category')
                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                </div>
            </div>
            @endif 
            <!-- Library Address & Location -->
            <div class="card mt-5">
                <h4 class="mb-4">Library Address & Location</h4>
                <div class="row g-4">
                    <div class="col-lg-12">
                        <label for="library_address">Library Address (Library Full Address with Landmark)<span>*</span></label>
                        <textarea id="library_address" rows="5" class="form-control @error('library_address') is-invalid @enderror" name="library_address"
                            style="height:auto !important;">{{ old('library_address', $branch->library_address ?? '') }}</textarea>
                        @error('library_address')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>

                    <div class="col-lg-4">
                        <label for="stateid">State <span>*</span></label>
                        <select name="state_id" id="stateid" class="form-select @error('state_id') is-invalid @enderror">
                            <option value="">Select State</option>
                            @foreach($states as $value)
                            <option value="{{ $value->id }}"
                                {{ old('state_id', $branch->state_id ?? '') == $value->id ? 'selected' : '' }}>
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
                        <label for="cityid">City <span>*</span></label>
                        <select name="city_id" id="cityid" class="form-select @error('city_id') is-invalid @enderror">
                            <option value="">Select City</option>
                            @foreach($cities as $value)
                            <option value="{{ $value->id }}"
                                {{ old('city_id', $branch->city_id ?? '') == $value->id ? 'selected' : '' }}>
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
                        <label for="library_zip">Library ZIP Code <span>*</span></label>
                        <input type="text" id="library_zip" class="form-control digit-only @error('library_zip') is-invalid @enderror" name="library_zip" maxlength="6"
                            value="{{ old('library_zip', $branch->library_zip ?? '') }}">
                        @error('library_zip')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                </div>
            </div>
             @if(!isset($branch))
            <div class="card mt-5">
                <h4 class="mb-4">Branch Master</h4>
                <div class="row g-4">
                     <div class="col-lg-6">
                        <label for="">Add Seats to Library Branch <span>*</span></label>
                        <input type="text" name="seats" class="form-control digit-only @error('seats') is-invalid @enderror" id="" placeholder="Enter Seats No." value="{{ old('seats') }}">
                        @error('seats')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <div class="col-lg-6">
                        <label for="">Operating Hours <span>*</span></label>
                        <select class="form-select @error('hour') is-invalid @enderror" name="hour" id="hour">
                            <option value="">Select Hour</option>
                            <option value="16" {{ old('hour', isset($hour) ? $hour->hour : '') == 16 ? 'selected' : '' }}>16</option>
                            <option value="14" {{ old('hour', isset($hour) ? $hour->hour : '') == 14 ? 'selected' : '' }}>14</option>
                            <option value="12" {{ old('hour', isset($hour) ? $hour->hour : '') == 12 ? 'selected' : '' }}>12</option>
                            @can('has-permission','All Day')
                            <option value="24" {{ old('hour', isset($hour) ? $hour->hour : '') == 24 ? 'selected' : '' }}>24</option>
                            @endif
                        </select>
                        @error('hour')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <div class="col-lg-6">
                        <label for="">Locker Amount <span>*</span></label>
                        <input type="text" name="locker_amount" class="form-control digit-only @error('locker_amount') is-invalid @enderror" id="" placeholder="Enter Amt." value="{{old('locker_amount')}}">
                        @error('locker_amount')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                     <div class="col-lg-6">
                        <label for="">Extend Days <span>*</span></label>
                        <input type="text" class="form-control digit-only @error('extend_days') is-invalid @enderror no-validate" name="extend_days" placeholder="Enter Days" value="{{old('extend_days')}}">
                        @error('extend_days')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    
                </div>
            </div>
            @endif
            @if(isset($branch))
            <div class="card mt-5">
                <h4 class="mb-4">Google Map</h4>
                <div class="row g-4">
                    <div class="col-lg-12">
                        <label for="google_map">Google Map Embed URL</label>
                        <textarea name="google_map" id="google_map" class="form-control no-validate" rows="5" placeholder="Paste Google Map Embed Code here">{{ old('google_map', $branch->google_map ?? '') }}</textarea>
                        <span class="text-info"><b>Note</b>: Your provided library address will be shown to visitors on your listing, so please mention it correctly (Put Map Embed Code).</span>
                    </div>
                    <div class="col-lg-6">
                        <label for="">Location Longitude </label>
                        <input type="text" class="form-control digit-only  @error('longitude') is-invalid @enderror no-validate" name="longitude"
                            value="{{ old('longitude', $branch->longitude ?? '') }}">
                        @error('longitude')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>

                    <div class="col-lg-6">
                        <label for="">Location Latitude </label>
                        <input type="text" class="form-control digit-only @error('latitude') is-invalid @enderror no-validate" name="latitude"
                            value="{{ old('latitude', $branch->latitude ?? '') }}">
                        @error('latitude')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Library Features -->
            <div class="card mt-5">
                <h4 class="mb-4">Library Features</h4>
                <div class="row g-4">
                    <div class="col-lg-12">
                       @php
                            $selectedFeatures = old('features', []);
                            if (isset($branch) && $branch !== null && $branch->features) {
                                $selectedFeatures = old('features', json_decode($branch->features, true));
                            }
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
                                        {{ in_array($feature->id, $selectedFeatures ?? []) ? 'checked' : '' }} class="no-validate">
                                    {{ $feature->name }}
                                </label>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Library Images Upload -->
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
                            @enderror
                        </label>
                    </div>
                    <small class="text-info d-block">Multiple images upload and must be in one of the following formats: JPG, JPEG, PNG, SVG, or WEBP. Image Size must be in 1024 * 1024 px</small>
                    <div id="imagePreview" style="display: flex; gap: 10px; flex-wrap: wrap;"></div>
                </div>
            </div>

        </div>
        @endif
        <!-- Sidebar for Library Logo and Submit -->
        @if(isset($branch))
        <div class="col-lg-4">
            <div class="card stick">
                <h4 class="mb-4">Library Logo</h4>
                 <div class="row g-4">
                    <div class="col-lg-12">
                        <div class="previewImage">
                            <img id="image" src="{{ asset('public/' . (isset($branch) && $branch->logo ? $branch->logo : 'uploads/images/no-image.png')) }}" alt="Library Logo" class="img-fluid" style="max-width: 100%;">
                        </div>
                        <label for="libraryLogo" class="uploadLogo">Upload Image
                            <input type="file" id="libraryLogo" class="form-control no-validate d-none @error('logo') is-invalid @enderror" name="logo" accept="image/*" onchange="document.getElementById('image').src = window.URL.createObjectURL(this.files[0])">
                            @error('logo')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </label>
                        <small class="text-info d-block mt-2">Image size should be 180*180 pixels (PNG, JPG, JPEG, SVG, WEBP).</small>
                    </div>
                </div>
               <div class="row mt-4">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary w-100 ">
                            {{ isset($branch) ? 'Update Profile' : 'Create Branch' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @else 
        <div class="row mt-4">
            <div class="col-lg-4">
                <button type="submit" class="btn btn-primary w-100 ">
                    {{ isset($branch) ? 'Update Profile' : 'Create Branch' }}
                </button>
            </div>
        </div>
        @endif
    </div>
</form>



<script>
    $(document).ready(function() {
        // Show existing images if available
        let existingImages = @json(json_decode($branch -> library_images ?? '[]'));

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