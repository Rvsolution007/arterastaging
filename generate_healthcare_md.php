<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$catId = 5; // Healthcare

$subCats = DB::table('business_sub_category')->where('business_category_id', $catId)->get();

$md = "# 🏥 Healthcare (Category ID: 5) — Structured Database View\n\n";
$md .= "> **Note**: This file shows the EXACT Sub-categories, Business Types, and Products currently in the database for Healthcare.\n";
$md .= "> \n> **Format**: Sub Category > Business Type (Count) > Product/Service | Status\n\n---\n\n";

$count = 1;
foreach ($subCats as $sub) {
    $subName = $sub->name;
    $btypes = DB::table('business_types')->where('business_sub_category_id', $sub->id)->get();
    
    $md .= "## $count. $subName\n\n";
    
    if ($btypes->isEmpty()) {
        $products = DB::table('business_products')
            ->where('business_category_id', $catId)
            ->where('business_sub_category_id', $sub->id)
            ->whereNull('business_type_id')
            ->get();
            
        $pCount = $products->count();
        $md .= "*(No Business Type) - ($pCount Products)*\n";
        
        if ($pCount > 0) {
            $md .= "| Service/Product | Status |\n";
            $md .= "|---------|-------------|\n";
            foreach ($products as $p) {
                $status = $p->status == 1 ? 'Active' : 'Inactive';
                $md .= "| {$p->name} | $status |\n";
            }
            $md .= "\n";
        }
    } else {
        foreach ($btypes as $bt) {
            $btName = $bt->name;
            $products = DB::table('business_products')
                ->where('business_category_id', $catId)
                ->where('business_sub_category_id', $sub->id)
                ->where('business_type_id', $bt->id)
                ->get();
                
            $pCount = $products->count();
            $md .= "**Business Type: $btName ($pCount Products)**\n";
            
            if ($pCount > 0) {
                $md .= "| Service/Product | Status |\n";
                $md .= "|---------|-------------|\n";
                foreach ($products as $p) {
                    $status = $p->status == 1 ? 'Active' : 'Inactive';
                    $md .= "| {$p->name} | $status |\n";
                }
                $md .= "\n";
            } else {
                $md .= "*(No products connected yet)*\n\n";
            }
        }
    }
    
    $count++;
}

file_put_contents('C:\Users\Admim\.gemini\antigravity\brain\149bd6d8-1407-41cf-9b5a-e1f454936a81\Healthcare.md', $md);
echo "Generated Healthcare.md from DB\n";
