<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FestivalAiStyle extends Model
{
    use HasFactory;

    protected $fillable = [
        'festival_ai_config_id', 'festival_ai_style_preset_id', 'name', 'prompt_text', 'product_placement_prompt', 'preview_images',
        'allowed_size_keys', 'product_required', 'sort_order', 'status',
    ];

    protected $casts = [
        'preview_images' => 'array',
        'allowed_size_keys' => 'array',
        'product_required' => 'boolean',
        'status' => 'boolean',
    ];

    public function config()
    {
        return $this->belongsTo(FestivalAiConfig::class, 'festival_ai_config_id');
    }

    public function preset()
    {
        return $this->belongsTo(FestivalAiStylePreset::class, 'festival_ai_style_preset_id');
    }
}
