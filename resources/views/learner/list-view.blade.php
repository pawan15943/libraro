@extends('layouts.library')
@section('content')

<!-- Content Header (Page header) -->
@php
    use Carbon\Carbon;
    use App\Helpers\HelperService; 
    if(request('type') === 'total_booking'){
        $text='Total Slots Bookings';
    }elseif(request('type') === 'active_booking'){
        $text='Active Slots';
    }elseif(request('type') === 'expired_seats'){
        $text='Expired Slots';
    }elseif(request('type') === 'thisbooking_slot'){
        $text='This month total slots';
    }elseif(request('type') === 'booing_slot'){
        $text='This month Booked slots';
    }elseif(request('type') === 'till_previous_book'){
        $text='Previous month booked slots';
    }elseif(request('type') === 'expire_booking_slot'){
        $text='This month Expired';
    }elseif(request('type') === 'expired_in_five'){
        $text='Expired in 5 Days';
    }elseif(request('type') === 'extended_seat'){
        $text='Extended Seats';
    }elseif(request('type') === 'online_paid'){
        $text='Online Paid';
    }elseif(request('type') === 'offline_paid'){
        $text='Offline Paid';
    }elseif(request('type') === 'other_paid'){
        $text='Pay Later';
    }elseif(request('type') === 'swap_seat'){
        $text='Swap Seats';
    }elseif(request('type') === 'learnerUpgrade'){
        $text='Upgrade Seats';
    }elseif(request('type') === 'reactive_seat'){
        $text='Reactive Seats';
    }elseif(request('type') === 'renew_seat'){
        $text='Renew Seats';
    }elseif(request('type') === 'close_seat'){
        $text='Close Seats';
    }elseif(request('type') === 'delete_seat'){
        $text='Delete Seats';
    }elseif(request('type') === 'change_plan_seat'){
        $text='Change Plan';
    }else{
        $text='';
    }
   
@endphp
<div class="row mb-4">
    <div class="col-lg-12">
        <b class="d-block pb-3">{{$text}} for  {{ request('month') }}/{{ request('year') }}: [{{$result->count()}}]</b>
        <div class="table-responsive">
            <table class="table text-center datatable border-bottom" id="datatable">
                <thead>
                    <tr>
                        <th>Seat No.</th>
                        <th>Learner Info</th>
                        <th>Contact Info</th>
                        <th>Active Plan</th>
                        <th>Expired On</th>
                        <th>Status</th>
                       
                    </tr>
                </thead>
                <tbody>
                    
                    
                    @foreach ($result as $data)
                   
                    @if($data->operation_date)
                        @php
                            $learner=App\Models\Learner::withTrashed()->where('id',$data->learner_id)->first();
                            $learner_detail=App\Models\LearnerDetail::withTrashed()->where('id',$data->learner_detail_id)->with(['plan','planType'])->first();
                           
                            $operation = DB::table('learner_operations_log')->where('learner_id',$data->learner_id)->where('learner_detail_id',$data->learner_detail_id)->where('operation',$data->operation)->whereDate('created_at',$data->operation_date)->first();
                            $operationDetails = HelperService::getOperationDetails($operation);
                            
                           
                        @endphp
                         <tr>
                            <td>{{ $learner->seat_no ?? 'GEN'}}<br>
                               {!! $operationDetails['message'] !!}
                                    </td> <!-- Seat No -->
                           
                            
                            <td><span class="uppercase truncate" data-bs-toggle="tooltip"
                                data-bs-title="{{$learner->name ?? ''}}" data-bs-placement="bottom">{{$learner->name ?? ''}}</span>
                            <br> <small>{{$learner->dob ?? ''}}</small>
                            </td>
                           
                            <td>
                            <span class="truncate"
                                data-bs-toggle="tooltip"
                                data-bs-title="{{ (isset($learner->email) && $learner->email) ? display_learner_email($learner->email) : 'Email ID Not Available' }}"
                                data-bs-placement="bottom">
                                @if(isset($learner->email) && $learner->email)
                                     {{ display_learner_email($learner->email) }}
                                @else
                                    <i class="fa-solid fa-times text-danger"></i>Email ID Not Available
                                @endif
                            </span>
                            <br>
                            <small>+91-{{ ($data->mobile ?? '') !== '' ? display_learner_mobile($data->mobile) : ' ' }}</small>
                            </td>
                            <td>
                                {{ $learner_detail->plan_start_date ?? 'N/A' }}<br>
                                    <small>{{ $learner_detail->plan->name ?? 'N/A' }}</small>
                            </td>
                            <td>
                                @php
                                    $_ldLabels = isset($learner_detail) ? \App\Support\LearnerShiftSupport::receiptLabelsForLearnerDetail($learner_detail) : ['subscription' => 'NA', 'shift_timing' => ''];
                                @endphp
                                {{ $learner_detail->plan_end_date ?? 'N/A' }}<br>
                                    <small>{{ $_ldLabels['subscription'] !== 'NA' ? $_ldLabels['subscription'] : ($learner_detail->planType->name ?? 'N/A') }}</small>
                                    @if($_ldLabels['shift_timing'] !== '')
                                    <br><small class="text-muted">{{ $_ldLabels['shift_timing'] }}</small>
                                    @endif
                            </td>
                            <td>
                                  
                                @if (isset($learner_detail) && $learner_detail->status == 1)
                                    Active <small>{{$learner_detail->is_paid==1 ? 'Paid' : 'Unpaid'}}</small><br>{{$operationDetails['operation_type']}}
                                @else
                                    Inactive <small>{{isset($learner_detail) && $learner_detail->is_paid==1 ? 'Paid' : 'Unpaid'}}</small><br>{{$operationDetails['operation_type']}}
                                @endif
                                <br>
                                @if($learner_detail)
                                {!! getUserStatusWithSpan($learner_detail->plan_end_date,$learner->id) !!}
                                @endif
                                   
                            </td>
                            
                            
                        </tr>
                    @elseif($data->learner)
                    <tr>
                        <td>{{ $data->learner->seat_no ?? 'GEN'}}</td> <!-- Seat No -->
                        
                        <td><span class="uppercase truncate" data-bs-toggle="tooltip"
                            data-bs-title="{{$data->learner->name}}" data-bs-placement="bottom">{{$data->learner->name}}</span>
                        <br> <small>{{$data->dob}}</small>
                        </td>
                       
                         <td>
                            <span class="truncate"
                                data-bs-toggle="tooltip"
                                data-bs-title="{{ (isset($data->email) && $data->email) ? display_learner_email($data->email) : 'Email ID Not Available' }}"
                                data-bs-placement="bottom">
                                @if(isset($data->email) && $data->email)
                                     {{ display_learner_email($data->email) }}
                                @else
                                    <i class="fa-solid fa-times text-danger"></i>Email ID Not Available
                                @endif
                            </span>
                            <br>
                            <small>+91-{{ ($data->mobile ?? '') !== '' ? display_learner_mobile($data->mobile) : ' ' }}</small>
                        </td>
                        <td>
                            {{ $data->plan_start_date ?? 'N/A' }}<br>
                                <small>{{ $data->plan->name ?? 'N/A' }}</small>
                        </td>
                        <td>
                            @php
                                $_dLabels = $data instanceof \App\Models\LearnerDetail ? \App\Support\LearnerShiftSupport::receiptLabelsForLearnerDetail($data) : ['subscription' => 'NA', 'shift_timing' => ''];
                            @endphp
                            {{ $data->plan_end_date ?? 'N/A' }}<br>
                                <small>{{ $_dLabels['subscription'] !== 'NA' ? $_dLabels['subscription'] : ($data->planType->name ?? 'N/A') }}</small>
                                @if($_dLabels['shift_timing'] !== '')
                                <br><small class="text-muted">{{ $_dLabels['shift_timing'] }}</small>
                                @endif
                        </td>
                        <td>
                                @php
                                    if($data->plan_end_date){
                                        $endDate =$data->plan_end_date;
                                    }elseif($data->learner->plan_end_date){
                                        $endDate =$data->learner->plan_end_date;
                                    }
                                  
                                @endphp
                                
                                @if ($data->status == 1 || $data->learner->status == 1)
                                    Active
                                @else
                                    Inactive
                                @endif
                                <br>
                               {!! getUserStatusWithSpan($endDate,$data->learner->id) !!}
                        </td>
                        
                        
                    </tr>
                    @elseif($data->max_plan_start_date)
                        @php
                            $learner_detail=App\Models\LearnerDetail::where('learner_id',$data->learner_id)->where('plan_start_date',$data->max_plan_start_date)->first();
                            $plan=App\Models\Plan::where('id',$learner_detail->plan_id)->first();
                            $planType=App\Models\planType::where('id',$learner_detail->plan_type_id)->first();
                        @endphp
                        <tr>
                            <td>{{ $data->seat_no ?? 'GEN' }}</td> <!-- Seat No -->
                            
                            <td><span class="uppercase truncate" data-bs-toggle="tooltip"
                                data-bs-title="{{$data->name}}" data-bs-placement="bottom">{{$data->name}}</span>
                            <br> <small>{{$data->dob}}</small>
                            </td>
                        
                          <td>
                            <span class="truncate"
                                data-bs-toggle="tooltip"
                                data-bs-title="{{ (isset($data->email) && $data->email) ? display_learner_email($data->email) : 'Email ID Not Available' }}"
                                data-bs-placement="bottom">
                                @if(isset($data->email) && $data->email)
                                     {{ display_learner_email($data->email) }}
                                @else
                                    <i class="fa-solid fa-times text-danger"></i>Email ID Not Available
                                @endif
                            </span>
                            <br>
                            <small>+91-{{ ($data->mobile ?? '') !== '' ? display_learner_mobile($data->mobile) : ' ' }}</small>
                        </td>

                            <td>
                                {{ $data->max_plan_start_date ?? 'N/A' }}<br>
                                    <small>{{ $plan->name ?? 'N/A' }}</small>
                            </td>
                            <td>
                                @php
                                    $_mxLabels = isset($learner_detail) ? \App\Support\LearnerShiftSupport::receiptLabelsForLearnerDetail($learner_detail) : ['subscription' => 'NA', 'shift_timing' => ''];
                                @endphp
                                {{ $data->max_plan_end_date ?? 'N/A' }}<br>
                                    <small>{{ $_mxLabels['subscription'] !== 'NA' ? $_mxLabels['subscription'] : ($planType->name ?? 'N/A') }}</small>
                                    @if($_mxLabels['shift_timing'] !== '')
                                    <br><small class="text-muted">{{ $_mxLabels['shift_timing'] }}</small>
                                    @endif
                            </td>
                            <td>
                                @php
                                    if($data->max_plan_end_date){
                                        $endDate =$data->max_plan_end_date;
                                    }
                                    $endDate = Carbon::parse($endDate);
                                    
                                @endphp
                                
                                @if ($learner_detail->status == 1)
                                    Active
                                @else
                                    Inactive
                                @endif
                                <br>
                                {!! getUserStatusWithSpan($endDate,$data->learner_id) !!}
                            </td>
                            
                            
                        </tr>
                    @else
                        <tr>
                            <td>{{ $data->seat_no ?? 'GEN'}}</td> <!-- Seat No -->
                            
                            <td><span class="uppercase truncate" data-bs-toggle="tooltip"
                                data-bs-title="{{$data->name}}" data-bs-placement="bottom">{{$data->name}}</span>
                            <br> <small>{{$data->dob}}</small>
                            </td>
                        
                            <td>
                            <span class="truncate"
                                data-bs-toggle="tooltip"
                                data-bs-title="{{ (isset($data->email) && $data->email) ? display_learner_email($data->email) : 'Email ID Not Available' }}"
                                data-bs-placement="bottom">
                                @if(isset($data->email) && $data->email)
                                     {{ display_learner_email($data->email) }}
                                @else
                                    <i class="fa-solid fa-times text-danger"></i>Email ID Not Available
                                @endif
                            </span>
                            <br>
                            <small>+91-{{ ($data->mobile ?? '') !== '' ? display_learner_mobile($data->mobile) : ' ' }}</small>
                            </td>
                            <td>
                                {{ $data->plan_start_date ?? 'N/A' }}<br>
                                    <small>{{ $data->plan->name ?? 'N/A' }}</small>
                            </td>
                            <td>
                                @php
                                    $_d2Labels = $data instanceof \App\Models\LearnerDetail ? \App\Support\LearnerShiftSupport::receiptLabelsForLearnerDetail($data) : ['subscription' => 'NA', 'shift_timing' => ''];
                                @endphp
                                {{ $data->plan_end_date ?? 'N/A' }}<br>
                                    <small>{{ $_d2Labels['subscription'] !== 'NA' ? $_d2Labels['subscription'] : ($data->planType->name ?? 'N/A') }}</small>
                                    @if($_d2Labels['shift_timing'] !== '')
                                    <br><small class="text-muted">{{ $_d2Labels['shift_timing'] }}</small>
                                    @endif
                            </td>
                            <td>
                                @php
                                    if($data->plan_end_date){
                                        $endDate =$data->plan_end_date;
                                    }elseif($data->learner->plan_end_date){
                                        $endDate =$data->learner->plan_end_date;
                                    }
                                    
                                @endphp
                                
                                @if ($data->status == 1)
                                    Active
                                @else
                                    Inactive
                                @endif
                                <br>
                                {!! getUserStatusWithSpan($endDate,$data->learner_id) !!}
                            </td>
                            
                            
                        </tr>
                    @endif
                    
                    @endforeach
                </tbody>
            </table>
          
        </div>
    </div>
</div>



@include('learner.script')
@endsection