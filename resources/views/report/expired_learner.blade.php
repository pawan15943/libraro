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

            <form action="{{ route('expired.learner.report') }}" method="GET">
                <div class="row g-4">
                    
                    <div class="col-lg-4">
                        <label for="expiredyear">Filter By Year</label>
                        <select id="expiredyear" class="form-select " name="expiredyear">
                            <option value="">Select Year</option>
                            @foreach($dynamicyears as $year)
                                <!-- Default to current year if no year is selected, else use selected year -->
                                <option value="{{ $year }}" 
                                    {{ (request('expiredyear') ?? $currentYear) == $year ? 'selected' : '' }}>
                                    {{ $year }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="col-lg-4">
                        <label for="expiredmonth">Select Month:</label>
                        <select id="expiredmonth" class="form-select " name="expiredmonth">
                            <option value="">Select Month</option>
                            @foreach($dynamicmonths as $month)
                                <option value="{{ str_pad($month, 2, '0', STR_PAD_LEFT) }}" 
                                    {{ request('expiredmonth') == str_pad($month, 2, '0', STR_PAD_LEFT) ? 'selected' : '' }}>
                                    {{ DateTime::createFromFormat('!m', $month)->format('M') }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    

                    <!-- Search By Name, Mobile & Email -->
                    <div class="col-lg-4">
                        <label for="search">Search By Name, Mobile & Email</label>
                        <input type="text" class="form-control" name="search" placeholder="Enter Name, Mobile or Email"
                            value="{{ request()->get('search') }}">
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
        </div>
    </div>
</div>

<div class="row mb-4 mt-4">
   
    <div class="col-lg-12">
        <div id="export" class="mb-3"></div>
        <div class="table-responsive ">
            <table class="table text-center datatable border-bottom" id="datatable">
                <thead>
                    <tr>
                        <th class="d-none"></th>
                        <th class="d-none"></th>
                        <th class="d-none"></th>
                        <th class="d-none"></th>
                        <th class="d-none"></th>
                        <th class="d-none"></th>
                        <th class="d-none"></th>
                        <th class="d-none"></th>
                        <th class="d-none"></th>
                        <th class="merged-display">Seat No.</th>
                        <th class="merged-display">Learner Info</th>
                        <th class="merged-display">Contact Info</th>
                        <th class="merged-display">Active Plan</th>
                        <th class="merged-display">Expired On</th>
                       
                    </tr>
                </thead>

                <tbody>
                    @foreach($learners as $value)
          
                    <tr>
                        <td class="d-none export-seat-no">{{ $value->learner->seat_no ?? "GEN" }}</td>
                        <td class="d-none export-plan-type">{{ $value->planType->name ?? '' }}</td>
                        <td class="d-none export-name">{{ $value->learner->name ?? '' }}</td>
                        <td class="d-none export-email">{{ $value->learner->email ?? 'Email ID Not Available' }}</td>
                        <td class="d-none export-mobile">{{ $value->learner->mobile ?? '' }}</td>
                        <td class="d-none export-start-date">{{ $value->plan_start_date }}</td>
                        <td class="d-none export-plan-name">{{ $value->plan->name ?? '' }}</td>
                        <td class="d-none export-end-date">{{ $value->plan_end_date }}</td>
                        <td class="d-none export-expiry-status">
                         {!! getUserStatusDetails($value->plan_end_date) !!}
                        </td>


                        <td class="merged-display">{{$value->learner->seat_no ?? 'GEN'}}<br>
                            <small>{{$value->planType->name ?? ''}}</small>
                        </td>
                        <td class="merged-display"> <span class="uppercase truncate name" data-bs-toggle="tooltip"
                                data-bs-title="{{$value->learner->name}}" data-bs-placement="bottom">{{$value->learner->name ?? ''}}</span>
                            <br> <small>{{$value->learner->dob ?? ''}}</small>
                        </td>
                        <td class="merged-display"><span class="truncate" >
                            {!! $value->learner->email ? $value->learner->email : '<i class="fa-solid fa-times text-danger"></i> Email ID Not Available' !!} 
                            </span>  <br>
                            <small> +91-{{$value->learner->mobile ?? ''}}</small>
                        </td>
                        <td class="merged-display">{{$value->plan_start_date ?? ''}}<br>
                            <small>{{$value->plan->name ?? ''}}</small>
                        </td>
                       
                        <td class="merged-display">{{$value->plan_end_date}}<br>
                          {!! getUserStatusDetails($value->plan_end_date) !!}
                        </td>
                    </tr>
                    @endforeach
             
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    $(document).ready(function () {
        var table =$('#datatable').DataTable({
            
            buttons: [
                {
                    extend: 'csvHtml5',
                    text: 'Export CSV',
                    title: 'ExpiredLearnerReport',
                    exportOptions: {
                        columns: function (idx, data, node) {
                             return $(node).hasClass('d-none'); // export only hidden columns
                        },
                        format: {
                            body: function (data) {
                                return data.replace(/<[^>]+>/g, '').replace(/&nbsp;/g, ' ').trim();
                            },
                             header: function (data, columnIdx) {
                                const headers = [
                                    'Seat No',
                                    'Plan Type',
                                    'Name',
                                    'Email',
                                    'Mobile',
                                    'Start Date',
                                    'Plan Name',
                                    'End Date',
                                    'Expiry Status',
                                ];
                                return headers[columnIdx] ?? '';
                            }
                        }
                    }

                }
            ],
             columnDefs: [
                { targets: 'export-seat-no', visible: false },
                { targets: 'export-plan-type', visible: false },
                { targets: 'export-name', visible: false },
                { targets: 'export-email', visible: false },
                { targets: 'export-mobile', visible: false },
                { targets: 'export-start-date', visible: false },
                { targets: 'export-plan-name', visible: false },
                { targets: 'export-end-date', visible: false },
                { targets: 'export-expiry-status', visible: false },
                { targets: 'merged-display', visible: true }
            ],
           
            lengthMenu: [10, 25, 50, 100],
            pageLength: 10
        });
        table.buttons().container().appendTo('#export');
    });
</script>

@endsection