<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$categories = \App\Models\BusinessCategory::whereIn('id', [16, 39])->get();
foreach ($categories as $cat) {
    $subs = \App\Models\BusinessSubCategory::where('business_category_id', $cat->id)->get();
    foreach ($subs as $sub) {
        if ($sub->has_business_type) {
            $types = \App\Models\BusinessType::where('business_sub_category_id', $sub->id)->get();
            foreach ($types as $type) {
                if (\App\Models\BusinessProduct::where('business_type_id', $type->id)->count() == 0) {
                    echo $type->name . "\n";
                }
            }
        } else {
            if (\App\Models\BusinessProduct::where('business_sub_category_id', $sub->id)->whereNull('business_type_id')->count() == 0) {
                echo $sub->name . "\n";
            }
        }
    }
}
