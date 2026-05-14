<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class NotificationSetting extends Model
{
    protected $table = "notification_setting";

    protected $fillable = [
        'key_name', 'key_value',
    ];

    public static function get($key)
    {
        $setting = NotificationSetting::where('key_name', $key)->first();
        return $setting ? $setting->key_value : null;
    }

    public static function getNotificationSetting($key)
    {
        $settings = Cache::remember('settings:notification', 3600, function () {
            return Arr::pluck(NotificationSetting::all()->toArray(), 'key_value', 'key_name');
        });
        if (is_array($key)) {
            return array_intersect_key($settings, array_flip($key));
        }
        return isset($settings[$key]) ? $settings[$key] : null;
    }

    public static function clearCache()
    {
        Cache::forget('settings:notification');
    }
}
