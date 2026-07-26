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

        $student = LearnerDetail::where('learner_id', $learnerId)
            ->where('status', 1)
            ->latest()
            ->first();

        if (! $student) {
            return [
                'ok' => false,
                'message' => 'Active learner detail not found',
                'status_code' => 200,
            ];
        }

        $newGiftDays = (int) $newGiftDays;

        $gift = DB::table('learner_gift_days')
            ->where('learner_id', $learnerId)
            ->first();

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
