<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class BackfillRenderVersion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'artera:backfill-render-version';
    protected $description = 'Read render_version from each frame ZIP and store it in the DB column';

    public function handle()
    {
        $frames = \App\Models\PosterMaker::all();
        $bar = $this->output->createProgressBar($frames->count());

        foreach ($frames as $frame) {
            $jsonPath = public_path("uploads/template/{$frame->zip_name}/json/{$frame->zip_name}.json");
            if (file_exists($jsonPath)) {
                $json = json_decode(file_get_contents($jsonPath), true);
                $version = $json['render_version'] ?? 1;
                $frame->render_version = $version;
                $frame->save();
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Done! All frames backfilled.');
        return 0;
    }
}
