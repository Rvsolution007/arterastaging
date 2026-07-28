<?php

namespace App\Jobs;

use App\Models\BusinessAiEditableRequest;
use App\Services\AiEditableDocumentGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessBusinessAiEditableDocument implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public int $timeout = 120;
    public int $tries = 1;
    public function __construct(private int $requestId) {}

    public function handle(AiEditableDocumentGenerator $generator): void
    {
        $request = DB::transaction(function () {
            $request = BusinessAiEditableRequest::lockForUpdate()->find($this->requestId);
            if (!$request || !in_array($request->status, ['queued', 'processing'], true) || ($request->status === 'processing' && $request->started_at)) return null;
            $request->update(['status' => 'processing', 'started_at' => now(), 'error_message' => null]);
            return $request->fresh(['generation.user']);
        });
        if (!$request || !$request->generation || $request->generation->status !== 'completed') return;
        try {
            $document = $generator->generate($request->generation);
            $request->update(['ai_editable_document_id' => $document->id, 'status' => 'ready', 'completed_at' => now()]);
        } catch (\Throwable $exception) {
            Log::warning('Business Post editable document failed.', ['request_id' => $this->requestId, 'message' => $exception->getMessage()]);
            $request->update(['status' => 'failed', 'completed_at' => now(), 'error_message' => 'The artwork is ready, but editable layers could not be created.']);
        }
    }

    public function failed(\Throwable $exception): void
    {
        $request = BusinessAiEditableRequest::find($this->requestId);
        if ($request && $request->status !== 'ready') $request->update(['status' => 'failed', 'completed_at' => now(), 'error_message' => 'The artwork is ready, but editable layers could not be created.']);
    }
}
