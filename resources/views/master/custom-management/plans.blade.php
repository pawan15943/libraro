@include('master.custom-management._toolbar', [
    'heading' => 'Plans',
    'hint' => 'Durations you sell. Set the 1 Month price and longer durations are calculated automatically.',
    'listable' => $cmPlans->isNotEmpty(),
    'modal' => 'plan',
    'canAdd' => auth()->user()->can('has-permission', 'Add Plan Master'),
    'addLabel' => 'Add Plan',
])

@if($cmPlans->isEmpty())
    @include('master.custom-management._empty', ['icon' => 'fa-calendar-days', 'heading' => 'No plans added yet', 'text' => 'Start with a 1 Month plan, then add longer durations.'])
@else
    <div class="cm-items">
        @foreach($cmPlans as $plan)
            @php
                $billing = $plan->type === 'MONTH' ? ($plan->monthdays ? $plan->monthdays . ' Days' : 'Calendar wise') : '—';
            @endphp
            @include('master.custom-management._item', [
                'icon' => 'fa-calendar-days', 'tone' => 'navy',
                'heading' => $plan->name,
                'subheading' => $plan->type ? ucfirst(strtolower($plan->type)) . ' plan' : 'No duration type',
                'meta' => ['Billing' => $billing, 'Learners' => (int) $plan->active_learners_count],
                'inactive' => (bool) $plan->deleted_at, 'locked' => $plan->active_learners_count > 0,
                'modal' => 'plan', 'table' => 'Plan', 'id' => $plan->id,
                'record' => $plan->only(['id', 'type', 'plan_id', 'monthdays']),
            ])
        @endforeach
    </div>
    @include('master.custom-management._no-results')
@endif
