{{--
  Read-only details as label / value rows (Settings style):
  <x-ui.kv :items="['Email' => $lib->email, 'Mobile' => '+91 '.$lib->mobile]" />
  or slot rows: <x-ui.kv><x-ui.kv-row label="Status"><x-ui.badge tone="green">Active</x-ui.badge></x-ui.kv-row></x-ui.kv>
  Put it inside <x-ui.card flush> (or a padded card: it bleeds to the card edge on its own only when flush).
--}}
@props(['items' => []])
<dl {{ $attributes->merge(['class' => 'ui-kv']) }}>
    @foreach($items as $k => $v)
        <div class="ui-kv__row"><dt>{{ $k }}</dt><dd>{{ filled($v) ? $v : '—' }}</dd></div>
    @endforeach
    {{ $slot }}
</dl>
