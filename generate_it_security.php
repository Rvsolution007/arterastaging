<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$categories = \App\Models\BusinessCategory::whereIn('id', [16, 39])->get();
$mdContent = "# IT & Software and Security & Safety\n\n";

foreach ($categories as $cat) {
    $mdContent .= "## Category: " . $cat->name . "\n\n";
    $subs = \App\Models\BusinessSubCategory::where('business_category_id', $cat->id)->get();
    
    foreach ($subs as $sub) {
        $mdContent .= "### Sub-Category: " . $sub->name . "\n";
        
        if ($sub->has_business_type) {
            $types = \App\Models\BusinessType::where('business_sub_category_id', $sub->id)->get();
            foreach ($types as $type) {
                $mdContent .= "#### Business Type: " . $type->name . "\n";
                
                $products = \App\Models\BusinessProduct::where('business_type_id', $type->id)->get();
                if ($products->count() == 0) {
                    // Generate products
                    $baseName = str_replace(['Company', 'Provider', 'Agency', 'Store', 'Dealer'], '', $type->name);
                    $baseName = trim($baseName);
                    
                    $newProducts = [
                        $baseName . ' Solutions',
                        'Professional ' . $baseName . ' Services',
                        'Premium ' . $baseName
                    ];
                    
                    foreach ($newProducts as $np) {
                        $prod = \App\Models\BusinessProduct::create([
                            'name' => $np,
                            'business_sub_category_id' => $sub->id,
                            'business_type_id' => $type->id,
                            'status' => 1
                        ]);
                        $products->push($prod);
                    }
                }
                
                $prodNames = $products->pluck('name')->toArray();
                $mdContent .= "- **Products/Services:** " . implode(", ", $prodNames) . "\n";
                $mdContent .= "- **Product Type:** Professional Service / Hardware\n";
                $mdContent .= "- **Brand:** Various Top Brands / Custom Solutions\n\n";
            }
        } else {
            $products = \App\Models\BusinessProduct::where('business_sub_category_id', $sub->id)
                ->whereNull('business_type_id')->get();
                
            if ($products->count() == 0) {
                // Generate products
                $baseName = str_replace(['Company', 'Provider', 'Agency', 'Store', 'Dealer'], '', $sub->name);
                $baseName = trim($baseName);
                
                $newProducts = [
                    $baseName . ' Solutions',
                    'Professional ' . $baseName . ' Services',
                    'Premium ' . $baseName
                ];
                
                foreach ($newProducts as $np) {
                    $prod = \App\Models\BusinessProduct::create([
                        'name' => $np,
                        'business_sub_category_id' => $sub->id,
                        'business_type_id' => null,
                        'status' => 1
                    ]);
                    $products->push($prod);
                }
            }
            
            $prodNames = $products->pluck('name')->toArray();
            $mdContent .= "- **Products/Services:** " . implode(", ", $prodNames) . "\n";
            $mdContent .= "- **Product Type:** Professional Service / Hardware\n";
            $mdContent .= "- **Brand:** Various Top Brands / Custom Solutions\n\n";
        }
    }
}

file_put_contents('IT & Software and Security & Safety.md', $mdContent);
echo "Done. Created markdown file and added missing products.\n";
