<?php

namespace App\Services;

use App\Models\AiEditableDocument;
use App\Models\AiEditableDocumentRevision;
use App\Models\BusinessAiGeneration;
use App\Models\FestivalAiGeneration;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class AiEditableDocumentService
{
    public function __construct(private AiEditableDocumentContract $contract)
    {
    }

    public function create(User $user, array $manifest, ?FestivalAiGeneration $sourceGeneration = null): AiEditableDocument
    {
        return $this->createDocument($user, $manifest, $sourceGeneration, null);
    }

    public function createForBusiness(User $user, array $manifest, BusinessAiGeneration $sourceGeneration): AiEditableDocument
    {
        return $this->createDocument($user, $manifest, null, $sourceGeneration);
    }

    private function createDocument(
        User $user,
        array $manifest,
        ?FestivalAiGeneration $festivalGeneration,
        ?BusinessAiGeneration $businessGeneration
    ): AiEditableDocument
    {
        $manifest = $this->contract->validate($manifest);
        $checksum = $this->contract->checksum($manifest);

        return DB::transaction(function () use ($user, $manifest, $checksum, $festivalGeneration, $businessGeneration) {
            $document = AiEditableDocument::create([
                'public_id' => (string) Str::uuid(),
                'user_id' => $user->id,
                'festival_ai_generation_id' => $festivalGeneration?->id,
                'business_ai_generation_id' => $businessGeneration?->id,
                'module_version' => 'ai_editable_v1',
                'document_contract' => $manifest['document_contract'],
                'schema_version' => $manifest['schema_version'],
                'status' => 'ready',
                'manifest' => $manifest,
                'manifest_checksum' => $checksum,
                'revision' => 1,
                'source_image_path' => $festivalGeneration?->generated_image_path ?? $businessGeneration?->generated_image_path,
            ]);

            AiEditableDocumentRevision::create([
                'ai_editable_document_id' => $document->id,
                'revision' => 1,
                'manifest' => $manifest,
                'manifest_checksum' => $checksum,
                'created_at' => now(),
            ]);

            return $document;
        });
    }

    public function save(AiEditableDocument $document, int $expectedRevision, array $manifest): AiEditableDocument
    {
        $manifest = $this->contract->validate($manifest);
        $checksum = $this->contract->checksum($manifest);

        return DB::transaction(function () use ($document, $expectedRevision, $manifest, $checksum) {
            $locked = AiEditableDocument::lockForUpdate()->findOrFail($document->id);
            if ($locked->revision !== $expectedRevision) {
                throw new RuntimeException('This document was changed on another device. Refresh it before saving again.', 409);
            }

            $nextRevision = $locked->revision + 1;
            $locked->update([
                'document_contract' => $manifest['document_contract'],
                'schema_version' => $manifest['schema_version'],
                'manifest' => $manifest,
                'manifest_checksum' => $checksum,
                'revision' => $nextRevision,
                'status' => 'ready',
            ]);

            AiEditableDocumentRevision::create([
                'ai_editable_document_id' => $locked->id,
                'revision' => $nextRevision,
                'manifest' => $manifest,
                'manifest_checksum' => $checksum,
                'created_at' => now(),
            ]);

            return $locked->fresh();
        });
    }
}
