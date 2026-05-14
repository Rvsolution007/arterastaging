<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class AppUpdateSetting extends Model
{
    protected $table = "app_update_setting";

    protected $fillable = [
        'key_name', 'key_value',
    ];

    public static function get($key)
    {
        return AppUpdateSetting::whereName($key)->first()->key_value;
    }

    public static function getAppUpdateSetting($key)
    {
        $settings = Cache::remember('settings:app_update', 3600, function () {
            return Arr::pluck(AppUpdateSetting::all()->toArray(), 'key_value', 'key_name');
        });
        return (is_array($key)) ? Arr::only($settings, $key) : ($settings[$key] ?? null);
    }

    public static function clearCache()
    {
        Cache::forget('settings:app_update');
    }
}
