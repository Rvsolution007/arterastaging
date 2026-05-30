<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Services\VertexAIService;
use Carbon\Carbon;

class ChurnController extends Controller
{
    public function index()
    {
        // Get all users, ordered by lowest health score first
        $users = User::orderBy('health_score', 'asc')->paginate(15);
        
        $stats = [
            'total_high_risk' => User::where('churn_risk', 'high')->count(),
            'total_medium_risk' => User::where('churn_risk', 'medium')->count(),
            'avg_health' => (int) User::avg('health_score'),
        ];

        return view('admin.churn.index', compact('users', 'stats'));
    }

    public function generateStrategy(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        // Calculate Penalties for Context
        $activity_penalty = 0;
        $activity_reason = "Active recently";
        if (!$user->last_active_at) {
            $activity_penalty = -40;
            $activity_reason = "Never logged in";
        } else {
            $daysInactive = Carbon::parse($user->last_active_at)->diffInDays(now());
            if ($daysInactive > 14) {
                $activity_penalty = -40;
                $activity_reason = "Inactive for more than 14 days ($daysInactive days)";
            } elseif ($daysInactive > 7) {
                $activity_penalty = -20;
                $activity_reason = "Inactive for more than 7 days ($daysInactive days)";
            } elseif ($daysInactive > 3) {
                $activity_penalty = -10;
                $activity_reason = "Inactive for more than 3 days ($daysInactive days)";
            }
        }

        $subscription_penalty = 0;
        $subscription_reason = "Active subscription";
        if (!$user->is_subscribe || ($user->subscription_end_date && Carbon::parse($user->subscription_end_date)->isPast())) {
            $subscription_penalty = -20;
            $subscription_reason = "Not subscribed or subscription expired";
        }

        $usageSum = $user->custom_post_used + 
                    $user->daily_drip_used + 
                    $user->magic_cloner_used + 
                    $user->festival_post_used + 
                    $user->business_category_post_used;

        $feature_usage_penalty = 0;
        if ($usageSum == 0) {
            $feature_usage_penalty = -20;
        } elseif ($usageSum < 5) {
            $feature_usage_penalty = -10;
        }

        $unused_features = [];
        if ($user->custom_post_used == 0) $unused_features[] = "Custom Posts";
        if ($user->daily_drip_used == 0) $unused_features[] = "Daily Drip";
        if ($user->magic_cloner_used == 0) $unused_features[] = "Magic Cloner";
        if ($user->festival_post_used == 0) $unused_features[] = "Festival Posts";
        if ($user->business_category_post_used == 0) $unused_features[] = "Business Category Posts";

        // Prepare context for the AI
        $context = [
            'name' => $user->name,
            'health_score' => $user->health_score,
            'last_active' => $user->last_active_at ? Carbon::parse($user->last_active_at)->diffForHumans() : 'Never',
            'is_subscribed' => $user->is_subscribe ? 'Yes' : 'No',
            'health_score_penalties_breakdown' => [
                'activity_penalty' => [
                    'points_deducted' => $activity_penalty,
                    'reason' => $activity_reason
                ],
                'subscription_penalty' => [
                    'points_deducted' => $subscription_penalty,
                    'reason' => $subscription_reason
                ],
                'feature_usage_penalty' => [
                    'points_deducted' => $feature_usage_penalty,
                    'total_usage_count' => $usageSum,
                    'features_never_used' => $unused_features
                ]
            ]
        ];

        $systemInstruction = "You are an expert SaaS Customer Success Manager and AI Retention Strategist. Based on the provided user profile and their Health Score Penalty Breakdown, generate a concise, actionable 3-step retention strategy to prevent this user from churning.\n\n" . 
        "Also suggest a highly personalized outreach message to send them. CRITICAL: The message MUST specifically mention the EXACT features/templates they haven't used (e.g., if they haven't used Custom Posts, suggest they try it out) and gently address their inactivity. Use Markdown **bold** for important keywords and feature names, and sprinkle in relevant emojis to make it attractive and engaging.\n\n" .
        "Additionally, generate a short, attractive, and actionable Push Notification to send to their mobile app. The title should be catchy (max 50 chars) and the message should be punchy (max 150 chars), inviting them back based on their unused features.\n\n" .
        "Output in pure JSON format: {\"strategy_steps\": [\"step1\", \"step2\", \"step3\"], \"email_subject\": \"Subject here\", \"email_body\": \"Message here\", \"push_title\": \"Push title here\", \"push_message\": \"Push message here\"}";
        
        $prompt = json_encode($context);

        $aiService = new VertexAIService(auth()->id());
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
            if(json_last_error() === JSON_ERROR_NONE) {
                return response()->json(['status' => 'success', 'data' => $result]);
            }
        }

        return response()->json(['status' => 'error', 'message' => 'Failed to generate strategy or invalid JSON received.']);
    }

    public function sendMail(Request $request, $id)
    {
        $request->validate([
            'subject' => 'required|string',
            'body' => 'required|string',
        ]);

        $user = User::findOrFail($id);

        try {
            \Illuminate\Support\Facades\Mail::raw($request->body, function ($message) use ($user, $request) {
                $message->to($user->email)
                        ->subject($request->subject);
            });
            return response()->json(['status' => 'success', 'message' => 'Email sent successfully!']);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Send Mail Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Email Error: ' . $e->getMessage()]);
        }
    }

    public function sendStrategyWhatsapp(Request $request, $id)
    {
        $request->validate([
            'body' => 'required|string',
        ]);

        $user = User::findOrFail($id);

        try {
            if (!$user->mobile_no) {
                return response()->json(['status' => 'error', 'message' => 'User does not have a mobile number saved.']);
            }
            
            $serverUrl = rtrim(\App\Models\WhatsAppSetting::getWhatsAppSetting('evolution_server_url'), '/');
            $apiKey = \App\Models\WhatsAppSetting::getWhatsAppSetting('evolution_api_key');
            $instanceName = \App\Models\WhatsAppSetting::getWhatsAppSetting('evolution_instance_name');
            
            if (!$serverUrl || !$apiKey || !$instanceName) {
                return response()->json(['status' => 'error', 'message' => 'WhatsApp API Settings are incomplete. Configure them in Settings -> WhatsApp.']);
            }
            
            $number = $user->mobile_no;
            if (!str_starts_with($number, '91') && strlen($number) == 10) {
                $number = "91" . $number;
            }

            $url = "{$serverUrl}/message/sendText/{$instanceName}";

            $text = $request->body;
            
            // Convert HTML to WhatsApp markdown
            $text = preg_replace('/<(b|strong)[^>]*>(.*?)<\/\1>/i', '*$2*', $text);
            $text = preg_replace('/<(i|em)[^>]*>(.*?)<\/\1>/i', '_$2_', $text);
            $text = preg_replace('/<br\s*\/?>/i', "\n", $text);
            $text = preg_replace('/<\/p>/i', "\n\n", $text);
            
            // Strip remaining HTML tags
            $text = strip_tags($text);
            
            // Convert standard markdown (**text**) to WhatsApp markdown (*text*)
            $text = preg_replace('/\*\*(.*?)\*\*/', '*$1*', $text);
            
            // Decode HTML entities
            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5);
            
            $text = trim($text);

            $data = [
                "number" => $number,
                "text" => $text
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "apikey: {$apiKey}",
                "Content-Type: application/json"
            ]);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            $result = curl_exec($ch);
            curl_close($ch);

            return response()->json(['status' => 'success', 'message' => "WhatsApp sent successfully!"]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Send Strategy WhatsApp Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to send WhatsApp.']);
        }
    }

    public function sendNotification(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string',
            'message' => 'required|string',
        ]);

        $user = User::findOrFail($id);

        try {
            $fcmService = new \App\Services\FcmService();
            if (!$fcmService->isConfigured()) {
                return response()->json(['status' => 'error', 'message' => 'FCM is not configured.']);
            }

            $result = $fcmService->sendNotificationToUser(
                $user->id,
                $request->title,
                $request->message,
                null,
                ['type' => 'churn_retention']
            );

            if ($result['status'] === 'error') {
                return response()->json(['status' => 'error', 'message' => $result['message']]);
            }

            return response()->json(['status' => 'success', 'message' => 'Push notification sent successfully!']);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Send Push Notification Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to send push notification.']);
        }
    }

    public function sendDunningEmail($id)
    {
        try {
            $user = User::findOrFail($id);
            // Uses standard Laravel Mailer configured in .env or Email Settings
            \Illuminate\Support\Facades\Mail::raw("Hi {$user->name}, your recent payment failed. Please update your payment method to avoid service interruption.", function($message) use ($user) {
                $message->to($user->email)->subject("Action Required: Payment Failed");
            });
            return response()->json(['status' => 'success', 'message' => "Email sent successfully to {$user->email}"]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Send Dunning Email Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to send email. Please check your SMTP / Email Settings.']);
        }
    }

    public function sendDunningWhatsapp($id)
    {
        try {
            $user = User::findOrFail($id);
            if (!$user->mobile_no) {
                return response()->json(['status' => 'error', 'message' => 'User does not have a mobile number saved.']);
            }
            
            $serverUrl = rtrim(\App\Models\WhatsAppSetting::getWhatsAppSetting('evolution_server_url'), '/');
            $apiKey = \App\Models\WhatsAppSetting::getWhatsAppSetting('evolution_api_key');
            $instanceName = \App\Models\WhatsAppSetting::getWhatsAppSetting('evolution_instance_name');
            
            if (!$serverUrl || !$apiKey || !$instanceName) {
                return response()->json(['status' => 'error', 'message' => 'WhatsApp API Settings are incomplete. Configure them in Settings -> WhatsApp.']);
            }
            
            $msg = "Hi {$user->name}, your recent payment failed. Please update your payment method to continue using the Premium Plan.";
            $number = $user->mobile_no;
            if (!str_starts_with($number, '91') && strlen($number) == 10) {
                $number = "91" . $number;
            }

            $url = "{$serverUrl}/message/sendText/{$instanceName}";

            $data = [
                "number" => $number,
                "text" => $msg
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                "apikey: {$apiKey}",
                "Content-Type: application/json"
            ]);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            $result = curl_exec($ch);
            curl_close($ch);

            return response()->json(['status' => 'success', 'message' => "WhatsApp sent successfully!"]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Send Dunning WhatsApp Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to send WhatsApp.']);
        }
    }
    public function triggerDiscovery(Request $request, $featureName)
    {
        try {
            // Find a few users to send the discovery push to (mocked as top 5 active users for demo)
            $users = User::orderBy('id', 'desc')->take(5)->get();
            
            if ($users->isEmpty()) {
                return response()->json(['status' => 'error', 'message' => 'No active users found.']);
            }

            $aiService = new VertexAIService(auth()->id() ?? 1);
            $fcmService = new \App\Services\FcmService();
            
            $sentCount = 0;
            
            foreach ($users as $user) {
                // Generate personalized push using AI
                $systemInstruction = "You are a friendly engagement AI for the Artera SaaS platform. Suggest a feature to a user who hasn't tried it yet. Keep it under 100 characters so it fits in a push notification. Return ONLY valid JSON: {\"title\": \"Short Title\", \"body\": \"Short push notification text\"}";
                $prompt = "User: {$user->name}. Feature they haven't tried yet: {$featureName}. Write a very short push notification to encourage them to try it today.";
                
                $response = $aiService->generateContent($systemInstruction, [
                    ['role' => 'user', 'text' => $prompt]
                ]);
                
                $cleanJson = str_replace(['```json', '```'], '', $response['text'] ?? '');
                $aiContent = json_decode($cleanJson, true);
                
                if (isset($aiContent['title']) && isset($aiContent['body'])) {
                    if ($fcmService->isConfigured()) {
                        $result = $fcmService->sendNotificationToUser(
                            $user->id,
                            $aiContent['title'],
                            $aiContent['body'],
                            null, // No image for now
                            [
                                'action' => 'feature_discovery',
                                'feature_name' => $featureName
                            ]
                        );
                        
                        if ($result['status'] === 'success') {
                            $sentCount++;
                        }
                    }
                }
            }
            
            return response()->json([
                'status' => 'success', 
                'message' => "AI Banner Push sent to $sentCount users for $featureName"
            ]);
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Trigger Discovery Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Failed to trigger discovery: ' . $e->getMessage()]);
        }
    }
}
