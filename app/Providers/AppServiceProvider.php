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
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Force HTTPS on any non-localhost domain (staging, production, etc.)
        // Get actual domain, bypassing proxy issues where request()->getHost() returns localhost
        $httpHost = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? request()->getHost();
        $isLocal = in_array($httpHost, ['localhost', '127.0.0.1']) || str_starts_with($httpHost, '192.168.');
        
        if (!$isLocal) {
            URL::forceScheme('https');
            // Force the root URL to use the actual domain we are on.
            // This completely fixes the issue where asset() incorrectly appends /Artera/ to CSS URLs
            URL::forceRootUrl('https://' . $httpHost);
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
