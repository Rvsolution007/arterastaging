<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomFrameImageType extends Model
{
    use HasFactory;

    protected $table = 'custom_frame_image_types';

    protected $fillable = [
        'name',
        'status',
    ];

    public function subCategories()
    {
        return $this->belongsToMany(BusinessSubCategory::class, 'image_type_sub_category', 'custom_frame_image_type_id', 'business_sub_category_id');
    }

    public function frames()
    {
        return $this->hasMany(BusinessCustomFrame::class, 'custom_frame_image_type_id');
    }
}
