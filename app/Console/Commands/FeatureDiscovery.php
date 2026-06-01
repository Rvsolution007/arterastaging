<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class FeatureDiscovery extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'artera:feature-discovery';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automated Feature Discovery - Spots unused features and suggests them via smart banners/push';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info("Starting Feature Discovery Analysis...");
        


        // Find users who have never used Custom Post
        $usersMissingCustomPost = User::where('custom_post_used', 0)
            ->whereNotNull('last_active_at')
            ->get();

        foreach ($usersMissingCustomPost as $user) {
            Log::info("FEATURE DISCOVERY: Queued 'Custom Post' banner for User {$user->id} ({$user->name})");
        }

        $this->info("Feature Discovery Completed.");
        return 0;
    }
}
