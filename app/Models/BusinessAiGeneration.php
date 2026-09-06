<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Frame-free Business Post AI request. Its artwork is intentionally separate
 * from template/custom-frame rendering and uses the V1 editable manifest.
 */
class BusinessAiGeneration extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id', 'user_id', 'subscription_id', 'root_generation_id', 'parent_generation_id', 'generation_kind', 'credit_cost', 'purpose_key', 'purpose_title',
        'business_ai_purpose_scope_id',
        'style_key', 'style_name', 'language_id', 'ai_image_model_id', 'provider',
        'provider_model_id', 'quality', 'size_key', 'size_value', 'brief',
        'user_instruction', 'final_prompt', 'request_diagnostics', 'product_snapshot',
        'business_snapshot', 'scope_snapshot', 'content_preview', 'palette_snapshot', 'status', 'is_saved_draft', 'saved_as_draft_at', 'attempt_count', 'quota_reserved_at',
        'quota_refunded_at', 'generated_image_path', 'error_code', 'error_message',
        'started_at', 'completed_at',
    ];

    protected $casts = [
        'brief' => 'array', 'product_snapshot' => 'array', 'business_snapshot' => 'array',
        'scope_snapshot' => 'array', 'content_preview' => 'array', 'palette_snapshot' => 'array',
        'request_diagnostics' => 'array', 'quota_reserved_at' => 'datetime',
        'quota_refunded_at' => 'datetime', 'saved_as_draft_at' => 'datetime', 'started_at' => 'datetime', 'completed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function imageModel()
    {
        return $this->belongsTo(AiImageModel::class, 'ai_image_model_id');
    }

    public function purposeScope()
    {
        return $this->belongsTo(BusinessAiPurposeScope::class, 'business_ai_purpose_scope_id');
    }

    public function editableRequest()
    {
        return $this->hasOne(BusinessAiEditableRequest::class);
    }

    public function parentGeneration()
    {
        return $this->belongsTo(self::class, 'parent_generation_id');
    }

    public function childGenerations()
    {
        return $this->hasMany(self::class, 'parent_generation_id');
    }
}
