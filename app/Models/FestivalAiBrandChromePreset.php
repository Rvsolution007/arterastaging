<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FestivalAiBrandChromePreset extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'header_prompt', 'footer_prompt', 'sort_order', 'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function festivalConfigs()
    {
        return $this->hasMany(FestivalAiConfig::class, 'festival_ai_brand_chrome_preset_id');
    }
}
