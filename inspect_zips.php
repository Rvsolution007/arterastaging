<?php
$zips = glob('C:/xampp/htdocs/brandkit/public/uploads/template/*.zip');
foreach (array_slice($zips, 0, 1) as $zipFile) {
    $zip = new ZipArchive;
    if ($zip->open($zipFile) === TRUE) {
        echo "Opened: " . basename($zipFile) . "\n-----------\n";
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (strpos($name, '.json') !== false) {
                echo "\n[JSON FOUND]: $name\n";
                $json = $zip->getFromName($name);
                $data = json_decode($json, true);
                if (isset($data['layers'])) {
                    foreach ($data['layers'] as $l) {
                        if ((isset($l['type']) && $l['type'] === 'image') || strpos(strtolower($l['name'] ?? ''), 'image') !== false) {
                            $w = $l['width'] ?? $l['w'] ?? '?';
                            $h = $l['height'] ?? $l['h'] ?? '?';
                            echo "  Image Layer: " . ($l['name'] ?? 'unnamed') . " Width: " . $w . " Height: " . $h . "\n";
                        }
                    }
                }
            }
        }
        $zip->close();
    } else {
        echo "Failed to open zip: $zipFile\n";
    }
}
