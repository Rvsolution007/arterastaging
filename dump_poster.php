<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo json_encode(DB::table('poster_category')->get(), JSON_PRETTY_PRINT);
