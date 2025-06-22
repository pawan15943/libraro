@extends('layouts.admin')

@section('content')
<div class="row">
    <div class="col-lg-9">
        <div class="actions">
            <div class="upper-box">
                <div class="d-flex">
                    <h4 class="mb-3">Library Info</h4>
                    <a href="javascript:void(0);" class="go-back"
                        onclick="window.history.back();">Go Back <i
                            class="fa-solid fa-backward pl-2"></i></a>
                </div>
                <div class="row g-4">
                    <div class="col-lg-6">
                        <span>Library Name</span>
                        <h5 class="uppercase">{{$library->library_name}}</h5>
                    </div>
                    <div class="col-lg-6">
                        <span>Library Join Date </span>
                        <h5>{{ \Carbon\Carbon::parse($library->created_at)->format('d-m-Y') }}</h5>

                    </div>
                    <div class="col-lg-6">
                        <span>Library Number</span>
                        <h5>+91-{{$library->library_mobile}}</h5>
                    </div>
                    <div class="col-lg-6">
                        <span>Library Email Id</span>
                        <h5>{{$library->email}}</h5>
                    </div>
                </div>
            </div>
            <div class="action-box">
                <h4>Library Plan Info :</h4>
                <div class="row g-4">
                    <div class="col-lg-4">
                        <span>Plan</span>
                        <h5>{{$library_transaction->month ?? ''}} MONTHS</h5>
                    </div>
                    <div class="col-lg-4">
                        <span>Plan Type</span>
                        <h5>{{$plan->name}}</h5>
                    </div>
                    <div class="col-lg-4">
                        <span>Plan Price</span>
                        <h5>{{$library_transaction->amount ?? ''}}</h5>
                    </div>
                    <div class="col-lg-4">
                        <span>Library Booked On</span>
                        <h5>{{$library_transaction->transaction_date ?? ''}}</h5>
                    </div>
                    <div class="col-lg-4">
                        <span>Plan Starts On</span>
                        <h5>{{$library_transaction->start_date ?? ''}}</h5>
                    </div>
                    <div class="col-lg-4">
                        <span>Plan Ends On</span>
                        <h5>{{$library_transaction->end_date ?? ''}}</h5>
                    </div>
                 
                    <div class="col-lg-4">
                        <span>Plan Expired In</span>
                        <h5><span class="text-success">Plan Expires in 24 Days</span></h5>
                    </div>
                    <div class="col-lg-4">
                        <span>Current Plan Status</span>
                        <h5>
                            @if($library->status==1)
                            <span class="text-success">Active</span>
                            @else
                            <span class="text-danger">InActive</span>
                            @endif
                            

                        </h5>
                    </div>
                </div>
                <h4 class="mt-4"> Library Other Info :</h4>
                <div class="row g-4">
                  
                    <div class="col-lg-4">
                        <span>Library Created At</span>
                        <h5>{{$library->created_at}}</h5>
                    </div>
                    <div class="col-lg-4">
                        <span>Library Modified At</span>
                        <h5>{{$library->updated_at}}</h5>
                    </div>
                    <div class="col-lg-4">
                        <span>Library Deleted At</span>
                        <h5>
                            {{$library->deleted_at?? 'NA'}}
                        </h5>
                    </div>
                </div>
                <h4 class="mt-4"> Library Owner Info :</h4>
                <div class="row g-4">
                    <div class="col-lg-6">
                        <span>Library Owner Name</span>
                        <h5 class="uppercase">{{$library->library_owner ?? ''}}</h5>
                    </div>
                    
                    <div class="col-lg-6">
                        <span>Library Number</span>
                        <h5>+91-{{$library->library_mobile ?? ''}}</h5>
                    </div>
                    <div class="col-lg-6">
                        <span>Library Email Id</span>
                        <h5>{{$library->email ?? ''}}</h5>
                    </div>
                </div>
                <h4 class="mt-4"> Library Payment Info :</h4>
                @foreach($library_all_transaction as  $value)
               
                    <div class="row g-4">
                        <div class="col-lg-4">
                            <span>Payment Mode</span>
                            <h5>{{$value->payment_mode==1 ? 'Online' : 'Offline'}}</h5>
                        </div>
                        <div class="col-lg-4">
                            <span>Payment Status</span>
                            <h5>
                                @if($value->is_paid==1)
                                <span
                                class="text-success">Paid</span>
                                @else
                                <span class="text-danger">Pending</span>  
                                @endif
                                 
                            </h5>
                        </div>
                        <div class="col-lg-4">
                            <span>Transaction Id</span>
                              <h5>{{$value->transaction_id}}</h5>
                        </div>
                        <div class="col-lg-4">
                            <span>Payment Date</span>
                              <h5>{{$value->transaction_date}}</h5>
                        </div>
                    </div>
                @endforeach
                
                <h4 class="mt-4"> Library Re-New History :</h4>
                <div class="row g-4">
                    <div class="col-lg-12">
                        <div class="table-responsive">
                            <table class="table text-center">
                                <thead>
                                    <tr>
                                        <th>Plan </th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                        <th>Paid Amount</th>
                                        <th>Payment Mode</th>
                                        <th>Paid On</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($library_all_transaction as  $value)
                                     @php
                                        $data_pr=getLibraryDataFromId($value->library_id);
                                       
                                    @endphp
                                    <tr>
                                        <td>{{$data_pr->latest_transaction->month}} MONTHLY <br> </td>
                                        <td>{{$data_pr->latest_transaction->start_date}}</td>
                                        <td>{{$data_pr->latest_transaction->end_date}}</td>
                                        <td>{{$data_pr->latest_transaction->paid_amount}}</td>
                                        <td>{{$data_pr->latest_transaction->payment_mode==1 ? 'Online' : 'Offline'}}</td>
                                       
                                        <td>{{$data_pr->latest_transaction->transaction_date}}</td>
                                        <td>
                                            <ul class="actionalbls" style="width: 90px;">
                                                <li><a href="javascript:;" data-toggle="modal"
                                                        data-target="#bookingDetailsModal"
                                                        title="View Transaction Details"><i
                                                            class="fa-solid fa-eye"></i></a></li>
                                                <li><a href="" title="Print Receipt"><i
                                                            class="fa-solid fa-print"></i></a></li>
                                                <li><a href="" title="Download Receipt"><i
                                                            class="fa-solid fa-download"></i></a>
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
        </div>
    </div>
    <div class="col-lg-3">
        {{-- <div class="seat--info">
            <img src="assets/images/plan.avif" alt="plan" class="img-fluid">
        </div> --}}

        {{-- <div class="request-logs mt-4">
            <h5>Library Request</h5>
            <ul class="request_list">
                <li>
                    <div class="d-flex">
                        <div class="icon"></div>
                        <div class="detials">
                            <p class="m-0"><i class="fa-solid fa-arrow-turn-down"></i> Request Name
                                : Renew Seat</p>
                            <span class="description">Message Send by <b>[Seat Owner]</b> on
                                10-05-2024.</span>
                            <span class="timestamp"><i class="fa-solid fa-calendar"></i> 29-05-2024
                                10:15:21 AM</span>
                            <small class="status"> <b>Status : </b> <span
                                    class=" text-danger d-inline">Pending</span> </small>
                        </div>
                    </div>
                </li>
                <li>
                    <div class="d-flex">
                        <div class="icon"></div>
                        <div class="detials">
                            <p class="m-0"><i class="fa-solid fa-arrow-turn-up"></i> Request Name :
                                Upgrade Plan</p>
                            <span class="description">Seat is Swapped by <b>[Seat Owner]</b> on
                                10-05-2024.</span>
                            <span class="timestamp"><i class="fa-solid fa-calendar"></i> 29-05-2024
                                10:15:21 AM</span>
                            <small class="status"> <b>Status : </b> <span
                                    class=" text-success d-inline">Resolved (By super Admin)</span>
                            </small>

                        </div>
                    </div>
                </li>
            </ul>
        </div> --}}
    </div>
</div>


@endsection