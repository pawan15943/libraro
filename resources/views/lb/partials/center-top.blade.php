<div class="lb-auth-top">
    @include('lb.partials.logo', ['height' => 34])
    <div class="lb-auth-top-right">
        @isset($backUrl)
            <a href="{{ $backUrl }}" class="lb-back"><x-lb-icon name="arrow-left" />{{ $backLabel ?? 'Back' }}</a>
        @endisset
        @include('lb.partials.theme-toggle')
    </div>
</div>
