@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('content')

<!-- Dahsboard Count -->
<div class="row">
    <div class="col-lg-3">
        <div class="adminCounts">
            <h4>Total Registration</h4>
            <h1>{{$totalregistration}}</h1>
            <a href="{{route('library.count.view', ['type' => 'total'])}}">View Details</a>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="adminCounts">
            <h4>Total Paid</h4>
            <h1>{{$paidregistration}}</h1>
            <a href="{{route('library.count.view', ['type' => 'paid_registration'])}}">View Details</a>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="adminCounts">
            <h4>Total Unpaid</h4>
            <h1>{{$unpaidregistration}}</h1>
            <a href="{{route('library.count.view', ['type' => 'unpaid_registration'])}}">View Details</a>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="adminCounts">
            <h4>Pending for Renew</h4>
            <h1>{{$renewCount}}</h1>
            <a href="{{route('library.count.view', ['type' => 'pending_renew'])}}">View Details</a>
        </div>
    </div>
</div>
<div class="row align-items-center mt-4">
    @php
    $currentYear = date('Y');
    $currentMonth = date('m');
    $year=['0'=>'2024','1'=>'2025','2'=>'2026','3'=>'2027'];
    $month=['0'=>'01','1'=>'02','2'=>'03','3'=>'04','4'=>'05','5'=>'06','6'=>'07','7'=>'08','8'=>'09','9'=>'10','10'=>'11','11'=>'12'];
    @endphp
    <div class="col-lg-3">
        <h4>Filter Dashboard Data</h4>
    </div>
    <div class="col-lg-3"></div>
    <div class="col-lg-3">
        <select id="datayaer" class="form-select form-control-sm">
            <option value="">Select Year</option>
            @foreach($year as $key => $value)
                <option value="{{ $value }}" {{ $value == $currentYear ? 'selected' : '' }}>{{$value}}</option>   
            @endforeach
            
           
        </select>
    </div>

    <div class="col-lg-3">
        <select id="dataFilter" class="form-select form-control-sm">
            <option value="">Select Month</option>
            @foreach($month as $key => $value)
                <option value="{{ $value }}" {{ $value == $currentMonth ? 'selected' : '' }}>{{$value}}</option>   
            @endforeach
        </select>
    </div>

</div>
<div class="col-lg-3">
    <div class="adminCounts">
        <h4>Total Revenue (A + B + C)</h4>
        <h1 id="total_revenue">20,200</h1>
        <a href="{{route('library.count.view', ['type' => 'total_revenue'])}}">View Details</a>
    </div>
</div>
<div class="row mt-4 planwisecount">
  
</div>
<div class="dashboard">


    <div class="row mt-4 mb-4">
        <div class="col-lg-6">
            <h4 class="mb-4">New Registrations</h4>
            <div class="table-responsive">
                <table class="table text-center datatable border-bottom" id="registration-table">
                    <thead>
                        <tr>
                            <th>S.No.</th>
                            <th>Library Name</th>
                            <th>Plan</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                       
                    </tbody>
                </table>
            </div>
        </div>
        <div class="col-lg-6">
            <h4 class="mb-4">Upcoming Renewal</h4>
            <div class="table-responsive">
                <table class="table text-center datatable border-bottom">
                    <thead>
                        <tr>
                            <th>S.No.</th>
                            <th>Library Name</th>
                            <th>Plan</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                    
                        @foreach($upcoming_registration as $key => $value)
                        <tr>
                            <td>{{$key+1}}</td>
                            <td>{{$value->name}}</td>
                            <td>{{$value->subscription->name}}</td>
                            @if($value->is_paid==1)
                            <td>Paid</td>
                            @else
                            <td>UnPaid</td>
                            @endif
                           
                            <td>
                                <ul class="actionalbls">
                                    <li>
                                        <a href=""><i class="fa fa-eye"></i></a>
                                    </li>
                                </ul>
                            </td>
                        </tr>
                        @endforeach
                   
                       

                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.concat.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@0.7.0"></script>
<script>
    (function($) {
        $(window).on("load", function() {
            $(".v-content").mCustomScrollbar({
                theme: "dark",
                scrollInertia: 300,
                axis: "x",
                autoHideScrollbar: false,
            });
        });

        function refreshScrollbar() {
            const $content = $(".v-content");
            $content.mCustomScrollbar("update");
        }

        $(document).on("change", "#dataFilter", function() {
            refreshScrollbar();
        });

        const observer = new MutationObserver(() => {
            refreshScrollbar();
        });

        observer.observe(document.querySelector(".v-content"), {
            childList: true,
            subtree: true
        });

    })(jQuery);
</script>
<script>
    (function($) {
        $(window).on("load", function() {
            $(".contents").mCustomScrollbar({
                theme: "dark",
                scrollInertia: 300,
                axis: "y",
                autoHideScrollbar: false, // Keeps scrollbar visible
            });
        });
    })(jQuery);
</script>
<script>
    $(document).ready(function() {
        var initialYear = $('#datayaer').val();
        var initialMonth = $('#dataFilter').val();
        fetchLibraryData(initialYear, initialMonth, null);
        // updateAllViewLinks(initialYear, initialMonth, null);

        // Event listener for year filter
        $('#datayaer').on('change', function() {
            var selectedYear = $(this).val();
            var selectedMonth = $('#dataFilter').val();
           
            fetchLibraryData(selectedYear, selectedMonth, null);
            // updateAllViewLinks(selectedYear, selectedMonth, null);
        });

        // Event listener for month filter
        $('#dataFilter').on('change', function() {
            var selectedYear = $('#datayaer').val();
            var selectedMonth = $(this).val();
            console.log(selectedYear,'and',selectedMonth);
            fetchLibraryData(selectedYear, selectedMonth, null);
            // updateAllViewLinks(selectedYear, selectedMonth, null);
        });

        // Event listener for date range picker
        $('#dateRange').on('change', function() {
            var selectedYear = $('#datayaer').val();
            var selectedMonth = $('#dataFilter').val();
            var dateRange = $(this).val(); 
            fetchLibraryData(selectedYear, selectedMonth, dateRange);
            // updateAllViewLinks(selectedYear, selectedMonth, dateRange);
        });
        function fetchLibraryData(year, month, dateRange) {
            $.ajax({
                url: '{{ route("library.dashboard.data.get") }}',
                method: 'POST',
                data: {
                    year: year,
                    month: month,
                    date_range: dateRange,
                    _token: '{{ csrf_token() }}' 
                },
                success: function(response) {
                    
                    
                    console.log('Full response:', response);
                    const highlights = response.highlights;
                    const newRegistrations = highlights.new_registration;
                    $('#total_revenue').text(highlights.total_revenue);
                  
                    let tableBody = '';
                    newRegistrations.forEach((registration, index) => {
                        tableBody += `
                            <tr>
                                <td>${index + 1}</td>
                                <td>${registration.library_name}</td>
                                <td>${registration.subscription_name || 'N/A'}</td>
                                <td>${registration.status == 1 ? 'Active' : 'Deactive'}</td>
                                 <td>
                                    <a href="/library/view/${registration.id}" class="btn btn-primary btn-sm">View</a>
                                </td>
                            </tr>
                        `;
                    });

                
                    $('#registration-table tbody').html(tableBody);

                    
                    var planWiseBookings = response.plan_wise_booking;
                    $('.row.mt-4.planwisecount').empty(); // Clear existing data

                    planWiseBookings.forEach(function(booking) {
                        var html=` <div class="col-lg-3">
                                        <div class="adminCounts basic">
                                            <h4>${booking.subscription_name} Booked (A)</h4>
                                            <h1 id="${booking.subscription_id}">${booking.booking}</h1>
                                            <a href="{{ route('library.count.view') }}">View Details</a>

                                        </div>
                                    </div>`;
                                    $('.row.mt-4.planwisecount').append(html);
                                    
                    });

                    // // Render charts for Revenue and Booking Count
                    // if (response.planTypeWiseRevenue && Array.isArray(response.planTypeWiseRevenue.labels) && Array.isArray(response.planTypeWiseRevenue.data)) {
                    //     renderRevenueChart(response.planTypeWiseRevenue.labels, response.planTypeWiseRevenue.data);
                    // } else {
                    //     console.error('Invalid data format for planTypeWiseRevenue:', response.planTypeWiseRevenue);
                    // }

                    // if (response.planTypeWiseCount && Array.isArray(response.planTypeWiseCount.labels) && Array.isArray(response.planTypeWiseCount.data)) {
                    //     renderBookingCountChart(response.planTypeWiseCount.labels, response.planTypeWiseCount.data);
                    // } else {
                    //     console.error('Invalid data format for planTypeWiseCount:', response.planTypeWiseCount);
                    // }


                },
                error: function(xhr) {
                    console.error(xhr);
                }
            });
        }
        

       
    });
</script>
@endsection