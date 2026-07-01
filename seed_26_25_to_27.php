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
        'sub_category' => 'Vape & Tobacco Shop',
        'has_business_type' => 0,
        'products' => [
            ['name' => 'Cigarettes', 'type' => 'Physical Product', 'brands' => 'Gold Flake, Classic, Marlboro'],
            ['name' => 'Premium Cigarettes', 'type' => 'Physical Product', 'brands' => 'Marlboro, Dunhill'],
            ['name' => 'Menthol Cigarettes', 'type' => 'Physical Product', 'brands' => 'Marlboro, Newport'],
            ['name' => 'Slim Cigarettes', 'type' => 'Physical Product', 'brands' => 'Vogue'],
            ['name' => 'Cigars', 'type' => 'Physical Product', 'brands' => 'Cohiba, Romeo y Julieta'],
            ['name' => 'Cigarillos', 'type' => 'Physical Product', 'brands' => 'Café Crème'],
            ['name' => 'Rolling Tobacco', 'type' => 'Physical Product', 'brands' => 'Drum, Golden Virginia'],
            ['name' => 'Pipe Tobacco', 'type' => 'Physical Product', 'brands' => 'Captain Black'],
            ['name' => 'Chewing Tobacco', 'type' => 'Physical Product', 'brands' => 'Chaini Khaini, Baba'],
            ['name' => 'Snuff Tobacco', 'type' => 'Physical Product', 'brands' => 'Various Brands'],
            ['name' => 'Hookah Tobacco (Shisha)', 'type' => 'Physical Product', 'brands' => 'Afzal, Al Fakher, Starbuzz'],
            ['name' => 'Herbal Shisha', 'type' => 'Physical Product', 'brands' => 'Soex'],
            ['name' => 'Hookah Charcoal', 'type' => 'Physical Product', 'brands' => 'CocoYaya, Cocobrico'],
            ['name' => 'Hookah Bowl', 'type' => 'Physical Product', 'brands' => 'CocoYaya'],
            ['name' => 'Hookah Pipe', 'type' => 'Physical Product', 'brands' => 'CocoYaya, Amy Deluxe'],
            ['name' => 'Hookah Hose', 'type' => 'Physical Product', 'brands' => 'CocoYaya'],
            ['name' => 'Hookah Mouth Tips', 'type' => 'Physical Product', 'brands' => 'CocoYaya'],
            ['name' => 'Hookah Cleaning Brush', 'type' => 'Physical Product', 'brands' => 'CocoYaya'],
            ['name' => 'Disposable Vape', 'type' => 'Physical Product', 'brands' => 'Elf Bar, HQD'],
            ['name' => 'Rechargeable Vape Device', 'type' => 'Physical Product', 'brands' => 'Vaporesso, Voopoo, GeekVape'],
            ['name' => 'Pod System', 'type' => 'Physical Product', 'brands' => 'Uwell, Vaporesso'],
            ['name' => 'Vape Mod', 'type' => 'Physical Product', 'brands' => 'GeekVape, SMOK'],
            ['name' => 'Replacement Pods', 'type' => 'Physical Product', 'brands' => 'Vaporesso, Uwell'],
            ['name' => 'Replacement Coils', 'type' => 'Physical Product', 'brands' => 'GeekVape, SMOK'],
            ['name' => 'Vape Tank', 'type' => 'Physical Product', 'brands' => 'GeekVape'],
            ['name' => 'Drip Tip', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Vape Battery', 'type' => 'Physical Product', 'brands' => 'Sony, Samsung'],
            ['name' => 'Battery Charger', 'type' => 'Physical Product', 'brands' => 'Nitecore'],
            ['name' => 'Carry Case', 'type' => 'Physical Product', 'brands' => 'Vaporesso'],
            ['name' => 'Rolling Papers', 'type' => 'Physical Product', 'brands' => 'RAW, OCB'],
            ['name' => 'Filter Tips', 'type' => 'Physical Product', 'brands' => 'RAW'],
            ['name' => 'Rolling Machine', 'type' => 'Physical Product', 'brands' => 'RAW'],
            ['name' => 'Cigarette Case', 'type' => 'Physical Product', 'brands' => 'Zippo'],
            ['name' => 'Cigar Cutter', 'type' => 'Physical Product', 'brands' => 'Colibri'],
            ['name' => 'Cigar Punch', 'type' => 'Physical Product', 'brands' => 'Colibri'],
            ['name' => 'Cigar Humidor', 'type' => 'Physical Product', 'brands' => 'Prestige Import Group'],
            ['name' => 'Humidor Hygrometer', 'type' => 'Physical Product', 'brands' => 'Xikar'],
            ['name' => 'Lighter', 'type' => 'Physical Product', 'brands' => 'Zippo, Clipper'],
            ['name' => 'Torch Lighter', 'type' => 'Physical Product', 'brands' => 'Zippo'],
            ['name' => 'Ashtray', 'type' => 'Physical Product', 'brands' => 'Zippo'],
            ['name' => 'Pocket Ashtray', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Tobacco Pouch', 'type' => 'Physical Product', 'brands' => 'RAW'],
            ['name' => 'Pipe', 'type' => 'Physical Product', 'brands' => 'Peterson'],
            ['name' => 'Pipe Cleaners', 'type' => 'Physical Product', 'brands' => 'BJ Long'],
            ['name' => 'Pipe Tool', 'type' => 'Physical Product', 'brands' => 'Peterson'],
            ['name' => 'Cigar Case', 'type' => 'Physical Product', 'brands' => 'Xikar'],
            ['name' => 'Tobacco Storage Jar', 'type' => 'Physical Product', 'brands' => 'RAW'],
            ['name' => 'Odor Proof Bag', 'type' => 'Physical Product', 'brands' => 'Revelry'],
            ['name' => 'Smoking Accessories Kit', 'type' => 'Physical Product', 'brands' => 'RAW'],
            ['name' => 'Gift Set', 'type' => 'Physical Product', 'brands' => 'Zippo'],
            ['name' => 'Premium Lighter Gift Box', 'type' => 'Physical Product', 'brands' => 'Zippo'],
            ['name' => 'Hookah Gift Set', 'type' => 'Physical Product', 'brands' => 'CocoYaya'],
            ['name' => 'Cigar Gift Set', 'type' => 'Physical Product', 'brands' => 'Cohiba'],
            ['name' => 'Display Humidor', 'type' => 'Physical Product', 'brands' => 'Prestige Import Group'],
            ['name' => 'Tobacco Grinder', 'type' => 'Physical Product', 'brands' => 'RAW'],
            ['name' => 'Rolling Tray', 'type' => 'Physical Product', 'brands' => 'RAW'],
        ]
    ],
    [
        'category' => 'Retail',
        'sub_category' => 'Antique & Collectibles Store',
        'has_business_type' => 0,
        'products' => [
            ['name' => 'Antique Furniture', 'type' => 'Physical Product', 'brands' => 'Local Antique Dealers'],
            ['name' => 'Vintage Chair', 'type' => 'Physical Product', 'brands' => 'Local Antique Dealers'],
            ['name' => 'Antique Table', 'type' => 'Physical Product', 'brands' => 'Local Antique Dealers'],
            ['name' => 'Antique Cabinet', 'type' => 'Physical Product', 'brands' => 'Local Antique Dealers'],
            ['name' => 'Wooden Chest', 'type' => 'Physical Product', 'brands' => 'Local Antique Dealers'],
            ['name' => 'Vintage Clock', 'type' => 'Physical Product', 'brands' => 'Seiko, Ajanta'],
            ['name' => 'Antique Wall Clock', 'type' => 'Physical Product', 'brands' => 'Local Antique Dealers'],
            ['name' => 'Pocket Watch', 'type' => 'Physical Product', 'brands' => 'Titan Vintage, Seiko'],
            ['name' => 'Antique Mirror', 'type' => 'Physical Product', 'brands' => 'Local Antique Dealers'],
            ['name' => 'Brass Statue', 'type' => 'Physical Product', 'brands' => 'Local Artisans'],
            ['name' => 'Bronze Statue', 'type' => 'Physical Product', 'brands' => 'Local Artisans'],
            ['name' => 'Marble Sculpture', 'type' => 'Physical Product', 'brands' => 'Local Artisans'],
            ['name' => 'Wooden Sculpture', 'type' => 'Physical Product', 'brands' => 'Local Artisans'],
            ['name' => 'Antique Idol', 'type' => 'Physical Product', 'brands' => 'Local Artisans'],
            ['name' => 'Vintage Lamps', 'type' => 'Physical Product', 'brands' => 'Local Antique Dealers'],
            ['name' => 'Oil Lamp', 'type' => 'Physical Product', 'brands' => 'Local Antique Dealers'],
            ['name' => 'Hurricane Lantern', 'type' => 'Physical Product', 'brands' => 'Local Antique Dealers'],
            ['name' => 'Gramophone', 'type' => 'Physical Product', 'brands' => 'HMV'],
            ['name' => 'Vinyl Records', 'type' => 'Physical Product', 'brands' => 'Sony Music, Universal Music'],
            ['name' => 'Record Player', 'type' => 'Physical Product', 'brands' => 'Audio-Technica'],
            ['name' => 'Typewriter', 'type' => 'Physical Product', 'brands' => 'Godrej, Remington'],
            ['name' => 'Vintage Camera', 'type' => 'Physical Product', 'brands' => 'Canon, Nikon'],
            ['name' => 'Film Camera', 'type' => 'Physical Product', 'brands' => 'Canon, Nikon'],
            ['name' => 'Binoculars', 'type' => 'Physical Product', 'brands' => 'Nikon'],
            ['name' => 'Telescope', 'type' => 'Physical Product', 'brands' => 'Celestron'],
            ['name' => 'Antique Coins', 'type' => 'Physical Product', 'brands' => 'Local Collectors'],
            ['name' => 'Rare Coins', 'type' => 'Physical Product', 'brands' => 'Local Collectors'],
            ['name' => 'Coin Collection Album', 'type' => 'Physical Product', 'brands' => 'Lighthouse'],
            ['name' => 'Commemorative Coins', 'type' => 'Physical Product', 'brands' => 'Government Mint'],
            ['name' => 'Antique Currency Notes', 'type' => 'Physical Product', 'brands' => 'Local Collectors'],
            ['name' => 'Stamp Collection', 'type' => 'Physical Product', 'brands' => 'India Post'],
            ['name' => 'Stamp Album', 'type' => 'Physical Product', 'brands' => 'Lighthouse'],
            ['name' => 'Postcards', 'type' => 'Physical Product', 'brands' => 'Local Collectors'],
            ['name' => 'Vintage Maps', 'type' => 'Physical Product', 'brands' => 'National Geographic'],
            ['name' => 'Historical Documents', 'type' => 'Physical Product', 'brands' => 'Local Collectors'],
            ['name' => 'Old Books', 'type' => 'Physical Product', 'brands' => 'Penguin Classics'],
            ['name' => 'First Edition Books', 'type' => 'Physical Product', 'brands' => 'HarperCollins'],
            ['name' => 'Rare Manuscripts', 'type' => 'Physical Product', 'brands' => 'Local Collectors'],
            ['name' => 'Vintage Comics', 'type' => 'Physical Product', 'brands' => 'DC, Marvel'],
            ['name' => 'Action Figure Collectibles', 'type' => 'Physical Product', 'brands' => 'Funko Pop, Hasbro'],
            ['name' => 'Limited Edition Figurines', 'type' => 'Physical Product', 'brands' => 'Funko Pop'],
            ['name' => 'Die-Cast Model Cars', 'type' => 'Physical Product', 'brands' => 'Hot Wheels, Maisto'],
            ['name' => 'Model Trains', 'type' => 'Physical Product', 'brands' => 'Hornby'],
            ['name' => 'Model Airplanes', 'type' => 'Physical Product', 'brands' => 'Revell'],
            ['name' => 'Ship Models', 'type' => 'Physical Product', 'brands' => 'Revell'],
            ['name' => 'Chess Collector Set', 'type' => 'Physical Product', 'brands' => 'StonKraft'],
            ['name' => 'Luxury Chess Set', 'type' => 'Physical Product', 'brands' => 'House of Staunton'],
            ['name' => 'Crystal Showpieces', 'type' => 'Physical Product', 'brands' => 'Swarovski'],
            ['name' => 'Crystal Figurines', 'type' => 'Physical Product', 'brands' => 'Swarovski'],
            ['name' => 'Porcelain Dolls', 'type' => 'Physical Product', 'brands' => 'Local Collectors'],
            ['name' => 'Ceramic Collectibles', 'type' => 'Physical Product', 'brands' => 'Local Artisans'],
            ['name' => 'Antique Pottery', 'type' => 'Physical Product', 'brands' => 'Local Artisans'],
            ['name' => 'Vintage Crockery', 'type' => 'Physical Product', 'brands' => 'Noritake'],
            ['name' => 'Silverware', 'type' => 'Physical Product', 'brands' => 'Silver Centre'],
            ['name' => 'Brass Utensils', 'type' => 'Physical Product', 'brands' => 'Local Artisans'],
            ['name' => 'Handcrafted Artifacts', 'type' => 'Physical Product', 'brands' => 'ExclusiveLane'],
            ['name' => 'Tribal Artifacts', 'type' => 'Physical Product', 'brands' => 'Local Artisans'],
            ['name' => 'Antique Jewelry', 'type' => 'Physical Product', 'brands' => 'Local Antique Dealers'],
            ['name' => 'Vintage Brooch', 'type' => 'Physical Product', 'brands' => 'Local Antique Dealers'],
            ['name' => 'Antique Necklace', 'type' => 'Physical Product', 'brands' => 'Local Antique Dealers'],
            ['name' => 'Decorative Swords (Display)', 'type' => 'Physical Product', 'brands' => 'Local Antique Dealers'],
            ['name' => 'Heritage Gift Box', 'type' => 'Physical Product', 'brands' => 'Local Antique Dealers'],
            ['name' => 'Collector Display Case', 'type' => 'Physical Product', 'brands' => 'IKEA'],
            ['name' => 'Coin Display Frame', 'type' => 'Physical Product', 'brands' => 'Lighthouse'],
            ['name' => 'Display Stand', 'type' => 'Physical Product', 'brands' => 'IKEA'],
        ]
    ],
    [
        'category' => 'Retail',
        'sub_category' => 'Home Decor Store',
        'has_business_type' => 0,
        'products' => [
            ['name' => 'Wall Art', 'type' => 'Physical Product', 'brands' => 'Home Centre, ExclusiveLane'],
            ['name' => 'Canvas Painting', 'type' => 'Physical Product', 'brands' => 'Home Centre'],
            ['name' => 'Framed Artwork', 'type' => 'Physical Product', 'brands' => 'Home Centre'],
            ['name' => 'Wall Hanging', 'type' => 'Physical Product', 'brands' => 'ExclusiveLane'],
            ['name' => 'Wall Clock', 'type' => 'Physical Product', 'brands' => 'Ajanta, Titan'],
            ['name' => 'Decorative Mirror', 'type' => 'Physical Product', 'brands' => 'IKEA, Home Centre'],
            ['name' => 'Photo Frames', 'type' => 'Physical Product', 'brands' => 'Home Centre, Cello'],
            ['name' => 'Floating Shelves', 'type' => 'Physical Product', 'brands' => 'IKEA'],
            ['name' => 'Wall Shelves', 'type' => 'Physical Product', 'brands' => 'IKEA'],
            ['name' => 'Wooden Wall Decor', 'type' => 'Physical Product', 'brands' => 'ExclusiveLane'],
            ['name' => 'Metal Wall Decor', 'type' => 'Physical Product', 'brands' => 'Home Centre'],
            ['name' => 'Decorative Showpiece', 'type' => 'Physical Product', 'brands' => 'ExclusiveLane, Home Centre'],
            ['name' => 'Resin Figurines', 'type' => 'Physical Product', 'brands' => 'ExclusiveLane'],
            ['name' => 'Crystal Showpiece', 'type' => 'Physical Product', 'brands' => 'Swarovski'],
            ['name' => 'Ceramic Vase', 'type' => 'Physical Product', 'brands' => 'Home Centre'],
            ['name' => 'Glass Vase', 'type' => 'Physical Product', 'brands' => 'IKEA'],
            ['name' => 'Flower Vase', 'type' => 'Physical Product', 'brands' => 'Home Centre'],
            ['name' => 'Artificial Flowers', 'type' => 'Physical Product', 'brands' => 'Home Centre'],
            ['name' => 'Artificial Plants', 'type' => 'Physical Product', 'brands' => 'IKEA'],
            ['name' => 'Indoor Plants', 'type' => 'Physical Product', 'brands' => 'Ugaoo'],
            ['name' => 'Decorative Planters', 'type' => 'Physical Product', 'brands' => 'Ugaoo'],
            ['name' => 'Table Decor', 'type' => 'Physical Product', 'brands' => 'Home Centre'],
            ['name' => 'Center Table Decor', 'type' => 'Physical Product', 'brands' => 'Home Centre'],
            ['name' => 'Candle Holder', 'type' => 'Physical Product', 'brands' => 'IKEA'],
            ['name' => 'Scented Candles', 'type' => 'Physical Product', 'brands' => 'Miniso, IKEA'],
            ['name' => 'Aroma Diffuser', 'type' => 'Physical Product', 'brands' => 'Miniso'],
            ['name' => 'Essential Oils', 'type' => 'Physical Product', 'brands' => 'Soulflower'],
            ['name' => 'Incense Holder', 'type' => 'Physical Product', 'brands' => 'ExclusiveLane'],
            ['name' => 'Wind Chimes', 'type' => 'Physical Product', 'brands' => 'Home Centre'],
            ['name' => 'Dream Catcher', 'type' => 'Physical Product', 'brands' => 'Local Brands'],
            ['name' => 'Decorative Tray', 'type' => 'Physical Product', 'brands' => 'Home Centre'],
            ['name' => 'Table Lamp', 'type' => 'Physical Product', 'brands' => 'Philips, Wipro'],
            ['name' => 'Floor Lamp', 'type' => 'Physical Product', 'brands' => 'IKEA'],
            ['name' => 'Pendant Light', 'type' => 'Physical Product', 'brands' => 'Philips'],
            ['name' => 'Fairy Lights', 'type' => 'Physical Product', 'brands' => 'Philips, Wipro'],
            ['name' => 'LED Decorative Lights', 'type' => 'Physical Product', 'brands' => 'Philips'],
            ['name' => 'Cushion Covers', 'type' => 'Physical Product', 'brands' => 'D\'Decor, Home Centre'],
            ['name' => 'Cushions', 'type' => 'Physical Product', 'brands' => 'Home Centre'],
            ['name' => 'Throws & Blankets', 'type' => 'Physical Product', 'brands' => 'IKEA'],
            ['name' => 'Rugs', 'type' => 'Physical Product', 'brands' => 'IKEA, D\'Decor'],
            ['name' => 'Carpets', 'type' => 'Physical Product', 'brands' => 'Jaipur Rugs'],
            ['name' => 'Doormats', 'type' => 'Physical Product', 'brands' => 'Home Centre'],
            ['name' => 'Curtains', 'type' => 'Physical Product', 'brands' => 'D\'Decor, Home Centre'],
            ['name' => 'Curtain Rods', 'type' => 'Physical Product', 'brands' => 'IKEA'],
            ['name' => 'Blinds', 'type' => 'Physical Product', 'brands' => 'Vista'],
            ['name' => 'Bed Runner', 'type' => 'Physical Product', 'brands' => 'D\'Decor'],
            ['name' => 'Table Runner', 'type' => 'Physical Product', 'brands' => 'Home Centre'],
            ['name' => 'Dining Table Mat', 'type' => 'Physical Product', 'brands' => 'Home Centre'],
            ['name' => 'Coasters', 'type' => 'Physical Product', 'brands' => 'Home Centre'],
            ['name' => 'Decorative Bowls', 'type' => 'Physical Product', 'brands' => 'Home Centre'],
            ['name' => 'Fruit Basket', 'type' => 'Physical Product', 'brands' => 'Home Centre'],
            ['name' => 'Storage Basket', 'type' => 'Physical Product', 'brands' => 'IKEA'],
            ['name' => 'Wooden Crates', 'type' => 'Physical Product', 'brands' => 'IKEA'],
            ['name' => 'Book Ends', 'type' => 'Physical Product', 'brands' => 'IKEA'],
            ['name' => 'Magazine Holder', 'type' => 'Physical Product', 'brands' => 'IKEA'],
            ['name' => 'Jewelry Box', 'type' => 'Physical Product', 'brands' => 'Home Centre'],
            ['name' => 'Key Holder', 'type' => 'Physical Product', 'brands' => 'ExclusiveLane'],
            ['name' => 'Welcome Name Plate', 'type' => 'Physical Product', 'brands' => 'ExclusiveLane'],
            ['name' => 'Religious Decor', 'type' => 'Physical Product', 'brands' => 'ExclusiveLane'],
            ['name' => 'Brass Idol', 'type' => 'Physical Product', 'brands' => 'Local Artisans'],
            ['name' => 'Fountain Decor', 'type' => 'Physical Product', 'brands' => 'Home Centre'],
            ['name' => 'Aquarium Decor', 'type' => 'Physical Product', 'brands' => 'Venus Aqua'],
            ['name' => 'Home Fragrance Set', 'type' => 'Physical Product', 'brands' => 'Miniso'],
            ['name' => 'Decorative Gift Set', 'type' => 'Physical Product', 'brands' => 'Home Centre'],
            ['name' => 'Premium Home Decor Combo', 'type' => 'Physical Product', 'brands' => 'Home Centre, IKEA'],
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

echo "Successfully seeded Data for Vape & Tobacco Shop, Antique & Collectibles Store, and Home Decor Store.\n";
