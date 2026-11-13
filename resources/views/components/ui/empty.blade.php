{{-- <x-ui.empty icon="fa-regular fa-folder-open" title="No leads" text="Add or import leads to start."><x-ui.button href="...">Add lead</x-ui.button></x-ui.empty> --}}
@props(['icon' => 'fa-regular fa-folder-open', 'title', 'text' => null])
<div {{ $attributes->merge(['class' => 'ui-empty']) }}>
    <span class="ui-empty__icon"><i class="{{ $icon }}"></i></span>
    <div class="ui-empty__title">{{ $title }}</div>
    @if($text)<div>{{ $text }}</div>@endif
    {{ $slot }}
</div>
