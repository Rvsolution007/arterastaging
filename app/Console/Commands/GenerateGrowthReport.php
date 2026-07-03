<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class GenerateGrowthReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'growth:generate-report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Midnight Cron: Analyzes daily data and uses AI to generate the next day\'s execution plan';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting Growth OS Data Aggregation...');
        
        $yesterday = Carbon::yesterday();

        // 1. Gather Data Points
        $newInstalls = DB::table('users')->whereDate('created_at', $yesterday)->count();
        $dau = DB::table('user_activities')->whereDate('created_at', $yesterday)->distinct('user_id')->count('user_id');
        $topTemplates = DB::table('general_posts')->orderBy('downloads_count', 'desc')->limit(5)->pluck('title');
        
        $dataSummary = [
            "date" => $yesterday->toDateString(),
            "new_installs" => $newInstalls,
            "daily_active_users" => $dau,
            "top_performing_templates" => $topTemplates
        ];

        $this->info('Calling Vertex AI Gemeni 2.5 Pro...');
        
        // 2. Call AI (Vertex AI via Laravel HTTP client - using dummy structure for now)
        $aiPrompt = "You are the Chief Growth Officer of ArtEra. Analyze this data and provide a json response with top_opportunities, top_problems, execution_plan (array of tasks with priority), and scores out of 100 for overall_growth, content, retention, engagement, revenue. Data: " . json_encode($dataSummary);

        // Dummy AI Response for preview purposes. Real integration will use Vertex API.
        $aiResponse = json_encode([
            'top_opportunities' => ['Promote Top 5 Templates on Social Media', 'Launch a weekend offer'],
            'top_problems' => ['DAU is slightly lower than MAU average'],
            'execution_plan' => [
                ['priority' => 'High', 'task' => 'Send Push Notification about new templates']
            ],
            'scores' => [
                'overall_growth' => 85,
                'content' => 88,
                'retention' => 76,
                'engagement' => 82,
                'revenue' => 70
            ]
        ]);

        $parsedResponse = json_decode($aiResponse, true);

        // 3. Save to AI Growth Reports Table
        DB::table('ai_growth_reports')->insert([
            'report_date' => $yesterday,
            'top_opportunities' => json_encode($parsedResponse['top_opportunities']),
            'top_problems' => json_encode($parsedResponse['top_problems']),
            'execution_plan' => json_encode($parsedResponse['execution_plan']),
            'overall_growth_score' => $parsedResponse['scores']['overall_growth'],
            'content_score' => $parsedResponse['scores']['content'],
            'retention_score' => $parsedResponse['scores']['retention'],
            'engagement_score' => $parsedResponse['scores']['engagement'],
            'revenue_score' => $parsedResponse['scores']['revenue'],
            'raw_ai_response' => $aiResponse,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->info('Growth Report Generated Successfully.');
        return Command::SUCCESS;
    }
}
