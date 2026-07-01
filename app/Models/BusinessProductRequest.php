<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessProductRequest extends Model
{
    use HasFactory;

    protected $table = 'business_product_requests';

    protected $fillable = [
        'business_id',
        'business_sub_category_id',
        'requested_name',
        'status',
        'resolved_product_id'
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function subCategory()
    {
        return $this->belongsTo(BusinessSubCategory::class, 'business_sub_category_id');
    }

    public function resolvedProduct()
    {
        return $this->belongsTo(BusinessProduct::class, 'resolved_product_id');
    }
}
