@extends('layouts.library')

@section('content')


<!-- Breadcrumb -->

    <div class="row justify-content-center mb-4">
        <div class="col-lg-5">
            <div class="payment-detaile">

                <div class="paymentinfo  @switch($plan->name)
                @case('Basic Plan')
                    basic
                    @break
                @case('Standard Plan')
                    standard
                    @break
                @case('Premium Plan')
                    premium
                    @break
                @endswitch <?php if ($plan ? $plan->name == 'Basic' : '') ?>">
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
                                <h4>{{ $month->amount }}</h4>
                            </div>
                            <div class="col-lg-6">
                                <span>Subscription Start Date</span>
                                <h4>{{ \Carbon\Carbon::parse($month->start_date)->format('d M Y') }}</h4>
                            </div>
                            @if($month->end_date)
                            <div class="col-lg-6">
                                <span>Subscription End Date</span>
                                <h4>{{ \Carbon\Carbon::parse($month->end_date)->format('d M Y') }}</h4>
                            </div>
                            @endif
                            <div class="col-lg-6">
                                <span>Current Plan Status :</span>
                                @if($month->status == 1)
                                <h4 class="text-white">Active</h4>
                                @else
                                <h4 class="text-danger">Inactive</h4>
                                @endif
                            </div>
                            <div class="col-lg-6">
                                <span>Next Due Date</span>
                                <h4>{{ \Carbon\Carbon::parse($month->end_date)->format('d M Y') }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    @php
                    $premiumSub=App\Models\Subscription::where('id',3)->first();
                    $subscribedPermissions = $data->subscription->permissions->pluck('name')->toArray();
                    @endphp
                    {{-- <ul class="plan-features contents mb-3">
                        @if(isset($data->subscription) && $data->subscription->permissions->isNotEmpty())
                        @foreach($data->subscription->permissions as $permission)
                        <li><i class="fa-solid fa-check text-success me-2"></i> {{ $permission->name }}</li>
                        @endforeach
                        @foreach($premiumSub->permissions as $permission)
                        <li><i class="fa-solid fa-xmark text-danger me-2"></i>{{ $permission->name }}</li>
                        @endforeach
                        @else
                        <li>No permissions available</li>
                        @endif
                    </ul> --}}
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
            <i class="fa fa-circle-check fa-3x text-success"></i>
        </div>
    </div>
    <div class="row justify-content-center mb-4">
        <div class="col-lg-12 text-center">
            <p class="text-danger">Both buttons will be available starting 5 days before your plan’s expiration.</p>
        </div>
        @if(isset($librarydiffInDays) && $librarydiffInDays <= 5)
        <div class="col-lg-2">
            <a href="{{route('subscriptions.choosePlan')}}" class="btn btn-primary button box-shadow">Upgrade Plan</a>
        </div>
        <div class="col-lg-2">
            <a href="{{route('subscriptions.choosePlan')}}" class="btn btn-primary button box-shadow renew">Renew your Plan</a>
        </div>
        @endif
    </div>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/malihu-custom-scrollbar-plugin/3.1.5/jquery.mCustomScrollbar.concat.min.js"></script>

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