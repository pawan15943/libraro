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

            <form action="{{ route('activity.report') }}" method="GET">
                <div class="row g-4">
                   
                    <div class="col-lg-2">
                            <label for="year">Filter By Year</label>
                            <select id="year" class="form-select " name="year">
                                <option value="">Select Year</option>
                                @foreach($years as $year)
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
                                @foreach($months as $month)
                                    <option value="{{ str_pad($month, 2, '0', STR_PAD_LEFT) }}" 
                                        {{ request('month') == str_pad($month, 2, '0', STR_PAD_LEFT) ? 'selected' : '' }}>
                                        {{ DateTime::createFromFormat('!m', $month)->format('M') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                 
                    <!-- Filter By Payment Status -->
                    <div class="col-lg-2">
                        <label for="operation">Filter By Activity</label>
                        <select name="operation" id="operation" class="form-select">
                            <option value="">Choose Activity</option>
                            <option value="swapseat" {{ old('operation', request()->get('operation')) == 'swapseat' ? 'selected' : '' }}>Swapseat</option>
                            <option value="changePlan" {{ old('operation', request()->get('operation')) == 'changePlan' ? 'selected' : '' }}>Change Plan</option>
                            <option value="learnerUpgrade" {{ old('operation', request()->get('operation')) == 'learnerUpgrade' ? 'selected' : '' }}>Upgrade</option>
                            <option value="renewSeat" {{ old('operation', request()->get('operation')) == 'renewSeat' ? 'selected' : '' }}>Renew</option>

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
        <div class="table-responsive ">
            <table class="table text-center datatable border-bottom" id="datatable">
                <thead>
                    <tr>
                        <th>Seat No.</th>
                        <th>Learner Name</th>
                      
                        <th>Activity Name</th>
                        <th>Previous Value</th>
                        <th>New Value</th>
                        <th>Status</th>
                        <th>Activity Date</th>
                       
                    </tr>
                </thead>

                <tbody>
                  
                    @foreach($learners as $value)
                   
                    <tr>
                        <td>{{$value->learner->seat_no ?? 'General'}}<br>
                            
                        </td>
                        <td class="text-uppercase">{{$value->learner->name ?? ''}}</td>
                     
                       <td>{{$value->operation ?? ''}}</td>
                       <td class="text-danger">{{$value->old_value ?? ''}}</td>
                       <td class="text-success">{{$value->new_value ?? ''}}</td>
                       <td class="text-success">{{$value->status}}</td>
                       <td>{{$value->created_at}}</td>
                     
                    </tr>
                   
                    @endforeach
                </tbody>
                

            </table>
            

        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        let table = new DataTable('#datatable', {
            searching: false // This option hides the search bar
        });
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

@include('learner.script')
@endsection