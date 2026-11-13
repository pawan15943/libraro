{{--
  Dropdown menu:
  <x-ui.menu label="More" icon="fa-solid fa-ellipsis" align="end">
      <x-ui.menu-item href="..." icon="fa-solid fa-pen">Edit</x-ui.menu-item>
      <x-ui.menu-item danger icon="fa-solid fa-trash" onclick="...">Delete</x-ui.menu-item>
  </x-ui.menu>
--}}
@props(['label' => null, 'icon' => 'fa-solid fa-ellipsis', 'align' => 'end', 'variant' => 'gray', 'size' => 'sm'])
<div {{ $attributes->merge(['class' => 'dropdown inline-block']) }}>
    <x-ui.button :variant="$variant" :size="$size" :icon="$icon" data-bs-toggle="dropdown" aria-expanded="false" :label="$label"
        data-bs-popper-config='{"strategy":"fixed"}'>@if($label && $icon === null){{ $label }}@endif</x-ui.button>
    <div class="dropdown-menu dropdown-menu-{{ $align }}">{{ $slot }}</div>
</div>
