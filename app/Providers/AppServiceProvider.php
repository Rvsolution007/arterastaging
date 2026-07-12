<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // ==========================================
        // DEVOPS: Intercept ASSET_URL early!
        // ==========================================
        // The UrlGenerator singleton captures config('app.asset_url') VERY early.
        // If the staging .env file has a broken ASSET_URL or APP_URL (e.g. with /Artera),
        // we MUST override it here in register() before the singleton is created.
        $request = request();
        $host = $request->header('X-Forwarded-Host') ?? $request->header('Host') ?? $request->getHost();
        $host = explode(':', $host)[0];
        
        $isLocal = in_array($host, ['localhost', '127.0.0.1', '::1']) || str_starts_with($host, '192.168.') || str_ends_with($host, '.test');
        
        if (!$isLocal) {
            config(['app.asset_url' => 'https://' . $host]);
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // ==========================================
        // DEVOPS: Ultimate Flexible URL & Proxy Fix
        // ==========================================
        // This handles local, staging, and production environments seamlessly,
        // ignoring misconfigured .env files and fixing reverse proxy path mangling.
        
        $request = request();
        $host = $request->header('X-Forwarded-Host') ?? $request->header('Host') ?? $request->getHost();
        $host = explode(':', $host)[0]; // Remove port if present
        
        $isLocal = in_array($host, ['localhost', '127.0.0.1', '::1']) || str_starts_with($host, '192.168.') || str_ends_with($host, '.test');
        $isSecure = $request->isSecure() || $request->header('X-Forwarded-Proto') === 'https';
        
        if (!$isLocal) {
            // 1. Force HTTPS on all live domains
            URL::forceScheme('https');
            
            // 2. Force the root URL to strictly be the domain name.
            // This strips out any internal proxy paths (e.g. /Artera) that cause 404s.
            URL::forceRootUrl('https://' . $host);
        } elseif ($isLocal && $isSecure) {
            URL::forceScheme('https');
        }
        
        // ==========================================
        // SECURITY: Verify Critical Environment Variables
        // ==========================================
        $requiredEnvVars = ['APP_KEY', 'DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME'];
        foreach ($requiredEnvVars as $var) {
            if (empty(env($var))) {
                throw new \Exception("Critical Environment Variable Missing: $var. The application refuses to start.");
            }
        }

        
        Paginator::useBootstrapFour();
        Schema::defaultStringLength(191);
        
        // Dynamically set Mail configuration from database EmailSettings
        try {
            if (Schema::hasTable('email_setting')) {
                $emailSettings = \App\Models\EmailSetting::all()->pluck('key_value', 'key_name')->toArray();
                if (!empty($emailSettings['smtp_host'])) {
                    config([
                        'mail.mailers.smtp.host'       => $emailSettings['smtp_host'] ?? config('mail.mailers.smtp.host'),
                        'mail.mailers.smtp.port'       => $emailSettings['port'] ?? config('mail.mailers.smtp.port'),
                        'mail.mailers.smtp.encryption' => $emailSettings['encryption'] ?? config('mail.mailers.smtp.encryption'),
                        'mail.mailers.smtp.username'   => $emailSettings['username'] ?? config('mail.mailers.smtp.username'),
                        'mail.mailers.smtp.password'   => $emailSettings['password'] ?? config('mail.mailers.smtp.password'),
                        'mail.from.address'            => $emailSettings['username'] ?? config('mail.from.address'),
                        'mail.from.name'               => config('app.name', 'Artera'),
                    ]);
                }
            }
        } catch (\Exception $e) {
            // Ignore if DB not ready
        }
    }
}
