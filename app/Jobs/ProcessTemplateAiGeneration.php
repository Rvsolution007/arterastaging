<?php

namespace App\Jobs;

use App\Models\AiGenerationBatch;
use App\Models\AiGenerationLog;
use App\Models\BusinessCustomFrame;
use App\Models\Product;
use App\Models\User;
use App\Services\CustomFrameAIService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessTemplateAiGeneration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $frameId;
    public int $batchId;

    /**
     * Job timeout in seconds (10 minutes for large user bases).
     */
    public int $timeout = 600;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 1;

    public function __construct(int $frameId, int $batchId)
    {
        $this->frameId = $frameId;
        $this->batchId = $batchId;
    }

    public function handle(): void
    {
        $batch = AiGenerationBatch::find($this->batchId);
        if (!$batch) {
            Log::error("ProcessTemplateAiGeneration: Batch #{$this->batchId} not found.");
            return;
        }

        $batch->update(['status' => 'processing']);

        // Get all users who have at least one product (active app users)
        $userIds = Product::select('user_id')->distinct()->pluck('user_id');

        $batch->update(['total_users' => $userIds->count()]);

        $totalTokens = 0;

        foreach ($userIds->chunk(50) as $chunk) {
            foreach ($chunk as $userId) {
                try {
                    $result = CustomFrameAIService::generateForUser($this->frameId, $userId);

                    $logEntry = CustomFrameAIService::getLastGenerationLog();

                    AiGenerationLog::create([
                        'ai_generation_batch_id' => $this->batchId,
                        'user_id' => $userId,
                        'product_id' => $logEntry['product_id'] ?? null,
                        'raw_prompt' => $logEntry['raw_prompt'] ?? null,
                        'raw_response' => $logEntry['raw_response'] ?? null,
                        'tokens_used' => $logEntry['tokens_used'] ?? 0,
                        'status' => $result ? 'success' : 'failed',
                        'error_message' => $logEntry['error'] ?? null,
                    ]);

                    $totalTokens += ($logEntry['tokens_used'] ?? 0);

                } catch (\Exception $e) {
                    AiGenerationLog::create([
                        'ai_generation_batch_id' => $this->batchId,
                        'user_id' => $userId,
                        'status' => 'failed',
                        'error_message' => $e->getMessage(),
                    ]);
                    Log::warning("ProcessTemplateAiGeneration: Failed for user #{$userId}: " . $e->getMessage());
                }

                $batch->increment('processed_users');
            }
        }

        // Calculate estimated cost (Gemini 2.0 Flash pricing: ~$0.10 per 1M input tokens)
        $estimatedCost = ($totalTokens / 1000000) * 0.10;

        $batch->update([
            'status' => 'completed',
            'total_tokens' => $totalTokens,
            'total_cost' => $estimatedCost,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $batch = AiGenerationBatch::find($this->batchId);
        if ($batch) {
            $batch->update(['status' => 'failed']);
        }
        Log::error("ProcessTemplateAiGeneration: Job failed - " . $exception->getMessage());
    }
}
