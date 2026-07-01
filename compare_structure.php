<?php
// Compare z_index order and key fields across all templates
$templates = [
    '5f05d03c-e8c9-48ab-876d-c4bd8299b7df' => 'WORKING-1',
    'e98e8410-0558-4cc8-b9f4-66ed80bb8ac1' => 'WORKING-2',
    'd18a5372-9a7a-4397-9408-4987c1937259' => 'WORKING-3',
    'da7bfd65-0675-4445-9dfe-eab7b2078d54' => 'BROKEN-1',
    '50e71b01-fa59-4672-a4e3-9fef53984aed' => 'BROKEN-2',
];

foreach ($templates as $uuid => $label) {
    $json_files = glob("./uploads/template/$uuid/json/*.json");
    if (empty($json_files)) continue;
    $j = json_decode(file_get_contents($json_files[0]), true);
    
    echo "=== $label ($uuid) ===\n";
    echo "DPI: " . ($j['info']['dpi'] ?? 'NOT_SET') . "\n";
    
    // Layer 0 analysis
    $l0 = $j['layers'][0];
    echo "Layer 0: name={$l0['name']}, z_index={$l0['z_index']}\n";
    echo "  is_background=" . (isset($l0['is_background']) ? $l0['is_background'] : 'NOT_SET') . "\n";
    echo "  is_shape=" . (isset($l0['is_shape']) ? ($l0['is_shape'] ? 'true' : 'false') : 'NOT_SET') . "\n";
    echo "  shapeType=" . ($l0['shapeType'] ?? 'NOT_SET') . "\n";
    echo "  fillEnabled=" . (isset($l0['fillEnabled']) ? ($l0['fillEnabled'] ? 'true' : 'false') : 'NOT_SET') . "\n";
    echo "  fillColor=" . ($l0['fillColor'] ?? 'NOT_SET') . "\n";
    echo "  image_type=" . ($l0['image_type'] ?? 'NOT_SET') . "\n";
    echo "  opacity=" . ($l0['opacity'] ?? 1) . "\n";
    echo "  blendMode=" . ($l0['blendMode'] ?? 'normal') . "\n";
    
    // z_index order
    echo "  Z-order: ";
    $zs = [];
    foreach ($j['layers'] as $l) $zs[] = $l['z_index'];
    echo implode(',', $zs) . "\n";
    
    // Is z_index ascending or descending?
    $asc = true; $desc = true;
    for ($i = 1; $i < count($zs); $i++) {
        if ($zs[$i] < $zs[$i-1]) $asc = false;
        if ($zs[$i] > $zs[$i-1]) $desc = false;
    }
    echo "  Z-order pattern: " . ($asc ? 'ASCENDING' : ($desc ? 'DESCENDING' : 'MIXED')) . "\n";
    
    // Check for is_background field
    $has_bg_flag = false;
    foreach ($j['layers'] as $l) {
        if (isset($l['is_background']) && $l['is_background']) {
            $has_bg_flag = true;
            break;
        }
    }
    echo "  has is_background=1: " . ($has_bg_flag ? 'YES' : 'NO') . "\n";
    
    // Check for is_slot field
    $has_slot = false;
    foreach ($j['layers'] as $l) {
        if (isset($l['is_slot']) && $l['is_slot']) {
            $has_slot = true;
            break;
        }
    }
    echo "  has is_slot=1: " . ($has_slot ? 'YES' : 'NO') . "\n";
    
    // Check for isSmartObject
    $has_smart = false;
    foreach ($j['layers'] as $l) {
        if (isset($l['isSmartObject']) && $l['isSmartObject']) {
            $has_smart = true;
            break;
        }
    }
    echo "  has isSmartObject=1: " . ($has_smart ? 'YES' : 'NO') . "\n";
    
    // Exported from which JSX?
    echo "  Has DPI (PhotoshopExtractorV4): " . (isset($j['info']['dpi']) ? 'YES' : 'NO') . "\n";
    echo "  Has ai_role (ExportAllLayers): ";
    $has_ai = false;
    foreach ($j['layers'] as $l) {
        if (isset($l['ai_role'])) { $has_ai = true; break; }
    }
    echo ($has_ai ? 'YES' : 'NO') . "\n";
    
    echo "\n";
}
