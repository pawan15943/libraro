{{-- Libraro logo; colour version in light mode, white version in dark mode. --}}
<a href="{{ url('/') }}" class="lb-logo {{ $class ?? '' }}" aria-label="Libraro home">
    <img src="{{ asset('public/img/libraro.svg') }}" alt="Libraro" class="lb-logo-light" width="115" height="28" @isset($height) style="height:{{ $height }}px" @endisset>
    <img src="{{ asset('public/img/libraro-white.svg') }}" alt="Libraro" class="lb-logo-dark" width="115" height="28" @isset($height) style="height:{{ $height }}px" @endisset>
</a>
