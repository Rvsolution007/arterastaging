<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\BusinessCategory;
use App\Models\BusinessSubCategory;
use App\Models\BusinessProduct;
use App\Models\ProductType;
use App\Models\Brand;
use Illuminate\Support\Str;

$data = [
    [
        'category' => 'Retail',
        'sub_category' => 'Luggage & Bag Store',
        'has_business_type' => 0,
        'products' => [
            ['name' => 'Cabin Trolley Bag', 'type' => 'Physical Product', 'brands' => 'VIP, Safari, American Tourister'],
            ['name' => 'Medium Trolley Bag', 'type' => 'Physical Product', 'brands' => 'VIP, Safari'],
            ['name' => 'Large Trolley Bag', 'type' => 'Physical Product', 'brands' => 'VIP, Samsonite'],
            ['name' => 'Hard Luggage', 'type' => 'Physical Product', 'brands' => 'Samsonite, American Tourister'],
            ['name' => 'Soft Luggage', 'type' => 'Physical Product', 'brands' => 'VIP, Safari'],
            ['name' => 'Travel Suitcase', 'type' => 'Physical Product', 'brands' => 'Samsonite, VIP'],
            ['name' => 'Travel Duffel Bag', 'type' => 'Physical Product', 'brands' => 'Wildcraft, Safari'],
            ['name' => 'Wheeled Duffel Bag', 'type' => 'Physical Product', 'brands' => 'American Tourister'],
            ['name' => 'Travel Backpack', 'type' => 'Physical Product', 'brands' => 'Wildcraft, Skybags'],
            ['name' => 'Hiking Backpack', 'type' => 'Physical Product', 'brands' => 'Wildcraft, Decathlon'],
            ['name' => 'Trekking Backpack', 'type' => 'Physical Product', 'brands' => 'Quechua, Wildcraft'],
            ['name' => 'Laptop Backpack', 'type' => 'Physical Product', 'brands' => 'Lenovo, HP, American Tourister'],
            ['name' => 'Office Backpack', 'type' => 'Physical Product', 'brands' => 'Skybags, Wildcraft'],
            ['name' => 'College Backpack', 'type' => 'Physical Product', 'brands' => 'Skybags, F Gear'],
            ['name' => 'School Bag', 'type' => 'Physical Product', 'brands' => 'Skybags, American Tourister'],
            ['name' => 'Kids School Bag', 'type' => 'Physical Product', 'brands' => 'Disney, Skybags'],
            ['name' => 'Lunch Bag', 'type' => 'Physical Product', 'brands' => 'Milton, Cello'],
            ['name' => 'Picnic Bag', 'type' => 'Physical Product', 'brands' => 'Coleman'],
            ['name' => 'Gym Bag', 'type' => 'Physical Product', 'brands' => 'Puma, Adidas, Nike'],
            ['name' => 'Sports Duffel Bag', 'type' => 'Physical Product', 'brands' => 'Adidas, Puma'],
            ['name' => 'Messenger Bag', 'type' => 'Physical Product', 'brands' => 'American Tourister, Lavie Sport'],
            ['name' => 'Sling Bag', 'type' => 'Physical Product', 'brands' => 'Lavie, Caprese'],
            ['name' => 'Crossbody Bag', 'type' => 'Physical Product', 'brands' => 'Lavie, Baggit'],
            ['name' => 'Tote Bag', 'type' => 'Physical Product', 'brands' => 'Lavie, Caprese'],
            ['name' => 'Handbag', 'type' => 'Physical Product', 'brands' => 'Lavie, Caprese, Baggit'],
            ['name' => 'Shoulder Bag', 'type' => 'Physical Product', 'brands' => 'Lavie, Caprese'],
            ['name' => 'Wallet', 'type' => 'Physical Product', 'brands' => 'Titan, WildHorn, Tommy Hilfiger'],
            ['name' => 'Card Holder', 'type' => 'Physical Product', 'brands' => 'Titan'],
            ['name' => 'Passport Holder', 'type' => 'Physical Product', 'brands' => 'Samsonite'],
            ['name' => 'Travel Organizer', 'type' => 'Physical Product', 'brands' => 'DailyObjects, Mokobara'],
            ['name' => 'Toiletry Bag', 'type' => 'Physical Product', 'brands' => 'Wildcraft'],
            ['name' => 'Cosmetic Bag', 'type' => 'Physical Product', 'brands' => 'Lavie'],
            ['name' => 'Makeup Organizer', 'type' => 'Physical Product', 'brands' => 'DailyObjects'],
            ['name' => 'Laptop Sleeve', 'type' => 'Physical Product', 'brands' => 'HP, Lenovo'],
            ['name' => 'Laptop Messenger Bag', 'type' => 'Physical Product', 'brands' => 'Dell, Lenovo'],
            ['name' => 'Camera Bag', 'type' => 'Physical Product', 'brands' => 'Lowepro, Case Logic'],
            ['name' => 'DSLR Backpack', 'type' => 'Physical Product', 'brands' => 'Lowepro'],
            ['name' => 'Drone Carry Case', 'type' => 'Physical Product', 'brands' => 'DJI'],
            ['name' => 'Garment Bag', 'type' => 'Physical Product', 'brands' => 'Samsonite'],
            ['name' => 'Shoe Bag', 'type' => 'Physical Product', 'brands' => 'Wildcraft'],
            ['name' => 'Laundry Bag', 'type' => 'Physical Product', 'brands' => 'IKEA'],
            ['name' => 'Storage Bag', 'type' => 'Physical Product', 'brands' => 'IKEA'],
            ['name' => 'Shopping Bag', 'type' => 'Physical Product', 'brands' => 'Baggit'],
            ['name' => 'Eco-Friendly Cloth Bag', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Jute Bag', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Trolley Bag Cover', 'type' => 'Physical Product', 'brands' => 'VIP'],
            ['name' => 'Luggage Tag', 'type' => 'Physical Product', 'brands' => 'Samsonite'],
            ['name' => 'Luggage Belt', 'type' => 'Physical Product', 'brands' => 'Samsonite'],
            ['name' => 'TSA Lock', 'type' => 'Physical Product', 'brands' => 'Samsonite'],
            ['name' => 'Combination Lock', 'type' => 'Physical Product', 'brands' => 'Godrej'],
            ['name' => 'Rain Cover for Backpack', 'type' => 'Physical Product', 'brands' => 'Wildcraft'],
            ['name' => 'Waterproof Backpack', 'type' => 'Physical Product', 'brands' => 'Wildcraft'],
            ['name' => 'Anti-Theft Backpack', 'type' => 'Physical Product', 'brands' => 'Arctic Hunter'],
            ['name' => 'RFID Wallet', 'type' => 'Physical Product', 'brands' => 'WildHorn'],
            ['name' => 'Travel Neck Pillow', 'type' => 'Physical Product', 'brands' => 'Amazon Basics, Safari'],
            ['name' => 'Eye Mask', 'type' => 'Physical Product', 'brands' => 'Safari'],
            ['name' => 'Travel Pouch Set', 'type' => 'Physical Product', 'brands' => 'Mokobara'],
            ['name' => 'Packing Cubes', 'type' => 'Physical Product', 'brands' => 'Mokobara'],
            ['name' => 'Compression Packing Bags', 'type' => 'Physical Product', 'brands' => 'Mokobara'],
            ['name' => 'Luggage Scale', 'type' => 'Physical Product', 'brands' => 'Samsonite'],
            ['name' => 'Travel Bottle Set', 'type' => 'Physical Product', 'brands' => 'Cello'],
            ['name' => 'Business Briefcase', 'type' => 'Physical Product', 'brands' => 'Samsonite, Hidesign'],
            ['name' => 'Leather Office Bag', 'type' => 'Physical Product', 'brands' => 'Hidesign'],
            ['name' => 'Premium Travel Set', 'type' => 'Physical Product', 'brands' => 'Samsonite'],
            ['name' => 'Luggage Gift Set', 'type' => 'Physical Product', 'brands' => 'VIP, Safari'],
            ['name' => 'Travel Accessories Combo', 'type' => 'Physical Product', 'brands' => 'Safari, Mokobara'],
        ]
    ],
    [
        'category' => 'Retail',
        'sub_category' => 'Watch Store',
        'has_business_type' => 0,
        'products' => [
            ['name' => 'Analog Watch', 'type' => 'Physical Product', 'brands' => 'Titan, Fastrack, Casio'],
            ['name' => 'Digital Watch', 'type' => 'Physical Product', 'brands' => 'Casio, Timex'],
            ['name' => 'Smart Watch', 'type' => 'Physical Product', 'brands' => 'Apple, Samsung, Noise, boAt'],
            ['name' => 'Hybrid Smart Watch', 'type' => 'Physical Product', 'brands' => 'Fossil'],
            ['name' => 'Luxury Watch', 'type' => 'Physical Product', 'brands' => 'Tissot, Seiko, Citizen'],
            ['name' => "Men's Watch", 'type' => 'Physical Product', 'brands' => 'Titan, Fastrack, Fossil'],
            ['name' => "Women's Watch", 'type' => 'Physical Product', 'brands' => 'Titan Raga, Fossil'],
            ['name' => 'Kids Watch', 'type' => 'Physical Product', 'brands' => 'Zoop, Fastrack'],
            ['name' => 'Couple Watch Set', 'type' => 'Physical Product', 'brands' => 'Titan, Fastrack'],
            ['name' => 'Sports Watch', 'type' => 'Physical Product', 'brands' => 'Casio G-Shock, Garmin'],
            ['name' => 'Fitness Watch', 'type' => 'Physical Product', 'brands' => 'Fitbit, Garmin'],
            ['name' => 'Running Watch', 'type' => 'Physical Product', 'brands' => 'Garmin, Coros'],
            ['name' => 'Outdoor Watch', 'type' => 'Physical Product', 'brands' => 'Garmin, Casio Pro Trek'],
            ['name' => 'Diving Watch', 'type' => 'Physical Product', 'brands' => 'Seiko, Citizen'],
            ['name' => 'Chronograph Watch', 'type' => 'Physical Product', 'brands' => 'Fossil, Casio Edifice'],
            ['name' => 'Automatic Watch', 'type' => 'Physical Product', 'brands' => 'Seiko, Citizen'],
            ['name' => 'Mechanical Watch', 'type' => 'Physical Product', 'brands' => 'Seiko, Tissot'],
            ['name' => 'Quartz Watch', 'type' => 'Physical Product', 'brands' => 'Titan, Timex'],
            ['name' => 'Stainless Steel Watch', 'type' => 'Physical Product', 'brands' => 'Titan, Casio'],
            ['name' => 'Leather Strap Watch', 'type' => 'Physical Product', 'brands' => 'Fossil, Titan'],
            ['name' => 'Metal Strap Watch', 'type' => 'Physical Product', 'brands' => 'Casio, Titan'],
            ['name' => 'Silicone Strap Watch', 'type' => 'Physical Product', 'brands' => 'Noise, boAt'],
            ['name' => 'Ceramic Watch', 'type' => 'Physical Product', 'brands' => 'Rado'],
            ['name' => 'Gold Finish Watch', 'type' => 'Physical Product', 'brands' => 'Titan'],
            ['name' => 'Rose Gold Watch', 'type' => 'Physical Product', 'brands' => 'Fossil'],
            ['name' => 'Diamond Watch', 'type' => 'Physical Product', 'brands' => 'Titan Raga'],
            ['name' => 'Slim Watch', 'type' => 'Physical Product', 'brands' => 'Titan, Skagen'],
            ['name' => 'Fashion Watch', 'type' => 'Physical Product', 'brands' => 'Fastrack, Fossil'],
            ['name' => 'Premium Watch', 'type' => 'Physical Product', 'brands' => 'Citizen, Seiko'],
            ['name' => 'Wedding Watch Gift Set', 'type' => 'Physical Product', 'brands' => 'Titan'],
            ['name' => 'Corporate Gift Watch', 'type' => 'Physical Product', 'brands' => 'Titan, Casio'],
            ['name' => 'Limited Edition Watch', 'type' => 'Physical Product', 'brands' => 'G-Shock, Seiko'],
            ['name' => 'Smart Watch Strap', 'type' => 'Physical Product', 'brands' => 'Apple, Samsung'],
            ['name' => 'Silicone Watch Strap', 'type' => 'Physical Product', 'brands' => 'Noise, boAt'],
            ['name' => 'Leather Watch Strap', 'type' => 'Physical Product', 'brands' => 'Titan'],
            ['name' => 'Metal Watch Strap', 'type' => 'Physical Product', 'brands' => 'Casio'],
            ['name' => 'Watch Battery', 'type' => 'Physical Product', 'brands' => 'Maxell, Renata'],
            ['name' => 'Watch Charger', 'type' => 'Physical Product', 'brands' => 'Apple, Samsung'],
            ['name' => 'Wireless Watch Charger', 'type' => 'Physical Product', 'brands' => 'Samsung'],
            ['name' => 'Watch Screen Protector', 'type' => 'Physical Product', 'brands' => 'Spigen'],
            ['name' => 'Watch Case', 'type' => 'Physical Product', 'brands' => 'DailyObjects'],
            ['name' => 'Watch Storage Box', 'type' => 'Physical Product', 'brands' => 'Wolf, Local Brands'],
            ['name' => 'Watch Winder', 'type' => 'Physical Product', 'brands' => 'Wolf'],
            ['name' => 'Watch Cleaning Kit', 'type' => 'Physical Product', 'brands' => 'Cape Cod'],
            ['name' => 'Watch Repair Tool Kit', 'type' => 'Physical Product', 'brands' => 'Jakemy'],
            ['name' => 'Spring Bar Tool', 'type' => 'Physical Product', 'brands' => 'Bergeon'],
            ['name' => 'Watch Buckle', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Watch Dial Protector', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Pocket Watch', 'type' => 'Physical Product', 'brands' => 'Titan'],
            ['name' => 'Alarm Clock', 'type' => 'Physical Product', 'brands' => 'Ajanta, Casio'],
            ['name' => 'Wall Clock', 'type' => 'Physical Product', 'brands' => 'Ajanta, Titan'],
            ['name' => 'Table Clock', 'type' => 'Physical Product', 'brands' => 'Ajanta'],
            ['name' => 'Digital Clock', 'type' => 'Physical Product', 'brands' => 'Casio'],
            ['name' => 'Travel Alarm Clock', 'type' => 'Physical Product', 'brands' => 'Casio'],
            ['name' => 'Luxury Clock', 'type' => 'Physical Product', 'brands' => 'Seiko'],
            ['name' => 'Gift Watch Box', 'type' => 'Physical Product', 'brands' => 'Titan'],
            ['name' => 'Couple Smart Watch Set', 'type' => 'Physical Product', 'brands' => 'Noise, boAt'],
            ['name' => 'Premium Smart Watch', 'type' => 'Physical Product', 'brands' => 'Apple, Samsung, Garmin'],
            ['name' => 'Fitness Band', 'type' => 'Physical Product', 'brands' => 'Xiaomi, Fitbit'],
            ['name' => 'Activity Tracker', 'type' => 'Physical Product', 'brands' => 'Fitbit, Garmin'],
            ['name' => 'Heart Rate Monitor Watch', 'type' => 'Physical Product', 'brands' => 'Garmin, Polar'],
            ['name' => 'GPS Smart Watch', 'type' => 'Physical Product', 'brands' => 'Garmin, Apple'],
            ['name' => 'Kids GPS Watch', 'type' => 'Physical Product', 'brands' => 'imoo'],
            ['name' => 'Smart Watch Accessories Combo', 'type' => 'Physical Product', 'brands' => 'Apple, Samsung'],
        ]
    ],
    [
        'category' => 'Retail',
        'sub_category' => 'Optical Store',
        'has_business_type' => 0,
        'products' => [
            ['name' => 'Prescription Eyeglasses', 'type' => 'Physical Product', 'brands' => 'Titan Eye+, Lenskart'],
            ['name' => 'Reading Glasses', 'type' => 'Physical Product', 'brands' => 'Vincent Chase, John Jacobs'],
            ['name' => 'Computer Glasses', 'type' => 'Physical Product', 'brands' => 'Lenskart, Crizal'],
            ['name' => 'Blue Light Blocking Glasses', 'type' => 'Physical Product', 'brands' => 'Lenskart, GKB Opticals'],
            ['name' => 'Single Vision Lenses', 'type' => 'Physical Product', 'brands' => 'Essilor, ZEISS'],
            ['name' => 'Bifocal Lenses', 'type' => 'Physical Product', 'brands' => 'Essilor, HOYA'],
            ['name' => 'Progressive Lenses', 'type' => 'Physical Product', 'brands' => 'ZEISS, Essilor'],
            ['name' => 'Photochromic Lenses', 'type' => 'Physical Product', 'brands' => 'Transitions, ZEISS'],
            ['name' => 'Anti-Glare Lenses', 'type' => 'Physical Product', 'brands' => 'Crizal, Essilor'],
            ['name' => 'UV Protection Lenses', 'type' => 'Physical Product', 'brands' => 'ZEISS, HOYA'],
            ['name' => 'High Index Lenses', 'type' => 'Physical Product', 'brands' => 'Essilor'],
            ['name' => 'Polycarbonate Lenses', 'type' => 'Physical Product', 'brands' => 'HOYA'],
            ['name' => 'Rimless Frames', 'type' => 'Physical Product', 'brands' => 'Titan Eye+, Ray-Ban'],
            ['name' => 'Full Rim Frames', 'type' => 'Physical Product', 'brands' => 'Ray-Ban, Vincent Chase'],
            ['name' => 'Half Rim Frames', 'type' => 'Physical Product', 'brands' => 'Titan Eye+'],
            ['name' => 'Metal Frames', 'type' => 'Physical Product', 'brands' => 'Ray-Ban, Vogue'],
            ['name' => 'Plastic Frames', 'type' => 'Physical Product', 'brands' => 'Vincent Chase'],
            ['name' => 'Acetate Frames', 'type' => 'Physical Product', 'brands' => 'Oakley, Ray-Ban'],
            ['name' => 'Titanium Frames', 'type' => 'Physical Product', 'brands' => 'Silhouette, Titan'],
            ['name' => 'Kids Eyeglasses', 'type' => 'Physical Product', 'brands' => 'Lenskart Kids, Disney'],
            ['name' => 'Sunglasses', 'type' => 'Physical Product', 'brands' => 'Ray-Ban, Oakley, Fastrack'],
            ['name' => 'Polarized Sunglasses', 'type' => 'Physical Product', 'brands' => 'Ray-Ban, Polaroid'],
            ['name' => 'Aviator Sunglasses', 'type' => 'Physical Product', 'brands' => 'Ray-Ban'],
            ['name' => 'Wayfarer Sunglasses', 'type' => 'Physical Product', 'brands' => 'Ray-Ban'],
            ['name' => 'Sports Sunglasses', 'type' => 'Physical Product', 'brands' => 'Oakley'],
            ['name' => 'Fashion Sunglasses', 'type' => 'Physical Product', 'brands' => 'Fastrack, Vogue'],
            ['name' => 'Clip-On Sunglasses', 'type' => 'Physical Product', 'brands' => 'Lenskart'],
            ['name' => 'Contact Lenses', 'type' => 'Physical Product', 'brands' => 'Bausch + Lomb, Acuvue'],
            ['name' => 'Daily Contact Lenses', 'type' => 'Physical Product', 'brands' => 'Acuvue'],
            ['name' => 'Monthly Contact Lenses', 'type' => 'Physical Product', 'brands' => 'Bausch + Lomb'],
            ['name' => 'Toric Contact Lenses', 'type' => 'Physical Product', 'brands' => 'Acuvue'],
            ['name' => 'Colored Contact Lenses', 'type' => 'Physical Product', 'brands' => 'FreshLook, Bella'],
            ['name' => 'Contact Lens Solution', 'type' => 'Physical Product', 'brands' => 'Bausch + Lomb, Opti-Free'],
            ['name' => 'Contact Lens Case', 'type' => 'Physical Product', 'brands' => 'Bausch + Lomb'],
            ['name' => 'Lens Cleaning Spray', 'type' => 'Physical Product', 'brands' => 'ZEISS'],
            ['name' => 'Microfiber Cleaning Cloth', 'type' => 'Physical Product', 'brands' => 'ZEISS'],
            ['name' => 'Eyeglass Cleaning Kit', 'type' => 'Physical Product', 'brands' => 'ZEISS'],
            ['name' => 'Eyeglass Case', 'type' => 'Physical Product', 'brands' => 'Lenskart, Ray-Ban'],
            ['name' => 'Hard Glasses Case', 'type' => 'Physical Product', 'brands' => 'Ray-Ban'],
            ['name' => 'Soft Glasses Pouch', 'type' => 'Physical Product', 'brands' => 'Lenskart'],
            ['name' => 'Eyeglass Chain', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Eyeglass Cord', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Nose Pads', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Temple Tips', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Frame Screws', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Eyeglass Repair Kit', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Vision Testing Kit', 'type' => 'Physical Product', 'brands' => 'Essilor'],
            ['name' => 'Eye Patch', 'type' => 'Physical Product', 'brands' => '3M'],
            ['name' => 'Eye Care Drops', 'type' => 'Physical Product', 'brands' => 'Refresh, Systane'],
            ['name' => 'Safety Glasses', 'type' => 'Physical Product', 'brands' => '3M, Honeywell'],
            ['name' => 'Industrial Safety Goggles', 'type' => 'Physical Product', 'brands' => 'Honeywell'],
            ['name' => 'Swimming Goggles', 'type' => 'Physical Product', 'brands' => 'Speedo'],
            ['name' => 'Ski Goggles', 'type' => 'Physical Product', 'brands' => 'Oakley'],
            ['name' => 'Motorcycle Riding Glasses', 'type' => 'Physical Product', 'brands' => 'Vega'],
            ['name' => 'Magnifying Glass', 'type' => 'Physical Product', 'brands' => 'Maped'],
            ['name' => 'Binoculars', 'type' => 'Physical Product', 'brands' => 'Nikon, Celestron'],
            ['name' => 'Monocular', 'type' => 'Physical Product', 'brands' => 'Celestron'],
            ['name' => 'Telescope', 'type' => 'Physical Product', 'brands' => 'Celestron'],
            ['name' => 'Premium Eyewear Gift Box', 'type' => 'Physical Product', 'brands' => 'Ray-Ban'],
            ['name' => 'Eyewear Accessories Combo', 'type' => 'Physical Product', 'brands' => 'ZEISS, Lenskart'],
        ]
    ]
];

foreach ($data as $block) {
    // 1. Get/Create Category
    $category = BusinessCategory::firstOrCreate(
        ['name' => $block['category']],
        ['slug' => Str::slug($block['category']), 'status' => 1]
    );

    // 2. Get/Create Sub Category
    $subCategory = BusinessSubCategory::firstOrCreate(
        [
            'name' => $block['sub_category'],
            'business_category_id' => $category->id
        ],
        [
            'slug' => Str::slug($block['sub_category']),
            'has_business_type' => $block['has_business_type'],
            'status' => 1
        ]
    );

    // 3. Insert Products
    foreach ($block['products'] as $p) {
        $productType = ProductType::firstOrCreate(
            ['name' => $p['type']],
            ['slug' => Str::slug($p['type']), 'status' => 1]
        );

        $brandIds = [];
        if (!empty($p['brands']) && $p['brands'] !== 'None') {
            $brandsArr = array_map('trim', explode(',', $p['brands']));
            foreach ($brandsArr as $b) {
                $brandObj = Brand::firstOrCreate(
                    ['name' => $b],
                    ['slug' => Str::slug($b), 'status' => 1]
                );
                $brandIds[] = (string)$brandObj->id;
            }
        }

        BusinessProduct::updateOrCreate(
            [
                'name' => $p['name'],
                'business_category_id' => $category->id,
                'business_sub_category_id' => $subCategory->id,
            ],
            [
                'slug' => Str::slug($p['name']),
                'product_type_id' => $productType->id,
                'brand_id' => $brandIds, // the cast handles array to json automatically!
                'status' => 1,
            ]
        );
    }
}

echo "Successfully seeded Data for Luggage & Bag Store, Watch Store, and Optical Store.\n";
