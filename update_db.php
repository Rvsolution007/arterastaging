<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$frame = \App\Models\BusinessCustomFrame::where('zip_file_path', 'like', '%e8912c49-c06b-4f28-b03f-8ca763d0e3e7%')->first();
if($frame) {
    $json = json_decode($frame->json_rules, true);
    foreach($json['layers'] as &$layer) {
        if($layer['name'] === 'image') {
            $layer['w'] = 663;
            $layer['h'] = 663;
        }
    }
    $frame->json_rules = json_encode($json);
    $frame->save();
    echo 'Database JSON updated successfully!';
} else {
    echo 'Frame not found';
}
