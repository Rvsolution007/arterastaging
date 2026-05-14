<?php
include 'vendor/autoload.php';
$app = include 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$row = DB::table('business_custom_frame')->whereNotNull('json_rules')->first();
if ($row) {
    echo $row->json_rules;
} else {
    echo "No row found";
}
