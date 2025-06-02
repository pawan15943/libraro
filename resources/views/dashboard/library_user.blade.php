@extends('layouts.library')

@section('title', 'Admin Dashboard')

@section('content')


<div class="dashboard learner">
    <div class="row">
        <div class="col-lg-6">
            <div class="greeting-container">
                <i id="greeting-icon" class="fas fa-sun greeting-icon"></i>
                <h2 id="greeting-message" class="typing-text">Good Morning! Library Owner</h2>
            </div>
        </div>
        <div class="col-lg-6">
            <ul class="QuickAction">
                <li><a href="{{ route('seats') }}"><i class="fa fa-plus"></i> Book A Seat</a></li>
                <li><a href="{{ route('seats.history') }}"><i class="fa fa-chair"></i> View Seat Booking Matrix</a></li>
            </ul>
        </div>
    </div>
    <div class="row g-4">
        <div class="col-lg-9">
            <div class="dashboard-Header">
                <img src="{{url('public/img/bg-library-welcome.png')}}" alt="library" class="img-fluid rounded">
                <h1>Welcome to <span>Libraro</span><br>
                    Let’s Make Your <span class="typing-text"> Library the Place to Be! 📚🌟</span></h1>
            </div>
        </div>
     
    </div>
  
 


</div>









@endsection