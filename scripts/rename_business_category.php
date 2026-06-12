<?php
$directories = [
    "C:/xampp/htdocs/Artera/app",
    "C:/xampp/htdocs/Artera/routes",
    "C:/xampp/htdocs/Artera/database",
    "C:/xampp/htdocs/Artera/resources",
    "C:/xampp/htdocs/Artera/brandkit_mobile/lib",
];
$files_to_check = [
    "C:/xampp/htdocs/Artera/arterastaging.sql"
];

$replacements = [
    "business_category_post" => "category_post",
    "Business Category Post" => "Category Post",
    "business category post" => "category post",
    "Business Category Posts" => "Category Posts",
    "business category posts" => "category posts"
];

function processFile($filePath, $replacements) {
    if (!is_file($filePath)) return;
    $content = file_get_contents($filePath);
    $newContent = $content;
    
    foreach ($replacements as $search => $replace) {
        $newContent = str_replace($search, $replace, $newContent);
    }
    
    if ($content !== $newContent) {
        file_put_contents($filePath, $newContent);
        echo "Updated: $filePath\n";
    }
}

foreach ($directories as $dir) {
    if (!is_dir($dir)) continue;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $ext = pathinfo($file->getFilename(), PATHINFO_EXTENSION);
            if (in_array($ext, ["php", "dart", "html", "blade.php"])) {
                processFile($file->getPathname(), $replacements);
            }
        }
    }
}

foreach ($files_to_check as $file) {
    processFile($file, $replacements);
}
echo "Done.\n";
?>
