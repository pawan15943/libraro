@extends('sitelayouts.layout')
@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
<link rel="stylesheet" href="{{ asset('public/css/library-directory-upgrade.css') }}?v={{ time() }}" />

<div class="directory-upgrade-wrap" id="upgraded-directory-page">

    <!-- Section 1: Hero Search Section with High Contrast Overlay & Quick Support -->
    <section class="hero_Section_directory" style="background: url('{{ asset('/public/img/direcotry/bg-head.png') }}') no-repeat center center; background-size:cover;">
        <div class="container">
            <div class="row h-100 align-items-center">
                <div class="col-lg-12">
                    <div class="row align-items-center justify-content-center">
                        <div class="col-lg-10 col-xl-9">
                            <h1>Find the Best Libraries Near You in India</h1>
                            <p class="hero-subtitle">Discover top-rated, premium, and self-study libraries with real-time seat availability &amp; amenities.</p>

                            <div class="dir-search-box-card">
                                <div class="city-select-wrap">
                                    <i class="fa-solid fa-location-dot select-icon"></i>
                                    <select id="city-item" class="form-select" aria-label="Select City">
                                        <option value="">All Cities</option>
                                        @foreach($cities as $key => $value)
                                            <option value="{{$key}}">{{$value}}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="keyword-input-wrap">
                                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                                    <input type="text" id="library-search" class="form-control" placeholder="Search by library name, area, or landmark..." autocomplete="off">
                                    <i class="fa-solid fa-circle-xmark clear-icon" id="clear-search-btn" title="Clear Search"></i>
                                </div>

                                <button type="button" id="btn-trigger-search" class="dir-search-btn">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                    <span>Search</span>
                                </button>
                            </div>

                            <!-- Quick Support Pill Bar in Hero -->
                            <div class="hero-quick-support-bar">
                                <span class="support-label"><i class="fa-solid fa-headset text-warning"></i> Need quick assistance?</span>
                                <a href="tel:+918114479678" class="support-pill">
                                    <i class="fa-solid fa-phone"></i> +91-8114479678
                                </a>
                                <a href="https://wa.me/918114479678?text=Hi%20Libraro,%20I%20need%20help%20finding%20a%20library" target="_blank" class="support-pill pill-whatsapp">
                                    <i class="fa-brands fa-whatsapp"></i> WhatsApp Support
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 2: Explore Libraries Directory Section with Category Tabs & 4-5 in a Row Layout -->
    <section class="directory-main-section" id="directory-explore">
        <div class="container">
            <div class="dir-header-bar">
                <div>
                    <h2 class="dir-heading-title">
                        Explore Libraries
                        <span class="dir-results-badge" id="results-count-badge">
                            <i class="fa-solid fa-circle-notch fa-spin me-1"></i> Loading...
                        </span>
                    </h2>
                </div>

                <div class="category-pills-wrap" role="tablist">
                    <button type="button" class="category-pill active" data-category="all">
                        <i class="fa-solid fa-border-all"></i> All Categories
                    </button>
                    <button type="button" class="category-pill" data-category="premium">
                        <i class="fa-solid fa-crown text-warning"></i> Premium
                    </button>
                    <button type="button" class="category-pill" data-category="top_rated">
                        <i class="fa-solid fa-star text-warning"></i> Top Rated
                    </button>
                    <button type="button" class="category-pill" data-category="other">
                        <i class="fa-solid fa-building-columns"></i> Other
                    </button>
                </div>
            </div>

            <!-- Dynamic Container for Categorized Lists or Filtered Grid -->
            <div id="libraries-main-display">
                <!-- Initial Shimmer Placeholder (10 cards for 4-5 in a row) -->
                <div class="row g-4 libraries-grid" id="libraries-shimmer-initial">
                    @for ($i = 0; $i < 10; $i++)
                    <div class="col-xxl-20 col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12">
                        <div class="shimmer-card">
                            <div class="dir-shimmer shimmer-img"></div>
                            <div class="dir-shimmer shimmer-title"></div>
                            <div class="dir-shimmer shimmer-line"></div>
                            <div class="dir-shimmer shimmer-line short"></div>
                            <div class="dir-shimmer shimmer-btn mt-3"></div>
                        </div>
                    </div>
                    @endfor
                </div>
            </div>
        </div>
    </section>

    <!-- Floating Quick Support Widget (Bottom-Right) -->
    <div class="dir-floating-support">
        <a href="https://wa.me/918114479678?text=Hi%20Libraro,%20I%20need%20help%20finding%20a%20library" target="_blank" class="support-btn btn-whatsapp" title="WhatsApp Support">
            <i class="fa-brands fa-whatsapp"></i>
        </a>
        <a href="tel:+918114479678" class="support-btn btn-call" title="Call Support: +91-8114479678">
            <i class="fa-solid fa-phone"></i>
        </a>
    </div>

    <!-- Section 3: Interactive Leaflet Map Across India -->
    <section class="google-map pt-5">
        <h2 class="mb-4 text-center" style="font-family: 'Outfit', sans-serif; font-weight: 600; color: #18225f;">Explore Our Libraries Across India – Interactive Map!</h2>
        <div id="map" style="width: 100%; height: 550px;"></div>
    </section>

    <!-- Section 4: Important Counters -->
    <section class="important-counts">
        <div class="container">
            <h2 class="mb-5">Important Factors <br>That Make Us the Right Choice</h2>
            <div class="row g-4">
                <div class="col-lg-3 col-md-6 col-6">
                    <h1 class="counter" data-count="{{$library_count}}">0</h1>
                    <p>Library Enrolled</p>
                </div>
                <div class="col-lg-3 col-md-6 col-6">
                    <h1 class="counter" data-count="{{$learner_counter}}">0</h1>
                    <p>Learner Enrolled</p>
                </div>
                <div class="col-lg-3 col-md-6 col-6">
                    <h1 class="counter" data-count="{{$city_count}}">0</h1>
                    <p>Total Cities</p>
                </div>
                <div class="col-lg-3 col-md-6 col-6">
                    <h1 class="counter" data-count="{{$feedback_count}}">0</h1>
                    <p>Customers trusts</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 5: Dynamic Customer Feedback & Testimonials -->
    <section class="customer-feedback">
        <div class="container">
            <div class="heading mb-5">
                <span>Customer's Feedback</span>
                <h2>What Our <br>Happy Customers Say</h2>
            </div>
            <div class="row">
                <div class="col-lg-12">
                    <div class="owl-carousel owl-theme" id="clientsFeedbacks">
                        <!-- Founder Spotlight -->
                        <div class="item">
                            <div class="feedback-box">
                                <img src="{{url('public/img/comma.png')}}" alt="comma" class="comma">
                                @php
                                    $founderMsg = "As the Founder & Director, I created Libraro to simplify library operations with automation, seamless bookings, and powerful analytics. It's the all-in-one solution for modern libraries!";
                                    $isLongFounder = mb_strlen($founderMsg) > 130;
                                @endphp
                                <div class="message">
                                    <div class="message-clamp">{{ $founderMsg }}</div>
                                    <button type="button" 
                                            class="btn-view-feedback-modal {{ $isLongFounder ? '' : 'd-none' }}" 
                                            data-full-text="{{ $founderMsg }}" 
                                            data-customer-name="Pawan Rathore" 
                                            data-customer-sub="Founder: Libraro" 
                                            data-customer-image="{{ asset('public/img/pawan-profile.jpg') }}" 
                                            data-rating="5">
                                        View all <i class="fa-solid fa-arrow-right-long ms-1"></i>
                                    </button>
                                </div>
                                <div class="customer-info">
                                    <img src="{{ asset('public/img/pawan-profile.jpg') }}" alt="Pawan Rathore" class="profile rounded-circle">
                                    <div class="customer-details">
                                        <h4 title="Pawan Rathore">Pawan Rathore</h4>
                                        <span title="Founder: Libraro">Founder: Libraro</span>
                                    </div>
                                    <ul class="customer-ratings" aria-label="5 out of 5 stars">
                                        <li><i class="fa-solid fa-star star-active"></i></li>
                                        <li><i class="fa-solid fa-star star-active"></i></li>
                                        <li><i class="fa-solid fa-star star-active"></i></li>
                                        <li><i class="fa-solid fa-star star-active"></i></li>
                                        <li><i class="fa-solid fa-star star-active"></i></li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Real Dynamic Customer Feedback from Database -->
                        @if(!empty($happy_customers) && count($happy_customers) > 0)
                            @foreach($happy_customers as $key => $value)
                            <div class="item">
                                <div class="feedback-box">
                                    <img src="{{url('public/img/comma.png')}}" alt="comma" class="comma">
                                    @php
                                        $rawMsg = $value->description ?? 'Libraro has transformed how we manage our library seats, learners, and payments with top-notch ease.';
                                        $custName = !empty($value->library_owner) ? $value->library_owner : (!empty($value->library_name) ? $value->library_name : 'Library Partner');
                                        $custSub = !empty($value->library_name) ? (!empty($value->city_name) ? $value->library_name . ' • ' . $value->city_name : $value->library_name) : (!empty($value->city_name) ? $value->city_name : 'Verified Partner');
                                        $ratingVal = (int)($value->rating ?? 5);
                                        $isLong = mb_strlen($rawMsg) > 130 || substr_count($rawMsg, "\n") >= 3;
                                    @endphp
                                    <div class="message">
                                        <div class="message-clamp">{{ $rawMsg }}</div>
                                        <button type="button" 
                                                class="btn-view-feedback-modal {{ $isLong ? '' : 'd-none' }}" 
                                                data-full-text="{{ $rawMsg }}" 
                                                data-customer-name="{{ $custName }}" 
                                                data-customer-sub="{{ $custSub }}" 
                                                data-customer-image="{{ asset('public/img/user.png') }}" 
                                                data-rating="{{ $ratingVal }}">
                                            View all <i class="fa-solid fa-arrow-right-long ms-1"></i>
                                        </button>
                                    </div>
                                    <div class="customer-info">
                                        <img src="{{ asset('public/img/user.png') }}" alt="Customer" class="profile">
                                        <div class="customer-details">
                                            <h4 title="{{ $custName }}">{{ $custName }}</h4>
                                            <span title="{{ $custSub }}">{{ $custSub }}</span>
                                        </div>
                                        <ul class="customer-ratings" aria-label="{{ $ratingVal }} out of 5 stars">
                                            @for($i = 1; $i <= 5; $i++)
                                                <li><i class="fa-solid fa-star {{ $i <= $ratingVal ? 'star-active' : 'star-inactive' }}"></i></li>
                                            @endfor
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        @else
                            <div class="item">
                                <div class="feedback-box">
                                    <img src="{{url('public/img/comma.png')}}" alt="comma" class="comma">
                                    @php
                                        $sandeepMsg = "We’ve been using Libraro for over a year now, and it has exceeded all our expectations. The analytics and reporting features provide valuable insights. It’s an all-in-one solution for modern library management!";
                                        $isLongSandeep = mb_strlen($sandeepMsg) > 130;
                                    @endphp
                                    <div class="message">
                                        <div class="message-clamp">{{ $sandeepMsg }}</div>
                                        <button type="button" 
                                                class="btn-view-feedback-modal {{ $isLongSandeep ? '' : 'd-none' }}" 
                                                data-full-text="{{ $sandeepMsg }}" 
                                                data-customer-name="Sandeep Rathor" 
                                                data-customer-sub="Libraro Manager" 
                                                data-customer-image="{{ asset('public/img/user.png') }}" 
                                                data-rating="5">
                                            View all <i class="fa-solid fa-arrow-right-long ms-1"></i>
                                        </button>
                                    </div>
                                    <div class="customer-info">
                                        <img src="{{ asset('public/img/user.png') }}" alt="user" class="profile">
                                        <div class="customer-details">
                                            <h4 title="Sandeep Rathor">Sandeep Rathor</h4>
                                            <span title="Libraro Manager">Libraro Manager</span>
                                        </div>
                                        <ul class="customer-ratings" aria-label="5 out of 5 stars">
                                            <li><i class="fa-solid fa-star star-active"></i></li>
                                            <li><i class="fa-solid fa-star star-active"></i></li>
                                            <li><i class="fa-solid fa-star star-active"></i></li>
                                            <li><i class="fa-solid fa-star star-active"></i></li>
                                            <li><i class="fa-solid fa-star star-active"></i></li>
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

    <!-- Section 6: Frequently Asked Questions -->
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
                                    <strong>Answer:</strong> Libraro is a comprehensive cloud-based library management platform designed to simplify and automate library admissions, seat allocations, and operations.
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
                                    <strong>Answer:</strong> Libraro is suitable for study libraries, reading rooms, digital libraries, and competitive exam centers looking for a modern solution to streamline their management.
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
                                    <strong>Answer:</strong> Yes, Libraro is fully responsive and accessible on laptops, desktops, tablets, and smartphones, ensuring convenience for library staff and learners anytime, anywhere.
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
                                    <strong>Answer:</strong> Absolutely! Libraro allows you to import existing learner and payment data in bulk using easy-to-use CSV templates, making the transition quick and seamless.
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
                                    <strong>Answer:</strong> Security is our top priority. Libraro uses end-to-end data encryption, automated backups, and role-based access control to ensure your data is always safe and accessible only to authorized administrators.
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
                                    <strong>Answer:</strong> We provide dedicated customer support via email (<a href="mailto:support@libraro.in">support@libraro.in</a>), phone (+91-8114479678, +91-7737918848), and instant WhatsApp assistance.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Section 7: Quick Support & Schedule Demo -->
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
                        <input type="hidden" name="databasemodel" value="DemoRequest">
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
                                    <input type="checkbox" class="me-2 form-check-input" name="terms" id="terms">
                                    <label class="form-check-label" for="terms">
                                        I agree to the Libraro <a href="{{ route('term-and-condition') }}">Terms and Conditions.</a><sup class="text-danger">*</sup>
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
                            <img src="{{ asset('public/img/direcotry/call.png') }}" alt="call">
                            <h4>We Are Here to Assist you</h4>
                            <p class="m-0">Call : <a href="tel:91-8114479678">91-8114479678</a></p>
                            <p>Mail : <a href="mailto:info@libraro.in">info@libraro.in</a></p>
                        </div>
                        <img src="{{ asset('public/img/contact.png') }}" alt="support" class="img-fluid">
                    </div>
                </div>
            </div>
        </div>
    </section>

</div>

<!-- Client Scripts -->
<script>
(function() {
    function initDirectory() {
        var $ = window.jQuery;
        if (!$) return;

        var baseUrl = "{{ url('/') }}";
        var currentCategory = 'all';
        var searchTimer = null;
        var currentXhr = null;

        // Current active full list of libraries for category/search pagination
        var activeLibrariesList = [];
        var activeDisplayedCount = 10;

        // Curated authentic study space photography for realistic ambience
        var curatedStudyPhotos = [
            "{{ asset('public/img/modern-library-banner.jpg') }}",
            "{{ asset('public/img/library-image.jpg') }}",
            "{{ asset('public/img/libraryImg.jpg') }}",
            "{{ asset('public/img/direcotry/01.png') }}",
            "{{ asset('public/img/direcotry/02.png') }}",
            "{{ asset('public/img/direcotry/03.png') }}"
        ];

        // Shimmer skeleton HTML generator (10 cards for 4-5 in a row)
        function getShimmerHtml(count) {
            count = count || 10;
            var html = '<div class="row g-4 libraries-grid">';
            for (var i = 0; i < count; i++) {
                html += `
                    <div class="col-xxl-20 col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12">
                        <div class="shimmer-card">
                            <div class="dir-shimmer shimmer-img"></div>
                            <div class="dir-shimmer shimmer-title"></div>
                            <div class="dir-shimmer shimmer-line"></div>
                            <div class="dir-shimmer shimmer-line short"></div>
                            <div class="dir-shimmer shimmer-btn mt-3"></div>
                        </div>
                    </div>
                `;
            }
            html += '</div>';
            return html;
        }

        // HTML escape helper
        function escapeHtml(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // Humanoid & Student-Centric Library Card Builder
        function renderCard(library, isCarouselItem) {
            var title = escapeHtml(library.display_name || library.library_name || 'Library');
            var address = escapeHtml(library.library_address || 'India');
            var cityName = escapeHtml(library.city_name || '');
            var slug = library.slug ? (baseUrl + '/' + library.slug) : (baseUrl + '/search-libraries');
            var rating = library.rating ? parseFloat(library.rating).toFixed(1) : '4.8';
            var reviewCount = parseInt(library.review_count, 10) || 0;

            // Deterministic selection of authentic study photo fallback
            var photoIndex = Math.abs(parseInt(library.id, 10) || 0) % curatedStudyPhotos.length;
            var fallbackPhoto = curatedStudyPhotos[photoIndex];

            // Resolve best available photo: Uploaded gallery image > Logo > Authentic study photo
            var displayPhoto = fallbackPhoto;
            if (library.library_images) {
                try {
                    var parsedImgs = (typeof library.library_images === 'string') ? JSON.parse(library.library_images) : library.library_images;
                    if (Array.isArray(parsedImgs) && parsedImgs.length > 0 && parsedImgs[0]) {
                        displayPhoto = baseUrl + '/' + parsedImgs[0].replace(/^\/+/, '');
                    }
                } catch(e) {}
            }
            if (displayPhoto === fallbackPhoto && library.library_logo) {
                displayPhoto = baseUrl + '/' + library.library_logo.replace(/^\/+/, '');
            }

            // Top Badges
            var badgeTag = '';
            if (library.is_paid == 1) {
                badgeTag = '<span class="badge-category-tag tag-premium"><i class="fa-solid fa-crown me-1 text-warning"></i> Premium</span>';
            } else if (parseFloat(rating) >= 4.5) {
                badgeTag = '<span class="badge-category-tag tag-top-rated"><i class="fa-solid fa-star me-1"></i> Top Choice</span>';
            } else {
                badgeTag = '<span class="badge-category-tag tag-verified"><i class="fa-solid fa-shield-halved me-1"></i> Verified</span>';
            }

            var cityBadge = cityName ? `<span class="badge-city-tag"><i class="fa-solid fa-location-dot me-1 text-danger"></i> ${cityName}</span>` : '';
            var ratingBadgeOnImage = `<span class="badge-rating-tag"><i class="fa-solid fa-star"></i> ${rating}</span>`;

            // Atmospheric Student Vibe Badge (Compact)
            var vibePresets = [
                { icon: 'fa-solid fa-volume-xmark', text: 'Silent' },
                { icon: 'fa-solid fa-bolt', text: '24x7 Power' },
                { icon: 'fa-solid fa-bullseye', text: 'Exam Hub' },
                { icon: 'fa-solid fa-snowflake', text: 'AC Hall' }
            ];
            var vibe = vibePresets[Math.abs(parseInt(library.id, 10) || 0) % vibePresets.length];

            // Student Amenities Chips parsed from database features
            var featureIds = [];
            if (library.features) {
                try {
                    featureIds = (typeof library.features === 'string') ? JSON.parse(library.features) : library.features;
                    if (!Array.isArray(featureIds)) featureIds = [];
                } catch(e) {
                    featureIds = [];
                }
            }
            featureIds = featureIds.map(function(id) { return String(id); });

            var amenitiesList = [];
            if (featureIds.indexOf('2') !== -1 || featureIds.length === 0) {
                amenitiesList.push('<span class="amenity-chip"><i class="fa-solid fa-snowflake text-info"></i> AC</span>');
            }
            if (featureIds.indexOf('1') !== -1 || featureIds.length === 0) {
                amenitiesList.push('<span class="amenity-chip"><i class="fa-solid fa-wifi text-primary"></i> 5G WiFi</span>');
            }
            if (featureIds.indexOf('15') !== -1 || featureIds.length === 0) {
                amenitiesList.push('<span class="amenity-chip"><i class="fa-solid fa-bolt text-warning"></i> Power Backup</span>');
            }
            if (featureIds.indexOf('18') !== -1) {
                amenitiesList.push('<span class="amenity-chip"><i class="fa-solid fa-lock text-secondary"></i> Locker</span>');
            } else if (featureIds.indexOf('3') !== -1) {
                amenitiesList.push('<span class="amenity-chip"><i class="fa-solid fa-lightbulb text-warning"></i> Desk Lamp</span>');
            } else if (featureIds.indexOf('17') !== -1) {
                amenitiesList.push('<span class="amenity-chip"><i class="fa-solid fa-droplet text-primary"></i> RO Water</span>');
            } else {
                amenitiesList.push('<span class="amenity-chip"><i class="fa-solid fa-volume-xmark text-secondary"></i> Silent Zone</span>');
            }
            var amenitiesHtml = amenitiesList.slice(0, 4).join('');

            // Human Social Proof Strip (Compact)
            var trustPresets = [
                'Verified Study Space',
                '100+ Students Studying',
                'Quiet Exam Atmosphere',
                'High-Speed WiFi & Backup'
            ];
            var trustText = trustPresets[Math.abs(parseInt(library.id, 10) || 0) % trustPresets.length];

            // Student Quick Contact Buttons (Call & WhatsApp)
            var quickContactHtml = '';
            if (library.mobile) {
                var rawMobile = String(library.mobile).trim();
                var cleanDigits = rawMobile.replace(/\D/g, '');
                if (cleanDigits.length >= 10) {
                    var tenDigits = cleanDigits.slice(-10);
                    var waText = encodeURIComponent("Hello, I am a student interested in reserving a study seat at " + (library.display_name || library.library_name || 'Library') + ". Is a seat available?");
                    quickContactHtml = `
                        <a href="tel:${tenDigits}" class="btn-quick-call" title="Call: ${tenDigits}">
                            <i class="fa-solid fa-phone"></i>
                        </a>
                        <a href="https://api.whatsapp.com/send?phone=91${tenDigits}&text=${waText}" target="_blank" rel="noopener noreferrer" class="btn-quick-wa" title="WhatsApp Enquiry">
                            <i class="fa-brands fa-whatsapp"></i>
                        </a>
                    `;
                } else {
                    quickContactHtml = `
                        <a href="tel:${rawMobile}" class="btn-quick-call" title="Call: ${rawMobile}">
                            <i class="fa-solid fa-phone"></i>
                        </a>
                    `;
                }
            }

            var cardInner = `
                <div class="lib-card ${library.is_paid == 1 ? 'is-premium-card' : ''}">
                    <div class="lib-img-wrap">
                        ${badgeTag}
                        ${cityBadge}
                        <img src="${displayPhoto}" alt="${title}" loading="lazy" class="lib-real-photo" onerror="this.onerror=null; this.src='${fallbackPhoto}';">
                        <div class="lib-photo-scrim"></div>
                        <div class="badge-vibe-tag">
                            <i class="${vibe.icon}"></i> <span>${vibe.text}</span>
                        </div>
                        ${ratingBadgeOnImage}
                    </div>
                    <div class="lib-card-body">
                        <a href="${slug}" class="lib-title" title="${title}">${title}</a>
                        <div class="lib-address" data-bs-toggle="tooltip" data-bs-placement="top" title="${address}">
                            <i class="fa-solid fa-location-dot text-danger flex-shrink-0"></i>
                            <span class="address-text">${address}</span>
                        </div>
                        <div class="student-amenities-row">
                            ${amenitiesHtml}
                        </div>
                        <div class="student-trust-strip">
                            <i class="fa-solid fa-circle-check text-success flex-shrink-0"></i>
                            <span>${trustText}</span>
                        </div>
                        <div class="card-action-bar">
                            <a href="${slug}" class="btn-view-lib">
                                <span>Reserve Seat</span> <i class="fa-solid fa-arrow-right"></i>
                            </a>
                            ${quickContactHtml}
                        </div>
                    </div>
                </div>
            `;

            if (isCarouselItem) {
                return `<div class="item">${cardInner}</div>`;
            }

            return `<div class="col-xxl-20 col-xl-3 col-lg-4 col-md-6 col-sm-6 col-12">${cardInner}</div>`;
        }

        // Empty state
        function renderEmptyState(query, city, category) {
            var message = "We couldn't find any libraries matching your criteria.";
            if (query) {
                message = `No libraries found matching "<strong>${escapeHtml(query)}</strong>". Try checking the spelling or selecting another city.`;
            }
            return `
                <div class="dir-empty-state">
                    <div class="dir-empty-icon">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </div>
                    <h3 class="dir-empty-title">No Libraries Found</h3>
                    <p class="dir-empty-desc">${message}</p>
                    <button type="button" class="btn-reset-filters" id="btn-reset-all">
                        <i class="fa-solid fa-rotate-left me-1"></i> Reset All Filters
                    </button>
                </div>
            `;
        }

        // Render separate categorized lists with Premium Carousel
        function renderGroupedSections(data) {
            var html = '';

            // 1. Premium Libraries Section - Interactive Carousel
            if (data.premium && data.premium.length > 0) {
                html += `
                    <div class="cat-section-block premium-carousel-section">
                        <div class="cat-section-header">
                            <div class="cat-title-wrap">
                                <div><span class="premium-vip-pill"><i class="fa-solid fa-crown text-warning me-1"></i> VIP Showcase</span></div>
                                <h3>Premium Verified Libraries <span class="cat-count-badge">${data.counts.premium} Total</span></h3>
                                <p>Air-conditioned halls, 5G Wi-Fi, silent study zones & ergonomic seating</p>
                            </div>
                            <div class="carousel-nav-group">
                                <button type="button" class="btn-carousel-nav" id="btn-premium-prev" title="Previous" aria-label="Previous"><i class="fa-solid fa-chevron-left"></i></button>
                                <button type="button" class="btn-carousel-nav" id="btn-premium-next" title="Next" aria-label="Next"><i class="fa-solid fa-chevron-right"></i></button>
                                <button type="button" class="btn-view-all-cat ms-2" data-switch-cat="premium">
                                    View All (${data.counts.premium}) <i class="fa-solid fa-arrow-right ms-1"></i>
                                </button>
                            </div>
                        </div>
                        <div class="owl-carousel owl-theme premium-owl-slider" id="premium-owl-slider">
                `;
                $.each(data.premium, function(i, lib) {
                    html += renderCard(lib, true);
                });
                html += `
                        </div>
                    </div>
                `;
            }

            // 2. Top-Rated Libraries Section - 10 items grid + View All
            if (data.top_rated && data.top_rated.length > 0) {
                html += `
                    <div class="cat-section-block">
                        <div class="cat-section-header">
                            <div class="cat-title-wrap">
                                <h3><i class="fa-solid fa-star text-warning"></i> Top-Rated Study Spaces <span class="cat-count-badge">${data.counts.top_rated} Total</span></h3>
                                <p>Highest learner ratings, pristine cleanliness & verified management</p>
                            </div>
                            <button type="button" class="btn-view-all-cat" data-switch-cat="top_rated">
                                View All Top-Rated (${data.counts.top_rated}) <i class="fa-solid fa-arrow-right ms-1"></i>
                            </button>
                        </div>
                        <div class="row g-4 libraries-grid">
                `;
                $.each(data.top_rated.slice(0, 10), function(i, lib) {
                    html += renderCard(lib, false);
                });
                html += `</div>`;

                if (data.counts.top_rated > 10) {
                    html += `
                        <div class="text-center mt-3">
                            <button type="button" class="btn-cat-footer-viewall" data-switch-cat="top_rated">
                                View All ${data.counts.top_rated} Top-Rated Libraries <i class="fa-solid fa-arrow-right ms-1"></i>
                            </button>
                        </div>
                    `;
                }
                html += `</div>`;
            }

            // 3. More Study Libraries Section - 10 items grid + View All
            if (data.other && data.other.length > 0) {
                html += `
                    <div class="cat-section-block">
                        <div class="cat-section-header">
                            <div class="cat-title-wrap">
                                <h3><i class="fa-solid fa-building-columns text-primary"></i> More Study Libraries <span class="cat-count-badge">${data.counts.other} Total</span></h3>
                                <p>Affordable, accessible neighborhood reading rooms and self-study centers</p>
                            </div>
                            <button type="button" class="btn-view-all-cat" data-switch-cat="other">
                                View All Other (${data.counts.other}) <i class="fa-solid fa-arrow-right ms-1"></i>
                            </button>
                        </div>
                        <div class="row g-4 libraries-grid">
                `;
                $.each(data.other.slice(0, 10), function(i, lib) {
                    html += renderCard(lib, false);
                });
                html += `</div>`;

                if (data.counts.other > 10) {
                    html += `
                        <div class="text-center mt-3">
                            <button type="button" class="btn-cat-footer-viewall" data-switch-cat="other">
                                View All ${data.counts.other} Study Libraries <i class="fa-solid fa-arrow-right ms-1"></i>
                            </button>
                        </div>
                    `;
                }
                html += `</div>`;
            }

            return html;
        }

        // Render paginated category/search cards (10 initial, then load more button)
        function renderPaginatedBatch() {
            var itemsToRender = activeLibrariesList.slice(0, activeDisplayedCount);
            var cardsHtml = '<div class="row g-4 libraries-grid" id="libraries-grid-inner">';
            $.each(itemsToRender, function(index, lib) {
                cardsHtml += renderCard(lib);
            });
            cardsHtml += '</div>';

            var remaining = activeLibrariesList.length - activeDisplayedCount;
            if (remaining > 0) {
                cardsHtml += `
                    <div class="text-center mt-4 w-100" id="load-more-wrap">
                        <button type="button" class="btn-load-more-records" id="btn-load-more-action">
                            <i class="fa-solid fa-circle-plus me-1"></i> View More Libraries (${remaining} more)
                        </button>
                    </div>
                `;
            }

            $('#libraries-main-display').html(cardsHtml);
            initTooltips();
        }

        // Initialize Bootstrap tooltips on rendered cards
        function initTooltips() {
            setTimeout(function() {
                if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
                    tooltipTriggerList.forEach(function (tooltipTriggerEl) {
                        bootstrap.Tooltip.getOrCreateInstance(tooltipTriggerEl, {
                            container: 'body',
                            boundary: 'window'
                        });
                    });
                } else if (window.jQuery && $.fn && $.fn.tooltip) {
                    $('[data-bs-toggle="tooltip"]').tooltip({
                        container: 'body',
                        boundary: 'window'
                    });
                }
            }, 120);
        }

        // Initialize Owl Carousel for Premium Showcase
        function initPremiumCarousel() {
            var $slider = $('#premium-owl-slider');
            if (!$slider.length) return;

            if (window.jQuery && $.fn && $.fn.owlCarousel) {
                if ($slider.hasClass('owl-loaded')) {
                    $slider.trigger('destroy.owl.carousel');
                    $slider.removeClass('owl-loaded');
                }

                $slider.owlCarousel({
                    loop: ($slider.children().length > 4),
                    margin: 20,
                    nav: false,
                    dots: true,
                    autoplay: true,
                    autoplayTimeout: 4200,
                    autoplayHoverPause: true,
                    smartSpeed: 500,
                    responsive: {
                        0: { items: 1, margin: 12, stagePadding: 16 },
                        576: { items: 2, margin: 16 },
                        992: { items: 3, margin: 20 },
                        1400: { items: 4, margin: 20 }
                    }
                });

                $('#btn-premium-prev').off('click').on('click', function(e) {
                    e.preventDefault();
                    $slider.trigger('prev.owl.carousel');
                });

                $('#btn-premium-next').off('click').on('click', function(e) {
                    e.preventDefault();
                    $slider.trigger('next.owl.carousel');
                });
            } else {
                setTimeout(initPremiumCarousel, 200);
            }
        }

        // Main AJAX fetcher
        function fetchDirectoryLibraries() {
            var query = $('#library-search').val().trim();
            var city = $('#city-item').val();

            if (currentXhr && currentXhr.readyState !== 4) {
                currentXhr.abort();
            }

            var isGroupedMode = (currentCategory === 'all' && !query && !city);
            var reqCategory = isGroupedMode ? 'grouped' : currentCategory;

            $('#libraries-main-display').html(getShimmerHtml(10));
            $('#results-count-badge').html('<i class="fa-solid fa-circle-notch fa-spin me-1"></i> Searching...');

            currentXhr = $.ajax({
                url: '{{ route("get-libraries") }}',
                method: 'GET',
                data: {
                    query: query,
                    city: city,
                    category: reqCategory
                },
                dataType: 'json',
                success: function(data) {
                    if (isGroupedMode && data && data.status === 'grouped') {
                        var totalLibraries = data.counts.total || 0;
                        $('#results-count-badge').text(totalLibraries + ' Libraries Across India');
                        $('#libraries-main-display').html(renderGroupedSections(data));
                        initPremiumCarousel();
                        initTooltips();
                    } else if (Array.isArray(data)) {
                        activeLibrariesList = data;
                        activeDisplayedCount = 10;
                        var total = data.length;
                        var label = total === 1 ? ' Library Found' : ' Libraries Found';
                        $('#results-count-badge').text(total + label);

                        if (total > 0) {
                            renderPaginatedBatch();
                        } else {
                            $('#libraries-main-display').html(renderEmptyState(query, city, currentCategory));
                        }
                    } else {
                        $('#libraries-main-display').html(renderEmptyState(query, city, currentCategory));
                    }
                },
                error: function(xhr, status) {
                    if (status === 'abort') return;
                    $('#results-count-badge').text('Error loading libraries');
                    $('#libraries-main-display').html(`
                        <div class="col-12 text-center py-5">
                            <p class="text-danger fw-semibold">Unable to load libraries at this time.</p>
                            <button type="button" class="btn-reset-filters" id="btn-retry-fetch">
                                <i class="fa-solid fa-rotate-right me-1"></i> Retry
                            </button>
                        </div>
                    `);
                }
            });
        }

        // Click "Load More / View More Libraries" button in paginated view
        $(document).on('click', '#btn-load-more-action', function(e) {
            e.preventDefault();
            activeDisplayedCount += 10;
            renderPaginatedBatch();
        });

        // Category tab change
        $('.category-pill').on('click', function(e) {
            e.preventDefault();
            $('.category-pill').removeClass('active');
            $(this).addClass('active');
            currentCategory = $(this).data('category') || 'all';
            fetchDirectoryLibraries();
        });

        // "View All Category" button inside grouped sections
        $(document).on('click', '.btn-view-all-cat, .btn-cat-footer-viewall', function(e) {
            e.preventDefault();
            var targetCat = $(this).data('switch-cat');
            $('.category-pill').removeClass('active');
            $('.category-pill[data-category="' + targetCat + '"]').addClass('active');
            currentCategory = targetCat;
            $('html, body').animate({
                scrollTop: $("#directory-explore").offset().top - 80
            }, 300);
            fetchDirectoryLibraries();
        });

        // Search input with 280ms debounce
        $('#library-search').on('input', function() {
            var val = $(this).val();
            if (val.length > 0) {
                $('#clear-search-btn').show();
            } else {
                $('#clear-search-btn').hide();
            }

            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() {
                fetchDirectoryLibraries();
            }, 280);
        });

        // Clear search button
        $('#clear-search-btn').on('click', function() {
            $('#library-search').val('');
            $(this).hide();
            fetchDirectoryLibraries();
        });

        // City dropdown
        $('#city-item').on('change', function() {
            fetchDirectoryLibraries();
        });

        // Search button
        $('#btn-trigger-search').on('click', function() {
            fetchDirectoryLibraries();
        });

        // Reset all filters
        $(document).on('click', '#btn-reset-all', function() {
            $('#library-search').val('');
            $('#clear-search-btn').hide();
            $('#city-item').val('');
            $('.category-pill').removeClass('active');
            $('.category-pill[data-category="all"]').addClass('active');
            currentCategory = 'all';
            fetchDirectoryLibraries();
        });

        $(document).on('click', '#btn-retry-fetch', function() {
            fetchDirectoryLibraries();
        });

        // Initial fetch
        fetchDirectoryLibraries();
    }

    if (window.jQuery) {
        $(document).ready(initDirectory);
    } else {
        window.addEventListener('DOMContentLoaded', function() {
            if (window.jQuery) {
                $(document).ready(initDirectory);
            } else {
                window.addEventListener('load', initDirectory);
            }
        });
    }
})();

// Leaflet Map Initialization with custom brand pins & popups
document.addEventListener("DOMContentLoaded", function() {
    var mapEl = document.getElementById('map');
    if (!mapEl || typeof L === 'undefined') return;

    var map = L.map('map', {
        scrollWheelZoom: false // Prevents trapping page scroll
    }).setView([22.5, 79.5], 5);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
        maxZoom: 18
    }).addTo(map);

    // Custom branded pin for Leaflet
    var customPin = L.divIcon({
        className: 'custom-map-pin',
        html: '<div class="map-pin-inner"><i class="fa-solid fa-book-bookmark"></i></div>',
        iconSize: [32, 32],
        iconAnchor: [16, 32],
        popupAnchor: [0, -30]
    });

    var baseUrl = "{{ url('/') }}";

    fetch("{{ route('getLibrariesLocations') }}")
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (!data || data.length === 0) return;

            var bounds = L.latLngBounds();

            data.forEach(function(library) {
                var lat = parseFloat(library.latitude);
                var lng = parseFloat(library.longitude);

                if (!isNaN(lat) && !isNaN(lng) && lat >= 8 && lat <= 38 && lng >= 68 && lng <= 98) {
                    var linkUrl = library.slug ? (baseUrl + '/' + library.slug) : (baseUrl + '/search-libraries');
                    var popupHtml = `
                        <div class="map-popup-card">
                            <h5 class="map-popup-title">${library.library_name}</h5>
                            <p class="map-popup-address"><i class="fa-solid fa-location-dot text-danger me-1"></i>${library.library_address}</p>
                            <a href="${linkUrl}" class="map-popup-btn">View Library <i class="fa-solid fa-arrow-right ms-1"></i></a>
                        </div>
                    `;

                    var marker = L.marker([lat, lng], { icon: customPin })
                        .addTo(map)
                        .bindPopup(popupHtml);

                    bounds.extend([lat, lng]);
                }
            });

            if (bounds.isValid()) {
                map.fitBounds(bounds, { padding: [40, 40], maxZoom: 13 });
            }
        })
        .catch(function(error) {
            console.error("Error loading map locations:", error);
        });
});
</script>
@endsection