import re

file_path = 'c:/xampp/htdocs/brandkit/app/Http/Controllers/Api/AuthApi.php'
with open(file_path, 'r', encoding='utf-8') as f:
    content = f.read()

ad_config_code = r'''                'createdAt' => date('Y-m-d H:i:s', strtotime($user->created_at)),
                'adConfig' => [
                    'show_global_ads' => $user->shouldShowGlobalAds(),
                    'features' => [
                        'custom_post' => [
                            'base_limit' => $user->subscription ? $user->subscription->custom_post_edit_limit : 0,
                            'used' => $user->custom_post_used,
                            'max_ad_uses' => $user->subscription ? $user->subscription->custom_post_ad_reward_limit : 0,
                            'ad_used' => $user->custom_post_ad_used,
                            'state' => $user->getAdState('custom_post'),
                            'post_ad_flow_paid' => $user->getPostAdFlow('custom_post', true),
                            'post_ad_flow_free' => $user->getPostAdFlow('custom_post', false)
                        ],
                        'daily_drip' => [
                            'base_limit' => $user->subscription ? $user->subscription->daily_drip_limit : 0,
                            'used' => $user->daily_drip_used,
                            'max_ad_uses' => $user->subscription ? $user->subscription->daily_drip_ad_reward_limit : 0,
                            'ad_used' => $user->daily_drip_ad_used,
                            'state' => $user->getAdState('daily_drip')
                        ],
                        'magic_cloner' => [
                            'base_limit' => $user->subscription ? $user->subscription->magic_cloner_limit : 0,
                            'used' => $user->magic_cloner_used,
                            'max_ad_uses' => $user->subscription ? $user->subscription->magic_cloner_ad_reward_limit : 0,
                            'ad_used' => $user->magic_cloner_ad_used,
                            'state' => $user->getAdState('magic_cloner')
                        ],
                        'festival_post' => [
                            'base_limit' => $user->subscription ? $user->subscription->festival_post_limit : 0,
                            'used' => $user->festival_post_used,
                            'max_ad_uses' => $user->subscription ? $user->subscription->festival_post_ad_reward_limit : 0,
                            'ad_used' => $user->festival_post_ad_used,
                            'state' => $user->getAdState('festival_post'),
                            'post_ad_flow_paid' => $user->getPostAdFlow('festival_post', true),
                            'post_ad_flow_free' => $user->getPostAdFlow('festival_post', false)
                        ],
                        'category_post' => [
                            'base_limit' => $user->subscription ? $user->subscription->category_post_limit : 0,
                            'used' => $user->category_post_used,
                            'max_ad_uses' => $user->subscription ? $user->subscription->business_category_ad_reward_limit : 0,
                            'ad_used' => $user->business_category_ad_used,
                            'state' => $user->getAdState('category_post'),
                            'post_ad_flow_paid' => $user->getPostAdFlow('category_post', true),
                            'post_ad_flow_free' => $user->getPostAdFlow('category_post', false)
                        ]
                    ]
                ]'''

new_content = re.sub(
    r"'createdAt' => date\('Y-m-d H:i:s', strtotime\(\$user->created_at\)\),?", 
    ad_config_code, 
    content
)

with open(file_path, 'w', encoding='utf-8') as f:
    f.write(new_content)

print('AuthApi updated successfully with AdConfig')
