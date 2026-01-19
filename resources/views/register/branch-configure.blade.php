@extends('layouts.library')

@section('title', 'Admin Dashboard')

@section('content')

<form action="{{route('branch.configure')}}" method="POST" enctype="multipart/form-data" id="branchUpdate">
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
                        <div class="input-control">
                            <label for="name"> Branch Name <span>*</span></label>
                            <input type="text" id="name" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $branch->name ?? '') }}" placeholder="Enter Branch name" {{ isset($branch) ? 'readonly' : '' }}>
                            @error('name')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        <small class="text-information">Note : For internal use only. You can’t change this later.</small>
                    </div>


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

                    <div class="col-lg-12">
                        <div class="input-control">
                            <label for="mobile">UPI ID(for payment receive)</label>
                            <input type="text" class="form-control  @error('upi_id') is-invalid @enderror" name="upi_id" value="{{ old('upi_id', $branch->upi_id ?? '') }}">
                            @error('upi_id')
                            <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
                            @enderror
                        </div>
                        <small class="text-information">Note : Add your UPI ID to enable online and QR-code bookings from your listing page.</small>
                    </div>

                </div>
            </div>

            <div class="card mt-5">
                <h4 class="mb-4">Branch Master</h4>
                <div class="row g-4">

                    <div class="col-lg-6">
                        <label for="">Add Seats to Library Branch <span>*</span></label>
                        <input type="text" name="seats" class="form-control digit-only @error('seats') is-invalid @enderror" id="" placeholder="Enter Seats No." value="{{ old('seats') }}" maxlength="4" >
                        @error('seats')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <div class="col-lg-6">
                        <label for="">Operating Hours <span>*</span></label>
                        <select class="form-select @error('hour') is-invalid @enderror" name="hour" id="hour" >
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
                        <div class="input-control">
                            <label for="">Locker Amount <span>*</span></label>
                            <input type="text" name="locker_amount" class="form-control digit-only @error('locker_amount') is-invalid @enderror" id="" placeholder="Enter Amt." value="{{old('locker_amount')}}" maxlength="4" >
                            @error('locker_amount')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        <small class="text-information">Note : Enter 0 if you don’t offer lockers.</small>
                    </div>
                    <div class="col-lg-6">
                        <div class="input-control">
                            <label for="">Extend Days <span>*</span></label>
                            <input type="text" class="form-control digit-only @error('extend_days') is-invalid @enderror no-validate" name="extend_days" placeholder="Enter Days" value="{{old('extend_days')}}" maxlength="4" >
                            @error('extend_days')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        <small class="text-information">Note : Enter days allowed after plan expiry.</small>
                    </div>

                    <div class="col-lg-6">
                        <label for="">Token Money(optional) </label>
                        <input type="text" class="form-control digit-only @error('token_money') is-invalid @enderror no-validate" name="token_money" placeholder="Enter Days" value="{{old('token_money')}}" maxlength="4" >
                        @error('token_money')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                </div>
            </div>


            @if($branchCount==0)
            <div class="card mt-5" style="overflow: visible !important;">
                <h4 class="mb-4">Plan Info</h4>
                @php
                $oldPlans = old('plans', []);
                @endphp
                <div class="row g-4">

                    <!-- Plans -->
                    <div class="col-lg-6">
                        <label>Choose your Plans <span class="text-danger">*</span></label>

                        <select class="form-select h-auto @error('plans') is-invalid @enderror" id="duration" name="plans[]" multiple>
                            <option value="">Choose your plans</option>
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
                        <label>Choose only for 1 Month </label>
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

                </div>
            </div>
            @endif

            <div class="card mt-5" style="overflow: visible !important;">
                <h4 class="mb-4">Add Floors</h4>
                <div class="row">
                    <!-- Floors Container -->
                    <div class="col-lg-12" id="floorWrapper">

                        <div class="floor-block">
                            <div class="row g-4 align-items-end">

                                <div class="col-lg-5">
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

                                <div class="col-lg-1">
                                    <button type="button" class="btn btn-primary btn-sm mt-2 add-delete add" id="addFloor">
                                        <i class="fa fa-plus"></i>
                                    </button>
                                </div>

                            </div>
                        </div>

                    </div>
                </div>
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
                            @endif
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
                        <div id="logoUploadError" class="text-danger mt-2"></div>
                        <!-- <div class="progress">
                            <div class="progress-bar" id="progressBar"></div>
                        </div> -->
                    </div>
                    <div class="col-lg-12">
                        <button type="submit" class="btn btn-primary button">
                            {{ isset($branch) ? 'Update' : 'Save & Next' }}
                        </button>
                    </div>


                </div>
            </div>

        </div>

    </div>
</form>

<script>
    $(document).ready(function() {

        function loadCities(stateId, selectedCity = null) {

            if (!stateId || !cityChoices) return;

            $.ajax({
                url: "{{ route('cityGetStateWise') }}",
                type: "GET",
                data: {
                    state_id: stateId
                },
                dataType: "json",
                success: function(cities) {

                    // Convert response to Choices format
                    let choicesData = [];

                    $.each(cities, function(id, name) {
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
        $('#state_id').on('change', function() {
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
        let existingImages = @json( json_decode($branch -> library_images ?? '[]'));

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
                        position: 'relative',
                        display: 'inline-block',
                        margin: '5px'
                    });

                    // Create image
                    const img = $('<img>').attr('src', e.target.result).css({
                        width: '100px',
                        height: '100px',
                        objectFit: 'cover',
                        border: '1px solid #ccc',
                        borderRadius: '5px'
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
    $(document).ready(function() {

        // ADD FLOOR
        $('#addFloor').on('click', function() {

            let floorCount = $('.floor-block').length;

            let floorHtml = `
            <div class="floor-block mt-3">
                <div class="row g-4 align-items-end">

                    <div class="col-lg-5">
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

                    <div class="col-lg-1">
                        <button type="button" class="btn btn-danger btn-sm remove-floor add-delete">
                           <i class="fa fa-minus"></i>
                        </button>
                    </div>

                </div>
            </div>
        `;

            $('#floorWrapper').append(floorHtml);
            updateFloorIndexes();
        });

        // REMOVE FLOOR (delegated)
        $(document).on('click', '.remove-floor', function() {
            $(this).closest('.floor-block').remove();
            updateFloorIndexes();
        });

        // UPDATE INPUT INDEXES
        function updateFloorIndexes() {

            let floors = $('.floor-block');

            floors.each(function(index) {

                $(this).find('input').each(function() {
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

<script>
    $('#branchUpdate').on('submit', function(e) {
        e.preventDefault();

        // Clear previous errors
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();
        $('.form-error').remove();

        let form = this;
        let formData = new FormData(form);

        $.ajax({
            url: $(form).attr('action'),
            method: $(form).find('input[name="_method"]').val() || 'POST',
            data: formData,
            processData: false,
            contentType: false,

            beforeSend: function() {
                $('button[type="submit"]').prop('disabled', true);
            },

            success: function(res) {
                if (res.status === true && res.redirect) {
                    window.location.href = res.redirect;
                }
            },

            error: function(xhr) {

                $('button[type="submit"]').prop('disabled', false);

                /* =========================
                VALIDATION ERRORS (422)
                ========================= */
                if (xhr.status === 422) {

                    let errors = xhr.responseJSON.errors;

                    $.each(errors, function(key, messages) {

                        // Convert Laravel dot notation to HTML name
                        // floors.0.from -> floors[0][from]
                        let fieldName = key
                            .replace(/\.(\d+)\./g, '[$1][')
                            .replace(/\./g, ']') + ']';

                        // Handle array fields like plans[]
                        // let $field = $('[name="' + fieldName + '"], [name="' + fieldName.replace(/\]$/, '') + '"]');
                        let $field;
                        let $errorTarget;

                        // 🔹 Special handling for multi-select plans[]
                        if (key === 'plans') {
                            $field = $('[name="plans[]"]');
                            $errorTarget = $field.closest('.col-lg-6'); // show error OUTSIDE select
                        } else {
                            // floors.0.from → floors[0][from]
                            let fieldName = key
                                .replace(/\.(\d+)\./g, '[$1][')
                                .replace(/\./g, ']');

                            $field = $('[name="' + fieldName + '"]');
                            $errorTarget = $field.parent();
                        }

                        if ($field.length) {

                            // Remove old error
                            $field.removeClass('is-invalid');
                            $errorTarget.find('.invalid-feedback').remove();

                            // Mark invalid
                            $field.addClass('is-invalid');

                            // Append error BELOW field (outside input)
                            $errorTarget.append(
                                `<span class="invalid-feedback d-block">
                                <strong>${messages[0]}</strong>
                            </span>`
                            );
                        }

                    });
                }

                /* =========================
                BUSINESS / LOGIC ERRORS
                ========================= */
                if (xhr.status === 400) {
                    $('#branchUpdate').prepend(
                        `<div class="alert alert-danger form-error">
                        ${xhr.responseJSON.message}
                    </div>`
                    );
                }
            },

            complete: function() {
                $('button[type="submit"]').prop('disabled', false);
            }
        });
    });
</script>


@endsection