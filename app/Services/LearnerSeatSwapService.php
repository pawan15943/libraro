<?php

namespace App\Services;

use App\Http\Controllers\NotificationSentController;
use App\Models\Hour;
use App\Models\Learner;
use App\Models\LearnerDetail;
use App\Traits\LearnerQueryTrait;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * Shared seat swap persistence (web swapSeat + API).
 */
class LearnerSeatSwapService
{
    use LearnerQueryTrait;

    public function __construct(private LearnerOperationLogService $operationLogService)
    {
    }

    /**
     * @param  int|string  $learnerId
     * @param  int|string|null  $seatId  New seat number (same as web: seat_id)
     *
     * @throws Exception
     */
    public function swap($learnerId, $seatId): void
    {
        DB::transaction(function () use ($learnerId, $seatId) {
            $customer = $this->getLearnersByLibrary()
                ->where('learners.id', $learnerId)
                ->select('learners.id as id', 'learners.*', 'learner_detail.plan_type_id', 'learner_detail.seat_no')
                ->first();

            if (! $customer) {
                throw new Exception('Learner not found.');
            }

            $newSeatId = $seatId;

            $first_record = Hour::first();
            $total_hour = $first_record ? $first_record->hour : null;

            $newSeatNo = $seatId;

            $total_cust_hour = Learner::where('library_id', getLibraryId())
                ->where('seat_no', $newSeatNo)
                ->where('status', 1)
                ->sum('hours');
            $new_seat_remainig = $total_hour - $total_cust_hour;

            if ($seatId && ($customer->hours > $new_seat_remainig)) {
                throw new Exception('Not available according to your hours.');
            }

            if (
                $this->getLearnersByLibrary()->where('learner_detail.seat_no', $newSeatNo)
                    ->where('plan_type_id', $customer->plan_type_id)
                    ->where('learners.status', 1)
                    ->where('learner_detail.status', 1)
                    ->count() > 0 && $seatId
            ) {
                throw new Exception('The new seat is not available for your plan type.');
            }

            $data = Learner::findOrFail($learnerId);
            $data->seat_no = $newSeatNo;
            $data->save();

            LearnerDetail::where('learner_id', $learnerId)->update([
                'seat_no' => $newSeatId,
            ]);

            $detailId = LearnerDetail::where('learner_id', $learnerId)->latest('id')->value('id');
            $this->operationLogService->log(
                (int) $learnerId,
                $detailId ? (int) $detailId : null,
                'swapseat',
                'seat_no',
                $customer->seat_no,
                $newSeatNo,
                'Seat swapped'
            );

            try {
                $noti = new NotificationSentController;

                if (autowabaNotificationActive()) {
                    \Log::info('autowabaNotificationActive');
                    $noti->autoMessage($learnerId, 'waba', 'swapseat-waba');
                } else {
                    \Log::info('nowaba seond part swap');
                }
                if (autotextNotificationActive()) {
                    \Log::info('autotextNotificationActive');
                    $noti->autoMessage($learnerId, 'text', 'swapseat-text');
                } else {
                    \Log::info('no text seond part swap');
                }
            } catch (\Throwable $e) {
                \Log::error('Notification sending failed: '.$e->getMessage(), [
                    'exception' => $e,
                ]);
            }
        });
    }
}
