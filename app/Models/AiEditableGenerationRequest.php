<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Optional V1 work requested alongside a normal Festival AI generation.
 *
 * It is purposefully separate from the generation status and quota state: a
 * layered-document failure must never make a successfully generated flat
 * Festival visual disappear or refund/recharge the original request.
 */
class AiEditableGenerationRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id',
        'festival_ai_generation_id',
        'user_id',
        'ai_editable_document_id',
        'status',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function generation()
    {
        return $this->belongsTo(FestivalAiGeneration::class, 'festival_ai_generation_id');
    }

    public function document()
    {
        return $this->belongsTo(AiEditableDocument::class, 'ai_editable_document_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
