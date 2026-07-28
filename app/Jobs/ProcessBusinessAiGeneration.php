<?php

namespace App\Jobs;

use App\Models\BusinessAiEditableRequest;
use App\Models\BusinessAiGeneration;
use App\Models\User;
use App\Services\BusinessAiImageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessBusinessAiGeneration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 210;
    public int $tries = 1;

    public function __construct(public int $generationId) {}

    public function handle(BusinessAiImageService $images): void
    {
        $generation = BusinessAiGeneration::find($this->generationId);
        if (!$generation || $generation->status !== 'queued') return;
        $generation->update(['status' => 'processing', 'started_at' => now(), 'attempt_count' => $generation->attempt_count + 1]);
        try {
            $result = $images->generate($generation->fresh());
            $generation->update(['status' => 'completed', 'generated_image_path' => $result['path'], 'completed_at' => now(), 'error_code' => null, 'error_message' => null]);
            $this->queueEditableDocument($generation->fresh());
        } catch (\Throwable $exception) {
            Log::warning('Custom Post AI generation failed.', ['generation_id' => $this->generationId, 'message' => $exception->getMessage()]);
            $this->markFailedAndRefund($exception->getMessage());
        }
    }

    public function failed(\Throwable $exception): void { $this->markFailedAndRefund($exception->getMessage()); }

    private function queueEditableDocument(BusinessAiGeneration $generation): void
    {
        $request = $generation->editableRequest;
        if (!$request || $request->status !== 'queued') return;
        try {
            ProcessBusinessAiEditableDocument::dispatch($request->id)->onConnection('festival-ai')->onQueue('festival-ai');
        } catch (\Throwable $exception) {
            $request->update(['status' => 'failed', 'completed_at' => now(), 'error_message' => 'The artwork is ready, but its editable layers could not be prepared.']);
        }
    }

    private function markFailedAndRefund(string $reason): void
    {
        DB::transaction(function () use ($reason) {
            $generation = BusinessAiGeneration::lockForUpdate()->find($this->generationId);
            if (!$generation || in_array($generation->status, ['completed', 'failed'], true)) return;
            if ($generation->quota_reserved_at && !$generation->quota_refunded_at && ($user = User::lockForUpdate()->find($generation->user_id))) {
                $user->ai_image_used = max(0, (int) $user->ai_image_used - 1); $user->save();
                $generation->quota_refunded_at = now();
            }
            $generation->status = 'failed';
            $generation->error_code = 'provider_error';
            $generation->error_message = str_starts_with($reason, 'Custom Post AI') ? mb_substr($reason, 0, 700) : 'The image provider could not complete this Custom Post. Your credit was restored.';
            $generation->completed_at = now();
            $generation->save();
        });
    }
}
