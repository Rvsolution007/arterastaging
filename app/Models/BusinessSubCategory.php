<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessSubCategory extends Model
{
    use HasFactory;
    protected $table = "business_sub_category";

    protected $fillable = [
        'name',
        'business_category_id',
        'icon',
        'status',
        'image_1',
        'image_2',
        'more_images'
    ];

    protected $casts = [
        'more_images' => 'array'
    ];

    public function business_category()
    {
        return $this->hasOne("App\Models\BusinessCategory", "id", "business_category_id");
    }
}
