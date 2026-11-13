{{--
  Dialog on desktop, bottom sheet on phones:
  <x-ui.sheet id="addThing" title="Add thing" subtitle="…">
      body…
      <x-slot:footer><x-ui.button variant="gray" data-bs-dismiss="modal">Cancel</x-ui.button><x-ui.button type="submit" form="f">Save</x-ui.button></x-slot:footer>
  </x-ui.sheet>
  Open with data-bs-toggle="modal" data-bs-target="#addThing".
--}}
@props(['id', 'title', 'subtitle' => null, 'size' => null, 'scrollable' => true])
<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-labelledby="{{ $id }}Title" aria-hidden="true" {{ $attributes }}>
    <div class="modal-dialog modal-dialog-centered {{ $scrollable ? 'modal-dialog-scrollable' : '' }} {{ $size ? 'modal-'.$size : '' }}">
        <div class="modal-content">
            <div class="modal-header">
                <div class="min-w-0">
                    <h5 class="modal-title" id="{{ $id }}Title">{{ $title }}</h5>
                    @if($subtitle)<div class="ui-subhead mt-1">{{ $subtitle }}</div>@endif
                </div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">{{ $slot }}</div>
            @isset($footer)<div class="modal-footer">{{ $footer }}</div>@endisset
        </div>
    </div>
</div>
