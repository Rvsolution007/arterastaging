<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Models\BusinessCategory;
use App\Models\BusinessSubCategory;
use App\Models\BusinessType;
use App\Models\BusinessProduct;
use Illuminate\Support\Facades\DB;

class EnterpriseMasterDataSeeder extends Seeder
{
    public function run()
    {
        // 1. Seed Product Types
        $productTypes = [
            'Physical Product', 'Service', 'Digital Product', 'Software', 
            'Subscription', 'Membership', 'Package', 'Rental', 
            'Consultation', 'Maintenance (AMC)', 'Installation', 
            'Repair Service', 'Training', 'Certification', 'Course', 
            'License', 'Warranty', 'Insurance Plan', 'Booking', 
            'Event Ticket', 'Donation', 'Gift Card', 'Voucher', 
            'Bundle', 'Custom Solution', 'Other'
        ];

        foreach ($productTypes as $type) {
            DB::table('product_types')->updateOrInsert(
                ['slug' => Str::slug($type)],
                ['name' => $type, 'status' => 1, 'created_at' => now(), 'updated_at' => now()]
            );
        }

        // 2. Generate Slugs for BusinessCategory
        $categories = BusinessCategory::all();
        foreach ($categories as $cat) {
            $cat->slug = Str::slug($cat->name) . '-' . $cat->id;
            DB::table('business_category')->where('id', $cat->id)->update(['slug' => $cat->slug]);
        }

        // 3. Generate Slugs and update has_business_type for BusinessSubCategory
        $subCategories = BusinessSubCategory::all();
        foreach ($subCategories as $subCat) {
            $subCat->slug = Str::slug($subCat->name) . '-' . $subCat->id;
            
            $hasTypes = BusinessType::where('business_sub_category_id', $subCat->id)->exists() ? 1 : 0;
            
            DB::table('business_sub_category')->where('id', $subCat->id)->update([
                'slug' => $subCat->slug,
                'has_business_type' => $hasTypes
            ]);
        }

        // 4. Generate Slugs for BusinessType
        DB::table('business_types')->orderBy('id')->chunk(1000, function ($types) {
            foreach ($types as $type) {
                DB::table('business_types')->where('id', $type->id)->update([
                    'slug' => Str::slug($type->name) . '-' . $type->id
                ]);
            }
        });
        
        // 5. Generate Slugs for BusinessProduct
        DB::table('business_products')->orderBy('id')->chunk(1000, function ($products) {
            foreach ($products as $prod) {
                DB::table('business_products')->where('id', $prod->id)->update([
                    'slug' => Str::slug($prod->name) . '-' . $prod->id
                ]);
            }
        });
        
        echo "Enterprise Data Seeded Successfully!\n";
    }
}
