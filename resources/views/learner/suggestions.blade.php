@extends('layouts.learner')
@section('content')


<div class="card">
    <form action="{{ route('learner.suggestion.store') }}" class="validateForm" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="row g-4">

            <h4>Add Suggestions</h4>
            <div class="col-lg-12">
                <input type="text" name="title" class="form-control char-only @error('title') is-invalid @enderror" placeholder="Enter Title" value="{{ old('title') }}">
                @error('title')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="col-lg-12">
                <textarea name="description" class="form-control @error('description') is-invalid @enderror" placeholder="Enter your description">{{ old('description') }}</textarea>
                @error('description')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="col-lg-12">
                <input type="file" name="attachment" class="form-control no-validate @error('attachment') is-invalid @enderror">
                <div class="screenshot"></div>
                @error('attachment')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>

            <div class="col-lg-3 mt-4">
                <button type="submit" class="btn btn-primary button">Submit</button>
            </div>


        </div>
    </form>
</div>
<h4 class="py-4">Suggestion List</h4>
<div class="row mb-4">
    <div class="col-lg-12">
        <div class="table-responsive">
            <table class="table text-center datatable dataTable" id="datatable">
                <thead>
                    <tr>
                        <th>S.No.</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Library Status</th>
                    </tr>
                </thead>
                <tbody>
                    @if(isset($data))
                    @foreach($data as $index => $value)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $value->title }}</td>
                        <td>{{$value->description}}</td>
                        <td>
                            @if($value->status==1)
                            <span class="text-success">Resolved</span>
                            @elseif($value->status==2)
                            <span class="text-secondary">Clarification</span>
                            @else
                            <span class="text-danger">Pending</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                    @else
                    <tr>
                        <td colspan="4">No Data Found</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        let table = new DataTable('#datatable', {

        });

    });
</script>
@endsection