<?php
namespace App\Services;

use App\Models\Branch;
use Illuminate\Support\Facades\Auth;
use App\Models\Library;
use App\Models\LibraryTransaction;

class LibraryService
{
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
