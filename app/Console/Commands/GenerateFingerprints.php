<?php

namespace App\Console\Commands;

use App\Models\CustomPostFrame;
use App\Services\TemplateFingerprintService;
use Illuminate\Console\Command;

class GenerateFingerprints extends Command
{
    protected $signature = 'fingerprint:generate';
    protected $description = 'Generate structural fingerprints for all existing Custom Post Frame templates';

    public function handle()
    {
        $service = new TemplateFingerprintService();
        $templates = CustomPostFrame::whereNotNull('zip_name')->get();

        $this->info("Found {$templates->count()} templates with ZIP files.");

        $success = 0;
        $failed = 0;

        foreach ($templates as $template) {
            $templateDir = public_path('uploads/template/' . $template->zip_name);

            if (!is_dir($templateDir)) {
                $this->warn("  ✗ ID #{$template->id} ({$template->zip_name}) — directory not found, skipping.");
                $failed++;
                continue;
            }

            $fingerprint = $service->extractFromZip($templateDir);

            if ($fingerprint) {
                $template->fingerprint = $fingerprint;
                $template->save();
                $this->info("  ✓ ID #{$template->id} ({$template->zip_name}) — img_count: {$fingerprint['img_count']}, shapes: {$fingerprint['shape_count']}, texts: {$fingerprint['text_count']}");
                $success++;
            } else {
                $this->warn("  ✗ ID #{$template->id} ({$template->zip_name}) — could not extract fingerprint.");
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Done! ✓ {$success} fingerprinted, ✗ {$failed} failed.");

        return Command::SUCCESS;
    }
}
