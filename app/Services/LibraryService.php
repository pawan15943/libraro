<?php
namespace App\Services;

use App\Models\Branch;
use Illuminate\Support\Facades\Auth;
use App\Models\Library;
use App\Models\LibraryTransaction;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LibraryService
{
    public function runDailyUpdate()
    {
        $today = Carbon::today('Asia/Kolkata');

        Library::select('id')
            ->chunkById(200, function ($libraries) use ($today) {

                foreach ($libraries as $library) {

                    try {
                        $this->processSingleLibrary($library->id, $today);
                    } catch (\Throwable $e) {

                        Log::error('Library status update failed', [
                            'library_id' => $library->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            });
    }

    private function processSingleLibrary($libraryId, $today)
    {
        $library = Library::find($libraryId);
        if (!$library) return;

        $extendDays = $library->extend_days ?? 0;

        DB::transaction(function () use ($library, $today, $extendDays) {

            // 1️⃣ Deactivate all transactions
            LibraryTransaction::withoutGlobalScopes()
                ->where('library_id', $library->id)
                ->update(['status' => 0]);

            // 2️⃣ Find valid transaction (extension aware)
            $validTransaction = LibraryTransaction::withoutGlobalScopes()
                ->where('library_id', $library->id)
                ->where('is_paid', 1)
                ->whereDate('start_date', '<=', $today)
                ->get()
                ->filter(function ($transaction) use ($today, $extendDays) {

                    $endWithExtend = Carbon::parse($transaction->end_date)
                        ->addDays($extendDays);

                    return $endWithExtend->gte($today);
                })
                ->sortByDesc('start_date')
                ->first();

            if ($validTransaction) {

                $validTransaction->update(['status' => 1]);

                $library->update([
                    'status' => 1,
                    'is_paid' => 1,
                    'library_type' => $validTransaction->subscription
                ]);

            } else {

                $library->update([
                    'status' => 0,
                    'is_paid' => 0
                ]);
            }
        });
    }
    public function checkLibraryStatus()
    {
        if (Auth::check()) {
           
            $library_id = Auth::user()->id;

            $isEmailVeri = Library::where('id', $library_id)->whereNotNull('email_verified_at')->exists();
            $checkSub = LibraryTransaction::where('library_id', $library_id)->where('status',1)->exists();
            $ispaid = Library::where('id', $library_id)->where('is_paid', 1)->exists();
            $isProfile = Library::where('id', $library_id)->where('is_profile', 1)->exists();
            $isBranch =Branch::where('library_id',$library_id)->where('status',1)->exists();
            $iscomp = Library::where('id', $library_id)->where('status', 1)->exists();
     

            if (($checkSub && $ispaid && $isBranch)) {
           
               
                return route('library.configration');
            }

            if ($checkSub && $ispaid) {
           
                return route('branch.configure.create');
            }

            if ($isEmailVeri) {
             
                $planId = session('selected_plan_id');
                $planMode = session('selected_plan_mode');
                if($planId && $planMode){
                    
                    return route('payment.store');
                }else{
                    
                     return route('subscriptions.choosePlan');
                }
               
               
            }
 
            return route('verification.notice');
        }

        return null;
    }
}
