<?php
/**
 * ANTIGRAVITY MASTER DEDUPLICATION SCRIPT
 * 
 * Logic: For each duplicate name, keep the one under the BEST-FIT parent category.
 * Rules:
 *   1. Specialist category wins over generic ("Services", "Retail", "Others")
 *   2. Industry-specific > generic catch-all
 *   3. "Retail" only for physical product stores
 *   4. "Others" is always the worst choice
 *   5. "Automobile" merges into "Automotive"
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\BusinessCategory;
use App\Models\BusinessSubCategory;
use App\Models\BusinessType;
use App\Models\BusinessProduct;
use Illuminate\Support\Facades\DB;

// ===========================
// CATEGORY PRIORITY MAP
// Lower number = BETTER fit (higher priority)
// ===========================
$categoryPriority = [
    // Specialist categories (BEST)
    'Healthcare' => 1,
    'Food & Beverage' => 1,
    'Education' => 1,
    'Beauty & Wellness' => 1,
    'Fashion & Apparel' => 1,
    'IT & Software' => 1,
    'Advertising & Marketing' => 1,
    'Event & Wedding Services' => 1,
    'Pet Care & Animal Services' => 1,
    'Sports & Fitness' => 1,
    'Religious & Spiritual' => 1,
    'Baby & Kids' => 1,
    'Printing & Packaging' => 1,
    'Logistics & Transportation' => 1,
    'Import & Export' => 1,
    'Telecom & Internet' => 1,
    'Security & Safety' => 1,
    'Energy & Renewable' => 1,
    'Ngo & Non-profit' => 1,
    'Books Stationery & Office Supplies' => 1,
    'Arts Crafts & Gifts' => 1,
    'Agriculture & Farming' => 1,
    'E-commerce & Online Business' => 1,
    'Government & Public Sector' => 1,
    
    // Strong specialist
    'Home Improvement' => 2,
    'Home Decor & Furnishing' => 2,
    'Electronics & Electrical' => 2,
    'Real Estate & Construction' => 2,
    'Construction & Infrastructure' => 2,
    'Hospitality' => 2,
    'Travel & Tourism' => 2,
    'Finance & Insurance' => 2,
    'Manufacturing' => 2,
    'Industrial & Engineering' => 2,
    'Automotive' => 2,
    'Events & Entertainment' => 2,
    'Professional Services' => 2,
    'Wholesale & Distribution' => 2,

    // GENERIC (worst choices)
    'Automobile' => 8,  // Will merge into Automotive
    'Entertainment & Media' => 3,
    'Services' => 9,   // Very generic
    'Retail' => 9,     // Very generic
    'Others' => 10,    // Worst
];

// ===========================
// MANUAL OVERRIDES for specific sub-categories where algorithm might pick wrong
// Format: 'Sub Category Name' => 'Best Parent Category Name'
// ===========================
$subCategoryOverrides = [
    // Food/Hospitality specific
    'Bakery' => 'Food & Beverage',
    'Cafe' => 'Food & Beverage',
    'Cake Shop' => 'Food & Beverage',
    'Chicken Shop' => 'Food & Beverage',
    'Cloud Kitchen' => 'Food & Beverage',
    'Dry Fruits Store' => 'Food & Beverage',
    'Fish Shop' => 'Food & Beverage',
    'Ice Cream Parlour' => 'Food & Beverage',
    'Meat Shop' => 'Food & Beverage',
    'Sweet Shop' => 'Food & Beverage',
    'Restaurant' => 'Food & Beverage',
    'Lodge' => 'Hospitality',
    'Resort' => 'Hospitality',
    'Hotel' => 'Hospitality',
    'Guest House' => 'Hospitality',
    'Hostel' => 'Hospitality',
    'Banquet Hall' => 'Events & Entertainment',
    'Catering Service' => 'Food & Beverage',
    'Beverage Manufacturer' => 'Manufacturing',
    'Food Manufacturer' => 'Manufacturing',
    
    // Fashion
    'Boutique' => 'Fashion & Apparel',
    'Dress Material Store' => 'Fashion & Apparel',
    'Fabric Store' => 'Fashion & Apparel',
    'Footwear Store' => 'Fashion & Apparel',
    'Handbag Store' => 'Fashion & Apparel',
    'Jewellery Store' => 'Fashion & Apparel',
    'Kids Wear Store' => 'Fashion & Apparel',
    'Leather Goods Store' => 'Fashion & Apparel',
    'Luggage Store' => 'Fashion & Apparel',
    'Mens Clothing Store' => 'Fashion & Apparel',
    'Perfume Store' => 'Fashion & Apparel',
    'Saree Store' => 'Fashion & Apparel',
    'Sportswear Store' => 'Fashion & Apparel',
    'Sunglasses Store' => 'Fashion & Apparel',
    'Watch Store' => 'Fashion & Apparel',
    'Womens Clothing Store' => 'Fashion & Apparel',
    'Garment Manufacturer' => 'Manufacturing',
    'Textile Manufacturer' => 'Manufacturing',
    'Cosmetics Store' => 'Beauty & Wellness',
    
    // Healthcare
    'Medical Store' => 'Healthcare',
    'Pharmacy' => 'Healthcare',
    'Dermatology Clinic' => 'Healthcare',
    'Skin Clinic' => 'Healthcare',
    'Rehabilitation Center' => 'Healthcare',
    'Wellness Center' => 'Healthcare',
    'Physiotherapy Center' => 'Healthcare',
    'Veterinary Clinic' => 'Pet Care & Animal Services',
    'Veterinary Hospital' => 'Pet Care & Animal Services',
    
    // Home
    'Furniture Store' => 'Home Decor & Furnishing',
    'Curtain Store' => 'Home Decor & Furnishing',
    'Home Decor' => 'Home Decor & Furnishing',
    'Home Decor Store' => 'Home Decor & Furnishing',
    'Lighting Store' => 'Home Decor & Furnishing',
    'Mattress Store' => 'Home Decor & Furnishing',
    'Wallpaper Store' => 'Home Decor & Furnishing',
    'Paint Store' => 'Home Improvement',
    'Paint Manufacturer' => 'Manufacturing',
    'Hardware Store' => 'Home Improvement',
    'Sanitaryware Store' => 'Home Improvement',
    'Plumbing Store' => 'Home Improvement',
    'Glass Store' => 'Home Improvement',
    'Granite Store' => 'Home Improvement',
    'Steel Store' => 'Home Improvement',
    'Aluminium Store' => 'Home Improvement',
    'Cable Dealer' => 'Electronics & Electrical',
    'Cctv Dealer' => 'Security & Safety',
    'Electrical Store' => 'Electronics & Electrical',
    'Electronics Store' => 'Electronics & Electrical',
    'Electrician Service' => 'Home Improvement',
    'Plumber Service' => 'Home Improvement',
    'Welding Service' => 'Home Improvement',
    'Stone Supplier' => 'Home Improvement',
    'Pvc Pipe Dealer' => 'Home Improvement',
    'Building Material Supplier' => 'Construction & Infrastructure',
    'Cement Dealer' => 'Construction & Infrastructure',
    
    // Construction
    'Architect' => 'Real Estate & Construction',
    'Building Contractor' => 'Real Estate & Construction',
    'Civil Contractor' => 'Construction & Infrastructure',
    'Construction Company' => 'Construction & Infrastructure',
    'Construction Material Supplier' => 'Construction & Infrastructure',
    'Demolition Contractor' => 'Construction & Infrastructure',
    'Electrical Contractor' => 'Construction & Infrastructure',
    'False Ceiling Contractor' => 'Construction & Infrastructure',
    'Flooring Contractor' => 'Construction & Infrastructure',
    'Glass Contractor' => 'Home Improvement',
    'Hvac Contractor' => 'Construction & Infrastructure',
    'Landscape Designer' => 'Home Improvement',
    'Plumbing Contractor' => 'Construction & Infrastructure',
    'Roofing Contractor' => 'Construction & Infrastructure',
    'Solar Epc Company' => 'Energy & Renewable',
    'Structural Engineer' => 'Construction & Infrastructure',
    'Project Management Consultant' => 'Professional Services',
    'Valuation Consultant' => 'Professional Services',
    
    // IT & Digital
    'Computer Repair' => 'IT & Software',
    'Laptop Repair' => 'IT & Software',
    'Mobile App Development' => 'IT & Software',
    'Website Development' => 'IT & Software',
    'Digital Marketing Agency' => 'Advertising & Marketing',
    'Seo Agency' => 'Advertising & Marketing',
    'Sem Agency' => 'Advertising & Marketing',
    'Branding Agency' => 'Advertising & Marketing',
    'Creative Agency' => 'Advertising & Marketing',
    'Advertising Agency' => 'Advertising & Marketing',
    'Graphic Design Studio' => 'Advertising & Marketing',
    
    // Professional Services
    'Accounting Firm' => 'Finance & Insurance',
    'Bookkeeping Service' => 'Finance & Insurance',
    'Chartered Accountant' => 'Finance & Insurance',
    'Company Secretary' => 'Finance & Insurance',
    'Cost Accountant' => 'Finance & Insurance',
    'Financial Consultant' => 'Finance & Insurance',
    'Gst Consultant' => 'Finance & Insurance',
    'Income Tax Consultant' => 'Finance & Insurance',
    'Payroll Service' => 'Finance & Insurance',
    'Tax Consultant' => 'Finance & Insurance',
    'Vehicle Insurance Agent' => 'Finance & Insurance',
    'Recruitment Agency' => 'Professional Services',
    'Translation Service' => 'Professional Services',
    'Virtual Assistant' => 'Professional Services',
    'Freelancer' => 'Professional Services',
    'Study Abroad Consultant' => 'Education',
    
    // Transport & Travel
    'Bike Rental' => 'Travel & Tourism',
    'Cab Service' => 'Logistics & Transportation',
    'Car Rental' => 'Travel & Tourism',
    'Courier Service' => 'Logistics & Transportation',
    'Luxury Car Rental' => 'Travel & Tourism',
    'Packers & Movers' => 'Logistics & Transportation',
    'Self Drive Car Rental' => 'Travel & Tourism',
    'Taxi Service' => 'Logistics & Transportation',
    'Travel Agency' => 'Travel & Tourism',
    'Visa Consultant' => 'Travel & Tourism',
    'Freight Forwarder' => 'Logistics & Transportation',
    'Cold Storage' => 'Logistics & Transportation',
    'Warehouse' => 'Logistics & Transportation',
    
    // Events & Entertainment
    'Dj Service' => 'Event & Wedding Services',
    'Florist' => 'Event & Wedding Services',
    'Makeup Artist' => 'Event & Wedding Services',
    'Photography' => 'Event & Wedding Services',
    'Stage Decoration' => 'Event & Wedding Services',
    'Videography' => 'Event & Wedding Services',
    'Wedding Planner' => 'Event & Wedding Services',
    'Animation Studio' => 'Entertainment & Media',
    'Dance Academy' => 'Education',
    'Music Academy' => 'Education',
    'Beauty Academy' => 'Education',
    
    // Printing
    'Digital Printing' => 'Printing & Packaging',
    'Flex Printing' => 'Printing & Packaging',
    'Offset Printing' => 'Printing & Packaging',
    'Screen Printing' => 'Printing & Packaging',
    'Packaging Manufacturer' => 'Printing & Packaging',
    
    // Others
    'Personal Trainer' => 'Sports & Fitness',
    'Pilates Studio' => 'Sports & Fitness',
    'Zumba Studio' => 'Sports & Fitness',
    'Meditation Center' => 'Beauty & Wellness',
    'Influencer' => 'Entertainment & Media',
    'Interior Designer' => 'Home Improvement',
    'Baby Products Store' => 'Baby & Kids',
    'Toy Store' => 'Baby & Kids',
    'Pet Shop' => 'Pet Care & Animal Services',
    'Aquarium Store' => 'Pet Care & Animal Services',
    'Animal Feed Supplier' => 'Agriculture & Farming',
    'Battery Dealer' => 'Electronics & Electrical',
    'Generator Dealer' => 'Electronics & Electrical',
    'Ev Charging Station' => 'Automotive',
    'Beauty Products Store' => 'Beauty & Wellness',
    'Book Store' => 'Books Stationery & Office Supplies',
    'Stationery Store' => 'Books Stationery & Office Supplies',
    'Gift Shop' => 'Arts Crafts & Gifts',
    'Exporter' => 'Import & Export',
    'Importer' => 'Import & Export',
    'Religious Trust' => 'Religious & Spiritual',
    'Security Guard Service' => 'Security & Safety',
    
    // Auto
    'Auto Parts Store' => 'Automotive',
    'Car Accessories Store' => 'Automotive',
    'Car Dealer' => 'Automotive',
    'Tractor Dealer' => 'Automotive',
    
    // Manufacturing/Industrial
    'Casting Manufacturer' => 'Manufacturing',
    'Die Manufacturer' => 'Manufacturing',
    'Industrial Machinery Manufacturer' => 'Manufacturing',
    'Lubricant Manufacturer' => 'Manufacturing',
    'Metal Fabrication' => 'Manufacturing',
    'Mould Manufacturer' => 'Manufacturing',
];

echo "=== ANTIGRAVITY MASTER DEDUPLICATION ===\n\n";

// ===========================
// STEP 1: Merge Automobile (ID:10) into Automotive (ID:41)
// ===========================
echo "STEP 1: Merging Automobile into Automotive...\n";
$automobile = BusinessCategory::where('name', 'Automobile')->first();
$automotive = BusinessCategory::where('name', 'Automotive')->first();

if ($automobile && $automotive) {
    // Move all sub-categories from Automobile to Automotive
    BusinessSubCategory::where('business_category_id', $automobile->id)
        ->update(['business_category_id' => $automotive->id]);
    // Move all products
    BusinessProduct::where('business_category_id', $automobile->id)
        ->update(['business_category_id' => $automotive->id]);
    echo "  Moved sub-categories and products from Automobile (ID:{$automobile->id}) to Automotive (ID:{$automotive->id})\n";
    // Don't delete the category itself yet — let the sub-category dedup handle children first
}

// ===========================
// STEP 2: Deduplicate Sub Categories
// ===========================
echo "\nSTEP 2: Deduplicating Sub Categories...\n";
$subDuplicates = DB::select("SELECT name FROM business_sub_category GROUP BY name HAVING COUNT(id) > 1");
$subDeleteCount = 0;

foreach ($subDuplicates as $dup) {
    $subs = BusinessSubCategory::with('business_category')->where('name', $dup->name)->get();
    
    // Determine which one to keep
    $bestSub = null;
    $bestPriority = 999;
    
    foreach ($subs as $sub) {
        $catName = $sub->business_category ? $sub->business_category->name : '';
        
        // Check manual override first
        if (isset($subCategoryOverrides[$dup->name]) && $catName === $subCategoryOverrides[$dup->name]) {
            $bestSub = $sub;
            $bestPriority = 0;
            break;
        }
        
        // Use priority map
        $priority = $categoryPriority[$catName] ?? 5;
        if ($priority < $bestPriority) {
            $bestPriority = $priority;
            $bestSub = $sub;
        }
    }
    
    if (!$bestSub) {
        $bestSub = $subs->first();
    }
    
    // Delete the others, moving their children to the best one
    foreach ($subs as $sub) {
        if ($sub->id === $bestSub->id) continue;
        
        // Move Business Types to best sub
        BusinessType::where('business_sub_category_id', $sub->id)
            ->update(['business_sub_category_id' => $bestSub->id]);
        
        // Move Products to best sub
        BusinessProduct::where('business_sub_category_id', $sub->id)
            ->update(['business_sub_category_id' => $bestSub->id]);
        
        $sub->delete();
        $subDeleteCount++;
    }
}
echo "  Deleted {$subDeleteCount} duplicate sub-categories.\n";

// ===========================
// STEP 3: Deduplicate Business Types
// ===========================
echo "\nSTEP 3: Deduplicating Business Types...\n";
$typeDuplicates = DB::select("SELECT name FROM business_types GROUP BY name HAVING COUNT(id) > 1");
$typeDeleteCount = 0;

foreach ($typeDuplicates as $dup) {
    $types = BusinessType::with('business_sub_category.business_category')->where('name', $dup->name)->get();
    
    // For business types, keep the one under the best sub-category (which is now under the best category)
    $bestType = null;
    $bestPriority = 999;
    
    foreach ($types as $type) {
        $sub = $type->business_sub_category;
        $catName = $sub && $sub->business_category ? $sub->business_category->name : '';
        
        $priority = $categoryPriority[$catName] ?? 5;
        if ($priority < $bestPriority) {
            $bestPriority = $priority;
            $bestType = $type;
        }
    }
    
    if (!$bestType) {
        $bestType = $types->first();
    }
    
    foreach ($types as $type) {
        if ($type->id === $bestType->id) continue;
        
        // Move products to the best type
        BusinessProduct::where('business_type_id', $type->id)
            ->update(['business_type_id' => $bestType->id]);
        
        $type->delete();
        $typeDeleteCount++;
    }
}
echo "  Deleted {$typeDeleteCount} duplicate business types.\n";

// ===========================
// STEP 4: Deduplicate Products
// ===========================
echo "\nSTEP 4: Deduplicating Products...\n";
$prodDuplicates = DB::select("SELECT name FROM business_products GROUP BY name HAVING COUNT(id) > 1");
$prodDeleteCount = 0;

foreach ($prodDuplicates as $dup) {
    $prods = BusinessProduct::where('name', $dup->name)->get();
    
    // Keep the first one (oldest/original), delete the rest
    $keep = $prods->first();
    
    foreach ($prods as $prod) {
        if ($prod->id === $keep->id) continue;
        $prod->delete();
        $prodDeleteCount++;
    }
}
echo "  Deleted {$prodDeleteCount} duplicate products.\n";

// ===========================
// STEP 5: Now run sub-level dedup within same parent (exact same parent + same name)
// ===========================
echo "\nSTEP 5: Running same-parent duplicate cleanup...\n";

// Business Types: same name + same sub_category_id
$sameParentTypes = DB::select("SELECT name, business_sub_category_id FROM business_types GROUP BY name, business_sub_category_id HAVING COUNT(id) > 1");
$sameParentTypeDelete = 0;
foreach ($sameParentTypes as $dup) {
    $types = BusinessType::where('name', $dup->name)->where('business_sub_category_id', $dup->business_sub_category_id)->get();
    $keep = $types->first();
    foreach ($types as $type) {
        if ($type->id === $keep->id) continue;
        BusinessProduct::where('business_type_id', $type->id)->update(['business_type_id' => $keep->id]);
        $type->delete();
        $sameParentTypeDelete++;
    }
}
echo "  Cleaned {$sameParentTypeDelete} same-parent duplicate business types.\n";

// ===========================
// STEP 6: Delete empty Automobile category if it has no children
// ===========================
if ($automobile) {
    $remainingSubs = BusinessSubCategory::where('business_category_id', $automobile->id)->count();
    if ($remainingSubs === 0) {
        $automobile->delete();
        echo "\nDeleted empty 'Automobile' category (merged into 'Automotive').\n";
    }
}

// ===========================
// FINAL COUNTS
// ===========================
echo "\n=== FINAL COUNTS ===\n";
echo "Categories: " . BusinessCategory::count() . "\n";
echo "Sub Categories: " . BusinessSubCategory::count() . "\n";
echo "Business Types: " . BusinessType::count() . "\n";
echo "Products: " . BusinessProduct::count() . "\n";
echo "\nDone!\n";
