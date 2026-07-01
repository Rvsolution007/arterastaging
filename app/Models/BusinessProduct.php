<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessProduct extends Model
{
    use HasFactory;

    protected $table = 'business_products';

    protected $fillable = [
        'business_category_id',
        'business_sub_category_id',
        'business_type_id',
        'product_type_id',
        'brand_id',
        'name',
        'slug',
        'keywords',
        'icon',
        'status',
        'sort_order'
    ];

    protected $casts = [
        'brand_id' => 'array',
    ];

    public function businessCategory()
    {
        return $this->belongsTo(BusinessCategory::class, 'business_category_id');
    }

    public function businessSubCategory()
    {
        return $this->belongsTo(BusinessSubCategory::class, 'business_sub_category_id');
    }

    public function businessType()
    {
        return $this->belongsTo(BusinessType::class, 'business_type_id');
    }

    public function productType()
    {
        return $this->belongsTo(ProductType::class, 'product_type_id');
    }

    public function getBrandsAttribute()
    {
        $brandIds = $this->brand_id;
        if (empty($brandIds) || !is_array($brandIds)) {
            return collect();
        }
        return \App\Models\Brand::whereIn('id', $brandIds)->get();
    }
}
