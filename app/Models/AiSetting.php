<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

class AiSetting extends Model
{
    protected $table = "ai_setting";

    protected $fillable = [
        'key_name',
        'key_value',
    ];

    public static function get($key)
    {
        $setting = AiSetting::where('key_name', $key)->first();
        return $setting ? $setting->key_value : null;
    }

    public static function getAiSetting($key)
    {
        $settings = Arr::pluck(AiSetting::all()->toArray(), 'key_value', 'key_name');
        if (is_array($key)) {
            return array_intersect_key($settings, array_flip($key));
        }
        return isset($settings[$key]) ? $settings[$key] : null;
    }
}
