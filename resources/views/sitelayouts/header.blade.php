<header>
    <nav class="navbar navbar-expand-lg bg-body-tertiary">
        <div class="container">
            <a class="navbar-brand" href="{{url('/')}}"><img src="{{ asset('public/img/libraro.svg') }}" alt="logo" class="logo"></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav ms-auto mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" aria-current="page" href="{{url('/')}}">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{route('about-us')}}">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{url('/#pricing')}}">Pricing</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link border-dashed" href="{{route('find-my-library')}}">FIND MY LIBRARY</a>
                    </li>
                    
                    <li class="nav-item">
                        <a class="nav-link " href="{{route('blog')}}">Blog</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link " href="{{route('contact-us')}}">Contact Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link button" href="{{route('register')}}">Sign Up Your Library</a>
                    </li>
                </ul>

            </div>
        </div>
    </nav>
</header>