<?php

namespace App\Services;

use App\Models\LearnerDetail;
use App\Models\LearnerDetailSlot;
use Carbon\Carbon;

/**
 * Extra shifts of a booking (learner_detail_slots). See docs/ADVANCED_SHIFTS.md.
 */
class MultiShiftService
{
    /**
     * Seat and shift text for receipts and summaries, with the extra shifts appended
     * (e.g. seats "12, 30", shifts "First Half · Seat 12 Second Half · Seat 30 Night").
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
     * Adds the extra shifts to a booking. Call inside the booking's DB transaction, after the
     * booking row exists: each shift is checked with checkAvailability() against everything
     * already on its seat, including the main shift and the shifts added before it, so
     * clashes inside the same booking are caught too. Throws on the first clash.
     *
     * @param  array<int, array{seat_no: string, plan_type_id: int, price: float}>  $shifts
     */
    public function attach(LearnerDetail $detail, array $shifts, $planId, Carbon $startDate): void
    {
        foreach (array_values($shifts) as $i => $shift) {
            $result = checkAvailability(
                (int) $detail->branch_id,
                (int) $shift['seat_no'],
                null,
                (int) $shift['plan_type_id'],
                (string) $planId,
                $startDate->format('Y-m-d')
            );

            if ($result['error']) {
                throw new \Exception('Shift ' . ($i + 2) . ' (Seat ' . $shift['seat_no'] . '): ' . $result['message']);
            }

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
}
