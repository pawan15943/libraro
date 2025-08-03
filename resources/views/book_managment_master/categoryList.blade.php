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



<!-- Masters -->
<div class="heading-list justify-content-end">
    <a href="{{ route('book.category.create') }}" class="btn btn-primary export m-0">
        <i class="fa-solid fa-plus "></i> Add Category
    </a>
</div>

<div class="card p-0 mb-4 mt-3">
    <div class="table-responsive">
        <table class="table text-center datatable" id="datatable">
            <thead>
                <tr>
                    <th>S.No.</th>
                    <th class="w-50">Category Name</th>
                    <th class="w-50">Sub Category Name</th>
                    <th class="w-25">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $key => $value)
                <tr>
                    <td>{{ $key+1 }}</td>
                    <td>{{ $value->category_name  }}</td>
                    <td>{{ $value->sub_category_name  }}</td>


                    <td>
                        <ul class="actionalbls">
                            <li><a href="javascript:void(0)" class="active-deactive" data-id="{{ $value->id }}" data-table="BooksCategory" title="Active/Deactive">
                                    @if($value->deleted_at)
                                    <i class="fas fa-ban"></i>
                                    @else
                                    <i class="fa fa-check"></i>
                                    @endif</a></li>
                            <li><a href="{{route('plan.create',$value->id)}}" title="Edit "><i class="fas fa-edit"></i></a></li>
                            <li><a href="javascript:void(0)" class="delete-btn" data-id="{{ $value->id }}" data-route="{{ route('master.delete', $value->id) }}" data-table="BooksCategory" title="Delete"><i class="fa fa-trash"></i></a></li>
                          
                        </ul>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>


<!-- /.content -->
@include('master.script')
@endsection