<?php

namespace App\Services;

use App\Models\LearnerDetail;
use App\Models\LearnerDetailSlot;
use App\Models\PlanType;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Extra shifts of a booking (learner_detail_slots). See docs/ADVANCED_SHIFTS.md.
 */
class MultiShiftService
{
    /**
     * Seat and shift text for receipts and summaries, with the extra shifts appended
     * (e.g. seats "12, 30", shifts "Seat 12 First Half · Seat 12 Second Half · Seat 30 Night").
     * A booking without extra shifts keeps its plain values.
     *
     * @return array{seats: string, shifts: string, count: int}
     */
    public function describe(LearnerDetail $detail, string $mainSeat, string $mainShift): array
    {
        $slots = LearnerDetailSlot::where('learner_detail_id', $detail->id)->with('planType')->orderBy('id')->get();
        if ($slots->isEmpty()) {
            return ['seats' => $mainSeat, 'shifts' => $mainShift, 'count' => 1];
        }

        $seatName = fn ($seat) => function_exists('receiptSeatDisplay') ? receiptSeatDisplay($seat) : (string) $seat;
        $seats = collect([$mainSeat])->merge($slots->map(fn ($s) => $seatName($s->seat_no)))->filter()->unique()->implode(', ');
        $shifts = collect([($mainSeat !== '' ? 'Seat ' . $mainSeat . ' ' : '') . $mainShift])
            ->merge($slots->map(fn ($s) => 'Seat ' . $seatName($s->seat_no) . ' ' . (optional($s->planType)->name ?? '')))
            ->filter()
            ->implode(' · ');

        return ['seats' => $seats, 'shifts' => $shifts, 'count' => $slots->count() + 1];
    }

    /**
     * Active extra shifts of a booking, minus the ones the owner chose to drop.
     *
     * @param  int[]  $dropIds
     * @return Collection<int, LearnerDetailSlot>
     */
    public function slotsOf(LearnerDetail $detail, array $dropIds = []): Collection
    {
        return LearnerDetailSlot::where('learner_detail_id', $detail->id)
            ->when($dropIds, fn ($q) => $q->whereNotIn('id', $dropIds))
            ->orderBy('id')
            ->get();
    }

    /**
     * What the kept extra shifts cost on a plan, priced like the main shift (plan prices,
     * fixed billing cycle included).
     *
     * @param  int[]  $dropIds
     */
    public function carryTotal(LearnerDetail $detail, $planId, $startDate, $branchId, array $dropIds = []): float
    {
        return (float) $this->slotsOf($detail, $dropIds)
            ->sum(fn ($slot) => $this->priceOf((int) $slot->plan_type_id, $planId, $startDate, $branchId));
    }

    /**
     * Copies the kept extra shifts of $from onto the new booking $to (renew, upgrade,
     * reactivate), priced for the new plan and checked for the new period.
     *
     * @param  int[]  $dropIds
     */
    public function carry(LearnerDetail $from, LearnerDetail $to, $planId, Carbon $startDate, int $learnerId, array $dropIds = []): void
    {
        $shifts = $this->slotsOf($from, $dropIds)->map(fn ($slot) => [
            'seat_no' => (string) $slot->seat_no,
            'plan_type_id' => (int) $slot->plan_type_id,
            'price' => $this->priceOf((int) $slot->plan_type_id, $planId, $startDate, $to->branch_id),
        ])->all();

        $this->attach($to, $shifts, $planId, $startDate, $learnerId);
    }

    /**
     * Change plan / edit: the booking keeps its extra shifts. Drops the ones the owner
     * removed, re-prices the rest for the plan and re-checks them for the booking's dates.
     *
     * @param  int[]  $dropIds
     */
    public function reprice(LearnerDetail $detail, $planId, $startDate, $branchId, int $learnerId, array $dropIds = []): void
    {
        if ($dropIds) {
            LearnerDetailSlot::where('learner_detail_id', $detail->id)->whereIn('id', $dropIds)->delete();
        }

        $placed = $this->mainPlacement($detail);
        foreach ($this->slotsOf($detail)->values() as $i => $slot) {
            $result = checkSeatAvailability(
                (int) $slot->seat_no,
                $learnerId,
                (int) $slot->plan_type_id,
                Carbon::parse($detail->plan_start_date)->format('Y-m-d'),
                Carbon::parse($detail->plan_end_date)->format('Y-m-d'),
                (int) $detail->id
            );
            if ($result['error']) {
                throw new \Exception('Extra shift ' . ($i + 2) . ' (Seat ' . $slot->seat_no . '): ' . $result['message']);
            }
            $this->assertNoOwnClash($placed, (string) $slot->seat_no, (int) $slot->plan_type_id, $i + 2);

            $slot->update(['price' => $this->priceOf((int) $slot->plan_type_id, $planId, $startDate, $branchId)]);
        }
    }

    /**
     * Adds the extra shifts to a booking. Call inside the booking's DB transaction, after the
     * booking row exists. Each shift is checked with checkAvailability() against everyone
     * else on its seat ($excludeLearnerId leaves out the learner's own other bookings, as
     * renewals need) and against the booking's own shifts. Throws on the first clash.
     *
     * @param  array<int, array{seat_no: string, plan_type_id: int, price: float}>  $shifts
     */
    public function attach(LearnerDetail $detail, array $shifts, $planId, Carbon $startDate, ?int $excludeLearnerId = null): void
    {
        // extra shifts in one booking are a Pro plan feature
        if ($shifts && !libraryPlanAllows('Multiple Shifts')) {
            throw new \Exception('Booking more than one shift on a seat is part of the Pro plan. Upgrade your plan to use it.');
        }
        // the booking's own shifts so far: the main one and any it already holds (an operation adding more)
        $placed = $this->mainPlacement($detail);
        $held = $this->slotsOf($detail);
        foreach ($held as $slot) {
            $type = PlanType::withoutGlobalScopes()->find($slot->plan_type_id);
            $placed[] = ['seat' => (string) $slot->seat_no, 'start' => $type?->start_time, 'end' => $type?->end_time];
        }
        $offset = $held->count();

        foreach (array_values($shifts) as $i => $shift) {
            $i += $offset;
            $result = checkAvailability(
                (int) $detail->branch_id,
                (int) $shift['seat_no'],
                $excludeLearnerId,
                (int) $shift['plan_type_id'],
                (string) $planId,
                $startDate->format('Y-m-d')
            );

            if ($result['error']) {
                throw new \Exception('Shift ' . ($i + 2) . ' (Seat ' . $shift['seat_no'] . '): ' . $result['message']);
            }
            $this->assertNoOwnClash($placed, (string) $shift['seat_no'], (int) $shift['plan_type_id'], $i + 2);

            LearnerDetailSlot::create([
                'library_id' => $detail->library_id,
                'branch_id' => $detail->branch_id,
                'learner_id' => $detail->learner_id,
                'learner_detail_id' => $detail->id,
                'seat_no' => (string) $shift['seat_no'],
                'plan_type_id' => (int) $shift['plan_type_id'],
                'price' => $shift['price'],
            ]);
        }
    }

    private function priceOf(int $planTypeId, $planId, $startDate, $branchId): float
    {
        $result = app(PlanService::class)->calculatePrice(
            (int) $planId,
            $planTypeId,
            $startDate ? Carbon::parse($startDate)->format('Y-m-d') : null,
            $branchId ? (int) $branchId : null
        );

        return round((float) ($result['price'] ?? 0), 2);
    }

    /**
     * @return array<int, array{seat: string, start: ?string, end: ?string}>
     */
    private function mainPlacement(LearnerDetail $detail): array
    {
        if (empty($detail->seat_no)) {
            return [];
        }
        $type = PlanType::withoutGlobalScopes()->find($detail->plan_type_id);

        return [['seat' => (string) $detail->seat_no, 'start' => $type?->start_time, 'end' => $type?->end_time]];
    }

    // Two shifts of the same booking may not overlap on one seat
    private function assertNoOwnClash(array &$placed, string $seat, int $planTypeId, int $n): void
    {
        $type = PlanType::withoutGlobalScopes()->find($planTypeId);
        foreach ($placed as $p) {
            if ($p['seat'] === $seat && $type && $p['start'] && $p['end']
                && planTypeTimesOverlap($p['start'], $p['end'], $type->start_time, $type->end_time)) {
                throw new \Exception('Shift ' . $n . ' (Seat ' . $seat . '): overlaps another shift of this booking on the same seat.');
            }
        }
        $placed[] = ['seat' => $seat, 'start' => $type?->start_time, 'end' => $type?->end_time];
    }
}
