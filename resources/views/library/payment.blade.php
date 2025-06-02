@extends('layouts.library')

@section('content')


<!-- Breadcrumb -->
@if($iscomp==false && !$is_expire)
<div class="row">
    <div class="col-lg-12">
        <div class="steps">
            <ul>
                <li>
                    <a href="{{ ($checkSub) ? '#' : route('subscriptions.choosePlan')  }}">Pick Your Perfect Plan</a>
                </li>
                <li class="active">
                    <a href="{{ ($ispaid) ? route('subscriptions.payment')  : '#' }}">Make Payment</a>
                </li>
                <li>
                    <a href="{{ ($ispaid ) ? route('profile') : '#' }}">Update Profile</a>
                </li>
                <li>
                    <a href="{{ ($checkSub && $ispaid && $isProfile) ? route('library.master') : '#' }}">Configure Library</a>
                </li>
            </ul>
        </div>
    </div>
</div>


<div class="row mb-4">
    <div class="col-lg-12">
        <h2 class="text-center typing-text">Secure your plan in just one step!</h2>
    </div>
</div>
@endif
<div class="row justify-content-center mb-4">
    <div class="col-lg-7">
        <div class="payment-detaile ">


            <!-- .basic | .standard | .premium  use these classes conditionllly-->
            <div class="paymentinfo basic">
                <div class="plan-info">
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <span>Subscription Name</span>
                            <h4>{{$plan ? $plan->name : $month->plan}}</h4>
                        </div>
                        <div class="col-lg-6">
                            <span>Subscription Type</span>
                            @if($month->month==1)
                            <h4>{{ 'MONTHLY'}}</h4>
                            @else
                            <h4>{{ $month->month}} Months</h4>
                            @endif
                        </div>
                        <div class="col-lg-6">
                            <span>Subscription Price</span>
                            <h4>{{ $month->amount}}</h4>
                        </div>
                        @if($month->start_date)
                        <div class="col-lg-6">
                            <span>Subscription Start Date</span>
                            <h4>{{ $month->start_date}}</h4>
                        </div>
                        @endif
                        @if($month->end_date)
                        <div class="col-lg-6">
                            <span>Subscription End Date</span>
                            <h4>{{ $month->end_date}}</h4>
                        </div>
                        @endif

                    </div>

                </div>
            </div>



            <div class="payment-information">
                <h4 class="mb-4">Plan Features</h4>
                @php
                $premiumSub=App\Models\Subscription::where('id',3)->first();
                $subscribedPermissions = $data->subscription->permissions->pluck('name')->toArray();
                @endphp
                <ul class="plan-features contents">
                    @foreach($premiumSub->permissions as $permission)
                    @if(in_array($permission->name, $subscribedPermissions))
                    <!-- Check mark for subscribed permissions -->
                    <li><i class="fa-solid fa-check text-success me-2"></i>{{ $permission->name }}</li>
                    @else
                    <!-- Cross mark for non-subscribed permissions -->
                    <li><i class="fa-solid fa-xmark text-danger me-2"></i>{{ $permission->name }}</li>
                    @endif
                    @endforeach
                </ul>

            </div>

        </div>

    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="col-lg-12">
                <h4 class="text-center mb-4">Order Summery</h4>
                <div class="row g-4">
                    <div class="col-lg-8">
                        Amount Paid
                    </div>
                    <div class="col-lg-4 text-end">
                        <i class="fa fa-inr"></i> {{ $month->amount}}
                    </div>
                    <!--GST and Discount insert in table from API call for created according -->
                    <div class="col-lg-8">
                        {{ $month->discount ?? 0}}% Discount (<a href="">Offer & Discounts</a>)
                    </div>
                    <div class="col-lg-4 text-end">
                        <i class="fa fa-inr"></i> {{$discount_amount}}
                    </div>
                    <div class="col-lg-8">
                        {{ $month->gst ?? 0}}% GST
                    </div>
                    <div class="col-lg-4 text-end">
                        <i class="fa fa-inr"></i> {{$gst_amount}}
                    </div>



                    <div class="col-lg-8">
                        <b>Total Amount to Pay</b>
                    </div>
                    <div class="col-lg-4 text-end">
                        <b><i class="fa fa-inr"></i> {{ $month->paid_amount}}</b>
                    </div>

                </div>

            </div>
        </div>
        <div class="dicount-cupon mt-4">
            Discount Cupon Code
        </div>
        <form action="{{route('library.payment.store')}}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="card mt-4">
                <h4 class="mb-3 text-center">Transaction Summery</h4>
                <div class="row g-4">
                    <div class="col-lg-6">
                        <span>Transaction Date</span>
                        <input type="date" name="transaction_date" class="form-control" value="{{ old('transaction_date', date('Y-m-d')) }}">
                        @if($errors->has('transaction_date'))
                        <span class="text-danger">{{ $errors->first('transaction_date') }}</span>
                        @endif

                    </div>

                    <div class="col-lg-6">
                        <span>Transaction Id</span>
                        <input type="text" name="transaction_id" class="form-control" value="{{ old('transaction_id', mt_rand(10000000, 99999999)) }}">
                        @if($errors->has('transaction_id'))
                        <span class="text-danger">{{ $errors->first('transaction_id') }}</span>
                        @endif
                    </div>

                    <div class="col-lg-12">
                        <span>Payment Method</span>
                        <select name="payment_method" class="form-select">
                            <option value="">Select Mode</option>
                            <option value="1" {{ old('payment_method') == 'Online' ? 'selected' : '' }}>Online</option>
                            {{-- <option value="2" {{ old('payment_method') == 'Offline' ? 'selected' : '' }}>Offline</option> --}}
                        </select>
                        @if($errors->has('payment_method'))
                        <span class="text-danger">{{ $errors->first('payment_method') }}</span>
                        @endif
                    </div>
                </div>

            </div>
            <div class="row justify-content-center mt-3">
                <div class="col-lg-12">

                    <input type="hidden" name="library_transaction_id" value="{{$transactionId}}">
                    <button type="submit" class="btn btn-primary btn-block button" id="pay-btn"> Make Payment </button>

                </div>
            </div>
        </form>
    </div>
</div>
@if($ispaid)
<div class="row mb-4">
    <h4 class="py-4">Transaction Detials</h4>
    <div class="col-lg-12">
        <div class="table-responsive">
            <table class="table text-center data-table">
                <thead>
                    <tr>
                        <th>S.No.</th>
                        <th>Subscription Name</th>
                        <th>Subscription Amt</th>
                        <th>Discount</th>
                        <th>Paid Amt</th>
                        <th>Transaction Id</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>


                    @if($all_transaction->count() > 0)
                    @foreach($all_transaction as $index => $transaction)

                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $transaction->subscription->name ?? 'N/A' }}</td>
                        <td>{{ $transaction->amount }}</td>
                        <td>{{ $transaction->discount ?? 'N/A' }}%</td> <!-- Assuming there's a discount field -->
                        <td>{{ $transaction->paid_amount }}</td>
                        <td>{{ $transaction->transaction_id ?? $transaction->id }}</td> <!-- Assuming transaction_id is stored -->
                        <td>
                            <ul class="actionalbls">
                                <li>
                                    <form action="{{ route('fee.generateReceipt') }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <input type="hidden" id="custId" name="id" value="{{ $transaction->id }}">
                                        <input type="hidden" name="type" value="library">

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
                        <td colspan="7">No transactions found.</td>
                    </tr>
                    @endif
                </tbody>
            </table>

        </div>
    </div>
</div>
@endif

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.concat.min.js"></script>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
    (function($) {
        $(window).on("load", function() {
            $(".contents").mCustomScrollbar({
                theme: "dark",
                scrollInertia: 300,
                axis: "y",
                autoHideScrollbar: false, // Keeps
            });
        });
    })(jQuery);
</script>


@endsection