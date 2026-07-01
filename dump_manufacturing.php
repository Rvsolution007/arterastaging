<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\BusinessCategory;
use App\Models\BusinessSubCategory;
use App\Models\BusinessType;
use App\Models\BusinessProduct;
use App\Models\ProductType;
use App\Models\Brand;

$cat = BusinessCategory::where('name', 'Manufacturing')->first();
echo "Manufacturing Category ID: " . $cat->id . "\n\n";

$subs = BusinessSubCategory::where('business_category_id', $cat->id)->orderBy('name')->get();

foreach ($subs as $sub) {
    $types = BusinessType::where('business_sub_category_id', $sub->id)->get();
    $prods = BusinessProduct::where('business_sub_category_id', $sub->id)->get();
    
    echo "SUB: " . $sub->name . " (ID:" . $sub->id . ") | Types:" . $types->count() . " | Products:" . $prods->count() . "\n";
    
    foreach ($types as $type) {
        $typeProds = BusinessProduct::where('business_type_id', $type->id)->count();
        echo "  TYPE: " . $type->name . " (ID:" . $type->id . ") | Products:" . $typeProds . "\n";
    }
    
    if ($prods->count() > 0 && $prods->count() <= 20) {
        foreach ($prods as $prod) {
            $ptName = $prod->productType ? $prod->productType->name : 'N/A';
            $brandNames = [];
            if (is_array($prod->brand_id)) {
                $brands = Brand::whereIn('id', $prod->brand_id)->pluck('name')->toArray();
                $brandNames = $brands;
            }
            echo "    PROD: " . $prod->name . " | PT: " . $ptName . " | Brands: " . implode(', ', $brandNames) . "\n";
        }
    }
    echo "\n";
}

// Also list all Product Types
echo "\n=== ALL PRODUCT TYPES ===\n";
$allPT = ProductType::orderBy('name')->get();
foreach ($allPT as $pt) {
    echo "PT: " . $pt->name . " (ID:" . $pt->id . ")\n";
}

echo "\n=== ALL BRANDS (first 50) ===\n";
$allBrands = Brand::orderBy('name')->limit(50)->get();
foreach ($allBrands as $b) {
    echo "BRAND: " . $b->name . " (ID:" . $b->id . ")\n";
}
echo "Total brands: " . Brand::count() . "\n";
