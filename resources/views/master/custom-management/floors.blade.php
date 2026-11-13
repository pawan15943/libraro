@php
    $branchSeats = (int) ($branchHour->seats ?? 0);
@endphp
{{-- branch total seats on the right of the page title, like operating hours on the shifts page --}}
@if($branchHour)
@push('page_head_right')
    <div class="lp-headcard">
        <i><x-lb-icon name="armchair" /></i>
        <div><small>Total seats</small><b>{{ $branchSeats }} <em>seats</em></b></div>
        @can('has-permission', 'Add Library Seats')
            <a href="{{ route('seat.create', $branchHour->branch_id) }}" class="lp-headcard-edit" title="Change total seats" aria-label="Change total seats"><x-lb-icon name="pencil" /></a>
        @endcan
    </div>
@endpush
@endif
@include('master.custom-management._toolbar', [
    'heading' => 'Floors',
    'hint' => 'Seat numbers restart on every floor; all floors together can not exceed the branch seats.',
    'listable' => $branchId && $cmFloors->isNotEmpty(),
    'modal' => $branchId ? 'floor' : null,
    'canAdd' => auth()->user()->can('has-permission', 'Add Floor Master'),
    'addLabel' => 'Add Floor',
])

@if(!$branchId)
    @include('master.custom-management._no-branch', ['text' => 'Floors are branch-specific.'])
@else

    @if($cmFloors->isEmpty())
        @include('master.custom-management._empty', ['icon' => 'fa-layer-group', 'heading' => 'No floors added yet', 'text' => 'Add your first floor to map seats floor-wise.'])
    @else
        <div class="cm-items">
            @foreach($cmFloors as $floor)
                @include('master.custom-management._item', [
                    'icon' => 'fa-layer-group', 'tone' => 'teal',
                    'heading' => 'Floor ' . $floor->floor_no,
                    'subheading' => $floor->name ?: 'No name',
                    'meta' => ['Seats' => $floor->from_seat . ' – ' . $floor->to_seat, 'Total' => $floor->total_seats],
                    'inactive' => (bool) $floor->deleted_at, 'locked' => false,
                    'modal' => 'floor', 'table' => 'Floor', 'id' => $floor->id,
                    'record' => $floor->only(['id', 'floor_no', 'name', 'from_seat', 'to_seat', 'total_seats']),
                ])
            @endforeach
        </div>
        @include('master.custom-management._no-results')
    @endif
@endif
