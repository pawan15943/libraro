{{-- One lead in the queue / list (phones, and the queue on desktop) --}}
<article class="crm-lead prio-{{ $lead->priority }}" data-lead='@json($lead->sheetData())'>
    <div class="flex flex-wrap items-start justify-between gap-x-3 gap-y-1">
        <a href="{{ route('crm.leads.show', $lead->id) }}" class="crm-lead__title">
            <span class="crm-prio prio-{{ $lead->priority }}" title="{{ ucfirst($lead->priority) }} lead"></span><span>{{ $lead->display_name }}</span>
        </a>
        <span class="shrink-0 pt-0.5">@include('crm.partials.follow-chip')</span>
    </div>
    <div class="crm-lead__sub">
        @if($lead->library_name && $lead->contact_name)<span>{{ $lead->contact_name }}</span>@endif
        <a href="tel:+91{{ $lead->mobile }}" class="crm-mono">+91 {{ $lead->mobile }}</a>
        @if($lead->city)<span>{{ $lead->city }}</span>@endif
    </div>
    <div class="flex flex-wrap items-center gap-1.5">
        @include('crm.partials.stage-chip')
        @if($lead->source === 'signup')<x-ui.badge tone="orange" pill>Not paid</x-ui.badge>@endif
        @if($lead->agent && $crm['seesAll'])<x-ui.badge pill class="ms-auto" title="Agent"><i class="fa-regular fa-user"></i>{{ $lead->agent->name }}</x-ui.badge>@endif
    </div>
    <div class="crm-lead__last">
        @if($lead->last_called_at)
            <i class="fa-solid fa-phone-flip"></i>
            <span>{{ $lead->lastDisposition->name ?? 'Called' }} · {{ $lead->last_called_at->diffForHumans(null, true) }} ago</span>
            <span class="shrink-0 text-label-3">· {{ $lead->calls_count }} {{ \Illuminate\Support\Str::plural('call', $lead->calls_count) }}</span>
        @else
            <i class="fa-regular fa-circle-dot"></i><span>Not called yet · added {{ $lead->created_at->diffForHumans(null, true) }} ago</span>
        @endif
    </div>
    <div class="crm-lead__foot">@include('crm.partials.lead-actions')</div>
</article>
