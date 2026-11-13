<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\GlobalDiscount;
use Carbon\Carbon;

/**
 * The one place that decides the global offer of a booking (docs/GLOBAL_DISCOUNT.md).
 *
 * - An offer is running when it is switched on and today is between its start and end date.
 *   Only one offer of a library can run on a given day (the master refuses overlapping dates).
 * - It is worked out on the plan fee (not the locker) and applies to new booking, renew, upgrade,
 *   change plan and reactivate. Editing an existing booking never adds or drops it.
 * - A booking gets the bigger of the offer and any manual discount, never both, so the
 *   learner always gets the better deal and nothing is counted twice.
 *
 * Every booking path calls apply() and passes the type/value it returns to BillingAmountService,
 * so the totals, pending and receipts stay one calculation.
 */
class GlobalDiscountService
{
    public const SKIP_OPERATIONS = ['EDIT', 'EDITLEARNER'];

    private array $cache = [];

    public function libraryForBranch(?int $branchId): ?int
    {
        if (! $branchId) {
            return null;
        }

        return $this->cache['branch:'.$branchId] ??= Branch::withoutGlobalScopes()->where('id', $branchId)->value('library_id');
    }

    /** The offer running today (or on $on) for a library. */
    public function active(?int $libraryId, $on = null): ?GlobalDiscount
    {
        $libraryId = $libraryId ?: getLibraryId();
        if (! $libraryId) {
            return null;
        }
        $day = Carbon::parse($on ?? today())->toDateString();
        $key = 'offer:'.$libraryId.':'.$day;
        if (! array_key_exists($key, $this->cache)) {
            $this->cache[$key] = GlobalDiscount::withoutGlobalScopes()->whereNull('deleted_at')
                ->where('library_id', $libraryId)->runningOn($day)
                ->orderByDesc('start_date')->orderByDesc('id')->first();
        }

        return $this->cache[$key];
    }

    public function activeForBranch(?int $branchId, $on = null): ?GlobalDiscount
    {
        $libraryId = $this->libraryForBranch($branchId);

        return $libraryId ? $this->active($libraryId, $on) : null;
    }

    /** What the offer takes off a plan fee. */
    public function amount(?GlobalDiscount $offer, float $planPrice): float
    {
        if (! $offer || $planPrice <= 0) {
            return 0.0;
        }
        $off = $offer->discount_type === 'percentage'
            ? $planPrice * min(100, max(0, $offer->discount_value)) / 100
            : max(0, $offer->discount_value);

        return round(min($off, $planPrice), 2);
    }

    /** Plan fee less the running offer of the branch's library (public QR / app bookings). */
    public function payable(float $planPrice, ?int $branchId): float
    {
        return round(max(0, $planPrice - $this->amount($this->activeForBranch($branchId), $planPrice)), 2);
    }

    public function appliesTo(?string $operation): bool
    {
        return ! in_array(strtoupper((string) $operation), self::SKIP_OPERATIONS, true);
    }

    /**
     * The discount a booking really gets. Returns the type/value to pass on to BillingAmountService
     * plus what to store on the transaction.
     *
     * @return array{discount_type: ?string, discount_value: float, global_discount_id: ?int, global_discount_amount: float, offer: ?array}
     */
    public function apply(float $planPrice, float $lockerAmount, ?string $discountType, float $discountValue, ?int $branchId, ?string $operation = 'NEW', ?int $libraryId = null): array
    {
        $manual = [
            'discount_type' => $discountType ?: null,
            'discount_value' => $discountValue,
            'global_discount_id' => null,
            'global_discount_amount' => 0.0,
            'offer' => null,
        ];
        if (! $this->appliesTo($operation)) {
            return $manual;
        }

        $offer = $libraryId ? $this->active($libraryId) : $this->activeForBranch($branchId);
        $offerAmount = $this->amount($offer, $planPrice);
        if ($offerAmount <= 0) {
            return $manual;
        }

        $manualAmount = BillingAmountService::discountAmount($planPrice, $lockerAmount, $discountType, $discountValue);
        if ($manualAmount > $offerAmount + 0.009) {
            return $manual;
        }

        return [
            'discount_type' => 'amount',
            'discount_value' => $offerAmount,
            'global_discount_id' => $offer->id,
            'global_discount_amount' => $offerAmount,
            'offer' => $this->toArray($offer, $planPrice),
        ];
    }

    /** Offer details for JSON responses and the booking forms' script. */
    public function toArray(?GlobalDiscount $offer, ?float $planPrice = null): ?array
    {
        if (! $offer) {
            return null;
        }
        $data = [
            'id' => $offer->id,
            'name' => $offer->name,
            'discount_type' => $offer->discount_type,
            'discount_value' => (float) $offer->discount_value,
            'label' => $offer->label(),
            'start_date' => $offer->start_date->toDateString(),
            'end_date' => $offer->end_date->toDateString(),
        ];
        if ($planPrice !== null) {
            $data['amount'] = $this->amount($offer, $planPrice);
        }

        return $data;
    }

    /** Another switched-on offer of the library whose dates touch start..end. */
    public function overlapping(int $libraryId, $start, $end, ?int $ignoreId = null): ?GlobalDiscount
    {
        return GlobalDiscount::withoutGlobalScopes()->whereNull('deleted_at')
            ->where('library_id', $libraryId)->where('is_enabled', true)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->whereDate('start_date', '<=', Carbon::parse($end)->toDateString())
            ->whereDate('end_date', '>=', Carbon::parse($start)->toDateString())
            ->first();
    }
}
