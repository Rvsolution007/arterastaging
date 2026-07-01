<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\BusinessCategory;
use App\Models\BusinessSubCategory;
use App\Models\BusinessType;
use App\Models\BusinessProduct;

$output = "# Duplicate Name Analysis Report\n\n";

// 1. Categories
$output .= "## 1. Categories\n";
$catDuplicates = BusinessCategory::select('name')
    ->groupBy('name')
    ->havingRaw('COUNT(id) > 1')
    ->get();

if ($catDuplicates->count() > 0) {
    foreach ($catDuplicates as $dup) {
        $cats = BusinessCategory::where('name', $dup->name)->get();
        $output .= "- **" . $dup->name . "** appears " . $cats->count() . " times (Root Level).\n";
    }
} else {
    $output .= "- No duplicate categories found.\n";
}

// 2. Sub Categories
$output .= "\n## 2. Sub Categories\n";
$subDuplicates = BusinessSubCategory::select('name')
    ->groupBy('name')
    ->havingRaw('COUNT(id) > 1')
    ->get();

if ($subDuplicates->count() > 0) {
    foreach ($subDuplicates as $dup) {
        $subs = BusinessSubCategory::with('business_category')->where('name', $dup->name)->get();
        $output .= "- **" . $dup->name . "** appears " . $subs->count() . " times. Parents:\n";
        foreach ($subs as $s) {
            $parentName = $s->business_category ? $s->business_category->name : 'No Parent';
            $output .= "  - Category: " . $parentName . " (ID: " . $s->business_category_id . ")\n";
        }
    }
} else {
    $output .= "- No duplicate sub categories found.\n";
}

// 3. Business Types
$output .= "\n## 3. Business Types\n";
$typeDuplicates = BusinessType::select('name')
    ->groupBy('name')
    ->havingRaw('COUNT(id) > 1')
    ->get();

if ($typeDuplicates->count() > 0) {
    foreach ($typeDuplicates as $dup) {
        $types = BusinessType::with('business_sub_category.business_category')->where('name', $dup->name)->get();
        $output .= "- **" . $dup->name . "** appears " . $types->count() . " times. Parents:\n";
        foreach ($types as $t) {
            $sub = $t->business_sub_category;
            $cat = $sub && $sub->business_category ? $sub->business_category->name : 'N/A';
            $subName = $sub ? $sub->name : 'N/A';
            $output .= "  - Category: " . $cat . " > Sub: " . $subName . "\n";
        }
    }
} else {
    $output .= "- No duplicate business types found.\n";
}

// 4. Products
$output .= "\n## 4. Products\n";
$prodDuplicates = BusinessProduct::select('name')
    ->groupBy('name')
    ->havingRaw('COUNT(id) > 1')
    ->get();

if ($prodDuplicates->count() > 0) {
    foreach ($prodDuplicates as $dup) {
        $prods = BusinessProduct::with('businessCategory', 'businessSubCategory', 'businessType')->where('name', $dup->name)->get();
        $output .= "- **" . $dup->name . "** appears " . $prods->count() . " times. Parents:\n";
        foreach ($prods as $p) {
            $cat = $p->businessCategory ? $p->businessCategory->name : 'N/A';
            $sub = $p->businessSubCategory ? $p->businessSubCategory->name : 'N/A';
            $type = $p->businessType ? $p->businessType->name : 'N/A';
            $output .= "  - Category: " . $cat . " > Sub: " . $sub . " > Type: " . $type . "\n";
        }
    }
} else {
    $output .= "- No duplicate products found.\n";
}

file_put_contents(__DIR__ . '/duplicate_analysis.md', $output);
echo "Analysis completed. Output written to duplicate_analysis.md";
