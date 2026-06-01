<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\UserActivity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\YearInReviewMail;
use Carbon\Carbon;
use App\Models\Setting;

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

        $milestoneMonthsSetting = '1,6,12';
        $milestoneMonths = explode(',', $milestoneMonthsSetting);
        $milestoneMonths = array_map('trim', $milestoneMonths);

        // Find users created exactly X months ago today
        $users = User::where('status', 1)
            ->whereNotNull('created_at')
            ->get()
            ->filter(function ($user) use ($milestoneMonths) {
                $createdAt = Carbon::parse($user->created_at)->startOfDay();
                $today = now()->startOfDay();
                
                // Diff in exact months (day of month must match)
                if ($createdAt->day === $today->day) {
                    $diffInMonths = $createdAt->diffInMonths($today);
                    return in_array((string)$diffInMonths, $milestoneMonths);
                }
                return false;
            });

        $this->info("Found {$users->count()} users with an anniversary milestone today.");

        foreach ($users as $user) {
            // Compile stats since the beginning
            $totalPosts = UserActivity::where('user_id', $user->id)
                ->whereIn('action', ['download_template', 'create_custom_post', 'create_festival_post'])
                ->count();

            $badgesEarned = DB::table('user_achievements')
                ->where('user_id', $user->id)
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
