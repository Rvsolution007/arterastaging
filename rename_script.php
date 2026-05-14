<?php
$dirs = ['app', 'resources/views', 'routes'];
foreach ($dirs as $dir) {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($files as $file) {
        if ($file->isFile() && in_array($file->getExtension(), ['php', 'dart'])) {
            $c = file_get_contents($file);
            $nc = str_replace(
                ['festival_frame', 'FestivalFrame', 'festivalFrame', 'category_frame', 'CategoryFrame', 'categoryFrame'], 
                ['festival_post', 'FestivalPost', 'festivalPost', 'category_post', 'CategoryPost', 'categoryPost'], 
                $c
            );
            if ($c !== $nc) file_put_contents($file, $nc);
        }
    }
}
echo "Done replacing.";
