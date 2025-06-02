@extends('layouts.library')

@section('title', 'Admin Dashboard')

@section('content')
@if (session('error'))
<div class="alert alert-danger">
    {{ session('error') }}
</div>
@endif


@if($is_feedback)
@if (session('success'))
<div class="alert alert-success">
    {{ session('success') }}
</div>
@else
<div class="alert alert-success">
    {{ "Your Feedback already Submmitted." }}
</div>
@endif
@else   

<div>
<!-- Content -->
<form action="{{ route('library.feedback.store') }}" class="validateForm" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card">
                <div class="row g-4">
                    
                    <div class="col-lg-6">
                        <label for="">Feedback Type <span>*</span></label>
                        <select name="feedback_type" class="form-select @error('feedback_type') is-invalid @enderror">
                            <option value="">Select</option>
                            <option value="1">Product</option>
                            <option value="2">Service</option>
                        </select>
                        @error('feedback_type')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>

                    <div class="col-lg-6">
                        <label for="">Rating <span>*</span></label>
                        <select name="rating" class="form-select @error('rating') is-invalid @enderror">
                            <option value="">Select</option>
                            <option value="1">1 Star</option>
                            <option value="2">2 Stars</option>
                            <option value="3">3 Stars</option>
                            <option value="4">4 Stars</option>
                            <option value="5">5 Stars</option>
                        </select>
                        @error('rating')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>

                    <div class="col-lg-12">
                        <label for="">Feedback Description <span>*</span></label>
                        <textarea name="description" class="form-control @error('description') is-invalid @enderror" placeholder="Feedback Description" style="height:100px !important;"></textarea>
                        @error('description')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>

                    <div class="col-lg-6">
                        <label for="">Attachment (If Needed)</label>
                        <input type="file" name="attachment" class="form-control @error('attachment') is-invalid @enderror no-validate">
                        @error('attachment')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>

                    <div class="col-lg-6">
                        <label for="">Would You Recommend Us? <span>*</span></label>
                        <select name="recommend" class="form-select @error('recommend') is-invalid @enderror">
                            <option value="">Select</option>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                        @error('recommend')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>

                    <div class="col-lg-4">
                        <input type="submit" value="Submit Feedback" class="btn btn-primary button">
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

</div>
@endif
@include('library.script')
@endsection