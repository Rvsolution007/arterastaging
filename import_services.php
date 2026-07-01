<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$mdContent = file_get_contents('C:\Users\Admim\.gemini\antigravity\brain\149bd6d8-1407-41cf-9b5a-e1f454936a81\Healthcare.md');
$lines = explode("\n", $mdContent);

$currentCatId = 5; // Healthcare
$currentSubCatId = null;
$currentBtypeId = null;

$addedProducts = 0;
$addedMappings = 0;

foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line)) continue;

    // Detect Sub Category: ## 1. Ac Repair
    if (preg_match('/^##\s+\d+\.\s+(.+)$/', $line, $matches)) {
        $subCatName = trim($matches[1]);
        $subCat = DB::table('business_sub_category')->where('business_category_id', $currentCatId)->where('name', $subCatName)->first();
        if ($subCat) {
            $currentSubCatId = $subCat->id;
            $currentBtypeId = null;
        } else {
            echo "WARNING: Sub Category not found: $subCatName\n";
            $currentSubCatId = null;
        }
        continue;
    }

    // Detect Business Type: **Business Type: Telecalling**
    if (preg_match('/^\*\*Business Type:\s+(.+)\*\*$/', $line, $matches)) {
        $bTypeName = trim($matches[1]);
        if ($currentSubCatId) {
            $bType = DB::table('business_types')->where('business_sub_category_id', $currentSubCatId)->where('name', $bTypeName)->first();
            if ($bType) {
                $currentBtypeId = $bType->id;
            } else {
                echo "WARNING: Business Type not found: $bTypeName (under SubCat $currentSubCatId)\n";
                $currentBtypeId = null;
            }
        }
        continue;
    }

    // Detect No Business Type: *(No Business Type)*
    if (preg_match('/^\*\(No Business Type\)\*$/', $line)) {
        $currentBtypeId = null;
        continue;
    }

    // Parse Table Row: | AC Regular Servicing | Service | Urban Company, OnSiteGo |
    if (preg_match('/^\|(.*)\|(.*)\|(.*)\|$/', $line, $matches)) {
        $productName = trim($matches[1]);
        $productTypeStr = trim($matches[2]);
        $brandsStr = trim($matches[3]);

        // Skip headers and separators
        if ($productName === 'Service' || strpos($productName, '---') !== false) {
            continue;
        }

        if (!$currentSubCatId) {
            continue; // Skip if we don't have a valid sub-category
        }

        // Check if product exists in this exact hierarchy
        $query = DB::table('business_products')
            ->where('name', $productName)
            ->where('business_category_id', $currentCatId)
            ->where('business_sub_category_id', $currentSubCatId);
            
        if ($currentBtypeId) {
            $query->where('business_type_id', $currentBtypeId);
        } else {
            $query->whereNull('business_type_id');
        }
        
        $exists = $query->exists();

        if (!$exists) {
            DB::table('business_products')->insert([
                'name' => $productName,
                'business_category_id' => $currentCatId,
                'business_sub_category_id' => $currentSubCatId,
                'business_type_id' => $currentBtypeId,
                'status' => 1
            ]);
            $addedProducts++;
        }
    }
}

echo "DONE! Added $addedProducts new products.\n";
