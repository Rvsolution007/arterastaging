<?php

namespace App\Traits;

use App\Models\AppSetting;
use App\Models\StorageSetting;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

trait WatermarkImageTrait
{
    public function applyWatermark($originalFilename, $diskType = null)
    {
        if ($diskType === null) {
            $diskType = StorageSetting::getStorageSetting("storage");
        }

        try {
            $manager = new ImageManager(new Driver());

            if ($diskType == 'DigitalOcean') {
                if (!Storage::disk('spaces')->exists('uploads/' . $originalFilename)) {
                    return;
                }
                $originalData = Storage::disk('spaces')->get('uploads/' . $originalFilename);
                $image = $manager->read($originalData);
            } else {
                $originalPath = public_path('uploads/' . $originalFilename);
                if (!file_exists($originalPath)) {
                    return;
                }
                $image = $manager->read($originalPath);
            }

            // Calculate Aspect Ratio
            $imageWidth = $image->width();
            $imageHeight = $image->height();
            $ratio = $imageWidth / $imageHeight;
            
            // Determine Watermark Key
            $watermarkImageKey = 'seo_watermark_image_1_1'; // Default square
            
            if ($ratio >= 1.2) { 
                $watermarkImageKey = 'seo_watermark_image_16_9'; // Landscape
            } else if ($ratio <= 0.8) { 
                $watermarkImageKey = 'seo_watermark_image_9_16'; // Portrait
            }
            
            $watermarkImage = AppSetting::getAppSetting($watermarkImageKey);
            
            // Fallbacks
            if (empty($watermarkImage)) {
                $watermarkImage = AppSetting::getAppSetting('seo_watermark_image_1_1'); // Fallback to 1:1 if specific size missing
            }
            if (empty($watermarkImage)) {
                $watermarkImage = AppSetting::getAppSetting('seo_watermark_image'); // Fallback to legacy
            }
            if (empty($watermarkImage)) {
                return; // No watermark configured
            }

            // Load Watermark
            $watermarkPath = public_path('uploads/' . $watermarkImage);
            
            if ($diskType == 'DigitalOcean') {
                if (!Storage::disk('spaces')->exists('uploads/' . $watermarkImage)) {
                    return;
                }
                $watermarkData = Storage::disk('spaces')->get('uploads/' . $watermarkImage);
            } else {
                if (!file_exists($watermarkPath)) {
                    return;
                }
                $watermarkData = $watermarkPath;
            }

            $watermark = $manager->read($watermarkData);

            // Resize watermark (40% of original image width)
            $watermarkWidth = intval($imageWidth * 0.4); 
            $watermark->scale(width: $watermarkWidth);

            // Place watermark in the center with 50% opacity
            $image->place($watermark, 'center', 0, 0, 50);

            $watermarkedFilename = 'watermarked_' . $originalFilename;

            $ext = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
            if ($ext == 'png') {
                $encoded = $image->toPng();
            } else if ($ext == 'webp') {
                $encoded = $image->toWebp(80);
            } else {
                $encoded = $image->toJpeg(80);
            }

            if ($diskType == 'DigitalOcean') {
                Storage::disk('spaces')->put('uploads/' . $watermarkedFilename, (string) $encoded, 'public');
            } else {
                file_put_contents(public_path('uploads/' . $watermarkedFilename), (string) $encoded);
            }

        } catch (\Exception $e) {
            \Log::error("Watermark Trait Error: " . $e->getMessage());
        }
    }
}
