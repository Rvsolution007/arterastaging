<?php
$dirs = ['brandkit_mobile/lib'];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) continue;
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($files as $file) {
        if ($file->isFile() && in_array($file->getExtension(), ['dart'])) {
            $c = file_get_contents($file);
            $nc = str_replace(
                ['festivals_frame', 'festival_frame', 'FestivalsFrame', 'FestivalFrame', 'festivalFrame', 'category_frame', 'CategoryFrame', 'categoryFrame', 'Festivals Frame', 'Festival Frame', 'Category Frame'], 
                ['festivals_post', 'festival_post', 'FestivalsPost', 'FestivalPost', 'festivalPost', 'category_post', 'CategoryPost', 'categoryPost', 'Festivals Post', 'Festival Post', 'Category Post'], 
                $c
            );
            if ($c !== $nc) file_put_contents($file, $nc);
        }
    }
}
echo "Done replacing in flutter.";
