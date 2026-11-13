@if($lead->stage)
    <x-ui.badge :color="$lead->stage->color" dot pill class="max-w-full">{{ $lead->stage->name }}@if($lead->subStage)<span class="crm-stage-sub">&nbsp;›&nbsp;{{ $lead->subStage->name }}</span>@endif</x-ui.badge>
@endif
