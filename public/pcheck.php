<?php
// We need to bootstrap Laravel to use public_path() helper
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

header('Content-Type: text/plain');
echo "=== File Permissions Diagnostic ===\n";

$paths = [
    'uploads',
    'uploads/template',
    'uploads/custom_frames_zips',
    'uploads/33e746ef-a43a-4c7d-bca4-0557fa3b90b6.png'
];

foreach ($paths as $path) {
    $fullPath = public_path($path);
    echo "\nPath: " . $path . "\n";
    echo "Full Path: " . $fullPath . "\n";
    echo "Exists: " . (file_exists($fullPath) ? 'YES' : 'NO') . "\n";
    if (file_exists($fullPath)) {
        echo "Is Dir: " . (is_dir($fullPath) ? 'YES' : 'NO') . "\n";
        echo "Is Readable: " . (is_readable($fullPath) ? 'YES' : 'NO') . "\n";
        echo "Is Writable: " . (is_writable($fullPath) ? 'YES' : 'NO') . "\n";
        echo "Perms: " . substr(sprintf('%o', fileperms($fullPath)), -4) . "\n";
        if (function_exists('posix_getpwuid')) {
            $owner = posix_getpwuid(fileowner($fullPath));
            $group = posix_getgrgid(filegroup($fullPath));
            echo "Owner: " . ($owner ? $owner['name'] : 'unknown') . " (" . fileowner($fullPath) . ")\n";
            echo "Group: " . ($group ? $group['name'] : 'unknown') . " (" . filegroup($fullPath) . ")\n";
        }
    }
}

echo "\n=== PHP Process Info ===\n";
if (function_exists('posix_getpwuid') && function_exists('posix_geteuid')) {
    $processUser = posix_getpwuid(posix_geteuid());
    echo "Process User: " . $processUser['name'] . "\n";
} else {
    echo "Process User: " . get_current_user() . "\n";
}

// Check for .htaccess in public/uploads
$htaccess = public_path('uploads/.htaccess');
echo "\nuploads/.htaccess exists: " . (file_exists($htaccess) ? 'YES' : 'NO') . "\n";
if (file_exists($htaccess)) {
    echo "Content:\n" . file_get_contents($htaccess) . "\n";
}
