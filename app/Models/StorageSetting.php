<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class StorageSetting extends Model
{
    protected $table = "storage_setting";

    protected $fillable = [
        'key_name', 'key_value',
    ];

    public static function get($key)
    {
        return StorageSetting::whereKeyName($key)->first()->key_value;
    }

    public static function getStorageSetting($key)
    {
        $settings = Cache::remember('settings:storage', 3600, function () {
            try {
                return Arr::pluck(StorageSetting::all()->toArray(), 'key_value', 'key_name');
            } catch (\Exception $e) {
                return [];
            }
        });
        return (is_array($key)) ? Arr::only($settings, $key) : ($settings[$key] ?? null);
    }

    /**
     * Clear cached settings. Call from admin controller when settings are updated.
     */
    public static function clearCache()
    {
        Cache::forget('settings:storage');
    }
}
