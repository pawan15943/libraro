<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    // protected function redirectTo(Request $request): ?string
    // {
    //     return $request->expectsJson() ? null : route('login');
    // }
    protected function redirectTo(Request $request): ?string
    {
        
    
        if ($request->expectsJson()) {
            return null;
        }
    
        if ($request->is('administrator/*')) {
            return route('login.administrator'); 
        } elseif ($request->is('library/*')) {
            return route('login.library'); 
        } elseif ($request->is('learner/*')) {
            return route('login.learner'); 
        }
    
        return route('login.learner');
    }
    
    

}
