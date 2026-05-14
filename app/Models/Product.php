<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = "product";

    protected $fillable = [
        'title', 'image', 'status', 'description', 'price', 'discount_price',
        'product_category_id', 'user_id', 'category_name', 'sku', 'unit',
        'mrp', 'sale_price', 'gst_percent', 'hsn_code',
    ];

    protected $casts = [
        'mrp' => 'integer',
        'sale_price' => 'integer',
        'gst_percent' => 'decimal:2',
    ];

    protected $appends = ['display_name', 'image_url'];

    public function getImageUrlAttribute()
    {
        if (!$this->image) return null;
        if (str_starts_with($this->image, 'http')) return $this->image;
        
        // Handle images stored directly in public/uploads/products (from API)
        if (str_starts_with($this->image, 'products/')) {
            return asset('uploads/' . $this->image);
        }
        
        // Handle images stored directly in public/uploads (if already prefixed)
        if (str_starts_with($this->image, 'uploads/')) {
            return asset($this->image);
        }
        
        // Default for standard uploads (filename only, from Admin Panel)
        return asset('uploads/' . $this->image);
    }

    public function ProductCategory()
    {
        return $this->hasOne("App\Models\ProductCategory", "id", "product_category_id");
    }

    public function customValues()
    {
        return $this->hasMany(CatalogueCustomValue::class, 'product_id');
    }

    public function combos()
    {
        return $this->hasMany(ProductCombo::class, 'product_id');
    }

    public function variations()
    {
        return $this->hasMany(ProductVariation::class, 'product_id');
    }

    /**
     * Dynamic display name resolution:
     * 1. Try is_title column value
     * 2. Fallback to is_unique column value
     * 3. Fallback to native title
     */
    public function getDisplayNameAttribute()
    {
        // Try title column
        $titleCol = CatalogueCustomColumn::where('user_id', $this->user_id)
            ->where('is_title', true)->first();
        if ($titleCol) {
            $val = $this->customValues->firstWhere('column_id', $titleCol->id);
            if ($val && !empty($val->value)) {
                $prefix = $this->category_name ? $this->category_name . ' - ' : '';
                return $prefix . $val->value;
            }
        }

        // Try unique column
        $uniqueCol = CatalogueCustomColumn::where('user_id', $this->user_id)
            ->where('is_unique', true)->first();
        if ($uniqueCol) {
            $val = $this->customValues->firstWhere('column_id', $uniqueCol->id);
            if ($val && !empty($val->value)) {
                return $val->value;
            }
        }

        return $this->title ?: 'Product #' . $this->id;
    }

    /**
     * Build dynamic description from all active custom column values.
     */
    public function getDynamicDescription(): string
    {
        $lines = [];
        $columns = CatalogueCustomColumn::where('user_id', $this->user_id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        foreach ($columns as $col) {
            if ($col->is_category) continue;
            $val = $this->customValues->firstWhere('column_id', $col->id);
            if ($val && !empty($val->value)) {
                $displayVal = $val->value;
                // Try decode JSON for multiselect
                $decoded = json_decode($displayVal, true);
                if (is_array($decoded)) {
                    $displayVal = implode(', ', $decoded);
                }
                $lines[] = $col->name . ': ' . $displayVal;
            }
        }

        return implode("\n", $lines);
    }
}

