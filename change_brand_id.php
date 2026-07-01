<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

\DB::statement('ALTER TABLE business_products MODIFY brand_id TEXT NULL');
echo "brand_id changed to TEXT.\n";
