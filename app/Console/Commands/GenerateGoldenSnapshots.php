<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PosterMaker;
use App\Models\GoldenRender;
use App\Services\WebRenderSimulator;
use App\Services\NativeRenderSimulator;

class GenerateGoldenSnapshots extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'frames:generate-golden-snapshots';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Golden Snapshots (V1 baseline) for all existing frames';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting Golden Snapshot Generation...');

        $frames = PosterMaker::all();
        $total = $frames->count();
        $this->info("Found $total frames to process.");

        $webSim = new WebRenderSimulator();
        $nativeSim = new NativeRenderSimulator();

        $successCount = 0;
        $errorCount = 0;

        $bar = $this->output->createProgressBar($total);

        foreach ($frames as $frame) {
            try {
                $jsonPath = public_path("uploads/template/{$frame->zip_name}/json/{$frame->zip_name}.json");

                if (!file_exists($jsonPath)) {
                    $this->error("\nJSON not found for frame: {$frame->zip_name}");
                    $errorCount++;
                    $bar->advance();
                    continue;
                }

                $json = json_decode(file_get_contents($jsonPath), true);
                if (!$json) {
                    $this->error("\nInvalid JSON for frame: {$frame->zip_name}");
                    $errorCount++;
                    $bar->advance();
                    continue;
                }

                // Simulate at Version 1 (Baseline)
                $webComputed = $webSim->compute($json, 1);
                $nativeComputed = $nativeSim->compute($json, 1);

                GoldenRender::capture($frame->id, $frame->zip_name, 1, [
                    'web_computed' => $webComputed,
                    'native_computed' => $nativeComputed,
                    'source' => 'system_batch'
                ]);

                $successCount++;
            } catch (\Exception $e) {
                $this->error("\nError processing frame {$frame->zip_name}: " . $e->getMessage());
                $errorCount++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->info("\n\nGolden Snapshot Generation Complete!");
        $this->info("Successfully generated: $successCount");
        $this->info("Errors encountered: $errorCount");

        return 0;
    }
}
