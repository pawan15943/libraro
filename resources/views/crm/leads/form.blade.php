@extends('layouts.admin')

@section('body_class', 'ax-custom-page crm-page')

@section('page_actions')
    <x-ui.button variant="gray" :href="$lead->exists ? route('crm.leads.show', $lead->id) : route('crm.leads.index')" icon="fa-solid fa-chevron-left">Back</x-ui.button>
@endsection

@section('content')
@include('crm.partials.nav')
@include('crm.partials.flash')

<form method="POST" action="{{ $lead->exists ? route('crm.leads.update', $lead->id) : route('crm.leads.store') }}" class="mx-auto max-w-3xl">
    @csrf
    @if($lead->exists) @method('PUT') @endif

    <x-ui.section label="Contact">
        <x-ui.card>
            <div class="grid gap-x-4 gap-y-4 *:mb-0 md:grid-cols-2">
                <x-ui.input name="library_name" id="f_library" label="Library name" :value="$lead->library_name" maxlength="255" placeholder="e.g. Gyan Deep Library" />
                <x-ui.input name="contact_name" id="f_contact" label="Contact person" :value="$lead->contact_name" maxlength="255" placeholder="Owner / manager name" />
                <x-ui.input name="mobile" id="f_mobile" label="Mobile" type="tel" prefix="+91" inputmode="numeric" :value="$lead->mobile" required maxlength="20" placeholder="10 digit mobile" />
                <x-ui.input name="alt_mobile" id="f_alt" label="Other number" type="tel" prefix="+91" inputmode="numeric" :value="$lead->alt_mobile" maxlength="20" />
                <x-ui.input name="email" id="f_email" label="E-mail" type="email" :value="$lead->email" maxlength="255" />
                <div class="grid grid-cols-2 gap-3 *:mb-0">
                    <x-ui.input name="city" id="f_city" label="City" :value="$lead->city" maxlength="100" />
                    <x-ui.input name="state" id="f_state" label="State" :value="$lead->state" maxlength="100" />
                </div>
                <div class="md:col-span-2">
                    <x-ui.textarea name="address" id="f_address" label="Address" rows="2" :value="$lead->address" maxlength="1000" class="!min-h-[72px]" />
                </div>
            </div>
        </x-ui.card>
    </x-ui.section>

    <x-ui.section label="Lead">
        <x-ui.card>
            <div class="grid gap-x-4 gap-y-4 *:mb-0 md:grid-cols-2">
                <div class="ui-field">
                    <div class="ui-label">Priority<span class="req">*</span></div>
                    <x-ui.segmented name="priority" :options="\App\Models\Crm\CrmLead::PRIORITIES" :value="$lead->priority ?: 'warm'" block />
                </div>
                <x-ui.select name="source" id="f_source" label="Source">
                    @foreach(\App\Models\Crm\CrmLead::SOURCES as $k => $v)
                        @continue(in_array($k, ['import', 'signup', 'old-leads'], true) && $lead->source !== $k)
                        <option value="{{ $k }}" @selected(old('source', $lead->source ?: 'manual') === $k)>{{ $v }}</option>
                    @endforeach
                </x-ui.select>

                @unless($lead->exists)
                    <x-ui.select name="stage_id" id="f_stage" label="Stage" placeholder="New" :value="old('stage_id')"
                        :options="$crm['stages']->where('is_closed', false)->pluck('name', 'id')" data-crm-form-stage />
                    <x-ui.select name="sub_stage_id" id="f_sub" label="Sub-stage" placeholder="—" data-crm-form-sub />
                    @if($crm['can']['assign'])
                        <x-ui.select name="assigned_to" id="f_agent" label="Assign to" placeholder="Open pool (anyone can call)" :options="$crm['agents']->pluck('name', 'id')" />
                    @endif
                    <x-ui.input name="follow_up_at" id="f_follow" type="datetime-local" label="First call at" />
                @endunless

                <div class="md:col-span-2">
                    <x-ui.textarea name="notes" id="f_notes" label="Notes" rows="3" :value="$lead->notes" maxlength="2000" placeholder="Anything the caller should know" />
                </div>
            </div>
        </x-ui.card>
    </x-ui.section>

    <div class="crm-form-foot flex justify-end gap-2 pb-5">
        <x-ui.button variant="gray" :href="$lead->exists ? route('crm.leads.show', $lead->id) : route('crm.leads.index')">Cancel</x-ui.button>
        <x-ui.button type="submit" icon="fa-solid fa-check">{{ $lead->exists ? 'Save changes' : 'Add lead' }}</x-ui.button>
    </div>
</form>
@endsection

@push('scripts')
<script>
    (function () {
        var stages = @json($crm['stages']->mapWithKeys(fn ($s) => [$s->id => $s->children->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values()]));
        var st = document.querySelector('[data-crm-form-stage]'), sub = document.querySelector('[data-crm-form-sub]');
        if (!st || !sub) return;
        var keep = @json(old('sub_stage_id'));
        function fill() {
            var list = stages[st.value] || [];
            sub.innerHTML = '<option value="">—</option>' + list.map(function (s) { return '<option value="' + s.id + '"' + (String(s.id) === String(keep) ? ' selected' : '') + '>' + s.name.replace(/</g, '&lt;') + '</option>'; }).join('');
            sub.disabled = !list.length;
        }
        st.addEventListener('change', function () { keep = null; fill(); });
        fill();
    })();
</script>
@endpush
