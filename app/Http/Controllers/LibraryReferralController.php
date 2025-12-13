<?php

namespace App\Http\Controllers;

use App\Models\LibraryReferral;
use Illuminate\Http\Request;

class LibraryReferralController extends Controller
{
     public function dashboard()
    {
        $libraryId = getLibraryId();

        return view('referral.library-referral-dashboard', [
            'total' => LibraryReferral::where('referrer_library_id', $libraryId)->count(),
            'completed' => LibraryReferral::where('referrer_library_id', $libraryId)->where('status','completed')->count(),
            'pending' => LibraryReferral::where('referrer_library_id', $libraryId)->where('status','pending')->count(),
        ]);
    }
}
