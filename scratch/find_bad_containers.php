<?php
$dir = 'c:/xampp/htdocs/brandkit/brandkit_mobile/lib';
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
$dartFiles = new RegexIterator($files, '/\.dart$/');

foreach ($dartFiles as $file) {
    $content = file_get_contents($file->getPathname());
    // Match Container(...) with both color and decoration
    // Note: This is a simple regex and might miss some complex cases or have false positives, but it's a good start.
    if (preg_match('/Container\s*\(\s*[^)]*color\s*:\s*[^,)]+[^)]*decoration\s*:\s*/s', $content) ||
        preg_match('/Container\s*\(\s*[^)]*decoration\s*:\s*[^,)]+[^)]*color\s*:\s*/s', $content)) {
        echo "Potential issue in: " . $file->getPathname() . "\n";
    }
}
