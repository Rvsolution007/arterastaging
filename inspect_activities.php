<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$activities = App\Models\UserActivity::orderBy('id', 'desc')->limit(20)->get();
foreach($activities as $a) {
    echo "ID: " . $a->id . " | Action: " . $a->action . " | Payload: " . json_encode($a->payload) . PHP_EOL;
}
