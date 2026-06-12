<?php
$file = __DIR__.'/SaaS_AI_Implementation_Blueprint.md';
if(file_exists($file)) {
    $c = file_get_contents($file);
    $c = str_ireplace('BrandKit', 'ArtEra Pixel', $c);
    $c = str_replace('brandkit', 'artera_pixel', $c);
    file_put_contents($file, $c);
    echo "Blueprint updated.\n";
} else {
    echo "Blueprint not found.\n";
}

$file2 = __DIR__.'/database/migrations/2026_05_19_000002_update_brandkit_settings_to_artera.php';
if(file_exists($file2)) {
    $c = file_get_contents($file2);
    $c = str_replace(["'BrandKit'", "'brandkit'", "'Brandkit'", "'BRANDKIT'"], ["'ArtEra Pixel'", "'artera_pixel'", "'ArtEra Pixel'", "'ARTERA_PIXEL'"], $c);
    file_put_contents($file2, $c);
    echo "Migration updated.\n";
}
