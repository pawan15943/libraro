@extends('sitelayouts.layout')
@section('content')
<style>
    header,
    footer {
        display: none;
    }

    .online-qr-booking {
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        background: #efefff;

    }

    .online-booking {
        border: none !important;
    }

    .logo {
        width: 180px;
        padding: .5rem 0;
        margin: 0 auto;
        display: block;
        margin-bottom: 1rem;
    }

    .invalid-feedback {
        font-weight: 500;
    }

    *{
        font-family: 'Outfit','sans-sarif';
    }

</style>
<section class="py-3 online-qr-booking">

    <div class="container py-4">

        {{-- Page Header --}}


        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8 col-12">
                <a href="{{'/'}}"><img src="{{ asset('public/img/libraro.webp') }}" alt="logo" class="logo"></a>
                <div class="online-booking">
                    <span class="steps">Step-2</span>
                    <h4 class="mb-4 text-center">Verify Membership Detials</h4>
                    {{-- Customer & Plan Info --}}
                    <div class="card shadow-sm mb-3">
                        <div class="card-header bg-light fw-semibold">
                            Customer Details
                        </div>
                        <div class="card-body small">
                            <div class="row g-2">
                                <div class="col-6"><strong>Seat No:</strong></div>
                                <div class="col-6 text-end">{{ $learnerSeat ?? 'GEN' }}</div>

                                <div class="col-6"><strong>Name:</strong></div>
                                <div class="col-6 text-end">{{ $customer->name }}</div>

                                <div class="col-6"><strong>Mobile:</strong></div>
                                <div class="col-6 text-end">{{ $customer->mobile }}</div>

                                <div class="col-6"><strong>Current Plan:</strong></div>
                                <div class="col-6 text-end">{{ $customer_detail->plan->name ?? 'N/A' }}</div>

                                <div class="col-6"><strong>Plan Type:</strong></div>
                                <div class="col-6 text-end">{{ $customer_detail->planType->name ?? 'N/A' }}</div>

                                <div class="col-6"><strong>Start Date:</strong></div>
                                <div class="col-6 text-end">{{ $customer_detail->plan_start_date }}</div>

                                <div class="col-6"><strong>End Date:</strong></div>
                                <div class="col-6 text-end text-danger fw-bold">
                                    {{ $customer_detail->plan_end_date }}
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($transactions)
                    {{-- Last Transaction --}}
                    <div class="card shadow-sm mb-3">
                        <div class="card-header bg-light fw-semibold">
                            Last Transaction Summary
                        </div>
                        <div class="card-body p-0">

                            {{-- Desktop Table --}}
                            <div class="table-responsive d-none d-md-block">
                                <table class="table table-bordered mb-0 small">
                                    <thead class="table-secondary">
                                        <tr>
                                            <th>Plan</th>
                                            <th>Locker</th>
                                            <th>Discount</th>
                                            <th>Total</th>
                                            <th>Paid</th>
                                            <th>Pending</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($transactions as $key => $transaction)
                                        <tr>
                                             <td>{{ rtrim(rtrim(number_format($transaction->plan_price_id, 2, '.', ''), '0'), '.') }}</td>
                                           
                                            <td>{{ $transaction->locker_amount }}</td>
                                           <td>{{ rtrim(rtrim(number_format($transaction->discount_amount, 2, '.', ''), '0'), '.') }}</td>
                                            <td>{{ rtrim(rtrim(number_format($transaction->total_amount, 2, '.', ''), '0'), '.') }}</td>
                                            <td>{{ rtrim(rtrim(number_format($transaction->paid_amount, 2, '.', ''), '0'), '.') }}</td>
                                            <td>{{ rtrim(rtrim(number_format($transaction->pending_amount, 2, '.', ''), '0'), '.') }}</td>

                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            {{-- Mobile View --}}
                            <div class="d-md-none p-3 small">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Plan Price</span>
                                    <strong>{{ $customer_detail->plan_price_id }}</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Locker</span>
                                    <strong>{{ $transaction->locker_amount }}</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Discount</span>
                                    <strong>{{ $transaction->discount_amount }}</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Paid</span>
                                    <strong class="text-success">{{ $transaction->paid_amount }}</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Pending</span>
                                    <strong class="text-danger">{{ $transaction->pending_amount }}</strong>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <span>Total</span>
                                    <strong>{{ $transaction->total_amount }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                     @endif
                    <div class="col-lg-6">
                            <label for="">Previous Pending Amount <span>*</span></label>
                        <input type="text" class="form-control @error('previous_pending') is-invalid @enderror" name="previous_pending"  value="{{ rtrim(rtrim(number_format(totalPending($customer->id), 2, '.', ''), '0'), '.') }}" readonly>
                    </div>
                   
                    <div class="col-lg-6">
                         <label for="">Amount to Pay<span>*</span></label> 
                        <input type="text" class="form-control @error('total_amount') is-invalid @enderror" name="total_amount"   value="{{ rtrim(rtrim(number_format($lastamount->total_amount + totalPending($customer->id), 2, '.', ''), '0'), '.') }}" readonly> 

                    </div>
                    {{-- Amount --}}
                    <form action="{{ route('booking.store', $branch->uuid) }}" method="POST">
                        @csrf
                    <div class="col-lg-6">
                         <label for="">Final Payble amount<span>*</span></label> 
                        <input type="text" class="form-control @error('paid_amount') is-invalid @enderror" name="paid_amount"   value="{{ old('paid_amount',  $lastamount->total_amount+totalPending($customer->id))}}"> 

                    </div>
                    

                    {{-- Renew Form --}}
                    

                        {{-- Hidden Fields (UNCHANGED) --}}
                        <input type="hidden" name="renewal" value="1">
                        <input type="hidden" name="name" value="{{ $customer->name }}">
                        <input type="hidden" name="email" value="{{ $customer->email }}">
                        <input type="hidden" name="mobile" value="{{ $customer->mobile }}">
                        <input type="hidden" name="dob" value="{{ $customer->dob }}">
                        <input type="hidden" name="seat_no" value="{{ $customer->seat_no }}">
                        <input type="hidden" name="plan_id" value="{{ $customer_detail->plan_id }}">
                        @foreach(\App\Support\LearnerShiftSupport::planTypeIdsForLearnerDetail($customer_detail) as $renewPtId)
                        <input type="hidden" name="plan_type_id[]" value="{{ $renewPtId }}">
                        @endforeach
                        <input type="hidden" name="plan_price_id" value="{{ $customer_detail->plan_price_id }}">
                        <input type="hidden" name="plan_start_date" value="{{ \Carbon\Carbon::parse($customer_detail->plan_end_date)->addDay()->toDateString() }}">
                        <input type="hidden" name="payment_mode" value="online">
                        <input type="hidden" name="learner_detail_id" value="{{ $customer_detail->id }}">
                        <input type="hidden" name="learner_transaction_id" value="{{ $lastamount->id }}">

                        {{-- CTA --}}
                        <button type="submit" class="btn btn-primary button">Proceed to Pay <i class="fa fa-long-arrow-right ms-2"></i></button>
                    </form>

                </div>
            </div>
        </div>
    </div>
</section>

@endsection
