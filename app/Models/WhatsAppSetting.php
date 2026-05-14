<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class WhatsAppSetting extends Model
{
    protected $table = "whatsapp_setting";

    protected $fillable = [
        'key_name', 'key_value',
    ];

    public static function get($key)
    {
        return WhatsAppSetting::whereKeyName($key)->first()->key_value;
    }

    public static function getWhatsAppSetting($key)
    {
        $settings = Cache::remember('settings:whatsapp', 3600, function () {
            return Arr::pluck(WhatsAppSetting::all()->toArray(), 'key_value', 'key_name');
        });
        return (is_array($key)) ? Arr::only($settings, $key) : ($settings[$key] ?? null);
    }

    public static function clearCache()
    {
        Cache::forget('settings:whatsapp');
    }
}
