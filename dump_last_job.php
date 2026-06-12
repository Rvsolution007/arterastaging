<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$job = \App\Models\AiVideoGeneration::orderBy('id', 'desc')->first();
echo "Job Mode: " . $job->mode . "\n";
echo "Start Image: " . ($job->start_image ? 'YES' : 'NO') . "\n";
echo "Prompt: " . $job->user_prompt . "\n";
echo "Expanded: " . $job->expanded_prompt . "\n";
