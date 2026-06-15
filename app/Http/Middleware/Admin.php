<?php 

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;

class Admin 
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && (Auth::user()->user_type == "Super Admin" || Auth::user()->user_type == "Demo")) {
            return $next($request);
        }

        if (!Auth::check()) {
            return redirect('/login');
        }

        return redirect(RouteServiceProvider::HOME);
    }
}