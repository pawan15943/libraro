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

            <form action="{{ route('learner.report') }}" method="GET">
                <div class="row g-4">
                   
                    <div class="col-lg-2">
                        <label for="year">Filter By Year</label>
                        <select id="year" class="form-select " name="year">
                            <option value="">Select Year</option>
                            @foreach($months as $year => $monthData)
                                <option value="{{ $year }}" {{ $year == $currentYear ? 'selected' : '' }}>
                                    {{ $year }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="col-lg-2">
                        <label for="month">Select Month:</label>
                        <select id="month" class="form-select " name="month">
                            <option value="">Select Month</option>
                            @if(isset($months[$currentYear]))
                                @foreach($months[$currentYear] as $monthNumber => $monthName)
                                    <option value="{{ $monthNumber }}" {{ $monthNumber == $currentMonth ? 'selected' : '' }}>
                                        {{ $monthName }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                 
                    <!-- Filter By Payment Status -->
                    <div class="col-lg-2">
                        <label for="is_paid">Filter By Payment Status</label>
                        <select name="is_paid" id="is_paid" class="form-select">
                            <option value="">Choose Payment Status</option>
                            <option value="1" {{ old('is_paid', request()->get('is_paid')) == '1' ? 'selected' : '' }}>Paid</option>
                            <option value="0" {{ old('is_paid', request()->get('is_paid')) == '0' ? 'selected' : '' }}>Unpaid</option>
                        </select>
                    </div>

                    <!-- Filter By Active/Expired Status -->
                    <div class="col-lg-3">
                        <label for="status">Filter By Active / Expired</label>
                        <select name="status" id="status" class="form-select">
                            <option value="">Choose Status</option>
                            <option value="1" {{ old('status', request()->get('status')) == '1' ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ old('status', request()->get('status')) == '0' ? 'selected' : '' }}>Expired</option>
                        </select>
                    </div>


                    <!-- Search By Name, Mobile & Email -->
                    <div class="col-lg-3">
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
                        <th class="d-none"></th>
                        <th class="d-none"></th>
                       
                        <th class="merged-display">Seat No.</th>
                        <th class="merged-display">Learner Info</th>
                        <th class="merged-display">Contact Info</th>
                        <th class="merged-display">Active Plan</th>
                        <th class="merged-display">Expired On</th>
                        <th class="merged-display">Payment Status</th>
                       
                    </tr>
                </thead>

                <tbody>
                  
                    @foreach($learners as $value)
                   
                    <tr>
                          
                        <td class="d-none export-seat-no">{{ $value->learner->seat_no ?? "GEN" }}</td>
                        <td class="d-none export-plan-type">{{ $value->planType->name ?? '' }}</td>
                        <td class="d-none export-name">{{ $value->learner->name ?? '' }}</td>
                        <td class="d-none export-dob">{{ $value->learner->dob ?? '' }}</td>
                        <td class="d-none export-email">{{ $value->learner->email ?? 'Email ID Not Available' }}</td>
                        <td class="d-none export-mobile">{{ $value->learner->mobile ?? '' }}</td>
                        <td class="d-none export-start-date">{{ $value->plan_start_date }}</td>
                        <td class="d-none export-plan-name">{{ $value->plan->name ?? '' }}</td>
                        <td class="d-none export-end-date">{{ $value->plan_end_date }}</td>
                        <td class="d-none export-expiry-status">
                         {!! getUserStatusDetails($value->plan_end_date) !!}
                        </td>
                        <td class="d-none export-payment">
                            {{ $value->is_paid == 1 ? 'Paid' : 'Unpaid' }}
                        </td>

                        


                        <td class="merged-display">{{$value->learner->seat_no ?? "GEN"}}<br>
                            <small>{{$value->planType->name ?? ''}}</small>
                        </td>
                        <td class="merged-display"><span class="uppercase truncate name" data-bs-toggle="tooltip"
                                data-bs-title="{{$value->learner->name ?? ''}}" data-bs-placement="bottom">{{$value->learner->name ?? ''}}</span>
                            <br> <small>{{$value->learner->dob ?? ''}}</small>
                        </td>
                        <td class="merged-display"><span class="truncate" >
                            @if($value->learner)
                                {!! $value->learner->email ? $value->learner->email : '<i class="fa-solid fa-times text-danger"></i> Email ID Not Available' !!} 
                            @else
                                {!!'<i class="fa-solid fa-times text-danger"></i> Email ID Not Available'!!}
                            @endif
                            
                            </span> <br>
                            <small> +91-{{$value->learner->mobile ?? ''}}</small>
                        </td>
                        <td class="merged-display">{{$value->plan_start_date}}<br>
                            <small>{{$value->plan->name}}</small>
                        </td>
                       
                        <td class="merged-display">{{$value->plan_end_date}}<br>
                          {!! getUserStatusDetails($value->plan_end_date) !!}
                        </td>
                        <td class="merged-display">
                            @if($value->is_paid==1)
                                Paid
                            @else
                                Unpaid
                            @endif
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
                    title: 'AllLearnerReport',
                    exportOptions: {
                        columns: function (idx, data, node) {
                            // Export only hidden columns
                            return $(node).hasClass('d-none');
                        },
                        modifier: {
                            page: 'all' // Export all pages
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
                                    'Date of Birth',
                                    'Email',
                                    'Mobile',
                                    'Start Date',
                                    'Plan Name',
                                    'End Date',
                                    'Expiry Status',
                                    'Payment'
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
                { targets: 'export-dob', visible: false },
                { targets: 'export-email', visible: false },
                { targets: 'export-mobile', visible: false },
                { targets: 'export-start-date', visible: false },
                { targets: 'export-plan-name', visible: false },
                { targets: 'export-end-date', visible: false },
                { targets: 'export-expiry-status', visible: false },
                { targets: 'export-payment', visible: false },
                { targets: 'merged-display', visible: true }
            ],
            lengthMenu: [10, 25, 50, 100],
            pageLength: 10
        });
         table.buttons().container().appendTo('#export');
    });
</script>


<script>
    // Year or month dropdown
  const yearDropdown = document.getElementById('year');
  const monthDropdown = document.getElementById('month');

  yearDropdown.addEventListener('change', function () {
      const selectedYear = this.value;
      const monthsData = @json($months);

      monthDropdown.innerHTML = '<option value="">Select Month</option>'; // Reset

      if (selectedYear && monthsData[selectedYear]) {
          Object.entries(monthsData[selectedYear]).forEach(([monthNumber, monthName]) => {
              const option = document.createElement('option');
              option.value = monthNumber;
              option.textContent = monthName;
              monthDropdown.appendChild(option);
          });

          // Automatically select the current month if it matches
          if (selectedYear == @json($currentYear)) {
              monthDropdown.value = @json($currentMonth);
          }

          monthDropdown.disabled = false;
      } else {
          monthDropdown.disabled = true;
      }
  });
</script>


@endsection