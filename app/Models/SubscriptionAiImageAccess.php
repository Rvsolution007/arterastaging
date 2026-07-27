<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionAiImageAccess extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'ai_image_model_id',
        'allowed_qualities',
        'allowed_size_keys',
        'max_reference_images',
        'allow_refinement',
        'status',
        'sort_order',
    ];

    protected $casts = [
        'allowed_qualities' => 'array',
        'allowed_size_keys' => 'array',
        'allow_refinement' => 'boolean',
        'status' => 'boolean',
    ];

    public function subscription()
    {
        return $this->belongsTo(Subscription::class, 'subscription_id');
    }

    public function imageModel()
    {
        return $this->belongsTo(AiImageModel::class, 'ai_image_model_id');
    }
}
