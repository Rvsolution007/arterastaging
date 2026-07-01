<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$text = <<<EOD
Printing Press
Offset Printing
Digital Printing
Screen Printing
Flex Printing
Large Format Printing
Book Printing
Magazine Printing
Packaging Printing
Label Printing
Commercial Printing
Printing Service
Visiting Card Printing
Brochure Printing
Catalogue Printing
Flyer Printing
Poster Printing
Sticker Printing
Invoice Printing
Corporate Printing
Packaging Company
Corrugated Box Manufacturer
Flexible Packaging
Rigid Packaging
Food Packaging
Pharmaceutical Packaging
Cosmetic Packaging
Luxury Packaging
Custom Packaging
Eco-Friendly Packaging
Label Manufacturer
Barcode Labels
Product Labels
Bottle Labels
QR Labels
Security Labels
RFID Labels
Promotional Products Company
Corporate Gifts
Promotional Merchandise
Customized Products
Employee Gift Solutions
Branding Merchandise
Corporate Gift Supplier
Luxury Corporate Gifts
Festival Gifts
Employee Welcome Kits
Executive Gifts
Promotional Gifts
Florist
Fresh Flower Shop
Wedding Florist
Event Florist
Luxury Florist
Online Flower Delivery
Artificial Flower Boutique
Gift Shop
Personalized Gifts
Corporate Gifts
Festival Gifts
Wedding Gifts
Luxury Gift Boutique
Handmade Gifts
Balloon Decoration
Birthday Decoration
Wedding Decoration
Corporate Decoration
Baby Shower Decoration
Anniversary Decoration
Tent House
Wedding Tent
Corporate Event Tent
Exhibition Tent
Luxury Tent
Outdoor Tent Rental
Event Rental Company
Furniture Rental
Stage Rental
LED Wall Rental
Sound Rental
Lighting Rental
Wedding Equipment Rental
Sound & Lighting Company
Professional Sound Systems
Stage Lighting
DJ Equipment
Concert Production
Wedding Audio
Co-working Space
Startup Co-working
Corporate Workspace
Private Office
Virtual Office
Shared Office
Business Lounge
Business Center
Virtual Office
Meeting Rooms
Conference Center
Executive Suites
Managed Offices
Self Storage Company
Personal Storage
Business Storage
Warehouse Storage
Document Storage
Climate Controlled Storage
Laundry Service
Premium Laundry
Express Laundry
Commercial Laundry
Hotel Laundry
Industrial Laundry
Dry Cleaning
Luxury Garment Cleaning
Wedding Dress Cleaning
Corporate Uniform Cleaning
Leather Cleaning
Shoe Repair
Leather Shoe Repair
Luxury Shoe Care
Sneaker Restoration
Bag Repair
Watch Repair
Luxury Watch Repair
Swiss Watch Repair
Battery Replacement
Watch Restoration
Locksmith
Residential Locksmith
Commercial Locksmith
Automotive Locksmith
Digital Lock Installation
Smart Lock Services
Key Duplication Center
House Keys
Vehicle Keys
Digital Keys
Smart Key Programming
Pet Shop
Dog Supplies
Cat Supplies
Bird Supplies
Aquarium Supplies
Exotic Pet Supplies
Pet Food Store
Pet Grooming
Dog Grooming
Cat Grooming
Luxury Pet Spa
Mobile Pet Grooming
Pet Hygiene Center
Pet Boarding
Dog Boarding
Cat Boarding
Luxury Pet Hotel
Pet Day Care
Veterinary Clinic
Small Animal Clinic
Large Animal Clinic
Pet Surgery
Pet Vaccination
Emergency Veterinary Care
Astrology Center
Vedic Astrology
KP Astrology
Online Astrology
Business Astrology
Marriage Astrology
Career Astrology
Numerology Consultant
Business Numerology
Name Numerology
Mobile Number Numerology
Personal Numerology
Vastu Consultant
Residential Vastu
Commercial Vastu
Industrial Vastu
Office Vastu
Factory Vastu
Feng Shui Consultant
Home Feng Shui
Business Feng Shui
Office Feng Shui
Retail Feng Shui
Spiritual Services
Meditation Retreats
Healing Sessions
Religious Ceremonies
Life Guidance
Spiritual Counseling
Drone Company
Drone Manufacturing
Drone Photography
Drone Survey
Drone Mapping
Agriculture Drone Services
Industrial Drone Inspection
3D Printing Company
Prototype Printing
Industrial 3D Printing
Medical 3D Printing
Architectural Models
Custom Manufacturing
Maker Space
Innovation Lab
Electronics Lab
Prototyping Lab
Engineering Workshop
STEM Innovation Center
Fab Lab
Digital Fabrication
Laser Cutting
CNC Routing
Electronics Prototyping
3D Design Lab
Biotechnology Company
Healthcare Biotechnology
Agriculture Biotechnology
Industrial Biotechnology
Research Biotechnology
Nanotechnology Company
Nano Materials
Nano Coatings
Nano Healthcare
Nano Manufacturing
SpaceTech Company
Satellite Technology
Launch Services
Space Research
Earth Observation
Space Robotics
Quantum Technology Company
Quantum Computing
Quantum Security
Quantum Networking
Quantum Research
ClimateTech Company
Carbon Management
Green Technology
Climate Analytics
Renewable Innovation
CleanTech Company
Clean Energy Solutions
Waste Reduction Technology
Water Innovation
Energy Efficiency
AI Startup
Generative AI
AI Agents
AI Automation
AI SaaS
Enterprise AI
Robotics Startup
Industrial Robotics
Service Robotics
Healthcare Robotics
Warehouse Robotics
Venture Studio
Startup Incubation
Startup Building
Product Studio
Innovation Studio
Startup Incubator
Technology Incubator
University Incubator
Corporate Incubator
Innovation Hub
Startup Accelerator
Seed Accelerator
Growth Accelerator
AI Accelerator
FinTech Accelerator
HealthTech Accelerator
Business Incubation Center
Entrepreneur Support
MSME Incubation
Innovation Center
Startup Mentoring
Innovation Lab
Research Lab
Prototype Lab
Emerging Technology Lab
Corporate Innovation Center
Creator Economy Platform
Creator Monetization
Digital Membership
Fan Community Platform
Creator Commerce
Web3 Company
Decentralized Applications
Smart Contracts
Token Platforms
Blockchain Products
XR Company
Augmented Reality
Virtual Reality
Mixed Reality
Spatial Computing
Metaverse Company
Virtual Events
Virtual Commerce
Digital Assets
Immersive Experiences
Digital Collectibles Company
NFT Marketplace
Digital Art Platform
Collectible Platform
Creator Assets
Smart Device Startup
Wearables
Smart Gadgets
Consumer IoT
Connected Products
Electronics Repair Center
TV Repair
Home Appliance Repair
Audio System Repair
Smart Device Repair
Appliance Service Center
AC Repair
Refrigerator Repair
Washing Machine Repair
Microwave Repair
Water Purifier Repair
EOD;

$lines = explode("\n", $text);

$currentSubCatId = null;
$typesAdded = 0;

foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line)) continue;
    
    $subCategory = \App\Models\BusinessSubCategory::where('name', $line)->first();
    
    if ($subCategory) {
        $currentSubCatId = $subCategory->id;
        echo "Found SubCategory: $line\n";
    } else {
        if ($currentSubCatId) {
            $type = \App\Models\BusinessType::firstOrCreate([
                'name' => $line,
                'business_sub_category_id' => $currentSubCatId
            ], [
                'status' => 1
            ]);
            if ($type->wasRecentlyCreated) {
                $typesAdded++;
            }
        } else {
            echo "Warning: No sub category found for type: $line\n";
        }
    }
}

echo "Done. Added $typesAdded types.\n";
