<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
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

        if (method_exists($response, 'header')) {
            $response->header('X-Content-Type-Options', 'nosniff');
            $response->header('X-Frame-Options', 'DENY');
            $response->header('Referrer-Policy', 'strict-origin-when-cross-origin');
            $response->header('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
            $response->header('X-Permitted-Cross-Domain-Policies', 'none');
            $response->header('Cross-Origin-Opener-Policy', 'same-origin');
            $response->header('Cross-Origin-Resource-Policy', 'same-site');
            $response->header('X-DNS-Prefetch-Control', 'off');

            if ($request->is('login', 'register', 'forgot-password', 'password/*', 'admin/login') || $request->is(config('app.api_prefix') . '/*')) {
                $response->header('Cache-Control', 'no-store, private');
                $response->header('Pragma', 'no-cache');
            }

            // Only apply strict CSP and HSTS on staging/production (not localhost)
            $host = $request->getHost();
            if ($host !== 'localhost' && !str_starts_with($host, '127.') && !str_starts_with($host, '192.168.')) {
                $response->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
                $response->header('Content-Security-Policy', "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://code.jquery.com https://www.googletagmanager.com https://kit.fontawesome.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://code.jquery.com https://kit.fontawesome.com; font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://ka-f.fontawesome.com data: blob:; img-src * data: blob:; media-src * data: blob:; connect-src *; frame-ancestors 'none'; object-src 'none'; base-uri 'self'");
            }
        }

        return $response;
    }
}
