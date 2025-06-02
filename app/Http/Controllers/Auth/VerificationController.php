<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\VerifiesEmails;
use Illuminate\Http\Request; 
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;

class VerificationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Email Verification Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling email verification for any
    | user that recently registered with the application. Emails may also
    | be re-sent if the user didn't receive the original email message.
    |
    */

    use VerifiesEmails;

    /**
     * Where to redirect users after verification.
     *
     * @var string
     */
    public function redirectPath()
    {
        return route('library.home'); // Adjust as necessary
    }


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware('auth');
        $this->middleware('auth:library');
        $this->middleware('signed')->only('verify');
        $this->middleware('throttle:6,1')->only('verify', 'resend');
    }

    // Override guard method to use the library guard
    protected function guard()
    {
        return Auth::guard('library');
    }

    // Show the email verification notice
    // Show the email verification notice
    public function show(Request $request)
    {
       
        // Ensure the user is authenticated with the 'library' guard
        $user = $request->user('library');

        // Otherwise, show the email verification notice view
        return view('auth.verify')->with('message', 'Please verify your email to continue.');
    }

    



   
}
