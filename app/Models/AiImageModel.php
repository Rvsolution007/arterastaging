<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiImageModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider', 'model_id', 'display_name', 'description', 'quality_options', 'quality_display_names',
        'size_options', 'default_quality', 'default_size_key', 'supports_reference_images',
        'supports_edits', 'supports_transparent_background', 'max_reference_images',
        'estimated_seconds', 'pricing_config', 'is_active', 'is_recommended', 'sort_order',
    ];

    protected $casts = [
        'quality_options' => 'array',
        'quality_display_names' => 'array',
        'size_options' => 'array',
        'pricing_config' => 'array',
        'supports_reference_images' => 'boolean',
        'supports_edits' => 'boolean',
        'supports_transparent_background' => 'boolean',
        'is_active' => 'boolean',
        'is_recommended' => 'boolean',
    ];
}
