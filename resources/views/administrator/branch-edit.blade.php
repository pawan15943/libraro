@extends('layouts.admin')

@section('content')
<link rel="stylesheet" href="{{ asset('public/css/library-style.css') }}">

<div class="actions mb-4">
    <div class="upper-box">
        <div class="d-flex justify-content-between align-items-center">
            <h4 class="mb-3">Edit Branch — {{ $branch->display_name ?? $branch->name }}</h4>
            <a href="javascript:void(0);" class="go-back" onclick="window.history.back();">Go Back <i class="fa-solid fa-backward pl-2"></i></a>
        </div>
    </div>
</div>

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
@if($errors->any())
<div class="alert alert-danger">
    <ul class="m-0">
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<form method="POST" action="{{ route('library.branch.update', $branch->id) }}" enctype="multipart/form-data" id="branchUpdate">
    @csrf
    @method('PUT')

    <div class="row mb-4 g-4">
    <div class="col-lg-8">

    <div class="card p-3 mb-4">
        <h5>Branch Details</h5>
        <div class="row g-4">
            <div class="col-lg-4">
                <label>Branch Name <span>*</span></label>
                <input type="text" class="form-control" name="name" value="{{ old('name', $branch->name) }}" required>
            </div>
            <div class="col-lg-4">
                <label>Display Name <span>*</span></label>
                <input type="text" class="form-control" name="display_name" value="{{ old('display_name', $branch->display_name) }}" required>
            </div>
            <div class="col-lg-4">
                <label>Founder Day</label>
                <input type="date" class="form-control" name="founder_day" value="{{ old('founder_day', $branch->founder_day) }}">
            </div>
            <div class="col-lg-4">
                <label>Email <span>*</span></label>
                <input type="email" class="form-control" name="email" value="{{ old('email', $branch->email) }}" required>
            </div>
            <div class="col-lg-4">
                <label>Mobile <span>*</span></label>
                <input type="text" maxlength="10" class="form-control" name="mobile" value="{{ old('mobile', $branch->mobile) }}" required>
            </div>
            <div class="col-lg-4">
                <label>UPI ID</label>
                <input type="text" class="form-control" name="upi_id" value="{{ old('upi_id', $branch->upi_id) }}">
            </div>
        </div>
    </div>

    <div class="card p-3 mb-4">
        <h5>Branch Profile</h5>
        <div class="row g-4">
            <div class="col-lg-12">
                <label>Working Days</label>
                <textarea class="form-control" name="working_days">{{ old('working_days', $branch->working_days) }}</textarea>
            </div>
            <div class="col-lg-12">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="4">{{ old('description', $branch->description) }}</textarea>
            </div>
            <div class="col-lg-4">
                <label>Library Category</label>
                <select class="form-select" name="library_category">
                    <option value="">Select Category</option>
                    <option value="Public" {{ old('library_category', $branch->library_category) == 'Public' ? 'selected' : '' }}>Public</option>
                    <option value="Private" {{ old('library_category', $branch->library_category) == 'Private' ? 'selected' : '' }}>Private</option>
                </select>
            </div>
            <div class="col-lg-4">
                <label>Billing Fix Date</label>
                <input type="number" min="1" max="31" class="form-control" name="fixed_billing_date" value="{{ old('fixed_billing_date', $branch->fixed_billing_date) }}">
            </div>
        </div>
    </div>

    <div class="card p-3 mb-4">
        <h5>Address & Location</h5>
        <div class="row g-4">
            <div class="col-lg-12">
                <label>Library Address <span>*</span></label>
                <textarea class="form-control" name="library_address" rows="3" required>{{ old('library_address', $branch->library_address) }}</textarea>
            </div>
            <div class="col-lg-4">
                <label>State <span>*</span></label>
                <select class="form-select" name="state_id" id="state_id" required>
                    <option value="">Select State</option>
                    @foreach($states as $state)
                    <option value="{{ $state->id }}" {{ old('state_id', $branch->state_id) == $state->id ? 'selected' : '' }}>{{ $state->state_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-4">
                <label>City <span>*</span></label>
                <select class="form-select" name="city_id" id="city_id" required>
                    <option value="">Select City</option>
                    @foreach($cities as $city)
                    <option value="{{ $city->id }}" {{ old('city_id', $branch->city_id) == $city->id ? 'selected' : '' }}>{{ $city->city_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-lg-4">
                <label>ZIP Code <span>*</span></label>
                <input type="text" maxlength="6" class="form-control" name="library_zip" value="{{ old('library_zip', $branch->library_zip) }}" required>
            </div>
            <div class="col-lg-12">
                <label>Google Map Embed URL</label>
                <textarea class="form-control" name="google_map" rows="3">{{ old('google_map', $branch->google_map) }}</textarea>
            </div>
            <div class="col-lg-6">
                <label>Longitude</label>
                <input type="text" class="form-control" name="longitude" value="{{ old('longitude', $branch->longitude) }}">
            </div>
            <div class="col-lg-6">
                <label>Latitude</label>
                <input type="text" class="form-control" name="latitude" value="{{ old('latitude', $branch->latitude) }}">
            </div>
        </div>
    </div>

    <div class="card p-3 mb-4">
        <h5>Branch Master Settings</h5>
        <div class="row g-4">
            <div class="col-lg-4">
                <label>Locker Amount</label>
                <input type="number" min="0" class="form-control" name="locker_amount" value="{{ old('locker_amount', $branch->locker_amount) }}">
            </div>
            <div class="col-lg-4">
                <label>Extend Days</label>
                <input type="number" min="0" max="30" class="form-control" name="extend_days" value="{{ old('extend_days', $branch->extend_days) }}">
            </div>
            <div class="col-lg-4">
                <label>Token Money</label>
                <input type="number" min="0" class="form-control" name="token_money" value="{{ old('token_money', $branch->token_money) }}">
            </div>
        </div>
    </div>

    <div class="card p-3 mb-4">
        <h5>Library Features</h5>
        <div class="row g-4">
            <div class="col-lg-12">
                @php $selectedFeatures = old('features', $branch->features ?? []); @endphp
                @foreach($features as $feature)
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="features[]" value="{{ $feature->id }}" id="feature_{{ $feature->id }}" {{ in_array($feature->id, $selectedFeatures ?? []) ? 'checked' : '' }}>
                    <label class="form-check-label" for="feature_{{ $feature->id }}">{{ $feature->name }}</label>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="card p-3 mb-4">
        <h5>Library Gallery</h5>
        <div class="row g-4">
            @php $existingImages = $branch->library_images ?? []; @endphp
            @if(!empty($existingImages))
            <div class="col-lg-12">
                <label>Existing Images (check to remove)</label>
                <div class="d-flex flex-wrap gap-3">
                    @foreach($existingImages as $image)
                    <div class="text-center">
                        <img src="{{ asset('public/' . $image) }}" width="100" class="d-block mb-1 border">
                        <label class="form-check-label small">
                            <input type="checkbox" name="deleted_images[]" value="{{ $image }}"> Remove
                        </label>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
            <div class="col-lg-12">
                <label>Upload New Images (max 4 total)</label>
                <input type="file" class="form-control" name="library_images[]" accept="image/jpeg,image/png,image/webp" multiple>
            </div>
        </div>
    </div>

    </div>
    <!-- /col-lg-8 -->

    <!-- Sidebar for Library Logo and Submit -->
    <div class="col-lg-4">
        <div class="card stick p-3">
            <h5 class="mb-4">Library Logo</h5>
            <div class="row g-4">
                <div class="col-lg-12">
                    <div class="preview" id="preview">
                        @if($branch->library_logo)
                        <img src="{{ asset('public/' . $branch->library_logo) }}" class="img-thumbnail rounded shadow preview" style="max-width: 250px;">
                        @else
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
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </div>
        </div>
    </div>

    </div>
    <!-- /row -->
</form>

<script>
$(document).ready(function () {
    const maxLogoSize = 2 * 1024 * 1024; // 2 MB

    $('#fileInput').on('change', function (event) {
        let file = event.target.files[0];
        let validTypes = ["image/jpeg", "image/png", "image/webp"];
        let statusMessage = $('#statusMessage');
        let preview = $('#preview');
        let progressBar = $('#progressBar');

        statusMessage.text('').removeClass('success error');
        $('#logoUploadError').html('');
        progressBar.width('0');

        if (!file) return;

        if (!validTypes.includes(file.type)) {
            statusMessage.text('Invalid file format. Only JPG, PNG, JPEG, WEBP allowed.').addClass('error');
            return;
        }
        if (file.size > maxLogoSize) {
            $('#logoUploadError').html('Image size should not exceed 2 MB.');
            return;
        }

        let reader = new FileReader();
        reader.onload = function (e) {
            preview.html('<img src="' + e.target.result + '" alt="Preview" class="preview">');

            let progress = 0;
            let interval = setInterval(function () {
                progress += 20;
                progressBar.width(progress + '%');
                if (progress >= 100) {
                    clearInterval(interval);
                    statusMessage.text('Upload Successful!').addClass('success');
                }
            }, 200);
        };
        reader.readAsDataURL(file);
    });

    $('#branchUpdate').on('submit', function () {
        const fileInput = $('#fileInput')[0];
        if (fileInput.files.length > 0 && fileInput.files[0].size > maxLogoSize) {
            $('#logoUploadError').html('Image size should not exceed 2 MB.');
            return false;
        }
    });
});
</script>

<script>
document.getElementById('state_id').addEventListener('change', function () {
    const stateId = this.value;
    const citySelect = document.getElementById('city_id');
    citySelect.innerHTML = '<option value="">Loading...</option>';

    fetch('{{ route("cityGetStateWise") }}?state_id=' + encodeURIComponent(stateId), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function (res) { return res.json(); })
    .then(function (cities) {
        citySelect.innerHTML = '<option value="">Select City</option>';
        Object.keys(cities).forEach(function (id) {
            const opt = document.createElement('option');
            opt.value = id;
            opt.textContent = cities[id];
            citySelect.appendChild(opt);
        });
    });
});
</script>
@endsection
