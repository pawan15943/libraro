{{--
  <x-ui.table>
      <x-slot:head><th>Name</th><th class="is-num">Calls</th></x-slot:head>
      <tr><td>…</td><td class="is-num">3</td></tr>
  </x-ui.table>
  Put it inside <x-ui.card flush>. td helpers: is-num, is-end, is-nowrap, is-actions; .ui-table__title / .ui-table__sub.
  stack → rows become small cards on phones (< 640px): td.ui-table__lead is the card title, other cells
  show their data-label on the left, td.is-actions sits bottom right (same as <x-ui.datatable stack>).
--}}
@props(['stack' => false])
<div {{ $attributes->merge(['class' => 'ui-table-wrap']) }}>
    <table class="ui-table{{ $stack ? ' ui-table--stack' : '' }}">
        @isset($head)<thead><tr>{{ $head }}</tr></thead>@endisset
        <tbody>{{ $slot }}</tbody>
    </table>
</div>
