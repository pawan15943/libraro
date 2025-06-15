<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use Illuminate\Support\Facades\Password;

use App\Models\Library;
use App\Models\LibraryUser;
use Illuminate\Support\Facades\Hash;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    // use SendsPasswordResetEmails;
    // protected function broker()
    // {
    //     return app('auth.password.broker');
    // }

    public function showLinkRequestForm()
    {
        return view('auth.passwords.email');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = Library::where('email', $request->email)->select('library_name as name','email')->first();
        if (!$user) {
            $user = LibraryUser::where('email', $request->email)
                ->select('name', 'email') // assuming LibraryUser has `name`
                ->first();
        }
        if (!$user) {
            return back()->withErrors(['email' => 'Email not found.']);
        }

        // Create a token
        $token = Str::random(60);

        // Save token in password_resets table
        \DB::table('password_resets')->updateOrInsert(
            ['email' => $request->email],
            ['token' => $token, 'created_at' => now()]
        );
        $resetUrl = url(route('password.reset', ['token' => $token, 'email' => $user->email]));
        // Send reset email (you can customize this)
        \Mail::send('email.forgot-password', ['token' => $token, 'email' => $user->email,'name'=>$user->name,'resetLink' => $resetUrl], function ($message) use ($request) {
            $message->to($request->email);
            $message->subject('Reset Your Account Password');
        });

        return back()->with('status', 'Password reset link sent!');
    }
}
