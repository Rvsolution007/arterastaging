<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CategoryBackgroundImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_category_id',
        'image',
        'aspect_ratio'
    ];

    public function businessCategory()
    {
        return $this->belongsTo(BusinessCategory::class);
    }
}
