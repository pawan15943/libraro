@extends('sitelayouts.layout')
@section('content')

<style>
    .search-console {
        background: linear-gradient(176deg, #f4ffd6, #e5fcff);
        padding: 3.5rem;
    }

    .search-console h4 {
        text-align: center;
        font-weight: 600;
        margin-bottom: 1.5rem;
        color: navy;
    }

    .searchInput {
        position: relative;
    }

    .searchInput input {
        border-radius: 45px;
        height: 50px;
        padding-left: 25px;
        font-size: .9rem;
    }

    .searchInput .searchButton {
        position: absolute;
        top: 7px;
        right: 7px;
        border: none;
        width: 36px;
        height: 36px;
        border-radius: 36px;
        background: navy;
        color: #fff;
    }

    .filter ul {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        gap: 1rem;
    }

    .filter {
        display: flex;
        gap: 1.5rem;
        justify-content: center;
        font-family: 'Outfit', 'sans-sarif';
    }

    .filter ul li a {
        background: #fff;
        padding: .2rem .5rem;
        border-radius: 1rem;
        font-size: .8rem;
        box-shadow: 1px 0 5px #00000021;
        text-decoration: none;
        font-family: 'Outfit', 'sans-sarif';
    }

    .filter {
        font-size: .8rem;
        letter-spacing: 1px;
        align-items: center;
        font-weight: 600;
    }

    .libFeatures {
        background: #fff;
        padding: 1rem;
        text-align: center;
    }

    .featuresss {

        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
        width: 100%;
    }

    .featuresss .one {
        width: calc(100% / 5 - 1rem);
        text-align: center;
        background: #f1f1ff;
        padding: 1.5rem;
        border-radius: 1.2rem;
    }

    .featuresss .one h1 {
        font-size: 1.56rem;
        margin-bottom: .5rem;
        color: #ababab;
    }

    .featuresss .one p {
        font-family: 'Outfit', 'sans-sarif';
        font-size: .9em !Important;
    }

    .libraries {
        display: flex;
        gap: 1.5rem;
    }

    .libraries .library-box {
        width: calc(100% / 5 - 1rem);
    }

    .libraries .library-box .imgbox {
        position: relative;
        z-index: 1;
        display: block;
        overflow: hidden;
        border-radius: 1.5rem;
    }

    .libraries .library-box .imgbox:hover img{
        transform: scale(1.05);
    }

    .libraries .library-box .imgbox::after {
        content: '';
        width: 100%;
        height: 100%;
        position: absolute;
        left: 0;
        top: 0;
        background: linear-gradient(0deg, black 10%, transparent 70%);
        z-index: 2;
        border-radius: 1rem;
    }

    .libInfo h5 {
        font-size: 1rem !important;
        margin-top: .8rem;
        color: #0092a4;
        font-weight: 600;
        padding: 0 1rem;
    }

    .flex span {
        font-family: 'Outfit', 'sans-sarif';
        font-size: .8rem;
        font-weight: 500;
        color: #8e8e8e;
    }

    .flex {
        display: flex;
        justify-content: space-between;
        padding: 0 1rem;
    }
</style>

<section class="search-console">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <h4>Explore, Compare & Choose Your Study Library</h4>
                <div class="row justify-content-center">
                    <div class="col-lg-6">
                        <div class="searchInput">
                            <input type="text" class="form-control" placeholder="Enter What you want to search">
                            <button class="searchButton"><i class="fa fa-search"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row justify-content-center mt-4">
            <div class="col-lg-6">
                <div class="filter">
                    Popular Cities
                    <ul>
                        <li><a href="">KOTA</a></li>
                        <li><a href="">Jaipur</a></li>
                        <li><a href="">Delhi</a></li>
                        <li><a href="">Jodhpur</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <h6 class="text-center py-4">Quick Filter by Library Facilities</h6>
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="owl-carousel owl-theme px-5" id="LibFeatures">
                    <div class="item text-center">
                        <img src="https://via.placeholder.com/80" class="rounded-2">
                        <span>Luxary</span>
                    </div>
                    <div class="item text-center">
                        <img src="https://via.placeholder.com/80" class="rounded-2">
                        <span>Luxary</span>
                    </div>
                    <div class="item text-center">
                        <img src="https://via.placeholder.com/80" class="rounded-2">
                        <span>Luxary</span>
                    </div>
                    <div class="item text-center">
                        <img src="https://via.placeholder.com/80" class="rounded-2">
                        <span>Luxary</span>
                    </div>
                    <div class="item text-center">
                        <img src="https://via.placeholder.com/80" class="rounded-2">
                        <span>Luxary</span>
                    </div>
                    <div class="item text-center">
                        <img src="https://via.placeholder.com/80" class="rounded-2">
                        <span>Luxary</span>
                    </div>
                    <div class="item text-center">
                        <img src="https://via.placeholder.com/80" class="rounded-2">
                        <span>Luxary</span>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <img src="" alt="">
    <img src="" alt="">
</section>

<section class="why-libraro py-5">
    <div class="container">
        <h2 class="text-center mb-4">WHY LIBRARO</h2>
        <div class="row">
            <div class="col-lg-12">
                <div class="featuresss">
                    <div class="one">
                        <h1>01</h1>
                        <p class="m-0">Verified Libraries Only</p>

                    </div>
                    <div class="one">
                        <h1>02</h1>
                        <p class="m-0">Transparent Pricing</p>

                    </div>
                    <div class="one">
                        <h1>03</h1>
                        <p class="m-0">Seat Availability Updates</p>
                    </div>
                    <div class="one">
                        <h1>04</h1>
                        <p class="m-0">Easy Booking via QR/Online</p>
                    </div>
                    <div class="one">
                        <h1>05</h1>
                        <p class="m-0">Reviews from Real Students</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="featured-libraries py-5">
    <div class="container">
        <h4 class="mb-4">Featured / Top Rated Libraries :</h4>
        <div class="row">
            <div class="col-lg-12">
                <div class="libraries">
                    <div class="library-box">
                        <div class="imgbox">
                            <img src="{{ asset('public/img/library-image.jpg') }}" alt="library" class="img-fluid">
                        </div>
                        <div class="libInfo">
                            <h5>Abcd Library</h5>
                            <div class="flex">
                                <span>Address : Mkjdsahfsad </span>
                                <span><i class="fa fa-star"></i> 4.5</span>
                            </div>
                        </div>
                    </div>
                    <div class="library-box">
                        <div class="imgbox">
                            <img src="{{ asset('public/img/library-image.jpg') }}" alt="library" class="img-fluid">
                        </div>
                        <div class="libInfo">
                            <h5>Abcd Library</h5>
                            <div class="flex">
                                <span>Address : Mkjdsahfsad </span>
                                <span><i class="fa fa-star"></i> 4.5</span>
                            </div>
                        </div>
                    </div>
                    <div class="library-box">
                        <div class="imgbox">
                            <img src="{{ asset('public/img/library-image.jpg') }}" alt="library" class="img-fluid">
                        </div>
                        <div class="libInfo">
                            <h5>Abcd Library</h5>
                            <div class="flex">
                                <span>Address : Mkjdsahfsad </span>
                                <span><i class="fa fa-star"></i> 4.5</span>
                            </div>
                        </div>
                    </div>
                    <div class="library-box">
                        <div class="imgbox">
                            <img src="{{ asset('public/img/library-image.jpg') }}" alt="library" class="img-fluid">
                        </div>
                        <div class="libInfo">
                            <h5>Abcd Library</h5>
                            <div class="flex">
                                <span>Address : Mkjdsahfsad </span>
                                <span><i class="fa fa-star"></i> 4.5</span>
                            </div>
                        </div>
                    </div>
                    <div class="library-box">
                        <div class="imgbox">
                            <img src="{{ asset('public/img/library-image.jpg') }}" alt="library" class="img-fluid">
                        </div>
                        <div class="libInfo">
                            <h5>Abcd Library</h5>
                            <div class="flex">
                                <span>Address : Mkjdsahfsad </span>
                                <span><i class="fa fa-star"></i> 4.5</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


@endsection
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js" integrity="sha512-v2CJ7UaYy4JwqLDIrZUI/4hqeoQieOmAZNXBeQyjo21dadnwR+8ZaIJVT8EE2iyI61OV8e6M8PP2/4hpQINQ/g==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script>
    $(document).ready(function() {
        $('#LibFeatures').owlCarousel({
            loop: true,
            margin: 25,
            nav: true,
            dots: false,
            autoplay: true,
            autoplayTimeout: 2500,
            responsive: {
                0: {
                    items: 1
                },
                576: {
                    items: 2
                },
                768: {
                    items: 3
                },
                992: {
                    items: 5
                },
                1200: {
                    items: 5
                }
            }
        });
    });
</script>