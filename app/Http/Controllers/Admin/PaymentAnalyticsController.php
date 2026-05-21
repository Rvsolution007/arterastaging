<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\SubscriptionInfo; // Ensure this exists or mock appropriately

class PaymentAnalyticsController extends Controller
{
    /**
     * Display the MRR, ARR, and LTV advanced payment dashboard.
     */
    public function index()
    {
        // For demonstration, we will calculate metrics.
        // In a real app, this joins with the actual Subscription/Payments tables.
        
        $activeSubscribers = User::where('is_subscribe', 1)->count();
        $totalUsers = User::count();
        $churnedUsers = User::where('is_subscribe', 0)->whereNotNull('subscription_end_date')->count();

        // Assume average premium plan price is $29/mo
        $arpu = 29; 

        // Monthly Recurring Revenue (MRR)
        $mrr = $activeSubscribers * $arpu;

        // Annual Recurring Revenue (ARR)
        $arr = $mrr * 12;

        // Churn Rate = Churned Users / Total Users ever subscribed
        $totalEverSubscribed = $activeSubscribers + $churnedUsers;
        $churnRate = $totalEverSubscribed > 0 ? ($churnedUsers / $totalEverSubscribed) : 0;
        
        // Lifetime Value (LTV) = ARPU / Churn Rate
        // If churn rate is 0, we estimate it conservatively at 5% to avoid division by zero
        $effectiveChurnRate = $churnRate > 0 ? $churnRate : 0.05;
        $ltv = $arpu / $effectiveChurnRate;

        return view('admin.analytics.revenue', compact(
            'activeSubscribers', 'mrr', 'arr', 'churnRate', 'ltv'
        ));
    }
}
