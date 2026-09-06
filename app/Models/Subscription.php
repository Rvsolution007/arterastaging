<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    use HasFactory;
    protected $table = "subscription_plan";

    protected $fillable = [
        'plan_name','duration','duration_type','plan_price','discount_price','status','plan_detail',
        'monthly_price', 'monthly_discount_price', 'yearly_price', 'yearly_discount_price',
        'business_limit','custom_post_edit_limit','daily_drip_limit',
        'festival_post_limit','category_post_limit',
        'custom_post_ad_reward','daily_drip_ad_reward',
        'festival_post_ad_reward','category_ad_reward',
        'custom_post_ad_reward_limit','daily_drip_ad_reward_limit',
        'festival_post_ad_reward_limit','category_ad_reward_limit',
        'google_product_enable','google_product_id','photoroom_bg_limit',
        'ai_image_limit', 'business_ai_generation_credit_cost'
    ];

    public function aiImageAccesses()
    {
        return $this->hasMany(SubscriptionAiImageAccess::class, 'subscription_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
