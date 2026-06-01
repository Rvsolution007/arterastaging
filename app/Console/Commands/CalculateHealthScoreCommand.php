<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Carbon\Carbon;

class CalculateHealthScoreCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'artera:calculate-health';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate health score and churn risk for all users';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info("Starting health score calculation...");

        User::chunk(100, function ($users) {
            foreach ($users as $user) {
                $score = 100;

                // 1. Activity Penalty
                if (!$user->last_active_at) {
                    $score -= 40;
                } else {
                    $daysInactive = $user->last_active_at->diffInDays(now());
                    if ($daysInactive > 14) {
                        $score -= 40;
                    } elseif ($daysInactive > 7) {
                        $score -= 20;
                    } elseif ($daysInactive > 3) {
                        $score -= 10;
                    }
                }

                // 2. Subscription Penalty
                if (!$user->is_subscribe || ($user->subscription_end_date && Carbon::parse($user->subscription_end_date)->isPast())) {
                    $score -= 20;
                }

                // 3. Feature Usage Penalty
                $usageSum = $user->custom_post_used + 
                            $user->daily_drip_used + 
                            $user->festival_post_used + 
                            $user->business_category_post_used;

                if ($usageSum == 0) {
                    $score -= 20;
                } elseif ($usageSum < 5) {
                    $score -= 10;
                }

                // Ensure bounds
                $score = max(0, min(100, $score));

                // Determine Risk
                if ($score < 40) {
                    $risk = 'high';
                } elseif ($score < 70) {
                    $risk = 'medium';
                } else {
                    $risk = 'low';
                }

                // Save
                // Avoid using full save() to prevent updating updated_at if nothing changed
                if ($user->health_score != $score || $user->churn_risk != $risk) {
                    \DB::table('users')->where('id', $user->id)->update([
                        'health_score' => $score,
                        'churn_risk' => $risk,
                    ]);
                }
            }
        });

        $this->info("Health score calculation completed.");
        return 0;
    }
}
