<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$categories = \App\Models\BusinessCategory::whereIn('id', [16, 39])->get();
$mdContent = "\n\n## ADDED FROM PREVIOUSLY SHIFTED ENTITIES\n\n";
$addedCount = 0;

foreach ($categories as $cat) {
    $subs = \App\Models\BusinessSubCategory::where('business_category_id', $cat->id)->get();
    foreach ($subs as $sub) {
        if ($sub->has_business_type) {
            $types = \App\Models\BusinessType::where('business_sub_category_id', $sub->id)->get();
            foreach ($types as $type) {
                if (\App\Models\BusinessProduct::where('business_type_id', $type->id)->count() == 0) {
                    $baseName = str_replace(['Company', 'Provider', 'Dealer', 'Solutions', 'Specialist'], '', $type->name);
                    $baseName = trim($baseName);
                    
                    $newProducts = [
                        $baseName . ' Solutions',
                        'Professional ' . $baseName . ' Services',
                        'Premium ' . $baseName
                    ];
                    
                    $mdContent .= "### Sub-Category: " . $sub->name . "\n";
                    $mdContent .= "#### Business Type: " . $type->name . "\n";
                    $mdContent .= "- **Products/Services:** " . implode(", ", $newProducts) . "\n";
                    $mdContent .= "- **Product Type:** Professional Service / Hardware\n";
                    $mdContent .= "- **Brand:** Various Top Brands / Custom Solutions\n\n";

                    foreach ($newProducts as $np) {
                        \App\Models\BusinessProduct::create([
                            'name' => $np,
                            'business_sub_category_id' => $sub->id,
                            'business_type_id' => $type->id,
                            'status' => 1
                        ]);
                        $addedCount++;
                    }
                }
            }
        } else {
            if (\App\Models\BusinessProduct::where('business_sub_category_id', $sub->id)->whereNull('business_type_id')->count() == 0) {
                $baseName = str_replace(['Company', 'Provider', 'Dealer', 'Solutions', 'Specialist'], '', $sub->name);
                $baseName = trim($baseName);
                
                $newProducts = [
                    $baseName . ' Solutions',
                    'Professional ' . $baseName . ' Services',
                    'Premium ' . $baseName
                ];

                $mdContent .= "### Sub-Category: " . $sub->name . "\n";
                $mdContent .= "- **Products/Services:** " . implode(", ", $newProducts) . "\n";
                $mdContent .= "- **Product Type:** Professional Service / Hardware\n";
                $mdContent .= "- **Brand:** Various Top Brands / Custom Solutions\n\n";

                foreach ($newProducts as $np) {
                    \App\Models\BusinessProduct::create([
                        'name' => $np,
                        'business_sub_category_id' => $sub->id,
                        'business_type_id' => null,
                        'status' => 1
                    ]);
                    $addedCount++;
                }
            }
        }
    }
}

file_put_contents('IT & Software and Security & Safety.md', $mdContent, FILE_APPEND);
echo "Added {$addedCount} products to shifted entities and updated markdown.\n";
