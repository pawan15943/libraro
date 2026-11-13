<?php

namespace App\Services;

use App\Models\Plan;
use Carbon\Carbon;
use InvalidArgumentException;

/**
 * Custom plan: the owner picks the start and end date of a booking instead of a fixed plan.
 * The booking is stored against a hidden plan row (type CUSTOM, plan_id = days, is_custom = 1),
 * so getEndDate, pricing, seat checks, renew and receipts work on it as on any other plan.
 *
 * One row per library and day count, reused by every booking of that length.
 * See docs/CUSTOM_PLAN.md.
 */
class CustomPlanService
{
    public const MAX_DAYS = 366;

    /**
     * Number of days (inclusive) between the two dates, or an error message.
     *
     * @return array{error: bool, message?: string, days?: int}
     */
    public function validateDates($startDate, $endDate): array
    {
        if (empty($startDate) || empty($endDate)) {
            return ['error' => true, 'message' => 'Pick the start and end date of the custom plan.'];
        }

        try {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->startOfDay();
        } catch (\Throwable $e) {
            return ['error' => true, 'message' => 'Enter a valid custom plan end date.'];
        }

        if ($end->lt($start)) {
            return ['error' => true, 'message' => 'Custom plan end date cannot be before the start date.'];
        }

        $days = $start->diffInDays($end) + 1;

        if ($days > self::MAX_DAYS) {
            return ['error' => true, 'message' => 'A custom plan can be at most ' . self::MAX_DAYS . ' days long.'];
        }

        return ['error' => false, 'days' => $days];
    }

    /**
     * Finds or creates the hidden plan for these dates.
     *
     * @throws InvalidArgumentException when the dates are not valid
     */
    public function resolve(int $libraryId, $startDate, $endDate): Plan
    {
        $check = $this->validateDates($startDate, $endDate);
        if ($check['error']) {
            throw new InvalidArgumentException($check['message']);
        }

        return $this->forDays($libraryId, $check['days']);
    }

    public function forDays(int $libraryId, int $days): Plan
    {
        return Plan::withoutGlobalScopes()->firstOrCreate(
            [
                'library_id' => $libraryId,
                'is_custom' => 1,
                'type' => Plan::TYPE_CUSTOM,
                'plan_id' => $days,
                'deleted_at' => null,
            ],
            [
                'name' => 'Custom · ' . $days . ($days === 1 ? ' Day' : ' Days'),
                'monthdays' => null,
            ]
        );
    }
}
