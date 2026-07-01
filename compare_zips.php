<?php
$frames = [
    'Frame20260527180220' => 'Working',
    'Frame20260527182102' => 'Broken',
    'Frame20260527182035' => 'Broken'
];

foreach ($frames as $frame => $status) {
    echo "====================================\n";
    echo "FRAME: $frame ($status)\n";
    echo "====================================\n";
    
    $path = "c:\\xampp\\htdocs\\Artera\\uploads\\template\\$frame";
    if (!is_dir($path)) {
        echo "Directory not found: $path\n";
        continue;
    }
    
    $skinsDir = "$path/skins";
    if (is_dir($skinsDir)) {
        $subDirs = glob("$skinsDir/*", GLOB_ONLYDIR);
        if (!empty($subDirs)) {
            $skinFolder = $subDirs[0];
            $files = glob("$skinFolder/*");
            $totalSize = 0;
            echo "Skins Folder: " . basename($skinFolder) . "\n";
            echo "Image Files:\n";
            foreach ($files as $file) {
                $size = filesize($file);
                $totalSize += $size;
                $kb = round($size / 1024, 2);
                echo "  - " . basename($file) . " ($kb KB)\n";
            }
            echo "Total Images Size: " . round($totalSize / 1024, 2) . " KB\n";
        }
    }
    
    $jsonDir = "$path/json";
    if (is_dir($jsonDir)) {
        $jsonFiles = glob("$jsonDir/*.json");
        if (!empty($jsonFiles)) {
            $jsonFile = $jsonFiles[0];
            echo "\nJSON File: " . basename($jsonFile) . "\n";
            $data = json_decode(file_get_contents($jsonFile), true);
            $imageCount = 0;
            if (isset($data['layers'])) {
                foreach ($data['layers'] as $layer) {
                    if ($layer['type'] == 'image') {
                        $imageCount++;
                        echo "  - Layer src: " . $layer['src'] . "\n";
                    }
                }
            }
            echo "Total Image Layers in JSON: $imageCount\n";
        }
    }
    echo "\n";
}
