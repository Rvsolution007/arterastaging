<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessAiStyle extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_ai_purpose_id', 'key', 'name', 'description', 'prompt_text', 'colors', 'preview_image', 'status', 'sort_order',
    ];

    protected $casts = [
        'colors' => 'array',
        'status' => 'boolean',
    ];

    public function purposes()
    {
        return $this->belongsToMany(BusinessAiPurpose::class, 'business_ai_purpose_styles')
            ->withTimestamps()->orderBy('sort_order')->orderBy('title');
    }

    public function scopes()
    {
        return $this->belongsToMany(BusinessAiPurposeScope::class, 'business_ai_purpose_scope_styles')
            ->withTimestamps();
    }
}
