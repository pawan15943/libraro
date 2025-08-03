<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Libraro </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">
    <!-- Owl Carousel CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css">

  
    <link rel="stylesheet" href="{{ asset('public/css/home-style.css')}}">

    <style>
        h2 {
            /* background: -webkit-linear-gradient(45deg, #00116d, #00cbff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent; */
        }

        .head {
            padding: 3.5rem 0;
            background: linear-gradient(45deg, #3F51B5, #00BCD4);
        }

        .head .form-box {
            background: #fff;
            padding: 1.5rem;
            border-radius: 1rem;
        }

        .head .form-group a {
            padding: 0;
            font-size: inherit;
            margin: 0;
            display: inline-block;
            margin-top: -.3rem ! IMPORTANT;
        }

        .head .form-box .btn {
            width: 100%;
            background: navy;
            border-color: navy;
            border-radius: .5rem;
            box-shadow: 1px 0 5px #00000021;
            font-weight: 600;
            font-family: 'Outfit', 'sans-serif';
        }

        .head .form-control {
            font-family: 'Outfit', 'sans-serif';
            font-size: .9rem;
        }

        .head h1 {
            color: #fff;
        }

        .head p {
            color: #fff;
        }

        .head a {
            background: #fff;
            text-decoration: none;
            padding: .8rem 1.5rem;
            display: inline-block;
            border-radius: .5rem;
            font-family: 'Outfit', 'sans-serif';
        }


        .why-choose-us {
            padding: 3.5rem;
        }

        .why__box img {
            width: 50px;
            margin-bottom: 1.5rem;
        }

        .why-choose-us .why__box {
            border: 1px solid #dedede;
            padding: 1.5rem;
            height: 305px;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            border-radius: 1.5rem;
        }

        .why-choose-us .why__box h4 {
            margin: 0;
            padding: 0;
            margin-bottom: 0.5rem;
            font-size: 1.2rem;
        }

        .why-choose-us .why__box span {
            font-size: .9rem;
            color: #858383;
        }

        .libraro-features.py-5 {
            background: linear-gradient(180deg, #efefff, transparent);
        }

        .featuresss h4 {
            font-size: 1.2rem;
            color: navy;
            margin-bottom: 1.5rem;
            min-height: 45px;
        }

        .featuresss {
            border: 1px solid #dedede;
            padding: 1.5rem;
            border-radius: 1.5rem;
            height: 100%;
            background: #fff;
        }

        .libraro-cta.py-5 {
            background: linear-gradient(45deg, navy, #00BCD4);
        }

        .libraro-cta.py-5 * {
            color: #fff;
        }

        .libraro-cta.py-5 a {
            background: #fff;
            margin-top: 1.5rem;
            padding: .7rem 1.5rem;
            display: inline-block;
            color: #000;
            font-weight: 600;
            font-family: 'outfit', 'sans-sarif';
        }

        .steps {
            padding: 1.5rem;
            background: #d7faff;
            height: 100%;
            border-radius: 1.5rem;
        }

        .steps h4 {
            font-size: 1rem;
            padding: 1rem 0;
        }

        .steps span {
            font-size: .9rem;
        }

        .steps .step-number {
            font-weight: 700;
            display: inline-flex;
            font-family: 'Outfit';
            background: #3F51B5;
            color: #fff;
            width: 30px;
            height: 30px;
            justify-content: center;
            align-items: center;
            border-radius: 30px;
            font-size: .8rem;
        }

    </style>
</head>

<body>

    <!-- Navigation -->
    <header>
        <div class="container">
            <div class="row">
                <div class="col-lg-6">
                    <img src="https://libraro.in/public/img/libraro.svg" alt="logo" class="logo">
                </div>
                <div class="col-lg-6"></div>
            </div>
        </div>
    </header>

    <!-- Head Section -->
    <section class="head">
        <div class="container">
            <div class="row g-4 align-items-center">
                <div class="col-lg-8">
                    <h1 class="m-0 mb-2">India's #1 Library Management Portal – LIBRARO</h1>
                    <p class="m-0 mb-4">Simplify your library operations with our all-in-one platform</p>
                    <a href="">Book your FREE Demo</a>
                </div>
                <div class="col-lg-4">
                    <form class="me-3" id="leadForm">
                        @csrf
                        <input type="hidden" name="databasetable" value="DemoRequest">
                        <div class="form-box">
                            <h4 class="mb-4">Request a Callback</h4>
                            <div class="row g-4">
                                <div class="col-lg-12">
                                    <input type="text" class="form-control char-only" placeholder="Enter your Library Name" name="library_name">
                                </div>
                                <div class="col-lg-12">
                                    <input type="email" class="form-control" placeholder="Enter Email Id" name="email">
                                </div>
                                <div class="col-lg-12">
                                    <input type="text" class="form-control digit-only" placeholder="Enter your contact Number" name="contact_number" maxlength="10" minlength="10">
                                </div>
                                <div class="col-lg-12">
                                    <div class="form-group">
                                        <input type="checkbox" class="form-check-input " name="terms" id="terms" autocomplete="off">
                                        <label class="form-check-label" for="terms" style="font-size: .8rem !important">
                                            I agree to the Libraro <a href="#">Terms and Conditions.</a><sup class="text-danger">*</sup>
                                        </label>
                                        <span class="terms" role="alert"></span>
                                    </div>
                                </div>
                                <div class="col-lg-12">
                                    <input type="submit" class="btn btn-primary">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Why Choose Libraro -->
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

    <!-- Library Features -->
    <section class="product-benefits">
        <div class="container">
            <div class="heading mb-5 text-center">
                <h2>Features of LIBRARO</h2>
            </div>
            <div class="row g-4">
                <div class="col-lg-3">
                    <div class="benefits">
                        <div class="iconbox">

                            <img src="https://www.libraro.in/public/img/libraro-features/detailed-dashboard.png" alt="Delete Seat Booking" class="icon">
                        </div>
                        <h4>Interactive &amp; Insightful Dashboard</h4>
                        <span>Get a complete overview of your library with an intuitive and visually engaging
                            dashboard.</span>
                    </div>
                </div>

                <div class="col-lg-3">
                    <div class="benefits">
                        <div class="iconbox">
                            <img src="https://www.libraro.in/public/img/libraro-features/user-interface.png" alt="Easy Plan Upgrades" class="icon">
                        </div>
                        <h4>Seamless &amp; Intuitive User Interface</h4>
                        <span>Our platform is designed for an effortless user experience, making navigation smooth and
                            hassle-free.</span>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="benefits">
                        <div class="iconbox">

                            <img src="https://www.libraro.in/public/img/libraro-features/import-data.png" alt="Close Seat Option" class="icon">
                        </div>
                        <h4>One-Click Data Import</h4>
                        <span>Effortlessly migrate your existing data into our system with just a single click.</span>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="benefits">
                        <div class="iconbox">
                            <img src="https://www.libraro.in/public/img/libraro-features/seat-management.png" alt="Reactivate
                        Seat Access" class="icon">
                        </div>
                        <h4>Smart Seat Management</h4>
                        <span>Easily track Expired and Extended seats with a dedicated section for better
                            organization.</span>
                    </div>
                </div>

                <div class="col-lg-3">
                    <div class="benefits">
                        <div class="iconbox">
                            <img src="https://www.libraro.in/public/img/libraro-features/data-security.png" alt="Swap Seat" class="icon" loading="lazy">
                        </div>
                        <h4>End-to-End Encryption &amp; Data Security</h4>
                        <span>Rest assured, only the library owner has access to learners' email and mobile details,
                            ensuring complete privacy.</span>
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
                    <a href="https://www.libraro.in/#demo" class="cta mt-4" style="display: inline-block;">Get Started
                        Today!</a>
                </div>
            </div>
        </div>
    </section>
    <!-- Watch Libraro Highlights -->
    <section class="libraro-highlights py-5">
        <div class="container">
            <h2 class="mb-5 text-center">How LIBRARO Works</h2>
            <div class="row ">
                <div class="col-lg-3">
                    <div class="steps">
                        <span class="step-number">01</span>
                        <h4>Register Your Library</h4>
                        <span>Create your account and register your library to get started.</span>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="steps">
                        <span class="step-number">02</span>
                        <h4>Configure Library Settings</h4>
                        <span>Customize your library preferences, policies, and user roles.</span>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="steps">
                        <span class="step-number">03</span>
                        <h4>Import Library Data</h4>
                        <span>Easily upload members, and transaction records.</span>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="steps">
                        <span class="step-number">04</span>
                        <h4>You're All Set</h4>
                        <span>Start managing your library efficiently with LIBRARO.</span>
                    </div>
                </div>
            </div>
            <div class="row g-4 justify-content-center">
                <div class="col-lg-6">
                    <div class="video-box">

                    </div>
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
                            <h4 id="before_discount_fees_{{$subscription->id}}"></h4>
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
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq_01" aria-expanded="true" aria-controls="faq_01">
                                    Qus 1: What is Libraro, and how does it work?
                                </button>
                            </h2>
                            <div id="faq_01" class="accordion-collapse collapse show" data-bs-parent="#accordionExample">
                                <div class="accordion-body">
                                    <strong>Answer</strong> Libraro is a comprehensive library management portal
                                    designed to simplify and automate library operations.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq_02" aria-expanded="false" aria-controls="faq_02">
                                    Qus 2: Who can use Libraro?
                                </button>
                            </h2>
                            <div id="faq_02" class="accordion-collapse collapse" data-bs-parent="#accordionExample">
                                <div class="accordion-body">
                                    <strong>Answer</strong> Libraro is suitable for public libraries, and private
                                    libraries looking for a modern solution to streamline their library management
                                    processes.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq_03" aria-expanded="false" aria-controls="faq_03">
                                    Qus 3: Is Libraro compatible with different devices?
                                </button>
                            </h2>
                            <div id="faq_03" class="accordion-collapse collapse" data-bs-parent="#accordionExample">
                                <div class="accordion-body">
                                    <strong>Answer</strong> Yes, Libraro is accessible on desktops (Preffered), laptops,
                                    tablets, and smartphones (Support Available Soon), ensuring convenience for library
                                    staff and users anytime, anywhere.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq_04" aria-expanded="false" aria-controls="faq_04">
                                    Qus 4: Can I import my existing library data into Libraro?
                                </button>
                            </h2>
                            <div id="faq_04" class="accordion-collapse collapse" data-bs-parent="#accordionExample">
                                <div class="accordion-body">
                                    <strong>Answer</strong> Absolutely! Libraro allows you to import existing data in
                                    bulk using easy-to-use templates (.csv file), making the transition seamless for
                                    your library.
                                </div>
                            </div>
                        </div>

                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq_06" aria-expanded="false" aria-controls="faq_06">
                                    Qus 5: Is my library data secure with Libraro?
                                </button>
                            </h2>
                            <div id="faq_06" class="accordion-collapse collapse" data-bs-parent="#accordionExample">
                                <div class="accordion-body">
                                    <strong>Answer</strong> Security is our top priority. Libraro uses end to end
                                    encryption (for Learner Mobile and Email) and data protection measures to ensure
                                    your library's data is safe and accessible only to authorized users.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq_07" aria-expanded="false" aria-controls="faq_07">
                                    Qus 6: How do I get support if I face any issues?
                                </button>
                            </h2>
                            <div id="faq_07" class="accordion-collapse collapse" data-bs-parent="#accordionExample">
                                <div class="accordion-body">
                                    <strong>Answer</strong> We provide dedicated customer support via email
                                    (support@libraro.in), phone (+91-8114479678, +91-7737918848), and chat to assist you
                                    with any technical or operational queries.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
   

    <section class="libraro-cta py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-12 text-center">
                    <h2 class="mb-4">Ready to Transform Your Library?</h2>
                    <p>Book a FREE demo today and experience the difference.</p>
                    <a href="" class="btn">Request a Callback</a>
                </div>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="row">
                <div class="col-lg-6">
                    <img src="https://libraro.in/public/img/libraro-white.svg" alt="logo" class="logo">
                </div>
            </div>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Input restrictions
        $(document).on('input', '.digit-only', function() {
            this.value = this.value.replace(/[^0-9.]/g, '');
        });

        $(document).on('input', '.char-only', function() {
            this.value = this.value.replace(/[^a-zA-Z ]/g, '');
        });

        $(document).on('input', '.char-with-sps', function() {
            this.value = this.value.replace(/[^a-zA-Z!@#\$%\^\&*\)\(+=._-\s]/g, '');
        });

        $('input[type="file"]').on('change', function() {
            $(this).valid();
        });

    </script>
    <script>
        $(document).ready(function() {
            $('#leadForm').on('submit', function(e) {
                e.preventDefault();

                var formData = new FormData(this);

                $.ajax({
                    url: '{{ route("lead.store") }}'
                    , type: 'POST'
                    , data: formData
                    , processData: false
                    , contentType: false
                    , dataType: 'json'
                    , success: function(response) {
                        console.log(response);

                        if (response.status === 'success') {
                            toastr.success(response.message);

                            // Clear error messages and reset form
                            $(".is-invalid").removeClass("is-invalid");
                            $(".invalid-feedback").remove();

                            // Optionally, reset the form after success
                            $('#leadForm')[0].reset();
                            $("#error-message").hide();
                        } else {
                            $("#error-message").text(response.message).show();
                            $("#success-message").hide();
                        }
                    }
                    , error: function(xhr) {
                        var response = xhr.responseJSON;

                        if (xhr.status === 422 && response.errors) { // Validation error check
                            $(".is-invalid").removeClass("is-invalid");
                            $(".invalid-feedback").remove();

                            $.each(response.errors, function(key, value) {
                                var element = $("[name='" + key + "']");
                                element.addClass("is-invalid");
                              

                                if (key === 'terms') {
                                   
                                    $(".terms").html('<span class="invalid-feedback d-block" role="alert">' + value[0] + '</span>');
                                } else {
                                    element.after('<span class="invalid-feedback" role="alert">' + value[0] + '</span>');
                                }
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
<!-- Home Page Scripts -->
    <script>
        
        $('#featureSlider').owlCarousel({
            loop: true,
            nav: false,
            margin: 20,
            autoplay: true,
            autoplaySpeed: 2000,
            smartSpeed: 2000,
            autoplayTimeout: 5000,
            autoplayHoverPause: true,
            responsive: {
                0: {
                    items: 1,
                    nav: false
                },
                768: {
                    items: 2,
                    nav: false
                },
                992: {
                    items: 3
                },
                1200: {
                    items: 3
                },
                1920: {
                    items: 4
                }
            }
        });
    </script>

    <script>
        $('#clientsFeedbacks').owlCarousel({
            loop: true,
            nav: false,
            dots: true,
            margin: 20,
            pagination: true,
            autoplay: true,
            autoPlaySpeed: 2000,
            smartSpeed: 2000,
            autoplayTimeout: 5000,
            autoplayHoverPause: true,
            responsive: {
                0: {
                    items: 1,
                },
                768: {
                    items: 2,
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
</body>

</html>
