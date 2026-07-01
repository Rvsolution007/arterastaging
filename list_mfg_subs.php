<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$cat = App\Models\BusinessCategory::where('name', 'Manufacturing')->first();
$subs = App\Models\BusinessSubCategory::where('business_category_id', $cat->id)->orderBy('name')->pluck('name')->toArray();
echo json_encode($subs);
