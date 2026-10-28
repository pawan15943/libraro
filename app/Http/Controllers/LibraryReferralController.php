<?php

namespace App\Http\Controllers;

use App\Models\LibraryReferral;
use App\Models\LibraryTransaction;
use Illuminate\Http\Request;
use DB;
use Carbon\Carbon;

class LibraryReferralController extends Controller
{
     public function dashboard()
    {
        $libraryId = getLibraryId();
        
        $referralCode = getLibrary()->referral_code;
        $referralLink = url('/library/register?ref=' . $referralCode);
            // Counts
        $totalReferrals = DB::table('library_referrals')
            ->where('referrer_library_id', $libraryId)
            ->count();

        $completedReferrals = DB::table('library_referrals')
            ->where('referrer_library_id', $libraryId)
            ->where('status', 'completed')
            ->count();

        $maxReferrals = 10; // business rule
        $availableReferrals = max($maxReferrals - $completedReferrals, 0);

        // Lists
        // the referred library's name, when it has signed up, makes the lists readable
        $referralList = fn (string $status) => DB::table('library_referrals')
            ->leftJoin('libraries', 'libraries.id', '=', 'library_referrals.referred_library_id')
            ->where('library_referrals.referrer_library_id', $libraryId)
            ->where('library_referrals.status', $status)
            ->orderByDesc('library_referrals.updated_at')
            ->select('library_referrals.*', 'libraries.library_name as referred_library_name')
            ->get();

        $yourReferrals = $referralList('pending');
        $completedList = $referralList('completed');
        $earnReward=DB::table('library_referrals')->where('referrer_library_id', $libraryId)->where('status', 'completed')->where('redeem_status',0)->count()*10;
        $is_redeem=$earnReward >=30 ? true : false;


        return view('referral.library-referral-dashboard',compact('referralCode','referralLink','totalReferrals',
        'completedReferrals','maxReferrals','availableReferrals','yourReferrals','completedList','earnReward','is_redeem'));
    }

    public function redeem()
{
    $libraryId = auth()->id();

    DB::transaction(function () use ($libraryId) {
          // Get active library transaction (LOCKED)
        $transaction = LibraryTransaction::where('library_id', $libraryId)
            ->where('status', 1)
            ->lockForUpdate()
            ->firstOrFail();
       
        $referrals = DB::table('library_referrals')
            ->where('referrer_library_id', $libraryId)
            ->where('status', 'completed')
            ->where('redeem_status', 0)
            ->orderBy('created_at')
            ->limit(3)
            ->lockForUpdate()
            ->get();

        if ($referrals->count() < 3) {
            abort(403, 'Not enough points to redeem');
        }

        DB::table('library_referrals')
            ->whereIn('id', $referrals->pluck('id'))
            ->update([
                'redeem_status' => 1,
                'redeemed_at'   => now(),
            ]);

        // Current end date
        $currentEndDate = Carbon::parse($transaction->end_date);

        // If expired → start from today
        $newEndDate = $currentEndDate->isPast()
            ? Carbon::today()->addDays(30)
            : $currentEndDate->addDays(30);

        // Update end date
        $transaction->update([
            'end_date' => $newEndDate,
        ]);
        
    });

    return back()->with('success','Reward redeemed successfully!');
}

}
