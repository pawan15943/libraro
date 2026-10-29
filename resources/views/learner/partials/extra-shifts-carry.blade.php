{{--
    Operation forms (renew, upgrade, change plan, edit, reactivate): the shifts of a
    multi-shift booking. The main shift is the form's Plan Type select; each extra shift can be
    removed (drop_slot_ids[]). Kept shifts stay with the booking and are part of the plan price.
    learner/script.blade.php moves this box under the plan price row, keeps the main shift
    line in step with the select and disables main shifts that overlap a kept extra shift on
    the same seat. The form always posts slot_prices_included=1: its plan price is the total.
    See docs/ADVANCED_SHIFTS.md.
--}}
@php
    $carryDetail = \App\Models\LearnerDetail::where('learner_id', $learnerId)->latest('id')->first();
    $carrySlots = $carryDetail ? $carryDetail->slots()->with('planType')->orderBy('id')->get() : collect();
    $carryTime = fn ($t) => $t ? \Carbon\Carbon::parse($t)->format(\Carbon\Carbon::parse($t)->format('i') === '00' ? 'g A' : 'g:i A') : '';
    $carrySeat = fn ($s) => $s ? (getSeatDisplayShortFloorName($s) ?: $s) : 'GEN';
    if ($carrySlots->isNotEmpty()) {
        $carryMainType = \App\Models\PlanType::withoutGlobalScopes()->find($carryDetail->plan_type_id);
        $carryTimes = \App\Models\PlanType::withoutGlobalScopes()->where('branch_id', $carryDetail->branch_id)
            ->get(['id', 'start_time', 'end_time'])
            ->mapWithKeys(fn ($p) => [$p->id => [$p->start_time, $p->end_time, $carryTime($p->start_time) . ' – ' . $carryTime($p->end_time)]]);
    }
@endphp
<input type="hidden" name="slot_prices_included" value="1">
@if($carrySlots->isNotEmpty())
    <div class="lp-carry" data-carry-shifts data-main-seat="{{ $carryDetail->seat_no }}" data-times='@json($carryTimes)'>
        <div class="lp-carry-h">
            <b>Shifts in this booking</b>
            <small>{{ $carrySlots->count() + 1 }} shifts · one plan and payment</small>
        </div>
        <div class="lp-carry-row is-main">
            <span class="lp-carry-no">1</span>
            <span class="lp-carry-t">
                <b data-carry-main-name>{{ optional($carryMainType)->name }}</b>
                <small>Seat {{ $carrySeat($carryDetail->seat_no) }} · <span data-carry-main-time>{{ $carryMainType ? $carryTime($carryMainType->start_time) . ' – ' . $carryTime($carryMainType->end_time) : '' }}</span> · main shift, chosen above</small>
            </span>
        </div>
        @foreach($carrySlots as $i => $slot)
            <label class="lp-carry-row" data-carry-slot data-pt="{{ $slot->plan_type_id }}" data-same-seat="{{ (string) $slot->seat_no === (string) $carryDetail->seat_no ? '1' : '0' }}">
                <span class="lp-carry-no">{{ $i + 2 }}</span>
                <span class="lp-carry-t">
                    <b>{{ optional($slot->planType)->name }}</b>
                    <small>Seat {{ $carrySeat($slot->seat_no) }} · {{ $slot->planType ? $carryTime($slot->planType->start_time) . ' – ' . $carryTime($slot->planType->end_time) : '' }} · ₹{{ rtrim(rtrim(number_format((float) $slot->price, 2, '.', ','), '0'), '.') }} now</small>
                </span>
                <span class="lp-carry-x"><input type="checkbox" name="drop_slot_ids[]" value="{{ $slot->id }}"><em>Remove</em></span>
            </label>
        @endforeach
        <p class="lp-carry-note">The plan price above covers every kept shift for the selected plan. A main shift that overlaps a kept shift on the same seat can't be chosen; remove that shift first.</p>
    </div>
@endif
