<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\UserActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\YearInReviewMail;
use Carbon\Carbon;

class SendMilestoneEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'emails:milestones';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send automated milestone and anniversary emails to users';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Checking for users with account anniversaries today...');

        // Find users created exactly 1, 2, 3... years ago today
        $users = User::where('status', 1)
            ->whereNotNull('created_at')
            ->whereMonth('created_at', now()->month)
            ->whereDay('created_at', now()->day)
            ->whereYear('created_at', '<', now()->year)
            ->get();

        $this->info("Found {$users->count()} users with an anniversary today.");

        foreach ($users as $user) {
            // Compile stats for the year
            $totalPosts = UserActivity::where('user_id', $user->id)
                ->whereIn('action', ['download_template', 'create_custom_post', 'create_festival_post', 'magic_cloner_use'])
                ->where('created_at', '>=', now()->subYear())
                ->count();

            $badgesEarned = DB::table('user_achievements')
                ->where('user_id', $user->id)
                ->where('created_at', '>=', now()->subYear())
                ->count();

            $stats = [
                'total_posts' => $totalPosts,
                'max_streak' => $user->max_streak,
                'badges_earned' => $badgesEarned,
            ];

            try {
                Mail::to($user->email)->send(new YearInReviewMail($user, $stats));
                $this->info("Sent anniversary email to: {$user->email}");
            } catch (\Exception $e) {
                $this->error("Failed to send email to {$user->email}: " . $e->getMessage());
            }
        }

        $this->info('Milestone emails processing completed.');
        return Command::SUCCESS;
    }
}
