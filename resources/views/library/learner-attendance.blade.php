@extends('layouts.library')
@section('content')
@php
use Carbon\Carbon;
$current_route = Route::currentRouteName();
@endphp

<!-- Bootstrap Toggle CSS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-toggle/2.2.2/css/bootstrap-toggle.min.css" rel="stylesheet">



<div class="row mb-4 ">
    <div class="col-lg-12">
        <div class="filter-box">
            <form action="{{ route('get.learner.attendance') }}" method="GET">
               
                    <!-- Filter By Plan -->
                <div class="row g-4">
                    <div class="col-lg-4">
                        <input type="date" class="form-control" name="date" value="{{ request('date') ?: date('Y-m-d') }}" id="date">
                    </div>
                    {{-- <div class="col-lg-4">
                        <select name="learner_id" class="form-control">
                            <option value="">Select Learner</option>
                            @foreach($data as $key => $value)
                                <option value="{{ $key }}" {{ request('learner_id') == $key ? 'selected' : '' }}>
                                    {{ $value }}
                                </option>
                            @endforeach
                        </select>
                    </div> --}}
                    
                    <div class="col-lg-2">
                        <button class="btn btn-primary button">
                            <i class="fa fa-search"></i> Search Records
                        </button>
                    </div>

                </div>


            </form>
        </div>
    </div>


</div>

<div class="row mb-4">
    <div class="col-lg-12">
        <div class="text-danger pb-3"><b>Note :</b> If you don't provide an out time, then learner's  closing shift time will be used as the out time.</div>
        <div class="table-responsive ">
            <table class="table text-center datatable border-bottom" id="datatable">
                <thead>
                    <tr>
                        <th>Seat No.</th>
                        <th>Learner Name</th>
                        <th>In time</th>
                        <th>Out time</th>
                        <th>Attendance</th>
                    </tr>
                </thead>
                <tbody>
                    @if($learners)


                    @foreach($learners as $key => $value)
                    @php
                    $today = Carbon::today();
                    $endDate = Carbon::parse($value->plan_end_date);
                    $diffInDays = $today->diffInDays($endDate, false);
                    $inextendDate = $endDate->copy()->addDays($extendDay); // Preserving the original $endDate
                    $diffExtendDay= $today->diffInDays($inextendDate, false);
                   
                    @endphp
                    <tr>
                        <td>{{$value->seat_no}}<br>
                        </td>
                        <td>
                           {{$value->name}}
                        </td>
                        

                        <td>{{ $value->in_time ? \Carbon\Carbon::parse($value->in_time)->format('h:i A') : '-' }}</td>

                        <td>{{ $value->out_time ? \Carbon\Carbon::parse($value->out_time)->format('h:i A') : '-' }}</td>
                        
                        
                        <td>
                            @if($value->attendance==1)
                                Present
                            @elseif($value->attendance==0)
                            Absent
                            @else
                                No Attendance
                            @endif
                        </td>
                    </tr>
                    @endforeach

                    @else
                    <tr>
                        <td colspan="7">Please select a date to view the student list. No date is currently selected.</td>
                    </tr>
                    @endif
                </tbody>


            </table>

        </div>
    </div>
</div>



<script>
    $(document).ready(function() {
       
       

       
    });
</script>

@endsection