@extends('layouts.library')

@section('content')

{{-- @if($iscomp==false && !$is_expire)
<div class="row">
    <div class="col-lg-12">
        <div class="steps">
            <ul>
                <li class="active">
                    <a href="{{ ($checkSub) ? '#' : route('subscriptions.choosePlan')  }}">Pick Your Perfect Plan</a>
                </li>

                <li>
                    <a href="{{ ($ispaid ) ? route('branch.configure.create') : '#' }}">Branch</a>
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
@endif --}}

<div class="choose-plan-page">

    <div class="pricing-head text-center">
        <h2 class="pricing-title">Pick the plan that fits you best</h2>
        <p class="pricing-subtitle">Simple, transparent pricing — switch or upgrade anytime.</p>
    </div>

    <div class="row g-4 justify-content-center">
        <div class="col-lg-4 payment-mode">
            <label for="plan_mode" class="m-auto d-block">Select Plan Mode <span>*</span></label>
            <select name="plan_mode" id="plan_mode" class="form-select">
                {{-- <option value="1">1 MONTHLY</option>
                <option value="3">3 MONTHLY</option>
                <option value="4">6 MONTHLY</option>
                <option value="2">1 YEARLY </option>
                <option value="5">2 YEARLY</option> --}}
                @foreach($month as $key => $value)
                    <option value="{{$key}}">{{$value}}</option>
                @endforeach

            </select>
        </div>
    </div>


    <div class="row mt-4 justify-content-center mb-4 g-4 pricing-grid">
        @foreach($subscriptions as $subscription)
        <div class="col-lg-4 col-md-6">
            <div class="plan-box {{ $loop->index === 1 ? 'plan-box--popular' : '' }}">
                @if($loop->index === 1)
                    <span class="plan-badge"><i class="fa-solid fa-star"></i> Most Popular</span>
                @endif
                @php
                 // Features of current subscription
                $subscriptionFeatures = $features->where('subscription_id', $subscription->id)->whereNull('deleted_at')->pluck('name')->toArray();

                // All unique features
                $allFeatures = $features->pluck('name')->unique()->toArray();
                @endphp

                <div class="plan-content">
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
                    <span class="plan-current-tag">Current Plan <b class="{{$class}}">{{$text}}</b></span>
                    @endif

                    <h4 class="plan-name">{{$subscription->name}}</h4>
                    <span class="d-block plan-subtitle" id="planDescription_{{$subscription->id}}">{{$subscription->plan_description}}</span>

                    <div class="plan-price-row">
                        <h1 id="subscription_fees_{{$subscription->id}}" class="plan-fees">--</h1>
                        <span class="plan-period" id="plan_period_{{$subscription->id}}"></span>
                    </div>
                    <div class="plan-slash-row" id="before_discount_fees_{{$subscription->id}}"></div>

                    <form id="payment-form-{{$subscription->id}}" action="{{route('payment.store')}}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="library_id" value="{{Auth::user()->id}}">
                        <input type="hidden" name="subscription_id" id="subscription_id_{{$subscription->id}}" value="{{$subscription->id}}">
                        <input type="hidden" name="plan_mode" id="plan_mode_{{$subscription->id}}">
                        <input type="hidden" name="price" id="price_{{$subscription->id}}">
                        <button type="submit" class="btn btn-primary button plan-cta">
                            Choose Plan <i class="fa-solid fa-arrow-right ms-1"></i>
                        </button>
                    </form>
                </div>

                <ul class="plan-features contents mt-4">
                     @foreach($allFeatures as $featureName)

                        @if(in_array($featureName, $subscriptionFeatures))
                            <li>
                                <div class="d-flex">
                                    <i class="fa-solid fa-check text-success me-2"></i>
                                {{ $featureName }}
                                </div>
                            </li>
                        @else
                            <li>
                                <div class="d-flex">
                                    <i class="fa-solid fa-xmark text-danger me-2"></i>
                                    {{ $featureName }}
                                </div>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </div>
        </div>

        @endforeach
    </div>

</div>

<style>
    .choose-plan-page .pricing-head {
        margin-bottom: .5rem;
    }

    .choose-plan-page .pricing-title {
        font-weight: 800;
        color: #18225f;
    }

    .choose-plan-page .pricing-subtitle {
        color: #6c757d;
    }

    .choose-plan-page .pricing-grid {
        align-items: stretch;
    }

    .choose-plan-page .pricing-grid>[class*="col-"] {
        display: flex;
    }

    .choose-plan-page .plan-box {
        position: relative;
        display: flex;
        flex-direction: column;
        width: 100%;
        border-radius: 1rem;
        background: #fff;
        border: 1px solid #efefef;
        padding: 2.25rem 0 1.75rem;
        transition: box-shadow .25s ease, transform .25s ease, border-color .25s ease;
    }

    .choose-plan-page .plan-box:hover {
        box-shadow: 1px 0 20px #00000021;
        border-color: transparent;
        transform: translateY(-6px);
    }

    .choose-plan-page .plan-box--popular {
        border: 2px solid #f7a600;
        box-shadow: 0 10px 26px #f7a60026;
    }

    .choose-plan-page .plan-badge {
        position: absolute;
        top: -.9rem;
        left: 50%;
        transform: translateX(-50%);
        background: linear-gradient(135deg, #ffd166, #f7a600);
        color: #3a2600;
        font-size: .7rem;
        font-weight: 800;
        letter-spacing: .03em;
        text-transform: uppercase;
        padding: .4rem 1rem;
        border-radius: 2rem;
        box-shadow: 0 4px 10px #00000030;
        white-space: nowrap;
    }

    .choose-plan-page .plan-content {
        padding: 0 1.75rem;
    }

    .choose-plan-page .plan-current-tag {
        display: inline-flex;
        gap: .35rem;
        font-size: .75rem;
        font-weight: 700;
        padding: .3rem .9rem;
        border-radius: 2rem;
        margin-bottom: .75rem;
        background: #edf0ff;
        color: #18225f;
    }

    .choose-plan-page .plan-name {
        font-weight: 800 !important;
        color: #18225f !important;
        text-align: left !important;
        margin: 0 0 .35rem !important;
        padding-bottom: 0 !important;
    }

    .choose-plan-page .plan-subtitle {
        display: block;
        font-size: .85rem;
        font-weight: 500 !important;
        color: #6c757d !important;
        margin-bottom: 1.25rem !important;
        min-height: 1.2rem;
    }

    .choose-plan-page .plan-price-row {
        display: flex;
        align-items: flex-end;
        justify-content: flex-start;
        gap: .35rem;
        margin-bottom: .2rem;
    }

    .choose-plan-page .plan-fees {
        margin: 0 !important;
        font-size: 2.6rem;
        font-weight: 800;
        color: #18225f !important;
        text-align: left !important;
        padding-bottom: 0 !important;
        line-height: 1;
    }

    .choose-plan-page .plan-period {
        font-size: .95rem;
        font-weight: 600;
        color: #6c757d;
        padding-bottom: .3rem;
    }

    .choose-plan-page .plan-slash-row {
        min-height: 1.4rem;
        margin-bottom: 1.25rem;
        font-size: .85rem;
    }

    .choose-plan-page .plan-slash-row .slash {
        text-decoration: line-through;
        font-weight: 500;
        color: #9a9a9a;
        margin-right: .5rem;
    }

    .choose-plan-page .plan-slash-row .save {
        display: inline-block;
        background: #e6f7ee;
        color: #1a7f4b;
        font-weight: 700;
        padding: .15rem .6rem;
        border-radius: 2rem;
    }

    .choose-plan-page .plan-box form {
        margin-top: .25rem;
    }

    .choose-plan-page .plan-cta {
        border-radius: 2.5rem !important;
        background: #fff !important;
        color: #18225f !important;
        font-weight: 700 !important;
        letter-spacing: .02em;
        border: 1.5px solid #18225f !important;
        transition: all .2s ease !important;
    }

    .choose-plan-page .plan-cta:hover {
        background: #18225f !important;
        color: #fff !important;
        transform: translateY(-2px);
    }

    .choose-plan-page .plan-box--popular .plan-cta {
        background: linear-gradient(45deg, #1b2458, #232d6a) !important;
        color: #fff !important;
        border-color: transparent !important;
    }

    .choose-plan-page .plan-box--popular .plan-cta:hover {
        filter: brightness(1.12);
        transform: translateY(-2px);
    }

    .choose-plan-page ul.plan-features {
        list-style: none;
        padding: 0;
        margin: 1.5rem 0 0;
        max-height: 340px;
        overflow: auto;
    }

    .choose-plan-page ul.plan-features .d-flex {
        align-items: center;
        gap: .5rem;
        font-weight: 500;
        font-size: .85rem;
        color: #3b3b3b;
    }

    .choose-plan-page ul.plan-features li {
        padding: .6rem 1.75rem;
    }

    .choose-plan-page ul.plan-features li+li {
        border-top: 1px dotted #c9c9c9;
    }

    .choose-plan-page .col-lg-4.payment-mode select {
        height: 50px !important;
        border-radius: 2rem !important;
        text-align: center;
        font-weight: 600;
        border-color: #dcdcdc;
    }
</style>


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

        subscription_price(plan_mode);

        $('#plan_mode').on('change', function() {
            var plan_mode = $(this).val();

            subscription_price(plan_mode);

        });

        function formatPeriodLabel(optionText) {
            var parts = optionText.trim().split(' ');
            var num = parseInt(parts[0], 10) || 1;
            var isYearly = (parts[1] || '').toLowerCase().indexOf('year') === 0;
            var unit = isYearly ? 'yr' : 'mo';
            return '/ ' + (num > 1 ? num + ' ' : '') + unit;
        }

        function formatMoney(value) {
            return Number(value).toLocaleString('en-IN');
        }

        function subscription_price(plan_mode){

            if (plan_mode) {
                var planModeText = $('#plan_mode option[value="' + plan_mode + '"]').text();
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

                            // Loop through each subscription price and dynamically update the HTML
                            response.subscription_prices.forEach(function(subscription) {

                            if (subscription.fees == 0) {
                                $('#subscription_fees_' + subscription.id).text('FREE');
                                $('#plan_period_' + subscription.id).text('');
                                $('#before_discount_fees_' + subscription.id).html('');
                            } else {
                                $('#subscription_fees_' + subscription.id).text('₹' + formatMoney(subscription.fees));
                                $('#plan_period_' + subscription.id).text(formatPeriodLabel(planModeText));

                                var diff = subscription.slash_price - subscription.fees;
                                var slashHtml = '';
                                if (subscription.slash_price && diff > 0) {
                                    slashHtml = '<span class="slash">₹' + formatMoney(subscription.slash_price) + '</span>' +
                                        '<span class="save">Save ₹' + formatMoney(diff) + '</span>';
                                }
                                $('#before_discount_fees_' + subscription.id).html(slashHtml);
                            }

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
