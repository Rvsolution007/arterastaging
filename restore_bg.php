<?php
/**
 * Restore Layer-0 to pure white for broken templates, then restore JSON
 */

$templates = [
    'da7bfd65-0675-4445-9dfe-eab7b2078d54' => 'Hiring_103',
    '50e71b01-fa59-4672-a4e3-9fef53984aed' => 'Hiring_101',
];

foreach ($templates as $uuid => $skin_name) {
    $skin_folder = "./uploads/template/$uuid/skins/$skin_name";
    $json_file = "./uploads/template/$uuid/json/$skin_name.json";
    
    // Restore Layer-0 to white
    $l0_path = "$skin_folder/Layer-0.png";
    $canvas = imagecreatetruecolor(1080, 1080);
    $white = imagecolorallocate($canvas, 255, 255, 255);
    imagefill($canvas, 0, 0, $white);
    imagepng($canvas, $l0_path, 9);
    imagedestroy($canvas);
    echo "Restored Layer-0 white: $uuid\n";
    
    // For 50e71b01, also restore Gradient-Fill-1, Layer-3, Layer-4 from compress
    // These were palette-reduced but that's OK
    
    // Restore JSON - need to add back the removed layers
    $j = json_decode(file_get_contents($json_file), true);
    echo "  Current layers: " . count($j['layers']) . "\n";
}
