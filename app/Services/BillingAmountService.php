<?php

namespace App\Services;

class BillingAmountService
{
    public static function discountAmount(float $planPrice, float $lockerAmount, ?string $discountType, float $discountValue): float
    {
        $grossAmount = max(0, $planPrice + $lockerAmount);
        $discountAmount = 0;

        if ($discountType === 'percentage') {
            $discountAmount = ($grossAmount * $discountValue) / 100;
        } elseif ($discountType === 'amount') {
            $discountAmount = $discountValue;
        }

        return max(0, min($discountAmount, $grossAmount));
    }

    public static function discountExceedsPayable(float $planPrice, float $lockerAmount, ?string $discountType, float $discountValue): bool
    {
        if (! in_array($discountType, ['amount', 'percentage'], true)) {
            return false;
        }

        $grossAmount = max(0, $planPrice + $lockerAmount);

        $discountAmount = $discountType === 'percentage'
            ? ($grossAmount * $discountValue) / 100
            : $discountValue;

        return $discountAmount > $grossAmount;
    }

    public static function totals(float $planPrice, float $lockerAmount, float $discountAmount, float $paidAmount): array
    {
        $totalAmount = max(0, ($planPrice + $lockerAmount) - $discountAmount);
        $appliedPaidAmount = min(max(0, $paidAmount), $totalAmount);

        return [
            'total_amount' => $totalAmount,
            'paid_amount' => $appliedPaidAmount,
            'pending_amount' => max(0, $totalAmount - $appliedPaidAmount),
            'extra_amount' => max(0, $paidAmount - $totalAmount),
        ];
    }

    public static function calculate(
        float $planPrice,
        float $lockerAmount,
        ?string $discountType,
        float $discountValue,
        float $paidAmount
    ): array {
        $discountAmount = self::discountAmount($planPrice, $lockerAmount, $discountType, $discountValue);

        return [
            'discount_amount' => $discountAmount,
            ...self::totals($planPrice, $lockerAmount, $discountAmount, $paidAmount),
        ];
    }
}
