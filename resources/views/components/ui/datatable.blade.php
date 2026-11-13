{{--
  Table that keeps its own id on the <table> (DataTables, jQuery hooks):
  <x-ui.datatable id="datatable" stack>
      <x-slot:head><th>Name</th><th class="is-end">Actions</th></x-slot:head>
      <tr><td class="ui-table__lead">…</td><td data-label="Plan">…</td><td class="is-actions">…</td></tr>
  </x-ui.datatable>
  stack → on phones every row becomes a small card: the td.ui-table__lead cell spans the row,
  other cells show their data-label on the left, td.is-actions sits at the bottom right.
  Put it inside <x-ui.card flush>. Extra attributes go on the <table>.
--}}
@props(['stack' => false])
<div class="ui-table-wrap">
    <table {{ $attributes->merge(['class' => 'ui-table'.($stack ? ' ui-table--stack' : '')]) }}>
        @isset($head)<thead><tr>{{ $head }}</tr></thead>@endisset
        <tbody>{{ $slot }}</tbody>
    </table>
</div>
