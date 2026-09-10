@extends('sitelayouts.layout')
@section('content')


<!-- Section 1 -->
 <section class="hero_Section">
    <div class="container">
        <div class="row g-4 align-items-center">
            <div class="col-lg-6 order-2 order-md-1 text-center text-md-start">
                <h4 class="head-text-1">Revolutionize Your Library with the Best Library Management Software</h4>
                
                <h2 id="typing-text" class="head-text-2 d-inline"></h2>
                <h1 class="typing-cursor d-inline">|</h1>

                
                <p class="head-text-3 mt-4">Optimize your library operations with our feature-rich, user-friendly software perfect for public and private libraries.</p>
                <a href="{{route('register')}}" class="cta">Sign Up for Easy Management</a>
            </div>
            <div class="col-lg-6 order-1 order-md-2 mb-4 mb-md-0">
                <img src="{{ asset('public/img/head.webp') }}" loading="lazy" alt="Library management software" class="img-fluid">
            </div>
        </div>
    </div>
</section> 

<!-- <section class="mt-2 d-none">
    <div class="owl-carousel owl-theme" id="mainSlider">
        <div class="item">
            <img src="{{ asset('public/img/slider/slider-3.png') }}" loading="lazy" alt="slider" class="img-fluid rounded-4">
        </div>
        <div class="item">
            <img src="{{ asset('public/img/slider/slider-2.png') }}" loading="lazy" alt="slider" class="img-fluid rounded-4">
        </div>
        <div class="item">
            <img src="{{ asset('public/img/slider/slider-1.png') }}" loading="lazy" alt="slider" class="img-fluid rounded-4">
        </div>
    </div>
</section> -->

<!-- <section class="offer d-none">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
            <div class="offer-box alert alert-warning alert-dismissible fade show" role="alert">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="text">
                        <p><b>Limited-Time Offer!</b> Get up to 30% OFF on all products – offer valid until April 15, 2025! Hurry, don’t miss out!</p>
                        <div id="countdown-timer"></div>
                    </div>
                    <a class="btn btn-primary ms-3" href="http://localhost/libraryProject/library/register" target="_blank">Register Now!</a>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                
            </div>
            </div>
        </div>
    </div>
</section> -->

<!-- Section 2 -->
<section class="product-features">
    <div class="container">
        <div class="heading text-center">
            <span>Features of Product</span>
            <h2>Why Choose Libraro ?</h2>
        </div>
        <div class="row d-none">
            <div class="col-lg-4">
                <div class="featureBox">
                    <img src="{{ asset('public/img/dashboard.png') }}" loading="lazy" alt="Interactive Dashboard" class="img-fluid">
                    <h4>Interactive Dashboard with Complete Seat Tracking</h4>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="featureBox">
                    <img src="{{ asset('public/img/seat-assignment.png') }}" loading="lazy" alt="Interactive Dashboard" class="img-fluid">
                    <h4>Engage with Our Seat Mapping Feature: Expired and Extended Highlights</h4>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="featureBox">
                    <img src="{{ asset('public/img/reporting.png') }}" loading="lazy" alt="Interactive Dashboard" class="img-fluid">
                    <h4>Efficient & Seamless Reporting that make you Hasselfree</h4>
                </div>
            </div>

        </div>
        <div class="row">
            <div class="col-lg-12">

                <div class="owl-carousel owl-theme" id="featureSlider">

                    <div class="item">
                        <div class="product-features-box">
                            <h4>Interactive Dashboard with <br>
                                Complete Seat Tracking</h4>
                            <img src="{{ asset('public/img/01.webp') }}" loading="lazy" alt="Library management system">
                        </div>
                    </div>

                    <div class="item">
                        <div class="product-features-box">
                            <h4>Engage with Our Seat Mapping Feature: Expired and Extended Highlights</h4>
                            <img src="{{ asset('public/img/02.webp') }}" loading="lazy" alt="Library manager tool">
                        </div>
                    </div>

                    <div class="item">
                        <div class="product-features-box">
                            <h4>Efficient & Seamless
                                Reporting that make you Hasselfree</h4>
                            <img src="{{ asset('public/img/03.webp') }}" loading="lazy" alt="Online library system">
                        </div>
                    </div>
                    <div class="item">
                        <div class="product-features-box">
                            <h4>Efficient & Seamless
                                Reporting that make you Hasselfree</h4>
                            <img src="{{ asset('public/img/03.webp') }}" loading="lazy" alt="Online library system">
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</section>

<!-- Section 3 -->
<section class="product-benefits">
    <div class="container">
        <div class="heading mb-5 text-center">
            <span class="text-white">Features of Our Library Automation Software</span>
            <h2>Why Choose Our Library Management Tool?</h2>
        </div>
        <div class="row g-4">
            <div class="col-lg-3">
                <div class="benefits">
                    <div class="iconbox">

                        <img src="https://www.libraro.in/public/img/libraro-features/detailed-dashboard.png" alt="Delete Seat Booking" class="icon" >
                    </div>
                    <h4>Interactive &amp; Insightful Dashboard</h4>
                    <span>Get a complete overview of your library with an intuitive and visually engaging dashboard.</span>
                </div>
            </div>

            <div class="col-lg-3">
                <div class="benefits">
                    <div class="iconbox">
                        <img src="https://www.libraro.in/public/img/libraro-features/user-interface.png" alt="Easy Plan Upgrades" class="icon" >
                    </div>
                    <h4>Seamless &amp; Intuitive User Interface</h4>
                    <span>Our platform is designed for an effortless user experience, making navigation smooth and hassle-free.</span>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="benefits">
                    <div class="iconbox">

                        <img src="https://www.libraro.in/public/img/libraro-features/import-data.png" alt="Close Seat Option" class="icon" >
                    </div>
                    <h4>One-Click Data Import</h4>
                    <span>Effortlessly migrate your existing data into our system with just a single click.</span>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="benefits">
                    <div class="iconbox">
                        <img src="https://www.libraro.in/public/img/libraro-features/seat-management.png" alt="Reactivate
                        Seat Access" class="icon" >
                    </div>
                    <h4>Smart Seat Management</h4>
                    <span>Easily track Expired and Extended seats with a dedicated section for better organization.</span>
                </div>
            </div>

            <div class="col-lg-3">
                <div class="benefits">
                    <div class="iconbox">
                        <img src="https://www.libraro.in/public/img/libraro-features/data-security.png" alt="Swap Seat" class="icon" loading="lazy">
                    </div>
                    <h4>End-to-End Encryption &amp; Data Security</h4>
                    <span>Rest assured, only the library owner has access to learners' email and mobile details, ensuring complete privacy.</span>
                </div>
            </div>

            <div class="col-lg-3">
                <div class="benefits">
                    <div class="iconbox">
                        <img src="https://www.libraro.in/public/img/libraro-features/identity-card.png" alt="Flexible Membership Plans" class="icon" loading="lazy">
                    </div>
                    <h4>Attendance &amp; ID Card Management</h4>
                    <span>Track attendance seamlessly and manage ID cards with ease.</span>
                </div>
            </div>

            <div class="col-lg-3">
                <div class="benefits">
                    <div class="iconbox">

                        <img src="https://www.libraro.in/public/img/libraro-features/report.png" alt="Swap Seat" class="icon" loading="lazy">
                    </div>
                    <h4>Comprehensive Reports</h4>
                    <span>Generate detailed reports in seconds to simplify your library management.</span>
                </div>
            </div>

            <div class="col-lg-3">
                <div class="benefits">
                    <div class="iconbox">
                        <img src="https://www.libraro.in/public/img/libraro-features/directory-listing.png" alt="Effortless Communication" class="icon" loading="lazy">
                    </div>
                    <h4>Free Directory Listing</h4>
                    <span>Boost your library’s visibility by getting a free listing with any of our plans.</span>
                </div>
            </div>


        </div>
        <div class="row pt-5">
            <div class="col-lg-12 text-center">
                <h4 class="text-white">Make library management effortless and efficient</h4>
                <a href="{{url(path: '/#demo')}}" class="cta mt-4" style="display: inline-block;">Get Started Today!</a>
            </div>
        </div>
    </div>

</section>

<div class="our-plan" id="pricing">
    <div class="container">
        <!-- Heading & Refund Guarantee Banner -->
        <div class="heading mb-4 text-center">
            <span class="text-white">Libraro Plans & Pricing</span>
            <h2>Choose the Best Plan for You</h2>
            
            <div class="mt-3">
                <div class="refund-guarantee-badge">
                    <i class="fa-solid fa-shield-halved"></i>
                    <span><strong>7 Days No-Questions-Asked Refund Policy</strong> — 100% Risk Free</span>
                </div>
            </div>
        </div>

        <div class="row g-4 justify-content-center">
            <div class="col-lg-4 payment-mode">
                <select name="plan_mode" id="plan_mode" class="form-select">
                    <option value="1">MONTHLY</option>
                    <option value="2">YEARLY</option>
                </select>
            </div>
        </div>

        <div class="row mt-4 g-4 justify-content-center mb-4 pricing-grid">
            @foreach($subscriptions as $subscription)
                @php
                // Features of current subscription
                $subscriptionFeatures = $features->where('subscription_id', $subscription->id)->whereNull('deleted_at')->pluck('name')->toArray();

                // All unique features
                $allFeatures = $features->pluck('name')->unique()->toArray();
                $checkedFeatureCount = count($subscriptionFeatures);

                $professionalDesc = match((int)$subscription->id) {
                    1 => 'Up to 100 seats & 1 Branch with essential features',
                    2 => 'Up to 200 seats & 2 Branches with smart features',
                    3 => 'Unlimited seats & 3 Branches with all pro features',
                    default => $subscription->plan_description ?? ''
                };
                @endphp

                <div class="col-lg-4 col-md-6">
                    <div class="plan-box {{ $loop->index === 1 ? 'plan-box--popular' : '' }}">
                        @if($loop->index === 1)
                            <span class="plan-badge"><i class="fa-solid fa-star"></i> Most Popular</span>
                        @endif

                        <div class="plan-content">
                            <h4 class="plan-name">{{$subscription->name}}</h4>
                            <span class="d-block plan-subtitle" id="planDescription_{{$subscription->id}}">{{ $professionalDesc }}</span>

                            <div class="plan-price-row">
                                <h1 id="subscription_fees_{{$subscription->id}}" class="plan-fees">--</h1>
                                <span class="plan-period" id="plan_period_{{$subscription->id}}"></span>
                            </div>
                            <div class="plan-slash-row" id="before_discount_fees_{{$subscription->id}}"></div>

                            <button class="btn btn-primary button plan-cta buy-now-btn" data-id="{{ $subscription->id }}" data-plan_mode="">
                                Buy Now <i class="fa-solid fa-arrow-right ms-1"></i>
                            </button>
                        </div>

                        <div class="features-header-box d-flex justify-content-between align-items-center mt-4 mb-2 px-3">
                            <span class="features-title font-outfit fw-bold" style="font-size: 0.88rem; color: #18225f;">Included Features</span>
                            <span class="features-count-badge font-outfit fw-bold">
                                <i class="fa-solid fa-circle-check me-1 text-success"></i>{{ $checkedFeatureCount }} Features
                            </span>
                        </div>

                        <ul class="plan-features contents">
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
</div>

<style>
    .our-plan .refund-guarantee-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.6rem;
        background: #ffffff;
        color: #18225f;
        padding: 0.55rem 1.4rem;
        border-radius: 50px;
        box-shadow: 0 4px 15px rgba(24, 34, 95, 0.12);
        border: 1.5px solid #cbd5e1;
        font-family: 'Outfit', sans-serif;
        font-size: 0.9rem;
    }

    .our-plan .refund-guarantee-badge i {
        color: #16a34a;
        font-size: 1.1rem;
    }

    .our-plan .pricing-grid {
        align-items: stretch;
    }

    .our-plan .pricing-grid > [class*="col-"] {
        display: flex;
    }

    .our-plan .plan-box {
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

    .our-plan .plan-box:hover {
        box-shadow: 1px 0 20px #00000021;
        border-color: transparent;
        transform: translateY(-6px);
    }

    .our-plan .plan-box--popular {
        border: 2px solid #f7a600;
        box-shadow: 0 10px 26px #f7a60026;
    }

    .our-plan .plan-badge {
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

    .our-plan .plan-content {
        padding: 0 1.75rem;
    }

    .our-plan .plan-name {
        font-weight: 800 !important;
        color: #18225f !important;
        text-align: left !important;
        margin: 0 0 .35rem !important;
        padding-bottom: 0 !important;
    }

    .our-plan .plan-subtitle {
        display: block;
        font-size: .84rem;
        font-weight: 600 !important;
        color: #34939F !important;
        margin-bottom: 1.15rem !important;
        line-height: 1.35;
        min-height: 2.4rem;
    }

    .our-plan .plan-price-row {
        display: flex;
        align-items: flex-end;
        justify-content: flex-start;
        gap: .35rem;
        margin-bottom: .2rem;
    }

    .our-plan .plan-fees {
        margin: 0 !important;
        font-size: 2.6rem;
        font-weight: 800;
        color: #18225f !important;
        text-align: left !important;
        padding-bottom: 0 !important;
        line-height: 1;
    }

    .our-plan .plan-period {
        font-size: .95rem;
        font-weight: 600;
        color: #6c757d;
        padding-bottom: .3rem;
    }

    .our-plan .plan-slash-row {
        min-height: 1.4rem;
        margin-bottom: 1.25rem;
        font-size: .85rem;
    }

    .our-plan .plan-slash-row .slash {
        text-decoration: line-through;
        font-weight: 500;
        color: #9a9a9a;
        margin-right: .5rem;
    }

    .our-plan .plan-slash-row .save {
        display: inline-block;
        background: #e6f7ee;
        color: #1a7f4b;
        font-weight: 700;
        padding: .15rem .6rem;
        border-radius: 2rem;
    }

    .our-plan .plan-cta {
        border-radius: 2.5rem !important;
        background: #fff !important;
        color: #18225f !important;
        font-weight: 700 !important;
        letter-spacing: .02em;
        border: 1.5px solid #18225f !important;
        transition: all .2s ease !important;
        width: 100% !important;
        padding: 0.6rem 1.5rem !important;
    }

    .our-plan .plan-cta:hover {
        background: #18225f !important;
        color: #fff !important;
        transform: translateY(-2px);
    }

    .our-plan .plan-box--popular .plan-cta {
        background: linear-gradient(45deg, #1b2458, #232d6a) !important;
        color: #fff !important;
        border-color: transparent !important;
    }

    .our-plan .plan-box--popular .plan-cta:hover {
        background: linear-gradient(45deg, #151c45, #1d2657) !important;
    }

    .our-plan .features-header-box {
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 0.5rem;
    }

    .our-plan .features-count-badge {
        background: #e6f7ee !important;
        color: #16a34a !important;
        border: 1px solid #bbf7d0 !important;
        border-radius: 50px !important;
        padding: 0.25rem 0.75rem !important;
        font-size: 0.78rem !important;
    }

    .our-plan ul.plan-features {
        margin: 0;
        padding: 1.25rem 1.75rem 0;
        list-style: none;
        max-height: 280px;
        overflow-y: auto;
    }
</style>

<!-- Customer's Feedback -->
<section class="customer-feedback">

    <div class="container">
        <div class="heading mb-5 text-center text-md-start">
            <span>Customer's Feedback</span>
            <h2>What Our <br>
                Happy Customers Say’s</h2>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <div class="owl-carousel owl-theme" id="clientsFeedbacks">

                    @if(!($happy_customers->isEmpty()))
                    <div class="item">
                        <div class="feedback-box">
                            <img src="{{url('public/img/comma.png')}}" loading="lazy" alt="comma" class="comma">

                            <div class="message">As the <b>Founder & Director</b>, I created Libraro to simplify library operations with automation, seamless bookings, and powerful analytics. It's the all-in-one solution for modern libraries!</div>
                            <div class="customer-info">
                                <img src="{{ asset('public/img/pawan-profile.jpg') }}" loading="lazy" alt="user" class="profile rounded-circle">
                                <div class="customer-details">
                                    <h4>Pawan Rathore</h4>
                                    <span>Founder: Libraro</span>
                                </div>
                                <ul class="customer-ratings">
                                    <li><img src="{{ asset('public/img/star.png') }}" alt="star"></li>
                                    <li><img src="{{ asset('public/img/star.png') }}" alt="star"></li>
                                    <li><img src="{{ asset('public/img/star.png') }}" alt="star"></li>
                                    <li><img src="{{ asset('public/img/star.png') }}" alt="star"></li>
                                    <li><img src="{{ asset('public/img/star.png') }}" alt="star"></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <!-- <div class="item d-none">
                        <div class="feedback-box">
                            <img src="{{url('public/img/comma.png')}}" loading="lazy" alt="comma" class="comma">

                            <div class="message">As the Developer of Libraro, I built this platform to streamline library operations with automation, intuitive booking, and advanced analytics. Designed for efficiency, it's the ultimate tool for modern libraries!</div>
                            <div class="customer-info">
                                <img src="{{ asset('public/img/user.png') }}" loading="lazy" alt="user" class="profile">
                                <div class="customer-details">
                                    <h4>Heena Kaushar</h4>
                                    <span>Developer: Libraro </span>
                                </div>
                                <ul class="customer-ratings">
                                    <li><img src="{{ asset('public/img/star.png') }}" alt="star"></li>
                                    <li><img src="{{ asset('public/img/star.png') }}" alt="star"></li>
                                    <li><img src="{{ asset('public/img/star.png') }}" alt="star"></li>
                                    <li><img src="{{ asset('public/img/star.png') }}" alt="star"></li>
                                    <li><img src="{{ asset('public/img/star.png') }}" alt="star"></li>
                                </ul>
                            </div>
                        </div>
                    </div> -->
                    <div class="item">
                        <div class="feedback-box">
                            <img src="{{url('public/img/comma.png')}}" loading="lazy" alt="comma" class="comma">

                            <div class="message">We’ve been using Library Manager for over a year now, and it has exceeded all our expectations. The analytics and reporting features provide valuable insights. It’s an all-in-one solution for modern library management!</div>
                            <div class="customer-info">
                                <img src="{{ asset('public/img/user.png') }}" loading="lazy" alt="user" class="profile">
                                <div class="customer-details">
                                    <h4>Sandeep Rathor</h4>
                                    <span>Libraro Manager</span>
                                </div>
                                <ul class="customer-ratings">
                                    <li><img src="{{ asset('public/img/star.png') }}" alt="star"></li>
                                    <li><img src="{{ asset('public/img/star.png') }}" alt="star"></li>
                                    <li><img src="{{ asset('public/img/star.png') }}" alt="star"></li>
                                    <li><img src="{{ asset('public/img/star.png') }}" alt="star"></li>
                                    <li><img src="{{ asset('public/img/star.png') }}" alt="star"></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                   
                    @foreach($happy_customers as $key => $value)
                    <div class="item">
                        <div class="feedback-box">
                            <img src="{{url('public/img/comma.png')}}" loading="lazy" alt="comma" class="comma">
                            <div class="message">{{$value->description ?? ''}}</div>
                            <div class="customer-info">
                                <img src="{{ asset('public/img/user.png') }}" loading="lazy" alt="user" class="profile rounded-circle">
                                <div class="customer-details">
                                    <h4>{{$value->library_owner ?? ''}}</h4>
                                    <span>{{$value->library_name ?? ''}}</span>
                                </div>
                                <ul class="customer-ratings">
                                    <li><img src="{{ asset('public/img/star.png') }}" alt="star"></li>
                                    <li><img src="{{ asset('public/img/star.png') }}" alt="star"></li>
                                    <li><img src="{{ asset('public/img/star.png') }}" alt="star"></li>
                                    <li><img src="{{ asset('public/img/star.png') }}" alt="star"></li>
                                    <li><img src="{{ asset('public/img/star.png') }}" alt="star"></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    @endforeach

                    @else
                    <div class="item">
                        <div class="feedback-box">
                            <img src="{{url('public/img/comma.png')}}" loading="lazy" alt="comma" class="comma">

                            <div class="message">As the <b>Founder & Director</b>, I created Libraro to simplify library operations with automation, seamless bookings, and powerful analytics. It's the all-in-one solution for modern libraries!</div>
                            <div class="customer-info">
                                <img src="{{ asset('public/img/pawan-profile.jpg') }}" loading="lazy" alt="user" class="profile rounded-circle">
                                <div class="customer-details">
                                    <h4>Pawan Rathore</h4>
                                    <span>Founder: Libraro</span>
                                </div>
                                <ul class="customer-ratings">
                                    <li><img src="{{ asset('public/img/star.png') }}" alt="star"></li>
                                    <li><img src="{{ asset('public/img/star.png') }}" alt="star"></li>
                                    <li><img src="{{ asset('public/img/star.png') }}" alt="star"></li>
                                    <li><img src="{{ asset('public/img/star.png') }}" alt="star"></li>
                                    <li><img src="{{ asset('public/img/star.png') }}" alt="star"></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <!-- <div class="item d-none">
                        <div class="feedback-box">
                            <img src="{{url('public/img/comma.png')}}" alt="comma" loading="lazy" class="comma">

                            <div class="message">As the Developer of Libraro, I built this platform to streamline library operations with automation, intuitive booking, and advanced analytics. Designed for efficiency, it's the ultimate tool for modern libraries!</div>
                            <div class="customer-info">
                                <img src="{{ asset('public/img/user.png') }}" loading="lazy" alt="user" class="profile">
                                <div class="customer-details">
                                    <h4>Heena Kaushar</h4>
                                    <span>Developer: Libraro </span>
                                </div>
                                <ul class="customer-ratings">
                                    <li><img src="{{ asset('public/img/star.png') }}" alt="star"></li>
                                    <li><img src="{{ asset('public/img/star.png') }}" alt="star"></li>
                                    <li><img src="{{ asset('public/img/star.png') }}" alt="star"></li>
                                    <li><img src="{{ asset('public/img/star.png') }}" alt="star"></li>
                                    <li><img src="{{ asset('public/img/star.png') }}" alt="star"></li>
                                </ul>
                            </div>
                        </div>
                    </div> -->
                    <div class="item">
                        <div class="feedback-box">
                            <img src="{{url('public/img/comma.png')}}" loading="lazy" alt="comma" class="comma">
                            <div class="message">We’ve been using Library Manager for over a year now, and it has exceeded all our expectations. The analytics and reporting features provide valuable insights. It’s an all-in-one solution for modern library management!</div>
                            <div class="customer-info">
                                <img src="{{ asset('public/img/user.png') }}" loading="lazy" alt="user" class="profile">
                                <div class="customer-details">
                                    <h4>Sandeep Rathor</h4>
                                    <span>Libraro Manager</span>
                                </div>
                                <ul class="customer-ratings">
                                    <li><img src="{{ asset('public/img/star.png') }}" alt="star"></li>
                                    <li><img src="{{ asset('public/img/star.png') }}" alt="star"></li>
                                    <li><img src="{{ asset('public/img/star.png') }}" alt="star"></li>
                                    <li><img src="{{ asset('public/img/star.png') }}" alt="star"></li>
                                    <li><img src="{{ asset('public/img/star.png') }}" alt="star"></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Frequently Asked Questions -->
<section class="py-5" id="faqy">
    <div class="container">
        <h2 class="text-center mb-5">Frequently Asked Questions</h2>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="accordion" id="accordionExample">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse"
                                data-bs-target="#faq_01" aria-expanded="true" aria-controls="faq_01">
                                Qus 1: What is Libraro, and how does it work?
                            </button>
                        </h2>
                        <div id="faq_01" class="accordion-collapse collapse show"
                            data-bs-parent="#accordionExample">
                            <div class="accordion-body">
                                <strong>Answer</strong> Libraro is a comprehensive library management portal designed to simplify and automate library operations.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#faq_02" aria-expanded="false" aria-controls="faq_02">
                                Qus 2: Who can use Libraro?
                            </button>
                        </h2>
                        <div id="faq_02" class="accordion-collapse collapse"
                            data-bs-parent="#accordionExample">
                            <div class="accordion-body">
                                <strong>Answer</strong> Libraro is suitable for public libraries, and private libraries looking for a modern solution to streamline their library management processes.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#faq_03" aria-expanded="false" aria-controls="faq_03">
                                Qus 3: Is Libraro compatible with different devices?
                            </button>
                        </h2>
                        <div id="faq_03" class="accordion-collapse collapse"
                            data-bs-parent="#accordionExample">
                            <div class="accordion-body">
                                <strong>Answer</strong> Yes, Libraro is accessible on desktops (Preffered), laptops, tablets, and smartphones (Support Available Soon), ensuring convenience for library staff and users anytime, anywhere.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#faq_04" aria-expanded="false" aria-controls="faq_04">
                                Qus 4: Can I import my existing library data into Libraro?
                            </button>
                        </h2>
                        <div id="faq_04" class="accordion-collapse collapse"
                            data-bs-parent="#accordionExample">
                            <div class="accordion-body">
                                <strong>Answer</strong> Absolutely! Libraro allows you to import existing data in bulk using easy-to-use templates (.csv file), making the transition seamless for your library.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#faq_06" aria-expanded="false" aria-controls="faq_06">
                                Qus 5: Is my library data secure with Libraro?
                            </button>
                        </h2>
                        <div id="faq_06" class="accordion-collapse collapse"
                            data-bs-parent="#accordionExample">
                            <div class="accordion-body">
                                <strong>Answer</strong> Security is our top priority. Libraro uses end to end encryption (for Learner Mobile and Email) and data protection measures to ensure your library's data is safe and accessible only to authorized users.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#faq_07" aria-expanded="false" aria-controls="faq_07">
                                Qus 6: How do I get support if I face any issues?
                            </button>
                        </h2>
                        <div id="faq_07" class="accordion-collapse collapse"
                            data-bs-parent="#accordionExample">
                            <div class="accordion-body">
                                <strong>Answer</strong> We provide dedicated customer support via email (support@libraro.in), phone (+91-8114479678, +91-7737918848), and chat to assist you with any technical or operational queries.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Quick Support -->
<section class="inquiry" id="demo">
    <div class="container">
        <div class="row g-4 align-items-center">
            @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
            @endif
            <div class="col-lg-5 order-2 order-md-2">
                <h2 class="mb-4">Would you like to <br><span>Schedule a free Demo?</span></h2>
                <form class="me-3" id="demoRequest">
                    @csrf
                    <input type="hidden" name="databasemodel" value="">
                    <div class="form-box">
                        <div class="row g-4">
                            <div class="col-lg-12">
                                <input type="text" name="full_name" class="form-control @error('full_name') is-invalid @enderror char-only" placeholder="Full Name" autocomplete="off" id="full_name">
                            </div>
                            
                            <div class="col-lg-12">
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" placeholder="Email ID" autocomplete="off" id="email">

                            </div>
                            <div class="col-lg-6">
                                <input type="text" name="mobile_number" class="form-control @error('mobile_number') is-invalid @enderror digit-only" placeholder="Mobile Number" minlength="8" maxlength="10" autocomplete="off" id="mobile_number">
                                @error('mobile_number')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                            <div class="col-lg-6">
                                <input type="date" name="preferred_date" class="form-control @error('preferred_date') is-invalid @enderror" id="preferred_date" placeholder="Date" min="<?= date('Y-m-d'); ?>">
                                <small class="text-gray" style="font-size: .8rem;">Choose Preffered Slot Date</small>

                            </div>
                            
                            <div class="col-lg-12">
                                <select class="form-select no-validate" id="timeSlot" name="preferred_time">
                                    <option value="">Select Preffered Time Slot</option>
                                    <option value="7:00 AM - 7:30 AM">7:00 AM - 7:30 AM</option>
                                    <option value="7:30 AM - 8:00 AM">7:30 AM - 8:00 AM</option>
                                    <option value="8:00 AM - 8:30 AM">8:00 AM - 8:30 AM</option>
                                    <option value="8:30 AM - 9:00 AM">8:30 AM - 9:00 AM</option>
                                    <option value="9:00 AM - 9:30 AM">9:00 AM - 9:30 AM</option>
                                    <option value="9:30 AM - 10:00 AM">9:30 AM - 10:00 AM</option>
                                    <option value="10:00 AM - 10:30 AM">10:00 AM - 10:30 AM</option>
                                    <option value="10:30 AM - 11:00 AM">10:30 AM - 11:00 AM</option>
                                    <option value="11:00 AM - 11:30 AM">11:00 AM - 11:30 AM</option>
                                    <option value="11:30 AM - 12:00 PM">11:30 AM - 12:00 PM</option>
                                    <option value="12:00 PM - 12:30 PM">12:00 PM - 12:30 PM</option>
                                    <option value="12:30 PM - 1:00 PM">12:30 PM - 1:00 PM</option>
                                    <option value="1:00 PM - 1:30 PM">1:00 PM - 1:30 PM</option>
                                    <option value="1:30 PM - 2:00 PM">1:30 PM - 2:00 PM</option>
                                    <option value="2:00 PM - 2:30 PM">2:00 PM - 2:30 PM</option>
                                    <option value="2:30 PM - 3:00 PM">2:30 PM - 3:00 PM</option>
                                    <option value="3:00 PM - 3:30 PM">3:00 PM - 3:30 PM</option>
                                    <option value="3:30 PM - 4:00 PM">3:30 PM - 4:00 PM</option>
                                    <option value="4:00 PM - 4:30 PM">4:00 PM - 4:30 PM</option>
                                    <option value="4:30 PM - 5:00 PM">4:30 PM - 5:00 PM</option>
                                    <option value="5:00 PM - 5:30 PM">5:00 PM - 5:30 PM</option>
                                    <option value="5:30 PM - 6:00 PM">5:30 PM - 6:00 PM</option>
                                    <option value="6:00 PM - 6:30 PM">6:00 PM - 6:30 PM</option>
                                    <option value="6:30 PM - 7:00 PM">6:30 PM - 7:00 PM</option>
                                    <option value="7:00 PM - 7:30 PM">7:00 PM - 7:30 PM</option>
                                </select>
                            </div>
                            <div class="col-lg-12 form-group">
                                <input type="checkbox" class="me-2 form-check-input " name="terms" id="terms">
                                <label class="form-check-label" for="terms">
                                    I agree to the Libraro <a href="#">Terms and Conditions.</a><sup class="text-danger">*</sup>
                                </label>
                                <div class="error-msg"></div>
                            </div>
                            <div class="col-lg-4">
                                <button class="btn btn-primary" type="submit">Book My Slot</button>
                            </div>
                        </div>
                    </div>
                </form>

            </div>
            <div class="col-lg-7 order-1 order-md-2">
                <div class="main-box">
                    <div class="support">
                        <img src="{{ asset('public/img/direcotry/call.png') }}" loading="lazy" alt="call">
                        <h4>We Are Here
                            to Assist you</h4>
                        <p class="m-0">Call : <a href="tel:91-8114479678">91-8114479678</a></p>
                        <p>Mail : <a href="mailto:info@libraro.in">info@libraro.in</a></p>
                    </div>
                    <img src="{{ asset('public/img/contact.png') }}" loading="lazy" alt="support" class="img-fluid">
                </div>
            </div>
        </div>
    </div>
</section>



@endsection