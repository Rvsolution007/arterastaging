<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessType extends Model
{
    use HasFactory;

    protected $table = 'business_types';

    protected $fillable = [
        'business_sub_category_id',
        'name',
        'slug',
        'icon',
        'status'
    ];

    public function business_sub_category()
    {
        return $this->belongsTo(BusinessSubCategory::class, 'business_sub_category_id');
    }

    public function products()
    {
        return $this->hasMany(BusinessProduct::class, 'business_type_id');
    }
}
