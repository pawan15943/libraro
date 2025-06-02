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
                
                </div>
                <div class="col-lg-4 mt-4">
                    <button type="submit" value="Login" placeholder="Email Id" class="btn btn-primary button">Update and Next</button>
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