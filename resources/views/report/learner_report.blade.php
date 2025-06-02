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
        <div class="table-responsive ">
            <table class="table text-center datatable border-bottom" id="datatable">
                <thead>
                    <tr>
                        <th>Seat No.</th>
                        <th>Learner Info</th>
                        <th>Contact Info</th>
                        <th>Active Plan</th>
                        <th>Expired On</th>
                        <th>Payment Status</th>
                       
                    </tr>
                </thead>

                <tbody>
                  
                    @foreach($learners as $value)
                    
                    @php
                    $today = Carbon::today();
                    $endDate = Carbon::parse($value->plan_end_date);
                    $diffInDays = $today->diffInDays($endDate, false);
                    @endphp

                    <tr>
                        <td>{{$value->learner->seat_no}}<br>
                            <small>{{$value->planType->name}}</small>
                        </td>
                        <td><span class="uppercase truncate" data-bs-toggle="tooltip"
                                data-bs-title="{{$value->learner->name}}" data-bs-placement="bottom">{{$value->learner->name}}</span>
                            <br> <small>{{$value->learner->dob}}</small>
                        </td>
                        <td><span class="truncate" data-bs-toggle="tooltip"
                                data-bs-title="{{$value->learner->email }}" data-bs-placement="bottom"><i
                                    class="fa-solid fa-times text-danger"></i></i>
                                {{$value->learner->email }}</span> <br>
                            <small> +91-{{$value->learner->mobile}}</small>
                        </td>
                        <td>{{$value->plan_start_date}}<br>
                            <small>{{$value->plan->name}}</small>
                        </td>
                       
                        <td>{{$value->plan_end_date}}<br>
                          
                            @if ($diffInDays > 0)
                            <small class="text-success fs-10 d-block">Expires in {{ $diffInDays }} days</small>
                            @elseif ($diffInDays < 0)
                            <small class="text-danger fs-10 d-block">Expired {{ abs($diffInDays) }} days ago</small>
                            @else
                            <small class="text-warning fs-10 d-block">Expires today</small>
                            @endif
                        </td>
                        <td>
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