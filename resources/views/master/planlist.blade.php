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
    <a href="{{ route('plan.create') }}" class="btn btn-primary export m-0">
        <i class="fa-solid fa-plus "></i> Add Plan
    </a>
</div>

<div class="card p-0 mb-4 mt-3">
    <div class="table-responsive">
        <table class="table text-center datatable" id="datatable">
            <thead>
                <tr>
                    <th>S.No.</th>
                    <th class="w-50">Plan Name</th>
                    <th class="w-25">Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $key => $value)
                <tr>
                    <td>{{ $key+1 }}</td>
                    <td>{{ $value->name }}</td>


                    <td>
                        <ul class="actionalbls">
                            <li><a href="javascript:void(0)" class="active-deactive" data-id="{{ $value->id }}" data-table="Plan" title="Active/Deactive">
                                    @if($value->deleted_at)
                                    <i class="fas fa-ban"></i>
                                    @else
                                    <i class="fa fa-check"></i>
                                    @endif</a></li>
                            <li><a href="{{route('plan.create',$value->id)}}" title="Edit "><i class="fas fa-edit"></i></a></li>
                            <li><a href="javascript:void(0)" class="delete-btn" data-id="{{ $value->id }}" data-route="{{ route('master.delete', $value->id) }}" data-table="Plan" title="Delete"><i class="fa fa-trash"></i></a></li>
                          
                        </ul>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>





<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.concat.min.js"></script>

<script>
    (function($) {
        $(window).on("load", function() {
            $(".contents").mCustomScrollbar({
                theme: "dark",
                scrollInertia: 300,
                axis: "y",
                autoHideScrollbar: false, // Keeps
            });
        });
    })(jQuery);
</script>


<!-- /.content -->
@include('master.script')
@endsection