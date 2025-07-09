@extends('layouts.admin')
@section('content')

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger">{{ session('error') }}</div>
@endif

<h2>Upload Video</h2>

    @if(session('success'))
        <p style="color: green;">{{ session('success') }}</p>
    @endif
<form action="{{ route('videos.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
   
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <label for="">Title<span>*</span></label>
                         <input type="text" name="video_titel" class="form-control @error('video_titel') is-invalid @enderror">
                       
                        @error('video_titel')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                    <div class="col-lg-6">
                        <label for="">YouTube Link<span>*</span></label>
                         <input type="url" name="youtube_link" class="form-control @error('youtube_link') is-invalid @enderror">
                       
                        @error('youtube_link')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                     <div class="col-lg-6">
                        <label for="">Upload Video<span>*</span></label>
                         <input  type="file" name="video" class="form-control @error('video') is-invalid @enderror">
                       
                        @error('video')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $message }}</strong>
                        </span>
                        @enderror
                    </div>
                </div>
                
                <div class="row justify-content-center mt-3">
                    <div class="col-lg-2">
    
                        <button type="submit" class="btn btn-primary btn-block button" > Save</button>
    
                    </div>
                </div>

               
            </div>
        </div>
    </div>

</form>
    
    <h3>All Videos</h3>
    @foreach($videos as $video)
        <div style="margin-bottom: 20px;">
            <strong>{{ $video->title }}</strong><br>

            @if($video->youtube_link)
                <a href="{{ $video->youtube_link }}" target="_blank">YouTube Link</a><br>
            @endif

            @if($video->video_path)
                <video width="320" height="240" controls>
                    <source src="{{ asset('storage/' . $video->video_path) }}" type="video/mp4">
                    Your browser does not support the video tag.
                </video><br>
            @endif

            {{-- <form action="{{ route('videos.destroy', $video) }}" method="POST" style="display:inline;">
                @csrf
                @method('DELETE')
                <button type="submit" onclick="return confirm('Delete this video?')">Delete</button>
            </form> --}}
        </div>
    @endforeach


@endsection