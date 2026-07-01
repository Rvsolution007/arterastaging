<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\BusinessCategory;
use App\Models\BusinessSubCategory;
use App\Models\BusinessType;
use App\Models\BusinessProduct;
use App\Models\ProductType;
use App\Models\Brand;
use Illuminate\Support\Str;

echo "Starting Manufacturing Product Import...\n";

$markdownPath = 'C:\Users\Admim\.gemini\antigravity\brain\149bd6d8-1407-41cf-9b5a-e1f454936a81\Manufacturing.md';
if (!file_exists($markdownPath)) {
    die("Markdown file not found: $markdownPath\n");
}

$content = file_get_contents($markdownPath);
$lines = explode("\n", $content);

$category = BusinessCategory::firstOrCreate(['name' => 'Manufacturing'], ['slug' => Str::slug('Manufacturing')]);
echo "Category: Manufacturing (ID: {$category->id})\n";

$currentSubCategory = null;
$currentBusinessType = null;
$productCount = 0;

foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line)) continue;

    // Detect Sub Category
    if (preg_match('/^## \d+\.\s+(.+)$/', $line, $matches)) {
        $subCatName = trim($matches[1]);
        // Special case for '39–91. Remaining Sub Categories' which is just a header in the middle, but we replaced it. Wait, the regex might catch it if it exists.
        // Let's assume the names are clean.
        $currentSubCategory = BusinessSubCategory::where('business_category_id', $category->id)
                                ->where('name', $subCatName)
                                ->first();
        if (!$currentSubCategory) {
            echo "  [WARNING] Sub Category not found: $subCatName\n";
            $currentSubCategory = BusinessSubCategory::create([
                'business_category_id' => $category->id,
                'name' => $subCatName,
                'slug' => Str::slug($subCatName),
                'has_business_type' => 0
            ]);
        }
        $currentBusinessType = null; // Reset business type when sub category changes
        echo "Processing Sub Category: $subCatName\n";
        continue;
    }

    // Detect Business Type
    if (preg_match('/^### Business Type:\s+(.+)$/', $line, $matches)) {
        $btName = trim($matches[1]);
        if ($currentSubCategory) {
            $currentBusinessType = BusinessType::where('business_sub_category_id', $currentSubCategory->id)
                                    ->where('name', $btName)
                                    ->first();
            if (!$currentBusinessType) {
                echo "  [WARNING] Business Type not found: $btName under $currentSubCategory->name. Creating...\n";
                $currentBusinessType = BusinessType::create([
                    'business_sub_category_id' => $currentSubCategory->id,
                    'name' => $btName,
                    'slug' => Str::slug($btName)
                ]);
            }
            // Ensure sub-category has_business_type is 1
            if ($currentSubCategory->has_business_type != 1) {
                $currentSubCategory->has_business_type = 1;
                $currentSubCategory->save();
            }
            echo "  Processing Business Type: $btName\n";
        }
        continue;
    }

    // Detect Product Row (skipping table headers and separators)
    if (str_starts_with($line, '|') && !str_contains($line, 'Product | Product Type | Popular Brands') && !str_contains($line, '---------')) {
        $parts = explode('|', $line);
        if (count($parts) >= 4) { // | Prod | Type | Brands | -> [ "", "Prod", "Type", "Brands", "" ]
            $prodName = trim($parts[1]);
            $prodTypeName = trim($parts[2]);
            $brandsStr = trim($parts[3]);

            if (empty($prodName) || $prodName === 'Product') continue;

            if (!$currentSubCategory) {
                echo "    [ERROR] No active sub-category for product: $prodName\n";
                continue;
            }

            // Find or Create Product Type
            $productType = ProductType::firstOrCreate(['name' => $prodTypeName], ['slug' => Str::slug($prodTypeName)]);

            // Find or Create Brands
            $brandNames = array_map('trim', explode(',', $brandsStr));
            $brandIds = [];
            foreach ($brandNames as $bName) {
                if (empty($bName)) continue;
                $slug = Str::slug($bName);
                
                // Lookup by slug first to avoid duplicate slug constraint violations
                $brand = Brand::where('slug', $slug)->first();
                if (!$brand) {
                    $brand = Brand::create(['name' => $bName, 'slug' => $slug]);
                }
                $brandIds[] = (string)$brand->id; 
            }

            // Find or Create Product
            $product = BusinessProduct::where('name', $prodName)
                        ->where('business_category_id', $category->id)
                        ->where('business_sub_category_id', $currentSubCategory->id)
                        ->where('business_type_id', $currentBusinessType ? $currentBusinessType->id : null)
                        ->first();

            if (!$product) {
                BusinessProduct::create([
                    'business_category_id' => $category->id,
                    'business_sub_category_id' => $currentSubCategory->id,
                    'business_type_id' => $currentBusinessType ? $currentBusinessType->id : null,
                    'name' => $prodName,
                    'slug' => Str::slug($prodName),
                    'product_type_id' => $productType->id,
                    'brand_id' => $brandIds // This relies on the model having protected $casts = ['brand_id' => 'array'];
                ]);
                $productCount++;
                // echo "    Inserted: $prodName\n";
            } else {
                 // Update existing just in case brands or type changed
                 $product->update([
                     'product_type_id' => $productType->id,
                     'brand_id' => $brandIds
                 ]);
            }
        }
    }
}

echo "Finished! Successfully inserted/processed $productCount new products.\n";

