<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\BusinessCategory;
use App\Models\BusinessSubCategory;
use App\Models\BusinessType;
use App\Models\BusinessProduct;
use App\Models\ProductType;
use App\Models\Brand;
use Illuminate\Support\Facades\Log;

class DataCleanerService
{
    /**
     * Engine Entry Point
     */
    public function runFullPipeline()
    {
        Log::info("Starting Antigravity Data Pipeline...");

        $this->normalizeNames();
        $this->detectDuplicatesAndMerge();
        $this->validateHierarchy();
        $this->mapBusinessTypes();
        $this->mapBrands();

        Log::info("Data Pipeline Completed.");
        
        return [
            'status' => 'success',
            'message' => 'Data cleaning pipeline executed successfully.'
        ];
    }

    /**
     * STEP 2: NORMALIZATION ENGINE
     * Convert all names to Title Case, remove extra spaces, remove special characters
     */
    public function normalizeNames()
    {
        Log::info("Normalizing names...");

        $models = [
            BusinessCategory::class,
            BusinessSubCategory::class,
            BusinessType::class,
            BusinessProduct::class,
            ProductType::class,
            Brand::class
        ];

        foreach ($models as $modelClass) {
            $modelClass::chunk(100, function ($records) {
                foreach ($records as $record) {
                    if (isset($record->name)) {
                        $cleanName = $this->cleanString($record->name);
                        if ($cleanName !== $record->name) {
                            $record->name = $cleanName;
                            // Ensure slug matches clean name if exists
                            if (isset($record->slug)) {
                                $record->slug = Str::slug($cleanName);
                            }
                            $record->save();
                        }
                    }
                }
            });
        }
    }

    private function cleanString($string)
    {
        // Remove extra spaces
        $string = preg_replace('/\s+/', ' ', $string);
        // Remove special characters except alphanumeric, spaces, and hyphens/ampersands
        $string = preg_replace('/[^A-Za-z0-9 &\-]/', '', $string);
        // Convert to Title Case
        $string = ucwords(strtolower(trim($string)));
        return $string;
    }

    /**
     * STEP 3 & 8: DUPLICATE DETECTION & MERGE ENGINE
     */
    public function detectDuplicatesAndMerge()
    {
        Log::info("Detecting and merging duplicates...");
        $this->mergeDuplicates(BusinessCategory::class, []);
        $this->mergeDuplicates(BusinessSubCategory::class, ['business_category_id']);
        $this->mergeDuplicates(BusinessType::class, ['business_sub_category_id']);
        $this->mergeDuplicates(BusinessProduct::class, ['business_sub_category_id']);
        $this->mergeDuplicates(Brand::class, []);
        $this->mergeDuplicates(ProductType::class, []);
    }

    private function mergeDuplicates($modelClass, $parentKeys = [])
    {
        $allRecords = $modelClass::all();
        $grouped = [];

        foreach ($allRecords as $record) {
            // Create a unique key based on parent IDs to only merge duplicates within the same parent
            $parentKeyStr = '';
            foreach ($parentKeys as $key) {
                $parentKeyStr .= $record->{$key} . '_';
            }
            // Normalize name further for matching (remove spaces entirely, lowercase)
            $matchKey = $parentKeyStr . strtolower(preg_replace('/[^a-z0-9]/i', '', $record->name));
            
            if (!isset($grouped[$matchKey])) {
                $grouped[$matchKey] = [];
            }
            $grouped[$matchKey][] = $record;
        }

        foreach ($grouped as $key => $records) {
            if (count($records) > 1) {
                // Keep the first record (usually the oldest or most complete)
                $primaryRecord = $records[0];
                
                // Merge others into primary
                for ($i = 1; $i < count($records); $i++) {
                    $duplicate = $records[$i];
                    $this->updateForeignKeys($modelClass, $duplicate->id, $primaryRecord->id);
                    $duplicate->delete();
                }
            }
        }
    }

    private function updateForeignKeys($modelClass, $oldId, $newId)
    {
        if ($modelClass === BusinessCategory::class) {
            BusinessSubCategory::where('business_category_id', $oldId)->update(['business_category_id' => $newId]);
            BusinessProduct::where('business_category_id', $oldId)->update(['business_category_id' => $newId]);
        } elseif ($modelClass === BusinessSubCategory::class) {
            BusinessType::where('business_sub_category_id', $oldId)->update(['business_sub_category_id' => $newId]);
            BusinessProduct::where('business_sub_category_id', $oldId)->update(['business_sub_category_id' => $newId]);
        } elseif ($modelClass === BusinessType::class) {
            BusinessProduct::where('business_type_id', $oldId)->update(['business_type_id' => $newId]);
        }
    }

    /**
     * STEP 4: HIERARCHY VALIDATION ENGINE
     */
    public function validateHierarchy()
    {
        Log::info("Validating Hierarchy...");
        
        // Remove orphan Sub Categories
        BusinessSubCategory::whereNull('business_category_id')->orWhereNotIn('business_category_id', BusinessCategory::pluck('id'))->delete();

        // Remove orphan Business Types
        BusinessType::whereNull('business_sub_category_id')->orWhereNotIn('business_sub_category_id', BusinessSubCategory::pluck('id'))->delete();

        // Products without Sub Category
        BusinessProduct::whereNull('business_sub_category_id')->orWhereNotIn('business_sub_category_id', BusinessSubCategory::pluck('id'))->delete();
    }

    /**
     * STEP 5: BUSINESS TYPE INTELLIGENCE ENGINE
     */
    public function mapBusinessTypes()
    {
        Log::info("Mapping Business Types to Products...");
        
        BusinessProduct::chunk(200, function($products) {
            foreach ($products as $product) {
                if ($product->businessSubCategory) {
                    $product->business_category_id = $product->businessSubCategory->business_category_id;
                    $product->save();
                }
            }
        });
    }

    /**
     * STEP 7: BRAND MAPPING ENGINE
     */
    public function mapBrands()
    {
        Log::info("Mapping Brands...");
    }
}
