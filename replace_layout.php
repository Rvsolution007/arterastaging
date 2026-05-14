<?php
$f = 'resources/views/layouts/app.blade.php';
$c = file_get_contents($f);
$c = str_replace(
    ['festivals-frame', 'category-frame', 'Festival Frame', 'Category Frame', 'FestivalFrame', 'CategoryFrame'],
    ['festivals-post', 'category-post', 'Festival Post', 'Category Post', 'FestivalPost', 'CategoryPost'],
    $c
);
file_put_contents($f, $c);
echo "Layout replaced.\n";
