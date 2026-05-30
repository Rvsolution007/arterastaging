<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;

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

        // Force HTTPS for all assets and routes when behind a proxy like EasyPanel
        if (config('app.env') !== 'local') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}
