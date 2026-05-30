<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\DynamicDiscountHistory;
use App\Services\VertexAIService;
use Exception;

class DynamicDiscountCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'artera:dynamic-discount {email?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Offer AI-generated dynamic discounts to users with high churn risk';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $email = $this->argument('email');

        // Target users with low health score (churn risk)
        // If testing, we use the specific email provided
        if ($email) {
            $users = User::where('email', $email)->get();
        } else {
            $threshold = \App\Models\Setting::getGlobalValue('retention', 'dynamic_discount_threshold', 40);
            
            $users = User::where('is_subscribe', 1)
                         ->whereNotNull('health_score')
                         ->where('health_score', '<', $threshold)
                         ->take(20)
                         ->get();
        }

        if ($users->isEmpty()) {
            $this->info("No high-risk users found for dynamic discounting.");
            return 0;
        }

        $aiService = new VertexAIService(1);

        foreach ($users as $user) {
            $this->info("Generating discount for high-risk user: {$user->name} (Score: {$user->health_score})");

            // Mock saving a coupon to DB. In a real scenario, you'd insert into a `coupons` table.
            $discountCode = "STAY" . rand(100, 999) . "30OFF";

            $systemInstruction = "You are a customer retention expert for Artera SaaS. Write a short, personalized email offering a 30% discount to a user who might cancel. Return ONLY valid JSON: {\"subject\": \"Subject here\", \"body\": \"Body text here\"}";
            $prompt = "User: {$user->name}. Coupon Code: {$discountCode}.";

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
                    
                    if (json_last_error() === JSON_ERROR_NONE && isset($result['subject']) && isset($result['body'])) {
                        $this->info("AI Email generated:");
                        $this->info("Subject: " . $result['subject']);
                        $this->info("Body: " . $result['body']);
                        
                        DynamicDiscountHistory::create([
                            'user_id' => $user->id,
                            'discount_code' => $discountCode,
                            'ai_subject' => $result['subject'],
                            'ai_body' => $result['body'],
                        ]);

                        // Send via Mail facade here
                        // Mail::raw($result['body'], function($msg) use ($user, $result) {
                        //    $msg->to($user->email)->subject($result['subject']);
                        // });
                    }
                }
            } catch (Exception $e) {
                \Log::error("Dynamic discount AI failed for user {$user->id}: " . $e->getMessage());
            }
        }

        return 0;
    }
}
