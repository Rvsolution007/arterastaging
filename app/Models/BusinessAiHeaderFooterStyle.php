<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessAiHeaderFooterStyle extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'header_prompt', 'footer_prompt', 'overlay_enabled', 'sort_order', 'status',
    ];

    protected $casts = [
        'overlay_enabled' => 'boolean',
        'status' => 'boolean',
    ];

    public function customPostTypes()
    {
        return $this->hasMany(BusinessAiPurpose::class, 'business_ai_header_footer_style_id');
    }
}
