<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\VertexAIService;
use App\Services\FcmService;
use Exception;

class AutomatedFeatureDiscoveryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'artera:feature-discovery {email?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze user usage and send AI generated push notifications suggesting unused features';

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
            // In production, you might only run this for users who haven't received a suggestion recently
            $users = User::where('is_subscribe', 1)->take(50)->get();
        }

        if ($users->isEmpty()) {
            $this->info("No users found for feature discovery.");
            return 0;
        }

        // Initialize AI service with admin ID 1
        $aiService = new VertexAIService(1);
        $fcmService = new FcmService();

        foreach ($users as $user) {
            $this->info("Analyzing user: {$user->name}");

            // Simulate usage analysis:
            // Since we don't have deep feature tracking tables yet, we'll use a mocked unused feature
            // In reality, this would query something like `UserFeatureUsage::where('user_id', $user->id)->pluck('feature_name')`
            $features = ['AI Image Generation', 'AI Text Generation', 'AI Video Scripting', 'Social Media Captioning'];
            
            // Randomly pick a feature they "haven't tried" for the sake of this system
            $unusedFeature = $features[array_rand($features)];

            $this->info("Selected unused feature for {$user->name}: {$unusedFeature}");

            $systemInstruction = "You are a friendly engagement AI for the Artera SaaS platform. Suggest a feature to a user who hasn't tried it yet. Keep it under 100 characters so it fits in a push notification. Return ONLY valid JSON: {\"title\": \"Short Title\", \"body\": \"Short push notification text\"}";
            
            $prompt = "User: {$user->name}. Feature they haven't tried yet: {$unusedFeature}. Write a very short push notification to encourage them to try it today.";

            try {
                $response = $aiService->generateContent($systemInstruction, [
                    ['role' => 'user', 'text' => $prompt]
                ]);

                if (isset($response['text'])) {
                    $jsonStr = trim($response['text']);
                    if(str_starts_with($jsonStr, '```json')) {
                        $jsonStr = str_replace(['```json', '```'], '', $jsonStr);
                    }
                    $jsonStr = trim($jsonStr);
                    
                    $result = json_decode($jsonStr, true);
                    
                    if (json_last_error() === JSON_ERROR_NONE && isset($result['title']) && isset($result['body'])) {
                        $title = $result['title'];
                        $body = $result['body'];
                        
                        $this->info("AI generated push - Title: $title | Body: $body");
                        
                        // Send Push via FCM
                        // $fcmService->sendNotificationToUser($user->id, $title, $body);
                        
                    }
                }
            } catch (Exception $e) {
                \Log::error("Feature Discovery AI failed for user {$user->id}: " . $e->getMessage());
            }
        }

        $this->info("Feature discovery automation completed.");
        return 0;
    }
}
