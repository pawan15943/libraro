{{-- lead details list (lead page sidebar on desktop, below the timeline on phones) --}}
<dl class="crm-dl">
    @if($lead->email)<dt>E-mail</dt><dd><a href="mailto:{{ $lead->email }}">{{ $lead->email }}</a></dd>@endif
    @if($lead->address)<dt>Address</dt><dd>{{ $lead->address }}</dd>@endif
    <dt>Agent</dt><dd>{{ $lead->agent->name ?? 'Not assigned' }}</dd>
    <dt>Added</dt><dd>{{ $lead->created_at->format('j M Y, g:i A') }}</dd>
    @if($lead->last_called_at)<dt>Last call</dt><dd>{{ $lead->lastDisposition->name ?? '' }} · {{ $lead->last_called_at->diffForHumans() }}</dd>@endif
    @if($lead->closed_at)<dt>Closed</dt><dd>{{ $lead->closed_at->format('j M Y') }}</dd>@endif
    @if($lead->library)
        <dt>Library</dt>
        <dd>
            <x-ui.badge :tone="$lead->library->is_paid ? 'green' : 'orange'" pill>{{ $lead->library->is_paid ? 'Paid' : 'Not paid' }}</x-ui.badge>
            <span class="text-label-3"> · registered {{ optional($lead->library->created_at)->format('j M Y') }}</span>
            @if(\App\Support\AdminNav::canOpen('library.show'))<div class="mt-1"><a href="{{ route('library.show', $lead->library_id) }}">Open library <i class="fa-solid fa-arrow-up-right-from-square text-[11px]"></i></a></div>@endif
        </dd>
    @endif
    @if($lead->notes)<dt>Notes</dt><dd>{!! nl2br(e($lead->notes)) !!}</dd>@endif
</dl>
