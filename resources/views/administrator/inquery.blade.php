@extends('layouts.admin')

@section('body_class', 'ax-custom-page')

@section('content')
<x-ui.flash />

<x-ui.card flush>
    <x-ui.datatable id="datatable" stack class="ui-table--compact">
        <x-slot:head>
            <th>#</th>
            <th>Name</th>
            <th>Mobile</th>
            <th>Email</th>
            <th>Message</th>
            <th>Received</th>
        </x-slot:head>
        @foreach($data as $key => $value)
            <tr>
                <td class="ui-table__hide-sm ui-footnote">{{ $key + 1 }}</td>
                <td class="ui-table__lead"><span class="ui-table__title">{{ $value->full_name }}</span></td>
                <td data-label="Mobile" class="is-nowrap">@if($value->mobile_number)<a href="tel:{{ $value->mobile_number }}">{{ $value->mobile_number }}</a>@endif</td>
                <td data-label="Email"><span class="block max-w-56 truncate" title="{{ $value->email }}">{{ $value->email }}</span></td>
                <td data-label="Message"><div class="max-w-md whitespace-pre-line text-label-2">{{ $value->message }}</div></td>
                <td data-label="Received" class="is-nowrap" data-order="{{ optional($value->created_at)->timestamp }}">{{ $value->created_at ? \Carbon\Carbon::parse($value->created_at)->format('j M Y, g:i A') : '—' }}</td>
            </tr>
        @endforeach
    </x-ui.datatable>
</x-ui.card>

<script>
    $(document).ready(function() {
        let table = new DataTable('#datatable', {

        });

    });
</script>
@endsection
