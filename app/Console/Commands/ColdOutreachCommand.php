<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Lead;
use App\Services\VertexAIService;
use Exception;

class ColdOutreachCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'artera:cold-outreach';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Draft and send personalized cold emails to unregistered leads';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info("Starting Cold Email Outreach...");
        
        $leads = Lead::whereNull('source')->orWhere('source', 'raw')->take(10)->get();

        if ($leads->isEmpty()) {
            // Mock a lead for testing if none exist
            $lead = Lead::create([
                'name' => 'Demo User',
                'email' => 'demo_lead_' . time() . '@example.com',
                'industry' => 'Real Estate',
                'source' => 'raw'
            ]);
            $leads->push($lead);
        }

        $aiService = new VertexAIService(1);

        foreach ($leads as $lead) {
            $this->info("Drafting cold email for: {$lead->email} ({$lead->industry})");

            $systemInstruction = "You are a top-tier B2B sales SDR for Artera SaaS. Write a short, highly personalized cold email. Return ONLY valid JSON: {\"subject\": \"Subject here\", \"body\": \"Body text here\"}";
            $prompt = "Write a cold email to a lead in the {$lead->industry} industry. Pitch our automated AI design tool.";

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
                        $this->info("Cold Email Generated:");
                        $this->info("Subject: " . $result['subject']);
                        $this->info("Body: " . $result['body']);
                        
                        // Mail::raw($result['body'], function($msg) use ($lead, $result) {
                        //    $msg->to($lead->email)->subject($result['subject']);
                        // });
                        
                        // Mark as contacted
                        $lead->source = 'contacted';
                        $lead->save();
                    }
                }
            } catch (Exception $e) {
                \Log::error("Cold outreach failed for lead {$lead->id}: " . $e->getMessage());
            }
        }

        return 0;
    }
}
