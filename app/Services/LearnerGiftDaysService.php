<?php

namespace App\Services;

use App\Models\Learner;
use App\Models\LearnerDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LearnerGiftDaysService
{
    public function __construct(private LearnerOperationLogService $operationLogService)
    {
    }

    public function getTotalGiftDays(int $learnerId): int
    {
        $total = DB::table('learner_gift_days')
            ->where('learner_id', $learnerId)
            ->orderByDesc('id')
            ->value('total_gift_days');

        return (int) ($total ?? 0);
    }

    /**
     * @return array{ok: bool, message: string, status_code?: int}
     */
    public function assign(int $learnerId, int $newGiftDays): array
    {
        $learner = Learner::query()->where('id', $learnerId)->first();

        if (! $learner) {
            return [
                'ok' => false,
                'message' => 'Learner not found',
                'status_code' => 200,
            ];
        }

        if ((int) $learner->frozen_status === 1) {
            return [
                'ok' => false,
                'message' => 'Cannot assign gift days while learner is frozen',
                'status_code' => 200,
            ];
        }

        // Gift days extend the *last* learner_detail entry, not just the currently
        // active (status = 1) one — a learner with an already-queued future renewal
        // should have its final plan_end_date pushed out, not the current plan that's
        // about to end anyway (which the renewal would otherwise silently override).
        $student = LearnerDetail::where('learner_id', $learnerId)
            ->orderByDesc('id')
            ->first();

        if (! $student) {
            return [
                'ok' => false,
                'message' => 'Learner detail not found',
                'status_code' => 200,
            ];
        }

        // an expired booking (plan + grace days over) isn't extended by gift days: the learner stays
        // expired and the days are lost; it has to be reactivated first (a future plan is fine)
        $hasStarted = ! $student->plan_start_date || Carbon::parse($student->plan_start_date)->lte(Carbon::today());
        if ($hasStarted && (int) ($learner->no_expiry ?? 0) !== 1 && $student->plan_end_date
            && getPlanStatusDetails($student->plan_end_date, getExtendDays($learner->branch_id))['diff_extend_day'] < 0) {
            return [
                'ok' => false,
                'message' => 'This plan has expired. Reactivate the learner first, then add gift days.',
                'status_code' => 422,
            ];
        }

        $newGiftDays = (int) $newGiftDays;
        if ($newGiftDays < 0) {
            return ['ok' => false, 'message' => 'Gift days cannot be less than 0.', 'status_code' => 422];
        }

        $gift = DB::table('learner_gift_days')
            ->where('learner_id', $learnerId)
            ->first();

        if (! $gift && $newGiftDays === 0) {
            return ['ok' => false, 'message' => 'Enter more than 0 gift days.', 'status_code' => 422];
        }

        if (! $gift) {
            DB::table('learner_gift_days')->insert([
                'learner_id' => $learnerId,
                'total_gift_days' => max($newGiftDays, 0),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            if ($newGiftDays > 0) {
                $student->plan_end_date = Carbon::parse($student->plan_end_date)->addDays($newGiftDays);
                $student->save();
            }

            $this->operationLogService->log(
                $learnerId,
                (int) $student->id,
                'giftDays',
                'total_gift_days',
                0,
                $newGiftDays,
                'Gift days added'
            );

            return [
                'ok' => true,
                'message' => $newGiftDays.' Gift Days added successfully',
            ];
        }

        $oldTotal = (int) $gift->total_gift_days;

        if ($oldTotal === $newGiftDays) {
            return [
                'ok' => false,
                'message' => 'No change: this learner already has '.$newGiftDays.' gift days.',
                'status_code' => 422,
            ];
        }

        // taking days back must not end the plan before it starts
        if ($newGiftDays < $oldTotal) {
            $newEnd = Carbon::parse($student->plan_end_date)->subDays($oldTotal - $newGiftDays);
            if ($student->plan_start_date && $newEnd->lt(Carbon::parse($student->plan_start_date))) {
                return [
                    'ok' => false,
                    'message' => 'That takes back too many days: the plan would end before it starts ('.Carbon::parse($student->plan_start_date)->format('d M Y').').',
                    'status_code' => 422,
                ];
            }
        }

        if ($oldTotal > $newGiftDays) {
            $incrementDecrementDay = $oldTotal - $newGiftDays;
            $increment = false;
        } else {
            $incrementDecrementDay = $newGiftDays - $oldTotal;
            $increment = true;
        }

        DB::table('learner_gift_days')
            ->where('learner_id', $learnerId)
            ->update([
                'total_gift_days' => $newGiftDays,
                'updated_at' => now(),
            ]);

        if ($increment) {
            $student->plan_end_date = Carbon::parse($student->plan_end_date)->addDays($incrementDecrementDay);
        } else {
            $student->plan_end_date = Carbon::parse($student->plan_end_date)->subDays(abs($incrementDecrementDay));
        }

        $student->save();

        $this->operationLogService->log(
            $learnerId,
            (int) $student->id,
            'giftDays',
            'total_gift_days',
            $oldTotal,
            $newGiftDays,
            'Gift days updated'
        );

        return [
            'ok' => true,
            'message' => 'Gift days updated successfully! Current Total: '.$newGiftDays,
        ];
    }
}
