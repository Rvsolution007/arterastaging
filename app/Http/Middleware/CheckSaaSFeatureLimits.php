<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckSaaSFeatureLimits
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $feature = null)
    {
        $user = auth()->guard('api')->user();
        if (!$user) {
            $user = auth()->user(); // Fallback for web testing if needed
        }

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        if ($feature) {
            if (!$user->canUseFeature($feature)) {
                $isAdEnabled = $user->isAdRewardEnabledForFeature($feature);
                
                return response()->json([
                    'success' => false,
                    'is_limit_reached' => true,
                    'feature' => $feature,
                    'ad_reward_enabled' => $isAdEnabled,
                    'message' => 'Monthly limit reached for ' . str_replace('_', ' ', $feature) . '. ' . ($isAdEnabled ? 'Watch an AD to get more.' : 'Please upgrade your plan to continue.')
                ], 403);
            }
        }

        return $next($request);
    }
}
