<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class ApiSetting extends Model
{
    protected $table = "api_setting";

    protected $fillable = [
        'key_name', 'key_value',
    ];

    public static function get($key)
    {
        return ApiSetting::whereName($key)->first()->key_value;
    }

    public static function getApiSetting($key)
    {
        $settings = Cache::remember('settings:api', 3600, function () {
            return Arr::pluck(ApiSetting::all()->toArray(), 'key_value', 'key_name');
        });
        return (is_array($key)) ? Arr::only($settings, $key) : ($settings[$key] ?? null);
    }

    /**
     * Clear cached settings. Call from admin controller when settings are updated.
     */
    public static function clearCache()
    {
        Cache::forget('settings:api');
    }
}
