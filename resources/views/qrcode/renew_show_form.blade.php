@extends('sitelayouts.layout')
@section('content')


<div class="sacnd-data py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <h4 class="mb-3 text-center">Renew Plan for {{ $customer->name }}</h4>

                <div class="card p-3">
                    <div class="row">
                        <div class="col-md-6"><strong>Mobile:</strong> {{ $customer->mobile }}</div>
                        <div class="col-md-6"><strong>Seat No:</strong> {{ $customer->seat_no ?? 'GEN' }}</div>

                        <div class="col-md-6"><strong>Current Plan:</strong> {{ $customer_detail->plan->name ?? 'N/A' }}</div>
                        <div class="col-md-6"><strong>Plan Type:</strong> {{ $customer_detail->planType->name ?? 'N/A' }}</div>

                        <div class="col-md-6"><strong>Start Date:</strong> {{ $customer_detail->plan_start_date }}</div>
                        <div class="col-md-6"><strong>End Date:</strong> {{ $customer_detail->plan_end_date }}</div>
                    </div>
                </div>

                @if($transaction)
                
                <h5 class="mb-3 mt-4 text-center">Last Transaction Summary</h5>
                <table class="table table-bordered m-0">
                    <thead>
                        <tr>
                            <th>Plan Price</th>
                            <th>Locker</th>
                            <th>Discount</th>
                            <th>Total</th>
                            <th>Paid</th>
                            <th>Pending</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ $customer_detail->plan_price_id }}</td>
                            <td>{{ $transaction->locker_amount }}</td>
                            <td>{{ $transaction->discount_amount }}</td>
                            <td>{{ $transaction->total_amount }}</td>
                            <td>{{ $transaction->paid_amount }}</td>
                            <td>{{ $transaction->pending_amount }}</td>
                        </tr>
                    </tbody>
                </table>

                <div class=" text-center py-4">
                    <h5>Amount to Pay:
                        <strong>{{ $transaction->total_amount }}</strong>
                    </h5>
                </div>
                @endif
                <!-- resources/views/booking/form.blade.php -->
                <form action="{{ route('booking.store', $branch->uuid) }}" method="POST" class="text-center">
                    @csrf

                    {{-- Required fields for validation --}}
                    <input type="hidden" name="renewal" value="1">
                    <input type="hidden" name="name" value="{{ $customer->name }}">
                    <input type="hidden" name="email" value="{{ $customer->email }}">
                    <input type="hidden" name="mobile" value="{{ $customer->mobile }}">

                    <input type="hidden" name="dob" value="{{ $customer->dob }}">
                    <input type="hidden" name="seat_no" value="{{ $customer->seat_no}}">

                    <input type="hidden" name="plan_id" value="{{ $customer_detail->plan_id }}">
                    <input type="hidden" name="plan_type_id" value="{{ $customer_detail->plan_type_id }}">
                    <input type="hidden" name="plan_price_id" value="{{ $customer_detail->plan_price_id }}">

                    {{-- New start & end date (maybe extended 1 month or per plan rules) --}}
                    <input type="hidden" name="plan_start_date" value="{{ \Carbon\Carbon::parse($customer_detail->plan_end_date)->addDay()->toDateString() }}">


                    <input type="hidden" name="payment_mode" value="online"> {{-- default or choose --}}

                    {{-- Internal tracking --}}
                    <input type="hidden" name="learner_detail_id" value="{{ $customer_detail->id }}">
                    <input type="hidden" name="learner_transaction_id" value="{{ $transaction->id }}">

                    <button type="submit" class="btn btn-success">Proceed to Pay</button>
                </form>
            </div>
        </div>

    </div>
</div>


<!-- DataTables JS -->
@endsection
