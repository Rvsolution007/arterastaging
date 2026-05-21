<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Blog;
use App\Services\VertexAIService;
use Illuminate\Support\Str;
use Exception;

class AIBlogGeneratorCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'artera:ai-blog';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate and publish an SEO-optimized blog post using AI';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info("Starting AI Blog Generation...");
        
        $aiService = new VertexAIService(1);
        
        $systemInstruction = "You are an expert SEO copywriter for Artera SaaS. Write a 500-word blog post in HTML format. Do NOT use markdown. Return ONLY valid JSON: {\"title\": \"Catchy Title\", \"content\": \"<p>HTML content here...</p>\", \"keywords\": \"seo, marketing, ai\"}";
        $prompt = "Write a blog post about how AI is changing digital marketing and graphic design in 2026. Make it engaging and optimized for search engines.";

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
                
                if (json_last_error() === JSON_ERROR_NONE && isset($result['title']) && isset($result['content'])) {
                    $title = $result['title'];
                    $content = $result['content'];
                    $keywords = $result['keywords'] ?? 'ai, saas';
                    
                    Blog::create([
                        'title' => $title,
                        'slug' => Str::slug($title) . '-' . time(),
                        'content' => $content,
                        'meta_keywords' => $keywords,
                        'status' => 'published'
                    ]);
                    
                    $this->info("Blog published successfully: " . $title);
                }
            }
        } catch (Exception $e) {
            \Log::error("Blog generation failed: " . $e->getMessage());
            $this->error("Failed to generate blog.");
        }

        return 0;
    }
}
