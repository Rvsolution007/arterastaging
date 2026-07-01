<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$category = \App\Models\BusinessCategory::find(31); // Import & Export
$mdContent = "# Import & Export Category\n\n";
$addedCount = 0;

$mdContent .= "## Category: " . $category->name . "\n\n";
$subs = \App\Models\BusinessSubCategory::where('business_category_id', $category->id)->get();

foreach ($subs as $sub) {
    $mdContent .= "### Sub-Category: " . $sub->name . "\n";
    
    if ($sub->has_business_type) {
        $types = \App\Models\BusinessType::where('business_sub_category_id', $sub->id)->get();
        foreach ($types as $type) {
            $mdContent .= "#### Business Type: " . $type->name . "\n";
            
            $products = \App\Models\BusinessProduct::where('business_type_id', $type->id)->get();
            if ($products->count() == 0) {
                // Generate products
                $baseName = str_replace(['Service', 'Services', 'Agent', 'Agents', 'Broker', 'Company', 'House', 'Firm'], '', $type->name);
                $baseName = trim($baseName);
                if (empty($baseName)) $baseName = $type->name;
                
                $newProducts = [
                    'Global ' . $baseName . ' Service',
                    'Premium ' . $baseName . ' Trade',
                    'International ' . $baseName
                ];
                
                foreach ($newProducts as $np) {
                    $prod = \App\Models\BusinessProduct::create([
                        'name' => $np,
                        'business_category_id' => $category->id,
                        'business_sub_category_id' => $sub->id,
                        'business_type_id' => $type->id,
                        'status' => 1
                    ]);
                    $products->push($prod);
                    $addedCount++;
                }
            }
            
            $prodNames = $products->pluck('name')->toArray();
            $mdContent .= "- **Products/Services:** " . implode(", ", $prodNames) . "\n";
            $mdContent .= "- **Product Type:** Trade Service / Import-Export Product\n";
            $mdContent .= "- **Brand:** Certified Traders / International Agents\n\n";
        }
    } else {
        $products = \App\Models\BusinessProduct::where('business_sub_category_id', $sub->id)
            ->whereNull('business_type_id')->get();
            
        if ($products->count() == 0) {
            // Generate products
            $baseName = str_replace(['Service', 'Services', 'Agent', 'Agents', 'Broker', 'Company', 'House', 'Firm'], '', $sub->name);
            $baseName = trim($baseName);
            if (empty($baseName)) $baseName = $sub->name;
            
            $newProducts = [
                'Global ' . $baseName . ' Service',
                'Premium ' . $baseName . ' Trade',
                'International ' . $baseName
            ];
            
            foreach ($newProducts as $np) {
                $prod = \App\Models\BusinessProduct::create([
                    'name' => $np,
                    'business_category_id' => $category->id,
                    'business_sub_category_id' => $sub->id,
                    'business_type_id' => null,
                    'status' => 1
                ]);
                $products->push($prod);
                $addedCount++;
            }
        }
        
        $prodNames = $products->pluck('name')->toArray();
        $mdContent .= "- **Products/Services:** " . implode(", ", $prodNames) . "\n";
        $mdContent .= "- **Product Type:** Trade Service / Import-Export Product\n";
        $mdContent .= "- **Brand:** Certified Traders / International Agents\n\n";
    }
}

file_put_contents('Import & Export.md', $mdContent);
echo "Done. Created markdown file and added {$addedCount} missing products.\n";
