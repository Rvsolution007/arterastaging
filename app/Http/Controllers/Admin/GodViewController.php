<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SystemAlert;
use App\Models\CompetitorWebsite;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Models\Subscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class GodViewController extends Controller
{
    public function index()
    {
        // 1. System Health & Alerts
        $activeAlerts = SystemAlert::where('is_resolved', false)->orderBy('created_at', 'desc')->get();
        
        $criticalAlertsCount = $activeAlerts->where('severity', 'critical')->count();
        $warningAlertsCount = $activeAlerts->where('severity', 'warning')->count();

        // 2. Revenue Metrics
        $totalRevenue = Transaction::where('status', 1)->sum('total_paid');
        $monthlyRevenue = Transaction::where('status', 1)->whereMonth('created_at', now()->month)->sum('total_paid');

        // 3. User Sentiment Analysis
        $tickets = Ticket::whereMonth('created_at', now()->month)->get();
        $sentimentStats = [
            'positive' => $tickets->where('sentiment', 'positive')->count(),
            'neutral' => $tickets->where('sentiment', 'neutral')->count(),
            'negative' => $tickets->where('sentiment', 'negative')->count(),
            'total' => $tickets->count() > 0 ? $tickets->count() : 1 // avoid div by 0
        ];

        // 4. Competitor Intel
        $competitors = CompetitorWebsite::all();

        // 5. Active Users & Churn
        $totalUsers = User::where('user_type', '!=', 'A')->count();
        $newUsersThisMonth = User::where('user_type', '!=', 'A')->whereMonth('created_at', now()->month)->count();

        return view('admin.god_view.index', compact(
            'activeAlerts',
            'criticalAlertsCount',
            'warningAlertsCount',
            'totalRevenue',
            'monthlyRevenue',
            'sentimentStats',
            'competitors',
            'totalUsers',
            'newUsersThisMonth'
        ));
    }

    public function resolveAlert($id)
    {
        $alert = SystemAlert::findOrFail($id);
        $alert->is_resolved = true;
        $alert->save();

        return back()->with('success', 'Alert marked as resolved.');
    }
}
