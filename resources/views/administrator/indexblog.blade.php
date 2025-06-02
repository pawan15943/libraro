@extends('layouts.admin')
@section('content')

@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
@if(session('error'))
<div class="alert alert-danger">{{ session('error') }}</div>
@endif
<div class="col-lg-3 mb-4">
    <a href="{{ route('add-blog') }}" class="btn btn-primary button">Create Blog</a>
</div>
<div class="row">
   
    <div class="col-lg-12">
       
        <div class="table-responsive">
            <table class="table text-center datatable dataTable" id="datatable">
             
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Slug</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($blogs as $value)
                            <tr>
                                <td>{{ $value->id }}</td>
                                <td>{{ $value->page_title }}</td>
                                <td>{{ $value->page_slug }}</td>
                                <td>
                                    <ul class="actionalbls">
                                        <li>
                                            <a href="{{ route('blog.edit', $value->id) }}"><i class="fas fa-edit"></i></a>

                                        </li>
                                    </ul>
                                   
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
            </table>
        </div>
    </div>
</div>


@endsection