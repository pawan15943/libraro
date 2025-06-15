@extends('layouts.library')

@section('content')

@if($iscomp==false && !$is_expire)
<div class="row">
    <div class="col-lg-12">
        <div class="steps">
            <ul>
                <li class="active">
                    <a href="{{ ($checkSub) ? '#' : route('subscriptions.choosePlan')  }}">Pick Your Perfect Plan</a>
                </li>
                <li >
                    <a href="{{ ($ispaid) ? route('subscriptions.payment')  : '#' }}">Make Payment</a>
                </li>
                <li >
                    <a href="{{ ($ispaid ) ? route('profile') : '#' }}">Update Profile</a>
                </li>
                <li >
                    <a href="{{ ($checkSub && $ispaid && $isProfile) ? route('library.master') : '#' }}">Configure Library</a>
                </li>
            </ul>
        </div>
    </div>
</div>
<div class="row mb-4">
    <div class="col-lg-12">
        <h2 class="text-center typing-text">Pick the plan that fits you best!</h2>
    </div>
</div>
@endif



<div class="row g-4 justify-content-center">
    <div class="col-lg-4 payment-mode">
        <label for="" class="m-auto d-block">Select Plan Mode <span>*</span></label>
        <select name="plan_mode" id="plan_mode" class="form-select">
            <option value="1">1 MONTHLY</option>
            <option value="3">3 MONTHLY</option>
            <option value="4">6 MONTHLY</option>
            <option value="2">1 YEARLY </option>
            <option value="5">2 YEARLY</option>
           
        </select>
    </div>
</div>


<div class="row mt-4 justify-content-center mb-4">
    @foreach($subscriptions as $subscription)
    <div class="col-lg-3">
        <div class="plan-box">
            @php

            $subscribedPermissions = $subscription->permissions->pluck('name')->toArray();
            @endphp
                @if ($subscription->id == Auth::user()->library_type)
                @php
                    if(Auth::user()->status == 0){
                        $text='Expired';
                        $class='text-danger';
                    }else{
                        $text='Active';
                        $class='text-success';
                    }
                    
                @endphp
                <h6 class="text-center bg-white">Current Plan <span class="{{$class}}">{{$text}} </span></h6>
                @else
                <h6></h6>
                @endif
            
            <h1 id="subscription_fees_{{$subscription->id}}"></h1> 
            <h4>{{$subscription->name}}</h4>
            <p class="m-0 text-white py-2 px-3 text-center plan-description" >{{$subscription->plan_description}}</p>
            <ul class="plan-features contents">
                @foreach($premiumSub->permissions as $permission)
                @if(in_array($permission->name, $subscribedPermissions))
                <li>
                    <div class="d-flex">
                        <i class="fa-solid fa-check text-success me-2"></i> {{ $permission->name }}
                    </div>
                </li>
                @else
                <li>
                    <div class="d-flex">
                        <i class="fa-solid fa-xmark text-danger me-2"></i> {{ $permission->name }}
                    </div>
                </li>
                @endif
                @endforeach
            </ul>
            <!-- <span class="showmore">Show More </span> -->
            <form id="payment-form" action="{{route('subscriptions.payment')}}" method="POST" >
                @csrf
                <input type="hidden" name="library_id" value="{{Auth::user()->id}}">
                <input type="hidden" name="subscription_id" id="subscription_id" value="{{$subscription->id}}">
                <input type="hidden" name="plan_mode" id="plan_mode_{{$subscription->id}}">
                <input type="hidden" name="price" id="price_{{$subscription->id}}">
                <button type="submit" class="btn btn-primary button ">BUY</button>
            </form>
        </div>
    </div>

    @endforeach
</div>



<!-- jQuery and AJAX -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).on('click', '.showmore', function() {
        var $planFeatures = $(this).closest('.plan-box').find('.plan-features');
        
        // Toggle the overflow-auto class
        $planFeatures.toggleClass('overflow-auto');

        // Change the button text between "Show More" and "Show Less"
        if ($planFeatures.hasClass('overflow-auto')) {
            $(this).text('Show Less');
        } else {
            $(this).text('Show More');
        }
    });



    $(document).ready(function() {
        
        var plan_mode = $('#plan_mode').find(":selected").val();
        console.log(plan_mode);
        
        subscription_price(plan_mode);
        
        $('#plan_mode').on('change', function() {
            var plan_mode = $(this).val();
            
            subscription_price(plan_mode);
           
        });
        function subscription_price(plan_mode){
           
            if (plan_mode) {
                $.ajax({
                    url: '{{ route('subscriptions.getSubscriptionPrice') }}',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="_token"]').attr('content')
                    },
                    type: 'GET',
                    data: {
                        "plan_mode": plan_mode
                    },
                    dataType: 'json',
                    success: function(response) {
                        console.log('response',response);
                        // Loop through each subscription price and dynamically update the HTML
                        response.subscription_prices.forEach(function(subscription) {
                            $('#subscription_fees_' + subscription.id).text(subscription.fees); 
                            $('#plan_mode_' + subscription.id).val(plan_mode)
                            $('#price_' + subscription.id).val(subscription.fees)
                        });
                    },
                    error: function(xhr) {
                        toastr.error('An error occurred. Please try again.');
                    }
                });
            }
        }   
    });
</script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.min.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.concat.min.js"></script>

<script>
    (function($) {
        $(window).on("load", function() {
            $(".contents").mCustomScrollbar();
        });
    })(jQuery);
</script>
@endsection
