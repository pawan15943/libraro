@extends('layouts.library')
@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}" />
<!-- Main content -->


<div id="success-message" class="alert alert-success" style="display:none;"></div>
<div id="error-message" class="alert alert-danger" style="display:none;"></div>
@if($errors->any())
<div class="alert alert-danger">
    <ul>
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif


<div class="card">

    <!-- Add Library User Form -->
   <form id="categoryForm" class="">
    @csrf
    <input type="hidden" name="id" value="{{ $category->id ?? '' }}">
    {{-- <input type="hidden" name="library_id" value="{{getLibraryId()}}"> --}}
    <input type="hidden" name="databasemodel" value="BooksCategory">
    <input type="hidden" name="redirect" value="{{ route('book.category.index') }}">

    <div class="row g-4">
        <div class="col-lg-6">
            <label>Category Name <span>*</span></label>
            <input type="text" name="category_name" class="form-control @error('category_name') is-invalid @enderror" value="{{ old('category_name', $category->category_name ?? '') }}">
            @error('category_name')
            <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
            </span>
            @enderror
        </div>
        <div class="col-lg-6">
            <label>Sub Category Name</label>
            <input type="text" name="sub_category_name" class="form-control @error('sub_category_name') is-invalid @enderror" value="{{ old('sub_category_name', $category->sub_category_name ?? '') }}">
            @error('sub_category_name')
            <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
            </span>
            @enderror
        </div>
        <div class="col-lg-3">
                <button type="submit" class="btn btn-primary button" ><i class="fa fa-plus"></i>
                    Save
                </button>
        </div>
    </div>
</form>

</div>

<!-- /.content -->
@include('master.script')
@endsection
