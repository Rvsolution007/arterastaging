<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$category = \App\Models\BusinessCategory::find(23); // Sports & Fitness
$mdContent = "# Sports & Fitness Category\n\n";
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
                $baseName = str_replace(['Store', 'Center', 'Academy', 'Club', 'Trainer', 'Dealer'], '', $type->name);
                $baseName = trim($baseName);
                if (empty($baseName)) $baseName = $type->name;
                
                $newProducts = [
                    'Premium ' . $baseName . ' Gear',
                    'Professional ' . $baseName . ' Service',
                    $baseName . ' Membership'
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
            $mdContent .= "- **Product Type:** Sports Equipment / Fitness Service\n";
            $mdContent .= "- **Brand:** Top Sports Brands / Certified Centers\n\n";
        }
    } else {
        $products = \App\Models\BusinessProduct::where('business_sub_category_id', $sub->id)
            ->whereNull('business_type_id')->get();
            
        if ($products->count() == 0) {
            // Generate products
            $baseName = str_replace(['Store', 'Center', 'Academy', 'Club', 'Trainer', 'Dealer'], '', $sub->name);
            $baseName = trim($baseName);
            if (empty($baseName)) $baseName = $sub->name;
            
            $newProducts = [
                'Premium ' . $baseName . ' Gear',
                'Professional ' . $baseName . ' Service',
                $baseName . ' Membership'
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
        $mdContent .= "- **Product Type:** Sports Equipment / Fitness Service\n";
        $mdContent .= "- **Brand:** Top Sports Brands / Certified Centers\n\n";
    }
}

file_put_contents('Sports & Fitness.md', $mdContent);
echo "Done. Created markdown file and added {$addedCount} missing products.\n";
