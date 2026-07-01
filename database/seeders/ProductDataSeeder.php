<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BusinessSubCategory;
use App\Models\BusinessProduct;
use Illuminate\Support\Facades\DB;

class ProductDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $productsData = [
            'Computer Store' => [
                'Desktop Computer', 'All-in-One PC', 'Mini PC', 'Workstation', 'Gaming PC', 
                'Custom PC', 'Server', 'Thin Client', 'Monitor', 'Gaming Monitor', 'Portable Monitor', 
                'Monitor Arm', 'Monitor Stand', 'Monitor Light Bar', 'CPU Cabinet', 'Computer Case', 
                'SMPS', 'Power Supply', 'Motherboard', 'Processor', 'CPU Cooler', 'Liquid Cooler', 
                'Cabinet Fan', 'Thermal Paste', 'RAM', 'Graphics Card', 'Sound Card', 'Network Card', 
                'WiFi Adapter', 'Bluetooth Adapter', 'Internal Hard Disk', 'External Hard Disk', 
                'SSD', 'NVMe SSD', 'SATA SSD', 'M.2 SSD', 'Hard Disk Enclosure', 'NAS Storage', 
                'USB Flash Drive', 'Memory Card', 'Card Reader', 'DVD Writer', 'Blu-ray Writer', 
                'Keyboard', 'Mechanical Keyboard', 'Membrane Keyboard', 'Wireless Keyboard', 
                'Gaming Keyboard', 'Mouse', 'Wireless Mouse', 'Gaming Mouse', 'Mouse Pad', 
                'Gaming Mouse Pad', 'Webcam', 'Conference Camera', 'Microphone', 'USB Microphone', 
                'Headset', 'Gaming Headset', 'Speakers', '2.1 Speakers', '5.1 Speakers', 'UPS', 
                'Power Backup', 'Extension Board', 'Surge Protector', 'Printer', 'Inkjet Printer', 
                'Laser Printer', 'Dot Matrix Printer', 'Thermal Printer', 'Label Printer', 
                'Photo Printer', '3D Printer', 'Printer Ink', 'Ink Bottle', 'Ink Cartridge', 
                'Toner Cartridge', 'Drum Unit', 'Scanner', 'Flatbed Scanner', 'Document Scanner', 
                'Barcode Scanner', 'QR Code Scanner', 'Biometric Scanner', 'Fingerprint Scanner', 
                'Projector', 'Projector Screen', 'Router', 'WiFi Router', 'Mesh WiFi', 'Network Switch', 
                'Hub', 'Access Point', 'Modem', 'LAN Cable', 'Ethernet Cable', 'Patch Cord', 
                'RJ45 Connector', 'Crimping Tool', 'LAN Tester', 'Rack Cabinet', 'Patch Panel', 
                'KVM Switch', 'Docking Station', 'USB Hub', 'USB Adapter', 'Type-C Hub', 'HDMI Cable', 
                'DisplayPort Cable', 'VGA Cable', 'DVI Cable', 'USB Extension Cable', 'Power Cable', 
                'CMOS Battery', 'Laptop Adapter', 'Universal Adapter', 'Cleaning Kit', 
                'Compressed Air Spray', 'Screen Cleaning Cloth', 'Screen Cleaning Solution', 
                'Cable Organizer', 'Cable Tie', 'External DVD Drive', 'Graphics Tablet', 'Pen Tablet', 
                'Digital Signature Pad', 'POS Machine', 'Cash Drawer', 'Receipt Printer', 'Office Software', 
                'Antivirus Software', 'Operating System License'
            ],
            'Laptop Store' => [
                'Business Laptop', 'Student Laptop', 'Gaming Laptop', 'Creator Laptop', '2-in-1 Laptop', 
                'Convertible Laptop', 'Ultrabook', 'Chromebook', 'MacBook Air', 'MacBook Pro', 
                'Refurbished Laptop', 'Laptop Battery', 'Laptop Adapter', 'Universal Laptop Charger', 
                'Type-C Laptop Charger', 'Laptop Sleeve', 'Laptop Bag', 'Laptop Backpack', 'Laptop Briefcase', 
                'Laptop Stand', 'Foldable Laptop Stand', 'Cooling Pad', 'Laptop Cooling Fan', 'Keyboard Cover', 
                'Laptop Skin', 'Screen Protector', 'Privacy Screen Filter', 'Trackpad Protector', 'Laptop Lock', 
                'Security Cable Lock', 'Docking Station', 'USB-C Dock', 'Thunderbolt Dock', 'Port Replicator', 
                'External GPU', 'Laptop RAM', 'Laptop SSD', 'Laptop Hard Disk', 'NVMe SSD', 'SATA SSD', 
                'Laptop Keyboard', 'Laptop Screen', 'Laptop Hinge', 'Laptop Webcam', 'Laptop Speaker', 
                'Laptop Fan', 'Laptop Touchpad', 'Laptop Motherboard', 'Laptop Processor', 'Laptop DC Jack', 
                'Laptop WiFi Card', 'Laptop Bluetooth Card', 'Laptop CMOS Battery', 'Laptop Display Cable', 
                'Laptop Camera Cover', 'Stylus Pen', 'Digital Pen', 'External Monitor', 'Portable Monitor', 
                'USB-C Monitor', 'Wireless Presenter', 'Laser Pointer', 'USB Flash Drive', 'External SSD', 
                'External Hard Disk', 'Memory Card', 'Card Reader', 'USB Hub', 'Type-C Hub', 'HDMI Adapter', 
                'DisplayPort Adapter', 'VGA Adapter', 'Ethernet Adapter', 'USB to LAN Adapter', 'Bluetooth Mouse', 
                'Wireless Mouse', 'Gaming Mouse', 'Travel Mouse', 'Bluetooth Keyboard', 'Wireless Keyboard', 
                'Mechanical Keyboard', 'Numeric Keypad', 'Headset', 'Wireless Headset', 'Earbuds', 'Webcam', 
                'USB Microphone', 'Ring Light', 'Tripod', 'Document Camera', 'Printer', 'Portable Printer', 
                'Scanner', 'Portable Scanner', 'Power Bank for Laptop', 'Universal Travel Adapter', 
                'Screen Cleaning Kit', 'Microfiber Cloth', 'Compressed Air Cleaner', 'Laptop Repair Service', 
                'Laptop Screen Replacement', 'Laptop Battery Replacement', 'Laptop Keyboard Replacement', 
                'Laptop SSD Upgrade', 'Laptop RAM Upgrade', 'Data Recovery', 'Operating System Installation', 
                'Software Installation', 'Virus Removal', 'Laptop Insurance', 'Extended Warranty'
            ]
        ];

        foreach ($productsData as $subCatName => $products) {
            $subCategory = BusinessSubCategory::where('name', $subCatName)->first();
            
            if ($subCategory) {
                foreach ($products as $prodName) {
                    BusinessProduct::firstOrCreate(
                        [
                            'name' => $prodName, 
                            'business_sub_category_id' => $subCategory->id
                        ],
                        [
                            'status' => 1,
                            'keywords' => $prodName
                        ]
                    );
                }
            }
        }
    }
}
