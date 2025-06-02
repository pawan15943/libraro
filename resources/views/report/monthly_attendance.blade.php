@extends('layouts.library')
@section('content')

<!-- Content Header (Page header) -->
@php
use Carbon\Carbon;
$currentYear = date('Y');
$currentMonth = date('m');
@endphp

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


<div class="row">
   
    <div class="col-lg-12">
        <div class="filter-box">
            <h4 class="mb-3">Filter Box</h4>

            <form action="{{ route('attendance.report') }}" method="GET">
                <div class="row g-4">
                        <!-- Filter By Payment Status -->
                        <div class="col-lg-2">
                            <label for="year">Filter By Year</label>
                            <select id="year" class="form-select " name="year">
                                <option value="">Select Year</option>
                                @foreach($dynamicyears as $year)
                                    <!-- Default to current year if no year is selected, else use selected year -->
                                    <option value="{{ $year }}" 
                                        {{ (request('year') ?? $currentYear) == $year ? 'selected' : '' }}>
                                        {{ $year }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        
                        <div class="col-lg-2">
                            <label for="month">Select Month:</label>
                            <select id="month" class="form-select " name="month">
                                <option value="">Select Month</option>
                                @foreach($dynamicmonths as $month)
                                    <option value="{{ str_pad($month, 2, '0', STR_PAD_LEFT) }}" 
                                        {{ request('month') == str_pad($month, 2, '0', STR_PAD_LEFT) ? 'selected' : '' }}>
                                        {{ DateTime::createFromFormat('!m', $month)->format('M') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                   
                </div>

                <div class="row mt-3">
                    <div class="col-lg-2">
                        <button class="btn btn-primary button">
                            <i class="fa fa-search"></i> Search Records
                        </button>
                    </div>
                </div>
            </form>
            <small class="d-block text-danger mt-2">TP : Total Present | TA : Total Absent</small>
        </div>
    </div>
</div>

<div class="row mb-4 mt-4">
   
    <div class="col-lg-12">
        <div class="table-responsive ">
            <table class="table text-center datatable border-bottom" id="datatable">
                <thead>
                    <tr>
                        <th>Seat No.</th>
                        <th>Learner Info</th>
                        
                        @for($i = 1; $i <= $daymonth; $i++)
                            <th>{{$i}}</th>
                        @endfor
                        <th>TP</th>
                        <th>TA</th>
                        
                    </tr>
                </thead>

                <tbody>
    @foreach($learnerAttendance as $value)
        <tr>
            <td>{{ $value['seat_no'] ?? 'G' }}</td>
            <td>
                <span class="uppercase truncate m-0" data-bs-toggle="tooltip"
                      data-bs-title="{{ $value['name'] ?? '' }}" data-bs-placement="bottom">
                    {{ $value['name'] ?? '' }}
                </span>
            </td>

            @foreach($value['daily'] ?? [] as $status)
                <td>{{ $status }}</td>
            @endforeach

            <td>{{ $value['present'] }}</td>
            <td>{{ $value['absent'] }}</td>
        </tr>
    @endforeach
</tbody>

            </table>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        let table = new DataTable('#datatable');
       
    });
</script>

@include('learner.script')
@endsection