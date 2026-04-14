@extends('layouts.library')
@section('content')

@if($learners->isEmpty())
<p class="not-found info-message">
<span class="close-btn" onclick="this.parentElement.style.display='none';">&times;</span>

There is currently no history available for this General seat for any learners.</p>
@else


<p><b>{{ $learners->total() }} Records for {{ $learners->perPage() }} per page</b></p>
    @foreach($learners as $value)
   
    
        @php
        $planStatus = getPlanStatusDetails($value->plan_end_date);
        $learner_detail_id=$value->id;
        $transaction = learnerTransaction($value->learner_id, $learner_detail_id);      

        if ($transaction && isset($transaction->pending_amount) && $transaction->due_date) {
    
        $due_date = $transaction->due_date;
        } else {
        
        $due_date = null;
        }
        $learner=myLearner($value->learner_id);
        $operation = optional(getLearnerOperation($learner_detail_id))->operation;
        $operationDate=optional(getLearnerOperation($learner_detail_id))->created_at;
        $learner_id=$value->learner_id;
        @endphp
        <div class="row">
            <div class="col-lg-12">
                <div class="seat-info bg-white">
                    <div class="seat-no">

                        @if(!empty($learner) && !empty($learner->seat_no))
                            <span>Seat No. {{ $learner->seat_no }}</span>
                        @else
                            <span>GEN</span>
                        @endif

                        @if($operation == 'closeSeat')
                        <span class="extended"> Closed Seat on {{ $operationDate ? date('j M Y', strtotime($operationDate)) : '' }}</span>
                        @elseif($operation == 'deleteSeat' && $value->deleted_at !=null)
                        <span class="extended"> Deleted Seat on {{ $operationDate ? date('j M Y', strtotime($operationDate)) : '' }}</span>
                        @else
                        {!! getUserStatusWithSpan($value->plan_end_date,$learner_id) !!}
                        @endif

                    </div>
                    
                    <div class="seat-actions"></div>
                    <div class="seat-informarion">
                        @if(!empty($learner) && !empty($learner->profile_picture))
                            <img src="{{ asset($learner->profile_picture) }}" alt="profile">
                        @else
                            <img src="{{ asset('public/img/student_profile.jpeg') }}" alt="profile">
                        @endif

                        <div class="information">
                            <h4>{{$learner->name}}
                                 @if($operation == 'closeSeat')
                                <span class="extended">Closed</span>
                                @elseif($operation == 'deleteSeat' && $value->deleted_at !=null)
                                <span class="extended">Deleted</span>
                                @else
                                <span class=" {{ $planStatus['class'] == 'expired' ? 'expired' : ($planStatus['class'] == 'extended' ? 'extedned' : 'actives') }} ps-1">{{$planStatus['status']}}</span>
                                @endif
                                

                            </h4>
                            <span>UID : <a href="{{route('learners.show',$learner_id)}}">{{$learner->learner_no}}</a> &nbsp; | &nbsp; M : <a href="tel:+91-{{$learner->mobile}}">+91-{{ display_learner_mobile($learner->mobile) }}</a> </span>
                            <span class="d-block">E: <a href="mailto:{{$learner->email}}"> @if($learner->email) {{ display_learner_email($learner->email) }} @else <i class="fa-solid fa-times text-danger"></i> Email ID Not Available @endif </a></span>
                        </div>
                    </div>
                    <div class="plan-info">
                        <ul>
                            <li>
                                <span>Plan</span>
                                <p>{{$value->plan->name??''}}</p>
                            </li>
                            <li>
                                <span>Plan Type</span>
                                <p>{{$value->planType->name ?? ''}}</p>
                            </li>
                            <li>
                                <span>Plan Start Date</span>
                                <p>{{ $value->plan_start_date ? date('j M Y', strtotime($value->plan_start_date)) : '' }}</p>

                            </li>
                            <li>
                                <span>Plan End Date</span>
                                <p>{{ $value->plan_end_date ? date('j M Y', strtotime($value->plan_end_date)) : '' }}</p>
                            </li>
                            <li>
                                <span>Payment Status</span>
                                <div class="d-flex g-1">
                                    @if(paylater($learner_detail_id) && learnerTransaction($learner_id,$learner_detail_id)->pending_amount!=0)
                                    <a href="{{ route('learner.pending.payment', ['id' => $transaction->id]) }}" class="text-danger d-block">
                                        <span class="extended" data-bs-title="Popover title" data-bs-content="And here’s some amazing content. It’s very engaging. Right?">
                                            PayLater {{ rtrim(rtrim(number_format(   (learnerTransaction($learner_id, $learner_detail_id))->pending_amount, 2, '.', ''), '0'), '.') }}

                                        </span>
                                    </a>
                                    @elseif(!empty(learnerTransaction($learner_id,$learner_detail_id)->pending_amount) && learnerTransaction($learner_id,$learner_detail_id)->pending_amount==0)
                                    <span class="payment" data-bs-title="Popover title" data-bs-content="And here’s some amazing content. It’s very engaging. Right?">Fully Paid</span>

                                    <form action="{{ route('fee.generateReceipt') }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <input type="hidden" name="learner_id" value="{{$learner_id}}">
                                        <input type="hidden" name="id" value="{{ learnerTransaction($learner_id,$learner_detail_id)->id ?? 'NA'}}">
                                        <input type="hidden" name="type" value="learner">
                                        <button type="submit" class="noLoader">
                                            <i class="fa fa-download receipt"></i>
                                        </button>
                                    </form>


                                    @elseif(empty(learnerTransaction($learner_id,$learner_detail_id)->pending_amount))
                                    <span></span>

                                    @elseif( pending_amt($learner_detail_id))

                                    <a href="{{ route('learner.pending.payment', ['id' => $transaction->id]) }}" class="text-danger d-block">

                                        @if(is_object($due_date) && !empty($due_date->due_date) && overdue($learner_id, learnerTransaction($learner_id, $learner_detail_id)->pending_amount))
                                        <span class="extended" data-bs-title="Popover title" data-bs-content="And here’s some amazing content. It’s very engaging. Right?">Overdue {{ rtrim(rtrim(number_format(optional(learnerTransaction($learner_id, $learner_detail_id))->pending_amount, 2, '.', ''), '0'), '.') }}({{date('j M Y', strtotime($due_date->due_date))}})</span>
                                        @else
                                        <span class="extended" data-bs-title="Popover title" data-bs-content="And here’s some amazing content. It’s very engaging. Right?">
                                            Pending {{ rtrim(rtrim(number_format(   (learnerTransaction($learner_id, $learner_detail_id))->pending_amount, 2, '.', ''), '0'), '.') }}

                                        </span>

                                        @endif
                                    </a>
                                    @endif
                                </div>
                            </li>
                            <li>
                                <span>Locker</span>
                                @if(optional($transaction)->locker_amount)
                                    <p>Yes – #{{ $transaction->locker_amount }} Paid</p>
                                @else
                                    <p>No</p>
                                @endif


                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
 
    @endforeach
   

    @if ($learners->lastPage() > 1)
    <ul class="paginations">
        {{-- Prev Button --}}
        <li>
            <a href="{{ $learners->onFirstPage() ? '#' : $learners->previousPageUrl() }}" class="w-auto px-3 text-muted {{ $learners->onFirstPage() ? 'disabled' : '' }}">
                Prev
            </a>
        </li>

        {{-- Page Numbers --}}
        @for ($i = 1; $i <= $learners->lastPage(); $i++)
            <li>
                <a href="{{ $learners->url($i) }}" class="{{ $learners->currentPage() == $i ? 'active' : '' }}">
                    {{ $i }}
                </a>
            </li>
            @endfor

            {{-- Next Button --}}
            <li>
                <a href="{{ $learners->hasMorePages() ? $learners->nextPageUrl() : '#' }}" class="w-auto px-3 text-muted {{ $learners->hasMorePages() ? '' : 'disabled' }}">
                    Next
                </a>
            </li>
    </ul>
    @endif
@endif
   

@endsection