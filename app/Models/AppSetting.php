<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class AppSetting extends Model
{
    protected $table = "app_setting";

    protected $fillable = [
        'key_name', 'key_value',
    ];

    public static function get($key)
    {
        return AppSetting::whereName($key)->first()->key_value;
    }

    public static function getAppSetting($key)
    {
        $settings = Cache::remember('settings:app', 3600, function () {
            try {
                return Arr::pluck(AppSetting::all()->toArray(), 'key_value', 'key_name');
            } catch (\Exception $e) {
                return [];
            }
        });
        return (is_array($key)) ? Arr::only($settings, $key) : ($settings[$key] ?? null);
    }

    public static function clearCache()
    {
        Cache::forget('settings:app');
    }
}
