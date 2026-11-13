{{-- Inner page header. Vars: $heading, $lead (optional), $eyebrow (optional), $crumb (optional), $meta (optional) --}}
@php
    // NB: don't use $title here — LoadMenus' view composer injects its own $title into every view.
    $lbHeroTitle = $heading; $lbHeroCrumb = $crumb ?? $heading;
    $lbHeroEyebrow = $eyebrow ?? null; $lbHeroLead = $lead ?? null; $lbHeroMeta = $meta ?? null;
@endphp
<header class="lb-page-hero">
    <nav class="lb-crumbs-c" aria-label="{{ $t('Breadcrumb') }}">
        <a href="{{ route('/') }}">{{ $t('Home') }}</a><x-lb-icon name="chevron-right" />
        <span aria-current="page">{{ $lbHeroCrumb }}</span>
    </nav>
    @if($lbHeroEyebrow)<span class="lb-eyebrow">{{ $lbHeroEyebrow }}</span>@endif
    <h1 data-reveal>{{ $lbHeroTitle }}</h1>
    @if($lbHeroLead)<p class="lb-lead">{{ $lbHeroLead }}</p>@endif
    @if($lbHeroMeta)<p class="lb-meta">{{ $lbHeroMeta }}</p>@endif
</header>
