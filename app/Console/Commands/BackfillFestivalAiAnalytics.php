<?php

namespace App\Console\Commands;

use App\Models\AiTokenLog;
use App\Models\FestivalAiGeneration;
use Illuminate\Console\Command;

class BackfillFestivalAiAnalytics extends Command
{
    protected $signature = 'festival-ai:backfill-analytics {--limit=500 : Maximum completed generations to inspect}';

    protected $description = 'Backfill missing Festival AI image analytics safely without duplicate usage rows';

    public function handle(): int
    {
        $limit = max(1, min((int) $this->option('limit'), 5000));
        $generations = FestivalAiGeneration::query()
            ->where('status', 'completed')
            ->with('imageModel')
            ->latest('id')
            ->limit($limit)
            ->get();

        $created = 0;
        foreach ($generations as $generation) {
            $reference = AiTokenLog::festivalSourceReference($generation->id);
            if (AiTokenLog::query()->where('source_reference', $reference)->exists()) {
                continue;
            }

            AiTokenLog::logFestivalImageUsage($generation);
            $created++;
        }

        $this->info("Festival AI analytics synced: {$created} new row(s), {$generations->count()} completed generation(s) checked.");

        return self::SUCCESS;
    }
}
