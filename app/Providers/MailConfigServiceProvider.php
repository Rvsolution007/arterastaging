<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

class MailConfigServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     * 
     * Dynamically override mail config from database (EmailSetting table).
     * This is essential for Docker deployments where .env is regenerated
     * on every build WITHOUT MAIL_* variables.
     */
    public function boot(): void
    {
        try {
            // Only attempt DB read if the table exists and DB is reachable
            if (Schema::hasTable('email_setting')) {
                $settings = \App\Models\EmailSetting::getEmailSetting([
                    'smtp_host', 'username', 'password', 'encryption', 'port'
                ]);

                if (!empty($settings) && is_array($settings)) {
                    $host = $settings['smtp_host'] ?? null;
                    $username = $settings['username'] ?? null;
                    $password = $settings['password'] ?? null;
                    $encryption = $settings['encryption'] ?? null;
                    $port = $settings['port'] ?? null;

                    // Only override if we have at least host and username from DB
                    if ($host && $username) {
                        Config::set('mail.mailers.smtp.host', $host);
                        Config::set('mail.mailers.smtp.username', $username);
                        Config::set('mail.mailers.smtp.password', $password);
                        Config::set('mail.mailers.smtp.encryption', $encryption ?: 'tls');
                        Config::set('mail.mailers.smtp.port', $port ?: 587);
                        Config::set('mail.from.address', $username);
                        Config::set('mail.from.name', config('app.name', 'Artera'));
                    }
                }
            }
        } catch (\Throwable $e) {
            // Silently fail — DB might not be ready during migrations/setup
            \Illuminate\Support\Facades\Log::debug('MailConfigServiceProvider: ' . $e->getMessage());
        }
    }
}
