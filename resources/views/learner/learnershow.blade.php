@extends('layouts.library')
@section('content')


<!-- View Customer Information -->
<div class="row g-4">
    <div class="col-lg-9 order-2 order-md-1">
        <div class="library-operations mt-4">

            {{-- Personal Info --}}
            <div class="info__section">
                <h4 class="inner-heading">Learner Info</h4>
                <ul>
                    <li>
                        <span>Learner UID</span>
                        <h4>{{ $customer->learner_no ?? 'Not Updated Yet' }}</h4>
                    </li>
                    <li>
                        <span>Full Name</span>
                        <h4>{{ $customer->name ?? 'Not Updated Yet' }}</h4>
                    </li>

                    @if(!in_array('2', toggleHideField()))
                    <li>
                        <span>DOB</span>
                        <h4>{{ $customer->dob ? \Carbon\Carbon::parse($customer->dob)->format('d F, Y') : 'Not Updated Yet' }}</h4>
                    </li>
                    @endif


                    <li>
                        <span>Mobile</span>
                        <h4>{{ $customer->mobile ? '+91-'.$customer->mobile : 'Not Updated Yet' }}</h4>
                    </li>
                    @if(!in_array('1', toggleHideField()))
                    <li>
                        <span>Email</span>
                        <h4>
                            <a href="mailto:{{$customer->email}}" class="text-white">
                                {!! $customer->email ? $customer->email : 'Not Updated Yet' !!}
                            </a>
                        </h4>
                    </li>
                    @endif
                </ul>
            </div>

            {{-- Plan Info --}}
            <div class="seat_plan_info">
                <h4 class="inner-heading">Plan Info</h4>
                <ul>
                    <li>
                        <span>Plan</span>
                        <h4>{{ $customer->plan_name ?? 'Not Updated Yet' }}</h4>
                    </li>
                    <li>
                        <span>Plan Type</span>
                        <h4>{{ $customer->plan_type_name ?? 'Not Updated Yet' }}</h4>
                    </li>
                    <li>
                        <span>Plan Price</span>
                        <h4>{{ $customer->plan_price_id ?? 'Not Updated Yet' }}</h4>
                    </li>
                    <li>
                        <span>Seat Booked On</span>
                        <h4>{{ $customer->join_date ?? 'Not Updated Yet' }}</h4>
                    </li>
                    <li>
                        <span>Plan Starts On</span>
                        <h4>{{ $customer->plan_start_date ?? 'Not Updated Yet' }}</h4>
                    </li>
                    <li>
                        <span>Plan Ends On</span>
                        <h4>{{ $customer->plan_end_date ?? 'Not Updated Yet' }}</h4>
                    </li>
                    <li>
                        <span>Seat Timings</span>
                        <h4>
                            {{ $customer->hours ? $customer->hours.' Hours ('.$customer->start_time.' to '.$customer->end_time.')' : 'Not Updated Yet' }}
                        </h4>
                    </li>
                    <li>
                        <span>Plan Expired In</span>
                        <h4>{!! $customer->plan_end_date ? getUserStatusWithSpan($customer->plan_end_date,$customer->id) : 'Not Updated Yet' !!}</h4>
                    </li>
                    <li>
                        <span>Current Plan Status</span>
                        @if($customer->status==1)
                        <h4 class="text-success">Active</h4>
                        @elseif($customer->plan_end_date)
                        <h4 class="text-danger">Expired on {{ $customer->plan_end_date }}</h4>
                        @else
                        <h4>Not Updated Yet</h4>
                        @endif
                    </li>
                </ul>
            </div>

            {{-- Other Info --}}
            <div class="seat_plan_info">
                <h4 class="inner-heading">Seat Other Info</h4>
                <ul>
                    <li>
                        <span>Father Name</span>
                        <h4>{{ $customer->father_name ?? 'Not Updated Yet' }}</h4>
                    </li>
                    <li>
                        <span>Alternate Mobile No.</span>
                        <h4>{{ $customer->alternate_mobile ?? 'Not Updated Yet' }}</h4>
                    </li>
                    <li>
                        <span>Address</span>
                        <h4>{{ $customer->address ?? 'Not Updated Yet' }}</h4>
                    </li>
                    <li>
                        <span>Remark</span>
                        <h4>{{ $customer->remark ?? 'Not Updated Yet' }}</h4>
                    </li>
                    <li>
                        <span>Id Proof</span>
                        <h4>
                            {{-- ID Proof Name --}}
                           @if($customer->id_proof_name == 1)
                                Aadhar Card
                            @elseif($customer->id_proof_name == 2)
                                Driving License
                            @elseif($customer->id_proof_name == 4)
                                Pan Card
                            @elseif($customer->id_proof_name == 5)
                                Voter Id
                            @elseif($customer->id_proof_name == 3)
                                Other
                            @else
                                Not Updated Yet
                            @endif

                            ({{$customer->id_proof_number??''}})

                        </h4>
                        {{-- ID Proof File --}}
                        @if(!empty($customer->id_proof_file))
                        <img src="{{ asset($customer->id_proof_file) }}" width="150" height="150" class="circle" alt="ID Proof">
                        @endif
                    </li>
                    <li>
                        <span>Seat Created At</span>
                        <h4>{{ $customer->created_at ?? 'Not Updated Yet' }}</h4>
                    </li>
                    <li>
                        <span>Seat Modified At</span>
                        <h4>{{ $customer->updated_at ?? 'Not Updated Yet' }}</h4>
                    </li>
                    <li>
                        <span>Seat Deleted At</span>
                        <h4>{{ $customer->deleted_at ?? 'Not Updated Yet' }}</h4>
                    </li>
                </ul>
            </div>

            {{-- Locker Info --}}
            @if(isset($transaction))
            <div class="locer_info">
                <h4 class="inner-heading">Locker Info</h4>
                <ul>
                    <li>
                        <span>Is Locker</span>
                        <h4>{{ $transaction->locker_amount ? 'Yes' : 'Not Updated Yet' }}</h4>
                    </li>
                    <li>
                        <span>Locker Number</span>
                        <h4>{{ $customer->locker_no ?? 'Not Updated Yet' }}</h4>
                    </li>
                </ul>
            </div>
            @endif

            {{-- Payment Info --}}
            <div class="paymentt_info">
                <h4 class="inner-heading">Payment Info</h4>
                <ul>
                    <li>
                        <span>Payment Date</span>
                        <h4>{{ $transaction->paid_date ?? 'Not Updated Yet' }}</h4>
                    </li>
                    <li>
                        <span>Payment Mode</span>
                        <h4>
                            @if($customer->payment_mode == 1)
                            Online
                            @elseif($customer->payment_mode == 2)
                            Offline
                            @elseif($customer->payment_mode == 3)
                            Pay Later
                            @else
                            Not Updated Yet
                            @endif
                        </h4>
                    </li>
                    <li>
                        <span>Total Amount to Pay</span>
                        <h4>{{ $transaction->total_amount ?? 'Not Updated Yet' }}</h4>
                    </li>
                    <li>
                        <span>Discount Amount</span>
                        <h4 class="text-success">{{ $transaction->discount_amount ?? 'Not Updated Yet' }}</h4>
                    </li>
                    <li>
                        <span>Locker Amt.</span>
                        <h4>{{ $transaction->locker_amount ?? 'Not Updated Yet' }}</h4>
                    </li>
                    <li>
                        <span>Paid Amt.</span>
                        <h4 class="text-success">{{ $transaction->paid_amount ?? 'Not Updated Yet' }}</h4>
                    </li>
                    <li>
                        <span>Pending Amt.</span>
                        <h4 class="text-danger">{{ $transaction->pending_amount ?? 'Not Updated Yet' }}</h4>
                    </li>
                    <li>
                        <span>Token Money</span>
                        <h4>{{ $transaction->token_money ?? 'Not Updated Yet' }}</h4>
                    </li>
                    <li>
                        <span>Miscellaneous</span>
                        <h4>{{ $transaction->miscellaneous ?? 'Not Updated Yet' }}</h4>
                    </li>
                    <li>
                        <span>Payment Status</span>
                        @if(isset($transaction->is_paid) && $transaction->is_paid==1)
                        <h4 class="text-success">Paid</h4>
                        @elseif(isset($transaction->is_paid))
                        <h4 class="text-danger">Unpaid</h4>
                        @else
                        <h4>Not Updated Yet</h4>
                        @endif
                    </li>
                    <li>
                        <span>Transaction Id</span>
                        <h4>{{ $transaction->transaction_id ?? 'Not Updated Yet' }}</h4>
                    </li>
                </ul>
            </div>


            {{-- Renewal History --}}
            <div class="locer_info">
                <h4 class="inner-heading">Renewal History</h4>
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
                            $learner_id=$value->learner_id;
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
                                <td>
                                    @if($value->payment_mode == 1) Online
                                    @elseif($value->payment_mode == 2) Offline
                                    @else Pay Later
                                    @endif
                                </td>
                                <td>{{$transactionRenew->paid_date ?? 'NA'}}</td>
                                <td>
                                    <ul class="actionalbls" style="width: 90px;">
                                        @can('has-permission', 'Receipt Generation')
                                        @if($value->is_paid==1)
                                        <li>

                                            <form action="{{ route('fee.generateReceipt') }}" method="POST" enctype="multipart/form-data">
                                                @csrf
                                                <input type="hidden" name="learner_id" value="{{$learner_id}}">
                                                <input type="hidden" name="learner_detail_id" value="{{ $value->id ?? 'NA'}}">
                                                <input type="hidden" name="id" value="{{ $transactionRenew->id ?? 'NA'}}">
                                                <input type="hidden" name="type" value="learner">
                                                <button type="submit" class="noLoader">
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

            {{-- Previous Owners --}}
            @if(!is_null($seat_history) && $seat_history->isNotEmpty())
            <div class="locer_info">
                <h4 class="inner-heading">History of Previous Seat Owners</h4>
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
                                    <small>{{getSeatDisplayByMainNo($learner->seat_no) ?? 'General'}}</small>
                                </td>
                                <td>{{ $learner->mobile }}</td>
                                <td>{{ $learner->email }}</td>
                                @if ($learner->learnerDetails->isNotEmpty())
                                @php
                                $firstDetail = $learner->learnerDetails->first();
                                $transactionRenew=App\Models\LearnerTransaction::where('learner_detail_id',$firstDetail->id)->first();

                                @endphp
                                <td>{{ $firstDetail->plan->name ?? 'N/A' }}<br><small>{{ $firstDetail->planType->name ?? 'N/A' }}</small></td>
                                <td>{{ $firstDetail->plan_start_date ?? 'N/A' }}</td>
                                <td>{{ $firstDetail->plan_end_date ?? 'N/A' }}</td>
                                <td>
                                    <ul class="actionalbls" style="width: 90px;">
                                        @can('has-permission', 'View Seat')
                                        <li><a href="{{route('learners.show',$firstDetail->learner_id)}}" title="View Seat Booking Full Details"><i class="fas fa-eye"></i></a></li>
                                        @endcan
                                        @can('has-permission', 'Receipt Generation')
                                        <li>
                                            <form action="{{ route('fee.generateReceipt') }}" method="POST" enctype="multipart/form-data">
                                                @csrf
                                                <input type="hidden" name="learner_id" value="{{$learner->id}}">
                                                <input type="hidden" name="learner_detail_id" value="{{ $firstDetail->id ?? 'NA'}}">
                                                <input type="hidden" name="id" value="{{ $transactionRenew->id ?? 'NA'}}">
                                                <input type="hidden" name="type" value="learner">
                                                
                                                <button type="submit" class="noLoader">
                                                    <i class="fa fa-print"></i>
                                                </button>
                                            </form>

                                        </li>
                                        @endcan
                                    </ul>
                                </td>
                                @else
                                <td colspan="4">No details available</td>
                                @endif
                            </tr>
                            @foreach ($learner->learnerDetails->skip(1) as $detail)
                            @php
                            $transactionRenew=App\Models\LearnerTransaction::where('learner_detail_id',$detail->id)->first();
                            @endphp
                            <tr>
                                <td>{{ $learner->name }}<br>
                                    <small>{{getSeatDisplayByMainNo($learner->seat_no) ?? 'General'}}</small>
                                </td>
                                <td>{{ $learner->mobile }}</td>
                                <td>{{ $learner->email }}</td>
                                <td>{{ $detail->plan->name ?? 'N/A' }}</td>
                                <td>{{ $detail->plan_start_date ?? 'N/A' }}</td>
                                <td>{{ $detail->plan_end_date ?? 'N/A' }}</td>
                                <td>
                                    <ul class="actionalbls" style="width: 90px;">
                                        @can('has-permission', 'View Seat')
                                        <li><a href="{{route('learners.show',$detail->learner_id)}}" title="View Seat Booking Full Details"><i class="fas fa-eye"></i></a></li>
                                        @endcan
                                        @can('has-permission', 'Receipt Generation')
                                        <li>
                                            <form action="{{ route('fee.generateReceipt') }}" method="POST" enctype="multipart/form-data">
                                                @csrf
                                                <input type="hidden" name="learner_id" value="{{$learner_id}}">
                                                <input type="hidden" name="learner_detail_id" value="{{ $detail->id ?? 'NA'}}">
                                                <input type="hidden" name="id" value="{{ $transactionRenew->id ?? 'NA'}}">
                                                <input type="hidden" name="type" value="learner">
                                                <button type="submit" class="noLoader">
                                                    <i class="fa fa-print"></i>
                                                </button>
                                            </form>

                                        </li>
                                        @endcan
                                    </ul>
                                </td>
                            </tr>
                            @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

        </div>
    </div>

    <div class="col-lg-3 order-1 order-md-2">
        <!-- End -->
        <div class="seatnumber">
            @php
            $planDetails = getPlanStatusDetails($customer->plan_end_date);
            @endphp
            <img src="{{ asset($customer->image) }}" alt="Seat" class="py-3 {{$planDetails['class']}}" style="width:60px; display:block; margin:0 auto;">
            @if($customer->seat_no)
            <span class="d-block ">Seat No : {{ getSeatDisplayByMainNo($customer->seat_no)}}</span>
            @else
            <span class="d-block ">General</span>
            @endif
            <div class="seat--plan">{{ $customer->plan_type_name}}</div>
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
        {{-- @if($learnerlog->count() >0)


        <div class="seat-activity d-none">
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
        @endif --}}
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




@endsection