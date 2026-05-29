<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\UserActivity;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Services\FcmService;

class SendSmartPushNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'push:smart-optimize {--force : Force send to all active users bypassing the optimal time check}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze user activity to determine the best time to send push notifications and queue them';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting Smart Push Notification Optimization...');

        // Get current hour
        $currentHour = now()->hour;

        // Find users whose most active hour is the current hour (unless --force is passed)
        $usersToNotify = User::where('status', 1)->get();
        
        if (!$this->option('force')) {
            $usersToNotify = $usersToNotify->filter(function ($user) use ($currentHour) {
                return $this->getBestNotificationHour($user->id) === $currentHour;
            });
        } else {
            $this->info('Force mode enabled: Bypassing time optimization check.');
        }

        $this->info("Found {$usersToNotify->count()} users to notify at hour {$currentHour}.");

        foreach ($usersToNotify as $user) {
            // Check if they haven't been notified today
            $cacheKey = "smart_push_notified_today_{$user->id}";
            $alreadyNotifiedToday = \Illuminate\Support\Facades\Cache::has($cacheKey);

            if (!$alreadyNotifiedToday) {
                $title = 'Daily Inspiration';
                $message = "Hey {$user->name}, it's your favorite time to create! Check out new templates today.";
                
                // Mark as notified for today (expires at midnight)
                $secondsUntilMidnight = now()->diffInSeconds(now()->endOfDay());
                \Illuminate\Support\Facades\Cache::put($cacheKey, true, $secondsUntilMidnight);

                // Also send via FCM
                $fcmService = new \App\Services\FcmService();
                if ($fcmService->isConfigured()) {
                    $result = $fcmService->sendNotificationToUser($user->id, $title, $message);
                    if ($result['status'] === 'success') {
                        $this->info("Sent FCM push notification for User ID: {$user->id}");
                    } else {
                        $this->error("Failed to send FCM push for User ID: {$user->id}. Error: " . ($result['message'] ?? 'Unknown error'));
                    }
                } else {
                    $this->info("Queued DB notification for User ID: {$user->id} (FCM not configured)");
                }
            }
        }

        $this->info('Smart Push Notification run completed.');
        return Command::SUCCESS;
    }

    /**
     * Determine the user's most active hour based on their activity logs.
     */
    private function getBestNotificationHour($userId)
    {
        $mostActiveHour = UserActivity::where('user_id', $userId)
            ->select(DB::raw('HOUR(created_at) as hour'), DB::raw('count(*) as count'))
            ->groupBy('hour')
            ->orderBy('count', 'desc')
            ->first();

        // Default to 10 AM if no activity data
        return $mostActiveHour ? (int) $mostActiveHour->hour : 10;
    }
}
