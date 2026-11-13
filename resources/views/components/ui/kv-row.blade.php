{{-- one row of <x-ui.kv>: <x-ui.kv-row label="Status">value or markup</x-ui.kv-row> --}}
@props(['label'])
<div {{ $attributes->merge(['class' => 'ui-kv__row']) }}><dt>{{ $label }}</dt><dd>{{ $slot->hasActualContent() ? $slot : '—' }}</dd></div>
