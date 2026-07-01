<?php
/**
 * Analyze large PNG files - check transparency, content, and try to compress
 */

$files = [
    './uploads/template/da7bfd65-0675-4445-9dfe-eab7b2078d54/skins/Hiring_103/Layer-1.png',
    './uploads/template/50e71b01-fa59-4672-a4e3-9fef53984aed/skins/Hiring_101/Layer-3.png',
    './uploads/template/50e71b01-fa59-4672-a4e3-9fef53984aed/skins/Hiring_101/Layer-4.png',
];

foreach ($files as $file) {
    echo "=== " . basename($file) . " (in " . basename(dirname(dirname($file))) . ") ===\n";
    echo "  Size: " . round(filesize($file)/1024) . "KB\n";
    
    $img = imagecreatefrompng($file);
    if (!$img) { echo "  CANNOT READ\n\n"; continue; }
    
    $w = imagesx($img);
    $h = imagesy($img);
    echo "  Dimensions: {$w}x{$h}\n";
    
    // Analyze: sample pixels to understand content
    $transparent_count = 0;
    $opaque_count = 0;
    $partial_count = 0;
    $total_samples = 0;
    
    for ($x = 0; $x < $w; $x += 10) {
        for ($y = 0; $y < $h; $y += 10) {
            $rgba = imagecolorat($img, $x, $y);
            $alpha = ($rgba >> 24) & 0x7F;
            $total_samples++;
            if ($alpha == 127) $transparent_count++;
            elseif ($alpha == 0) $opaque_count++;
            else $partial_count++;
        }
    }
    
    $trans_pct = round($transparent_count / $total_samples * 100, 1);
    $opaque_pct = round($opaque_count / $total_samples * 100, 1);
    $partial_pct = round($partial_count / $total_samples * 100, 1);
    
    echo "  Transparent: {$trans_pct}%, Opaque: {$opaque_pct}%, Semi-transparent: {$partial_pct}%\n";
    
    // If mostly transparent, find the bounding box of non-transparent content
    if ($trans_pct > 50) {
        $minX = $w; $minY = $h; $maxX = 0; $maxY = 0;
        for ($x = 0; $x < $w; $x += 2) {
            for ($y = 0; $y < $h; $y += 2) {
                $alpha = (imagecolorat($img, $x, $y) >> 24) & 0x7F;
                if ($alpha < 127) {
                    $minX = min($minX, $x);
                    $minY = min($minY, $y);
                    $maxX = max($maxX, $x);
                    $maxY = max($maxY, $y);
                }
            }
        }
        $contentW = $maxX - $minX + 1;
        $contentH = $maxY - $minY + 1;
        echo "  Content area: ({$minX},{$minY}) to ({$maxX},{$maxY}) = {$contentW}x{$contentH}\n";
        echo "  Content coverage: " . round($contentW * $contentH / ($w * $h) * 100, 1) . "% of canvas\n";
    }
    
    imagedestroy($img);
    echo "\n";
}
