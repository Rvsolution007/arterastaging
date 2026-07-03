<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\FestivalsPost;
use App\Models\CategoryPost;
use App\Models\CustomPostFrame;
use App\Models\AppSetting;
use App\Models\StorageSetting;
use Illuminate\Support\Facades\Storage;
use App\Traits\WatermarkImageTrait;

class ApplyWatermarks extends Command
{
    use WatermarkImageTrait;

    protected $signature = 'watermark:apply';

    protected $description = 'Apply watermark to all existing templates (Festivals, Categories, Custom)';

    public function handle()
    {
        ini_set('memory_limit', '-1'); // Prevent Out of Memory error during heavy image processing
        
        $watermarkImage = AppSetting::getAppSetting('seo_watermark_image');
        
        if (empty($watermarkImage)) {
            $this->error('No SEO Watermark Logo is set in Admin Settings. Please upload it first.');
            return;
        }

        $this->info('Starting Watermark Application to Existing Templates...');
        
        $diskType = StorageSetting::getStorageSetting("storage");

        // 1. Process Festival Posts
        $festivals = FestivalsPost::whereNotNull('frame_image')->get();
        $this->info("Processing {$festivals->count()} Festival Templates...");
        $this->processCollection($festivals, $diskType);

        // 2. Process Category Posts
        $categories = CategoryPost::whereNotNull('frame_image')->get();
        $this->info("Processing {$categories->count()} Category Templates...");
        $this->processCollection($categories, $diskType);

        // 3. Process Custom Posts
        $customs = CustomPostFrame::whereNotNull('frame_image')->get();
        $this->info("Processing {$customs->count()} Custom Templates...");
        $this->processCollection($customs, $diskType);

        $this->info('Watermark application complete!');
    }

    private function processCollection($items, $diskType)
    {
        $bar = $this->output->createProgressBar(count($items));
        $bar->start();

        foreach ($items as $item) {
            $filename = $item->frame_image;
            $watermarkedFilename = 'watermarked_' . $filename;

            // Check if watermarked version already exists
            $exists = false;
            if ($diskType == 'DigitalOcean') {
                $exists = Storage::disk('spaces')->exists('uploads/' . $watermarkedFilename);
            } else {
                $exists = file_exists(public_path('uploads/' . $watermarkedFilename));
            }

            if (!$exists) {
                $this->applyWatermark($filename, $diskType);
                gc_collect_cycles(); // Force garbage collection to free image memory
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }
}
