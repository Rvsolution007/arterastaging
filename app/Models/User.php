<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'mobile_no',
        'image',
        'status',
        'country',
        'subscription_id',
        'subscription_start_date',
        'subscription_end_date',
        'is_subscribe',
        'user_language',
        'login_type',
        // Security fix: 'api_token' removed — prevents token injection via mass assignment
        'email_verified_at',
        // Security fix: 'user_type' removed — prevents privilege escalation via mass assignment
        'business_limit',
        "referral_code",
        'registration_source',
        "current_balance",
        "total_balance",
        // Security fix: 'is_partner' removed — prevents partner fraud via mass assignment
        // Security fix: 'partner_commission_percent' removed — prevents commission fraud via mass assignment
        "last_notification_read_at",
        "custom_post_used",
        "daily_drip_used",
        "festival_post_used",
        "category_post_used",
        "photoroom_bg_used",
        "ai_image_used",
        "custom_post_ad_used",
        "daily_drip_ad_used",
        "festival_post_ad_used",
        "category_ad_used",
        "limits_reset_at",
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'api_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'google_linked_at' => 'datetime',
        'limits_reset_at' => 'datetime',
    ];

    public function subscription()
    {
        return $this->hasOne("App\Models\Subscription", "id", "subscription_id");
    }

    public function getActiveSubscriptionAttribute()
    {
        if ($this->subscription_id && $this->subscription) {
            return $this->subscription;
        }
        return \App\Models\Subscription::where('plan_price', 0)->first() ?? \App\Models\Subscription::first();
    }

    public function language()
    {
        return $this->hasOne("App\Models\Language", "id", "user_language");
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'user_id');
    }

    /**
     * Reset monthly usage counters if a new billing month has started.
     */
    public function resetLimitsIfNeeded()
    {
        if (!$this->limits_reset_at || $this->limits_reset_at->month !== now()->month) {
            $this->update([
                'custom_post_used' => 0,
                'daily_drip_used' => 0,
                'festival_post_used' => 0,
                'category_post_used' => 0,
                'photoroom_bg_used' => 0,
                'ai_image_used' => 0,
                'custom_post_ad_used' => 0,
                'daily_drip_ad_used' => 0,
                'festival_post_ad_used' => 0,
                'category_ad_used' => 0,
                'limits_reset_at' => now(),
            ]);
        }
    }

    /**
     * Check if the user can use a specific feature.
     * @param string $feature  One of: 'custom_post', 'daily_drip', 'photoroom_bg'
     * @return bool
     */
    public function canUseFeature($feature)
    {
        $this->resetLimitsIfNeeded();
        $plan = $this->active_subscription;
        if (!$plan) {
            $plan = \App\Models\Subscription::where('plan_price', 0)->first();
        }
        if (!$plan) return false;

        $limitMap = [
            'custom_post'            => ['used' => 'custom_post_used',            'limit' => 'custom_post_edit_limit'],
            'festival_post'          => ['used' => 'festival_post_used',          'limit' => 'festival_post_limit'],
            'category_post' => ['used' => 'category_post_used', 'limit' => 'category_post_limit'],
            'photoroom_bg'           => ['used' => 'photoroom_bg_used',           'limit' => 'photoroom_bg_limit'],
            'ai_image'               => ['used' => 'ai_image_used',               'limit' => 'ai_image_limit'],
        ];

        if (!isset($limitMap[$feature])) return false;

        $used  = $this->{$limitMap[$feature]['used']};
        $limit = $plan->{$limitMap[$feature]['limit']};

        return $used < $limit;
    }

    /**
     * Consume one unit of a feature.
     * @param string $feature  One of: 'custom_post', 'daily_drip', 'photoroom_bg'
     * @return bool
     */
    public function consumeFeature($feature)
    {
        if (!$this->canUseFeature($feature)) return false;

        $fieldMap = [
            'custom_post'            => 'custom_post_used',
            'festival_post'          => 'festival_post_used',
            'category_post' => 'category_post_used',
            'photoroom_bg'           => 'photoroom_bg_used',
            'ai_image'               => 'ai_image_used',
        ];

        $this->increment($fieldMap[$feature]);
        return true;
    }

    /**
     * Get remaining usage for a feature.
     * @param string $feature
     * @return int
     */
    public function getRemainingUsage($feature)
    {
        $this->resetLimitsIfNeeded();
        $plan = $this->active_subscription;
        if (!$plan) {
            $plan = \App\Models\Subscription::where('plan_price', 0)->first();
        }
        if (!$plan) return 0;

        $limitMap = [
            'custom_post'            => ['used' => 'custom_post_used',            'limit' => 'custom_post_edit_limit'],
            'festival_post'          => ['used' => 'festival_post_used',          'limit' => 'festival_post_limit'],
            'category_post' => ['used' => 'category_post_used', 'limit' => 'category_post_limit'],
            'photoroom_bg'           => ['used' => 'photoroom_bg_used',           'limit' => 'photoroom_bg_limit'],
            'ai_image'               => ['used' => 'ai_image_used',               'limit' => 'ai_image_limit'],
        ];

        if (!isset($limitMap[$feature])) return 0;

        return max(0, $plan->{$limitMap[$feature]['limit']} - $this->{$limitMap[$feature]['used']});
    }

    /**
     * Check if AdMob Reward is enabled and within the monthly limit for a feature.
     * @param string $feature
     * @return bool
     */
    public function isAdRewardEnabledForFeature($feature)
    {
        $this->resetLimitsIfNeeded();
        $plan = $this->active_subscription;
        if (!$plan) {
            $plan = \App\Models\Subscription::where('plan_price', 0)->first();
        }
        if (!$plan) return false;

        $adMap = [
            'custom_post'            => ['enabled' => 'custom_post_ad_reward',       'limit' => 'custom_post_ad_reward_limit',       'used' => 'custom_post_ad_used'],
            'festival_post'          => ['enabled' => 'festival_post_ad_reward',     'limit' => 'festival_post_ad_reward_limit',     'used' => 'festival_post_ad_used'],
            'category_post' => ['enabled' => 'category_ad_reward', 'limit' => 'category_ad_reward_limit', 'used' => 'category_ad_used'],
        ];

        if (!isset($adMap[$feature])) return false;

        $isEnabled = (bool) $plan->{$adMap[$feature]['enabled']};
        if (!$isEnabled) return false;

        $limit = $plan->{$adMap[$feature]['limit']};
        $used = $this->{$adMap[$feature]['used']};

        return $used < $limit;
    }

    /**
     * Consume an Ad Reward usage (track that an ad was watched).
     * @param string $feature
     * @return bool
     */
    public function consumeAdReward($feature)
    {
        if (!$this->isAdRewardEnabledForFeature($feature)) return false;

        $adMap = [
            'custom_post'            => 'custom_post_ad_used',
            'festival_post'          => 'festival_post_ad_used',
            'category_post' => 'category_ad_used',
        ];

        if (!isset($adMap[$feature])) return false;

        $this->increment($adMap[$feature]);
        return true;
    }

    /**
     * Determine the ad state for a feature.
     * Returns: 'no_ads' | 'all_ads' | 'rewarded_interstitial' | 'locked'
     */
    public function getAdState($feature)
    {
        $this->resetLimitsIfNeeded();
        $plan = $this->active_subscription;
        if (!$plan) return 'locked';

        $limits = [
            'custom_post'            => ['base' => 'custom_post_edit_limit',       'used' => 'custom_post_used',            'ad_limit' => 'custom_post_ad_reward_limit',       'ad_used' => 'custom_post_ad_used'],
            'festival_post'          => ['base' => 'festival_post_limit',          'used' => 'festival_post_used',          'ad_limit' => 'festival_post_ad_reward_limit',     'ad_used' => 'festival_post_ad_used'],
            'category_post' => ['base' => 'category_post_limit', 'used' => 'category_post_used', 'ad_limit' => 'category_ad_reward_limit', 'ad_used' => 'category_ad_used'],
        ];

        if (!isset($limits[$feature])) return 'locked';

        $baseLimit = $plan->{$limits[$feature]['base']};
        $used = $this->{$limits[$feature]['used']};
        $adLimit = $plan->{$limits[$feature]['ad_limit']};
        $adUsed = $this->{$limits[$feature]['ad_used']};

        if ($baseLimit > 0) {
            if ($used < $baseLimit) {
                return 'no_ads'; // Within base limit
            } else {
                if ($adUsed < $adLimit) {
                    return 'rewarded_interstitial'; // Base limit reached, but ad limit available
                } else {
                    return 'locked'; // Both base and ad limits reached
                }
            }
        } else {
            // Base limit is 0
            if ($adUsed < $adLimit) {
                return 'all_ads'; // All ads from the start
            } else {
                return 'locked'; // Ad limit reached
            }
        }
    }

    /**
     * Check if ALL features in user's plan have base_limit = 0
     * (triggers global banner/native ads)
     */
    public function shouldShowGlobalAds()
    {
        $plan = $this->active_subscription;
        if (!$plan) {
            $plan = \App\Models\Subscription::where('plan_price', 0)->first();
        }
        if (!$plan) return false;

        $settings = \Illuminate\Support\Arr::pluck(
            \App\Models\AdsSetting::all()->toArray(), 'key_value', 'key_name'
        );

        $adsGlobalEnable = $settings['ads_enable'] ?? '0';
        $bannerEnable = $settings['banner_ads_enable'] ?? '0';

        if ($adsGlobalEnable != '1' || $bannerEnable != '1') {
            return false;
        }

        return $plan->plan_price == 0;
    }

    /**
     * For Festival/Category posts: get the ad flow based on paid/free status.
     * Returns: 'no_ads' | 'rewarded_then_interstitial' | 'interstitial_only' | 'locked'
     */
    public function getPostAdFlow($feature, $isPaid)
    {
        $state = $this->getAdState($feature);

        if ($state === 'locked') return 'locked';
        if ($state === 'no_ads') return 'no_ads';

        // For 'all_ads' or 'rewarded_interstitial', check paid/free
        if ($isPaid) {
            return 'rewarded_then_interstitial';
        } else {
            return 'interstitial_only';
        }
    }

    /**
     * Get the complete ad config payload for API responses
     */
    public function getAdConfigPayload()
    {
        $plan = $this->active_subscription;
        if (!$plan) {
            $plan = \App\Models\Subscription::where('plan_price', 0)->first();
        }
        
        $settings = \Illuminate\Support\Arr::pluck(
            \App\Models\AdsSetting::all()->toArray(), 'key_value', 'key_name'
        );
        
        return [
            'show_global_ads' => $this->shouldShowGlobalAds(),
            'admob' => [
                'banner_ads_id' => $settings['banner_ads_id'] ?? '',
                'interstitial_ads_id' => $settings['interstitial_ads_id'] ?? '',
                'rewarded_ads_id' => $settings['rewarded_ads_id'] ?? '',
                'native_ads_id' => $settings['native_ads_id'] ?? '',
            ],
            'features' => [
                'custom_post' => [
                    'base_limit' => $plan ? $plan->custom_post_edit_limit : 0,
                    'used' => $this->custom_post_used,
                    'ad_limit' => $plan ? $plan->custom_post_ad_reward_limit : 0,
                    'ad_used' => $this->custom_post_ad_used,
                    'state' => $this->getAdState('custom_post'),
                    'max_ad_uses' => $plan ? $plan->custom_post_ad_reward_limit : 0,
                ],
                'festival_post' => [
                    'base_limit' => $plan ? $plan->festival_post_limit : 0,
                    'used' => $this->festival_post_used,
                    'ad_limit' => $plan ? $plan->festival_post_ad_reward_limit : 0,
                    'ad_used' => $this->festival_post_ad_used,
                    'state' => $this->getAdState('festival_post'),
                    'max_ad_uses' => $plan ? $plan->festival_post_ad_reward_limit : 0,
                    'post_ad_flow_free' => $this->getPostAdFlow('festival_post', false),
                    'post_ad_flow_paid' => $this->getPostAdFlow('festival_post', true),
                ],
                'category_post' => [
                    'base_limit' => $plan ? $plan->category_post_limit : 0,
                    'used' => $this->category_post_used,
                    'ad_limit' => $plan ? $plan->category_ad_reward_limit : 0,
                    'ad_used' => $this->category_ad_used,
                    'state' => $this->getAdState('category_post'),
                    'max_ad_uses' => $plan ? $plan->category_ad_reward_limit : 0,
                    'post_ad_flow_free' => $this->getPostAdFlow('category_post', false),
                    'post_ad_flow_paid' => $this->getPostAdFlow('category_post', true),
                ],
            ]
        ];
    }
}
