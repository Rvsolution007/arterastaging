<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$retailCategory = \App\Models\BusinessCategory::where('name', 'LIKE', '%Retail%')->first();
$subs = \App\Models\BusinessSubCategory::where('business_category_id', $retailCategory->id)->get();
$emptyItems = [];

foreach ($subs as $sub) {
    if ($sub->has_business_type) {
        $types = \App\Models\BusinessType::where('business_sub_category_id', $sub->id)->get();
        foreach ($types as $type) {
            $count = \App\Models\BusinessProduct::where('business_type_id', $type->id)->count();
            if ($count == 0) {
                $emptyItems[] = $sub->name . " -> " . $type->name;
            }
        }
    } else {
        $count = \App\Models\BusinessProduct::where('business_sub_category_id', $sub->id)
            ->whereNull('business_type_id')
            ->count();
        if ($count == 0) {
            $emptyItems[] = $sub->name;
        }
    }
}
echo json_encode($emptyItems);
