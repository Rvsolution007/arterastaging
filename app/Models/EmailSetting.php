<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class EmailSetting extends Model
{
    protected $table = "email_setting";

    protected $fillable = [
        'key_name', 'key_value',
    ];

    public static function get($key)
    {
        return EmailSetting::whereName($key)->first()->key_value;
    }

    public static function getEmailSetting($key)
    {
        $settings = Cache::remember('settings:email', 3600, function () {
            return Arr::pluck(EmailSetting::all()->toArray(), 'key_value', 'key_name');
        });
        return (is_array($key)) ? Arr::only($settings, $key) : ($settings[$key] ?? null);
    }

    public static function clearCache()
    {
        Cache::forget('settings:email');
    }
}
