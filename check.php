<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$frame = \App\Models\PosterMaker::find(115);
if($frame) { 
    $zipPath = public_path('editor/templates/' . str_replace('.zip', '', $frame->zip_name) . '.zip');
    echo "ZIP: $zipPath\n";
    $dir = public_path('editor/templates/' . basename($frame->post_thumb));
    echo "Thumb dir: " . dirname($dir) . "\n";
    $jsonPath = dirname($dir) . '/document.json';
    if (file_exists($jsonPath)) {
        $json = file_get_contents($jsonPath);
        $data = json_decode($json, true);
        foreach($data['layers'] as $l) {
            echo "Layer: " . $l['name'] . " - type: " . $l['type'] . "\n";
        }
    }
}
