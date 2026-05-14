<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class OtherSetting extends Model
{
    protected $table = "other_setting";

    protected $fillable = [
        'key_name', 'key_value',
    ];

    public static function get($key)
    {
        return OtherSetting::whereName($key)->first()->key_value;
    }

    public static function getOtherSetting($key)
    {
        $settings = Cache::remember('settings:other', 3600, function () {
            return Arr::pluck(OtherSetting::all()->toArray(), 'key_value', 'key_name');
        });
        return (is_array($key)) ? Arr::only($settings, $key) : ($settings[$key] ?? null);
    }

    public static function clearCache()
    {
        Cache::forget('settings:other');
    }
}
