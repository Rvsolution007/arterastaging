<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\GeneralPost;

class ResetAnalyticsData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analytics:reset';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resets all analytical metric numbers to 0 (Keeps real data like Users and Posts)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting Analytics Data Reset...');

        // 1. AI Analytics
        DB::table('ai_token_logs')->truncate();
        $this->line('✔ AI Analytics (ai_token_logs) cleared.');

        // 2. Growth OS
        DB::table('app_installs')->truncate();
        DB::table('user_sessions')->truncate();
        DB::table('ai_growth_reports')->truncate();
        DB::table('play_store_reviews')->truncate();
        $this->line('✔ Growth OS logs cleared.');

        // 3. User Performance & Engagement
        DB::table('user_activities')->truncate();
        $this->line('✔ User Activities (DAU/MAU tracking) cleared.');

        // 4. Churn Analytics (Reset user scores)
        DB::table('users')->update([
            'health_score' => 100,
            'churn_risk' => 'low',
            'lead_score' => 0
        ]);
        $this->line('✔ Churn Analytics (User health & risk scores) reset to default.');

        // 5. Content Analytics (Reset template stats)
        DB::table('general_posts')->update([
            'views_count' => 0,
            'downloads_count' => 0,
            'shares_count' => 0,
            'favorites_count' => 0,
            'popularity_score' => 0,
            'growth_score' => 0
        ]);
        $this->line('✔ Templates Analytics (Views, Downloads, Shares) reset to 0.');

        $this->info('All analytics data successfully reset to 0! Master data (Users/Posts) is safe.');

        return Command::SUCCESS;
    }
}
