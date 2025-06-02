@extends('layouts.library')

@section('content')


<!-- Breadcrumb -->

<div class="row justify-content-center mb-4">
    <div class="col-lg-12">
        <div class="table-responsive">
            <table class="table text-center datatable">
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Plan Name</th>
                        <th>Plan Price</th>
                        <th>Paid Amt (After GST)</th>
                        <th>Trxn Id</th>
                        <th>Trxn Date</th>
                        <th>Payment Method</th>
                        <th>Trxn Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach($transaction as $key => $value)
                    <tr>
                        <td>1</td>
                        <td><span class="premium p-2 rounded text-white">{{$plan ? $plan->name : $value->plan}}</span></td>
                        <td><i class="fa fa-inr"></i> {{$value->amount}}</td>
                        <td><i class="fa fa-inr"></i> {{$value->paid_amount}}</td>
                        <td>{{$value->transaction_id ? $value->transaction_id : 'NA'}}</td>
                        <td>{{$value->transaction_date}}</td>
                        <td>{{
                            $value->payment_mode == 1 ? 'Online' : 
                            ($value->payment_mode == 2 ? 'Offline' : 'Not Paid') 
                        }} Paid</td>

                        <td>@if($value->is_paid==1)
                            <span class="text-success">Success</span>
                            @else
                            <span class="text-danger">Failed</span>
                            @endif
                        </td>
                        @can('has-permission', 'Download Payment Receipt')
                        <td>
                            <ul class="actionalbls">
                                <li>

                                    <form action="{{ route('fee.generateReceipt') }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <input type="hidden" id="custId" name="id" value="{{ $value->id }}">
                                        <input type="hidden" name="type" value="library">

                                        <button type="submit">
                                            <i class="fa fa-print"></i>
                                        </button>
                                    </form>

                                </li>
                            </ul>
                        </td>
                        @endcan
                    </tr>


                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>



@endsection