<?php

require 'vendor/autoload.php'; 
$app = require_once 'bootstrap/app.php'; 
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap(); 

$emptyCategories = App\Models\BusinessCategory::whereDoesntHave('subCategories.products')->get(['id', 'name']);
$emptySubCategories = App\Models\BusinessSubCategory::doesntHave('products')->get(['id', 'name']);
$emptyTypes = App\Models\BusinessType::doesntHave('products')->get(['id', 'name']);

$output = "# Entities without Products\n\n";

$output .= "## Categories (0 Products)\n";
foreach($emptyCategories as $c) { $output .= "- " . $c->name . " (ID: " . $c->id . ")\n"; }

$output .= "\n## Sub Categories (0 Products)\n";
foreach($emptySubCategories as $s) { $output .= "- " . $s->name . " (ID: " . $s->id . ")\n"; }

$output .= "\n## Business Types (0 Products)\n";
foreach($emptyTypes as $t) { $output .= "- " . $t->name . " (ID: " . $t->id . ")\n"; }

file_put_contents('empty_entities.txt', $output);
