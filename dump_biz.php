<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$b = \App\Models\Business::find(25);
if ($b) {
    print_r($b->toArray());
} else {
    echo "Business 25 not found\n";
}
