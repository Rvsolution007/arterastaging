<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$catId = 5; // Healthcare

$subCats = DB::table('business_sub_category')->where('business_category_id', $catId)->get();
$data = [];

foreach ($subCats as $sub) {
    $types = DB::table('business_types')->where('business_sub_category_id', $sub->id)->get();
    $btypes = [];
    foreach ($types as $t) {
        $btypes[] = $t->name;
    }
    
    $data[] = [
        'sub_category' => $sub->name,
        'business_types' => $btypes
    ];
}

file_put_contents('healthcare_dump.json', json_encode($data, JSON_PRETTY_PRINT));
echo "Dumped to healthcare_dump.json\n";
