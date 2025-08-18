@extends('layouts.library')
@section('content')

<!-- Breadcrumb -->
<div class="heading-list justify-content-end mb-4">
    <a href="{{ route('branch.create') }}" class="btn btn-primary export m-0">
        <i class="fa-solid fa-plus "></i> Add Branch
    </a>
</div>
@if($branches->isEmpty())
<p class="not-found info-message">
<span class="close-btn" onclick="this.parentElement.style.display='none';">&times;</span>
There is currently no Data available </p>  
@else
<div class="row g-4 mb-4">
    @foreach($branches as $key => $value)
        <div class="col-lg-4 col-md-6">
            <div class="planBox">
                <div class="heading d-flex justify-content-between align-items-center">
                    <h4>Branch {{ $key+1 }}</h4>
                    <span class="active">Active</span>
                </div>

                <div class="plan border-top">
                    <div class="branchInfo">
                        <h4>{{ $value->name }}</h4>
                        <span>Address : {{ $value->library_address ?? 'Not updated yet' }}</span>
                    </div>
                    <ul>
                        <li>
                            <span>Contact</span>
                            <p class="m-0">+91-{{ $value->mobile ?? 'Not updated yet' }}</p>
                        </li>
                        <li class="w-100">
                            <span>Email ID</span>
                            <p class="m-0">{{ $value->email ?? 'Not updated yet' }}</p>
                        </li>
                    </ul>
                </div>

                <ul class="actionalbles">
                    @if(getCurrentBranch() != 0)
                        <li>
                            <a href="{{ route('seat.create', getCurrentBranch()) }}" title="Seat Update">
                                <i class="fa-solid fa-chair"></i>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('hour.create', getCurrentBranch()) }}" title="Hour Update">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('extendDay.create', $value->id) }}" title="Extend Day">
                                <i class="fa-solid fa-calendar-plus"></i>
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('lockeramount.create', $value->id) }}" title="Locker Amount">
                                <i class="fa-solid fa-lock"></i>
                            </a>
                        </li>
                         <li>
                            <a href="{{ route('tokenAmount.create', $value->id) }}" title="Token Money">
                                <i class="fa-solid fa-credit-card"></i>
                            </a>
                        </li>
                    @endif
                    <li>
                        <a href="{{ route('branch.edit', $value->id) }}" title="Branch Profile Edit">
                            <i class="fas fa-edit"></i>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    @endforeach
</div>
@endif

@include('library.script')

@endsection