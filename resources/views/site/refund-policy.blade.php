@extends('lb.layouts.site')
@php
    extract(require resource_path('views/lb/i18n/boot.php')); // website language: lb/i18n/boot.php
@endphp

{{-- Refund Policy — SEO stays DB-driven via lb.layouts.site. Policy text unchanged. --}}

@push('head')
    <link rel="stylesheet" href="{{ asset('public/css/lb-legal.css') }}?v={{ @filemtime(public_path('css/lb-legal.css')) }}">
@endpush

@section('content')
    @include('lb.partials.legal', [
        'active'  => 'refund',
        'heading' => $t('Refund Policy'),
        'lead'    => $t('When a refund can be considered, and how to ask for one.'),
        'glance'  => [
            ['circle-alert', $t('Refunds are exceptional'), $t('Considered only in critical cases.')],
            ['wrench', $t('Valid reasons'), $t('Major unresolved technical issues, or the software not working as described.')],
            ['mail', $t('How to ask'), $t('Email support@libraro.in. Our team reviews every request.')],
        ],
        'sections' => [
            [$t('Refund Policy'), $t('<ul><li>We have a strict no-refund policy for our library management software.</li><li>Refunds will only be considered in critical circumstances, such as:<ul><li>Major technical issues that cannot be resolved by our support team.</li><li>Failure of the software to function as described on our website.</li></ul></li><li>All refund requests must be reviewed and approved by our team.</li><li>To request a refund under exceptional circumstances, please contact us at <a href="mailto:support@libraro.in">support@libraro.in</a>.</li></ul>')],
        ],
    ])
@endsection
