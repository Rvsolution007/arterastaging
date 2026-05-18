<?php
require __DIR__."/vendor/autoload.php";
$app = require_once __DIR__."/bootstrap/app.php";
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AppTranslation;

$newEn = [
    "ai_generated_content" => "AI Generated Content", "no_custom_posts_available" => "No custom posts available"
];

$newHi = [
    "ai_generated_content" => "AI जनित सामग्री", "no_custom_posts_available" => "कोई कस्टम पोस्ट उपलब्ध नहीं"
];

$newGu = [
    "ai_generated_content" => "AI જનરેટેડ સામગ્રી", "no_custom_posts_available" => "કોઈ કસ્ટમ પોસ્ટ ઉપલબ્ધ નથી"
];

$newMr = [
    "ai_generated_content" => "AI व्युत्पन्न सामग्री", "no_custom_posts_available" => "कोणतीही कस्टम पोस्ट उपलब्ध नाही"
];

$langs = ["en" => $newEn, "hi" => $newHi, "gu" => $newGu, "mr" => $newMr];

foreach($langs as $code => $newDict) {
    $record = AppTranslation::where("language_code", $code)->first();
    if($record) {
        $existing = is_array($record->translations) ? $record->translations : json_decode($record->translations, true);
        if(!$existing) $existing = [];
        $merged = array_merge($existing, $newDict);
        $record->translations = $merged;
        $record->save();
        echo "Updated $code\n";
    }
}
echo "Done.\n";

