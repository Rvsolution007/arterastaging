<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use App\Models\DynamicDiscountHistory;
use App\Models\QuotaAlertHistory;
use App\Models\WinbackHistory;

class RetentionAnalyticsController extends Controller
{
    public function discountHistory()
    {
        $discounts = DynamicDiscountHistory::with('user')->latest()->paginate(20);
        return view('admin.retention.discounts', compact('discounts'));
    }

    public function quotaHistory()
    {
        $quotas = QuotaAlertHistory::with('user')->latest()->paginate(20);
        return view('admin.retention.quotas', compact('quotas'));
    }

    public function winbackHistory()
    {
        $winbacks = WinbackHistory::with('user')->latest()->paginate(20);
        return view('admin.retention.winbacks', compact('winbacks'));
    }

    public function settings()
    {
        $quota_alert_threshold = Setting::getGlobalValue('retention', 'quota_alert_threshold', 90);
        $dynamic_discount_threshold = Setting::getGlobalValue('retention', 'dynamic_discount_threshold', 40);
        $winback_days_expired = Setting::getGlobalValue('retention', 'winback_days_expired', 30);

        return view('admin.retention.settings', compact('quota_alert_threshold', 'dynamic_discount_threshold', 'winback_days_expired'));
    }

    public function saveSettings(Request $request)
    {
        Setting::setValue('retention', 'quota_alert_threshold', $request->quota_alert_threshold);
        Setting::setValue('retention', 'dynamic_discount_threshold', $request->dynamic_discount_threshold);
        Setting::setValue('retention', 'winback_days_expired', $request->winback_days_expired);

        return back()->with('success', 'AI Retention Settings updated successfully!');
    }

    public function reactivateUser(Request $request)
    {
        // Check signature and valid user
        if (! $request->hasValidSignature()) {
            abort(401, 'This reactivation link is expired or invalid.');
        }
        
        $user = \App\Models\User::find($request->user);
        if($user) {
            // Log in user and redirect to subscription page
            \Illuminate\Support\Facades\Auth::login($user);
            return redirect('/subscription')->with('success', 'Welcome back! Please choose a plan to reactivate.');
        }
        
        return redirect('/')->with('error', 'User not found.');
    }

    public function paymentAnalytics()
    {
        // Dummy data for analytics to render the dashboard
        $mrr = 12500.00;
        $arr = $mrr * 12;
        $activeSubscribers = 145;
        $ltv = 450.00;
        $churnRate = 0.05;

        return view('admin.analytics.revenue', compact('mrr', 'arr', 'activeSubscribers', 'ltv', 'churnRate'));
    }
}
