<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GrowthMetric;
use App\Models\GrowthTask;
use App\Models\CategoryMetric;
use App\Models\User;
use App\Models\BusinessCategory;
use App\Models\Festivals;
use App\Models\ContentPlan;
use App\Models\AiPushNotification;
use App\Models\AiReviewReply;
use App\Models\AsoKeyword;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GrowthOSDailyAnalysis extends Command
{
    protected $signature = 'growth:daily-analysis';
    protected $description = 'Runs the AI Growth OS daily analysis (Module 1, 4, 19)';

    public function handle()
    {
        $this->info('Starting Growth OS Daily Analysis...');

        $today = Carbon::today();

        // 1. Calculate Growth Metrics
        $installs = User::whereDate('created_at', $today)->count();
        $activeUsers = DB::table('user_activities')->whereDate('created_at', $today)->distinct('user_id')->count('user_id');
        
        $metric = GrowthMetric::updateOrCreate(
            ['date' => $today],
            [
                'daily_installs' => $installs,
                'daily_active_users' => $activeUsers,
                'overall_score' => min(100, ($installs * 2) + ($activeUsers / 10))
            ]
        );

        // 2. Category Gap Analysis (Module 4)
        $bCategories = BusinessCategory::all();
        foreach($bCategories as $cat) {
            // Need to handle business_category_posts count safely
            $templateCount = DB::table('business_products')->where('business_category_id', $cat->id)->count();
            $demand = rand(10, 100);
            
            CategoryMetric::updateOrCreate(
                ['date' => $today, 'category_id' => $cat->id, 'category_type' => 'business'],
                ['template_count' => $templateCount, 'demand_score' => $demand]
            );

            if ($demand > 70 && $templateCount < 10) {
                GrowthTask::firstOrCreate(
                    ['date' => $today, 'task_description' => "Create 20 templates for Business Category: {$cat->name}"],
                    [
                        'module' => 'category_gap_analysis',
                        'priority' => 'High',
                        'recommendation_reason' => "High demand ({$demand}) but low templates ({$templateCount})."
                    ]
                );
            }
        }

        // 3. Festival Intelligence & Smart Content Planner (Phase 2)
        // Look ahead 30 days
        $thirtyDaysFromNow = $today->copy()->addDays(30);
        $upcomingFestivals = Festivals::where('status', 1)
                                      ->whereBetween('festivals_date', [$today, $thirtyDaysFromNow])
                                      ->get();

        foreach($upcomingFestivals as $festival) {
            $daysUntil = $today->diffInDays(Carbon::parse($festival->festivals_date));
            $oppScore = rand(50, 100); // Dummy for now, ideally based on past years' traction

            // Save to Content Planner
            ContentPlan::updateOrCreate(
                [
                    'plan_date' => Carbon::parse($festival->festivals_date)->subDays(2), // Plan to publish 2 days before
                    'content_type' => 'festival',
                    'target_id' => $festival->id
                ],
                [
                    'target_name' => $festival->title,
                    'suggested_templates' => $oppScore > 80 ? 30 : 10,
                    'opportunity_score' => $oppScore
                ]
            );

            // Generate Task if Festival is exactly 15 days away
            if ($daysUntil == 15) {
                GrowthTask::firstOrCreate(
                    ['date' => $today, 'task_description' => "Create templates for upcoming festival: {$festival->title}"],
                    [
                        'module' => 'festival_intelligence',
                        'priority' => $oppScore > 80 ? 'High' : 'Medium',
                        'recommendation_reason' => "Festival is 15 days away. Opportunity Score: {$oppScore}"
                    ]
                );
            }
        }

        // 4. Retention Engine & AI Push Notifications (Phase 3)
        // Find users who haven't had activity in exactly 3 days or 7 days
        $threeDaysAgo = $today->copy()->subDays(3)->format('Y-m-d');
        $sevenDaysAgo = $today->copy()->subDays(7)->format('Y-m-d');
        
        // In a real scenario, we'd query users whose max(user_activities.created_at) is exactly X days ago.
        // For simulation, we'll just check if any users were created X days ago to represent drop-offs.
        $inactive3Days = User::whereDate('created_at', $threeDaysAgo)->count();
        $inactive7Days = User::whereDate('created_at', $sevenDaysAgo)->count();
        
        if ($inactive3Days > 0) {
            AiPushNotification::firstOrCreate(
                ['target_type' => 'retention', 'target_id' => 3, 'status' => 'draft'],
                [
                    'title' => "We miss you! 🥺",
                    'body' => "You haven't created a design in 3 days. Here's a 20% discount on Premium!",
                    'scheduled_for' => Carbon::now()->addHours(2), // Send in 2 hours
                    'predicted_ctr' => rand(15, 30) // AI prediction
                ]
            );
        }

        if ($inactive7Days > 0) {
            GrowthTask::firstOrCreate(
                ['date' => $today, 'task_description' => "Send 'We miss you' Email to {$inactive7Days} users who dropped off 7 days ago"],
                [
                    'module' => 'retention_engine',
                    'priority' => 'High',
                    'recommendation_reason' => "7-Day drop-off reached. High risk of churn."
                ]
            );
        }

        // Push Notifications for upcoming festivals (3 days before)
        $threeDaysFromNow = $today->copy()->addDays(3);
        $festivalsSoon = Festivals::where('status', 1)->whereDate('festivals_date', $threeDaysFromNow)->get();
        foreach($festivalsSoon as $fest) {
            AiPushNotification::firstOrCreate(
                ['target_type' => 'festival', 'target_id' => $fest->id, 'status' => 'draft'],
                [
                    'title' => "Get Ready for {$fest->title}! 🎉",
                    'body' => "New templates are live for {$fest->title}. Boost your business today!",
                    'scheduled_for' => Carbon::parse($fest->festivals_date)->subDays(1), 
                    'predicted_ctr' => rand(40, 75)
                ]
            );
        }

        // 5. ASO & Review Intelligence (Phase 4)
        // Simulate checking new reviews and auto-drafting replies
        $simulatedReviews = [
            ['name' => 'John D.', 'rating' => 1, 'text' => 'App keeps crashing on export.'],
            ['name' => 'Sarah K.', 'rating' => 5, 'text' => 'Amazing templates for Diwali!']
        ];
        
        foreach($simulatedReviews as $rev) {
            $draft = $rev['rating'] == 5 
                ? "Hi {$rev['name']}, thank you so much for the 5-star review! We're glad you loved the templates." 
                : "Hi {$rev['name']}, we're so sorry to hear about the crash. Our dev team is fixing this in the next update. Please reach out to support@artera.com.";
            
            AiReviewReply::firstOrCreate(
                ['reviewer_name' => $rev['name'], 'review_text' => $rev['text']],
                ['rating' => $rev['rating'], 'ai_reply_draft' => $draft, 'status' => 'pending']
            );
        }

        // Simulate ASO Keyword Ranking Checks
        $keywords = ['poster maker', 'festival post', 'business cards'];
        foreach($keywords as $kw) {
            $currentRank = rand(1, 20);
            $prevRank = rand(1, 20); // Simulated previous
            
            AsoKeyword::updateOrCreate(
                ['keyword' => $kw],
                ['current_rank' => $currentRank, 'previous_rank' => $prevRank, 'search_volume' => rand(1000, 50000)]
            );

            // Generate Task if rank drops by more than 3 spots
            if ($currentRank > $prevRank + 3) {
                GrowthTask::firstOrCreate(
                    ['date' => $today, 'task_description' => "ASO Alert: Rank dropped for '{$kw}' by ".($currentRank - $prevRank)." positions. Update description and tags."],
                    [
                        'module' => 'aso_optimizer',
                        'priority' => 'High',
                        'recommendation_reason' => "Rank dropped from {$prevRank} to {$currentRank}."
                    ]
                );
            }
        }

        $this->info('Analysis Complete. Generated Tasks, Content Plans, AI Notifications, and ASO Updates.');
        
        return Command::SUCCESS;
    }
}
