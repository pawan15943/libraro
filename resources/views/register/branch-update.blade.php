@extends('layouts.library')

@section('title', 'Admin Dashboard')

@section('content')


<form action="{{ isset($branch) ? route('branch.update', $branch->id) : route('branch.store') }}" method="POST" enctype="multipart/form-data" id="branchUpdate">
    @csrf

    @if(isset($branch))
    @method('PUT')
    @endif


    <div class="row mb-4 g-4">
        <div class="col-lg-8">
            <div class="card">
                <h4 class="mb-4">Branch Details</h4>
                <div class="row g-4">
                    <!-- Branch Name -->
                    <div class="col-lg-6">
                        <label for="name"> Branch Name <span>*</span></label>
                        <input type="text" id="name" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $branch->name ?? '') }}" placeholder="Enter Branch name" {{ isset($branch) ? 'readonly' : '' }}>
                        @error('name')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                        <small class="text-information">Note : For internal use only. You can’t change this later.</small>
                    </div>
                    @if(isset($branch))
                    <div class="col-lg-6">
                        <label for="name"> Branch Display Name <span>*</span></label>
                        <input type="text" class="form-control @error('display_name') is-invalid @enderror" name="display_name" value="{{ old('display_name', $branch->display_name ?? '') }}" placeholder="Enter Branch name">
                        @error('display_name')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                        <small class="text-information">Note : This will be visible in your online listing.</small>
                    </div>
                    @endif

                     <div class="col-lg-6">
                        <label for="name"> Founder Day<span>*</span></label>
                        <input type="date" class="form-control @error('founder_day') is-invalid @enderror" name="founder_day" value="{{ old('founder_day', $branch->founder_day ?? '') }}" placeholder="Enter Library Founder Date">
                        @error('founder_day')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                        
                     </div>

                    <!-- Branch Email -->
                    <div class="col-lg-6">
                        <label for="email">Email Id <span>*</span></label>
                        <input type="email" id="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $branch->email ?? '') }}">
                        @error('email')
                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <!-- Branch Contact -->
                    <div class="col-lg-6">
                        <label for="mobile">Contact No (WhatsApp No.) <span>*</span></label>
                        <input type="text" id="mobile" class="form-control digit-only @error('mobile') is-invalid @enderror" name="mobile" maxlength="10" value="{{ old('mobile', $branch->mobile ?? '') }}">
                        @error('mobile')
                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror

                    </div>
                    {{-- @can('has-permission','QR Seat Booking') --}}
                    <div class="col-lg-12">
                        <label for="mobile">UPI ID(for payment receive)<span>*</span></label>
                        <input type="text" class="form-control  @error('upi_id') is-invalid @enderror" name="upi_id" value="{{ old('upi_id', $branch->upi_id ?? '') }}">
                        @error('upi_id')
                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                        <small class="text-information">Note : Add your UPI ID to enable online and QR-code bookings from your listing page.</small>
                    </div>
                    {{-- @endcan --}}
                </div>
            </div>
            @if(isset($branch))
            <div class="card mt-5">
                <h4 class="mb-4">Branch Profile</h4>
                <div class="row g-4">
                    <!-- Branch Working Days -->
                    <div class="col-lg-12">
                        <label for="working_days">Library Working Days <span>*</span></label>
                        <textarea id="working_days" class="form-control char-only @error('working_days') is-invalid @enderror" name="working_days" placeholder="Working Days">{{ old('working_days', $branch->working_days ?? 'Monday, Tuesday, Wednesday, Thursday, Friday, Saturday, Sunday') }}</textarea>
                        @error('working_days')
                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                        <small class="text-information">Note : You can edit this according to your Library</small>
                    </div>

                    <!-- Branch Description -->
                    <div class="col-lg-12">
                        <label for="description">Tell us about your library: when it began and what you provide for your learners. </label>
                        <textarea id="description" class="form-control  @error('description') is-invalid @enderror" name="description" rows="5" placeholder="Enter Library Description">{{ old('description', $branch->description ?? '') }}</textarea>
                        @error('description')
                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>

                    <!-- Library Category -->
                    <div class="col-lg-12">
                        <label for="library_category">Library Category</label>
                        <select name="library_category" id="library_category" class="form-select @error('library_category') is-invalid @enderror">

                            <option value="">Select Category</option>

                            {{-- Show existing DB value if it’s not in the list --}}
                            @if($branch->library_category && !in_array($branch->library_category, ['Public','Private']))
                            <option value="{{ $branch->library_category }}" selected>
                                {{ $branch->library_category }}
                            </option>
                            @endif

                            <option value="Public" {{ old('library_category', $branch->library_category ?? '') === 'Public' ? 'selected' : '' }}>
                                Public
                            </option>

                            <option value="Private" {{ old('library_category', $branch->library_category ?? '') === 'Private' ? 'selected' : '' }}>
                                Private
                            </option>

                        </select>

                        @error('library_category')
                        <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                        @enderror
                    </div>
                </div>
            </div>
            @endif 

            <!-- Library Address & Location -->
            @if(isset($branch))
            <div class="card mt-5" style="overflow: visible !important;">   
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
                        <small class="text-information">Note : Add your correct address to show it on your listing page.”</small>
                    </div>

                    <div class="col-lg-4">
                        <label for="stateid">State <span>*</span></label>
                        <select name="state_id" id="state_id" class="form-select @error('state_id') is-invalid @enderror">
                            <option value="">Select State</option>
                            @foreach($states as $value)
                            <option value="{{ $value->id }}" {{ old('state_id', $branch->state_id ?? '') == $value->id ? 'selected' : '' }}>
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
                        <select name="city_id" id="city_id" class="form-select @error('city_id') is-invalid @enderror">
                            <option value="">Select City</option>
                           
                        </select>
                        @error('city_id')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>

                    <div class="col-lg-4">
                        <label for="library_zip">Library ZIP Code <span>*</span></label>
                        <input type="text" id="library_zip" class="form-control digit-only @error('library_zip') is-invalid @enderror" name="library_zip" maxlength="6" value="{{ old('library_zip', $branch->library_zip ?? '') }}">
                        @error('library_zip')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                </div>
            </div>
            @endif

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

                            <!-- Hours 10 to 23 -->
                            <option value="10" {{ old('hour', isset($hour) ? $hour->hour : '') == 10 ? 'selected' : '' }}>10</option>
                            <option value="11" {{ old('hour', isset($hour) ? $hour->hour : '') == 11 ? 'selected' : '' }}>11</option>
                            <option value="12" {{ old('hour', isset($hour) ? $hour->hour : '') == 12 ? 'selected' : '' }}>12</option>
                            <option value="13" {{ old('hour', isset($hour) ? $hour->hour : '') == 13 ? 'selected' : '' }}>13</option>
                            <option value="14" {{ old('hour', isset($hour) ? $hour->hour : '') == 14 ? 'selected' : '' }}>14</option>
                            <option value="15" {{ old('hour', isset($hour) ? $hour->hour : '') == 15 ? 'selected' : '' }}>15</option>
                            <option value="16" {{ old('hour', isset($hour) ? $hour->hour : '') == 16 ? 'selected' : '' }}>16</option>
                            <option value="17" {{ old('hour', isset($hour) ? $hour->hour : '') == 17 ? 'selected' : '' }}>17</option>
                            <option value="18" {{ old('hour', isset($hour) ? $hour->hour : '') == 18 ? 'selected' : '' }}>18</option>
                            <option value="19" {{ old('hour', isset($hour) ? $hour->hour : '') == 19 ? 'selected' : '' }}>19</option>
                            <option value="20" {{ old('hour', isset($hour) ? $hour->hour : '') == 20 ? 'selected' : '' }}>20</option>
                            <option value="21" {{ old('hour', isset($hour) ? $hour->hour : '') == 21 ? 'selected' : '' }}>21</option>
                            <option value="22" {{ old('hour', isset($hour) ? $hour->hour : '') == 22 ? 'selected' : '' }}>22</option>
                            <option value="23" {{ old('hour', isset($hour) ? $hour->hour : '') == 23 ? 'selected' : '' }}>23</option>

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
                        <small class="text-information">Note : Enter 0 if you don’t offer lockers.</small>
                    </div>
                    <div class="col-lg-6">
                        <label for="">Extend Days <span>*</span></label>
                        <input type="text" class="form-control digit-only @error('extend_days') is-invalid @enderror no-validate" name="extend_days" placeholder="Enter Days" value="{{old('extend_days')}}">
                        @error('extend_days')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                        <small class="text-information">Note : Enter days allowed after plan expiry.</small>
                    </div>

                    <div class="col-lg-6">
                        <label for="">Token Money(optional) </label>
                        <input type="text" class="form-control digit-only @error('token_money') is-invalid @enderror no-validate" name="token_money" placeholder="Enter Days" value="{{old('token_money')}}">
                        @error('token_money')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                </div>
            </div>
            @endif

            @if(!isset($branch))
            <div class="card mt-5" style="overflow: visible !important;">
                <h4 class="mb-4">Plan Duration and Floor info</h4>
                @php
                    $oldPlans = old('plans', []);
                @endphp
                <div class="row g-4">

                    <!-- Plans -->
                    <div class="col-lg-6">
                        <label>Choose your Plans <span class="text-danger">*</span></label>
                    
                        <select class="form-select h-auto @error('plans') is-invalid @enderror" id="duration" name="plans[]" multiple>
                            <option value="" >Choose your plans</option>
                            <optgroup label="Months">
                                @for($i = 1; $i <= 12; $i++)
                                    <option value="{{ $i }} MONTH">{{ $i }} MONTH</option>
                                @endfor
                            </optgroup>

                            <optgroup label="Weeks">
                                @for($i = 1; $i <= 4; $i++)
                                    <option value="{{ $i }} WEEK">{{ $i }} WEEK</option>
                                @endfor
                            </optgroup>

                            <optgroup label="Days">
                                @for($i = 1; $i <= 31; $i++)
                                    <option value="{{ $i }} DAY">{{ $i }} DAY</option>
                                @endfor
                            </optgroup>
                        </select>
                        @error('plans')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>

                    <div class="col-lg-6">
                        <label>Choose only for 1 Month <span class="text-danger">*</span></label>
                        <select class="form-select @error('monthdays') is-invalid @enderror" name="monthdays">
                            <option value="">Choose your Month Duration</option>
                            <option value="30" {{ old('monthdays') == 30 ? 'selected' : '' }}>30 Days</option>
                            <option value="28" {{ old('monthdays') == 28 ? 'selected' : '' }}>28 Days</option>
                            <option value="">Acoording to Months</option>
                        </select>
                        @error('monthdays')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>

                    <!-- Add Floor Button -->
                    <div class="col-lg-12">
                        <p> <b>Note :</b>   Fill only when you have multiple floors in your library </p>
                    </div>

                    <!-- Floors Container -->
                    <div class="col-lg-12" id="floorWrapper">

                        <div class="floor-block border rounded p-3 mb-3">
                            <div class="row g-4 align-items-end">

                                <div class="col-lg-4">
                                    <label>Floor Name </label>
                                    <input class="form-control" type="text" name="floors[0][name]" placeholder="Floor Name">
                                </div>

                                <div class="col-lg-3">
                                    <label>Seat No From </label>
                                    <input class="form-control" type="number" name="floors[0][from]" placeholder="1">
                                </div>

                                <div class="col-lg-3">
                                    <label>Seat No To </label>
                                    <input class="form-control" type="number" name="floors[0][to]" placeholder="50">
                                </div>

                                <div class="col-lg-2">
                                    <button type="button" class="btn btn-primary btn-sm mt-2" id="addFloor">
                                        Add Floor
                                    </button>
                                </div>

                            </div>
                        </div>

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
                        <label for="">Location Longitude <span>*</span></label>
                        <input type="text" class="form-control digit-only  @error('longitude') is-invalid @enderror no-validate" name="longitude" value="{{ old('longitude', $branch->longitude ?? '') }}">
                        @error('longitude')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>

                    <div class="col-lg-6">
                        <label for="">Location Latitude <span>*</span></label>
                        <input type="text" class="form-control digit-only @error('latitude') is-invalid @enderror no-validate" name="latitude" value="{{ old('latitude', $branch->latitude ?? '') }}">
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
                                    <input type="checkbox" name="features[]" value="{{ $feature->id }}" {{ in_array($feature->id, $selectedFeatures ?? []) ? 'checked' : '' }} class="no-validate">
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
                    <small class="text-danger d-block">You can only allow to upload 4 images of your library</small>
                    <small id="fileUploadError" class="text-danger mt-2"></small>
                    <div id="imagePreview1" style="display: flex; gap: 10px; flex-wrap: wrap;"></div>
                </div>
            </div>

            @endif
             <div class="col-lg-6">
                <label for="">Billing Fix Date(optional) </label>
                <input type="text" class="form-control digit-only @error('fixed_billing_date') is-invalid @enderror no-validate" name="fixed_billing_date" placeholder="Enter like 1" value="{{old('fixed_billing_date')}}" maxlength="2" >
                @error('fixed_billing_date')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
        </div>

        <!-- Sidebar for Library Logo and Submit -->

        <div class="col-lg-4">

            <div class="card stick">
                <h4 class="mb-4">Library Logo</h4>
                <div class="row g-4">
                    <div class="col-lg-12">
                        <div class="preview" id="preview">
                            @if(old('library_logo'))
                            <img src="{{ asset('public/' . old('library_logo')) }}" class="img-thumbnail rounded shadow preview" style="max-width: 250px;">
                            @elseif(isset($branch) && $branch->library_logo)
                            <img src="{{ asset('public/' . $branch->library_logo) }}" class="img-thumbnail rounded shadow preview" style="max-width: 250px;">
                            @else
                            <!-- Show empty preview or placeholder -->
                            <img src="{{ asset('public/img/user.png') }}" class="img-thumbnail rounded shadow preview" style="max-width: 250px;">
                            <p class="text-muted text-center">No logo uploaded</p>
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
                        <small class="text-info d-block" style="font-size: .8rem;">The logo should be 250px wide and 250px high and must be in one of the following formats: JPG, JPEG, PNG, SVG, or WEBP.</small>
                        <div id="logoUploadError" class="text-danger mt-2"></div>
                        <small class="text-information">Upload a logo to display it in your account and on receipts.</small>

                    </div>

                    <div class="col-lg-12">
                        <button type="submit" class="btn btn-primary button">
                            {{ isset($branch) ? 'Update' : 'Create' }}
                        </button>
                    </div>

                </div>
            </div>

        </div>

    </div>
</form>

<script>
$(document).ready(function () {

    function loadCities(stateId, selectedCity = null) {

        if (!stateId || !cityChoices) return;

        $.ajax({
            url: "{{ route('cityGetStateWise') }}",
            type: "GET",
            data: { state_id: stateId },
            dataType: "json",
            success: function (cities) {

                // Convert response to Choices format
                let choicesData = [];

                $.each(cities, function (id, name) {
                    choicesData.push({
                        value: id,
                        label: name,
                        selected: selectedCity == id
                    });
                });

                // 🔥 THIS IS REQUIRED
                cityChoices.clearChoices();
                cityChoices.setChoices(choicesData, 'value', 'label', true);
            }
        });
    }

    // State change
    $('#state_id').on('change', function () {
        loadCities($(this).val());
    });

    // Edit page support
    let initialState = $('#state_id').val();
    let selectedCity = "{{ old('city_id', $branch->city_id ?? '') }}";

    if (initialState) {
        loadCities(initialState, selectedCity);
    }

});
</script>


<script>
    $(document).ready(function() {
        // Show existing images if available
        let existingImages = @json(json_decode($branch -> library_images ?? '[]'));

        $.each(existingImages, function(index, image) {
            $("#imagePreview1").append(
                `<div class="image-container" style="position: relative; display: inline-block;">
                    <img src="{{ asset('public') }}/${image}" width="100" style="margin: 5px; border: 1px solid #ddd; padding: 5px;">
                    <button type="button" class="btn btn-danger btn-sm remove-existing-image" data-image="${image}" 
                            style="position: absolute; top: 0; right: 0;">×</button>
                </div>`
            );
        });



        // Remove existing image
        $(document).on("click", ".remove-existing-image", function() {
            let image = $(this).data("image");
            $(this).parent().remove();

            // Add hidden input to mark image as deleted
            $("<input>").attr({
                type: "hidden"
                , name: "deleted_images[]"
                , value: image
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
    $(document).ready(function() {
        const maxSize = 2 * 1024 * 1024; // 2 MB
        const maxFiles = 4;
        const allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/svg+xml', 'image/webp'];

        $('#libraryImages').on('change', function() {
            const files = this.files;
            const previewContainer = $('#imagePreview1');
            previewContainer.empty(); // Clear previous previews
            $("#fileUploadError").html('');
            let error = '';

            if (files.length > maxFiles) {
                error = `You can upload only up to ${maxFiles} images.`;
            }

            Array.from(files).forEach((file, index) => {
                if (!allowedTypes.includes(file.type)) {
                    error = `File type ${file.type} is not allowed.`;
                    return;
                }

                if (file.size > maxSize) {
                    error = `File "${file.name}" exceeds 2 MB size limit.`;
                    return;
                }

                // Show preview if valid
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Create wrapper for image + close button
                    const wrapper = $('<div>').css({
                        position: 'relative'
                        , display: 'inline-block'
                        , margin: '5px'
                    });

                    // Create image
                    const img = $('<img>').attr('src', e.target.result).css({
                        width: '100px'
                        , height: '100px'
                        , objectFit: 'cover'
                        , border: '1px solid #ccc'
                        , borderRadius: '5px'
                    });

                    // Create close button
                    const closeBtn = $(
                        `<button type="button" class="btn btn-danger btn-sm remove-existing-image" 
            style="position:absolute; top:0; right:0; padding:2px 6px;">×</button>`
                    );

                    // Remove image on button click
                    closeBtn.on('click', function() {
                        wrapper.remove();
                    });

                    // Append image and close button to wrapper
                    wrapper.append(img).append(closeBtn);

                    // Append wrapper to preview container
                    previewContainer.append(wrapper);
                };
                reader.readAsDataURL(file);

            });

            if (error !== '') {
                $("#fileUploadError").html(`<span class="text-danger">${error}</span>`);
                $('#branchUpdate button[type="submit"]').prop('disabled', true);
            } else {
                $('#branchUpdate button[type="submit"]').prop('disabled', false);
            }
        });

        // Logo
        $('#fileInput').on('change', function() {
            const file = this.files[0];
            if (file && file.size <= maxSize) {
                $("#logoUploadError").html('');
                $('#branchUpdate button[type="submit"]').prop('disabled', false);

            }
        });

        $('#branchUpdate').on('submit', function(e) {

            const files = $('#libraryImages')[0].files;
            let hasError = false;
            let errorMsg = '';
            const fileInput = $('#fileInput')[0];

            if (fileInput.files.length > 0) {
                const fileSize = fileInput.files[0].size;

                if (fileSize > maxSize) {
                    $("#logoUploadError").html('Image size should not exceed 2 MB.');
                    e.preventDefault();
                }
            }
            if (files.length > maxFiles) {
                errorMsg = `You can upload only up to ${maxFiles} images.`;
                hasError = true;
            }

            Array.from(files).forEach(file => {
                if (!allowedTypes.includes(file.type)) {
                    errorMsg = `File type ${file.type} is not allowed.`;
                    hasError = true;
                }

                if (file.size > maxSize) {
                    errorMsg = `File "${file.name}" exceeds 2 MB size limit.`;
                    hasError = true;
                }
            });

            if (hasError) {
                $("#fileUploadError").html(`<span class="text-danger">${errorMsg}</span>`);
                e.preventDefault();
            }
        });
    });

</script>


<script>
$(document).ready(function () {

    // ADD FLOOR
    $('#addFloor').on('click', function () {

        let floorCount = $('.floor-block').length;

        let floorHtml = `
            <div class="floor-block border rounded p-3 mb-3">
                <div class="row g-4 align-items-end">

                    <div class="col-lg-4">
                        <label>Floor Name </label>
                        <input class="form-control" type="text" name="floors[${floorCount}][name]" placeholder="Floor Name">
                    </div>

                    <div class="col-lg-3">
                        <label>Seat No From </label>
                        <input class="form-control" type="number" name="floors[${floorCount}][from]" placeholder="1">
                    </div>

                    <div class="col-lg-3">
                        <label>Seat No To </label>
                        <input class="form-control" type="number" name="floors[${floorCount}][to]" placeholder="50">
                    </div>

                    <div class="col-lg-2">
                        <button type="button" class="btn btn-danger btn-sm remove-floor">
                            Remove
                        </button>
                    </div>

                </div>
            </div>
        `;

        $('#floorWrapper').append(floorHtml);
        updateFloorIndexes();
    });

    // REMOVE FLOOR (delegated)
    $(document).on('click', '.remove-floor', function () {
        $(this).closest('.floor-block').remove();
        updateFloorIndexes();
    });

    // UPDATE INPUT INDEXES
    function updateFloorIndexes() {

        let floors = $('.floor-block');

        floors.each(function (index) {

            $(this).find('input').each(function () {
                let name = $(this).attr('name');
                name = name.replace(/\[\d+\]/, '[' + index + ']');
                $(this).attr('name', name);
            });
        });

        // Hide remove button if only one floor
        if (floors.length === 1) {
            $('.remove-floor').addClass('d-none');
        } else {
            $('.remove-floor').removeClass('d-none');
        }
    }

    // INITIAL
    updateFloorIndexes();

});

</script>





{{-- @include('library.script') --}}
@endsection
