@include('master.custom-management._toolbar', [
    'heading' => 'Shifts',
    'hint' => 'Operating hours: ' . ($branchHour->hour ?? 0) . ' hrs. Shift timings must fit inside the library hours.',
    'listable' => $branchId && $cmPlanTypes->isNotEmpty(),
    'modal' => $branchId ? 'shift' : null,
    'canAdd' => auth()->user()->can('has-permission', 'Add Plan Type Master'),
    'addLabel' => 'Add Shift',
])

@if(!$branchId)
    @include('master.custom-management._no-branch', ['text' => 'Shifts are branch-specific.'])
@elseif($cmPlanTypes->isEmpty())
    @include('master.custom-management._empty', ['icon' => 'fa-clock', 'heading' => 'No shifts added yet', 'text' => 'Add shifts like Full Day, First Half or a custom slot.'])
@else
    <div class="cm-items">
        @foreach($cmPlanTypes as $shift)
            @php
                $start = $shift->start_time ? \Carbon\Carbon::parse($shift->start_time)->format('h:i A') : '—';
                $end = $shift->end_time ? \Carbon\Carbon::parse($shift->end_time)->format('h:i A') : '—';
            @endphp
            @include('master.custom-management._item', [
                'icon' => 'fa-clock', 'tone' => 'amber',
                'heading' => $shift->name,
                'subheading' => $start . ' – ' . $end,
                'meta' => ['Duration' => $shift->slot_hours . ' hrs', 'Learners' => (int) $shift->active_learners_count],
                'inactive' => (bool) $shift->deleted_at, 'locked' => $shift->active_learners_count > 0,
                'modal' => 'shift', 'table' => 'PlanType', 'id' => $shift->id,
                'record' => [
                    'id' => $shift->id,
                    'day_type_id' => $shift->day_type_id,
                    'custom_plan_type' => (int) $shift->day_type_id === 0 ? $shift->name : '',
                    'start_time' => $shift->start_time === '24:00' ? '00:00' : substr((string) $shift->start_time, 0, 5),
                    'end_time' => $shift->end_time === '24:00' ? '00:00' : substr((string) $shift->end_time, 0, 5),
                    'slot_hours' => $shift->slot_hours,
                ],
            ])
        @endforeach
    </div>
    @include('master.custom-management._no-results')
@endif
