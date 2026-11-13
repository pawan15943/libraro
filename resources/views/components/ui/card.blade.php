{{--
  <x-ui.card title="Plans" subtitle="..." icon="fa-solid fa-layer-group">
      <x-slot:actions><x-ui.button size="sm" variant="tinted">Manage</x-ui.button></x-slot:actions>
      body…
      <x-slot:footer>…</x-slot:footer>
  </x-ui.card>
  flush → body without padding (lists / tables). flat → no shadow. line → hairline under the head.
--}}
@props(['title' => null, 'subtitle' => null, 'icon' => null, 'flush' => false, 'flat' => false, 'line' => false, 'hover' => false])
<section {{ $attributes->merge(['class' => 'ui-card'.($flat ? ' ui-card--flat' : '').($hover ? ' ui-card--hover' : '')]) }}>
    @if($title || isset($actions))
        <header class="ui-card__head {{ $line ? 'ui-card__head--line' : '' }}">
            <div class="min-w-0">
                @if($title)<h2 class="ui-card__title">@if($icon)<i class="{{ $icon }}" style="color: var(--tint)"></i>@endif{{ $title }}</h2>@endif
                @if($subtitle)<div class="ui-card__subtitle">{{ $subtitle }}</div>@endif
            </div>
            @isset($actions)<div class="ui-btn-group">{{ $actions }}</div>@endisset
        </header>
    @endif
    <div class="ui-card__body {{ $flush ? 'ui-card__body--flush' : '' }}">{{ $slot }}</div>
    @isset($footer)<footer class="ui-card__foot">{{ $footer }}</footer>@endisset
</section>
