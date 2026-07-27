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
        $updated = 0;
        foreach ($generations as $generation) {
            $reference = AiTokenLog::festivalSourceReference($generation->id);
            $existing = AiTokenLog::query()->where('source_reference', $reference)->first();
            if ($existing) {
                $diagnostics = (array) $generation->request_diagnostics;
                $actualReferenceCount = (int) data_get(
                    $diagnostics,
                    'attached_reference_count',
                    $generation->actual_reference_count ?? 0
                );
                $endpoint = (string) data_get($diagnostics, 'endpoint', '');
                $parameters = array_merge((array) $existing->parameters, [
                    'reference_image_count' => $actualReferenceCount,
                    'mode' => $endpoint === '/v1/images/edits' || $actualReferenceCount > 0
                        ? 'edit_with_reference'
                        : 'generate',
                ]);
                if ($parameters !== (array) $existing->parameters) {
                    $existing->update(['parameters' => $parameters]);
                    $updated++;
                }
                continue;
            }

            AiTokenLog::logFestivalImageUsage($generation);
            $created++;
        }

        $this->info("Festival AI analytics synced: {$created} new row(s), {$updated} corrected row(s), {$generations->count()} completed generation(s) checked.");

        return self::SUCCESS;
    }
}
