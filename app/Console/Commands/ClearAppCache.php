<?php

namespace App\Console\Commands;

use App\Helpers\CacheHelper;
use Illuminate\Console\Command;

/**
 * Artisan command to clear all application caches (settings + API responses).
 * 
 * Usage: php artisan cache:clear-app
 * 
 * This does NOT clear Laravel's framework cache (config, routes, views).
 * It only clears the application-level caches managed by CacheHelper.
 */
class ClearAppCache extends Command
{
    protected $signature = 'cache:clear-app {--settings : Clear only settings cache} {--api : Clear only API response cache}';
    
    protected $description = 'Clear all ArtEra Pixel application caches (settings + API responses)';

    public function handle()
    {
        if ($this->option('settings')) {
            CacheHelper::clearAllSettings();
            $this->info('✅ All settings caches cleared.');
            return;
        }

        if ($this->option('api')) {
            CacheHelper::clearAllApiCache();
            $this->info('✅ All API response caches cleared.');
            return;
        }

        // Clear everything
        CacheHelper::clearEverything();
        $this->info('✅ All application caches cleared (settings + API responses).');
    }
}
