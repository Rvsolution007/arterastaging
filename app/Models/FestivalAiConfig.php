<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FestivalAiConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'festival_id', 'festival_ai_brand_chrome_preset_id', 'is_enabled', 'base_prompt', 'product_prompt', 'allowed_size_keys',
        'max_products', 'allow_product_upload', 'require_product_name_for_upload',
        'max_user_instruction_characters',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'allowed_size_keys' => 'array',
        'allow_product_upload' => 'boolean',
        'require_product_name_for_upload' => 'boolean',
    ];

    public function festival()
    {
        return $this->belongsTo(Festivals::class, 'festival_id');
    }

    public function styles()
    {
        return $this->hasMany(FestivalAiStyle::class)->orderBy('sort_order')->orderBy('id');
    }

    public function brandChromePreset()
    {
        return $this->belongsTo(FestivalAiBrandChromePreset::class, 'festival_ai_brand_chrome_preset_id');
    }
}
