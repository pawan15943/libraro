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
                        <th>Name</th>
                        <th>Mobile</th>
                        <th>Plan type</th>
                        <th>Date & Time</th>
                        <th>Enquiry</th>
                    </tr>
                </thead>

                <tbody>
                  @foreach($datas as $key => $value)
                    <tr>
                        <td>{{ $key+1}}</td>
                        <td>{{$value->name}}</td>
                        <td>{{$value->mobile}}</td>
                        <td>{{$value->planType->name}}</td>
                        <td>{{$value->created_at}}</td>
                        <td>{{$value->enquiry}}</td>
                        
                    </tr>
                    
                  @endforeach
                   
                   
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection