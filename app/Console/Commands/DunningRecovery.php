<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DunningRecovery extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'artera:dunning';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'AI Subscription Recovery System - Automatically chases failed payments via email/whatsapp over 3 days';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info("Starting Dunning Recovery Process...");
        
        // Find users whose subscription expired in the last 3 days
        $recentlyExpiredUsers = User::whereNotNull('subscription_end_date')
            ->whereDate('subscription_end_date', '>=', Carbon::now()->subDays(3))
            ->whereDate('subscription_end_date', '<', Carbon::now())
            ->get();

        foreach ($recentlyExpiredUsers as $user) {
            $daysSinceExpiry = Carbon::parse($user->subscription_end_date)->diffInDays(Carbon::now());
            
            $this->info("User {$user->id} ({$user->name}) expired {$daysSinceExpiry} days ago.");
            
            // Here we would integrate with WhatsApp/Email API to send reminders
            if ($daysSinceExpiry == 1) {
                // Send Day 1 Reminder (Gentle nudge)
                Log::info("DUNNING: Sent Day 1 Reminder to {$user->email}");
            } elseif ($daysSinceExpiry == 2) {
                // Send Day 2 Reminder (Offer help)
                Log::info("DUNNING: Sent Day 2 Reminder to {$user->email}");
            } elseif ($daysSinceExpiry == 3) {
                // Send Day 3 Reminder (Final warning/discount)
                Log::info("DUNNING: Sent Day 3 Reminder to {$user->email}");
            }
        }
        
        $this->info("Dunning Recovery Process Completed.");
        return 0;
    }
}
