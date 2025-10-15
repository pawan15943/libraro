@extends('layouts.library')
@section('content')

@if($learners->isEmpty())
<p class="not-found info-message">
<span class="close-btn" onclick="this.parentElement.style.display='none';">&times;</span>

There is currently no history available for this seat for any learners.</p>
@else


<p><b>{{ $learners->total() }} Records for {{ $learners->perPage() }} per page</b></p>
    @foreach($learners as $value)
   
    
        @php
        $planStatus = getPlanStatusDetails($value->plan_end_date);
        $transaction = learnerTransaction($value->learner_id, $value->id);      

        if ($transaction && isset($transaction->pending_amount)) {
            $due_date = DB::table('learner_pending_transaction')
                ->where('learner_id', $value->id)
                ->where('status', 0)
                ->where('pending_amount', $transaction->pending_amount)
                ->select('due_date')
                ->first();
        } else {
            $due_date = null;
        }
        $learner=myLearner($value->learner_id);
        $operation = optional(getLearnerOperation($value->id))->operation;
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
                            <span class="extended"> Closed Seat on {{ $value->plan_end_date ? date('j M Y', strtotime($value->plan_end_date)) : '' }}</span>
                        @elseif($operation == 'deleteSeat')
                            <span class="extended"> Deleted Seat</span>
                        @else
                            {!! getUserStatusDetails($value->plan_end_date) !!}
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
                                <span class="{{$planStatus['class']}} ps-1">{{$planStatus['status']}}</span>

                            </h4>
                            <span>UID : <a href="{{route('learners.show',$learner->id)}}">{{$learner->learner_no}}</a> &nbsp; | &nbsp; M : <a href="tel:+91-{{$learner->mobile}}">+91-{{$learner->mobile}}</a> </span>
                            <span class="d-block">E: <a href="mailto:{{$learner->email}}"> {!! $learner->email ? $learner->email : '<i class="fa-solid fa-times text-danger"></i> Email ID Not Available' !!} </a></span>
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
                                    @if(!empty(learnerTransaction($value->learner_id,$value->id)->pending_amount) && learnerTransaction($value->learner_id,$value->id)->pending_amount==0)
                                    <span class="payment" data-bs-title="Popover title" data-bs-content="And here’s some amazing content. It’s very engaging. Right?">Fully Paid</span>

                                    <form action="{{ route('fee.generateReceipt') }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $value->learner_id ?? 'NA'}}">
                                        <input type="hidden" name="type" value="learner">
                                        <button type="submit">
                                            <i class="fa fa-download receipt"></i>
                                        </button>
                                    </form>

                                    @elseif(empty(learnerTransaction($value->learner_id,$value->id)->pending_amount))
                                    <span></span>
                                    @elseif( pending_amt($value->learner_detail_id))
                                    <a href="{{ route('learner.pending.payment', ['id' => $value->id]) }}" class="text-danger d-block">
                                        @if(overdue($value->id,learnerTransaction($value->learner_id,$value->id)->pending_amount))
                                        <span class="extended" data-bs-title="Popover title" data-bs-content="And here’s some amazing content. It’s very engaging. Right?">Overdue {{ rtrim(rtrim(number_format(optional(learnerTransaction($value->learner_id,$value->id))->pending_amount, 2, '.', ''), '0'), '.') }}({{date('j M Y', strtotime($due_date->due_date))}})</span>
                                        @else
                                        <span class="extended" data-bs-title="Popover title" data-bs-content="And here’s some amazing content. It’s very engaging. Right?"> {{ rtrim(rtrim(number_format(optional(learnerTransaction($value->learner_id,$value->id))->pending_amount, 2, '.', ''), '0'), '.') }}({{$due_date->due_date}})</span>
                                        @endif
                                    </a>

                                    @elseif(paylater($value->id))
                                    <span class="extended" data-bs-title="Popover title" data-bs-content="And here’s some amazing content. It’s very engaging. Right?">Pay Later</span>
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