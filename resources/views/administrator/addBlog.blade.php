
@extends('layouts.admin')
@section('content')

<div class="card mb-4">
    <h4 class="mb-4">{{ isset($data) ? 'Edit Blog' : 'Create Blog' }}</h4>
    <form action="{{ isset($data) ? route('blog.store', $data->id) : route('blog.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
      
        <div class="col-lg-12 mb-4">
            <label for="page_title">Page Title</label>
            <input 
                type="text" 
                id="page_title" 
                name="page_title" 
                class="form-control @error('page_title') is-invalid @enderror" 
                value="{{ old('page_title', $page->page_title ?? '') }}"
                onkeyup="generateSlug()" >
                <small>Blog URL : </small>
            @error('page_title')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>
    
        <div class="col-lg-12 mb-4">
            <label for="page_slug">Page Slug</label>
            <input  id="page_slug" name="page_slug" class="form-control"value="{{ old('page_slug', $page->page_slug ?? '') }}" >
        </div>
    
        <div class="col-lg-12 mb-4">
            <label for="page_content">Page Content</label>
            <textarea 
                id="editor" 
                name="page_content" 
                class="form-control @error('page_content') is-invalid @enderror" 
                >{{ old('page_content', $data->page_content ?? '') }}</textarea>
            @error('page_content')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>
    
        <div class="col-lg-12 mb-4">
            <label for="meta_title">Meta Title</label>
            <input 
                type="text" 
                id="meta_title" 
                name="meta_title" 
                class="form-control @error('meta_title') is-invalid @enderror" 
                value="{{ old('meta_title', $data->meta_title ?? '') }}">
            @error('meta_title')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>
    
        <div  class="col-lg-12 mb-4">
            <label for="meta_description">Meta Description</label>
            <textarea 
                id="meta_description" 
                name="meta_description" 
                class="form-control @error('meta_description') is-invalid @enderror">{{ old('meta_description', $data->meta_description ?? '') }}</textarea>
            @error('meta_description')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>
    
        <div  class="col-lg-12 mb-4">
            <label for="meta_keyword">Meta Keyword</label>
            <textarea 
                id="meta_keyword" 
                name="meta_keyword" 
                class="form-control @error('meta_keyword') is-invalid @enderror">{{ old('meta_keyword', $data->meta_keyword ?? '') }}</textarea>
            @error('meta_keyword')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>
    
        <div class="col-lg-12 mb-4">
            <label for="meta_og">Meta OG</label>
            <textarea 
                id="meta_og" 
                name="meta_og" 
                class="form-control @error('meta_og') is-invalid @enderror">{{ old('meta_og', $data->meta_og ?? '') }}</textarea>
            @error('meta_og')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>

        <!-- Tags -->
        <div class="col-lg-12 mb-4">
            <label for="tags">Tags</label>
            <input 
                id="tags" 
                name="tags" 
                class="form-control @error('tags') is-invalid @enderror" 
                value="{{ old('tags', isset($data->tags) ? implode(',', json_decode($data->tags, true)) : '') }}"
                >
            @error('tags')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>
        <!-- categories -->
        <div class="col-lg-12 mb-4">
            <label for="categories">Categories</label>
            <input 
                id="categories" 
                name="categories" 
                class="form-control @error('categories') is-invalid @enderror" 
                value="{{ old('categories') }}">
            @error('categories')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>
        <div class="col-lg-12 mb-4">
            <label for="categories_id">Select Categories</label>
            <select name="categories_id[]" id="categories_id" class="form-control" multiple>
                @foreach($categories as $category)
                    <option 
                        value="{{ $category->id }}" 
                        >
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Header Image -->
        <div class="col-lg-12 mb-4">
            <label for="header_image">Header Image</label>
            <input 
                type="file" 
                id="header_image" 
                name="header_image" 
                class="form-control @error('header_image') is-invalid @enderror">
            @error('header_image')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
            @enderror
        </div>
        <div class="col-lg-3">
            <button type="submit" class="btn btn-primary button">
                {{ isset($data) ? 'Update' : 'Save' }}
            </button>
        </div>
       
    </form>
</div>

<!-- Include Tagify JS & CSS -->
<link href="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify"></script>


<script>
    // Initialize Tagify for Tags and Categories
    new Tagify(document.querySelector('#tags'));
    new Tagify(document.querySelector('#categories'));
</script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/classic/ckeditor.js"></script>
<script>
    ClassicEditor
    .create(document.querySelector('#editor'))
    .then(editor => {
        editor.editing.view.focus(); // Ensures typing starts immediately
    })
    .catch(error => {
        console.error(error);
    });

    
</script>
<script>
    $(document).ready(function() {
        $('#categories_id').select2({
            placeholder: "Select Categories",
            allowClear: true
        });
    });
</script>
<script>
    // Function to generate the slug based on page title
    function generateSlug() {
        var title = document.getElementById('page_title').value;
        var slug = title
            .toLowerCase() // Convert to lowercase
            .replace(/[^a-z0-9\s-]/g, '') // Remove special characters
            .replace(/\s+/g, '-') // Replace spaces with hyphens
            .replace(/-+/g, '-'); // Replace multiple hyphens with a single hyphen

        document.getElementById('page_slug').value = slug;
    }
</script>
@endsection
