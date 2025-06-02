@extends('sitelayouts.layout')
@section('content')

<!-- Section 1 -->
<section class="hero_Section">
    <div class="container">
        <div class="row g-4 align-items-center">
            <div class="col-lg-6 order-2 order-md-1 text-center text-md-start">
                <h4 class="head-text-1">Revolutionize Your Library with the Best Library Management Software</h4>
                <h2 class="head-text-2">Effortlessly manage your library from seat bookings to reporting all in one place!</h2>
                <p class="head-text-3">Optimize your library operations with our feature-rich, user-friendly software perfect for public and private libraries.</p>
                <a href="{{route('register')}}" class="cta">Sign Up – Manage Effortlessly!</a>
            </div>
            <div class="col-lg-6 order-1 order-md-2 mb-4 mb-md-0">
                <img src="{{ asset('public/img/head.webp') }}" alt="Library management software" class="img-fluid">
            </div>
        </div>
    </div>
</section>

<!-- Section 2 -->
<section class="product-features">
    <div class="container">
        <div class="heading text-center text-md-start">
            <span>Features of Product</span>
            <h2>Transform Your <br>
                Library into a Smart Space </h2>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <div class="owl-carousel" id="featureSlider">

                    <div class="item">
                        <div class="product-features-box">
                            <h4>Interactive Dashboard with <br>
                                Complete Seat Tracking</h4>
                            <img src="{{ asset('public/img/01.png') }}" alt="Library management system">
                        </div>
                    </div>

                    <div class="item">
                        <div class="product-features-box">
                            <h4>Engage with Our Seat Mapping Feature: Expired and Extended Highlights</h4>
                            <img src="{{ asset('public/img/02.png') }}" alt="Library manager tool">
                        </div>
                    </div>

                    <div class="item">
                        <div class="product-features-box">
                            <h4>Efficient & Seamless
                                Reporting that make you Hasselfree</h4>
                            <img src="{{ asset('public/img/03.png') }}" alt="Online library system">
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
                        <img src="{{ asset('public/img/icons/swap.png') }}" alt="Swap Seat" class="icon">
                    </div>
                    <h4>Seamless
                        Seat Swapping</h4>
                    <span>Effortlessly switch seats for the perfect spot every time—boost comfort and productivity
                        with just a click!</span>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="benefits">
                    <div class="iconbox">
                        <img src="{{ asset('public/img/icons/upgrade.png') }}" alt="Easy Plan Upgrades" class="icon">
                    </div>
                    <h4>Easy
                        Plan Upgrades</h4>
                    <span>Instantly upgrade your plan for more access and perks—unlock the best library experience
                        anytime!</span>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="benefits">
                    <div class="iconbox">
                        <img src="{{ asset('public/img/icons/reactive.png') }}" alt="Reactivate
                        Seat Access" class="icon">
                    </div>
                    <h4>Reactivate
                        Seat Access</h4>
                    <span>Quickly reactivate your seat and jump back into your favorite study spot—no waiting, just
                        instant access!</span>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="benefits">
                    <div class="iconbox">

                        <img src="{{ asset('public/img/icons/closed.png') }}" alt="Close Seat Option" class="icon">
                    </div>
                    <h4>Close
                        Seat Option</h4>
                    <span>Quickly reactivate your seat and jump back into your favorite study spot—no waiting, just
                        instant access!</span>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="benefits">
                    <div class="iconbox">

                        <img src="{{ asset('public/img/icons/cancal.png') }}" alt="Swap Seat" class="icon">
                    </div>
                    <h4>Cancel
                        Seat Booking</h4>
                    <span>Cancel your seat booking anytime with ease—flexibility for your changing schedule!</span>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="benefits">
                    <div class="iconbox">

                        <img src="{{ asset('public/img/icons/delete.png') }}" alt="Delete Seat Booking" class="icon">
                    </div>
                    <h4>Delete Seat Booking</h4>
                    <span>Easily delete seat bookings you no longer need—maintain a clear and up-to-date booking
                        history!</span>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="benefits">
                    <div class="iconbox">
                        <img src="{{ asset('public/img/icons/notification.png') }}" alt="Effortless Communication" class="icon">
                    </div>
                    <h4>Effortless Communication</h4>
                    <span>Send updates, notifications, or reminders to students instantly via WhatsApp or email directly from the portal.</span>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="benefits">
                    <div class="iconbox">

                        <img src="{{ asset('public/img/icons/membership-card.png') }}" alt="Flexible Membership Plans" class="icon">
                    </div>
                    <h4>Flexible Membership Plans</h4>
                    <span>Modify learner plans seamlessly to fit their changing needs.</span>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="benefits">
                    <div class="iconbox">
                        <img src="{{ asset('public/img/icons/reset.png') }}" alt="Quick Library Reset" class="icon">
                    </div>
                    <h4>Quick Library Reset</h4>
                    <span>Mistakes happen! Reset your library settings with just one click to start fresh.</span>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="benefits">
                    <div class="iconbox">
                        <img src="{{ asset('public/img/icons/import.png') }}" alt="Simple Data Import" class="icon">
                    </div>

                    <h4>Simple Data Import</h4>
                    <span>Import existing learner data effortlessly in a single step to get started without delays.</span>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="benefits">
                    <div class="iconbox">
                        <img src="{{ asset('public/img/icons/export.png') }}" alt="Export Data with Ease" class="icon">
                    </div>
                    <h4>Export Data with Ease</h4>
                    <span>Keep complete control by exporting your library data whenever needed.</span>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="benefits">
                    <div class="iconbox">

                        <img src="{{ asset('public/img/icons/report.png') }}" alt="Comprehensive Reporting" class="icon">
                    </div>
                    <h4>Comprehensive Reporting</h4>
                    <span>Get detailed, actionable insights to optimize your library's performance.</span>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="benefits">
                    <div class="iconbox">
                        <img src="{{ asset('public/img/icons/dashboard.png') }}" alt="Interactive Dashboard" class="icon">
                    </div>
                    <h4>Interactive Dashboard</h4>
                    <span>Access everything at a glance with our intuitive and user-friendly dashboard.</span>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="benefits">
                    <div class="iconbox">
                        <img src="{{ asset('public/img/icons/extend.png') }}" alt="Swap Seat" class="icon">
                    </div>
                    <h4>Extend Seat Usage</h4>
                    <span>Extend seat access days for learners with just a few clicks.</span>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="benefits">
                    <div class="iconbox">
                        <img src="{{ asset('public/img/icons/backups.png') }}" alt="Swap Seat" class="icon">
                    </div>
                    <h4>Automated Weekly Backups</h4>
                    <span>Rest easy knowing your library’s data is backed up automatically every week.</span>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="benefits">
                    <div class="iconbox">
                        <img src="{{ asset('public/img/icons/history.png') }}" alt="Swap Seat" class="icon">
                    </div>
                    <h4>Maintain Full Seat History</h4>
                    <span>Keep a detailed record of every seat and its usage history.</span>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="benefits">
                    <div class="iconbox">
                        <img src="{{ asset('public/img/icons/user-interface.png') }}" alt="Swap Seat" class="icon">
                    </div>
                    <h4>Simplified User Interface</h4>
                    <span>Enjoy the easiest and most intuitive UI that requires no learning curve.</span>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="benefits">
                    <div class="iconbox">
                        <img src="{{ asset('public/img/icons/direcoty-listing.png') }}" alt="Swap Seat" class="icon">
                    </div>
                    <h4>Free Directory Listing</h4>
                    <span>Get a complimentary listing in our library directory to boost visibility.</span>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="benefits">
                    <div class="iconbox">
                        <img src="{{ asset('public/img/icons/login-portal.png') }}" alt="Swap Seat" class="icon">
                    </div>
                    <h4>Learner Login Portal</h4>
                    <span>Provide learners with a secure login to access all library features effortlessly.</span>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="benefits">
                    <div class="iconbox">
                        <img src="{{ asset('public/img/icons/activities.png') }}" alt="Swap Seat" class="icon">
                    </div>
                    <h4>Track Recent Activities</h4>
                    <span>Monitor all recent activities in your library to stay updated.</span>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="benefits">
                    <div class="iconbox">
                        <img src="{{ asset('public/img/icons/expired.png') }}" alt="Swap Seat" class="icon">
                    </div>
                    <h4>Quick Action on Expired Seats</h4>
                    <span>View a dedicated listing of expiring and expired seats to take timely actions.</span>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="benefits">
                    <div class="iconbox">
                        <img src="{{ asset('public/img/icons/simple-setup.png') }}" alt="Swap Seat" class="icon">
                    </div>
                    <h4>Flexible Setup Options</h4>
                    <span>Set up your library for half-day, full-day, or hourly operations—all in one step.</span>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="benefits">
                    <div class="iconbox">
                        <img src="{{ asset('public/img/icons/revenue.png') }}" alt="Swap Seat" class="icon">
                    </div>
                    <h4>Expense and Revenue Management</h4>
                    <span>Manage your library’s expenses and calculate monthly revenue seamlessly.</span>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="benefits">
                    <div class="iconbox">
                        <img src="{{ asset('public/img/icons/training.png') }}" alt="Swap Seat" class="icon">
                    </div>
                    <h4>Online Training Videos</h4>
                    <span>Access training videos anytime to make the most of the software’s features.</span>
                </div>
            </div>

            <div class="col-lg-3">
                <div class="benefits">
                    <div class="iconbox">
                        <img src="{{ asset('public/img/icons/id-card.png') }}" alt="Swap Seat" class="icon">
                    </div>
                    <h4>Easy ID Cards & Smart Attendance Tracking</h4>
                    <span>Effortless ID cards and smart attendance tracking with Libraro – simple, accurate, and efficient!</span>
                </div>
            </div>
        </div>
        <div class="row pt-5">
            <div class="col-lg-12 text-center">
                <a href="{{url(path: '/#demo')}}" class="cta">Start Your Free Demo Today!</a>
            </div>
        </div>
    </div>

</section>

<div class="our-plan" id="pricing">
    <div class="container-fluid">
        <!-- Dynamic 3 -->
        <div class="heading mb-5 text-center">
            <span class="text-white">Libraro Plans & Pricing</span>
            <h2>Choose the Best Plan for You</h2>
        </div>
        <div class="row g-4 justify-content-center">
            <div class="col-lg-4 payment-mode">
                <select name="plan_mode" id="plan_mode" class="form-select">
                    <option value="1">MONTHLY</option>
                    <option value="2">YEARLY</option>
                </select>
            </div>
        </div>


        <div class="row mt-4 g-4 justify-content-center mb-4">

            @foreach($subscriptions as $subscription)
            @php

            $subscribedPermissions = $subscription->permissions->pluck('name')->toArray();
            @endphp

            <div class="col-lg-3">
                <div class="plan-box">
                    <div class="plan-content">
                        <h4>{{$subscription->name}}</h4>
                        <span class="d-block mb-4" id="planDescription_{{$subscription->id}}"></span>
                        <h4 id="before_discount_fees_{{$subscription->id}}" class="slash"></h4>
                        <h1 id="subscription_fees_{{$subscription->id}}"></h1>

                        <button class="btn btn-primary buy-now-btn" data-id="{{ $subscription->id }}" data-plan_mode="">Buy Now</button>
                        <span class="expiry">*Offer Valid Till 31-04-2025</span>
                    </div>
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



                </div>
            </div>
            @endforeach
        </div>


        <!-- Dynamic 3 -->
    </div>

</div>

<!-- Customer's Feedback -->
<section class="customer-feedback">

    <div class="container">
        <div class="heading mb-5">
            <span>Customer's Feedback</span>
            <h2>What Our <br>
                Happy Customers Say’s</h2>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <div class="owl-carousel" id="clientsFeedbacks">

                    @if(!($happy_customers->isEmpty()))
                    <div class="item">
                        <div class="feedback-box">
                            <img src="{{url('public/img/comma.png')}}" alt="comma" class="comma">

                            <div class="message">As the <b>Founder & Director</b>, I created Libraro to simplify library operations with automation, seamless bookings, and powerful analytics. It's the all-in-one solution for modern libraries!</div>
                            <div class="customer-info">
                                <img src="{{ asset('public/img/pawan-profile.jpg') }}" alt="user" class="profile rounded-circle">
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
                    <div class="item">
                        <div class="feedback-box">
                            <img src="{{url('public/img/comma.png')}}" alt="comma" class="comma">

                            <div class="message">As the Developer of Libraro, I built this platform to streamline library operations with automation, intuitive booking, and advanced analytics. Designed for efficiency, it's the ultimate tool for modern libraries!</div>
                            <div class="customer-info">
                                <img src="{{ asset('public/img/user2.png') }}" alt="user" class="profile">
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
                    </div>
                    <div class="item">
                        <div class="feedback-box">
                            <img src="{{url('public/img/comma.png')}}" alt="comma" class="comma">

                            <div class="message">We’ve been using Library Manager for over a year now, and it has exceeded all our expectations. The analytics and reporting features provide valuable insights. It’s an all-in-one solution for modern library management!</div>
                            <div class="customer-info">
                                <img src="{{ asset('public/img/user2.png') }}" alt="user" class="profile">
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
                            <img src="{{url('public/img/comma.png')}}" alt="comma" class="comma">
                            <div class="message">{{$value->description ?? ''}}</div>
                            <div class="customer-info">
                                <img src="{{ asset('public/img/pawan.png') }}" alt="user" class="profile">
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
                            <img src="{{url('public/img/comma.png')}}" alt="comma" class="comma">

                            <div class="message">As the <b>Founder & Director</b>, I created Libraro to simplify library operations with automation, seamless bookings, and powerful analytics. It's the all-in-one solution for modern libraries!</div>
                            <div class="customer-info">
                                <img src="{{ asset('public/img/pawan-profile.jpg') }}" alt="user" class="profile rounded-circle">
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
                    <div class="item">
                        <div class="feedback-box">
                            <img src="{{url('public/img/comma.png')}}" alt="comma" class="comma">

                            <div class="message">As the Developer of Libraro, I built this platform to streamline library operations with automation, intuitive booking, and advanced analytics. Designed for efficiency, it's the ultimate tool for modern libraries!</div>
                            <div class="customer-info">
                                <img src="{{ asset('public/img/user2.png') }}" alt="user" class="profile">
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
                    </div>
                    <div class="item">
                        <div class="feedback-box">
                            <img src="{{url('public/img/comma.png')}}" alt="comma" class="comma">

                            <div class="message">We’ve been using Library Manager for over a year now, and it has exceeded all our expectations. The analytics and reporting features provide valuable insights. It’s an all-in-one solution for modern library management!</div>
                            <div class="customer-info">
                                <img src="{{ asset('public/img/user2.png') }}" alt="user" class="profile">
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
                                <strong>Answer</strong> Libraro is a comprehensive library management portal designed to simplify and automate library operations. It allows you to manage books, track issued and returned items, and generate reports efficiently, all through a user-friendly interface.
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
                                <strong>Answer</strong> Libraro is suitable for schools, colleges, universities, public libraries, and private libraries looking for a modern solution to streamline their library management processes.
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
                                <strong>Answer</strong> Yes, Libraro is accessible on desktops, laptops, tablets, and smartphones, ensuring convenience for library staff and users anytime, anywhere.
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
                                <strong>Answer</strong> Absolutely! Libraro allows you to import existing data in bulk using easy-to-use templates, making the transition seamless for your library.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#faq_05" aria-expanded="false" aria-controls="faq_05">
                                Qus 5: Does Libraro support multiple users and roles?
                            </button>
                        </h2>
                        <div id="faq_05" class="accordion-collapse collapse"
                            data-bs-parent="#accordionExample">
                            <div class="accordion-body">
                                <strong>Answer</strong> Yes, Libraro supports multiple user roles, such as administrators, librarians, and members. Each role has customized permissions to ensure smooth and secure operations.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#faq_06" aria-expanded="false" aria-controls="faq_06">
                                Qus 6: Is my library data secure with Libraro?
                            </button>
                        </h2>
                        <div id="faq_06" class="accordion-collapse collapse"
                            data-bs-parent="#accordionExample">
                            <div class="accordion-body">
                                <strong>Answer</strong> Security is our top priority. Libraro uses advanced encryption and data protection measures to ensure your library's data is safe and accessible only to authorized users.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                data-bs-target="#faq_07" aria-expanded="false" aria-controls="faq_07">
                                Qus 7: How do I get support if I face any issues?
                            </button>
                        </h2>
                        <div id="faq_07" class="accordion-collapse collapse"
                            data-bs-parent="#accordionExample">
                            <div class="accordion-body">
                                <strong>Answer</strong> We provide dedicated customer support via email, phone, and chat to assist you with any technical or operational queries. You can also access our online help documentation for quick solutions.
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
            <div class="col-lg-6">
                <h2 class="mb-4">Would you like to <br><span>Schedule a free Demo?</span></h2>
                <form class="me-3" id="demoRequest">
                    @csrf
                    <input type="hidden" name="databasemodel" value="DemoRequest">
                    <div class="form-box">
                        <div class="row g-3">
                            <div class="col-lg-12">
                                <label for="full_name">Full Name <span class="text-danger">*</span></label>
                                <input type="text" name="full_name" class="form-control @error('full_name') is-invalid @enderror char-only" placeholder="Enter your Name" autocomplete="off" id="full_name">

                            </div>
                            <div class="col-lg-12">
                                <label for="mobile_number">Mobile Number <span class="text-danger">*</span></label>
                                <input type="text" name="mobile_number" class="form-control @error('mobile_number') is-invalid @enderror digit-only" placeholder="Enter Mobile Number" minlength="8" maxlength="10" autocomplete="off" id="mobile_number">
                                @error('mobile_number')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                            <div class="col-lg-12">
                                <label for="email">Email Id <span class="text-danger">*</span></label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" placeholder="Enter Email Address" autocomplete="off" id="email">

                            </div>
                            <div class="col-lg-12">
                                <label for="preferred_date">Preferred Date <span class="text-danger">*</span></label>
                                <input type="date" name="preferred_date" class="form-control @error('preferred_date') is-invalid @enderror" id="preferred_date">

                            </div>
                            <div class="col-lg-12">
                                <label for="timeSlot">Preferred Time (Optional)</label>
                                <select class="form-select no-validate" id="timeSlot" name="preferred_time">
                                    <option value="">Select Time Slot</option>
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
                                <small class="text-danger">*We will call you at your preferred time based on our availability.</small>
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
            <div class="col-lg-6">
                <div class="main-box">
                    <div class="support">
                        <img src="{{ asset('public/img/direcotry/call.png') }}" alt="call">
                        <h4>We Are Here
                            to Assist you</h4>
                        <p class="m-0">Call : <a href="tel:91-8114479678">91-8114479678</a></p>
                        <p>Mail : <a href="mailto:info@libraro.in">info@libraro.in</a></p>
                    </div>
                    <img src="{{ asset('public/img/contact.png') }}" alt="support" class="img-fluid">
                </div>
            </div>
        </div>
    </div>
</section>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>

    <script>
        $('#featureSlider').owlCarousel({
            loop: true,
            nav: true,
            dots: true,
            margin: 20,
            navText: ['<i class="las la-angle-left arrow-left"></i>', '<i class="las la-angle-right arrow-right"></i>'],
            pagination: true,
            autoplay: true,
            autoPlaySpeed: 2000,
            smartSpeed: 2000,
            autoplayTimeout: 5000,
            autoplayHoverPause: true,
            responsive: {
                0: {
                    items: 1,
                    nav: false,
                },
                768: {
                    items: 2,
                    nav: false,
                },
                992: {
                    items: 3,
                },
                1200: {
                    items: 3,
                },
                1920: {
                    items: 4,
                }
            }
        });
    </script>

    <script>
        $('#clientsFeedbacks').owlCarousel({
            loop: true,
            nav: true,
            dots: true,
            margin: 20,
            navText: ['<i class="las la-angle-left arrow-left"></i>', '<i class="las la-angle-right arrow-right"></i>'],
            pagination: true,
            autoplay: true,
            autoPlaySpeed: 2000,
            smartSpeed: 2000,
            autoplayTimeout: 5000,
            autoplayHoverPause: true,
            responsive: {
                0: {
                    items: 1,
                    nav: false,
                },
                768: {
                    items: 2,
                    nav: false,
                },
                992: {
                    items: 3,
                },
                1200: {
                    items: 3,
                },
                1920: {
                    items: 4,
                }
            }
        });
    </script>

    <script>
        $(document).ready(function() {
            $('#demoRequest').on('submit', function(e) {
                e.preventDefault();

                var formData = new FormData(this);

                $.ajax({
                    url: '{{ route("demo-request") }}',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(response) {
                        console.log(response);

                        if (response.status === 'success') {
                            toastr.success(response.message);

                            // Clear error messages and reset form
                            $(".is-invalid").removeClass("is-invalid");
                            $(".invalid-feedback").remove();

                            // Optionally, reset the form after success
                            $('#demoRequest')[0].reset();
                            $("#error-message").hide();
                        } else {
                            $("#error-message").text(response.message).show();
                            $("#success-message").hide();
                        }
                    },
                    error: function(xhr) {
                        var response = xhr.responseJSON;

                        if (xhr.status === 422 && response.errors) { // Validation error check
                            $(".is-invalid").removeClass("is-invalid");
                            $(".invalid-feedback").remove();

                            $.each(response.errors, function(key, value) {
                                var element = $("[name='" + key + "']");
                                element.addClass("is-invalid");
                                element.after('<span class="invalid-feedback" role="alert">' + value[0] + '</span>');
                            });
                        } else {
                            console.error('AJAX Error:', xhr.responseText);
                            alert('There was an error processing the request. Please try again.');
                        }
                    }
                });
            });
        });
    </script>

@endsection