<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$s = new \App\Services\VertexAIService(1);
$ref = new ReflectionMethod($s, 'getAccessToken');
$ref->setAccessible(true);
$token = $ref->invoke($s);

$projectId = \App\Models\AiSetting::getAiSetting('google_cloud_project_id');
$location = \App\Models\AiSetting::getAiSetting('vertex_location') ?: 'us-central1';

$url = "https://{$location}-aiplatform.googleapis.com/v1/projects/{$projectId}/locations/{$location}/publishers/google/models";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $token
]);
$res = curl_exec($ch);
curl_close($ch);

$data = json_decode($res, true);
if (isset($data['models'])) {
    foreach ($data['models'] as $m) {
        if (str_contains($m['name'], 'gemini')) {
            echo basename($m['name']) . "\n";
        }
    }
} else {
    echo $res;
}
