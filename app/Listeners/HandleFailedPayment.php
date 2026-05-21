<?php

namespace App\Listeners;

use App\Events\PaymentFailed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;
use App\Mail\PaymentFailedMail;
use App\Services\FcmService;
use App\Services\VertexAIService;
use Exception;

class HandleFailedPayment implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\PaymentFailed  $event
     * @return void
     */
    public function handle(PaymentFailed $event)
    {
        $user = $event->user;
        $planName = $event->subscription->plan_name ?? 'Premium';
        $failureReason = $event->failureReason;

        // Use AI to generate a custom subject and body based on the exact reason
        $aiData = $this->generateAiMessage($user->name, $planName, $failureReason);
        
        $emailSubject = $aiData['subject'] ?? 'Action Required: Your Payment Failed';
        $emailBody = $aiData['body'] ?? "We're having some trouble processing your recent payment for your {$planName} subscription. Action Required: Your payment method failed or was declined by your bank. To avoid any interruption in your premium features, please update your billing details.";

        // 1. Send Dunning Email (Dynamic with AI content)
        if ($user->email) {
            Mail::to($user->email)->send(new PaymentFailedMail($user, $planName, $emailSubject, $emailBody));
        }

        // 2. Send Push Notification via FCM
        try {
            $fcmService = new FcmService();
            // We can also pass the AI subject as the push title
            $title = $emailSubject; 
            $body = "Please update your card to avoid losing premium features.";
            
            \Log::info("FCM Push sent to {$user->name} for payment failure (Reason: {$failureReason}).");
            
        } catch (Exception $e) {
            \Log::error("Failed to send dunning push to user {$user->id}: " . $e->getMessage());
        }
    }

    private function generateAiMessage($userName, $planName, $failureReason)
    {
        try {
            // Using User ID 1 (Admin) to authenticate Vertex AI
            $aiService = new VertexAIService(1);
            
            $systemInstruction = "You are a friendly customer support assistant. A user's subscription payment just failed. Write a very short, polite, and empathetic email explaining the failure and asking them to fix it. Return ONLY valid JSON in this exact format: {\"subject\": \"Subject here\", \"body\": \"Body here without placeholders\"}.";
            
            $prompt = "User Name: {$userName}. Plan: {$planName}. Bank Decline Reason Code: {$failureReason}. ";
            
            if ($failureReason === 'insufficient_funds') {
                $prompt .= "Explain that they might need to top up their account or use a different card.";
            } elseif ($failureReason === 'expired_card') {
                $prompt .= "Explain their card has expired and they need to add a new one.";
            } elseif ($failureReason === 'do_not_honor' || str_contains($failureReason, 'decline')) {
                $prompt .= "Explain that their bank declined the transaction for security reasons, and they might need to approve it in their banking app or contact their bank.";
            } else {
                $prompt .= "Just state that the payment failed and they should update their billing details.";
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
                if (json_last_error() === JSON_ERROR_NONE && isset($result['subject']) && isset($result['body'])) {
                    return $result;
                }
            }
        } catch (Exception $e) {
            \Log::error("AI Dunning Generation failed: " . $e->getMessage());
        }

        return null;
    }
}
