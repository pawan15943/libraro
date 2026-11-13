@extends('layouts.admin')

@section('body_class', 'ax-custom-page')

@section('page_actions')
    <x-ui.button variant="gray" icon="fa-solid fa-book-open" :href="route('admin.limo.knowledge')">Knowledge base</x-ui.button>
@endsection

@include('admin.limo._style')

@section('content')
<p class="ui-subhead mb-4 max-w-3xl">What libraries asked that Limo could not answer, and answers they marked as not helpful. Add an answer and Limo learns it right away.</p>

<div class="ui-stats ui-stats--4 mb-5">
    <x-ui.stat label="Questions · 30 days" :value="number_format($stats['asked'])" icon="fa-regular fa-comments" />
    <x-ui.stat label="Answered" :value="$stats['answeredPct'] === null ? '—' : $stats['answeredPct'].'%'" icon="fa-solid fa-circle-check" tone="green" />
    <x-ui.stat label="Not understood · 30 days" :value="number_format($stats['unanswered'])" icon="fa-regular fa-circle-question" tone="orange" />
    <x-ui.stat label="Helpful / not helpful" :value="number_format($stats['liked']).' / '.number_format($stats['disliked'])" icon="fa-regular fa-thumbs-up" />
</div>

<x-ui.segmented class="mb-3" :links="[
    ['label' => 'Not understood', 'href' => route('admin.limo.questions'), 'active' => $tab === 'unanswered'],
    ['label' => 'Marked not helpful', 'href' => route('admin.limo.questions', ['tab' => 'disliked']), 'active' => $tab === 'disliked'],
]" />

<x-ui.card flush>
    @if ($rows->isEmpty())
        <x-ui.empty icon="fa-solid fa-circle-check" title="Nothing here yet" text="Limo is handling everything it has been asked." />
    @elseif ($tab === 'unanswered')
        <div class="ui-table-wrap">
            <table class="ui-table lm-stack">
                <thead><tr><th>Question</th><th class="is-num">Times asked</th><th class="is-num">Libraries</th><th>Last asked</th><th class="is-end"></th></tr></thead>
                <tbody>
                @foreach ($rows as $r)
                    <tr>
                        <td class="min-w-[240px]"><span class="ui-table__title">{{ $r->q }}</span></td>
                        <td class="is-num" data-label="Asked">{{ $r->times }}</td>
                        <td class="is-num" data-label="Libraries">{{ $r->libraries }}</td>
                        <td class="is-nowrap text-label-3" data-label="Last">{{ \Carbon\Carbon::parse($r->last_asked)->format('j M Y, g:i A') }}</td>
                        <td class="is-end is-nowrap lm-actions"><x-ui.button size="sm" variant="tinted" icon="fa-solid fa-plus" :href="route('admin.limo.knowledge.create', ['keys' => $r->q])">Add answer</x-ui.button></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="ui-table-wrap">
            <table class="ui-table lm-stack">
                <thead><tr><th>Question</th><th>Limo answered with</th><th>Library</th><th>When</th><th class="is-end"></th></tr></thead>
                <tbody>
                @foreach ($rows as $r)
                    <tr>
                        <td class="min-w-[200px]"><span class="ui-table__title">{{ $r->question ?: '—' }}</span></td>
                        <td data-label="Answer">{{ $r->title }} <x-ui.badge class="ml-1">{{ $r->intent ?: '—' }}</x-ui.badge></td>
                        <td data-label="Library">{{ $r->library_name ?: '—' }}</td>
                        <td class="is-nowrap text-label-3" data-label="When">{{ \Carbon\Carbon::parse($r->updated_at)->format('j M Y, g:i A') }}</td>
                        <td class="is-end is-nowrap lm-actions"><x-ui.button size="sm" variant="gray" icon="fa-solid fa-plus" :href="route('admin.limo.knowledge.create', ['keys' => mb_strtolower((string) $r->question)])">Add better answer</x-ui.button></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
    @if ($rows->hasPages())<div class="border-t border-sep px-4 py-3">{{ $rows->links('pagination::ui') }}</div>@endif
</x-ui.card>
@endsection
