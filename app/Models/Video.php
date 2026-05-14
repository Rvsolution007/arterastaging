<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Video extends Model
{
    use HasFactory;

    protected $table = "video";

    protected $fillable = [
        'type','festival_id','category_id','business_category_id','paid','language_id','video','status'
    ];

    public function festival()
    {
        return $this->hasOne("App\Models\Festivals", "id", "festival_id");
    }

    public function category()
    {
        return $this->hasOne("App\Models\Category", "id", "category_id");
    }

    public function businessCategory()
    {
        return $this->hasOne("App\Models\BusinessCategory", "id", "business_category_id");
    }
    
    public function language()
    {
        return $this->hasOne("App\Models\Language", "id", "language_id");
    }
}
