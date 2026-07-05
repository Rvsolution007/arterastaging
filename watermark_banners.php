<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AppSetting;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

// Get watermark from database
$watermarkImage = AppSetting::getAppSetting('seo_watermark_image_1_1');
if (empty($watermarkImage)) {
    $watermarkImage = AppSetting::getAppSetting('seo_watermark_image');
}

if (empty($watermarkImage)) {
    echo "Error: No SEO Watermark uploaded in Admin Settings!\n";
    exit(1);
}

$watermarkPath = public_path('uploads/' . $watermarkImage);
if (!file_exists($watermarkPath)) {
    echo "Error: Watermark file not found at $watermarkPath\n";
    exit(1);
}

echo "Found watermark: $watermarkPath\n";

$manager = new ImageManager(new Driver());
$watermark = $manager->read($watermarkPath);

$baseDest = 'C:\xampp\htdocs\Artera\assets\images\banner_masonry';
$cols = ['col1', 'col2', 'col3'];

foreach ($cols as $col) {
    $dir = $baseDest . '/' . $col;
    $files = glob($dir . '/*.webp');
    
    foreach ($files as $file) {
        // We only watermark if it doesn't have the watermark already, or we just overwrite.
        echo "Watermarking $file...\n";
        
        $image = $manager->read($file);
        $imageWidth = $image->width();
        $imageHeight = $image->height();
        
        // Resize watermark to fit the image
        $wmClone = clone $watermark;
        $wmClone->resize($imageWidth, $imageHeight);
        
        // Place watermark
        $image->place($wmClone, 'center', 0, 0, 100);
        
        // Save back to webp
        $encoded = $image->toWebp(75);
        file_put_contents($file, (string) $encoded);
    }
}

echo "Watermarking complete!\n";
