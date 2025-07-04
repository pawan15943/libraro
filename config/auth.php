<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Defaults
    |--------------------------------------------------------------------------
    |
    | This option controls the default authentication "guard" and password
    | reset options for your application. You may change these defaults
    | as required, but they're a perfect start for most applications.
    |
    */

    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],

   

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
            'session' => 'session_web',
        ],
        'library' => [
            'driver' => 'session',
            'provider' => 'libraries',
            'session' => 'session_library',
        ],
        'learner' => [
            'driver' => 'session',
            'provider' => 'learners',
            'session' => 'session_learner',
        ],
        'library_user' => [
            'driver' => 'session',
            'provider' => 'library_user',
             'session' => 'session_library_user',
        ],

            // New API guards
        'web_api' => [
            'driver' => 'sanctum',
            'provider' => 'users',
        ],
        'library_api' => [
            'driver' => 'sanctum',
            'provider' => 'libraries',
        ],
        'learner_api' => [
            'driver' => 'sanctum',
            'provider' => 'learners',
        ],
        'library_user_api' => [
            'driver' => 'sanctum',
            'provider' => 'library_user',
        ],
    ],


  

   
'providers' => [
    'users' => [
        'driver' => 'eloquent',
        'model' => App\Models\User::class,
    ],

    'libraries' => [
        'driver' => 'eloquent',
        'model' => App\Models\Library::class,
    ],
    'library_user' => [
        'driver' => 'eloquent',
        'model' => App\Models\LibraryUser::class,
    ],

    'learners' => [
        'driver' => 'eloquent',
        'model' => App\Models\Learner::class,
    ],
],

    /*
    |--------------------------------------------------------------------------
    | Resetting Passwords
    |--------------------------------------------------------------------------
    |
    | You may specify multiple password reset configurations if you have more
    | than one user table or model in the application and you want to have
    | separate password reset settings based on the specific user types.
    |
    | The expiry time is the number of minutes that each reset token will be
    | considered valid. This security feature keeps tokens short-lived so
    | they have less time to be guessed. You may change this as needed.
    |
    | The throttle setting is the number of seconds a user must wait before
    | generating more password reset tokens. This prevents the user from
    | quickly generating a very large amount of password reset tokens.
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
         'library' => [
            'provider' => 'library',
            'table' => 'password_reset_tokens',
             'expire' => 60,
            'throttle' => 60,
        ],
        // 'library' => [
        //     'provider' => 'library',
        //     'table' => 'password_resets',
        //     'expire' => 60,
        // ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Password Confirmation Timeout
    |--------------------------------------------------------------------------
    |
    | Here you may define the amount of seconds before a password confirmation
    | times out and the user is prompted to re-enter their password via the
    | confirmation screen. By default, the timeout lasts for three hours.
    |
    */

    'password_timeout' => 10800,

];
