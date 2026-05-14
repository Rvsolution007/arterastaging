<?php
$dirs = ['resources/views', 'app/Http/Controllers', 'routes', 'brandkit_mobile/lib'];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) continue;
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($files as $file) {
        if ($file->isFile() && in_array($file->getExtension(), ['php', 'blade.php', 'dart'])) {
            $c = file_get_contents($file);
            $nc = str_replace(
                ['festivals-frame', 'category-frame'], 
                ['festivals-post', 'category-post'], 
                $c
            );
            if ($c !== $nc) file_put_contents($file, $nc);
        }
    }
}
echo "Done replacing hyphens.";
