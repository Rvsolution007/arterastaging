<?php

$json = file_get_contents('fa_metadata.json');
$data = json_decode($json, true);

$icons = [];

foreach ($data as $name => $meta) {
    // Determine free styles: we care about 'brands', 'solid', and 'regular'
    $style = 'solid'; // default
    if (in_array('brands', $meta['styles'])) {
        $style = 'brands';
    } elseif (in_array('solid', $meta['styles'])) {
        $style = 'solid';
    } elseif (in_array('regular', $meta['styles'])) {
        $style = 'regular';
    }

    $title = ucwords(str_replace('-', ' ', $name));
    
    // Add extra search terms from meta to the title for better searching
    if (isset($meta['search']) && isset($meta['search']['terms'])) {
        $title .= ' ' . implode(' ', $meta['search']['terms']);
    }
    
    $title = addslashes($title);

    $class = "fa-$style fa-$name";
    
    $icons[] = "{ class: \"$class\", title: \"$title\" }";
}

$js = "const FONT_AWESOME_ICONS = [\n    " . implode(",\n    ", $icons) . "\n];";

file_put_contents('assets/js/font_awesome_library.js', $js);
echo "Generated " . count($icons) . " icons.\n";
