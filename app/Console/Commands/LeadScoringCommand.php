<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class LeadScoringCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'artera:lead-score';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate and update lead scores for free users';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info("Starting Lead Scoring calculations...");
        
        // Target active free users
        $users = User::where('is_subscribe', 0)->get();

        foreach ($users as $user) {
            $score = 0;
            
            // Factor 1: Onboarding completed
            if (!empty($user->completed_onboarding_steps)) {
                $steps = json_decode($user->completed_onboarding_steps, true) ?? [];
                $score += count($steps) * 10;
            }

            // Factor 2: Usage
            $usage = $user->total_usage_count ?? 0;
            $score += ($usage * 5);
            
            // Cap at 100
            $score = min($score, 100);
            
            $user->lead_score = $score;
            $user->save();

            if ($score >= 80) {
                $this->info("HOT LEAD IDENTIFIED: {$user->name} (Score: $score)");
            }
        }
        
        $this->info("Lead scoring completed.");
        return 0;
    }
}
