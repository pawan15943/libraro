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
        <div class="row">
            <div class="col-lg-6">
                <div class="owl-carosal" id="featureSlider">
                    <div class="item">
                        <div class="libFeatures">
                            <img src="" alt="">
                            <span>Feature 1</span>
                        </div>
                        <div class="libFeatures">
                            <img src="" alt="">
                            <span>Feature 1</span>
                        </div>
                        <div class="libFeatures">
                            <img src="" alt="">
                            <span>Feature 1</span>
                        </div>
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
        <h4 class="">WHY LIBRARO</h4>
        <div class="row">
            <div class="col-lg-12">
                <div class="d-flex">
                    <div class="one">
                        <h1>01</h1>
                        <p class="m-0">Verified Libraries Only</p>
                    </div>
                    <div class="one">
                        <h1>01</h1>
                        <p class="m-0">Verified Libraries Only</p>
                    </div>
                    <div class="one">
                        <h1>01</h1>
                        <p class="m-0">Verified Libraries Only</p>
                    </div>
                    <div class="one">
                        <h1>01</h1>
                        <p class="m-0">Verified Libraries Only</p>
                    </div>
                    <div class="one">
                        <h1>01</h1>
                        <p class="m-0">Verified Libraries Only</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="featured-libries">
    <div class="container">
        <div class="row">
            <div class="library-box">
                <img src="" alt="">
                <div class="libInfo">
                    <h5>Abcd Library</h5>
                    <div class="flex">
                        <span>Address : Mkjdsahfsad </span>
                        <span><i class="fa fa-star"></i> 4.5</span>
                    </div>
                </div>
            </div>
            <div class="library-box">
                <img src="" alt="">
                <div class="libInfo">
                    <h5>Abcd Library</h5>
                    <div class="flex">
                        <span>Address : Mkjdsahfsad </span>
                        <span><i class="fa fa-star"></i> 4.5</span>
                    </div>
                </div>
            </div>
            <div class="library-box">
                <img src="" alt="">
                <div class="libInfo">
                    <h5>Abcd Library</h5>
                    <div class="flex">
                        <span>Address : Mkjdsahfsad </span>
                        <span><i class="fa fa-star"></i> 4.5</span>
                    </div>
                </div>
            </div>
            <div class="library-box">
                <img src="" alt="">
                <div class="libInfo">
                    <h5>Abcd Library</h5>
                    <div class="flex">
                        <span>Address : Mkjdsahfsad </span>
                        <span><i class="fa fa-star"></i> 4.5</span>
                    </div>
                </div>
            </div>
            <div class="library-box">
                <img src="" alt="">
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
</section>


@endsection