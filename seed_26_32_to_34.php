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
        'sub_category' => 'Sanitaryware Store',
        'has_business_type' => 0,
        'products' => [
            ['name' => 'One Piece Closet', 'type' => 'Physical Product', 'brands' => 'Cera, Hindware, Parryware, Jaquar'],
            ['name' => 'Two Piece Closet', 'type' => 'Physical Product', 'brands' => 'Cera, Hindware'],
            ['name' => 'Wall Hung Closet', 'type' => 'Physical Product', 'brands' => 'Jaquar, Cera, Kohler'],
            ['name' => 'Floor Mounted Closet', 'type' => 'Physical Product', 'brands' => 'Hindware, Parryware'],
            ['name' => 'Smart Toilet', 'type' => 'Physical Product', 'brands' => 'Kohler, TOTO, Jaquar'],
            ['name' => 'Wash Basin', 'type' => 'Physical Product', 'brands' => 'Cera, Hindware, Parryware'],
            ['name' => 'Counter Top Basin', 'type' => 'Physical Product', 'brands' => 'Cera, Kohler'],
            ['name' => 'Table Top Basin', 'type' => 'Physical Product', 'brands' => 'Jaquar, Cera'],
            ['name' => 'Wall Hung Basin', 'type' => 'Physical Product', 'brands' => 'Hindware, Parryware'],
            ['name' => 'Pedestal Basin', 'type' => 'Physical Product', 'brands' => 'Cera, Hindware'],
            ['name' => 'Semi Pedestal Basin', 'type' => 'Physical Product', 'brands' => 'Cera'],
            ['name' => 'Under Counter Basin', 'type' => 'Physical Product', 'brands' => 'Kohler, Jaquar'],
            ['name' => 'Art Basin', 'type' => 'Physical Product', 'brands' => 'Cera'],
            ['name' => 'Urinal', 'type' => 'Physical Product', 'brands' => 'Cera, Hindware'],
            ['name' => 'Kids Urinal', 'type' => 'Physical Product', 'brands' => 'Cera'],
            ['name' => 'Squatting Pan', 'type' => 'Physical Product', 'brands' => 'Hindware'],
            ['name' => 'Flush Tank', 'type' => 'Physical Product', 'brands' => 'Cera, Parryware'],
            ['name' => 'Concealed Cistern', 'type' => 'Physical Product', 'brands' => 'Geberit, Jaquar'],
            ['name' => 'Flush Plate', 'type' => 'Physical Product', 'brands' => 'Geberit'],
            ['name' => 'Toilet Seat Cover', 'type' => 'Physical Product', 'brands' => 'Cera, Hindware'],
            ['name' => 'Soft Close Seat Cover', 'type' => 'Physical Product', 'brands' => 'Jaquar'],
            ['name' => 'Bidet Seat', 'type' => 'Physical Product', 'brands' => 'TOTO'],
            ['name' => 'Health Faucet', 'type' => 'Physical Product', 'brands' => 'Jaquar, Cera'],
            ['name' => 'Hand Shower', 'type' => 'Physical Product', 'brands' => 'Jaquar'],
            ['name' => 'Overhead Shower', 'type' => 'Physical Product', 'brands' => 'Kohler, Jaquar'],
            ['name' => 'Rain Shower', 'type' => 'Physical Product', 'brands' => 'Jaquar, Kohler'],
            ['name' => 'Shower Panel', 'type' => 'Physical Product', 'brands' => 'Jaquar'],
            ['name' => 'Shower Arm', 'type' => 'Physical Product', 'brands' => 'Jaquar'],
            ['name' => 'Shower Mixer', 'type' => 'Physical Product', 'brands' => 'Jaquar, Kohler'],
            ['name' => 'Basin Mixer', 'type' => 'Physical Product', 'brands' => 'Jaquar'],
            ['name' => 'Sink Mixer', 'type' => 'Physical Product', 'brands' => 'Jaquar'],
            ['name' => 'Pillar Cock', 'type' => 'Physical Product', 'brands' => 'Jaquar, Hindware'],
            ['name' => 'Bib Cock', 'type' => 'Physical Product', 'brands' => 'Jaquar, Cera'],
            ['name' => 'Angle Cock', 'type' => 'Physical Product', 'brands' => 'Jaquar'],
            ['name' => 'Stop Cock', 'type' => 'Physical Product', 'brands' => 'Jaquar'],
            ['name' => 'Sensor Faucet', 'type' => 'Physical Product', 'brands' => 'Jaquar, Kohler'],
            ['name' => 'Touchless Faucet', 'type' => 'Physical Product', 'brands' => 'Kohler'],
            ['name' => 'Soap Dispenser', 'type' => 'Physical Product', 'brands' => 'Jaquar, Cera'],
            ['name' => 'Soap Dish', 'type' => 'Physical Product', 'brands' => 'Cera'],
            ['name' => 'Towel Ring', 'type' => 'Physical Product', 'brands' => 'Jaquar'],
            ['name' => 'Towel Rod', 'type' => 'Physical Product', 'brands' => 'Jaquar'],
            ['name' => 'Towel Rack', 'type' => 'Physical Product', 'brands' => 'Jaquar'],
            ['name' => 'Robe Hook', 'type' => 'Physical Product', 'brands' => 'Cera'],
            ['name' => 'Toilet Paper Holder', 'type' => 'Physical Product', 'brands' => 'Jaquar'],
            ['name' => 'Toothbrush Holder', 'type' => 'Physical Product', 'brands' => 'Cera'],
            ['name' => 'Tumbler Holder', 'type' => 'Physical Product', 'brands' => 'Jaquar'],
            ['name' => 'Bathroom Shelf', 'type' => 'Physical Product', 'brands' => 'Jaquar'],
            ['name' => 'Glass Shelf', 'type' => 'Physical Product', 'brands' => 'Cera'],
            ['name' => 'Corner Shelf', 'type' => 'Physical Product', 'brands' => 'Jaquar'],
            ['name' => 'Mirror Cabinet', 'type' => 'Physical Product', 'brands' => 'Cera'],
            ['name' => 'LED Bathroom Mirror', 'type' => 'Physical Product', 'brands' => 'Jaquar'],
            ['name' => 'Vanity Cabinet', 'type' => 'Physical Product', 'brands' => 'Cera, Hindware'],
            ['name' => 'Bathroom Vanity', 'type' => 'Physical Product', 'brands' => 'Jaquar, Kohler'],
            ['name' => 'Floor Drain', 'type' => 'Physical Product', 'brands' => 'Jaquar'],
            ['name' => 'Channel Drain', 'type' => 'Physical Product', 'brands' => 'Jaquar'],
            ['name' => 'Bottle Trap', 'type' => 'Physical Product', 'brands' => 'Jaquar'],
            ['name' => 'Waste Coupling', 'type' => 'Physical Product', 'brands' => 'Cera'],
            ['name' => 'Flexible Hose', 'type' => 'Physical Product', 'brands' => 'Jaquar'],
            ['name' => 'PTMT Tap', 'type' => 'Physical Product', 'brands' => 'Prayag'],
            ['name' => 'Bathroom Accessories Set', 'type' => 'Physical Product', 'brands' => 'Jaquar, Cera'],
            ['name' => 'Bathtub', 'type' => 'Physical Product', 'brands' => 'Kohler, Jaquar'],
            ['name' => 'Freestanding Bathtub', 'type' => 'Physical Product', 'brands' => 'Kohler'],
            ['name' => 'Jacuzzi Bathtub', 'type' => 'Physical Product', 'brands' => 'Jaquar'],
            ['name' => 'Shower Enclosure', 'type' => 'Physical Product', 'brands' => 'Jaquar, Saint-Gobain'],
            ['name' => 'Shower Curtain', 'type' => 'Physical Product', 'brands' => 'IKEA'],
            ['name' => 'Glass Partition', 'type' => 'Physical Product', 'brands' => 'Saint-Gobain'],
            ['name' => 'Grab Bar', 'type' => 'Physical Product', 'brands' => 'Jaquar'],
            ['name' => 'Bathroom Stool', 'type' => 'Physical Product', 'brands' => 'IKEA'],
            ['name' => 'Sanitaryware Cleaning Liquid', 'type' => 'Physical Product', 'brands' => 'Harpic, Cera'],
            ['name' => 'Bathroom Care Kit', 'type' => 'Physical Product', 'brands' => 'Jaquar'],
            ['name' => 'Sanitaryware Combo Set', 'type' => 'Physical Product', 'brands' => 'Cera, Hindware, Jaquar'],
        ]
    ],
    [
        'category' => 'Retail',
        'sub_category' => 'Tiles & Marble Store',
        'has_business_type' => 0,
        'products' => [
            ['name' => 'Vitrified Tiles', 'type' => 'Physical Product', 'brands' => 'Kajaria, Somany, Johnson'],
            ['name' => 'Double Charge Vitrified Tiles', 'type' => 'Physical Product', 'brands' => 'Kajaria, Simpolo'],
            ['name' => 'Glazed Vitrified Tiles (GVT)', 'type' => 'Physical Product', 'brands' => 'Kajaria, Somany'],
            ['name' => 'Polished Glazed Vitrified Tiles (PGVT)', 'type' => 'Physical Product', 'brands' => 'Simpolo, Kajaria'],
            ['name' => 'Ceramic Floor Tiles', 'type' => 'Physical Product', 'brands' => 'Kajaria, Orientbell'],
            ['name' => 'Ceramic Wall Tiles', 'type' => 'Physical Product', 'brands' => 'Kajaria, Somany'],
            ['name' => 'Porcelain Tiles', 'type' => 'Physical Product', 'brands' => 'Johnson, Simpolo'],
            ['name' => 'Digital Wall Tiles', 'type' => 'Physical Product', 'brands' => 'Kajaria'],
            ['name' => 'Elevation Tiles', 'type' => 'Physical Product', 'brands' => 'Simpolo, Johnson'],
            ['name' => 'Subway Tiles', 'type' => 'Physical Product', 'brands' => 'Kajaria'],
            ['name' => 'Mosaic Tiles', 'type' => 'Physical Product', 'brands' => 'Bisazza, Kajaria'],
            ['name' => 'Designer Tiles', 'type' => 'Physical Product', 'brands' => 'Simpolo, Orientbell'],
            ['name' => 'Wooden Finish Tiles', 'type' => 'Physical Product', 'brands' => 'Kajaria'],
            ['name' => 'Marble Finish Tiles', 'type' => 'Physical Product', 'brands' => 'Kajaria, Simpolo'],
            ['name' => 'Stone Finish Tiles', 'type' => 'Physical Product', 'brands' => 'Johnson'],
            ['name' => 'Outdoor Tiles', 'type' => 'Physical Product', 'brands' => 'Kajaria'],
            ['name' => 'Parking Tiles', 'type' => 'Physical Product', 'brands' => 'Johnson'],
            ['name' => 'Anti-Skid Tiles', 'type' => 'Physical Product', 'brands' => 'Kajaria, Somany'],
            ['name' => 'Rustic Tiles', 'type' => 'Physical Product', 'brands' => 'Simpolo'],
            ['name' => 'Large Slab Tiles', 'type' => 'Physical Product', 'brands' => 'Simpolo, Italica'],
            ['name' => 'Quartz Tiles', 'type' => 'Physical Product', 'brands' => 'Johnson'],
            ['name' => 'Granite Slabs', 'type' => 'Physical Product', 'brands' => 'Pokarna, RK Marble'],
            ['name' => 'Granite Tiles', 'type' => 'Physical Product', 'brands' => 'RK Marble'],
            ['name' => 'White Marble', 'type' => 'Physical Product', 'brands' => 'RK Marble'],
            ['name' => 'Italian Marble', 'type' => 'Physical Product', 'brands' => 'RK Marble'],
            ['name' => 'Makrana Marble', 'type' => 'Physical Product', 'brands' => 'RK Marble'],
            ['name' => 'Imported Marble', 'type' => 'Physical Product', 'brands' => 'RK Marble'],
            ['name' => 'Green Marble', 'type' => 'Physical Product', 'brands' => 'RK Marble'],
            ['name' => 'Black Marble', 'type' => 'Physical Product', 'brands' => 'RK Marble'],
            ['name' => 'Onyx Marble', 'type' => 'Physical Product', 'brands' => 'RK Marble'],
            ['name' => 'Sandstone', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Kota Stone', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Slate Stone', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Quartz Stone', 'type' => 'Physical Product', 'brands' => 'KalingaStone'],
            ['name' => 'Engineered Stone', 'type' => 'Physical Product', 'brands' => 'KalingaStone'],
            ['name' => 'Stone Cladding', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Tile Adhesive', 'type' => 'Physical Product', 'brands' => 'Roff, MYK LATICRETE'],
            ['name' => 'Tile Grout', 'type' => 'Physical Product', 'brands' => 'MYK LATICRETE, Roff'],
            ['name' => 'Epoxy Grout', 'type' => 'Physical Product', 'brands' => 'MYK LATICRETE'],
            ['name' => 'Tile Spacer', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Tile Leveling System', 'type' => 'Physical Product', 'brands' => 'Raimondi'],
            ['name' => 'Tile Trim', 'type' => 'Physical Product', 'brands' => 'Schluter'],
            ['name' => 'Skirting Tiles', 'type' => 'Physical Product', 'brands' => 'Kajaria'],
            ['name' => 'Staircase Tiles', 'type' => 'Physical Product', 'brands' => 'Kajaria'],
            ['name' => 'Kitchen Countertop', 'type' => 'Physical Product', 'brands' => 'KalingaStone'],
            ['name' => 'Granite Kitchen Top', 'type' => 'Physical Product', 'brands' => 'RK Marble'],
            ['name' => 'Marble Kitchen Top', 'type' => 'Physical Product', 'brands' => 'RK Marble'],
            ['name' => 'Bathroom Countertop', 'type' => 'Physical Product', 'brands' => 'KalingaStone'],
            ['name' => 'Window Sill Stone', 'type' => 'Physical Product', 'brands' => 'RK Marble'],
            ['name' => 'Wall Cladding Stone', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Cobbles', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Paving Stones', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Kerb Stone', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Tile Cleaner', 'type' => 'Physical Product', 'brands' => 'Roff'],
            ['name' => 'Marble Polish', 'type' => 'Physical Product', 'brands' => 'Akemi'],
            ['name' => 'Stone Sealer', 'type' => 'Physical Product', 'brands' => 'Akemi'],
            ['name' => 'Diamond Cutting Blade', 'type' => 'Physical Product', 'brands' => 'Bosch'],
            ['name' => 'Tile Cutter', 'type' => 'Physical Product', 'brands' => 'Bosch, Rubi'],
            ['name' => 'Marble Cutter', 'type' => 'Physical Product', 'brands' => 'Bosch'],
            ['name' => 'Tile Installation Kit', 'type' => 'Physical Product', 'brands' => 'Rubi'],
            ['name' => 'Tile Display Stand', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Tile Sample Book', 'type' => 'Physical Product', 'brands' => 'Kajaria'],
            ['name' => 'Tile & Marble Combo Pack', 'type' => 'Physical Product', 'brands' => 'Kajaria, Somany'],
        ]
    ],
    [
        'category' => 'Retail',
        'sub_category' => 'Furniture Store',
        'has_business_type' => 0,
        'products' => [
            ['name' => 'Sofa Set', 'type' => 'Physical Product', 'brands' => 'Godrej Interio, Nilkamal, Durian'],
            ['name' => 'Recliner Sofa', 'type' => 'Physical Product', 'brands' => 'La-Z-Boy, Durian'],
            ['name' => 'L Shape Sofa', 'type' => 'Physical Product', 'brands' => 'Durian, Urban Ladder'],
            ['name' => 'Sofa Cum Bed', 'type' => 'Physical Product', 'brands' => 'Nilkamal, Wakefit'],
            ['name' => 'Wooden Sofa', 'type' => 'Physical Product', 'brands' => 'Godrej Interio'],
            ['name' => 'Coffee Table', 'type' => 'Physical Product', 'brands' => 'IKEA, Home Centre'],
            ['name' => 'Center Table', 'type' => 'Physical Product', 'brands' => 'Home Centre, Nilkamal'],
            ['name' => 'Side Table', 'type' => 'Physical Product', 'brands' => 'IKEA'],
            ['name' => 'Console Table', 'type' => 'Physical Product', 'brands' => 'Home Centre'],
            ['name' => 'TV Unit', 'type' => 'Physical Product', 'brands' => 'IKEA, Godrej Interio'],
            ['name' => 'TV Cabinet', 'type' => 'Physical Product', 'brands' => 'Nilkamal'],
            ['name' => 'Entertainment Unit', 'type' => 'Physical Product', 'brands' => 'Home Centre'],
            ['name' => 'Dining Table', 'type' => 'Physical Product', 'brands' => 'Godrej Interio, Durian'],
            ['name' => '4 Seater Dining Table', 'type' => 'Physical Product', 'brands' => 'Nilkamal'],
            ['name' => '6 Seater Dining Table', 'type' => 'Physical Product', 'brands' => 'Godrej Interio'],
            ['name' => 'Dining Chair', 'type' => 'Physical Product', 'brands' => 'IKEA, Nilkamal'],
            ['name' => 'Bar Stool', 'type' => 'Physical Product', 'brands' => 'IKEA'],
            ['name' => 'Bedroom Bed', 'type' => 'Physical Product', 'brands' => 'Wakefit, Godrej Interio'],
            ['name' => 'Single Bed', 'type' => 'Physical Product', 'brands' => 'Nilkamal'],
            ['name' => 'Double Bed', 'type' => 'Physical Product', 'brands' => 'Godrej Interio'],
            ['name' => 'Queen Size Bed', 'type' => 'Physical Product', 'brands' => 'Wakefit'],
            ['name' => 'King Size Bed', 'type' => 'Physical Product', 'brands' => 'Wakefit'],
            ['name' => 'Hydraulic Bed', 'type' => 'Physical Product', 'brands' => 'Durian'],
            ['name' => 'Bunk Bed', 'type' => 'Physical Product', 'brands' => 'IKEA'],
            ['name' => 'Mattress', 'type' => 'Physical Product', 'brands' => 'Sleepwell, Wakefit, Kurlon'],
            ['name' => 'Pillow', 'type' => 'Physical Product', 'brands' => 'Sleepwell, Wakefit'],
            ['name' => 'Bedside Table', 'type' => 'Physical Product', 'brands' => 'IKEA'],
            ['name' => 'Wardrobe', 'type' => 'Physical Product', 'brands' => 'Godrej Interio, IKEA'],
            ['name' => 'Sliding Wardrobe', 'type' => 'Physical Product', 'brands' => 'Godrej Interio'],
            ['name' => 'Chest of Drawers', 'type' => 'Physical Product', 'brands' => 'IKEA'],
            ['name' => 'Dressing Table', 'type' => 'Physical Product', 'brands' => 'Godrej Interio'],
            ['name' => 'Study Table', 'type' => 'Physical Product', 'brands' => 'Nilkamal, IKEA'],
            ['name' => 'Computer Table', 'type' => 'Physical Product', 'brands' => 'Godrej Interio'],
            ['name' => 'Office Desk', 'type' => 'Physical Product', 'brands' => 'Featherlite, Godrej Interio'],
            ['name' => 'Office Chair', 'type' => 'Physical Product', 'brands' => 'Featherlite, Green Soul'],
            ['name' => 'Executive Chair', 'type' => 'Physical Product', 'brands' => 'Featherlite'],
            ['name' => 'Ergonomic Chair', 'type' => 'Physical Product', 'brands' => 'Green Soul, Featherlite'],
            ['name' => 'Gaming Chair', 'type' => 'Physical Product', 'brands' => 'Green Soul, Green Soul Monster'],
            ['name' => 'Plastic Chair', 'type' => 'Physical Product', 'brands' => 'Nilkamal, Cello'],
            ['name' => 'Folding Chair', 'type' => 'Physical Product', 'brands' => 'Nilkamal'],
            ['name' => 'Wooden Chair', 'type' => 'Physical Product', 'brands' => 'Godrej Interio'],
            ['name' => 'Rocking Chair', 'type' => 'Physical Product', 'brands' => 'IKEA'],
            ['name' => 'Bookshelf', 'type' => 'Physical Product', 'brands' => 'IKEA, Home Centre'],
            ['name' => 'Bookcase', 'type' => 'Physical Product', 'brands' => 'Godrej Interio'],
            ['name' => 'Display Cabinet', 'type' => 'Physical Product', 'brands' => 'Home Centre'],
            ['name' => 'Shoe Rack', 'type' => 'Physical Product', 'brands' => 'Nilkamal, IKEA'],
            ['name' => 'Storage Cabinet', 'type' => 'Physical Product', 'brands' => 'Godrej Interio'],
            ['name' => 'Kitchen Cabinet', 'type' => 'Physical Product', 'brands' => 'Godrej Interio'],
            ['name' => 'Modular Kitchen Unit', 'type' => 'Physical Product', 'brands' => 'Sleek Kitchens, Hafele'],
            ['name' => 'Bathroom Cabinet', 'type' => 'Physical Product', 'brands' => 'IKEA'],
            ['name' => 'Wall Shelf', 'type' => 'Physical Product', 'brands' => 'IKEA'],
            ['name' => 'Floating Shelf', 'type' => 'Physical Product', 'brands' => 'IKEA'],
            ['name' => 'Bean Bag', 'type' => 'Physical Product', 'brands' => 'Solimo, Sattva'],
            ['name' => 'Ottoman', 'type' => 'Physical Product', 'brands' => 'Home Centre'],
            ['name' => 'Pouffe', 'type' => 'Physical Product', 'brands' => 'Home Centre'],
            ['name' => 'Swing Chair', 'type' => 'Physical Product', 'brands' => 'Home Centre'],
            ['name' => 'Outdoor Furniture', 'type' => 'Physical Product', 'brands' => 'IKEA, Nilkamal'],
            ['name' => 'Garden Chair', 'type' => 'Physical Product', 'brands' => 'Nilkamal'],
            ['name' => 'Garden Table', 'type' => 'Physical Product', 'brands' => 'Nilkamal'],
            ['name' => 'Patio Set', 'type' => 'Physical Product', 'brands' => 'IKEA'],
            ['name' => 'Kids Furniture', 'type' => 'Physical Product', 'brands' => 'IKEA'],
            ['name' => 'Baby Crib', 'type' => 'Physical Product', 'brands' => 'R for Rabbit, Chicco'],
            ['name' => 'Study Chair', 'type' => 'Physical Product', 'brands' => 'Featherlite'],
            ['name' => 'Filing Cabinet', 'type' => 'Physical Product', 'brands' => 'Godrej Interio'],
            ['name' => 'Locker Cabinet', 'type' => 'Physical Product', 'brands' => 'Godrej'],
            ['name' => 'Furniture Care Polish', 'type' => 'Physical Product', 'brands' => 'Pledge'],
            ['name' => 'Furniture Cleaning Kit', 'type' => 'Physical Product', 'brands' => 'Pledge'],
            ['name' => 'Furniture Combo Set', 'type' => 'Physical Product', 'brands' => 'Godrej Interio, Nilkamal'],
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

echo "Successfully seeded Data for Sanitaryware Store, Tiles & Marble Store, and Furniture Store.\n";
