@extends('sitelayouts.layout')
@section('content')

<link rel="stylesheet" href="{{ asset('public/css/landing-page-upgrade.css') }}?v={{ time() }}">

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

<!-- Registration Steps Section -->
<section class="reg-steps-section" id="how-it-works">
    <div class="container">
        <div class="heading text-center mb-5">
            <span>Easy Setup</span>
            <h2>Get Your Library Live in 4 Simple Steps</h2>
        </div>

        <div class="row g-4">
            <!-- Step 1 -->
            <div class="col-lg-3 col-md-6">
                <div class="step-box">
                    <div class="step-number">1</div>
                    <h4>Create Account</h4>
                    <p>Sign up with your mobile number & library details. Instant OTP verification—no credit card required.</p>
                </div>
            </div>

            <!-- Step 2 -->
            <div class="col-lg-3 col-md-6">
                <div class="step-box">
                    <div class="step-number">2</div>
                    <h4>Set Shifts & Seats</h4>
                    <p>Configure your branch, floors, and custom shifts (Morning, Evening, 24x7) in a few seconds.</p>
                </div>
            </div>

            <!-- Step 3 -->
            <div class="col-lg-3 col-md-6">
                <div class="step-box">
                    <div class="step-number">3</div>
                    <h4>Admit Learners</h4>
                    <p>Admit students via desk QR scan with selfie & Aadhaar, or import your existing Excel list.</p>
                </div>
            </div>

            <!-- Step 4 -->
            <div class="col-lg-3 col-md-6">
                <div class="step-box step-highlight">
                    <div class="step-number">4</div>
                    <h4>Collect Fees & Relax</h4>
                    <p>Automated WhatsApp fee reminders, live seat tracking, and daily collection reports.</p>
                </div>
            </div>
        </div>

        <div class="steps-cta-wrap">
            <a href="{{ route('register') }}" class="cta">Start Managing Now</a>
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
                    <a class="btn btn-primary ms-3" href="{{ route('register') }}" target="_blank">Register Now!</a>
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
                    <h4>Efficient & Seamless Reporting that makes you Hassle-free</h4>
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
                                Reporting that makes you Hassle-free</h4>
                            <img src="{{ asset('public/img/03.webp') }}" loading="lazy" alt="Online library system">
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</section>

<!-- Section 3: High-Impact Features -->
<section class="product-benefits" id="features">
    <div class="container">
        <div class="heading mb-5 text-center">
            <span class="text-white">High-Impact Features</span>
            <h2>Why Choose Libraro for Your Library?</h2>
        </div>
        <div class="row g-4">
            <!-- Feature 1 -->
            <div class="col-lg-3 col-md-6">
                <div class="benefits">
                    <div class="iconbox">
                        <img src="{{ asset('public/img/libraro-features/detailed-dashboard.png') }}" alt="Live Seat Matrix" class="icon" loading="lazy">
                    </div>
                    <h4>Live Visual Seat Matrix</h4>
                    <span>Real-time color-coded seat layout showing occupied, available, and expiring seats across all shifts.</span>
                </div>
            </div>

            <!-- Feature 2 -->
            <div class="col-lg-3 col-md-6">
                <div class="benefits">
                    <div class="iconbox">
                        <img src="{{ asset('public/img/libraro-features/user-interface.png') }}" alt="Automated WhatsApp" class="icon" loading="lazy">
                    </div>
                    <h4>Automated WhatsApp Due Alerts</h4>
                    <span>Auto-send fee reminders directly on WhatsApp before expiry and share instant fee receipts with 1-click.</span>
                </div>
            </div>

            <!-- Feature 3 -->
            <div class="col-lg-3 col-md-6">
                <div class="benefits">
                    <div class="iconbox">
                        <img src="{{ asset('public/img/libraro-features/identity-card.png') }}" alt="Touchless QR Admission" class="icon" loading="lazy">
                    </div>
                    <h4>Touchless QR Admission</h4>
                    <span>Students scan desk QR code to register online, take selfie, and upload Aadhaar verification digitally.</span>
                </div>
            </div>

            <!-- Feature 4 -->
            <div class="col-lg-3 col-md-6">
                <div class="benefits">
                    <div class="iconbox">
                        <img src="{{ asset('public/img/libraro-features/report.png') }}" alt="Payment Collection Reports" class="icon" loading="lazy">
                    </div>
                    <h4>Payment Collection Reports</h4>
                    <span>Track daily and monthly revenue by UPI, Cash, and Cheque with balancesheets and 1-click CSV/Excel export.</span>
                </div>
            </div>

            <!-- Feature 5 -->
            <div class="col-lg-3 col-md-6">
                <div class="benefits">
                    <div class="iconbox">
                        <img src="{{ asset('public/img/libraro-features/seat-management.png') }}" alt="Smart Seat Management" class="icon" loading="lazy">
                    </div>
                    <h4>Seat Swap & Shift Management</h4>
                    <span>Easily swap learner seats, freeze membership days during exams, and prevent double-booking conflicts.</span>
                </div>
            </div>

            <!-- Feature 6 -->
            <div class="col-lg-3 col-md-6">
                <div class="benefits">
                    <div class="iconbox">
                        <img src="{{ asset('public/img/libraro-features/data-security.png') }}" alt="WebGuard Privacy" class="icon" loading="lazy">
                    </div>
                    <h4>WebGuard™ Data Privacy</h4>
                    <span>Bank-grade encryption ensures learner mobile and email are visible strictly to the authorized library owner.</span>
                </div>
            </div>

            <!-- Feature 7 -->
            <div class="col-lg-3 col-md-6">
                <div class="benefits">
                    <div class="iconbox">
                        <img src="{{ asset('public/img/libraro-features/import-data.png') }}" alt="One-Click Data Import" class="icon" loading="lazy">
                    </div>
                    <h4>One-Click Data Import</h4>
                    <span>Switch effortlessly from physical registers or old spreadsheets. Import your entire student roster in seconds.</span>
                </div>
            </div>

            <!-- Feature 8 -->
            <div class="col-lg-3 col-md-6">
                <div class="benefits">
                    <div class="iconbox">
                        <img src="{{ asset('public/img/libraro-features/directory-listing.png') }}" alt="Student ID Cards" class="icon" loading="lazy">
                    </div>
                    <h4>Digital Student ID Cards</h4>
                    <span>Auto-generate official, printable learner ID cards featuring unique QR codes, photos, and shift timings.</span>
                </div>
            </div>
        </div>

        <div class="row pt-5">
            <div class="col-lg-12 text-center">
                <h4 class="text-white">Make library management effortless and efficient</h4>
                <a href="{{ url('/#demo') }}" class="cta mt-4" style="display: inline-block;">Get Started Today!</a>
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

        <!-- 3-Plan Detailed Feature Comparison Table -->
        <div class="plans-comparison-section" id="compare-plans">
            <div class="heading text-center mb-4">
                <span>Plan Comparison</span>
                <h2>Compare Plans & Features</h2>
            </div>

            <div class="comparison-card">
                <div class="table-responsive">
                    <table class="table-comparison">
                        <thead>
                            <tr>
                                <th class="col-feature">Features</th>
                                <th class="col-plan">
                                    <span class="plan-head-title">Basic Plan</span>
                                    <span class="plan-head-price">From ₹49 / mo</span>
                                </th>
                                <th class="col-plan col-popular-head">
                                    <span class="plan-head-title">Smart Plan</span>
                                    <span class="plan-head-price">From ₹99 / mo</span>
                                </th>
                                <th class="col-plan">
                                    <span class="plan-head-title">Pro Plan</span>
                                    <span class="plan-head-price">From ₹199 / mo</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Capacity -->
                            <tr class="cat-header-row">
                                <td colspan="4"><i class="fa-solid fa-layer-group"></i> Capacity & Limits</td>
                            </tr>
                            <tr>
                                <td>Learner Capacity</td>
                                <td class="cell-plan">Up to 100 Seats</td>
                                <td class="cell-plan cell-popular">Up to 200 Seats</td>
                                <td class="cell-plan">Unlimited Seats</td>
                            </tr>
                            <tr>
                                <td>Branch Support</td>
                                <td class="cell-plan">1 Branch</td>
                                <td class="cell-plan cell-popular">2 Branches</td>
                                <td class="cell-plan">3+ Branches</td>
                            </tr>
                            <tr>
                                <td>Custom Shifts</td>
                                <td class="cell-plan">Up to 3 Shifts</td>
                                <td class="cell-plan cell-popular">Up to 6 Shifts</td>
                                <td class="cell-plan">Unlimited Shifts</td>
                            </tr>
                            <tr>
                                <td>Floor & Room Setup</td>
                                <td class="cell-plan">Single Floor</td>
                                <td class="cell-plan cell-popular">Multi-Floor</td>
                                <td class="cell-plan">Multi-Floor</td>
                            </tr>

                            <!-- Operations -->
                            <tr class="cat-header-row">
                                <td colspan="4"><i class="fa-solid fa-chair"></i> Seat Operations & Admission</td>
                            </tr>
                            <tr>
                                <td>Live Visual Seat Matrix</td>
                                <td class="cell-plan"><i class="fa-solid fa-check text-success"></i></td>
                                <td class="cell-plan cell-popular"><i class="fa-solid fa-check text-success"></i></td>
                                <td class="cell-plan"><i class="fa-solid fa-check text-success"></i></td>
                            </tr>
                            <tr>
                                <td>QR Code Digital Admission</td>
                                <td class="cell-plan"><i class="fa-solid fa-check text-success"></i></td>
                                <td class="cell-plan cell-popular"><i class="fa-solid fa-check text-success"></i></td>
                                <td class="cell-plan"><i class="fa-solid fa-check text-success"></i></td>
                            </tr>
                            <tr>
                                <td>Seat Swap & Day Freezing</td>
                                <td class="cell-plan"><i class="fa-solid fa-check text-success"></i></td>
                                <td class="cell-plan cell-popular"><i class="fa-solid fa-check text-success"></i></td>
                                <td class="cell-plan"><i class="fa-solid fa-check text-success"></i></td>
                            </tr>
                            <tr>
                                <td>Student ID Card Generator</td>
                                <td class="cell-plan">Single Print</td>
                                <td class="cell-plan cell-popular">Bulk Print</td>
                                <td class="cell-plan">All Batch Print</td>
                            </tr>

                            <!-- WhatsApp -->
                            <tr class="cat-header-row">
                                <td colspan="4"><i class="fa-brands fa-whatsapp"></i> WhatsApp & Communication</td>
                            </tr>
                            <tr>
                                <td>Automated Fee Due Reminders</td>
                                <td class="cell-plan">SMS / Add-on</td>
                                <td class="cell-plan cell-popular"><i class="fa-solid fa-check text-success"></i> Included</td>
                                <td class="cell-plan"><i class="fa-solid fa-check text-success"></i> Priority</td>
                            </tr>
                            <tr>
                                <td>WhatsApp Fee Receipts</td>
                                <td class="cell-plan"><i class="fa-solid fa-check text-success"></i></td>
                                <td class="cell-plan cell-popular"><i class="fa-solid fa-check text-success"></i></td>
                                <td class="cell-plan"><i class="fa-solid fa-check text-success"></i></td>
                            </tr>
                            <tr>
                                <td>Expired & Extended Student List</td>
                                <td class="cell-plan"><i class="fa-solid fa-check text-success"></i></td>
                                <td class="cell-plan cell-popular"><i class="fa-solid fa-check text-success"></i></td>
                                <td class="cell-plan"><i class="fa-solid fa-check text-success"></i></td>
                            </tr>

                            <!-- Reports -->
                            <tr class="cat-header-row">
                                <td colspan="4"><i class="fa-solid fa-chart-line"></i> Revenue & Reports</td>
                            </tr>
                            <tr>
                                <td>Daily & Monthly Collection Reports</td>
                                <td class="cell-plan">Standard</td>
                                <td class="cell-plan cell-popular">Advanced</td>
                                <td class="cell-plan">Full Suite</td>
                            </tr>
                            <tr>
                                <td>Expense Manager & Balance Sheet</td>
                                <td class="cell-plan"><i class="fa-solid fa-check text-success"></i></td>
                                <td class="cell-plan cell-popular"><i class="fa-solid fa-check text-success"></i></td>
                                <td class="cell-plan"><i class="fa-solid fa-check text-success"></i></td>
                            </tr>
                            <tr>
                                <td>Excel / CSV Data Export</td>
                                <td class="cell-plan"><i class="fa-solid fa-check text-success"></i></td>
                                <td class="cell-plan cell-popular"><i class="fa-solid fa-check text-success"></i></td>
                                <td class="cell-plan"><i class="fa-solid fa-check text-success"></i></td>
                            </tr>

                            <!-- Security & Support -->
                            <tr class="cat-header-row">
                                <td colspan="4"><i class="fa-solid fa-shield-halved"></i> Security & Support</td>
                            </tr>
                            <tr>
                                <td>WebGuard™ Student Data Privacy</td>
                                <td class="cell-plan"><i class="fa-solid fa-check text-success"></i> Encrypted</td>
                                <td class="cell-plan cell-popular"><i class="fa-solid fa-check text-success"></i> Encrypted</td>
                                <td class="cell-plan"><i class="fa-solid fa-check text-success"></i> Encrypted</td>
                            </tr>
                            <tr>
                                <td>Data Migration Assistance</td>
                                <td class="cell-plan">Self Import</td>
                                <td class="cell-plan cell-popular">Assisted</td>
                                <td class="cell-plan">Dedicated</td>
                            </tr>
                            <tr>
                                <td>Customer Support</td>
                                <td class="cell-plan">Email</td>
                                <td class="cell-plan cell-popular">Phone & WhatsApp</td>
                                <td class="cell-plan">24/7 Priority</td>
                            </tr>
                            <tr>
                                <td>Money-Back Guarantee</td>
                                <td class="cell-plan">7 Days</td>
                                <td class="cell-plan cell-popular">7 Days</td>
                                <td class="cell-plan">7 Days</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td></td>
                                <td>
                                    <button class="btn btn-plan-select buy-now-btn" data-id="1">Choose Basic</button>
                                </td>
                                <td class="cell-popular">
                                    <button class="btn btn-plan-select btn-plan-popular buy-now-btn" data-id="2">Choose Smart</button>
                                </td>
                                <td>
                                    <button class="btn btn-plan-select buy-now-btn" data-id="3">Choose Pro</button>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

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
                                <strong>Answer</strong> Yes, Libraro is accessible on desktops (Preferred), laptops, tablets, and smartphones (Support Available Soon), ensuring convenience for library staff and users anytime, anywhere.
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
                                <small class="text-gray" style="font-size: .8rem;">Choose Preferred Slot Date</small>

                            </div>
                            
                            <div class="col-lg-12">
                                <select class="form-select no-validate" id="timeSlot" name="preferred_time">
                                    <option value="">Select Preferred Time Slot</option>
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
                                    I agree to the Libraro <a href="{{ route('term-and-condition') }}" target="_blank">Terms and Conditions.</a><sup class="text-danger">*</sup>
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