<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\UserActivity;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SendSmartPushNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'push:smart-optimize';

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

        // Find users whose most active hour is the current hour
        $usersToNotify = User::where('status', 1)->get()->filter(function ($user) use ($currentHour) {
            return $this->getBestNotificationHour($user->id) === $currentHour;
        });

        $this->info("Found {$usersToNotify->count()} users to notify at hour {$currentHour}.");

        foreach ($usersToNotify as $user) {
            // Check if they haven't been notified today
            $alreadyNotifiedToday = UserNotification::where('user_id', $user->id)
                ->whereDate('created_at', now()->toDateString())
                ->where('title', 'Daily Inspiration')
                ->exists();

            if (!$alreadyNotifiedToday) {
                // Send a smart push notification (we simulate this by creating a DB notification for now)
                UserNotification::create([
                    'user_id' => $user->id,
                    'title' => 'Daily Inspiration',
                    'message' => "Hey {$user->name}, it's your favorite time to create! Check out new templates today.",
                    'icon' => 'fa-bell',
                    'action_url' => '/dashboard',
                    'status' => 'unread'
                ]);

                $this->info("Queued push notification for User ID: {$user->id}");
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
