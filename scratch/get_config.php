<?php
include 'vendor/autoload.php';
$app = include 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$row = DB::table('custom_frames')->whereNotNull('template_config')->first();
if ($row) {
    echo $row->template_config;
} else {
    echo "No row found";
}
