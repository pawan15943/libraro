@extends('layouts.learner')
@section('content')


<!-- Breadcrumb -->
<div class="row justify-content-center mb-4">
    <div class="col-lg-12">
        <div class="table-responsive">
            <table class="table text-center datatable">
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Plan Type</th>
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
                    @if(isset($transaction))
                    
                    @foreach($transaction as $key => $value)
                    @php
                        $plan=App\Models\Plan::withoutGlobalScopes()->where('id',$value->plan_id)->first();
                        $planType=App\Models\PlanType::withoutGlobalScopes()->where('id',$value->plan_type_id)->first();
                      
                    @endphp
                    <tr>
                        <td>{{$key+1}}</td>
                        <td>{{$planType ? $planType->name : ''}}</td>
                        <td><span class="premium p-2 rounded text-white">{{$plan ? $plan->name : ''}}</span></td>
                        <td><i class="fa fa-inr"></i> {{$value->total_amount}}</td>
                        <td><i class="fa fa-inr"></i> {{$value->paid_amount}}</td>
                        <td>{{$value->transaction_id ? $value->transaction_id : 'NA'}}</td>
                        <td>{{$value->paid_date}}</td>
                        <td>{{
                           $value->payment_mode == 1 ? 'Online ' : 'Offline '
                           
                        }} 
                        Paid
                        </td>

                        <td>@if($value->is_paid==1)
                            <span class="text-success">Success</span>
                            @else
                            <span class="text-danger">Failed</span>
                            @endif
                        </td>
                      
                        <td>
                            <ul class="actionalbls">
                                <li>

                                    <form action="{{ route('fee.generateReceipt') }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <input type="hidden" id="custId" name="id" value="{{ $value->id }}">
                                        <input type="hidden" name="type" value="learner">

                                        <button type="submit">
                                            <i class="fa fa-print"></i>
                                        </button>
                                    </form>

                                </li>
                            </ul>
                        </td>
                        
                    </tr>
                    @endforeach
                    @else
                    <tr>
                        <td colspan="9">No Data Found</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection