<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "<h3>Staging DB Search for Frame20260527174253</h3>";
$entries = \DB::table('poster_maker')->where('zip_name', 'like', '%Frame20260527174253%')->get();
echo "Found " . count($entries) . " entries in poster_makers:<pre>";
print_r($entries->toArray());
echo "</pre>";

$dir = base_path('public/uploads/template/Frame20260527174253/skins');
if (!is_dir($dir)) {
    $dir = base_path('uploads/template/Frame20260527174253/skins');
}
echo "<h3>Checking Skins Directory on Server: $dir</h3>";
if (is_dir($dir)) {
    $files = glob($dir . '/*');
    echo "Folders inside skins:<pre>";
    print_r(array_map('basename', $files));
    echo "</pre>";
} else {
    echo "Directory does not exist.";
}
