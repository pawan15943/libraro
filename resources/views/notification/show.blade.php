@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('content')


<div class="row justify-content-center">
    <div class="col-lg-8">
        <h4 class="mb-4 text-center">All Notifications</h4>
        <ul class="notification-list">
            @foreach($notifications as $index => $notification)
            @php
            $data = json_decode($notification->data, true);
            @endphp
            <li class="unread-bg">
                <div class="unread"></div>
                <h4>{{ $data['title'] ?? 'N/A' }} <span class="text-success">(Active)</span></h4>
                <p>{{ $data['description'] ?? 'N/A' }}</p>
                <div class="d-flex">
                    <div class="validity">
                        <span>Valid Till : </span>
                        <p class="m-0">From {{ $notification->start_date }} to {{ $notification->end_date }}</p>
                    </div>
                    @if(!empty($data['link']))
                    <a href="{{ $data['link'] }}" class="btn btn-primary view-info" target="_blank">View</a>
                    @else
                    <a href="{{ $data['link'] }}" class="btn btn-primary view-info" target="_blank">View Detials</a>
                    @endif
                </div>
            </li>
            @endforeach
        </ul>

        
    </div>
</div>


@include('library.script')
@endsection