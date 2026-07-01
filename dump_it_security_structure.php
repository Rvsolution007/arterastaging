<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$categories = \App\Models\BusinessCategory::whereIn('id', [16, 39])->get();
$data = [];

foreach ($categories as $cat) {
    $catData = [
        'name' => $cat->name,
        'sub_categories' => []
    ];
    $subs = \App\Models\BusinessSubCategory::where('business_category_id', $cat->id)->get();
    foreach ($subs as $sub) {
        $subData = [
            'name' => $sub->name,
            'id' => $sub->id,
            'has_types' => $sub->has_business_type,
            'types' => []
        ];
        if ($sub->has_business_type) {
            $types = \App\Models\BusinessType::where('business_sub_category_id', $sub->id)->get();
            foreach ($types as $type) {
                $subData['types'][] = [
                    'name' => $type->name,
                    'id' => $type->id
                ];
            }
        }
        $catData['sub_categories'][] = $subData;
    }
    $data[] = $catData;
}
echo json_encode($data);
