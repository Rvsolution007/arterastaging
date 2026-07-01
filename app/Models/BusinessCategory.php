<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessCategory extends Model
{
    use HasFactory;
    protected $table = "business_category";

    protected $fillable = [
        'name','icon','slug','status'
    ];

    public function subCategories()
    {
        return $this->hasMany(BusinessSubCategory::class, 'business_category_id');
    }

    public function types()
    {
        return $this->hasManyThrough(BusinessType::class, BusinessSubCategory::class, 'business_category_id', 'business_sub_category_id');
    }

    public function products()
    {
        return $this->hasMany(BusinessProduct::class, 'business_category_id');
    }
}
