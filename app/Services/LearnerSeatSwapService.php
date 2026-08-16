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
            $newSeatNo = $seatId;

            if (! empty($newSeatNo)) {
                $seatAvailability = app(SeatAvailabilityService::class);
                $statusCode = $seatAvailability->getSwapSeatStatusCode(
                    $newSeatNo,
                    $learnerId,
                    $customer->plan_type_id
                );

                if ($statusCode === 2) {
                    throw new Exception('Seat is already booked for future, currently not available to swap.');
                } elseif ($statusCode === 0) {
                    throw new Exception('The new seat is not available to swap.');
                }
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
