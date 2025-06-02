@extends('layouts.admin')
@section('content')


<div class="card mb-4">
    <div class="row">
        @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        <h4 class="mb-4">Add Features</h4>
        <form action="{{ isset($feature) ? route('feature.storeFeature', $feature->id) : route('feature.storeFeature') }}" method="POST" enctype="multipart/form-data">            
            @csrf

            <div class="form-group">
                <label for="category_name">Feature</label>
                <input 
                    type="text" 
                    name="name" 
                    id="name" 
                    class="form-control @error('name') is-invalid @enderror" 
                    value="{{ $feature->name ?? old('name') }}">
                @error('name')
                <span class="invalid-feedback" role="alert">
                    <strong>{{ $message }}</strong>
                </span>
                @enderror
            </div>
            
            <div class="form-group">
                <label for="image">Image</label>
                <input type="file" name="image" id="image" class="form-control">
            </div>
            
            
            <div class="col-lg-3 mt-4">
                <button type="submit" class="btn btn-primary button">{{ isset($feature) ? 'Update ' : 'Add ' }}</button>
            </div>
        </form>
    </div>
   
</div>
<div class="row">
    <div class="col-lg-12">
        <div class="table-responsive">
            <table class="table text-center datatable dataTable" id="datatable">
                <thead>
                    <tr>
                        <th>S.No.</th>
                        <th>Feature</th>
                        <th>Image Icon</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($features as $index => $feature)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $feature->name }}</td>
                        <td>
                            <img src="{{ asset('public/'.$feature->image) }}" alt="Image" width="50">
                        </td>                        
                        <td>
                            <ul class="actionalbls">
                               
                                <li><a href="{{ route('feature.edit', $feature->id) }}"  data-bs-title="View feature edit" ><i class="fas fa-edit"></i></a>
                                </li>
                                {{-- <li>
                                    <form action="{{ route('feature.destroy', $feature->id) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                                    </form>
                                </li> --}}
                            </ul>
                            
                       
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@include('master.script')

@endsection