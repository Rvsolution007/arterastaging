<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BusinessFrame;
use App\Models\CategoryPost;
use App\Models\CustomPostFrame;
use App\Models\FestivalsPost;
use App\Models\GeneralPost;
use App\Models\Greeting;
use Illuminate\Support\Facades\Storage;

class ConvertTemplateImagesToWebP extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'templates:convert-webp';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert PNG and JPG template frames to WebP format';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info("Starting image to WebP conversion for templates...");

        $models = [
            BusinessFrame::class,
            CategoryPost::class,
            CustomPostFrame::class,
            FestivalsPost::class,
            GeneralPost::class,
            Greeting::class,
        ];

        foreach ($models as $modelClass) {
            $this->info("Processing {$modelClass}...");
            
            // Get all records where frame_image has .png or .jpg
            $frames = $modelClass::where('frame_image', 'like', '%.png')
                                 ->orWhere('frame_image', 'like', '%.jpg')
                                 ->orWhere('frame_image', 'like', '%.jpeg')
                                 ->get();

            $count = 0;
            foreach ($frames as $frame) {
                if (empty($frame->frame_image)) continue;
                
                $oldRelativePath = $frame->frame_image;
                $oldPath = public_path('uploads/' . $oldRelativePath);
                
                if (file_exists($oldPath)) {
                    $ext = strtolower(pathinfo($oldRelativePath, PATHINFO_EXTENSION));
                    $newRelativePath = substr($oldRelativePath, 0, -(strlen($ext) + 1)) . '.webp';
                    $newPath = public_path('uploads/' . $newRelativePath);
                    
                    $this->line("Converting: {$oldRelativePath} -> {$newRelativePath}");
                    
                    // Convert using GD
                    $img = null;
                    if ($ext === 'png') {
                        $img = @imagecreatefrompng($oldPath);
                        if ($img !== false) {
                            imagepalettetotruecolor($img);
                            imagealphablending($img, true);
                            imagesavealpha($img, true);
                        }
                    } elseif ($ext === 'jpg' || $ext === 'jpeg') {
                        $img = @imagecreatefromjpeg($oldPath);
                    }
                    
                    if ($img !== false && $img !== null) {
                        // Quality 80
                        if (imagewebp($img, $newPath, 80)) {
                            imagedestroy($img);
                            // Only unlink old file if webp was successfully created
                            if (file_exists($newPath)) {
                                @unlink($oldPath);
                                
                                // Update DB
                                $frame->frame_image = $newRelativePath;
                                $frame->save();
                                $count++;
                            }
                        } else {
                            $this->error("Failed to write WebP for {$oldRelativePath}");
                        }
                    } else {
                        $this->error("Failed to read image: {$oldRelativePath}");
                    }
                }
            }
            $this->info("Completed {$modelClass}. Converted {$count} images.");
        }

        $this->info("Done!");
        return 0;
    }
}
