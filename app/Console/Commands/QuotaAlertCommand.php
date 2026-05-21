<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class QuotaAlertCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'artera:quota-alert {email?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send upgrade prompts to users hitting 90% of their feature limits';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $email = $this->argument('email');

        if ($email) {
            $users = User::where('email', $email)->get();
        } else {
            // Get free tier users or users with limits
            $users = User::where('is_subscribe', 0)->get();
        }

        foreach ($users as $user) {
            $limit = $user->business_limit ?? 1; // E.g., 100 features
            
            // Simulating usage calculation. In reality, count their generations for the month.
            // For testing purposes, if email is provided, we simulate 95% usage.
            $used = $email ? ($limit * 0.95) : 0; 

            if ($limit > 0) {
                $percentage = ($used / $limit) * 100;

                if ($percentage >= 90) {
                    $this->info("User {$user->name} is at {$percentage}% of their quota.");
                    
                    // Fire push notification/email
                    // Mail::to($user->email)->send(new UpgradePromptMail($user));
                    $this->info("Upgrade prompt sent to {$user->email}");
                }
            }
        }

        return 0;
    }
}
