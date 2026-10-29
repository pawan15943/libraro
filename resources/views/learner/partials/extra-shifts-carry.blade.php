{{--
    Operation forms (renew, upgrade, change plan, edit, reactivate): the extra shifts of a
    multi-shift booking. Kept shifts stay with the booking and are part of the plan price;
    a ticked one is removed (drop_slot_ids[]). See docs/ADVANCED_SHIFTS.md.
    The form always posts slot_prices_included=1: its plan price is the booking total.
--}}
@php
    $carryDetail = \App\Models\LearnerDetail::where('learner_id', $learnerId)->latest('id')->first();
    $carrySlots = $carryDetail ? $carryDetail->slots()->with('planType')->orderBy('id')->get() : collect();
@endphp
<input type="hidden" name="slot_prices_included" value="1">
@if($carrySlots->isNotEmpty())
    <div class="lp-carry" data-carry-shifts>
        <b>Extra shifts in this booking</b>
        @foreach($carrySlots as $slot)
            <label class="lp-carry-row">
                <input type="checkbox" name="drop_slot_ids[]" value="{{ $slot->id }}">
                <span>Remove · Seat {{ getSeatDisplayShortFloorName($slot->seat_no) ?? $slot->seat_no }} · {{ optional($slot->planType)->name }}</span>
            </label>
        @endforeach
        <small>Kept shifts continue with this booking and are included in the plan price.</small>
    </div>
@endif
