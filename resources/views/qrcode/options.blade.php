@extends('sitelayouts.layout')
@section('content')


<div class="sacnd-data py-5" style="min-height: 500px; display:flex; align-items:center;">
    <div class="container">
        <div class="row justify-content-center align-items-center h-100">
            <div class="col-lg-3">
                <div class="process-step-1">
                    <div class="action-content">
                        <div class="headings text-center">
                            <h4 class="mb-4">What would you like to do?</h4>
                            <h2>{{ $branch->name }} - Library</h2>
                            <span class="text-message">Please select an option from the list below to
                                proceed.</span>
                        </div>
                        <ul class="action-list">
                            <li><a href="{{ route('booking.form', $branch->uuid) }}">Book<br> Seat</a></li>
                            <li><a href="{{ route('renew.form', $branch->uuid) }}">Re-New Seat</a></li>
                            <li><a href="{{ route('qr.attendance.link') }}">Open Attendence App</a></li>
                            <!-- <li><a href="">Upgrade Plan</a></li>
                                <li><a href="">Change Plan</a></li>
                                <li><a href="">Close Plan</a></li>
                                <li><a href="">Raise Complaint</a></li> -->
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>




@endsection
