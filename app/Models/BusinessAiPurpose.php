<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessAiPurpose extends Model
{
    use HasFactory;

    protected $fillable = [
        'key', 'title', 'icon', 'description', 'base_prompt', 'product_prompt', 'brief_fields', 'allowed_size_keys',
        'business_ai_header_footer_style_id', 'product_upload_enabled', 'product_required',
        'max_product_references', 'change_instruction_limit', 'status', 'sort_order',
    ];

    protected $casts = [
        'brief_fields' => 'array',
        'allowed_size_keys' => 'array',
        'product_upload_enabled' => 'boolean',
        'product_required' => 'boolean',
        'status' => 'boolean',
    ];

    public function styles()
    {
        return $this->belongsToMany(BusinessAiStyle::class, 'business_ai_purpose_styles')
            ->withTimestamps()->orderBy('sort_order')->orderBy('name');
    }

    public function headerFooterStyle()
    {
        return $this->belongsTo(BusinessAiHeaderFooterStyle::class, 'business_ai_header_footer_style_id');
    }
}
