<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$poster_frames = \App\Models\PosterMaker::orderBy('id', 'desc')->get();
$frames_list = collect();
foreach ($poster_frames as $pf) {
    $zipName = $pf->zip_name ?? '';
    if (!$zipName) continue;

    $skinsDir = base_path('uploads/template/' . $zipName . '/skins');
    $skinFolder = '';
    $skinFiles = [];
    if (is_dir($skinsDir)) {
        $dirs = array_filter(glob($skinsDir . '/*'), 'is_dir');
        if (!empty($dirs)) {
            $skinFolderPath = reset($dirs);
            $skinFolder = basename($skinFolderPath);
            $skinFiles = glob($skinFolderPath . '/*');
        }
    }

    $jsonDir = base_path('uploads/template/' . $zipName . '/json');
    $config = null;
    if (is_dir($jsonDir)) {
        $jsonFiles = glob($jsonDir . '/*.json');
        if (!empty($jsonFiles)) {
            $config = json_decode(file_get_contents($jsonFiles[0]));
            if ($config && isset($config->layers) && !empty($skinFiles)) {
                foreach ($config->layers as $layer) {
                    if ($layer->type === 'image' && isset($layer->src)) {
                        $layerSrc = basename($layer->src);
                        $found = false;
                        foreach ($skinFiles as $file) {
                            if (basename($file) === $layerSrc) {
                                $found = true;
                                break;
                            }
                        }
                        if (!$found) {
                            $cleanSrc = strtolower(str_replace([' ', '_', '-'], '', $layerSrc));
                            foreach ($skinFiles as $file) {
                                if (strtolower(str_replace([' ', '_', '-'], '', basename($file))) === $cleanSrc) {
                                    $layer->src = dirname($layer->src) . '/' . basename($file);
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    $frames_list->push((object) [
        'id' => 'postermaker_' . $pf->id,
        'config' => $config,
    ]);
}

foreach ($frames_list as $frame) {
    $fConfig = $frame->config ?? null;
    $encoded = htmlspecialchars(json_encode($fConfig), ENT_QUOTES, 'UTF-8');
    echo $frame->id . " -> " . substr($encoded, 0, 50) . "\n";
}
