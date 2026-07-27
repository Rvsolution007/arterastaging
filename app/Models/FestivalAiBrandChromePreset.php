<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FestivalAiBrandChromePreset extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'header_prompt', 'footer_prompt', 'overlay_enabled',
        'header_height_percent', 'footer_height_percent', 'panel_style',
        'logo_position', 'text_tone', 'max_contact_items', 'sort_order', 'status',
    ];

    protected $casts = [
        'overlay_enabled' => 'boolean',
        'header_height_percent' => 'integer',
        'footer_height_percent' => 'integer',
        'max_contact_items' => 'integer',
        'status' => 'boolean',
    ];

    public function festivalConfigs()
    {
        return $this->hasMany(FestivalAiConfig::class, 'festival_ai_brand_chrome_preset_id');
    }
}
