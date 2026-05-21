<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CompetitorWebsite;
use App\Models\SystemAlert;
use App\Models\UserNotification;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use App\Mail\SystemAlertMail;

class TrackCompetitors extends Command
{
    protected $signature = 'competitor:track';
    protected $description = 'Track competitor websites for feature changes and social media stats';

    public function handle()
    {
        $this->info('Starting Competitor Tracker...');

        $competitors = CompetitorWebsite::all();

        foreach ($competitors as $competitor) {
            $this->info("Checking {$competitor->name} ({$competitor->url})...");
            
            try {
                // Fetch Website Content
                $response = Http::timeout(10)->get($competitor->url);
                if ($response->successful()) {
                    // Strip HTML tags and remove extra spaces to get plain text content
                    $content = strip_tags(preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', "", $response->body()));
                    $content = preg_replace('/\s+/', ' ', $content);
                    $newHash = md5($content);

                    // Check for changes
                    if ($competitor->last_content_hash && $competitor->last_content_hash !== $newHash) {
                        $this->warn("Change detected on {$competitor->name}!");
                        
                        $msg = "A content change was detected on competitor website: {$competitor->name} ({$competitor->url}). They might have launched a new feature or updated their landing page.";
                        
                        // Log Alert
                        SystemAlert::create([
                            'type' => 'competitor',
                            'message' => $msg,
                            'severity' => 'warning',
                            'is_resolved' => false
                        ]);

                        $admins = User::where('user_type', 'A')->get();
                        foreach ($admins as $admin) {
                            UserNotification::create([
                                'user_id' => $admin->id,
                                'title' => 'Competitor Update: ' . $competitor->name,
                                'message' => $msg,
                                'icon' => 'fa-binoculars',
                                'action_url' => '/admin/god-view',
                                'status' => 'unread'
                            ]);

                            try {
                                Mail::to($admin->email)->send(new SystemAlertMail('competitor', $msg, 'warning'));
                            } catch (\Exception $e) {}
                        }
                    }

                    $competitor->last_content_hash = $newHash;
                }

                // Simulate Social Media Tracking (e.g. fetching follower counts)
                if ($competitor->social_url) {
                    // In a real scenario, this would use Instagram/Twitter API
                    // Here we simulate a random follower growth to demonstrate the feature
                    $currentStats = json_decode($competitor->last_social_stats, true) ?? ['followers' => rand(1000, 50000)];
                    $growth = rand(0, 50); // Simulate random daily growth
                    $currentStats['followers'] += $growth;
                    
                    $competitor->last_social_stats = json_encode($currentStats);
                }

                $competitor->last_checked_at = now();
                $competitor->save();

            } catch (\Exception $e) {
                $this->error("Failed to check {$competitor->name}: " . $e->getMessage());
            }
        }

        $this->info('Competitor Tracker completed.');
        return Command::SUCCESS;
    }
}
