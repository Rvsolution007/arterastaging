<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class PaymentSetting extends Model
{
    protected $table = "payment_setting";

    protected $fillable = [
        'key_name', 'key_value',
    ];

    public static function get($key)
    {
        return PaymentSetting::whereKeyName($key)->first()->key_value;
    }

    public static function getPaymentSetting($key)
    {
        $settings = Cache::remember('settings:payment', 3600, function () {
            return Arr::pluck(PaymentSetting::all()->toArray(), 'key_value', 'key_name');
        });
        return (is_array($key)) ? Arr::only($settings, $key) : ($settings[$key] ?? null);
    }

    public static function clearCache()
    {
        Cache::forget('settings:payment');
    }
}
