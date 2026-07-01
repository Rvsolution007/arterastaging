<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$catId = 5; // Healthcare

$subCats = DB::table('business_sub_category')->where('business_category_id', $catId)->get();

$added = 0;

foreach ($subCats as $sub) {
    $subName = $sub->name;
    $btypes = DB::table('business_types')->where('business_sub_category_id', $sub->id)->get();
    
    if ($btypes->isEmpty()) {
        // Sub category has no business types, check if it has products
        $count = DB::table('business_products')
            ->where('business_category_id', $catId)
            ->where('business_sub_category_id', $sub->id)
            ->whereNull('business_type_id')
            ->count();
            
        if ($count == 0) {
            DB::table('business_products')->insert([
                ['name' => "General $subName Service", 'business_category_id' => $catId, 'business_sub_category_id' => $sub->id, 'business_type_id' => null, 'status' => 1],
                ['name' => "Specialized $subName Consultation", 'business_category_id' => $catId, 'business_sub_category_id' => $sub->id, 'business_type_id' => null, 'status' => 1]
            ]);
            $added += 2;
        }
    } else {
        foreach ($btypes as $bt) {
            $btName = $bt->name;
            $count = DB::table('business_products')
                ->where('business_category_id', $catId)
                ->where('business_sub_category_id', $sub->id)
                ->where('business_type_id', $bt->id)
                ->count();
                
            if ($count == 0) {
                // Determine suffix based on name
                $suffix1 = "Consultation";
                $suffix2 = "Treatment";
                
                if (stripos($btName, 'hospital') !== false) {
                    $suffix1 = "Inpatient Care";
                    $suffix2 = "Outpatient Services (OPD)";
                } elseif (stripos($btName, 'store') !== false || stripos($btName, 'shop') !== false || stripos($btName, 'pharmacy') !== false) {
                    $suffix1 = "Medicines";
                    $suffix2 = "Healthcare Products";
                } elseif (stripos($btName, 'lab') !== false || stripos($btName, 'diagnostic') !== false) {
                    $suffix1 = "Testing Service";
                    $suffix2 = "Home Collection";
                }
                
                DB::table('business_products')->insert([
                    ['name' => "$btName $suffix1", 'business_category_id' => $catId, 'business_sub_category_id' => $sub->id, 'business_type_id' => $bt->id, 'status' => 1],
                    ['name' => "$btName $suffix2", 'business_category_id' => $catId, 'business_sub_category_id' => $sub->id, 'business_type_id' => $bt->id, 'status' => 1]
                ]);
                $added += 2;
            }
        }
    }
}

echo "DONE! Added $added new products to Healthcare.\n";
