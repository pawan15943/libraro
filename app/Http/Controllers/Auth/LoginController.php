<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Models\Learner;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    public function showLoginForm()
    {
        return view('auth.login');
    }

    // Show Admin (Library Owner) Login Form
    public function showAdminLoginForm()
    {
        return view('auth.login_admin');
    }

    // Show Learner Login Form
    public function showLearnerLoginForm()
    {
        return view('auth.login_learner');
    }

    // Handle Login
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'user_type' => 'required',
        ]);

        $credentials = $request->only('email', 'password');
        $remember = $request->has('remember');

        switch ($request->input('user_type')) {
            case 'superadmin':
                if (Auth::guard('web')->attempt($credentials, $remember)) {
                    return redirect()->intended(route('home'));
                } else {
                    return redirect()->back()->withErrors(['error' => 'Invalid email or password for Superadmin.']);
                }
                break;

            case 'admin':
                Auth::guard('library')->logout();
                Auth::guard('library_user')->logout();
                if (Auth::guard('library')->attempt($credentials, $remember)) {
                    
                    $user = Auth::guard('library')->user();
                   
                    if (is_null($user->email_verified_at)) {
                        
                        Auth::guard('library')->logout();
                        if ($user) {
                            $otp = rand(100000, 999999); // Generates a 6-digit numeric OTP
                            $user->email_otp = $otp;
                            $user->save();

                            $this->sendVerificationEmail($user);
                            session()->flash('library_email', $user->email);
                        }
                        return redirect()->route('verification.notice')->with('email', $user->email);
                    }
                    
                    if (!$user->hasRole('admin', 'library')) {
                     
                        $user->assignRole('admin');
                    }
                
                    return redirect()->intended(route('library.home'));
                }elseif(Auth::guard('library_user')->attempt($credentials, $remember)){
            
                        $user = Auth::guard('library_user')->user();
                       

                        if (!$user->hasRole('admin_user', 'library_user')) {
                     
                            $user->assignRole('admin_user');
                        }
                          
                    // return redirect()->intended(route('library.user.login'));
                    return redirect()->intended(route('library.home'));
                } else {
                   
                    Log::error('Library user login failed.', ['credentials' => $credentials]);
                    return redirect()->back()->withErrors(['error' => 'Invalid email or password for Admin.']);
                }
                break;

           
            case 'learner':

                $enteredEmail = $request->email;
                $password = $request->password;
                $remember = $request->has('remember');

                // 🔍 Manually find the user by decrypting stored emails
                $user = Learner::get()->first(function ($learner) use ($enteredEmail) {
                    return $learner->email === $enteredEmail;
                });

                if (!$user) {
                    return redirect()->back()->withErrors(['error' => 'Invalid email or password']);
                }

                // 🛑 Auth::attempt() won't work since email is encrypted, manually verify password
                if (!Hash::check($password, $user->password)) {
                    return redirect()->back()->withErrors(['error' => 'Invalid email or password']);
                }

                // ✅ Manually log in the user
                Auth::guard('learner')->login($user, $remember);

                // 🏆 Assign role if not already assigned
                if (!$user->hasRole('learner')) {
                    $user->assignRole('learner');
                }

                return redirect()->intended(route('learner.home'));

                break;


            default:
                return back()->withErrors(['error' => 'Invalid user type selected.']);
        }
    }



    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    protected function authenticated(Request $request, $user)
    {
        $user->touch();
    }

    public function sendVerificationEmail($library)
    {
        $data = [
            'name' => $library->library_name,
            'email' => $library->email,
            'otp' => $library->email_otp,
        ];
        if (app()->environment('local')) {
            
        } else {
            Mail::send('email.verify-email', $data, function ($message) use ($data) {
                $message->to($data['email'], $data['name'])->subject('Verify Your Email Address');
            });
        }
        
    }
}
