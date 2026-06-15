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
        // This does NOT depend on APP_ENV — it checks the actual hostname
        $host = request()->getHost();
        $isLocal = in_array($host, ['localhost', '127.0.0.1']) || str_starts_with($host, '192.168.');
        
        if (!$isLocal) {
            URL::forceScheme('https');
            // Also force the root URL to use https if APP_URL was set with http
            $appUrl = config('app.url');
            if ($appUrl && str_starts_with($appUrl, 'http://')) {
                $appUrl = str_replace('http://', 'https://', $appUrl);
                config(['app.url' => $appUrl]);
                URL::forceRootUrl($appUrl);
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
