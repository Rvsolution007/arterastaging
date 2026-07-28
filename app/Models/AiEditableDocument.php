<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Canonical source of truth for the standalone AI Editable V1 editor.
 *
 * This model deliberately has no render_version field and no frame/template
 * relationship.  It must remain isolated from the existing frame contract.
 */
class AiEditableDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id',
        'user_id',
        'festival_ai_generation_id',
        'business_ai_generation_id',
        'module_version',
        'document_contract',
        'schema_version',
        'status',
        'manifest',
        'manifest_checksum',
        'revision',
        'source_image_path',
        'preview_image_path',
        'export_image_path',
    ];

    protected $casts = [
        'manifest' => 'array',
        'schema_version' => 'integer',
        'revision' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function festivalAiGeneration()
    {
        return $this->belongsTo(FestivalAiGeneration::class);
    }

    public function businessAiGeneration()
    {
        return $this->belongsTo(BusinessAiGeneration::class);
    }

    public function revisions()
    {
        return $this->hasMany(AiEditableDocumentRevision::class)->orderBy('revision');
    }
}
