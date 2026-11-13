{{--
  One row of an inset list (wrap rows in <ul class="ui-list"> or use them directly inside <x-ui.card flush>):
  <x-ui.row title="Gyan Deep Library" subtitle="Pro plan · 2 days ago" href="..." icon="fa-solid fa-building" chevron>
      <x-slot:leading><span class="ui-avatar">G</span></x-slot:leading>
      <x-slot:trailing><x-ui.badge tone="green">Active</x-ui.badge></x-slot:trailing>
  </x-ui.row>
--}}
@props(['title', 'subtitle' => null, 'href' => null, 'icon' => null, 'chevron' => false])
@php $hasLead = isset($leading) || $icon; @endphp
<{{ $href ? 'a' : 'div' }} @if($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => 'ui-row'.($hasLead ? ' ui-row--leading' : '')]) }}>
    @isset($leading){{ $leading }}@elseif($icon)<span class="ui-row__icon"><i class="{{ $icon }}"></i></span>@endisset
    <span class="ui-row__main">
        <span class="ui-row__title block">{{ $title }}</span>
        @if($subtitle)<span class="ui-row__sub block">{{ $subtitle }}</span>@endif
        {{ $slot }}
    </span>
    @if(isset($trailing) || $chevron)
        <span class="ui-row__trail">@isset($trailing){{ $trailing }}@endisset @if($chevron)<i class="fa-solid fa-chevron-right ui-row__chev" aria-hidden="true"></i>@endif</span>
    @endif
</{{ $href ? 'a' : 'div' }}>
