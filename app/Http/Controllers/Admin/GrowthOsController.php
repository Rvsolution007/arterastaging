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
        [$start, $end] = $this->dateRange($request);

        $metrics = \App\Models\GrowthMetric::whereBetween('date', [$start->toDateString(), $end->toDateString()]);
        $metric = (clone $metrics)
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
                'overall_growth' => (int) round((clone $metrics)->avg('overall_score') ?: 0),
                'content' => (int) round((clone $metrics)->avg('daily_downloads') ?: 0),
                'retention' => (int) round((clone $metrics)->avg('retention_day_7') ?: 0),
                'engagement' => (int) round((clone $metrics)->avg('daily_active_users') ?: 0),
                'revenue' => 0,
            ],
            'top_opportunities' => $metric?->top_opportunities ?: [],
            'top_problems' => $metric?->top_problems ?: [],
            'execution_plan' => empty($execution_plan) ? [['priority' => 'Low', 'task' => 'No urgent tasks today']] : $execution_plan
        ]);

    }

    /**
     * Get Acquisition & Health Stats (Tab 2)
     */
    public function getAcquisitionStats(Request $request)
    {
        [$start, $end] = $this->dateRange($request);

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
        [$start, $end] = $this->dateRange($request);

        $dailyActiveUsers = DB::table('user_activities')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('DATE(created_at) as activity_date, COUNT(DISTINCT user_id) as users')
            ->groupBy('activity_date')
            ->pluck('users');

        $averageSessionSeconds = (int) round(DB::table('user_sessions')
            ->whereBetween('start_time', [$start, $end])
            ->avg('duration_seconds') ?: 0);

        $mostActiveHour = DB::table('user_activities')
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('HOUR(created_at) as hour_of_day, COUNT(*) as activity_count')
            ->groupBy('hour_of_day')
            ->orderByDesc('activity_count')
            ->value('hour_of_day');

        $retention = DB::table('growth_metrics')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('AVG(retention_day_1) as day_1, AVG(retention_day_7) as day_7')
            ->first();

        return response()->json([
            'status' => 'success',
            'engagement' => [
                'dau' => (int) round($dailyActiveUsers->avg() ?: 0),
                'mau' => DB::table('user_activities')->whereBetween('created_at', [$start, $end])->distinct('user_id')->count('user_id'),
                'avg_session_time' => $this->formatDuration($averageSessionSeconds),
                'most_active_time' => $mostActiveHour === null ? 'No activity in this period' : Carbon::createFromTime($mostActiveHour)->format('g A')
            ],
            'retention' => [
                'day_1' => (int) round($retention->day_1 ?? 0) . '%',
                'day_3' => '—',
                'day_7' => (int) round($retention->day_7 ?? 0) . '%',
                'day_14' => '—',
                'day_30' => '—'
            ]
        ]);
    }

    /**
     * Get Content & Template Stats (Tab 4)
     */
    public function getContentStats(Request $request)
    {
        [$start, $end] = $this->dateRange($request);

        $query = DB::table('general_posts')->whereBetween('created_at', [$start, $end]);

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
        [$start, $end] = $this->dateRange($request);

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
                'opportunity_score' => 70 + ($fest->id % 30),
                'suggested_templates' => 5 + ($fest->id % 16),
                'status' => 'pending'
            ];
        }

        // 2. High-Growth Categories
        $categories = \App\Models\ProductCategory::where('status', 1)
            ->whereBetween('updated_at', [$start, $end])
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();
        $categoryPlans = [];
        foreach($categories as $cat) {
            $categoryPlans[] = [
                'plan_date' => 'Ongoing',
                'target_name' => $cat->name,
                'opportunity_score' => 0,
                'suggested_templates' => 0,
                'status' => 'pending'
            ];
        }

        // 3. Custom / Business Posts
        $customPlans = \App\Models\AiPushNotification::whereBetween('scheduled_for', [$start, $end])
            ->orderBy('scheduled_for')
            ->get()
            ->map(fn ($notification) => [
                'plan_date' => $notification->scheduled_for ? Carbon::parse($notification->scheduled_for)->toDateString() : null,
                'target_name' => $notification->title,
                'opportunity_score' => $notification->predicted_ctr,
                'suggested_templates' => 0,
                'status' => $notification->status,
            ])->values();

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
        [$start, $end] = $this->dateRange($request);

        $notifications = \App\Models\AiPushNotification::whereBetween('scheduled_for', [$start, $end])
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
        [$start, $end] = $this->dateRange($request);

        $reviews = \App\Models\AiReviewReply::whereBetween('created_at', [$start, $end])
            ->orderBy('created_at', 'desc')->get();
            
        $keywords = \App\Models\AsoKeyword::whereBetween('updated_at', [$start, $end])
            ->orderBy('current_rank', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'reviews' => $reviews,
            'keywords' => $keywords
        ]);
    }

    private function dateRange(Request $request): array
    {
        $request->validate([
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $start = $request->filled('start_date')
            ? Carbon::createFromFormat('Y-m-d', $request->input('start_date'))->startOfDay()
            : Carbon::now()->subDays(30)->startOfDay();
        $end = $request->filled('end_date')
            ? Carbon::createFromFormat('Y-m-d', $request->input('end_date'))->endOfDay()
            : Carbon::now()->endOfDay();

        if ($end->lt($start)) {
            abort(422, 'End date must be on or after the start date.');
        }

        return [$start, $end];
    }

    private function formatDuration(int $seconds): string
    {
        if ($seconds <= 0) {
            return '0m';
        }

        return intdiv($seconds, 60) . 'm ' . ($seconds % 60) . 's';
    }
}
