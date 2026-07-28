<?php

namespace App\Jobs;

use App\Models\FestivalAiGeneration;
use App\Models\AiTokenLog;
use App\Models\UserNotification;
use App\Models\User;
use App\Services\FestivalAiImageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessFestivalAiGeneration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 210;
    public int $tries = 1;

    public function __construct(public int $generationId)
    {
    }

    public function handle(FestivalAiImageService $imageService): void
    {
        $generation = FestivalAiGeneration::find($this->generationId);
        if (!$generation || $generation->status !== 'queued') {
            return;
        }

        $generation->update([
            'status' => 'processing',
            'started_at' => now(),
            'attempt_count' => $generation->attempt_count + 1,
        ]);

        try {
            $result = $imageService->generate($generation->fresh());
            $path = $result['path'];
            $generation->update([
                'status' => 'completed',
                'generated_image_path' => $path,
                'completed_at' => now(),
                'error_code' => null,
                'error_message' => null,
            ]);
            try {
                AiTokenLog::logFestivalImageUsage($generation->fresh(), (array) ($result['usage'] ?? []));
            } catch (\Throwable $exception) {
                // Analytics must never change a successful customer generation.
                Log::warning('Festival AI usage analytics could not be saved.', [
                    'generation_id' => $this->generationId,
                ]);
            }
            $this->queueEditableDocument($generation->fresh());
            $this->notifyUser($generation->fresh(), true);
        } catch (\Throwable $exception) {
            Log::warning('Festival AI generation failed.', [
                'generation_id' => $this->generationId,
                'message' => $exception->getMessage(),
            ]);
            $this->markFailedAndRefund($exception->getMessage());
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->markFailedAndRefund($exception->getMessage());
    }

    private function markFailedAndRefund(string $reason): void
    {
        $failedGeneration = DB::transaction(function () use ($reason) {
            $generation = FestivalAiGeneration::lockForUpdate()->find($this->generationId);
            if (!$generation || in_array($generation->status, ['completed', 'failed'], true)) {
                return null;
            }

            $generation->update([
                'status' => 'failed',
                'error_code' => 'provider_error',
                'error_message' => $this->safeFailureMessage($reason),
                'completed_at' => now(),
            ]);

            if ($generation->quota_reserved_at && !$generation->quota_refunded_at) {
                $user = User::lockForUpdate()->find($generation->user_id);
                if ($user) {
                    $user->ai_image_used = max(0, (int) $user->ai_image_used - 1);
                    $user->save();
                }
                $generation->update(['quota_refunded_at' => now()]);
            }

            return $generation->fresh();
        });

        if ($failedGeneration) {
            $this->notifyUser($failedGeneration, false);
        }
    }

    private function notifyUser(FestivalAiGeneration $generation, bool $completed): void
    {
        try {
            UserNotification::query()->firstOrCreate(
                [
                    'user_id' => $generation->user_id,
                    'type' => 'festival_ai',
                    'type_id' => $generation->id,
                ],
                [
                    'title' => $completed
                        ? 'Your festival visual is ready'
                        : 'Festival visual could not be created',
                    'message' => $completed
                        ? 'Your new AI visual is ready in My AI Creations.'
                        : 'Your generation did not complete. Your quota was restored.',
                    'image' => $completed ? $generation->generated_image_path : null,
                ]
            );
        } catch (\Throwable $exception) {
            // A notification must never change the completed/failed job state.
            Log::warning('Festival AI user notification could not be saved.', [
                'generation_id' => $generation->id,
            ]);
        }
    }

    private function queueEditableDocument(FestivalAiGeneration $generation): void
    {
        if (!(bool) config('ai_editable_v1.enabled') || blank(config('ai_editable_v1.planner_model'))) {
            return;
        }

        $editableRequest = $generation->editableRequest;
        if (!$editableRequest || $editableRequest->status !== 'queued') {
            return;
        }

        try {
            ProcessAiEditableDocument::dispatch($editableRequest->id)
                ->onConnection('festival-ai')
                ->onQueue('festival-ai');
        } catch (\Throwable $exception) {
            // The original Festival image is already completed. A V1 worker
            // failure only marks the optional editable output as failed.
            $editableRequest->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => 'The editable layer document could not be queued. Your original AI visual is still available.',
            ]);
            Log::warning('AI Editable V1 queue dispatch failed.', [
                'generation_id' => $generation->id,
            ]);
        }
    }

    private function safeFailureMessage(string $reason): string
    {
        // Provider failures are intentionally normalised in FestivalAiImageService.
        // Keep those useful account/billing messages visible, but never expose
        // unexpected server exception details to the mobile app.
        if (Str::startsWith($reason, 'Artera AI ')) {
            return Str::limit($reason, 700);
        }

        return 'The image provider could not complete this request. Your quota was restored.';
    }
}
