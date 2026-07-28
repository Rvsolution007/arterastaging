<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Immutable history for AI Editable V1 documents.  The document manifest is
 * canonical; this table makes a user save reversible without touching the
 * existing frame/template source-of-truth rules.
 */
class AiEditableDocumentRevision extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'ai_editable_document_id',
        'revision',
        'manifest',
        'manifest_checksum',
        'created_at',
    ];

    protected $casts = [
        'manifest' => 'array',
        'revision' => 'integer',
        'created_at' => 'datetime',
    ];

    public function document()
    {
        return $this->belongsTo(AiEditableDocument::class, 'ai_editable_document_id');
    }
}
