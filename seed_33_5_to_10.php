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
        'category' => 'Agriculture & Farming',
        'sub_category' => 'Farm Equipment Dealer',
        'has_business_type' => 1,
        'products' => [
            ['name' => 'Tractor', 'type' => 'Physical Product', 'brands' => 'Mahindra Tractors'],
            ['name' => 'Mini Tractor', 'type' => 'Physical Product', 'brands' => 'Sonalika'],
            ['name' => 'Power Tiller', 'type' => 'Physical Product', 'brands' => 'VST Tillers'],
            ['name' => 'Rotavator', 'type' => 'Physical Product', 'brands' => 'Shaktiman'],
            ['name' => 'Cultivator', 'type' => 'Physical Product', 'brands' => 'Fieldking'],
            ['name' => 'Disc Harrow', 'type' => 'Physical Product', 'brands' => 'Fieldking'],
            ['name' => 'Seed Drill', 'type' => 'Physical Product', 'brands' => 'Mahindra'],
            ['name' => 'Happy Seeder', 'type' => 'Physical Product', 'brands' => 'Fieldking'],
            ['name' => 'Zero Till Drill', 'type' => 'Physical Product', 'brands' => 'Shaktiman'],
            ['name' => 'Plough', 'type' => 'Physical Product', 'brands' => 'Shaktiman'],
            ['name' => 'Harvester', 'type' => 'Physical Product', 'brands' => 'John Deere'],
            ['name' => 'Combine Harvester', 'type' => 'Physical Product', 'brands' => 'Claas'],
            ['name' => 'Reaper', 'type' => 'Physical Product', 'brands' => 'Kubota'],
            ['name' => 'Thresher', 'type' => 'Physical Product', 'brands' => 'Mahindra'],
            ['name' => 'Baler', 'type' => 'Physical Product', 'brands' => 'John Deere'],
            ['name' => 'Boom Sprayer', 'type' => 'Physical Product', 'brands' => 'ASPEE'],
            ['name' => 'Power Sprayer', 'type' => 'Physical Product', 'brands' => 'Honda'],
            ['name' => 'Knapsack Sprayer', 'type' => 'Physical Product', 'brands' => 'Neptune'],
            ['name' => 'Water Pump', 'type' => 'Physical Product', 'brands' => 'Kirloskar'],
            ['name' => 'Irrigation Pipe', 'type' => 'Physical Product', 'brands' => 'Jain Irrigation'],
            ['name' => 'Drip Irrigation System', 'type' => 'Physical Product', 'brands' => 'Jain Irrigation'],
            ['name' => 'Sprinkler Irrigation System', 'type' => 'Physical Product', 'brands' => 'Rain Bird'],
            ['name' => 'Farm Trailer', 'type' => 'Physical Product', 'brands' => 'Mahindra'],
            ['name' => 'Tractor Trolley', 'type' => 'Physical Product', 'brands' => 'Local Brand'],
            ['name' => 'Farm Tools', 'type' => 'Physical Product', 'brands' => 'Falcon'],
            ['name' => 'Spare Parts', 'type' => 'Physical Product', 'brands' => 'Dealer'],
            ['name' => 'Engine Oil', 'type' => 'Physical Product', 'brands' => 'Castrol'],
            ['name' => 'Hydraulic Oil', 'type' => 'Physical Product', 'brands' => 'Servo'],
            ['name' => 'Battery', 'type' => 'Physical Product', 'brands' => 'Exide'],
            ['name' => 'Tractor Tyres', 'type' => 'Physical Product', 'brands' => 'MRF'],
            ['name' => 'Equipment Installation', 'type' => 'Service', 'brands' => 'Dealer'],
            ['name' => 'Repair & Maintenance', 'type' => 'Service', 'brands' => 'Dealer'],
            ['name' => 'Annual Maintenance Contract', 'type' => 'Service', 'brands' => 'Dealer'],
            ['name' => 'Farm Equipment Rental', 'type' => 'Service', 'brands' => 'Dealer'],
            ['name' => 'EMI Finance Assistance', 'type' => 'Service', 'brands' => 'Dealer'],
            ['name' => 'Spare Parts Supply', 'type' => 'Service', 'brands' => 'Dealer'],
            ['name' => 'On-Site Service', 'type' => 'Service', 'brands' => 'Dealer'],
            ['name' => 'Product Demonstration', 'type' => 'Service', 'brands' => 'Dealer'],
            ['name' => 'Operator Training', 'type' => 'Service', 'brands' => 'Dealer'],
            ['name' => '24×7 Customer Support', 'type' => 'Service', 'brands' => 'Dealer'],
        ]
    ],
    [
        'category' => 'Agriculture & Farming',
        'sub_category' => 'Tractor Dealer',
        'has_business_type' => 1,
        'products' => [
            ['name' => 'Compact Tractor', 'type' => 'Physical Product', 'brands' => 'Mahindra'],
            ['name' => 'Mini Tractor', 'type' => 'Physical Product', 'brands' => 'Sonalika'],
            ['name' => 'Utility Tractor', 'type' => 'Physical Product', 'brands' => 'Swaraj'],
            ['name' => 'Heavy Duty Tractor', 'type' => 'Physical Product', 'brands' => 'John Deere'],
            ['name' => 'Orchard Tractor', 'type' => 'Physical Product', 'brands' => 'Kubota'],
            ['name' => 'Electric Tractor', 'type' => 'Physical Product', 'brands' => 'Cellestial E-Mobility'],
            ['name' => 'Tractor Implements', 'type' => 'Physical Product', 'brands' => 'Shaktiman'],
            ['name' => 'Rotavator', 'type' => 'Physical Product', 'brands' => 'Shaktiman'],
            ['name' => 'Cultivator', 'type' => 'Physical Product', 'brands' => 'Fieldking'],
            ['name' => 'Disc Harrow', 'type' => 'Physical Product', 'brands' => 'Fieldking'],
            ['name' => 'MB Plough', 'type' => 'Physical Product', 'brands' => 'Shaktiman'],
            ['name' => 'Seed Drill', 'type' => 'Physical Product', 'brands' => 'Mahindra'],
            ['name' => 'Trailer', 'type' => 'Physical Product', 'brands' => 'Mahindra'],
            ['name' => 'Front Loader', 'type' => 'Physical Product', 'brands' => 'Mahindra'],
            ['name' => 'Tractor Tyres', 'type' => 'Physical Product', 'brands' => 'MRF'],
            ['name' => 'Tractor Battery', 'type' => 'Physical Product', 'brands' => 'Exide'],
            ['name' => 'Engine Oil', 'type' => 'Physical Product', 'brands' => 'Castrol'],
            ['name' => 'Hydraulic Oil', 'type' => 'Physical Product', 'brands' => 'Servo'],
            ['name' => 'Genuine Spare Parts', 'type' => 'Physical Product', 'brands' => 'OEM'],
            ['name' => 'Tractor Accessories', 'type' => 'Physical Product', 'brands' => 'Dealer'],
            ['name' => 'Tractor Insurance', 'type' => 'Service', 'brands' => 'ICICI Lombard'],
            ['name' => 'Tractor Finance', 'type' => 'Service', 'brands' => 'HDFC Bank'],
            ['name' => 'EMI Assistance', 'type' => 'Service', 'brands' => 'Dealer'],
            ['name' => 'Exchange Offer', 'type' => 'Service', 'brands' => 'Dealer'],
            ['name' => 'Used Tractor Sales', 'type' => 'Physical Product', 'brands' => 'Dealer'],
            ['name' => 'Certified Used Tractor', 'type' => 'Physical Product', 'brands' => 'Dealer'],
            ['name' => 'Tractor Registration Assistance', 'type' => 'Service', 'brands' => 'Dealer'],
            ['name' => 'RTO Documentation', 'type' => 'Service', 'brands' => 'Dealer'],
            ['name' => 'Extended Warranty', 'type' => 'Service', 'brands' => 'Dealer'],
            ['name' => 'Annual Maintenance Contract', 'type' => 'Service', 'brands' => 'Dealer'],
            ['name' => 'Tractor Servicing', 'type' => 'Service', 'brands' => 'Dealer'],
            ['name' => 'Breakdown Assistance', 'type' => 'Service', 'brands' => 'Dealer'],
            ['name' => 'Doorstep Service', 'type' => 'Service', 'brands' => 'Dealer'],
            ['name' => 'On-Farm Demonstration', 'type' => 'Service', 'brands' => 'Dealer'],
            ['name' => 'Test Drive', 'type' => 'Service', 'brands' => 'Dealer'],
            ['name' => 'Operator Training', 'type' => 'Service', 'brands' => 'Dealer'],
            ['name' => 'GPS Tracking Installation', 'type' => 'Service', 'brands' => 'Dealer'],
            ['name' => 'Fleet Management Support', 'type' => 'Service', 'brands' => 'Dealer'],
            ['name' => 'Online Booking', 'type' => 'Service', 'brands' => 'Dealer'],
            ['name' => '24×7 Customer Support', 'type' => 'Service', 'brands' => 'Dealer'],
        ]
    ],
    [
        'category' => 'Agriculture & Farming',
        'sub_category' => 'Irrigation Equipment Supplier',
        'has_business_type' => 1,
        'products' => [
            ['name' => 'Drip Irrigation System', 'type' => 'Physical Product', 'brands' => 'Jain Irrigation'],
            ['name' => 'Sprinkler Irrigation System', 'type' => 'Physical Product', 'brands' => 'Rain Bird'],
            ['name' => 'Mini Sprinkler', 'type' => 'Physical Product', 'brands' => 'Netafim'],
            ['name' => 'Micro Sprinkler', 'type' => 'Physical Product', 'brands' => 'Netafim'],
            ['name' => 'Rain Gun System', 'type' => 'Physical Product', 'brands' => 'Komet'],
            ['name' => 'Drip Pipe', 'type' => 'Physical Product', 'brands' => 'Jain Irrigation'],
            ['name' => 'Lateral Pipe', 'type' => 'Physical Product', 'brands' => 'Finolex'],
            ['name' => 'HDPE Pipe', 'type' => 'Physical Product', 'brands' => 'Supreme'],
            ['name' => 'PVC Pipe', 'type' => 'Physical Product', 'brands' => 'Astral'],
            ['name' => 'Irrigation Fittings', 'type' => 'Physical Product', 'brands' => 'Jain Irrigation'],
            ['name' => 'Control Valves', 'type' => 'Physical Product', 'brands' => 'Netafim'],
            ['name' => 'Filters', 'type' => 'Physical Product', 'brands' => 'Amiad'],
            ['name' => 'Sand Filter', 'type' => 'Physical Product', 'brands' => 'Netafim'],
            ['name' => 'Screen Filter', 'type' => 'Physical Product', 'brands' => 'Amiad'],
            ['name' => 'Disc Filter', 'type' => 'Physical Product', 'brands' => 'Netafim'],
            ['name' => 'Fertigation Unit', 'type' => 'Physical Product', 'brands' => 'Jain Irrigation'],
            ['name' => 'Venturi Injector', 'type' => 'Physical Product', 'brands' => 'Netafim'],
            ['name' => 'Water Pump', 'type' => 'Physical Product', 'brands' => 'Kirloskar'],
            ['name' => 'Solar Water Pump', 'type' => 'Physical Product', 'brands' => 'Shakti Pumps'],
            ['name' => 'Irrigation Controller', 'type' => 'Physical Product', 'brands' => 'Rain Bird'],
            ['name' => 'Automatic Irrigation Timer', 'type' => 'Physical Product', 'brands' => 'Rain Bird'],
            ['name' => 'Moisture Sensor', 'type' => 'Physical Product', 'brands' => 'Netafim'],
            ['name' => 'Water Flow Meter', 'type' => 'Physical Product', 'brands' => 'Jain Irrigation'],
            ['name' => 'Water Storage Tank', 'type' => 'Physical Product', 'brands' => 'Sintex'],
            ['name' => 'Fogging System', 'type' => 'Physical Product', 'brands' => 'Agriplast'],
            ['name' => 'Greenhouse Irrigation', 'type' => 'Physical Product', 'brands' => 'Netafim'],
            ['name' => 'Farm Irrigation Design', 'type' => 'Service', 'brands' => 'Supplier'],
            ['name' => 'Irrigation Installation', 'type' => 'Service', 'brands' => 'Supplier'],
            ['name' => 'Farm Survey', 'type' => 'Service', 'brands' => 'Supplier'],
            ['name' => 'Water Management Consultation', 'type' => 'Service', 'brands' => 'Supplier'],
            ['name' => 'Repair & Maintenance', 'type' => 'Service', 'brands' => 'Supplier'],
            ['name' => 'Annual Maintenance Contract', 'type' => 'Service', 'brands' => 'Supplier'],
            ['name' => 'Government Subsidy Assistance', 'type' => 'Service', 'brands' => 'Supplier'],
            ['name' => 'On-Site Inspection', 'type' => 'Service', 'brands' => 'Supplier'],
            ['name' => 'Product Demonstration', 'type' => 'Service', 'brands' => 'Supplier'],
            ['name' => 'Farmer Training', 'type' => 'Service', 'brands' => 'Supplier'],
            ['name' => 'Bulk Supply', 'type' => 'Service', 'brands' => 'Supplier'],
            ['name' => 'Online Ordering', 'type' => 'Service', 'brands' => 'Supplier'],
            ['name' => 'Technical Support', 'type' => 'Service', 'brands' => 'Supplier'],
            ['name' => '24×7 Customer Support', 'type' => 'Service', 'brands' => 'Supplier'],
        ]
    ],
    [
        'category' => 'Agriculture & Farming',
        'sub_category' => 'Dairy Farm',
        'has_business_type' => 1,
        'products' => [
            ['name' => 'Fresh Cow Milk', 'type' => 'Physical Product', 'brands' => 'Amul'],
            ['name' => 'Fresh Buffalo Milk', 'type' => 'Physical Product', 'brands' => 'Amul'],
            ['name' => 'Organic Milk', 'type' => 'Physical Product', 'brands' => 'Akshayakalpa'],
            ['name' => 'A2 Milk', 'type' => 'Physical Product', 'brands' => 'Sid\'s Farm'],
            ['name' => 'Pasteurized Milk', 'type' => 'Physical Product', 'brands' => 'Mother Dairy'],
            ['name' => 'Curd', 'type' => 'Physical Product', 'brands' => 'Amul'],
            ['name' => 'Buttermilk', 'type' => 'Physical Product', 'brands' => 'Amul'],
            ['name' => 'Paneer', 'type' => 'Physical Product', 'brands' => 'Amul'],
            ['name' => 'Butter', 'type' => 'Physical Product', 'brands' => 'Amul'],
            ['name' => 'Ghee', 'type' => 'Physical Product', 'brands' => 'Amul'],
            ['name' => 'Cream', 'type' => 'Physical Product', 'brands' => 'Amul'],
            ['name' => 'Cheese', 'type' => 'Physical Product', 'brands' => 'Amul'],
            ['name' => 'Flavored Milk', 'type' => 'Physical Product', 'brands' => 'Amul'],
            ['name' => 'Dairy Cattle', 'type' => 'Physical Product', 'brands' => 'Dairy Farm'],
            ['name' => 'Calves', 'type' => 'Physical Product', 'brands' => 'Dairy Farm'],
            ['name' => 'Cattle Feed', 'type' => 'Physical Product', 'brands' => 'Godrej Agrovet'],
            ['name' => 'Mineral Mixture', 'type' => 'Physical Product', 'brands' => 'Virbac'],
            ['name' => 'Silage', 'type' => 'Physical Product', 'brands' => 'Dairy Farm'],
            ['name' => 'Fodder Supply', 'type' => 'Physical Product', 'brands' => 'Dairy Farm'],
            ['name' => 'Milking Machine', 'type' => 'Physical Product', 'brands' => 'DeLaval'],
            ['name' => 'Bulk Milk Cooler', 'type' => 'Physical Product', 'brands' => 'DeLaval'],
            ['name' => 'Milk Testing', 'type' => 'Service', 'brands' => 'Dairy Farm'],
            ['name' => 'Milk Collection', 'type' => 'Service', 'brands' => 'Dairy Cooperative'],
            ['name' => 'Doorstep Milk Delivery', 'type' => 'Service', 'brands' => 'Dairy Farm'],
            ['name' => 'Subscription Milk Delivery', 'type' => 'Service', 'brands' => 'Dairy Farm'],
            ['name' => 'Dairy Farm Visit', 'type' => 'Service', 'brands' => 'Dairy Farm'],
            ['name' => 'Farm Training', 'type' => 'Service', 'brands' => 'Dairy Farm'],
            ['name' => 'Animal Nutrition Advice', 'type' => 'Service', 'brands' => 'Veterinarian'],
            ['name' => 'Artificial Insemination', 'type' => 'Service', 'brands' => 'Veterinary Service'],
            ['name' => 'Veterinary Support', 'type' => 'Service', 'brands' => 'Veterinary Clinic'],
            ['name' => 'Dairy Consultancy', 'type' => 'Service', 'brands' => 'Dairy Expert'],
            ['name' => 'Organic Dairy Consulting', 'type' => 'Service', 'brands' => 'Dairy Expert'],
            ['name' => 'Cattle Insurance Assistance', 'type' => 'Service', 'brands' => 'LIC'],
            ['name' => 'Government Dairy Scheme Guidance', 'type' => 'Service', 'brands' => 'NABARD'],
            ['name' => 'Dairy Equipment Installation', 'type' => 'Service', 'brands' => 'DeLaval'],
            ['name' => 'Bulk Milk Supply', 'type' => 'Service', 'brands' => 'Dairy Farm'],
            ['name' => 'Retail Milk Supply', 'type' => 'Service', 'brands' => 'Dairy Farm'],
            ['name' => 'Online Orders', 'type' => 'Service', 'brands' => 'Dairy Farm'],
            ['name' => 'Farm Management Support', 'type' => 'Service', 'brands' => 'Dairy Expert'],
            ['name' => '24×7 Customer Support', 'type' => 'Service', 'brands' => 'Dairy Farm'],
        ]
    ],
    [
        'category' => 'Agriculture & Farming',
        'sub_category' => 'Poultry Farm',
        'has_business_type' => 1,
        'products' => [
            ['name' => 'Broiler Chicken', 'type' => 'Physical Product', 'brands' => 'Suguna Foods'],
            ['name' => 'Layer Chicken', 'type' => 'Physical Product', 'brands' => 'Venky\'s'],
            ['name' => 'Country Chicken', 'type' => 'Physical Product', 'brands' => 'Poultry Farm'],
            ['name' => 'Day-Old Chicks', 'type' => 'Physical Product', 'brands' => 'Venky\'s'],
            ['name' => 'Hatching Eggs', 'type' => 'Physical Product', 'brands' => 'Poultry Farm'],
            ['name' => 'Table Eggs', 'type' => 'Physical Product', 'brands' => 'Suguna Foods'],
            ['name' => 'Brown Eggs', 'type' => 'Physical Product', 'brands' => 'Poultry Farm'],
            ['name' => 'Organic Eggs', 'type' => 'Physical Product', 'brands' => 'Happy Hens'],
            ['name' => 'Quail Eggs', 'type' => 'Physical Product', 'brands' => 'Poultry Farm'],
            ['name' => 'Duck Eggs', 'type' => 'Physical Product', 'brands' => 'Poultry Farm'],
            ['name' => 'Chicken Feed', 'type' => 'Physical Product', 'brands' => 'Godrej Agrovet'],
            ['name' => 'Layer Feed', 'type' => 'Physical Product', 'brands' => 'Godrej Agrovet'],
            ['name' => 'Broiler Feed', 'type' => 'Physical Product', 'brands' => 'Cargill'],
            ['name' => 'Feed Supplements', 'type' => 'Physical Product', 'brands' => 'Virbac'],
            ['name' => 'Vitamin Supplements', 'type' => 'Physical Product', 'brands' => 'Virbac'],
            ['name' => 'Poultry Vaccines', 'type' => 'Physical Product', 'brands' => 'MSD Animal Health'],
            ['name' => 'Poultry Medicines', 'type' => 'Physical Product', 'brands' => 'Zoetis'],
            ['name' => 'Poultry Equipment', 'type' => 'Physical Product', 'brands' => 'Big Dutchman'],
            ['name' => 'Automatic Feeder', 'type' => 'Physical Product', 'brands' => 'Big Dutchman'],
            ['name' => 'Automatic Drinker', 'type' => 'Physical Product', 'brands' => 'Big Dutchman'],
            ['name' => 'Incubator', 'type' => 'Physical Product', 'brands' => 'Poultry Farm Equipment'],
            ['name' => 'Brooder Equipment', 'type' => 'Physical Product', 'brands' => 'Poultry Equipment'],
            ['name' => 'Poultry Shed Design', 'type' => 'Service', 'brands' => 'Poultry Consultant'],
            ['name' => 'Farm Setup Consultation', 'type' => 'Service', 'brands' => 'Poultry Consultant'],
            ['name' => 'Veterinary Services', 'type' => 'Service', 'brands' => 'Veterinary Clinic'],
            ['name' => 'Vaccination Program', 'type' => 'Service', 'brands' => 'Poultry Farm'],
            ['name' => 'Disease Diagnosis', 'type' => 'Service', 'brands' => 'Veterinary Clinic'],
            ['name' => 'Bulk Chicken Supply', 'type' => 'Service', 'brands' => 'Poultry Farm'],
            ['name' => 'Bulk Egg Supply', 'type' => 'Service', 'brands' => 'Poultry Farm'],
            ['name' => 'Home Delivery', 'type' => 'Service', 'brands' => 'Poultry Farm'],
            ['name' => 'Farm Training', 'type' => 'Service', 'brands' => 'Poultry Expert'],
            ['name' => 'Contract Farming', 'type' => 'Service', 'brands' => 'Suguna Foods'],
            ['name' => 'Waste Management', 'type' => 'Service', 'brands' => 'Poultry Farm'],
            ['name' => 'Government Subsidy Guidance', 'type' => 'Service', 'brands' => 'NABARD'],
            ['name' => 'Insurance Assistance', 'type' => 'Service', 'brands' => 'LIC'],
            ['name' => 'Online Orders', 'type' => 'Service', 'brands' => 'Poultry Farm'],
            ['name' => 'Retail Supply', 'type' => 'Service', 'brands' => 'Poultry Farm'],
            ['name' => 'Wholesale Supply', 'type' => 'Service', 'brands' => 'Poultry Farm'],
            ['name' => 'Technical Support', 'type' => 'Service', 'brands' => 'Poultry Consultant'],
            ['name' => '24×7 Customer Support', 'type' => 'Service', 'brands' => 'Poultry Farm'],
        ]
    ],
    [
        'category' => 'Agriculture & Farming',
        'sub_category' => 'Goat Farm',
        'has_business_type' => 1,
        'products' => [
            ['name' => 'Boer Goats', 'type' => 'Physical Product', 'brands' => 'Goat Farm'],
            ['name' => 'Sirohi Goats', 'type' => 'Physical Product', 'brands' => 'Goat Farm'],
            ['name' => 'Jamunapari Goats', 'type' => 'Physical Product', 'brands' => 'Goat Farm'],
            ['name' => 'Barbari Goats', 'type' => 'Physical Product', 'brands' => 'Goat Farm'],
            ['name' => 'Beetal Goats', 'type' => 'Physical Product', 'brands' => 'Goat Farm'],
            ['name' => 'Black Bengal Goats', 'type' => 'Physical Product', 'brands' => 'Goat Farm'],
            ['name' => 'Goat Kids', 'type' => 'Physical Product', 'brands' => 'Goat Farm'],
            ['name' => 'Breeding Goats', 'type' => 'Physical Product', 'brands' => 'Goat Farm'],
            ['name' => 'Goat Milk', 'type' => 'Physical Product', 'brands' => 'Goat Farm'],
            ['name' => 'Goat Meat', 'type' => 'Physical Product', 'brands' => 'Goat Farm'],
            ['name' => 'Organic Goat Meat', 'type' => 'Physical Product', 'brands' => 'Goat Farm'],
            ['name' => 'Goat Feed', 'type' => 'Physical Product', 'brands' => 'Godrej Agrovet'],
            ['name' => 'Mineral Mixture', 'type' => 'Physical Product', 'brands' => 'Virbac'],
            ['name' => 'Fodder Supply', 'type' => 'Physical Product', 'brands' => 'Goat Farm'],
            ['name' => 'Silage', 'type' => 'Physical Product', 'brands' => 'Goat Farm'],
            ['name' => 'Goat Shed Equipment', 'type' => 'Physical Product', 'brands' => 'Farm Equipment'],
            ['name' => 'Water Feeders', 'type' => 'Physical Product', 'brands' => 'Farm Equipment'],
            ['name' => 'Breeding Consultation', 'type' => 'Service', 'brands' => 'Goat Expert'],
            ['name' => 'Farm Setup Consultation', 'type' => 'Service', 'brands' => 'Goat Consultant'],
            ['name' => 'Veterinary Services', 'type' => 'Service', 'brands' => 'Veterinary Clinic'],
            ['name' => 'Vaccination Program', 'type' => 'Service', 'brands' => 'Veterinary Clinic'],
            ['name' => 'Artificial Insemination', 'type' => 'Service', 'brands' => 'Veterinary Service'],
            ['name' => 'Disease Diagnosis', 'type' => 'Service', 'brands' => 'Veterinary Clinic'],
            ['name' => 'Animal Health Checkup', 'type' => 'Service', 'brands' => 'Veterinary Clinic'],
            ['name' => 'Farm Management Consulting', 'type' => 'Service', 'brands' => 'Goat Expert'],
            ['name' => 'Farmer Training', 'type' => 'Service', 'brands' => 'Goat Farm'],
            ['name' => 'Contract Farming', 'type' => 'Service', 'brands' => 'Goat Farm'],
            ['name' => 'Government Subsidy Guidance', 'type' => 'Service', 'brands' => 'NABARD'],
            ['name' => 'Livestock Insurance', 'type' => 'Service', 'brands' => 'LIC'],
            ['name' => 'Online Goat Booking', 'type' => 'Service', 'brands' => 'Goat Farm'],
            ['name' => 'Home Delivery', 'type' => 'Service', 'brands' => 'Goat Farm'],
            ['name' => 'Wholesale Supply', 'type' => 'Service', 'brands' => 'Goat Farm'],
            ['name' => 'Retail Supply', 'type' => 'Service', 'brands' => 'Goat Farm'],
            ['name' => 'Organic Farming Consultation', 'type' => 'Service', 'brands' => 'Goat Expert'],
            ['name' => 'Farm Visit', 'type' => 'Service', 'brands' => 'Goat Farm'],
            ['name' => 'Breeding Program', 'type' => 'Service', 'brands' => 'Goat Farm'],
            ['name' => 'Nutritional Planning', 'type' => 'Service', 'brands' => 'Goat Expert'],
            ['name' => 'Technical Support', 'type' => 'Service', 'brands' => 'Goat Farm'],
            ['name' => 'Emergency Veterinary Support', 'type' => 'Service', 'brands' => 'Veterinary Clinic'],
            ['name' => '24×7 Customer Support', 'type' => 'Service', 'brands' => 'Goat Farm'],
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

echo "Successfully seeded Data for Agriculture & Farming Sub Categories 33.5 to 33.10.\n";
