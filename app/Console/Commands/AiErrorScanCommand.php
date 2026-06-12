<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ErrorAnalysisService;
use App\Models\AiSetting;

class AiErrorScanCommand extends Command
{
    protected $signature = 'artera:ai-error-scan {--limit=30 : Max errors to analyze per run}';
    protected $description = 'AI-powered daily scan of unanalyzed client errors';

    public function handle()
    {
        // Check if auto-analyze is enabled
        if (!ErrorAnalysisService::isAutoAnalyzeEnabled()) {
            $this->info('AI Error Auto-Analyze is disabled in settings. Skipping.');
            return 0;
        }

        $limit = (int) $this->option('limit');
        $this->info("Starting AI Error Scan (limit: {$limit})...");

        $count = ErrorAnalysisService::batchAnalyze($limit);

        $this->info("AI Error Scan complete. Analyzed {$count} errors.");
        \Illuminate\Support\Facades\Log::info("AI Error Scan: Analyzed {$count} errors.");

        return 0;
    }
}
