<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BusinessCategory;
use App\Models\BusinessSubCategory;

class BusinessDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $categories = [
            'Business',
            'Retail',
            'Manufacturing',
            'Services',
            'Healthcare',
            'Education',
            'Food & Beverage',
            'Real Estate',
            'Finance',
            'Automobile',
            'Agriculture',
            'Fashion',
            'Beauty',
            'Home Improvement',
            'Travel',
            'IT & Software',
            'Entertainment',
            'Professional Services',
            'Industrial',
            'Others'
        ];

        // Seed Categories
        foreach ($categories as $catName) {
            BusinessCategory::firstOrCreate(
                ['name' => $catName],
                ['status' => 1]
            );
        }

        $subCategories = [
            'Business' => [
                'Company', 'Startup', 'MSME', 'Enterprise', 'NGO', 'Government Organization', 
                'Public Sector', 'Private Limited', 'Partnership Firm', 'Proprietorship'
            ],
            'Retail' => [
                'Mobile Shop', 'Electronics Store', 'Computer Store', 'Laptop Store', 'CCTV Shop', 
                'Furniture Store', 'Hardware Store', 'Sanitaryware Store', 'Paint Store', 
                'Tiles & Marble Store', 'Plywood & Laminate Store', 'Kitchenware Store', 
                'Home Appliances Store', 'Gift Shop', 'Toy Store', 'Book Store', 'Stationery Store', 
                'Sports Shop', 'Musical Instrument Store', 'Footwear Shop', 'Garment Shop', 
                'Boutique', 'Saree Shop', 'Kids Wear Store', 'Jewellery Store', 'Artificial Jewellery', 
                'Optical Shop', 'Watch Store', 'Cosmetic Store', 'Perfume Store', 'Medical Store', 
                'Supermarket', 'Grocery Store', 'Kirana Store', 'Dry Fruit Store', 'Dairy Shop', 
                'Sweet Shop', 'Bakery', 'Cake Shop', 'Ice Cream Parlour', 'Pet Shop', 'Florist', 
                'Nursery', 'Liquor Store'
            ],
            'Manufacturing' => [
                'Kitchenware Manufacturer', 'Plastic Manufacturer', 'Steel Manufacturer', 
                'Furniture Manufacturer', 'Pump Manufacturer', 'Motor Manufacturer', 
                'Electrical Manufacturer', 'Electronics Manufacturer', 'LED Manufacturer', 
                'Solar Products Manufacturer', 'Cable Manufacturer', 'Wire Manufacturer', 
                'Sanitaryware Manufacturer', 'Ceramic Manufacturer', 'Tiles Manufacturer', 
                'Marble Manufacturer', 'Granite Manufacturer', 'Glass Manufacturer', 
                'Paper Manufacturer', 'Packaging Manufacturer', 'Printing Press', 
                'Textile Manufacturer', 'Garment Manufacturer', 'Footwear Manufacturer', 
                'Chemical Manufacturer', 'Paint Manufacturer', 'Pharma Manufacturer', 
                'Food Manufacturer', 'Beverage Manufacturer', 'Agriculture Equipment Manufacturer', 
                'Machine Manufacturer', 'Automobile Parts Manufacturer', 'Rubber Products Manufacturer', 
                'Aluminum Products Manufacturer', 'Brass Parts Manufacturer', 'Casting Manufacturer', 
                'Fastener Manufacturer', 'Engineering Works', 'Fabrication Unit'
            ],
            'Services' => [
                'Digital Marketing Agency', 'Graphic Design', 'Printing Service', 'Branding Agency', 
                'Advertising Agency', 'Website Development', 'Mobile App Development', 'Software Company', 
                'IT Support', 'SEO Agency', 'Social Media Agency', 'Video Editing', 'Photography', 
                'Videography', 'Animation Studio', 'Event Management', 'Interior Designer', 'Architect', 
                'Civil Contractor', 'Electrical Contractor', 'Plumbing Service', 'Housekeeping', 
                'Security Service', 'Pest Control', 'Courier Service', 'Cleaning Service', 
                'Repair Service', 'Computer Repair', 'Mobile Repair', 'AC Repair', 'RO Service', 
                'Consultancy', 'HR Consultancy', 'Recruitment Agency', 'BPO', 'Call Center'
            ],
            'Healthcare' => [
                'Hospital', 'Clinic', 'Dental Clinic', 'Eye Hospital', 'ENT Clinic', 'Skin Clinic', 
                'Physiotherapy Center', 'Orthopedic Clinic', 'Diagnostic Lab', 'Blood Bank', 
                'Pharmacy', 'Medical Store', 'Veterinary Clinic', 'Animal Hospital', 'Ambulance Service', 
                'Nursing Home', 'Home Healthcare'
            ],
            'Education' => [
                'School', 'College', 'University', 'Coaching Class', 'Tuition Class', 'Computer Institute', 
                'Spoken English Class', 'Language Institute', 'Dance Academy', 'Music Academy', 'Art Class', 
                'Preschool', 'Kindergarten', 'Montessori', 'Skill Development Institute', 'Online Education', 
                'Library'
            ],
            'Food & Beverage' => [
                'Restaurant', 'Cafe', 'Fast Food', 'Pizza Outlet', 'Burger Outlet', 'South Indian Restaurant', 
                'Punjabi Restaurant', 'Chinese Restaurant', 'Kathiyawadi Restaurant', 'Gujarati Restaurant', 
                'Sweet Shop', 'Bakery', 'Cake Shop', 'Ice Cream Parlour', 'Juice Center', 'Tea Stall', 
                'Coffee Shop', 'Catering Service', 'Tiffin Service', 'Cloud Kitchen', 'Hotel', 'Resort', 
                'Banquet Hall'
            ],
            'Real Estate' => [
                'Real Estate Agent', 'Builder', 'Developer', 'Construction Company', 'Property Consultant', 
                'Property Dealer', 'Architect', 'Interior Designer', 'Civil Contractor', 'Land Developer', 
                'Rental Agency'
            ],
            'Finance' => [
                'CA', 'Tax Consultant', 'GST Consultant', 'Loan Consultant', 'Insurance Agent', 
                'Mutual Fund Advisor', 'Stock Broker', 'Investment Advisor', 'Financial Planner', 
                'Micro Finance', 'NBFC', 'Bank'
            ],
            'Automobile' => [
                'Car Dealer', 'Bike Dealer', 'EV Dealer', 'Used Car Dealer', 'Used Bike Dealer', 
                'Car Service Center', 'Bike Service Center', 'Car Accessories', 'Bike Accessories', 
                'Tyre Shop', 'Battery Shop', 'Car Washing', 'Car Detailing', 'Garage', 'Auto Parts Store', 
                'Lubricant Dealer'
            ],
            'Agriculture' => [
                'Seeds Dealer', 'Fertilizer Dealer', 'Pesticide Dealer', 'Irrigation Systems', 
                'Drip Irrigation', 'Tractor Dealer', 'Farm Equipment', 'Dairy Farm', 'Poultry Farm', 
                'Organic Farming', 'Nursery', 'Greenhouse', 'Agro Chemicals', 'Cold Storage'
            ],
            'Fashion' => [
                'Men\'s Wear', 'Women\'s Wear', 'Kids Wear', 'Boutique', 'Tailor', 'Fashion Designer', 
                'Saree Store', 'Ethnic Wear', 'Western Wear', 'Footwear', 'Handbags', 'Accessories'
            ],
            'Beauty' => [
                'Beauty Parlour', 'Salon', 'Spa', 'Makeup Artist', 'Nail Studio', 'Tattoo Studio', 
                'Hair Studio', 'Cosmetic Clinic', 'Skincare Clinic', 'Wellness Center'
            ],
            'Home Improvement' => [
                'Hardware', 'Paint Store', 'Plywood', 'Laminate', 'Modular Kitchen', 'Furniture', 
                'Interior Design', 'Home Decor', 'Lighting', 'Electrical Shop', 'Plumbing', 
                'Sanitaryware', 'Tiles', 'Marble', 'Granite', 'Glass', 'Curtain Store'
            ],
            'Travel' => [
                'Travel Agency', 'Tour Operator', 'Visa Consultant', 'Passport Consultant', 'Hotel Booking', 
                'Cab Service', 'Car Rental', 'Bus Operator', 'Air Ticketing', 'Railway Booking', 
                'Holiday Planner'
            ],
            'IT & Software' => [
                'Software Company', 'SaaS Company', 'AI Company', 'Cloud Services', 'Cyber Security', 
                'ERP Company', 'CRM Company', 'Mobile App Development', 'Web Development', 
                'Hosting Provider', 'Domain Provider', 'Data Center'
            ],
            'Entertainment' => [
                'Cinema', 'Gaming Zone', 'Event Organizer', 'DJ Service', 'Music Studio', 
                'Recording Studio', 'Film Production', 'OTT Production', 'Influencer', 'YouTuber', 
                'Content Creator'
            ],
            'Professional Services' => [
                'Advocate', 'Chartered Accountant', 'Company Secretary', 'Architect', 'Engineer', 
                'Interior Designer', 'Consultant', 'HR Consultant', 'Recruitment Consultant', 
                'Business Consultant', 'Management Consultant'
            ],
            'Industrial' => [
                'CNC Machine', 'Industrial Machinery', 'Engineering Company', 'Fabrication', 'Welding', 
                'Industrial Automation', 'Conveyor Systems', 'Material Handling', 'Packaging Machinery', 
                'Industrial Chemicals', 'Compressor', 'Generator', 'Boiler', 'Industrial Tools'
            ]
        ];

        // Seed Sub Categories
        foreach ($subCategories as $catName => $subCats) {
            $category = BusinessCategory::where('name', $catName)->first();
            if ($category) {
                foreach ($subCats as $subCatName) {
                    BusinessSubCategory::firstOrCreate(
                        ['name' => $subCatName, 'business_category_id' => $category->id],
                        ['status' => 1]
                    );
                }
            }
        }
    }
}
