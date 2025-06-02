@extends('layouts.library')

@section('title', 'Admin Dashboard')

@section('content')
@if (session('error'))
<div class="alert alert-danger">
    {{ session('error') }}
</div>
@endif
@if (session('success'))
<div class="alert alert-success">
    {{ session('success') }}
</div>
@endif
<div class="row justify-content-center mb-4">
    <div class="col-lg-12">
        <div class="table-responsive">
            <table class="table text-center datatable" id="datatable">
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Learner Name</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Response</th>
                        
                    </tr>
                </thead>

                <tbody>
                  
                    @foreach($data as $key => $value)
                  
                    <tr>
                        <td>{{$key+1}}</td>
                        <td>{{$value->learner_name}}</td>
                       
                        <td> {{$value->title}}</td>
                        <td> {{$value->description}}</td>
                        <td>@if($value->status==1)
                            <span class="text-success">Resolved</span>
                            @else
                            <span class="text-danger">Pending</span>
                            @endif
                        </td>
                        <td>{{$value->response ?? 'NA'}}</td>
                      
                        
                        
                    </tr>
                    @endforeach
                   
                </tbody>
            </table>
        </div>
    </div>
</div>
@include('library.script')
@endsection