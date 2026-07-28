<?php

namespace App\Jobs;

use App\Models\AiEditableGenerationRequest;
use App\Services\AiEditableDocumentGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessAiEditableDocument implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    public int $tries = 1;

    public function __construct(private int $requestId)
    {
    }

    public function handle(AiEditableDocumentGenerator $generator): void
    {
        $request = DB::transaction(function () {
            $request = AiEditableGenerationRequest::lockForUpdate()->find($this->requestId);
            if (!$request || !in_array($request->status, ['queued', 'processing'], true)) {
                return null;
            }
            if ($request->status === 'processing' && $request->started_at) {
                return null;
            }
            $request->update(['status' => 'processing', 'started_at' => now(), 'error_message' => null]);
            return $request->fresh(['generation.user']);
        });
        if (!$request || !$request->generation || $request->generation->status !== 'completed') {
            return;
        }

        try {
            $document = $generator->generate($request->generation);
            $request->update([
                'ai_editable_document_id' => $document->id,
                'status' => 'ready',
                'completed_at' => now(),
                'error_message' => null,
            ]);
        } catch (Throwable $exception) {
            Log::warning('AI Editable V1 document generation failed.', [
                'editable_request_id' => $this->requestId,
                'message' => $exception->getMessage(),
            ]);
            $request->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => 'The editable layer document could not be created. Your original AI visual is still available.',
            ]);
        }
    }

    /**
     * Covers worker timeouts or other failures outside handle's provider
     * guards. The parent flat generation remains completed either way.
     */
    public function failed(Throwable $exception): void
    {
        $request = AiEditableGenerationRequest::find($this->requestId);
        if (!$request || $request->status === 'ready') {
            return;
        }

        $request->update([
            'status' => 'failed',
            'completed_at' => now(),
            'error_message' => 'The editable layer document could not be created. Your original AI visual is still available.',
        ]);
        Log::warning('AI Editable V1 job terminated outside its provider guard.', [
            'editable_request_id' => $this->requestId,
            'exception' => $exception::class,
        ]);
    }
}
