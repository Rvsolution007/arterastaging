<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\VertexAIService;
use Exception;

class SocialMediaPosterCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'artera:social-post';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate and mock-publish a social media post via AI';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info("Starting Social Media Auto-Poster...");
        
        $aiService = new VertexAIService(1);
        
        $systemInstruction = "You are a social media manager. Write a single short, engaging tweet about a new feature in the Artera app (e.g. AI Image Generation, Business Cards). Include 2 emojis and 2 hashtags. Return ONLY the raw text of the tweet.";
        $prompt = "Write a tweet for today's update.";

        try {
            $response = $aiService->generateContent($systemInstruction, [
                ['role' => 'user', 'text' => $prompt]
            ]);

            if (isset($response['text'])) {
                $tweet = trim($response['text']);
                
                $this->info("Generated Tweet:");
                $this->line($tweet);
                
                // MOCK API CALL TO TWITTER/X or FACEBOOK
                // $twitterClient->postTweet(['text' => $tweet]);
                
                $this->info("\nMock: Successfully dispatched to Social Media APIs.");
            }
        } catch (Exception $e) {
            \Log::error("Social poster failed: " . $e->getMessage());
            $this->error("Failed to generate social post.");
        }

        return 0;
    }
}
