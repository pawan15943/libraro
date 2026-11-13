{{-- give the lead to an agent (lead page) --}}
<form method="POST" action="{{ route('crm.leads.assign') }}" class="flex items-center gap-2">
    @csrf
    <input type="hidden" name="ids[]" value="{{ $lead->id }}">
    <select name="agent_id" class="ui-select" aria-label="Agent">
        <option value="">Open pool</option>
        @foreach($crm['agents'] as $a)<option value="{{ $a->id }}" @selected($lead->assigned_to === $a->id)>{{ $a->name }}</option>@endforeach
    </select>
    <x-ui.button type="submit" variant="tinted">Save</x-ui.button>
</form>
