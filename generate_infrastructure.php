<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$category = \App\Models\BusinessCategory::find(44); // Construction & Infrastructure
$mdContent = "# Construction & Infrastructure Category\n\n";
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
                $baseName = str_replace(['Contractor', 'Contractors', 'Company', 'Builder', 'Builders', 'Service', 'Services'], '', $type->name);
                $baseName = trim($baseName);
                if (empty($baseName)) $baseName = $type->name;
                
                $newProducts = [
                    'Heavy Infrastructure ' . $baseName,
                    'Mega Construction ' . $baseName,
                    'Specialized ' . $baseName . ' Project'
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
            $mdContent .= "- **Product Type:** Infrastructure Project / Heavy Construction\n";
            $mdContent .= "- **Brand:** Top Engineering Firms / Government Contractors\n\n";
        }
    } else {
        $products = \App\Models\BusinessProduct::where('business_sub_category_id', $sub->id)
            ->whereNull('business_type_id')->get();
            
        if ($products->count() == 0) {
            // Generate products
            $baseName = str_replace(['Contractor', 'Contractors', 'Company', 'Builder', 'Builders', 'Service', 'Services'], '', $sub->name);
            $baseName = trim($baseName);
            if (empty($baseName)) $baseName = $sub->name;
            
            $newProducts = [
                'Heavy Infrastructure ' . $baseName,
                'Mega Construction ' . $baseName,
                'Specialized ' . $baseName . ' Project'
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
        $mdContent .= "- **Product Type:** Infrastructure Project / Heavy Construction\n";
        $mdContent .= "- **Brand:** Top Engineering Firms / Government Contractors\n\n";
    }
}

file_put_contents('Construction & Infrastructure.md', $mdContent);
echo "Done. Created markdown file and added {$addedCount} missing products.\n";
