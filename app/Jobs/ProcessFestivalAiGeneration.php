<?php

namespace App\Jobs;

use App\Models\FestivalAiGeneration;
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
            $path = $imageService->generate($generation->fresh());
            $generation->update([
                'status' => 'completed',
                'generated_image_path' => $path,
                'completed_at' => now(),
                'error_code' => null,
                'error_message' => null,
            ]);
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
        DB::transaction(function () use ($reason) {
            $generation = FestivalAiGeneration::lockForUpdate()->find($this->generationId);
            if (!$generation || $generation->status === 'completed') {
                return;
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
        });
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
