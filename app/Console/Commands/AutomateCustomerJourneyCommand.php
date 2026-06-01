<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\VertexAIService;
use Illuminate\Support\Facades\Mail;
use App\Mail\DynamicJourneyMail;
use Carbon\Carbon;

class AutomateCustomerJourneyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'artera:journey-automation {--test= : Test email to run the automation for a specific user}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Evaluate user journeys and send AI-personalized emails based on their stage.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $testEmail = $this->option('test');

        if ($testEmail) {
            $users = User::where('email', $testEmail)->get();
            $this->info("Running in TEST mode for: {$testEmail}");
        } else {
            // For production, maybe we limit to 50 a day so we don't spam or hit API limits
            $users = User::whereNotNull('email')->where('status', 1)->limit(50)->get();
            $this->info("Running in PRODUCTION mode for up to 50 users.");
        }

        if ($users->isEmpty()) {
            $this->warn("No users found to process.");
            return 0;
        }

        // We only want to instantiate the AI service once
        // For testing, we use admin ID 1 or the first user's ID
        $aiService = new VertexAIService(1);

        foreach ($users as $user) {
            $this->processUserJourney($user, $aiService);
        }

        $this->info("Journey automation completed.");
        return 0;
    }

    private function processUserJourney(User $user, VertexAIService $aiService)
    {
        // 1. Calculate New Stage
        $daysSinceJoined = $user->created_at ? $user->created_at->diffInDays(now()) : 0;
        $usageSum = $user->custom_post_used + $user->daily_drip_used + $user->festival_post_used;
        $isSubscribed = $user->is_subscribe && $user->subscription_end_date && Carbon::parse($user->subscription_end_date)->isFuture();
        
        $newStage = 'onboarding';

        if ($daysSinceJoined > 30 && !$isSubscribed && $user->last_active_at && Carbon::parse($user->last_active_at)->diffInDays(now()) > 30) {
            $newStage = 'winback';
        } elseif ($usageSum > 10 && $isSubscribed) {
            $newStage = 'retention'; // Loyal paid user
        } elseif ($usageSum > 5) {
            $newStage = 'engagement'; // Active free or low-tier user
        } elseif ($usageSum > 0) {
            $newStage = 'activation'; // Has used the app at least once
        } else {
            $newStage = 'onboarding'; // 0 usage
        }

        // Update stage in DB if changed
        if ($user->journey_stage !== $newStage) {
            \DB::table('users')->where('id', $user->id)->update(['journey_stage' => $newStage]);
            $this->info("Updated {$user->name} to stage: {$newStage}");
        }

        // 2. Determine if AI Action is Needed
        // To prevent spamming, we will ONLY send an email if explicitly testing, 
        // OR in real scenario, we'd check a `last_journey_email_sent_at` column.
        // For this task demo, if --test is provided, we ALWAYS generate and send.
        if ($this->option('test')) {
            $this->generateAndSendAiEmail($user, $newStage, $usageSum, $daysSinceJoined, $aiService);
        }
    }

    private function generateAndSendAiEmail(User $user, $stage, $usageSum, $daysSinceJoined, VertexAIService $aiService)
    {
        $this->info("Generating AI email for {$user->name} at stage '{$stage}'...");

        $context = [
            'user_name' => $user->name,
            'days_since_joined' => $daysSinceJoined,
            'total_features_used' => $usageSum,
            'journey_stage' => $stage,
            'subscription_status' => $user->is_subscribe ? 'Active' : 'Free/Expired'
        ];

        $systemInstruction = "You are a friendly SaaS Customer Success Manager. Based on the user's data, write a short, highly personalized email (2-3 paragraphs) to move them to the next stage of their journey. Do not include placeholders like [Your Name]. Sign off as 'The Artera Team'. Output ONLY in pure JSON format exactly like this: {\"subject\": \"Subject here\", \"body\": \"Message body here\"}";

        $prompt = "User Data: " . json_encode($context);
        
        if ($stage === 'onboarding') {
            $prompt .= "\nGoal: Encourage them to create their very first post using the Custom Post or Festival Post feature.";
        } elseif ($stage === 'winback') {
            $prompt .= "\nGoal: Tell them we miss them and offer them to come back and see the new AI features.";
        } else {
            $prompt .= "\nGoal: Congratulate them on their usage and subtly mention the benefits of upgrading to a premium plan.";
        }

        $response = $aiService->generateContent($systemInstruction, [
            ['role' => 'user', 'text' => $prompt]
        ]);

        if (isset($response['text']) && !str_contains($response['text'], 'Sorry, an error occurred')) {
            $jsonStr = trim($response['text']);
            if(str_starts_with($jsonStr, '```json')) {
                $jsonStr = str_replace(['```json', '```'], '', $jsonStr);
            }
            $jsonStr = trim($jsonStr);
            
            $result = json_decode($jsonStr, true);
            if(json_last_error() === JSON_ERROR_NONE && isset($result['subject']) && isset($result['body'])) {
                
                $this->info("AI Email Generated! Subject: " . $result['subject']);
                
                Mail::to($user->email)->send(new DynamicJourneyMail($user, $result['subject'], $result['body']));
                
                $this->info("Email successfully sent to {$user->email}");
            } else {
                $this->error("Failed to parse AI JSON response.");
            }
        } else {
            $this->error("AI API returned an error or empty response.");
        }
    }
}
