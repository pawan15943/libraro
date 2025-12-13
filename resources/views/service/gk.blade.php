@extends('layouts.library')

@section('content')
<div class="card mb-4">
    <h4 class="mb-4">{{ isset($data) ? 'Edit ' : 'Create ' }}</h4>
    <form>
    {{-- <form action="{{ isset($data) ? route('day-dashboard.store', $data->id) : route('day-dashboard.store') }}" method="POST" enctype="multipart/form-data"> --}}
        @csrf
           <!-- Type -->
        <div class="col-lg-4 mb-3">
            <label>Type</label>
            <select name="type" class="form-control">
                <option value="">Select</option>
                @foreach(['gk'=>'GK Question','daily_news'=>'Daily News','english_word'=>'English Word','quotes'=>'Quotes','reasoning'=>'Reasoning'] as $k=>$v)
                    <option value="{{ $k }}" {{ old('type',$data->type ?? '')==$k?'selected':'' }}>
                        {{ $v }}
                    </option>
                @endforeach
            </select>
        </div>

        <!-- Date -->
        <div class="col-lg-4 mb-3">
            <label>Date</label>
            <input type="date" name="content_date" class="form-control"
                value="{{ old('content_date',$data->content_date ?? '') }}">
        </div>
     
        <div class="col-lg-12 mb-4">
            <label for="page_content">Content</label>
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