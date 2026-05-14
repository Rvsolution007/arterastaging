<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeaturePost extends Model
{
    use HasFactory;
   
    protected $table = "feature_post";

    protected $fillable = [
        'festival_id','category_id','custom_id','type'
    ];

    public function category()
    {
        return $this->hasOne("App\Models\Category", "id", "category_id");
    }

    public function festival()
    {
        return $this->hasOne("App\Models\Festivals", "id", "festival_id");
    }

    public function custom()
    {
        return $this->hasOne("App\Models\CustomPost", "id", "custom_id");
    }
}
