<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = 'admin/';
    protected $namespace = 'App\\Http\\Controllers';
    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     *
     * @return void
     */
    public function boot()
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix(config('app.api_prefix'))
                ->namespace($this->namespace)
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->namespace($this->namespace)
                ->group(base_path('routes/web.php'));

            Route::middleware('web')
                ->prefix('admin')
                ->namespace($this->namespace)
                ->group(base_path('routes/admin.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     *
     * @return void
     */
    protected function configureRateLimiting()
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('login', function (Request $request) {
            $identity = strtolower((string) $request->input('email'));

            return [
                Limit::perMinute(5)->by('login:' . $identity . '|' . $request->ip()),
                Limit::perHour(25)->by('login-account:' . $identity),
            ];
        });

        RateLimiter::for('google-login', function (Request $request) {
            return Limit::perMinute(10)->by('google-login:' . $request->ip());
        });

        RateLimiter::for('admin-login', function (Request $request) {
            $identity = strtolower((string) $request->input('email'));

            return [
                Limit::perMinute(5)->by('admin-login:' . $identity . '|' . $request->ip()),
                Limit::perHour(20)->by('admin-login-account:' . $identity),
            ];
        });

        RateLimiter::for('password-reset', function (Request $request) {
            $identity = strtolower((string) $request->input('email'));

            return [
                Limit::perHour(3)->by('password-reset:' . $identity . '|' . $request->ip()),
                Limit::perHour(5)->by('password-reset-account:' . $identity),
            ];
        });

        RateLimiter::for('email-verification', function (Request $request) {
            return Limit::perMinute(5)->by('email-verification:' . $request->ip());
        });
    }
}
