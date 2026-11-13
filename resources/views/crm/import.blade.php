@extends('layouts.admin')

@section('body_class', 'ax-custom-page crm-page')

@section('page_actions')
    <form method="POST" action="{{ route('crm.leads.sync') }}">@csrf
        <x-ui.button type="submit" variant="gray" icon="fa-solid fa-rotate" title="Add libraries that registered but did not pay, and close leads whose library paid">Sync registrations</x-ui.button>
    </form>
@endsection

@section('content')
@include('crm.partials.nav')
@include('crm.partials.flash')

<div class="mx-auto max-w-3xl">
    <form method="POST" action="{{ route('crm.import.store') }}" enctype="multipart/form-data" data-crm-import>
        @csrf
        <x-ui.section label="1 · Add the leads">
            <x-ui.card>
                @php $importMethods = ['csv' => '<i class="fa-solid fa-file-csv"></i> CSV file', 'paste' => '<i class="fa-regular fa-paste"></i> Paste a list']; @endphp
                <x-ui.segmented name="method" :options="$importMethods" value="csv" block class="mb-4" />

                <div data-crm-method="csv">
                    <label class="crm-drop" for="crmFile">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <strong data-crm-file-name>Choose a .csv file</strong>
                        <span>Columns: library_name, contact_name, <b>mobile</b>, email, city, state, priority, notes.<br class="hidden sm:inline"> From Excel: File → Save as → CSV.</span>
                        <input type="file" id="crmFile" name="file" accept=".csv,text/csv">
                    </label>
                    <x-ui.button variant="plain" size="sm" :href="route('crm.import.template')" icon="fa-solid fa-download" class="!px-1">Download the sample file</x-ui.button>
                </div>

                <div data-crm-method="paste" hidden>
                    <textarea name="rows" rows="8" class="ui-textarea font-mono !text-[13.5px]" aria-label="Leads, one per line" placeholder="One lead per line, e.g.&#10;Gyan Deep Library, Rakesh Sharma, 9876543210, Jaipur&#10;Shiv Study Point, 9123456789, Kota">{{ old('rows') }}</textarea>
                    <div class="ui-hint">Separate with comma, tab or |. The 10-digit number is the mobile; the first text is the library, the next the contact name, then the city.</div>
                </div>
            </x-ui.card>
        </x-ui.section>

        <x-ui.section label="2 · Who calls them" note="Numbers already in the CRM are skipped, so uploading the same file twice is safe.">
            <x-ui.card>
                <x-ui.select name="assigned_to" placeholder="Open pool — any agent can pick them" :options="$crm['agents']->pluck('name', 'id')" aria-label="Assign to" />
            </x-ui.card>
        </x-ui.section>

        <div class="crm-form-foot mt-4 flex justify-end pb-2">
            <x-ui.button type="submit" icon="fa-solid fa-file-arrow-up">Import leads</x-ui.button>
        </div>
    </form>

    <x-ui.section label="Recent imports" class="mt-4">
        @if($imports->isEmpty())
            <x-ui.card><x-ui.empty icon="fa-solid fa-file-import" title="Nothing imported yet" class="!py-8" /></x-ui.card>
        @else
            <x-ui.card flush>
                <x-ui.table>
                    <x-slot:head><th>When</th><th>File</th><th class="is-num">Added</th><th class="is-num">Skipped</th><th class="is-num">Invalid</th><th>Given to</th><th>By</th></x-slot:head>
                    @foreach($imports as $i)
                        <tr>
                            <td class="is-nowrap tabular-nums">{{ $i->created_at->format('j M, g:i A') }}</td>
                            <td class="max-w-[200px] truncate">{{ $i->file_name ?: ($i->method === 'paste' ? 'Pasted list' : '—') }}</td>
                            <td class="is-num"><a href="{{ route('crm.leads.index', ['status' => 'all', 'source' => 'import']) }}" class="font-semibold">{{ $i->created }}</a></td>
                            <td class="is-num">{{ $i->duplicates }}</td>
                            <td class="is-num">{{ $i->invalid }}</td>
                            <td class="is-nowrap">{{ $i->agent->name ?? 'Open pool' }}</td>
                            <td class="is-nowrap text-label-3">{{ $i->user->name ?? '—' }}</td>
                        </tr>
                    @endforeach
                </x-ui.table>
            </x-ui.card>
        @endif
    </x-ui.section>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        var form = document.querySelector('[data-crm-import]');
        function show() {
            var m = form.querySelector('[name="method"]:checked').value;
            form.querySelectorAll('[data-crm-method]').forEach(function (b) { b.hidden = b.getAttribute('data-crm-method') !== m; });
        }
        form.addEventListener('change', function (e) {
            if (e.target.name === 'method') show();
            if (e.target.name === 'file') form.querySelector('[data-crm-file-name]').textContent = e.target.files[0] ? e.target.files[0].name : 'Choose a .csv file';
        });
        show();
    })();
</script>
@endpush
