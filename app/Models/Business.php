<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    use HasFactory;
    protected $table = "business";

    protected $fillable = [
        'logo',
        'name',
        'email',
        'mobile_no',
        'website',
        'address',
        'user_id',
        'business_category_id',
        'business_sub_category_ids',
        'business_type_id',
        'status',
        'is_default',
        'extra_emails',
        'extra_mobile_numbers',
        'extra_websites',
        'extra_addresses',
        'hidden_frame_fields',
        'brand_primary_color',
        'brand_secondary_color',
    ];

    protected $casts = [
        'business_sub_category_ids' => 'array',
        'extra_emails' => 'array',
        'extra_mobile_numbers' => 'array',
        'extra_websites' => 'array',
        'extra_addresses' => 'array',
        'hidden_frame_fields' => 'array',
    ];

    public function user()
    {
        return $this->hasOne("App\Models\User", "id", "user_id");
    }

    public function business_category()
    {
        return $this->hasOne("App\Models\BusinessCategory", "id", "business_category_id");
    }

    public function product_mappings()
    {
        return $this->hasMany(BusinessProductMapping::class, 'business_id');
    }

    public function products()
    {
        return $this->belongsToMany(BusinessProduct::class, 'business_product_mappings', 'business_id', 'business_product_id');
    }

    public function custom_product_requests()
    {
        return $this->hasMany(BusinessProductRequest::class, 'business_id');
    }

    public function sub_categories()
    {
        return $this->belongsToMany(BusinessSubCategory::class, 'business_sub_category_mappings', 'business_id', 'business_sub_category_id');
    }

    public function types()
    {
        return $this->belongsToMany(BusinessType::class, 'business_type_mappings', 'business_id', 'business_type_id');
    }

    // Natively querying against JSON array: usage -> whereJsonContains('business_sub_category_ids', $id)
}
