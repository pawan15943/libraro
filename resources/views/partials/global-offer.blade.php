{{--
    Global offer on a booking form (docs/GLOBAL_DISCOUNT.md). Pass the form's selectors:
    @include('partials.global-offer', ['forms' => [['form' => '#x', 'price' => '#p', 'type' => '#t', 'value' => '#v', ...]]])
    Nothing is printed when the library has no running offer. The server applies the offer either way.
--}}
@php
    $lbOfferSvc = app(\App\Services\GlobalDiscountService::class);
    $lbOffer = $lbOfferSvc->toArray($lbOfferSvc->active(getLibraryId()));
@endphp
@if ($lbOffer)
    @once
        <link rel="stylesheet" href="{{ asset('public/css/lb-offer.css') }}?v={{ @filemtime(public_path('css/lb-offer.css')) }}">
        <script>window.LB_OFFER = @json($lbOffer + ['sprite' => asset('public/img/lb-icons.svg') . '?v=' . @filemtime(public_path('img/lb-icons.svg'))]);</script>
        <script src="{{ asset('public/js/lb-offer.js') }}?v={{ @filemtime(public_path('js/lb-offer.js')) }}" defer></script>
    @endonce
    <script>(window.LB_OFFER_FORMS = window.LB_OFFER_FORMS || []); @foreach ($forms ?? [] as $f) window.LB_OFFER_FORMS.push(@json($f)); @endforeach</script>
@endif
