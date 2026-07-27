<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FestivalAiStylePreset extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'prompt_text', 'product_placement_prompt', 'preview_images', 'allowed_size_keys',
        'product_required', 'sort_order', 'status',
    ];

    protected $casts = [
        'preview_images' => 'array',
        'allowed_size_keys' => 'array',
        'product_required' => 'boolean',
        'status' => 'boolean',
    ];

    public function festivalAssignments()
    {
        return $this->hasMany(FestivalAiStyle::class, 'festival_ai_style_preset_id');
    }
}
