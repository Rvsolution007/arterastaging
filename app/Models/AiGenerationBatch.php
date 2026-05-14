<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiGenerationBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_custom_frame_id',
        'status',
        'total_users',
        'processed_users',
        'total_tokens',
        'total_cost',
    ];

    public function customFrame()
    {
        return $this->belongsTo(BusinessCustomFrame::class, 'business_custom_frame_id');
    }

    public function logs()
    {
        return $this->hasMany(AiGenerationLog::class, 'ai_generation_batch_id');
    }
}
