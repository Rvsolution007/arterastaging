<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Story extends Model
{
    use HasFactory;
    protected $table = "story";

    protected $fillable = [
        'story_type','festival_id','category_id','custom_category_id','subscription_id','image','external_link_title','external_link','status','user_id',
        'expire_type', 'expire_value', 'expires_at', 'sort_order', 'story_images'
    ];

    protected $casts = [
        'story_images' => 'array',
        'expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->hasOne("App\Models\User", "id", "user_id");
    }

    public function festival()
    {
        return $this->hasOne("App\Models\Festivals", "id", "festival_id");
    }

    public function category()
    {
        return $this->hasOne("App\Models\Category", "id", "category_id");
    }

    public function custom()
    {
        return $this->hasOne("App\Models\CustomPost", "id", "custom_category_id");
    }

    public function subscription()
    {
        return $this->hasOne("App\Models\Subscription", "id", "subscription_id");
    }

}
