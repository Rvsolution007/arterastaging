<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessAiEditableRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id', 'business_ai_generation_id', 'user_id', 'ai_editable_document_id',
        'status', 'error_message', 'started_at', 'completed_at',
    ];

    protected $casts = ['started_at' => 'datetime', 'completed_at' => 'datetime'];

    public function generation()
    {
        return $this->belongsTo(BusinessAiGeneration::class, 'business_ai_generation_id');
    }

    public function document()
    {
        return $this->belongsTo(AiEditableDocument::class, 'ai_editable_document_id');
    }
}
