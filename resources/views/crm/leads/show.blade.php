@extends('layouts.admin')

@section('body_class', 'ax-custom-page crm-page crm-lead-page')

@section('page_actions')
    @if($crm['can']['edit'])
        <x-ui.button variant="gray" :href="route('crm.leads.edit', $lead->id)" icon="fa-solid fa-pen"><span class="hidden sm:inline">Edit</span></x-ui.button>
    @endif
@endsection

@section('content')
@include('crm.partials.nav')
@include('crm.partials.flash')

@php
    $openStages = $crm['stages']->where('is_closed', false)->values();
    $currentIndex = $openStages->search(fn ($s) => $s->id === $lead->stage_id);
    $nextReminder = $reminders->firstWhere('status', 'pending');
    $prioTone = ['hot' => 'red', 'warm' => 'orange'][$lead->priority] ?? 'gray';
@endphp

<div data-lead-page data-lead='@json($lead->sheetData())'>
    {{-- header --}}
    <section class="ui-card crm-hero prio-{{ $lead->priority }} mb-4 flex flex-wrap items-start justify-between gap-4 p-4 sm:p-5">
        <div class="flex min-w-0 flex-[1_1_320px] gap-3.5">
            <x-ui.avatar :name="$lead->display_name" size="lg" soft />
            <div class="min-w-0">
                <h2 class="ui-title-2 break-words">{{ $lead->display_name }}</h2>
                <div class="crm-hero__sub mt-1.5">
                    @if($lead->library_name && $lead->contact_name)<span><i class="fa-regular fa-user"></i>{{ $lead->contact_name }}</span>@endif
                    <a href="tel:+91{{ $lead->mobile }}" class="crm-mono"><i class="fa-solid fa-phone"></i>+91 {{ $lead->mobile }}</a>
                    @if($lead->alt_mobile)<a href="tel:+91{{ $lead->alt_mobile }}" class="crm-mono"><i class="fa-solid fa-phone"></i>+91 {{ $lead->alt_mobile }}</a>@endif
                    @if($lead->city)<span><i class="fa-solid fa-location-dot"></i>{{ $lead->city }}@if($lead->state), {{ $lead->state }}@endif</span>@endif
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-1.5">
                    @include('crm.partials.stage-chip')
                    <x-ui.badge :tone="$prioTone" pill>{{ ucfirst($lead->priority) }}</x-ui.badge>
                    <x-ui.badge pill>{{ \App\Models\Crm\CrmLead::SOURCES[$lead->source] ?? $lead->source }}</x-ui.badge>
                    <span class="ms-1">@include('crm.partials.follow-chip')</span>
                </div>
            </div>
        </div>
        <div class="hidden flex-col items-end gap-2 md:flex">
            @include('crm.partials.lead-actions', ['size' => 'is-large'])
            @if($crm['can']['edit'])
                <x-ui.button variant="plain" size="sm" icon="fa-solid fa-flag" data-crm-open="stage">Update status</x-ui.button>
            @endif
        </div>
    </section>

    {{-- stage progress --}}
    <div class="crm-steps" aria-label="Lead progress">
        @foreach($openStages as $i => $s)
            <span class="crm-step {{ $currentIndex !== false && $i < $currentIndex ? 'done' : '' }} {{ $s->id === $lead->stage_id ? 'now' : '' }}" style="--c: {{ $s->color }}">{{ $s->name }}</span>
        @endforeach
        @if($lead->is_closed)
            <span class="crm-step now is-end" style="--c: {{ $lead->stage->color }}"><i class="fa-solid {{ $lead->stage->is_won ? 'fa-trophy' : 'fa-ban' }}"></i> {{ $lead->stage->name }}</span>
        @endif
    </div>

    {{-- phones: follow-up + numbers, timeline, details. Desktop: timeline left, the rest in a 360px column --}}
    <div class="grid gap-x-5 lg:grid-cols-[minmax(0,1fr)_360px] lg:grid-rows-[auto_auto_1fr]">
        <div class="min-w-0 lg:col-start-2 lg:row-start-1">
            {{-- next follow-up --}}
            <x-ui.card title="Next follow-up" icon="fa-regular fa-bell">
                @if($nextReminder)
                    <div class="crm-next {{ $nextReminder->remind_at->isPast() ? 'is-overdue' : '' }}">
                        <div class="crm-next__when">{{ $nextReminder->remind_at->format('D, j M · g:i A') }}</div>
                        <div class="ui-subhead mt-0.5">{{ $nextReminder->remind_at->diffForHumans() }}@if($nextReminder->agent) · {{ $nextReminder->agent->name }}@endif</div>
                        @if($nextReminder->note)<div class="mt-2 text-sm text-label-2">{{ $nextReminder->note }}</div>@endif
                    </div>
                    @if($crm['can']['remind'])
                        <div class="mt-4 flex gap-2">
                            <form method="POST" action="{{ route('crm.reminders.update', [$nextReminder->id, 'done']) }}">@csrf<x-ui.button type="submit" variant="tinted" size="sm" icon="fa-solid fa-check">Done</x-ui.button></form>
                            <x-ui.button variant="gray" size="sm" icon="fa-regular fa-clock" data-crm-open="reminder">Reschedule</x-ui.button>
                        </div>
                    @endif
                @else
                    <p class="ui-subhead mb-3">{{ $lead->is_closed ? 'This lead is closed.' : 'No follow-up set.' }}</p>
                    @if(!$lead->is_closed && $crm['can']['remind'])
                        <x-ui.button variant="tinted" size="sm" icon="fa-regular fa-bell" data-crm-open="reminder">Set follow-up</x-ui.button>
                    @endif
                @endif
            </x-ui.card>

            {{-- numbers --}}
            <div class="ui-card grid grid-cols-4 divide-x divide-sep py-3.5 text-center">
                @foreach([
                    [$lead->calls_count, 'Calls'],
                    [$lead->connected_count, 'Connected'],
                    [($lead->calls_count ? round($lead->connected_count / $lead->calls_count * 100) : 0).'%', 'Connect rate'],
                    [$lead->created_at->diffInDays().'d', 'Age'],
                ] as [$n, $l])
                    <div class="px-1"><div class="font-display text-xl font-bold tabular-nums tracking-tight">{{ $n }}</div><div class="text-[11.5px] text-label-3">{{ $l }}</div></div>
                @endforeach
            </div>
        </div>

        <div class="min-w-0 lg:col-start-1 lg:row-span-3 lg:row-start-1">
            {{-- note composer --}}
            <form method="POST" action="{{ route('crm.leads.note', $lead->id) }}" class="mb-5 flex items-end gap-2">
                @csrf
                <textarea name="note" rows="1" class="ui-textarea !min-h-[44px] bg-surface shadow-1" placeholder="Add a note…" required maxlength="2000" aria-label="Note"></textarea>
                <x-ui.button type="submit" icon="fa-solid fa-paper-plane" label="Save note" class="!h-11 !w-11 shrink-0" />
            </form>

            {{-- timeline --}}
            <x-ui.card title="Timeline" :subtitle="$calls->count().' '.\Illuminate\Support\Str::plural('call', $calls->count()).' · '.$lead->connected_count.' connected'">
                <ol class="crm-timeline">
                    @foreach($activities as $a)
                        <li class="t-{{ $a->type }}">
                            <span class="crm-tl-ico"><i class="{{ $a->icon }}"></i></span>
                            <div class="crm-tl-body">
                                <div class="crm-tl-title">{{ $a->title }}
                                    @if($a->type === 'call' && !empty($a->meta['duration']))<span class="font-normal text-label-3">· {{ gmdate($a->meta['duration'] >= 3600 ? 'G:i:s' : 'i:s', $a->meta['duration']) }}</span>@endif
                                    @if($a->type === 'stage' && !empty($a->meta['from']))<span class="font-normal text-label-3">· from {{ $a->meta['from'] }}</span>@endif
                                </div>
                                @if($a->body)<div class="crm-tl-text">{!! nl2br(e($a->body)) !!}</div>@endif
                                <div class="crm-tl-meta">{{ $a->user->name ?? 'System' }} · <time datetime="{{ $a->created_at->toIso8601String() }}" title="{{ $a->created_at->format('j M Y, g:i A') }}">{{ $a->created_at->diffForHumans() }}</time></div>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </x-ui.card>
        </div>

        <div class="min-w-0 lg:col-start-2 lg:row-start-2">
            <x-ui.card title="Details">
                @include('crm.leads.partials.details')
            </x-ui.card>

            @if($crm['can']['assign'])
                <x-ui.card title="Assign to">
                    @include('crm.leads.partials.assign')
                </x-ui.card>
            @endif

            @if($crm['can']['delete'])
                <form method="POST" action="{{ route('crm.leads.destroy', $lead->id) }}" class="mb-4 text-center" onsubmit="return confirm('Delete this lead? Its calls and timeline go with it.')">
                    @csrf @method('DELETE')
                    <x-ui.button type="submit" variant="plain" size="sm" icon="fa-regular fa-trash-can" class="!text-bad">Delete lead</x-ui.button>
                </form>
            @endif
        </div>
    </div>

    {{-- phone action dock --}}
    <div class="crm-dock md:hidden">
        @if($crm['can']['call'])<a href="tel:+91{{ $lead->mobile }}" class="crm-dock-btn is-call" data-crm-call><i class="fa-solid fa-phone"></i><span>Call</span></a>@endif
        @if($crm['can']['message'])<button type="button" class="crm-dock-btn is-wa" data-crm-open="message" data-channel="whatsapp"><i class="fa-brands fa-whatsapp"></i><span>WhatsApp</span></button>
        <button type="button" class="crm-dock-btn" data-crm-open="message" data-channel="sms"><i class="fa-regular fa-comment-dots"></i><span>SMS</span></button>@endif
        @if($crm['can']['call'])<button type="button" class="crm-dock-btn" data-crm-open="call"><i class="fa-solid fa-clipboard-check"></i><span>Log</span></button>@endif
        @if($crm['can']['edit'])<button type="button" class="crm-dock-btn" data-crm-open="stage"><i class="fa-solid fa-flag"></i><span>Status</span></button>@endif
    </div>
</div>

@include('crm.partials.sheets')
@endsection
