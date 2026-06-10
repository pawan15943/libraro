<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LibraryTransaction;
use App\Models\ReferralWallet;
use App\Services\ReferralRewardService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class LibraryReferralController extends Controller
{
    public function dashboard()
    {
        $libraryId = authLibraryId();
        $library = getLibrary();

        $referralCode = (string) ($library->referral_code ?? '');
        $referralLink = url('/library/register?ref=' . $referralCode);
        $referralQrSvg = $referralLink
            ? (string) QrCode::size(650)->generate($referralLink)
            : '';

        $maxReferrals = ReferralRewardService::MAX_REDEEM_COUNT;
        $pointPerReferral = 10;
        $redeemThresholdPoints = 30;

        $referrals = DB::table('library_referrals')
            ->where('referrer_library_id', $libraryId)
            ->orderByDesc('created_at')
            ->get();

        $completedReferralsCount = $referrals->where('status', 'completed')->count();

        $wallet = ReferralWallet::where('library_id', $libraryId)->first();
        $pendingPoints = (int) ($wallet->available_points ?? 0);
        $totalEarned = (int) ($wallet->total_earned_points ?? 0);
        $redeemedPoints = (int) ($wallet->redeemed_points ?? 0);
        $redeemedCount = (int) floor($redeemedPoints / ReferralRewardService::REDEEM_POINTS);
        $maxRewardPoints = (int) ($wallet->max_cap_points ?? ReferralRewardService::MAX_REWARD_POINTS);
        $availableReferrals = max($maxReferrals - $redeemedCount, 0);

        $mapRow = function ($row) {
            $referredLibrary = DB::table('libraries')
                ->select('id', 'library_name', 'status')
                ->where('id', $row->referred_library_id)
                ->first();
            $referredLibraryBranch = DB::table('branches')
                ->select('library_logo')
                ->where('library_id', $row->referred_library_id)
                ->orderByDesc('id')
                ->first();

            return [
                'id' => $row->id,
                'referred_library_id' => $row->referred_library_id,
                'referred_library_name' => (string) ($referredLibrary->library_name ?? ''),
                'profile_picture' => !empty($referredLibraryBranch->library_logo)
                    ? asset('public/' . ltrim($referredLibraryBranch->library_logo, '/'))
                    : null,
                'referred_library_status' => ((int) ($referredLibrary->status ?? 0) === 1) ? 'active' : 'inactive',
                'status' => (string) ($row->status ?? 'pending'),
                'redeem_status' => (int) ($row->redeem_status ?? 0),
                'referred_on' => !empty($row->created_at)
                    ? Carbon::parse($row->created_at)->format('d/m/Y')
                    : null,
            ];
        };

        $yourReferrals = $referrals
            ->where('status', 'pending')
            ->values()
            ->map($mapRow)
            ->values();

        $completedList = $referrals
            ->where('status', 'completed')
            ->values()
            ->map($mapRow)
            ->values();

        return response()->json([
            'status' => true,
            'message' => 'Referral dashboard fetched successfully',
            'data' => [
                'referral_code' => $referralCode,
                'referral_link' => $referralLink,
                'referral_rules' => '<p><strong>Who Can Refer:</strong> Only registered users/library owners can participate in the referral program.</p><p><strong>Who You Can Refer:</strong> You may refer only new library owners who are not already registered on our platform.</p><p><strong>Referral Reward:</strong> Both the referrer and the referred library owner will receive 10 referral points each upon a successful referral. Points will be credited only after the referred library owner completes the registration and required payment process.</p><p><strong>Successful Referral:</strong> A referral is considered successful when the referred library owner registers using your referral link/code and completes the required onboarding or payment process.</p><p><strong>Reward Eligibility:</strong> Rewards are issued only after the referred user successfully completes all required steps.</p><p><strong>Maximum Referrals:</strong> Each user can refer up to 10 library owners unless additional referral slots are granted.</p><p><strong>Misuse Prohibited:</strong> Fake accounts, self-referrals, or fraudulent activity will lead to removal from the referral program.</p><p><strong>Reward Usage:</strong> Earned rewards can be redeemed only within the platform as per the available options.</p><p><strong>Program Changes:</strong> The platform may modify, pause, or terminate the referral program at any time.</p><p><strong>Non-Transferable:</strong> Referral rewards cannot be transferred, exchanged, or converted to cash unless stated otherwise.</p><p><strong>Compliance:</strong> All participants must comply with the platform terms and conditions.</p>',
                'point_per_referral' => $pointPerReferral,
                'my_reward_points' => (int) $pendingPoints,
                'can_redeem' => $pendingPoints >= $redeemThresholdPoints,
                'redeemed_count' => $redeemedCount,
                'max_redeem_count' => ReferralRewardService::MAX_REDEEM_COUNT,
                'total_reward_earned' => $totalEarned,
                'max_reward_points' => $maxRewardPoints,
                'max_referral' => (int) $maxReferrals,
                'referral_availed' => $redeemedCount,
                'available_referral' => (int) $availableReferrals,
                
                'refer_method' => [
                    'referral_code' => $referralCode,
                    'referral_link' => $referralLink,
                    'referral_qr_value' => $referralLink,
                    'referral_qr_svg' => $referralQrSvg,
                ],
                'your_referrals' => $yourReferrals,
                'completed' => $completedList,
                
            ],
        ]);
    }

    public function redeem(ReferralRewardService $rewardService)
    {
        $libraryId = authLibraryId();
        $result = $rewardService->redeemLibraryReward($libraryId);
        if (!$result['status']) {
            return response()->json($result, 422);
        }

        $transaction = LibraryTransaction::where('library_id', $libraryId)
            ->where('status', 1)
            ->first();

        if ($transaction) {
            $currentEndDate = Carbon::parse($transaction->end_date);
            $transaction->update([
                'end_date' => $currentEndDate->isPast() ? Carbon::today()->addDays(30) : $currentEndDate->addDays(30),
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => $result['message'],
            // 'redeem_no' => $result['redeem_no'] ?? null,
        ]);
    }
}
