<?php
require __DIR__.'/../bootstrap/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$dir = base_path('public/uploads/template/Frame20260527174253/skins');
if (!is_dir($dir)) {
    $dir = base_path('uploads/template/Frame20260527174253/skins');
}

echo "<h3>Checking Skins Directory: $dir</h3>";
if (is_dir($dir)) {
    $files = glob($dir . '/*');
    echo "Files and folders found:<pre>";
    print_r(array_map('basename', $files));
    echo "</pre>";
    
    foreach ($files as $file) {
        if (is_dir($file)) {
            echo "<h4>Contents of " . basename($file) . ":</h4><pre>";
            print_r(array_map('basename', glob($file . '/*')));
            echo "</pre>";
        }
    }
} else {
    echo "Directory does not exist.";
}
