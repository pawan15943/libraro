@extends('layouts.library')
@section('content')

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
<!-- View Customer Information -->
<div class="row g-4">
    <div class="col-lg-9 order-2 order-md-1">
        <div class="actions">
            <div class="upper-box">
                <div class="d-flex">
                    <h4 class="mb-3">Leraners Info</h4>
                    <a href="javascript:void(0);" class="go-back" onclick="window.history.back();">Go
                        Back <i class="fa-solid fa-backward pl-2"></i></a>
                </div>
                <div class="row g-4">
                    <div class="col-lg-6 col-6">
                        <span>Seat Owner Name</span>
                        <h5 class="uppercase">{{ $customer->name }}</h5>
                    </div>
                    <div class="col-lg-6 col-6">
                        <span>Date Of Birth </span>
                        <h5>{{ $customer->dob }}</h5>
                    </div>
                    <div class="col-lg-6 col-6">
                        <span>Mobile Number</span>
                        <h5>+91-{{ $customer->mobile }}</h5>
                    </div>
                    <div class="col-lg-6 col-6">
                        <span>Email Id</span>
                        <h5>{{ $customer->email }}</h5>
                    </div>
                </div>
            </div>
            <div class="action-box">
                <h4>Seat Plan Info</h4>
                <div class="row g-4">
                    <div class="col-lg-6 col-6 col-6">
                        <span>Plan</span>
                        <h5>{{ $customer->plan_name }}</h5>
                    </div>
                    <div class="col-lg-6 col-6 col-6">
                        <span>Plan Type</span>
                        <h5>{{ $customer->plan_type_name }}</h5>
                    </div>
                    <div class="col-lg-6 col-6 col-6">
                        <span>Plan Price</span>
                        <h5>{{ $customer->plan_price_id }}</h5>
                    </div>
                    <div class="col-lg-6 col-6 col-6">
                        <span>Seat Booked On</span>
                        <h5>{{ $customer->join_date }}</h5>
                    </div>
                    <div class="col-lg-6 col-6 col-6">
                        <span>Plan Starts On</span>
                        <h5>{{ $customer->plan_start_date }}</h5>
                    </div>
                    <div class="col-lg-6 col-6 col-6">
                        <span>Plan Ends On</span>

                        <h5>{{ $customer->plan_end_date }}</h5>
                    </div>
                    <div class="col-lg-6 col-6 col-6">
                        <span>Seat Timings</span>
                        <h5>{{$customer->hours}} Hours ({{ $customer->start_time }} to {{ $customer->end_time }})</h5>
                    </div>
                    <div class="col-lg-6 col-6 col-6">
                        <span>Plan Expired In</span>
                        <h5>{!! getUserStatusWithSpan($customer->plan_end_date) !!}</h5>
                        
                      

                    </div>
                    <div class="col-lg-6 col-6 col-6">
                        <span>Current Plan Status</span>
                        <h5>
                            @if($customer->status==1)
                            <h5 class="text-success">Active</h5>
                            @else
                            <h5 class="text-danger">Expired on 20-10-2024</h5>
                            @endif
                        </h5>
                    </div>


                </div>
                <h4 class="mt-4"> Seat Other Info :</h4>
                <div class="row g-4">

                    <div class="col-lg-6 col-6 col-6">
                        <span>Id Proof</span>
                        <h5>
                            @if($customer->id_proof_name==1)
                            Aadhar
                            @elseif($customer->id_proof_name==2)
                            Driving License
                            @else
                            Other
                            @endif
                            @if($customer->id_proof_file)
                            <img src="{{ asset($customer->id_proof_file) }}" width="150" height="150">
                            @else
                            <img src="">

                            @endif
                        </h5>
                    </div>

                    <div class="col-lg-6 col-6 col-6">
                        <span>Seat Created At</span>
                        <h5>{{ $customer->created_at }}</h5>
                    </div>
                    <div class="col-lg-6 col-6 col-6">
                        <span>Seat Modified At</span>
                        <h5>{{ $customer->updated_at }}</h5>
                    </div>
                    <div class="col-lg-6 col-6 col-6">
                        <span>Seat Deleted At</span>
                        <h5> {{ $customer->deleted_at ? $customer->deleted_at : 'NA'}}</h5>
                    </div>
                </div>
                {{-- Locker Info --}}
                @if(isset($transaction) && $transaction->locker_amount)
                <h4 class="mt-4"> Locker Info :</h4>
                <div class="row g-4">
                    <div class="col-lg-6 col-6 col-6">
                        <span>Is Locker </span>
                        <h5>{{ $transaction->locker_amount ? 'Yes' : 'No' }}</h5>
                    </div>

                    <div class="col-lg-6 col-6 col-6">
                        <span>Locker Number</span>
                        <h5> {{ $customer->locker_no ? $customer->locker_no : 'NA'}}</h5>
                    </div>
                   
                </div>
                @endif
                {{-- Seat Payment Info --}}
                <h4 class="mt-4"> Seat Payment Info :</h4>
                <div class="row g-4">
                    <div class="col-lg-6 col-6 col-6">
                        <span>Payment Date</span>
                        @if(isset($transaction->paid_date) && $transaction->paid_date)
                        <h5>{{$transaction->paid_date}}</h5>
                        @else
                        <h5>NA</h5>
                        @endif
                    </div>
                    <div class="col-lg-6 col-6 col-6">
                        <span>Payment Mode</span>
                        @if($customer->payment_mode == 1)
                        <h5>{{ 'Online' }}</h5>
                        @elseif($customer->payment_mode == 2)
                        <h5>{{ 'Offline' }}</h5>
                        @else
                        <h5>{{ 'Pay Later' }}</h5>

                        @endif
                    </div>

                  
                    <div class="col-lg-6 col-6 col-6">
                        <span>Total Amount to Pay
                            @if(isset($transaction) && $transaction->locker_amount !=0 && $transaction->discount_amount ==0)
                            (Plan Price + locker Amt.)
                            @elseif(isset($transaction) && $transaction->discount_amount !=0 && $transaction->locker_amount !=0)
                            (Plan Price + Locker Amt. - Discount Amt.)
                            @elseif(isset($transaction) && $transaction->discount_amount !=0 && $transaction->locker_amount ==0)
                            (Plan Price - Discount Amt.)
                            @endif
                        </span>
                        <h5>
                           
                            {{ $transaction->total_amount ?? 'NA'}}</h5>
                    </div>
                      @if(isset($transaction) && $transaction->discount_amount)

                    <div class="col-lg-6 col-6 col-6">
                        <span>Discount Amount</span>
                        <h5 class="text-success">{{$transaction->discount_amount ?? '0'}}</h5>
                    </div>
                    @endif
                     @if(isset($transaction) && $transaction->locker_amount)
                     <div class="col-lg-6 col-6 col-6">
                        <span>Locker Amt.</span>
                        <h5> {{$transaction->locker_amount ?? '0'}}</h5>
                    </div>
                    @endif
                    <div class="col-lg-6 col-6 col-6">
                        <span>Paid Amt.</span>
                        <h5 class="text-success">{{ $transaction->paid_amount ?? 'NA'}}</h5>
                    </div>
                    <div class="col-lg-6 col-6 col-6">
                        <span>Pending Amt.</span>
                        <h5 class="text-danger">{{$transaction->pending_amount ?? '0'}}</h5>
                    </div>



                    <div class="col-lg-6 col-6 col-6">
                        <span>Payment Status</span>
                        <h5>
                            @if(isset($transaction->is_paid) && $transaction->is_paid==1)
                            <h5 class="text-success">Paid</h5>
                            @else
                            <h5 class="text-danger">Unpaid</h5>
                            @endif


                        </h5>
                    </div>
                    <div class="col-lg-6 col-6 col-6">
                        <span>Transaction Id</span>
                        @if(isset($transaction->transaction_id) && $transaction->transaction_id)
                        <h5>{{$transaction->transaction_id}}</h5>
                        @else
                        <h5>NA</h5>
                        @endif

                    </div>

                </div>
               
                <h4 class="mt-4">Current Seat Owner’s Renewal History</h4>
                <div class="row g-4">
                    <div class="col-lg-12">
                        <div class="table-responsive">
                            <table class="table text-center border-bottom" id="datatable">
                                <thead>
                                    <tr>
                                        <th>Plan </th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Amount</th>
                                        <th>Payment Mode</th>
                                        <th>Paid On</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    @foreach($renew_detail as $key => $value)
                                    @php
                                    $transactionRenew=App\Models\LearnerTransaction::where('learner_detail_id',$value->id)->where('is_paid',1)->first();
                                    @endphp
                                    <tr>
                                        <td>
                                            {{$value->plan->name}} <br>
                                            <small class="text-success">{{$value->planType->name}}</small>
                                        </td>
                                        <td>{{$value->plan_start_date}}</td>
                                        <td>{{$value->plan_end_date}}</td>
                                        <td>{{$transactionRenew->total_amount ?? 'NA'}}</td>

                                        @if($value->payment_mode == 1)
                                        <td>{{ 'Online' }}</td>
                                        @elseif($value->payment_mode == 2)
                                        <td>{{ 'Offline' }}</td>
                                        @else
                                        <td>{{ 'Pay Later' }}</td>

                                        @endif
                                        <td>{{$transactionRenew->paid_date ?? 'NA'}}</td>

                                        <td>
                                            <ul class="actionalbls" style="width: 90px;">
                                                {{-- @can('has-permission', 'View Seat')
                                                <li><a href="{{route('learners.show',$value->id)}}" title="View Seat Booking Full Details"><i class="fas fa-eye"></i></a></li>
                                                @endcan --}}

                                                @can('has-permission', 'Receipt Generation')
                                                @if($value->is_paid==1)
                                                <li>

                                                    <form action="{{ route('fee.generateReceipt') }}" method="POST" enctype="multipart/form-data">
                                                        @csrf
                                                        <input type="hidden" name="id" value="{{ $value->id ?? 'NA'}}">
                                                        <input type="hidden" name="type" value="learner">

                                                        <button type="submit">
                                                            <i class="fa fa-print"></i>
                                                        </button>
                                                    </form>

                                                </li>
                                                @endif

                                                @endcan
                                            </ul>
                                        </td>
                                    </tr>
                                    @endforeach


                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @if($seat_history->isNotEmpty())
                <h4 class="mt-4"> History of Previous Seat Owners</h4>
                <div class="row g-4">
                    <div class="col-lg-12">
                        <div class="table-responsive">
                            <table class="table text-center border-bottom" id="datatable1">
                                <thead>
                                    <tr>
                                        <th>Owner Name</th>
                                        <th>Mobile</th>
                                        <th>Email</th>
                                        <th>Plan</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    @foreach ($seat_history as $learner)
                                    <tr>
                                        <td>{{ $learner->name }}<br>
                                            <small>{{$learner->seat_no}}</small>
                                        </td> <!-- Owner Name -->
                                        <td>{{ $learner->mobile }}</td> <!-- Mobile -->
                                        <td>{{ $learner->email }}</td> <!-- Email -->

                                        <!-- Loop through learner details and display Plan, Start Date, End Date -->
                                        @if ($learner->learnerDetails->isNotEmpty())
                                        @php
                                        $firstDetail = $learner->learnerDetails->first();
                                        @endphp
                                        <!-- Display the first learner detail in the same row -->
                                        <td>{{ $firstDetail->plan->name ?? 'N/A' }}<br><small>{{ $firstDetail->planType->name ?? 'N/A' }}</small></td> <!-- Plan Name -->
                                        <td>{{ $firstDetail->plan_start_date ?? 'N/A' }}</td> <!-- Start Date -->
                                        <td>{{ $firstDetail->plan_end_date ?? 'N/A' }}</td> <!-- End Date -->
                                        <td>
                                            <!-- Action buttons for the first learner detail -->
                                            <ul class="actionalbls" style="width: 90px;">
                                                @can('has-permission', 'View Seat')
                                                <li><a href="{{route('learners.show',$firstDetail->learner_id)}}" title="View Seat Booking Full Details"><i class="fas fa-eye"></i></a></li>
                                                @endcan

                                                @can('has-permission', 'Receipt Generation')
                                                <li>
                                                    <form action="{{ route('fee.generateReceipt') }}" method="POST" enctype="multipart/form-data">
                                                        @csrf
                                                        <input type="hidden" id="custId" name="id" value="{{ $firstDetail->id }}">
                                                        <input type="hidden" name="type" value="learner">
                                                        <button type="submit"><i class="fa fa-print"></i></button>
                                                    </form>
                                                </li>
                                                @endcan
                                                {{-- <li><a href="" title="Download Receipt"><i class="fa-solid fa-download"></i></a></li> --}}
                                            </ul>
                                        </td>
                                    </tr>

                                    <!-- Display additional learner details in new rows (if any) -->
                                    @foreach ($learner->learnerDetails->skip(1) as $detail)
                                    <tr>
                                        <td colspan="3"></td> <!-- Empty columns for owner name, mobile, and email -->
                                        <td>{{ $detail->plan->name ?? 'N/A' }}</td> <!-- Plan Name -->
                                        <td>{{ $detail->start_date ?? 'N/A' }}</td> <!-- Start Date -->
                                        <td>{{ $detail->end_date ?? 'N/A' }}</td> <!-- End Date -->
                                        <td>
                                            <ul class="actionalbls" style="width: 90px;">
                                                @can('has-permission', 'View Seat')
                                                <li><a href="{{route('learners.show',$detail->learner_id)}}" title="View Seat Booking Full Details"><i class="fas fa-eye"></i></a></li>
                                                @endcan
                                                @can('has-permission', 'Receipt Generation')
                                                <li>
                                                    <form action="{{ route('fee.generateReceipt') }}" method="POST" enctype="multipart/form-data">
                                                        @csrf
                                                        <input type="hidden" id="custId" name="id" value="{{ $detail->id }}">
                                                        <input type="hidden" name="type" value="learner">
                                                        <button type="submit"><i class="fa fa-print"></i></button>
                                                    </form>
                                                </li>
                                                @endcan

                                                @can('has-permission', 'Download Payment Receipt')
                                                <li><a href="" title="Download Receipt"><i class="fa-solid fa-download"></i></a></li>
                                                @endcan
                                            </ul>
                                        </td>
                                    </tr>
                                    @endforeach
                                    @else
                                    <!-- If no learner details are available, show 'N/A' -->
                                    <td colspan="4">No details available</td>
                                    </tr>
                                    @endif
                                    @endforeach

                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-3 order-1 order-md-2">
        <div class="seat--info">
            @php
            $planDetails = getPlanStatusDetails($customer->plan_end_date);

            @endphp
            
            <span class="d-block ">Seat No : {{ $customer->seat_no}}</span>
           
            <img src="{{ asset($customer->image) }}" alt="Seat" class="seat py-3 {{ $planDetails['class']}}">
            <p>{{ $customer->plan_name}}</p>
            <button class="mb-3"> Booked for <b>{{ $customer->plan_type_name}}</b></button>

            {!! getUserStatusDetails($customer->plan_end_date) !!}
            <!-- End -->
        </div>
        @if($learner_request->isNotEmpty())
        <div class="request-logs mt-4">
            <h5>Learners Request</h5>
            <ul class="request_list">
                @foreach($learner_request as $key => $value)
                <li>
                    <div class="d-flex">
                        <div class="icon"></div>
                        <div class="detials">
                            <p class="m-0"><i class="fa-solid fa-arrow-turn-down"></i> Request Name
                                : {{$value->request_name}}</p>
                            <span class="description">Message Send by <b>[Seat Owner]</b> on
                                {{$value->request_date}}</span>
                            <span class="timestamp"><i class="fa-solid fa-calendar"></i> {{$value->created_at}}</span>
                            <small class="status"> <b>Status : </b>
                                @if($value->request_status==0)
                                <span class=" text-danger d-inline">Pending</span>
                                @else
                                <span class=" text-success d-inline">Resolved (By Admin)</span>
                                @endif

                            </small>
                        </div>
                    </div>
                </li>
                @endforeach
            </ul>
        </div>
        @endif
        @if($learnerlog->count() >0)


        <div class="seat-activity">
            <h5 class="py-4">All Activity Logs:</h5>
            <ul class="activity-log">
                @foreach($learnerlog as $key => $value)
                <li>
                    <p>{{ \Carbon\Carbon::parse($value->created_at)->format('Y-m-d') }} :
                        @if($value->operation=='learnerUpgrade')
                        Seat Upgrade
                        @elseif($value->operation=='swapseat')
                        Seat Swapped
                        @elseif($value->operation=='renewSeat')
                        Seat Renew
                        @elseif($value->operation=='reactive')
                        Reactive
                        @elseif($value->operation=='closeSeat')
                        Close Seat
                        @endif

                    </p>
                </li>
                @endforeach

            </ul>
        </div>
        @endif
    </div>
</div>
<script>
    $(document).ready(function() {
        let table = new DataTable('#datatable', {
            searching: false, // This option hides the search bar
        });
    });
    $(document).ready(function() {
        let table = new DataTable('#datatable1', {
            searching: false, // This option hides the search bar
        });
    });

</script>



@include('learner.script')
@endsection
