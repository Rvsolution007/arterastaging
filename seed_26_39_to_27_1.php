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
        'sub_category' => 'Packaging Material Store',
        'has_business_type' => 0,
        'products' => [
            ['name' => 'Corrugated Box', 'type' => 'Physical Product', 'brands' => 'B&B Triplewall, Local Brands'],
            ['name' => 'Carton Box', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Shipping Box', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Pizza Box', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Cake Box', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Sweet Box', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Gift Box', 'type' => 'Physical Product', 'brands' => 'Printo, Local Brands'],
            ['name' => 'Rigid Box', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Kraft Paper Box', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Duplex Box', 'type' => 'Physical Product', 'brands' => 'ITC, Local Brands'],
            ['name' => 'Paper Bag', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Kraft Paper Bag', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Shopping Bag', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Carry Bag', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Cloth Bag', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Jute Bag', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Bubble Wrap Roll', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Stretch Film Roll', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Cling Film', 'type' => 'Physical Product', 'brands' => 'Freshwrapp'],
            ['name' => 'Aluminium Foil Roll', 'type' => 'Physical Product', 'brands' => 'Freshwrapp, Gala'],
            ['name' => 'Corrugated Roll', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Foam Sheet', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'EPE Foam Roll', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Thermocol Sheet', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Air Bubble Pouch', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Courier Bag', 'type' => 'Physical Product', 'brands' => 'AmazonBasics, Local Brands'],
            ['name' => 'Tamper Proof Courier Bag', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Zip Lock Pouch', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Stand Up Pouch', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Vacuum Pouch', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Paper Envelope', 'type' => 'Physical Product', 'brands' => 'Oddy'],
            ['name' => 'Courier Envelope', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Document Envelope', 'type' => 'Physical Product', 'brands' => 'Oddy'],
            ['name' => 'Packing Tape', 'type' => 'Physical Product', 'brands' => 'Wonder, 3M'],
            ['name' => 'BOPP Tape', 'type' => 'Physical Product', 'brands' => 'Wonder'],
            ['name' => 'Masking Tape', 'type' => 'Physical Product', 'brands' => '3M'],
            ['name' => 'Double Sided Tape', 'type' => 'Physical Product', 'brands' => '3M'],
            ['name' => 'Fragile Tape', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Strapping Roll', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'PP Strap Roll', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'PET Strap Roll', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Plastic Wrap Roll', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Shrink Film', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Shrink Bags', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Cable Tie', 'type' => 'Physical Product', 'brands' => 'HellermannTyton'],
            ['name' => 'Packaging Labels', 'type' => 'Physical Product', 'brands' => 'Avery'],
            ['name' => 'Barcode Labels', 'type' => 'Physical Product', 'brands' => 'Avery'],
            ['name' => 'Shipping Labels', 'type' => 'Physical Product', 'brands' => 'Avery'],
            ['name' => 'Price Tags', 'type' => 'Physical Product', 'brands' => 'Avery'],
            ['name' => 'Hang Tags', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Sticker Labels', 'type' => 'Physical Product', 'brands' => 'Avery'],
            ['name' => 'Thermal Label Roll', 'type' => 'Physical Product', 'brands' => 'TSC'],
            ['name' => 'Tissue Paper', 'type' => 'Physical Product', 'brands' => 'Origami'],
            ['name' => 'Butter Paper', 'type' => 'Physical Product', 'brands' => 'Freshwrapp'],
            ['name' => 'Wax Paper', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Packing Rope', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Nylon Rope', 'type' => 'Physical Product', 'brands' => 'Garware'],
            ['name' => 'Twine Roll', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Packing Twine', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Edge Protector', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Corner Protector', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Pallet Wrap', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Wooden Pallet', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Plastic Pallet', 'type' => 'Physical Product', 'brands' => 'Nilkamal'],
            ['name' => 'Packaging Machine', 'type' => 'Physical Product', 'brands' => 'Hualian'],
            ['name' => 'Tape Dispenser', 'type' => 'Physical Product', 'brands' => '3M'],
            ['name' => 'Carton Sealer', 'type' => 'Physical Product', 'brands' => 'Hualian'],
            ['name' => 'Strapping Machine', 'type' => 'Physical Product', 'brands' => 'Hualian'],
            ['name' => 'Vacuum Packaging Machine', 'type' => 'Physical Product', 'brands' => 'Hualian'],
            ['name' => 'Heat Gun', 'type' => 'Physical Product', 'brands' => 'Bosch, Stanley'],
            ['name' => 'Packaging Material Combo', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'E-commerce Packaging Kit', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Premium Packaging Box Set', 'type' => 'Physical Product', 'brands' => 'Printo, Local Brands'],
        ]
    ],
    [
        'category' => 'Retail',
        'sub_category' => 'Safety Equipment Store',
        'has_business_type' => 0,
        'products' => [
            ['name' => 'Safety Helmet', 'type' => 'Physical Product', 'brands' => 'Karam, Udyogi, 3M'],
            ['name' => 'Industrial Helmet', 'type' => 'Physical Product', 'brands' => 'Karam, Honeywell'],
            ['name' => 'Construction Helmet', 'type' => 'Physical Product', 'brands' => 'Karam'],
            ['name' => 'Safety Shoes', 'type' => 'Physical Product', 'brands' => 'Liberty Warrior, Hillson, Karam'],
            ['name' => 'Steel Toe Shoes', 'type' => 'Physical Product', 'brands' => 'Liberty Warrior, Hillson'],
            ['name' => 'Gum Boots', 'type' => 'Physical Product', 'brands' => 'Safari Pro, Liberty'],
            ['name' => 'Safety Goggles', 'type' => 'Physical Product', 'brands' => '3M, Honeywell, Uvex'],
            ['name' => 'Welding Goggles', 'type' => 'Physical Product', 'brands' => 'ESAB'],
            ['name' => 'Face Shield', 'type' => 'Physical Product', 'brands' => 'Honeywell, Karam'],
            ['name' => 'Welding Helmet', 'type' => 'Physical Product', 'brands' => 'ESAB, Karam'],
            ['name' => 'Safety Glasses', 'type' => 'Physical Product', 'brands' => 'Honeywell, 3M'],
            ['name' => 'Ear Plugs', 'type' => 'Physical Product', 'brands' => '3M, Honeywell'],
            ['name' => 'Ear Muffs', 'type' => 'Physical Product', 'brands' => '3M, Honeywell'],
            ['name' => 'Disposable Face Mask', 'type' => 'Physical Product', 'brands' => 'Venus, 3M'],
            ['name' => 'N95 Mask', 'type' => 'Physical Product', 'brands' => '3M, Venus'],
            ['name' => 'Respirator Mask', 'type' => 'Physical Product', 'brands' => '3M, Honeywell'],
            ['name' => 'Respirator Cartridge', 'type' => 'Physical Product', 'brands' => '3M'],
            ['name' => 'Dust Mask', 'type' => 'Physical Product', 'brands' => 'Venus'],
            ['name' => 'Safety Gloves', 'type' => 'Physical Product', 'brands' => 'Karam, 3M'],
            ['name' => 'Cut Resistant Gloves', 'type' => 'Physical Product', 'brands' => 'Honeywell'],
            ['name' => 'Chemical Resistant Gloves', 'type' => 'Physical Product', 'brands' => 'Ansell'],
            ['name' => 'Heat Resistant Gloves', 'type' => 'Physical Product', 'brands' => 'Karam'],
            ['name' => 'Leather Safety Gloves', 'type' => 'Physical Product', 'brands' => 'Karam'],
            ['name' => 'Cotton Safety Gloves', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Reflective Safety Jacket', 'type' => 'Physical Product', 'brands' => 'Karam, Udyogi'],
            ['name' => 'High Visibility Vest', 'type' => 'Physical Product', 'brands' => 'Karam'],
            ['name' => 'Rain Suit', 'type' => 'Physical Product', 'brands' => 'Duckback'],
            ['name' => 'Chemical Protection Suit', 'type' => 'Physical Product', 'brands' => 'DuPont Tyvek'],
            ['name' => 'Coverall Suit', 'type' => 'Physical Product', 'brands' => 'Karam'],
            ['name' => 'Disposable Coverall', 'type' => 'Physical Product', 'brands' => 'DuPont Tyvek'],
            ['name' => 'Safety Harness', 'type' => 'Physical Product', 'brands' => 'Karam, Udyogi'],
            ['name' => 'Full Body Harness', 'type' => 'Physical Product', 'brands' => 'Karam'],
            ['name' => 'Fall Arrest System', 'type' => 'Physical Product', 'brands' => 'Honeywell'],
            ['name' => 'Lanyard', 'type' => 'Physical Product', 'brands' => 'Karam'],
            ['name' => 'Lifeline Rope', 'type' => 'Physical Product', 'brands' => 'Karam'],
            ['name' => 'Fire Extinguisher', 'type' => 'Physical Product', 'brands' => 'Ceasefire, Safex'],
            ['name' => 'Fire Blanket', 'type' => 'Physical Product', 'brands' => 'Ceasefire'],
            ['name' => 'Fire Bucket', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Fire Alarm', 'type' => 'Physical Product', 'brands' => 'Honeywell'],
            ['name' => 'Smoke Detector', 'type' => 'Physical Product', 'brands' => 'Honeywell'],
            ['name' => 'Gas Leak Detector', 'type' => 'Physical Product', 'brands' => 'Honeywell'],
            ['name' => 'Emergency Exit Light', 'type' => 'Physical Product', 'brands' => 'Philips'],
            ['name' => 'Safety Sign Board', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Caution Tape', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Barricade Tape', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Traffic Cone', 'type' => 'Physical Product', 'brands' => 'Nilkamal'],
            ['name' => 'Road Safety Cone', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Convex Mirror', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Wheel Stopper', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Speed Breaker', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'First Aid Kit', 'type' => 'Physical Product', 'brands' => 'Dettol, 3M'],
            ['name' => 'Eye Wash Station', 'type' => 'Physical Product', 'brands' => 'Honeywell'],
            ['name' => 'Spill Kit', 'type' => 'Physical Product', 'brands' => '3M'],
            ['name' => 'Lockout Tagout Kit', 'type' => 'Physical Product', 'brands' => 'Brady'],
            ['name' => 'Safety Padlock', 'type' => 'Physical Product', 'brands' => 'Brady'],
            ['name' => 'Emergency Torch', 'type' => 'Physical Product', 'brands' => 'Eveready'],
            ['name' => 'Rechargeable Emergency Light', 'type' => 'Physical Product', 'brands' => 'Syska'],
            ['name' => 'Safety Rope', 'type' => 'Physical Product', 'brands' => 'Karam'],
            ['name' => 'Rescue Kit', 'type' => 'Physical Product', 'brands' => 'Karam'],
            ['name' => 'Industrial Safety Kit', 'type' => 'Physical Product', 'brands' => 'Karam'],
            ['name' => 'PPE Kit', 'type' => 'Physical Product', 'brands' => 'Karam, Honeywell'],
            ['name' => 'Electrical Safety Kit', 'type' => 'Physical Product', 'brands' => 'Honeywell'],
            ['name' => 'Welding Safety Kit', 'type' => 'Physical Product', 'brands' => 'ESAB'],
            ['name' => 'Construction Safety Kit', 'type' => 'Physical Product', 'brands' => 'Karam'],
            ['name' => 'Road Safety Kit', 'type' => 'Physical Product', 'brands' => '3M'],
            ['name' => 'Safety Equipment Combo', 'type' => 'Physical Product', 'brands' => 'Karam, Honeywell'],
        ]
    ],
    [
        'category' => 'Automotive',
        'sub_category' => 'Car Dealer',
        'has_business_type' => 1,
        'products' => [
            ['name' => 'Hatchback Car', 'type' => 'Physical Product', 'brands' => 'Maruti Suzuki, Hyundai, Tata Motors'],
            ['name' => 'Sedan Car', 'type' => 'Physical Product', 'brands' => 'Honda, Hyundai, Volkswagen'],
            ['name' => 'SUV', 'type' => 'Physical Product', 'brands' => 'Mahindra, Tata Motors, Hyundai'],
            ['name' => 'Compact SUV', 'type' => 'Physical Product', 'brands' => 'Tata Motors, Maruti Suzuki, Kia'],
            ['name' => 'MUV', 'type' => 'Physical Product', 'brands' => 'Toyota, Kia, Maruti Suzuki'],
            ['name' => 'Luxury Car', 'type' => 'Physical Product', 'brands' => 'Mercedes-Benz, BMW, Audi'],
            ['name' => 'Electric Car (EV)', 'type' => 'Physical Product', 'brands' => 'Tata Motors, MG Motor, BYD'],
            ['name' => 'Hybrid Car', 'type' => 'Physical Product', 'brands' => 'Toyota, Honda'],
            ['name' => 'CNG Car', 'type' => 'Physical Product', 'brands' => 'Maruti Suzuki, Tata Motors'],
            ['name' => 'Pickup Truck', 'type' => 'Physical Product', 'brands' => 'Isuzu, Toyota'],
            ['name' => 'Sports Car', 'type' => 'Physical Product', 'brands' => 'Porsche, BMW M, Mercedes-AMG'],
            ['name' => 'Convertible Car', 'type' => 'Physical Product', 'brands' => 'BMW, Mini'],
            ['name' => 'Used Cars', 'type' => 'Physical Product', 'brands' => 'Maruti Suzuki True Value, Mahindra First Choice'],
            ['name' => 'Certified Pre-Owned Cars', 'type' => 'Physical Product', 'brands' => 'Toyota U Trust, Honda Auto Terrace'],
            ['name' => 'Car Accessories', 'type' => 'Physical Product', 'brands' => '3M, Bosch, Michelin'],
            ['name' => 'Seat Covers', 'type' => 'Physical Product', 'brands' => 'Autofurnish, Elegant'],
            ['name' => 'Floor Mats', 'type' => 'Physical Product', 'brands' => '3D Mats, Autofurnish'],
            ['name' => 'Car Cover', 'type' => 'Physical Product', 'brands' => 'Polco, Autofurnish'],
            ['name' => 'Car Perfume', 'type' => 'Physical Product', 'brands' => 'Areon, Ambi Pur'],
            ['name' => 'Car Cleaning Kit', 'type' => 'Physical Product', 'brands' => '3M, Wavex'],
            ['name' => 'Car Polish', 'type' => 'Physical Product', 'brands' => '3M, Formula 1'],
            ['name' => 'Engine Oil', 'type' => 'Physical Product', 'brands' => 'Castrol, Shell, Mobil'],
            ['name' => 'Coolant', 'type' => 'Physical Product', 'brands' => 'Castrol, Prestone'],
            ['name' => 'Car Battery', 'type' => 'Physical Product', 'brands' => 'Exide, Amaron'],
            ['name' => 'Alloy Wheels', 'type' => 'Physical Product', 'brands' => 'Neo Wheels, MRF'],
            ['name' => 'Tyres', 'type' => 'Physical Product', 'brands' => 'MRF, CEAT, Apollo, Bridgestone'],
            ['name' => 'Car Stereo', 'type' => 'Physical Product', 'brands' => 'Sony, Pioneer, Blaupunkt'],
            ['name' => 'Android Car Stereo', 'type' => 'Physical Product', 'brands' => 'Sony, Blaupunkt'],
            ['name' => 'Dash Camera', 'type' => 'Physical Product', 'brands' => 'DDPAI, 70mai'],
            ['name' => 'Reverse Camera', 'type' => 'Physical Product', 'brands' => 'Blaupunkt, Sony'],
            ['name' => 'GPS Navigation System', 'type' => 'Physical Product', 'brands' => 'Garmin'],
            ['name' => 'Car Charger', 'type' => 'Physical Product', 'brands' => 'Portronics, Ambrane'],
            ['name' => 'Jump Starter', 'type' => 'Physical Product', 'brands' => 'Michelin'],
            ['name' => 'Air Compressor', 'type' => 'Physical Product', 'brands' => 'Michelin'],
            ['name' => 'Car Vacuum Cleaner', 'type' => 'Physical Product', 'brands' => 'Black+Decker'],
            ['name' => 'Sun Shades', 'type' => 'Physical Product', 'brands' => 'Autofurnish'],
            ['name' => 'Steering Wheel Cover', 'type' => 'Physical Product', 'brands' => 'Autofurnish'],
            ['name' => 'Car Wax', 'type' => 'Physical Product', 'brands' => 'Meguiar\'s, 3M'],
            ['name' => 'Ceramic Coating Kit', 'type' => 'Physical Product', 'brands' => 'Turtle Wax, 3M'],
            ['name' => 'Wiper Blades', 'type' => 'Physical Product', 'brands' => 'Bosch, Michelin'],
            ['name' => 'Car Care Combo', 'type' => 'Physical Product', 'brands' => '3M, Wavex'],
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

echo "Successfully seeded Data for Packaging Material Store, Safety Equipment Store, and Car Dealer.\n";
