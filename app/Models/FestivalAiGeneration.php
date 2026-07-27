<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class FestivalAiGeneration extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id', 'user_id', 'subscription_id', 'festival_id', 'language_id', 'festival_ai_style_id',
        'ai_image_model_id', 'provider', 'provider_model_id', 'quality', 'size_key',
        'size_value', 'user_instruction', 'final_prompt', 'request_diagnostics',
        'actual_reference_count', 'product_snapshot', 'business_snapshot',
        'brand_chrome_snapshot', 'status',
        'attempt_count', 'quota_reserved_at', 'quota_refunded_at', 'generated_image_path',
        'error_code', 'error_message', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'product_snapshot' => 'array',
        'business_snapshot' => 'array',
        'brand_chrome_snapshot' => 'array',
        'request_diagnostics' => 'array',
        'actual_reference_count' => 'integer',
        'quota_reserved_at' => 'datetime',
        'quota_refunded_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected $appends = ['generated_image_url'];

    public function getGeneratedImageUrlAttribute(): ?string
    {
        if (!$this->generated_image_path) {
            return null;
        }

        if (StorageSetting::getStorageSetting('storage') === 'DigitalOcean') {
            return Storage::disk('spaces')->url('uploads/' . $this->generated_image_path);
        }

        return asset('uploads/' . $this->generated_image_path);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function festival()
    {
        return $this->belongsTo(Festivals::class, 'festival_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language_id');
    }

    public function style()
    {
        return $this->belongsTo(FestivalAiStyle::class, 'festival_ai_style_id');
    }

    public function imageModel()
    {
        return $this->belongsTo(AiImageModel::class, 'ai_image_model_id');
    }
}
