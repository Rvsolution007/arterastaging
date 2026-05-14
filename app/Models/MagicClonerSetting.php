<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MagicClonerSetting extends Model
{
    protected $table = 'magic_cloner_settings';

    protected $fillable = [
        'key_name',
        'key_value',
    ];

    public static function getSetting($key, $default = null)
    {
        $setting = self::where('key_name', $key)->first();
        return $setting ? $setting->key_value : $default;
    }
}
