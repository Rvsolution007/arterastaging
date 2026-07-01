<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$mappings = [
    // Subcategory 985 (Computer Sales)
    985 => [
        'Workstation Dealer',
        'Business Computers'
    ],
    
    // Subcategory 981 (Computer Networking)
    981 => [
        'Office Networking',
        'Campus Networking',
        'Data Networking',
        'Network Equipment Dealer',
        'Routers',
        'Switches',
        'Firewalls',
        'Access Points',
        'Network Racks',
        'Network Accessories',
        'Networking Company'
    ],
    
    // Subcategory 983 (Wifi Solution Provider)
    983 => [
        'Wi-fi Solution Provider', // Note: Check capitalization matching
        'Enterprise Wi-fi',
        'Hotel Wi-fi',
        'Campus Wi-fi',
        'Smart Wi-fi'
    ],
    
    // Subcategory 1529 (Cctv Dealer)
    1529 => [
        'Cctv Company',
        'Residential Cctv',
        'Commercial Cctv',
        'Industrial Cctv',
        'Ip Camera Solutions',
        'Wireless Cctv',
        'Ai Surveillance',
        'Security System Company'
    ],
    
    // Subcategory 986 (Laptop Sales)
    986 => [
        'Business Laptops',
        'Gaming Laptops',
        'Student Laptops',
        'Premium Laptop Store',
        'Refurbished Laptops',
        'Computer Store' // Included here or Computer Sales. Let's map Computer Store to 985. I'll split it.
    ]
];

// Let's add Computer Store to 985
$mappings[985][] = 'Computer Store';

$shiftedCount = 0;
$productUpdatedCount = 0;

foreach ($mappings as $targetSubId => $typeNames) {
    // 1. Enable has_business_type on the target subcategory
    \App\Models\BusinessSubCategory::where('id', $targetSubId)->update(['has_business_type' => 1]);
    
    foreach ($typeNames as $tName) {
        // Find the business type
        // Use LIKE to be case-insensitive just in case
        $type = \App\Models\BusinessType::where('name', 'LIKE', $tName)->first();
        if ($type) {
            // Move the Business Type
            $type->business_sub_category_id = $targetSubId;
            $type->save();
            $shiftedCount++;
            
            // Move its associated products
            $updated = \App\Models\BusinessProduct::where('business_type_id', $type->id)
                ->update(['business_sub_category_id' => $targetSubId]);
                
            $productUpdatedCount += $updated;
        }
    }
}

echo "Shifted {$shiftedCount} Business Types.\n";
echo "Updated SubCategory IDs for {$productUpdatedCount} Products.\n";
