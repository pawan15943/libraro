@php
    $branchSeats = (int) ($branchHour->seats ?? 0);
    $allocatedSeats = (int) $cmFloors->whereNull('deleted_at')->sum('total_seats');
@endphp
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
    @php
        $activeFloors = $cmFloors->whereNull('deleted_at')->values();
        $remainingSeats = $branchSeats - $allocatedSeats;
        $usedPercent = $branchSeats > 0 ? min(100, round($allocatedSeats / $branchSeats * 100)) : 0;
    @endphp
    {{-- Seat capacity: one bar split per floor, so the allocation reads at a glance. --}}
    <section class="cm-capacity" aria-label="Seat capacity">
        <div class="cm-capacity-top">
            <div>
                <p class="cm-capacity-label">Seats allocated to floors</p>
                <p class="cm-capacity-value">{{ $allocatedSeats }}<span>/ {{ $branchSeats }}</span></p>
            </div>
            <span class="cm-capacity-badge {{ $remainingSeats > 0 ? 'is-free' : ($remainingSeats === 0 ? 'is-full' : 'is-over') }}">
                @if($remainingSeats > 0)
                    <i class="fa-solid fa-circle-plus"></i> {{ $remainingSeats }} seats left
                @elseif($remainingSeats === 0)
                    <i class="fa-solid fa-circle-check"></i> All seats allocated
                @else
                    <i class="fa-solid fa-triangle-exclamation"></i> {{ abs($remainingSeats) }} over capacity
                @endif
            </span>
        </div>

        <div class="cm-capacity-bar" role="progressbar" aria-valuemin="0" aria-valuemax="{{ $branchSeats }}" aria-valuenow="{{ $allocatedSeats }}" aria-label="{{ $usedPercent }}% of seats allocated">
            @foreach($activeFloors as $floor)
                {{-- Width is data-driven, so it is passed as a CSS variable. --}}
                <span class="cm-capacity-seg" style="--cm-seg: {{ $branchSeats > 0 ? round($floor->total_seats / max($branchSeats, $allocatedSeats) * 100, 2) : 0 }}%" title="Floor {{ $floor->floor_no }}: {{ $floor->total_seats }} seats"></span>
            @endforeach
        </div>

        @if($activeFloors->isNotEmpty())
            <ul class="cm-capacity-legend">
                @foreach($activeFloors as $floor)
                    <li><span class="cm-capacity-dot"></span>Floor {{ $floor->floor_no }} <strong>{{ $floor->total_seats }}</strong></li>
                @endforeach
                @if($remainingSeats > 0)
                    <li class="is-free"><span class="cm-capacity-dot"></span>Unallocated <strong>{{ $remainingSeats }}</strong></li>
                @endif
            </ul>
        @endif
    </section>

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
