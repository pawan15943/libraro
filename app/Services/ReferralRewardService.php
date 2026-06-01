<?php

namespace App\Services;

use App\Models\Library;
use App\Models\LibraryReferral;
use App\Models\LibraryRewardPoint;
use App\Models\LibraryRewardRedeem;
use App\Models\ReferralWallet;
use Illuminate\Support\Facades\DB;

class ReferralRewardService
{
    public const MAX_REWARD_POINTS = 90;
    public const REDEEM_POINTS = 30;
    public const MAX_REDEEM_COUNT = 3;
    public const REFERRAL_CREDIT = 10;

    public function addLibraryRewardPoints(int $libraryId, int $points, string $source, ?int $referralId = null, ?string $remark = null): bool
    {
        $library = Library::where('id', $libraryId)->first();
        if (! $library) {
            return false;
        }

        $wallet = ReferralWallet::where('library_id', $libraryId)->lockForUpdate()->first();
        if (! $wallet) {
            $wallet = ReferralWallet::create([
                'library_id' => $libraryId,
                'total_earned_points' => 0,
                'redeemed_points' => 0,
                'available_points' => 0,
                'max_cap_points' => self::MAX_REWARD_POINTS,
            ]);
            $wallet = ReferralWallet::where('id', $wallet->id)->lockForUpdate()->first();
        }

        if ((int) $wallet->total_earned_points >= (int) $wallet->max_cap_points) {
            return false;
        }

        $allowedPoints = min($points, (int) $wallet->max_cap_points - (int) $wallet->total_earned_points);
        if ($allowedPoints <= 0) {
            return false;
        }

        LibraryRewardPoint::create([
            'library_id' => $libraryId,
            'referral_id' => $referralId,
            'points' => $allowedPoints,
            'type' => 'credit',
            'source' => $source,
            'remark' => $remark,
        ]);

        $wallet->available_points = (int) $wallet->available_points + $allowedPoints;
        $wallet->total_earned_points = (int) $wallet->total_earned_points + $allowedPoints;
        $wallet->save();

        return true;
    }

    public function processReferralReward(int $referralId): bool
    {
        return DB::transaction(function () use ($referralId) {
            $referral = LibraryReferral::where('id', $referralId)->lockForUpdate()->first();
            if (!$referral || $referral->status !== 'completed') {
                return false;
            }

            $alreadyRewarded = LibraryRewardPoint::where('referral_id', $referralId)
                ->where('source', 'referral_register')
                ->exists();

            if ($alreadyRewarded) {
                return false;
            }

            $this->addLibraryRewardPoints((int) $referral->referrer_library_id, self::REFERRAL_CREDIT, 'referral_register', $referralId, 'Referral reward as referrer');
            $this->addLibraryRewardPoints((int) $referral->referred_library_id, self::REFERRAL_CREDIT, 'referral_register', $referralId, 'Referral reward as referred');

            return true;
        });
    }

    public function redeemLibraryReward(int $libraryId): array
    {
        return DB::transaction(function () use ($libraryId) {
            $library = Library::where('id', $libraryId)->first();
            if (! $library) {
                return ['status' => false, 'message' => 'Library not found'];
            }

            $wallet = ReferralWallet::where('library_id', $libraryId)->lockForUpdate()->first();
            if (! $wallet) {
                return ['status' => false, 'message' => 'Wallet not found'];
            }

            if ((int) $wallet->available_points < self::REDEEM_POINTS) {
                return ['status' => false, 'message' => 'Minimum 30 reward points required'];
            }

            $redeemedCount = (int) floor(((int) $wallet->redeemed_points) / self::REDEEM_POINTS);
            if ($redeemedCount >= self::MAX_REDEEM_COUNT) {
                return ['status' => false, 'message' => 'Maximum redeem limit reached'];
            }

            $redeemNo = $redeemedCount + 1;

            LibraryRewardRedeem::create([
                'library_id' => $libraryId,
                'points' => self::REDEEM_POINTS,
                'redeem_no' => $redeemNo,
                'status' => 'approved',
                'remark' => 'Reward redeemed',
            ]);

            LibraryRewardPoint::create([
                'library_id' => $libraryId,
                'points' => self::REDEEM_POINTS,
                'type' => 'debit',
                'source' => 'redeem',
                'remark' => 'Reward redeem #' . $redeemNo,
            ]);

            $wallet->available_points = (int) $wallet->available_points - self::REDEEM_POINTS;
            $wallet->redeemed_points = (int) $wallet->redeemed_points + self::REDEEM_POINTS;
            $wallet->save();

            return [
                'status' => true,
                'message' => 'Reward redeemed successfully',
                'redeem_no' => $redeemNo,
            ];
        });
    }
}
