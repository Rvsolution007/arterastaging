<?php

namespace App\Traits;

use App\Models\AppSetting;
use App\Models\StorageSetting;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Modifiers\OpacityModifier;

trait WatermarkImageTrait
{
    public function applyWatermark($originalFilename, $diskType = null)
    {
        $watermarkImage = AppSetting::getAppSetting('seo_watermark_image');
        
        // If no watermark logo is set, we skip
        if (empty($watermarkImage)) {
            return;
        }

        if ($diskType === null) {
            $diskType = StorageSetting::getStorageSetting("storage");
        }

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

        try {
            $manager = new ImageManager(new Driver());
            $watermark = $manager->read($watermarkData);

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

            // Resize watermark (40% of original image width)
            $watermarkWidth = intval($image->width() * 0.4); 
            $watermark->scale(width: $watermarkWidth);
            
            // Set opacity to 50%
            $watermark->modify(new OpacityModifier(50));

            // Place watermark in the center
            $image->place($watermark, 'center');

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
