<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GrowthOsController extends Controller
{
    /**
     * Display the main Growth OS Dashboard
     */
    public function index()
    {
        return view('backend.growth_os.index');
    }

    /**
     * Get CEO Dashboard Stats (Tab 1)
     */
    public function getDashboardStats(Request $request)
    {
        $today = Carbon::today();
        $metric = \App\Models\GrowthMetric::whereDate('date', $today)->first();
        $tasks = \App\Models\GrowthTask::where('status', 'pending')->orderBy('id', 'desc')->take(10)->get();

        $execution_plan = [];
        foreach($tasks as $t) {
            $execution_plan[] = ['priority' => $t->priority, 'task' => $t->task_description];
        }

        return response()->json([
            'status' => 'success',
            'scores' => [
                'overall_growth' => $metric ? $metric->overall_score : 0,
                'content' => rand(60, 90), // Still dummy until phase 2
                'retention' => rand(50, 80),
                'engagement' => rand(75, 95),
                'revenue' => rand(65, 85),
            ],
            'top_opportunities' => [
                'Increase templates in "Jewelry" category by 20%', // Hardcoded for now
                'Send push notification at 7:00 PM for maximum CTR'
            ],
            'top_problems' => [
                '15% drop in downloads for "Real Estate" category', // Hardcoded for now
                'User retention dropped on Day 3 by 4%'
            ],
            'execution_plan' => empty($execution_plan) ? [['priority' => 'Low', 'task' => 'No urgent tasks today']] : $execution_plan
        ]);

    }

    /**
     * Get Acquisition & Health Stats (Tab 2)
     */
    public function getAcquisitionStats(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'installs' => [
                'today' => DB::table('users')->whereDate('created_at', Carbon::today())->count(),
                'yesterday' => DB::table('users')->whereDate('created_at', Carbon::yesterday())->count(),
                'last_7_days' => DB::table('users')->whereDate('created_at', '>=', Carbon::now()->subDays(7))->count(),
                'last_30_days' => DB::table('users')->whereDate('created_at', '>=', Carbon::now()->subDays(30))->count(),
            ],
            'sources' => [
                'organic' => 450,
                'referral' => 120,
                'paid' => 300,
                'play_store' => 800
            ],
            'reviews' => [
                'positive' => 120,
                'negative' => 15,
                'bug_reports' => 5,
                'feature_requests' => 20
            ]
        ]);
    }

    /**
     * Get Engagement & Retention Stats (Tab 3)
     */
    public function getEngagementStats(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'engagement' => [
                'dau' => DB::table('user_activities')->whereDate('created_at', Carbon::today())->distinct('user_id')->count('user_id'),
                'mau' => DB::table('user_activities')->whereDate('created_at', '>=', Carbon::now()->subDays(30))->distinct('user_id')->count('user_id'),
                'avg_session_time' => '4m 32s', // Dummy for now, will calculate from user_sessions
                'most_active_time' => 'Evening (6 PM - 9 PM)'
            ],
            'retention' => [
                'day_1' => '45%',
                'day_3' => '30%',
                'day_7' => '18%',
                'day_14' => '12%',
                'day_30' => '8%'
            ]
        ]);
    }

    /**
     * Get Content & Template Stats (Tab 4)
     */
    public function getContentStats(Request $request)
    {
        // Get top 10 downloaded templates
        $topTemplates = DB::table('general_posts')
            ->orderBy('downloads_count', 'desc')
            ->limit(10)
            ->get(['id', 'title', 'downloads_count', 'views_count', 'image']);

        return response()->json([
            'status' => 'success',
            'top_templates' => $topTemplates,
            'categories' => [
                ['name' => 'Jewelry', 'growth' => '+25%'],
                ['name' => 'Real Estate', 'growth' => '-5%'],
                ['name' => 'Food & Beverage', 'growth' => '+12%'],
            ]
        ]);
    }

    /**
     * Get Content Planner Stats (Tab 5 - Phase 2)
     */
    public function getPlannerStats(Request $request)
    {
        $plans = \App\Models\ContentPlan::orderBy('plan_date', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'plans' => $plans
        ]);
    }

    /**
     * Get Marketing AI Stats (Tab 6 - Phase 3)
     */
    public function getMarketingStats(Request $request)
    {
        $notifications = \App\Models\AiPushNotification::orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'notifications' => $notifications
        ]);
    }

    /**
     * Get ASO & Reviews Stats (Tab 7 - Phase 4)
     */
    public function getAsoStats(Request $request)
    {
        $reviews = \App\Models\AiReviewReply::orderBy('created_at', 'desc')->get();
        $keywords = \App\Models\AsoKeyword::orderBy('current_rank', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'reviews' => $reviews,
            'keywords' => $keywords
        ]);
    }
}
