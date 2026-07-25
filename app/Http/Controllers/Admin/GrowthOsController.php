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
        $start = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : Carbon::now()->subDays(30)->startOfDay();
        $end = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::now()->endOfDay();

        $metric = \App\Models\GrowthMetric::whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->orderBy('date', 'desc')->first();
            
        $tasks = \App\Models\GrowthTask::where('status', 'pending')
            ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->orderBy('id', 'desc')->take(10)->get();

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
        $start = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : Carbon::now()->subDays(30)->startOfDay();
        $end = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::now()->endOfDay();

        // These are app-originated events. User registrations, Play Store data,
        // and the AI-review-replies table are intentionally not used here.
        $totalInstalls = DB::table('app_install_events')
            ->where('event_type', 'install')
            ->whereBetween('occurred_at', [$start, $end])
            ->count();

        // A device is a unique install only on its first-ever app install.
        // Reinstall events remain part of Total Installs but cannot inflate this KPI.
        $uniqueInstalls = DB::table('app_install_events')
            ->select('device_hash')
            ->where('event_type', 'install')
            ->groupBy('device_hash')
            ->havingRaw('MIN(occurred_at) BETWEEN ? AND ?', [$start, $end])
            ->get()
            ->count();

        // Each device gets one uninstall event, generated when FCM confirms that
        // its app token is unregistered. This is the reliable server-side signal
        // available after the app itself has been removed.
        $totalUninstalls = DB::table('app_install_events')
            ->where('event_type', 'uninstall')
            ->whereBetween('occurred_at', [$start, $end])
            ->count();

        // Positive Reviews comes only from the Google Play review cache filled
        // by the official Android Publisher API sync, never from AI reply drafts.
        $positiveReviews = DB::table('play_store_reviews')
            ->where('star_rating', '>=', 4)
            ->whereBetween('review_date', [$start, $end])
            ->count();

        return response()->json([
            'status' => 'success',
            'installs' => [
                'unique' => $uniqueInstalls,
                'total' => $totalInstalls,
                'total_uninstalls' => $totalUninstalls,
            ],
            'reviews' => [
                'positive' => $positiveReviews,
            ]
        ]);
    }

    /**
     * Get Engagement & Retention Stats (Tab 3)
     */
    public function getEngagementStats(Request $request)
    {
        $start = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : Carbon::now()->subDays(30)->startOfDay();
        $end = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::now()->endOfDay();

        return response()->json([
            'status' => 'success',
            'engagement' => [
                'dau' => DB::table('user_activities')->whereBetween('created_at', [$start, $end])->distinct('user_id')->count('user_id'),
                'mau' => DB::table('user_activities')->whereBetween('created_at', [$start, $end])->distinct('user_id')->count('user_id'),
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
        $start = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : Carbon::now()->subDays(30)->startOfDay();
        $end = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::now()->endOfDay();

        $query = DB::table('general_posts');
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('created_at', [$start, $end]);
        }

        // Get top 10 downloaded templates
        $topTemplates = $query->orderBy('downloads_count', 'desc')
            ->limit(10)
            ->get(['id', 'task_name as title', 'downloads_count', 'views_count', 'frame_image as image']);

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
        $start = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : Carbon::now()->subDays(30)->startOfDay();
        $end = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::now()->endOfDay();

        // 1. Upcoming Festivals
        $festivals = \App\Models\Festivals::whereBetween('festivals_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->orderBy('festivals_date', 'asc')
            ->limit(10)
            ->get();
            
        $festivalPlans = [];
        foreach($festivals as $fest) {
            $festivalPlans[] = [
                'plan_date' => \Carbon\Carbon::parse($fest->festivals_date)->format('Y-m-d'),
                'target_name' => $fest->title,
                'opportunity_score' => rand(85, 99),
                'suggested_templates' => rand(10, 20),
                'status' => 'pending'
            ];
        }

        // 2. High-Growth Categories
        $categories = \App\Models\ProductCategory::where('status', 1)->inRandomOrder()->limit(10)->get();
        $categoryPlans = [];
        foreach($categories as $cat) {
            $categoryPlans[] = [
                'plan_date' => 'Ongoing',
                'target_name' => $cat->name,
                'opportunity_score' => rand(70, 95),
                'suggested_templates' => rand(5, 15),
                'status' => 'pending'
            ];
        }

        // 3. Custom / Business Posts
        $customPlans = [
            [
                'plan_date' => now()->addDays(2)->format('Y-m-d'),
                'target_name' => 'Flash Sale / Weekend Offer',
                'opportunity_score' => rand(80, 95),
                'suggested_templates' => 5,
                'status' => 'pending'
            ],
            [
                'plan_date' => now()->addDays(5)->format('Y-m-d'),
                'target_name' => 'New Product Arrival',
                'opportunity_score' => rand(75, 90),
                'suggested_templates' => 8,
                'status' => 'pending'
            ],
            [
                'plan_date' => now()->addDays(10)->format('Y-m-d'),
                'target_name' => 'Customer Testimonial / Review',
                'opportunity_score' => rand(65, 85),
                'suggested_templates' => 4,
                'status' => 'pending'
            ]
        ];

        return response()->json([
            'status' => 'success',
            'festivals' => $festivalPlans,
            'categories' => $categoryPlans,
            'custom' => $customPlans
        ]);
    }

    /**
     * Get Marketing AI Stats (Tab 6 - Phase 3)
     */
    public function getMarketingStats(Request $request)
    {
        $start = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : Carbon::now()->subDays(30)->startOfDay();
        $end = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::now()->endOfDay();

        $notifications = \App\Models\AiPushNotification::whereBetween('created_at', [$start, $end])
            ->orderBy('created_at', 'desc')->get();

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
        $start = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : Carbon::now()->subDays(30)->startOfDay();
        $end = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : Carbon::now()->endOfDay();

        $reviews = \App\Models\AiReviewReply::whereBetween('created_at', [$start, $end])
            ->orderBy('created_at', 'desc')->get();
            
        $keywords = \App\Models\AsoKeyword::orderBy('current_rank', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'reviews' => $reviews,
            'keywords' => $keywords
        ]);
    }
}
