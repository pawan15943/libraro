@extends('layouts.learner')
@section('content')



<div class="row">
    <div class="col-lg-4">
        <div class="address-box">
            <i class="fa fa-building fa-3x"></i>
            <h4>Address</h4>
            <p>{{$library_name->library_address}}</p>        
        </div>
    </div>
    <div class="col-lg-4">
        <div class="address-box">
            <i class="fa fa-envelope fa-3x"></i>
            <h4>Email</h4>
            <p><a href="mailto:{{$library_name->email}}">{{$library_name->email}}</a></p>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="address-box">
            <i class="fa fa-phone-volume fa-3x"></i>
            <h4>Phone</h4>
            <p><a href="tel:+91-{{$library_name->library_mobile}}">+91-{{$library_name->library_mobile}}, +91-{{$library_name->library_owner_contact}}</a></p>
        </div>
    </div>
</div>






@endsection