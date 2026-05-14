<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class LogUserActivity
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
        $response = $next($request);

        if (auth()->check()) {
            $user = auth()->user();
            // Don't log activity for Super Admin or admins
            if ($user->user_type == 'Super Admin' || $user->user_type == 'A') {
                return $response;
            }

            try {
                $payload = $request->except(['password', 'password_confirmation', '_token']);
                // Filter out UploadedFile objects to prevent serialization errors
                array_walk_recursive($payload, function (&$item, $key) {
                    if ($item instanceof \Illuminate\Http\UploadedFile) {
                        $item = '[FILE: ' . $item->getClientOriginalName() . ']';
                    }
                });

                \App\Models\UserActivity::create([
                    'user_id' => auth()->id(),
                    'url' => mb_substr($request->fullUrl(), 0, 191),
                    'method' => $request->method(),
                    'action' => mb_substr($this->getActionName($request), 0, 191),
                    'ip_address' => $request->ip(),
                    'user_agent' => mb_substr($request->userAgent() ?? '', 0, 191),
                    'payload' => $payload,
                ]);
            } catch (\Exception $e) {
                // Silently fail - don't let activity logging crash the actual page
                \Log::warning('UserActivity log failed: ' . $e->getMessage());
            }
        }

        return $response;
    }

    protected function getActionName($request)
    {
        $route = $request->route();
        if ($route) {
            $action = $route->getActionName();
            // Simplify action name: Controller@method
            if (str_contains($action, '@')) {
                $parts = explode('\\', $action);
                return end($parts);
            }
            return $action;
        }
        return 'Unknown';
    }
}
