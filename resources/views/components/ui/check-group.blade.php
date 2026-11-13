{{--
  A titled group of checkboxes (permission matrix, feature lists):
  <x-ui.check-group title="Learners" :count="12" icon="fa-solid fa-folder"
      :select-all="['id' => 'cat_cb_3', 'class' => 'category-select-all', 'data-cat-id' => 3]">
      <x-ui.check name="permissions[]" value="5" id="perm_5">Add learner</x-ui.check>
      …
  </x-ui.check-group>
  select-all → attributes of a header checkbox (your script wires it); leave it out for a plain title.
  scroll → max height of the list in px before it scrolls (0 = never).
--}}
@props(['title', 'count' => null, 'icon' => null, 'selectAll' => null, 'scroll' => 240])
<section {{ $attributes->merge(['class' => 'ui-checkgroup']) }}>
    <header class="ui-checkgroup__head">
        @if($selectAll)
            <label class="ui-check ui-checkgroup__title">
                <input type="checkbox" {{ new \Illuminate\View\ComponentAttributeBag($selectAll) }}>
                <span>@if($icon)<i class="{{ $icon }}" aria-hidden="true"></i>@endif{{ $title }}</span>
            </label>
        @else
            <span class="ui-checkgroup__title">@if($icon)<i class="{{ $icon }}" aria-hidden="true"></i>@endif{{ $title }}</span>
        @endif
        @if(!is_null($count))<x-ui.badge pill>{{ $count }}</x-ui.badge>@endif
    </header>
    <div class="ui-checkgroup__body" @if($scroll) style="max-height: {{ (int) $scroll }}px" @endif>{{ $slot }}</div>
</section>
