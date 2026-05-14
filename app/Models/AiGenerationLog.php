<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiGenerationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'ai_generation_batch_id',
        'user_id',
        'product_id',
        'raw_prompt',
        'raw_response',
        'tokens_used',
        'status',
        'error_message',
    ];

    public function batch()
    {
        return $this->belongsTo(AiGenerationBatch::class, 'ai_generation_batch_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
