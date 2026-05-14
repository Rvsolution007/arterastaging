<?php
include 'vendor/autoload.php';
$app = include 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
foreach(DB::select('SHOW TABLES') as $table) {
    echo current((array)$table) . "\n";
}
