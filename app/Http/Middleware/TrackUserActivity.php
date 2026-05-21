<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class TrackUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check()) {
            $user = auth()->user();
            
            // Only update DB if last_active_at is older than 1 hour to prevent DB overhead
            if (!$user->last_active_at || $user->last_active_at->diffInHours(now()) >= 1) {
                // Use a direct update to avoid updating the 'updated_at' timestamp unnecessarily 
                // and firing model events every time they hit the API.
                \DB::table('users')->where('id', $user->id)->update([
                    'last_active_at' => now(),
                ]);
            }
        }
        
        return $next($request);
    }
}
