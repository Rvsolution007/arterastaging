<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo json_encode(DB::table('business')->where('name', 'RV SOLUTIONS')->first(), JSON_PRETTY_PRINT);
