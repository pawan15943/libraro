@extends('layouts.admin')

@section('body_class', 'ax-custom-page')

@section('page_actions')
    <x-ui.button variant="gray" icon="fa-regular fa-circle-question" :href="route('admin.limo.questions')">Questions</x-ui.button>
    <x-ui.button icon="fa-solid fa-plus" :href="route('admin.limo.knowledge.create')">Add answer</x-ui.button>
@endsection

@include('admin.limo._style')

@section('content')
<x-ui.flash />

<p class="ui-subhead mb-4 max-w-3xl">Tips and answers Limo gives to every library, in English and हिंदी. Changes are live immediately.</p>

<form method="get" class="ui-toolbar">
    <div class="ui-toolbar__grow">
        <div class="ui-input-wrap">
            <i class="ui-input-icon fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <input type="search" name="q" value="{{ $search }}" class="ui-input" placeholder="Search by title or key phrase…" aria-label="Search answers">
        </div>
    </div>
    <x-ui.button type="submit" variant="gray">Search</x-ui.button>
    @if($search !== '')<x-ui.button variant="plain" :href="route('admin.limo.knowledge')">Clear</x-ui.button>@endif
</form>

<x-ui.card flush>
    @if ($entries->isEmpty())
        <x-ui.empty icon="fa-solid fa-book-open" title="No answers found" :text="$search !== '' ? 'Try another word or phrase.' : 'Add the first answer Limo should give.'">
            <x-ui.button variant="tinted" icon="fa-solid fa-plus" class="mt-3" :href="route('admin.limo.knowledge.create')">Add answer</x-ui.button>
        </x-ui.empty>
    @else
        <div class="ui-table-wrap">
            <table class="ui-table lm-stack">
                <thead><tr><th>Answer</th><th>Asked with</th><th>Status</th><th>Updated</th><th class="is-end"></th></tr></thead>
                <tbody>
                @foreach ($entries as $e)
                    @php($keys = array_values(array_filter(preg_split('/\r?\n/', (string) $e->keys))))
                    <tr>
                        <td class="min-w-[200px]"><div class="ui-table__title">{{ $e->title_en }}</div><div class="ui-table__sub" lang="hi">{{ $e->title_hi }}</div></td>
                        <td class="text-[13px] text-label-3">{{ implode(' · ', array_slice($keys, 0, 4)) }}@if (count($keys) > 4) · +{{ count($keys) - 4 }}@endif</td>
                        <td><x-ui.badge :tone="$e->status ? 'green' : 'gray'" dot pill>{{ $e->status ? 'On' : 'Off' }}</x-ui.badge></td>
                        <td class="is-nowrap text-label-3" data-label="Updated">{{ \Carbon\Carbon::parse($e->updated_at)->format('j M Y') }}</td>
                        <td class="is-end is-nowrap lm-actions">
                            <div class="inline-flex gap-1.5" data-ax-done="1">
                                <x-ui.button size="sm" variant="gray" icon="fa-regular fa-pen-to-square" :href="route('admin.limo.knowledge.edit', $e->id)">Edit</x-ui.button>
                                <form method="post" action="{{ route('admin.limo.knowledge.toggle', $e->id) }}" class="m-0">@csrf
                                    <x-ui.button type="submit" size="sm" variant="gray">{{ $e->status ? 'Turn off' : 'Turn on' }}</x-ui.button>
                                </form>
                                <form method="post" action="{{ route('admin.limo.knowledge.destroy', $e->id) }}" class="m-0" onsubmit="return confirm('Delete this answer? Limo will stop using it. This cannot be undone.');">@csrf @method('DELETE')
                                    <x-ui.button type="submit" size="sm" variant="destructive" icon="fa-regular fa-trash-can" label="Delete {{ $e->title_en }}" />
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
    @if ($entries->hasPages())<div class="border-t border-sep px-4 py-3">{{ $entries->links('pagination::ui') }}</div>@endif
</x-ui.card>
@endsection
