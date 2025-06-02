@extends('layouts.learner')

@section('title', 'Learner Dashboard')

@section('content')

<div class="dashboard learner">
    <!-- Welcome Message -->
    <div class="row">
        <div class="col-lg-12">
            <div class="greeting-container">
                <i id="greeting-icon" class="fas fa-sun greeting-icon"></i>
                <h2 id="greeting-message" class="typing-text">Good Morning! {{Auth::user()->name}}!</h2>
            </div>
        </div>
    </div>

    <!-- Library Info -->
    <div class="row g-4">
        <div class="col-lg-9">
            <div class="dashboard-Header">
                <img src="{{url('public/img/bg-library-welcome.png')}}" alt="library" class="img-fluid rounded">
                <h1>Welcome to <span>{{$library_name->library_name}}</span><br>
                    Let’s Make Your <span class="typing-text"> Library the Place to Be! 📚🌟</span></h1>
            </div>
        </div>

        <div class="col-lg-3">
            @foreach($learners as $key => $learner)
            @if($learner->status==1)

            <div class="active-plan-box basic">
                <h4>Seat No. : {{ $learner->seat_no }}</h4>
                <label for="">
                    Active
                </label>
                <div class="d-flex mt-2">
                    <ul class="plann-info">
                        <li>Plan End Date : <a href="javascript:;">{{ $learner->plan_end_date }}</a> </li>
                        <li>Plan Price : <a href="javascript:;"> {{ $learner->plan_price_id}} </a></li>
                        <li>Payment Status :  <a href="javascript:;"> {{$learner->is_paid ? 'Paid' : 'Unpaid'}} </a></li>
                    </ul>
                </div>
               
            </div>
            @endif
            @endforeach
        </div>
    </div>

    <!-- Library Features -->

    <div class="row">
        <div class="col-lg-12">
            <h4 class="py-4">Features List</h4>
            <ul class="features">
                @foreach($features as $key => $value)
                <li>
                    <!-- Feature Icon -->
                    <img src="{{ asset('public/'.$value->image) }}" alt="Feature Icon" width="50">
                    <p>{{$value->name}}</p>
                </li>
                @endforeach


            </ul>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <h4 class="py-4">Available Books List</h4>
            <ul class="features mb-4">
                <li>
                    <!-- Book Icon -->

                    <p>Book List is Available Soon!</p>
                </li>

            </ul>
        </div>
    </div>


    <div class="row">
        <div class="col-lg-12">
            <h4 class="py-4">Govt. Exam Information <span class="badge badge-danger">Beta (Coming Soon!)</span></h4>
            <ul class="features mb-4">
                <li>
                    <!-- Book Icon -->
                    <p>Govt. Exam Information is Available Soon!</p>
                </li>


            </ul>
        </div>
    </div>
</div>

@endsection