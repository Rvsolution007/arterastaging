<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;

/**
 * CacheHelper — Centralized cache management for API responses and settings.
 * 
 * Call the appropriate clear method from admin controllers when data changes.
 * All cache keys are prefixed with 'api:' for API responses and 'settings:' for settings.
 */
class CacheHelper
{
    /**
     * All registered API cache keys.
     * Used by clearAllApiCache() to flush everything.
     */
    private static array $apiKeys = [
        'api:home_data',
        'api:categories',
        'api:stories',
        'api:custom_categories',
        'api:business_categories',
        'api:stickers',
        'api:product_categories',
        'api:products',
        'api:languages',
        'api:subscriptions',
        'api:app_about',
        'api:poster_categories',
        'api:ad_config',
        'api:news',
        'api:notifications',
        'api:business_cards',
        'api:payment_details',
    ];

    /**
     * All registered settings cache keys.
     */
    private static array $settingsKeys = [
        'settings:storage',
        'settings:api',
        'settings:payment',
        'settings:app',
        'settings:ads',
        'settings:email',
        'settings:other',
        'settings:whatsapp',
        'settings:notification',
        'settings:referral',
        'settings:app_update',
    ];

    // ─── Settings Cache ──────────────────────────────────────────────

    public static function clearStorageSettings(): void
    {
        Cache::forget('settings:storage');
        // Storage setting affects nearly all API responses
        static::clearAllApiCache();
    }

    public static function clearApiSettings(): void
    {
        Cache::forget('settings:api');
        static::clearAllApiCache();
    }

    public static function clearPaymentSettings(): void
    {
        Cache::forget('settings:payment');
        Cache::forget('api:payment_details');
        Cache::forget('api:app_about');
    }

    public static function clearAppSettings(): void
    {
        Cache::forget('settings:app');
        Cache::forget('api:app_about');
    }

    public static function clearAdsSettings(): void
    {
        Cache::forget('settings:ads');
        Cache::forget('api:ad_config');
        Cache::forget('api:app_about');
    }

    public static function clearEmailSettings(): void
    {
        Cache::forget('settings:email');
        Cache::forget('api:app_about');
    }

    public static function clearOtherSettings(): void
    {
        Cache::forget('settings:other');
        Cache::forget('api:app_about');
    }

    public static function clearWhatsappSettings(): void
    {
        Cache::forget('settings:whatsapp');
        Cache::forget('api:app_about');
    }

    public static function clearNotificationSettings(): void
    {
        Cache::forget('settings:notification');
        Cache::forget('api:app_about');
    }

    public static function clearReferralSettings(): void
    {
        Cache::forget('settings:referral');
        Cache::forget('api:app_about');
    }

    public static function clearAppUpdateSettings(): void
    {
        Cache::forget('settings:app_update');
        Cache::forget('api:app_about');
    }

    public static function clearAllSettings(): void
    {
        foreach (static::$settingsKeys as $key) {
            Cache::forget($key);
        }
        static::clearAllApiCache();
    }

    // ─── API Response Cache ──────────────────────────────────────────

    public static function clearHomeCache(): void
    {
        Cache::forget('api:home_data');
    }

    public static function clearCategoryCache(): void
    {
        Cache::forget('api:categories');
        Cache::forget('api:home_data');
    }

    public static function clearFestivalCache(): void
    {
        Cache::forget('api:home_data');
        // Festival caches are date-specific, clear the pattern
        // For simplicity, we clear home_data which includes festivals
    }

    public static function clearStoryCache(): void
    {
        Cache::forget('api:stories');
        Cache::forget('api:home_data');
    }

    public static function clearCustomPostCache(): void
    {
        Cache::forget('api:custom_categories');
        Cache::forget('api:business_categories');
        Cache::forget('api:home_data');
    }

    public static function clearStickerCache(): void
    {
        Cache::forget('api:stickers');
    }

    public static function clearProductCache(): void
    {
        Cache::forget('api:products');
        Cache::forget('api:product_categories');
    }

    public static function clearSubscriptionCache(): void
    {
        Cache::forget('api:subscriptions');
        Cache::forget('api:app_about');
    }

    public static function clearNewsCache(): void
    {
        Cache::forget('api:news');
    }

    public static function clearNotificationCache(): void
    {
        Cache::forget('api:notifications');
    }

    public static function clearBusinessCardCache(): void
    {
        Cache::forget('api:business_cards');
    }

    public static function clearPosterCache(): void
    {
        Cache::forget('api:poster_categories');
    }

    public static function clearLanguageCache(): void
    {
        Cache::forget('api:languages');
    }

    /**
     * Clear a specific template's cached JSON data.
     */
    public static function clearTemplateJsonCache(string $zipName): void
    {
        Cache::forget("template_json:{$zipName}");
    }

    /**
     * Nuclear option — clear ALL API and settings caches.
     * Use sparingly; prefer targeted clears.
     */
    public static function clearAllApiCache(): void
    {
        foreach (static::$apiKeys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Clear everything — settings + API caches.
     */
    public static function clearEverything(): void
    {
        static::clearAllSettings();
        static::clearAllApiCache();
    }
}
