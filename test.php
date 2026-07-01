<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$srv = new \App\Services\CatalogueAIService(18);
$cols = \App\Models\CatalogueCustomColumn::where('user_id', 18)->get()->toArray();
$ref = new ReflectionMethod($srv, 'getProductExtractionPrompt');
$ref->setAccessible(true);
echo $ref->invoke($srv, $cols);
