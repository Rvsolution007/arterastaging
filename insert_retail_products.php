<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$productMap = [
    // Mobile Accessories Store (Types)
    'Mobile Covers' => ['Silicone Mobile Cover', 'Leather Flip Case', 'Printed Back Cover'],
    'Tempered Glass' => ['9H Tempered Glass', 'Matte Screen Protector', 'Privacy Screen Guard'],
    'Chargers' => ['Fast Charging Adapter', 'USB-C Cable', 'Wireless Charger', 'Car Charger'],
    'Power Banks' => ['10000mAh Power Bank', '20000mAh Power Bank', 'MagSafe Power Bank'],
    'Bluetooth Devices' => ['Bluetooth Earbuds', 'Wireless Headphones', 'Bluetooth Neckband'],
    'Mobile Gadgets' => ['Mobile Tripod', 'Ring Light', 'Mobile Stand'],
    'Android Repair' => ['Android Screen Repair Service', 'Android Battery Replacement', 'Android Speaker Repair'],
    'Iphone Repair' => ['iPhone Screen Repair', 'iPhone Battery Replacement', 'Face ID Repair'],
    'Screen Replacement' => ['LCD Screen Replacement', 'OLED Screen Replacement'],
    'Battery Replacement' => ['Original Battery Replacement', 'Compatible Battery Replacement'],
    'Motherboard Repair' => ['Mobile Motherboard Repair', 'IC Replacement Service'],
    'Water Damage Repair' => ['Liquid Damage Cleaning', 'Water Damage Diagnostic'],
    'Business Laptops' => ['Dell Latitude', 'Lenovo ThinkPad', 'HP EliteBook'],
    'Gaming Laptops' => ['Asus ROG', 'Acer Predator', 'MSI Gaming Laptop'],
    'Student Laptops' => ['HP Pavilion', 'Dell Inspiron', 'Lenovo IdeaPad'],
    'Premium Laptop Store' => ['Apple MacBook Pro', 'Dell XPS', 'Microsoft Surface'],
    'Refurbished Laptops' => ['Refurbished Dell Laptops', 'Refurbished HP Laptops', 'Second Hand Laptops'],
    'Computer Store' => ['Desktop PC', 'All-in-One PC', 'Computer Monitors'],
    'Networking Company' => ['Networking Solutions', 'IT Infrastructure Setup'],
    'Routers' => ['Dual Band Wi-Fi Router', 'Mesh Wi-Fi System', '4G Wi-Fi Router'],
    'Switches' => ['8-Port Gigabit Switch', '16-Port Network Switch', 'PoE Switch'],
    'Firewalls' => ['Hardware Firewall', 'Network Security Appliance'],
    'Access Points' => ['Wireless Access Point', 'Ceiling Mount AP'],
    'Network Racks' => ['Server Racks', 'Wall Mount Racks'],
    'Network Accessories' => ['Cat6 Cables', 'Patch Panels', 'RJ45 Connectors'],
    'Cctv Company' => ['CCTV Installation Service', 'Security Consultation'],
    'Residential Cctv' => ['Home Wi-Fi Camera', 'Outdoor Bullet Camera', 'Dome Camera'],
    'Commercial Cctv' => ['PTZ Camera', 'DVR System', 'NVR System'],
    'Industrial Cctv' => ['Explosion Proof Cameras', 'Thermal Imaging Cameras'],
    'Ip Camera Solutions' => ['IP Dome Cameras', 'IP Bullet Cameras', 'NVR 16 Channel'],
    'Wireless Cctv' => ['Wireless Security Camera', 'Battery Powered Camera'],
    'Ai Surveillance' => ['Facial Recognition Camera', 'ANPR Cameras'],
    'Security System Company' => ['Biometric Access Control', 'Intrusion Alarm System'],
    'Mobile Repair Center' => ['Smartphone Repair', 'Tablet Repair'],
    'Refurbished Mobile Store' => ['Refurbished iPhones', 'Refurbished Android Phones'],
    'Second Hand Mobile Dealer' => ['Used iPhones', 'Used Samsung Phones'],
    'Online Mobile Seller' => ['Mobile E-commerce Service', 'Smartphone Sales'],
    'Wholesale Mobile Dealer' => ['Bulk Smartphone Supply', 'Wholesale Feature Phones'],
    'Premium Accessories Store' => ['Apple Original Accessories', 'Samsung Original Accessories'],
    'Charging Accessories Store' => ['Multi-port Chargers', 'Car Adapters'],
    'Mobile Protection Store' => ['Rugged Cases', 'Lens Protectors'],
    'Audio Accessories Store' => ['Wired Earphones', 'Audio Splitters'],

    // Computer Accessories Store (Types)
    'Printer Store' => ['Laser Printers', 'Inkjet Printers', 'Printer Ink Cartridges', 'Toner Cartridges'],
    'Networking Store' => ['Ethernet Cables', 'Wi-Fi Adapters', 'Network Hubs'],
    'Refurbished Computer Dealer' => ['Refurbished Desktops', 'Used Monitors'],
    'Corporate It Supplier' => ['Bulk IT Hardware', 'Corporate Software Licensing'],
    
    // Sports Store (Types)
    'Fitness Equipment Store' => ['Dumbbells', 'Yoga Mats', 'Treadmills', 'Resistance Bands'],
    'Cricket Store' => ['Cricket Bat', 'Cricket Ball', 'Batting Gloves', 'Cricket Pads', 'Cricket Helmet'],
    'Badminton Store' => ['Badminton Racket', 'Shuttlecocks', 'Badminton Shoes', 'Racket Grip'],
    'Football Store' => ['Football', 'Football Studs', 'Shin Guards', 'Goal Keeper Gloves'],
    'Outdoor Sports Store' => ['Camping Tents', 'Trekking Poles', 'Sleeping Bags'],
    'Gym Equipment Dealer' => ['Multi Gym Machine', 'Bench Press', 'Barbells'],
    'Kids Store' => ['Kids Tricycles', 'Baby Walkers', 'Kids Clothing'],
    'Pet Store' => ['Dog Food', 'Cat Food', 'Pet Toys', 'Pet Grooming Kit', 'Fish Food'],
    'Gift Store' => ['Personalized Mugs', 'Greeting Cards', 'Photo Frames', 'Soft Toys', 'Gift Wrapping'],

    // Single SubCategories (No Types)
    'Fruit Store' => ['Fresh Apples', 'Bananas', 'Oranges', 'Grapes', 'Pomegranates', 'Mangoes'],
    'Vegetable Store' => ['Potatoes', 'Onions', 'Tomatoes', 'Carrots', 'Green Chilies', 'Leafy Greens'],
    'Nursery & Plants Store' => ['Indoor Plants', 'Outdoor Plants', 'Flower Pots', 'Plant Fertilizers', 'Bonsai Trees'],
    'Toys Store' => ['Action Figures', 'Board Games', 'Plush Toys', 'Educational Toys', 'Remote Control Cars'],
    'Office Supplies Store' => ['Printer Paper', 'Pens & Markers', 'Staplers & Pins', 'Office Files & Folders'],
    'Musical Instruments Store' => ['Acoustic Guitar', 'Keyboard', 'Drum Set', 'Violin', 'Flute'],
    'Plastic Goods Store' => ['Plastic Chairs', 'Storage Containers', 'Plastic Buckets', 'Water Bottles'],
    'Disposable Products Store' => ['Paper Cups', 'Paper Plates', 'Tissue Papers', 'Garbage Bags'],
    'Packaging Materials Store' => ['Corrugated Boxes', 'Bubble Wrap', 'Packing Tape', 'Stretch Film'],
    'Religious Items Store' => ['Incense Sticks (Agarbatti)', 'Pooja Thali', 'Idols & Murtis', 'Diya', 'Sandalwood Powder'],
    'Fireworks Store' => ['Sparklers', 'Firecrackers', 'Flower Pots (Anar)', 'Rockets', 'Chakri'],
    'Home Appliances Store' => ['Refrigerator', 'Washing Machine', 'Microwave Oven', 'Air Conditioner', 'Mixer Grinder'],
    'Tv & Audio Store' => ['Smart TV', 'LED TV', 'Soundbar', 'Home Theater System', 'Bluetooth Speakers'],
    'Camera Store' => ['DSLR Camera', 'Mirrorless Camera', 'Camera Lenses', 'Tripods', 'Camera Bags'],
    'Gaming Store' => ['PlayStation Console', 'Xbox Console', 'Gaming Controllers', 'Video Games'],
    'Office Furniture Store' => ['Office Chair', 'Office Desk', 'Conference Table', 'Filing Cabinet'],
    'Plywood & Laminate Store' => ['Commercial Plywood', 'Marine Plywood', 'Decorative Laminates', 'Wood Veneers'],
    'Kitchenware Store' => ['Cookware Set', 'Cutlery Set', 'Dinner Set', 'Kitchen Utensils', 'Pressure Cooker'],
    'Modular Kitchen Store' => ['Modular Kitchen Cabinets', 'Kitchen Chimney', 'Pull-out Drawers', 'Kitchen Countertops'],
    'Building Materials Store' => ['Bricks', 'Sand', 'Steel TMT Bars', 'Gravel'],
    'Cement Store' => ['OPC Cement', 'PPC Cement', 'White Cement'],
    'Textile Store' => ['Cotton Fabric', 'Silk Fabric', 'Dress Materials', 'Suiting & Shirting'],
    'Garment Store' => ['Men\'s T-Shirts', 'Women\'s Tops', 'Kids Wear', 'Jeans'],
    'Readymade Garments Store' => ['Casual Shirts', 'Formal Trousers', 'Ethnic Kurtas', 'Western Dresses'],
    'Artificial Jewellery Store' => ['Imitation Necklaces', 'Artificial Bangles', 'Fashion Earrings', 'Anklets'],
    'Diamond Jewellery Store' => ['Diamond Rings', 'Diamond Pendants', 'Diamond Earrings', 'Diamond Necklaces'],
    'Silver Jewellery Store' => ['Silver Payal', 'Silver Chains', 'Silver Rings', 'Silver Coins'],
    'Gold Jewellery Store' => ['Gold Necklaces', 'Gold Bangles', 'Gold Rings', 'Gold Chains', 'Gold Mangalsutra'],
    'Health Supplements Store' => ['Whey Protein', 'Vitamins', 'Mass Gainer', 'BCAA', 'Fish Oil'],
    'Organic Store' => ['Organic Pulses', 'Organic Honey', 'Organic Tea', 'Cold Pressed Oils', 'Organic Spices'],
    'Hypermarket' => ['Groceries & Staples', 'Household Cleaning', 'Personal Care', 'Packaged Foods'],
    'Department Store' => ['Cosmetics', 'Toiletries', 'Stationery', 'Daily Needs'],
    'Grocery Store' => ['Rice', 'Wheat Flour (Atta)', 'Sugar', 'Cooking Oil', 'Salt'],
    'Kirana Store' => ['Pulses (Dal)', 'Spices (Masala)', 'Snacks', 'Soaps & Detergents', 'Biscuits'],
    'General Store' => ['Daily Groceries', 'Cold Drinks', 'Bread & Eggs', 'Stationery items'],
    'Spices Store' => ['Turmeric Powder', 'Chilli Powder', 'Coriander Powder', 'Garam Masala', 'Whole Spices'],
    'Tea Store' => ['Assam Tea', 'Darjeeling Tea', 'Green Tea', 'Masala Chai', 'Tea Bags'],
    'Coffee Store' => ['Instant Coffee', 'Filter Coffee Powder', 'Roasted Coffee Beans', 'Espresso Powder'],
    'Chocolate Store' => ['Milk Chocolates', 'Dark Chocolates', 'Assorted Chocolate Boxes', 'Handmade Chocolates'],
    'Cycle Store' => ['Mountain Bikes', 'Kids Bicycles', 'Road Bikes', 'Bicycle Helmets', 'Cycle Accessories'],
    'Auto Accessories Store' => ['Car Seat Covers', 'Floor Mats', 'Car Perfumes', 'Steering Covers', 'Car Cleaning Kit'],
    'Wine Shop' => ['Red Wine', 'White Wine', 'Whiskey', 'Vodka', 'Beer'],
    'Tobacco Store' => ['Cigarettes', 'Cigars', 'Lighters', 'Smoking Pipes', 'Chewing Tobacco'],
    'Mobile Store' => ['Smartphones', 'Feature Phones', 'Tablets', 'Mobile Batteries']
];

$retailCategory = \App\Models\BusinessCategory::where('name', 'LIKE', '%Retail%')->first();
$subs = \App\Models\BusinessSubCategory::where('business_category_id', $retailCategory->id)->get();
$addedCount = 0;

foreach ($subs as $sub) {
    if ($sub->has_business_type) {
        $types = \App\Models\BusinessType::where('business_sub_category_id', $sub->id)->get();
        foreach ($types as $type) {
            $count = \App\Models\BusinessProduct::where('business_type_id', $type->id)->count();
            if ($count == 0) {
                // Determine products to add
                $pNames = [];
                if (isset($productMap[$type->name])) {
                    $pNames = $productMap[$type->name];
                } else {
                    $pNames = [
                        $type->name . ' Product 1',
                        $type->name . ' Product 2',
                        $type->name . ' Service 1'
                    ];
                }

                foreach ($pNames as $pName) {
                    \App\Models\BusinessProduct::create([
                        'name' => $pName,
                        'business_sub_category_id' => $sub->id,
                        'business_type_id' => $type->id,
                        'status' => 1
                    ]);
                    $addedCount++;
                }
            }
        }
    } else {
        $count = \App\Models\BusinessProduct::where('business_sub_category_id', $sub->id)
            ->whereNull('business_type_id')
            ->count();
        if ($count == 0) {
            $pNames = [];
            if (isset($productMap[$sub->name])) {
                $pNames = $productMap[$sub->name];
            } else {
                $pNames = [
                    $sub->name . ' Product 1',
                    $sub->name . ' Product 2',
                    $sub->name . ' Service 1'
                ];
            }

            foreach ($pNames as $pName) {
                \App\Models\BusinessProduct::create([
                    'name' => $pName,
                    'business_sub_category_id' => $sub->id,
                    'business_type_id' => null,
                    'status' => 1
                ]);
                $addedCount++;
            }
        }
    }
}

echo "Added " . $addedCount . " products to empty retail stores.\n";
