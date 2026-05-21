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
        
        // Prepare context for the AI
        $context = [
            'name' => $user->name,
            'health_score' => $user->health_score,
            'last_active' => $user->last_active_at ? Carbon::parse($user->last_active_at)->diffForHumans() : 'Never',
            'is_subscribed' => $user->is_subscribe ? 'Yes' : 'No',
            'usage_stats' => [
                'custom_posts' => $user->custom_post_used,
                'daily_drip' => $user->daily_drip_used,
                'magic_cloner' => $user->magic_cloner_used,
                'festival_posts' => $user->festival_post_used,
                'business_category_posts' => $user->business_category_post_used,
            ]
        ];

        $systemInstruction = "You are an expert SaaS Customer Success Manager and AI Retention Strategist. Based on the provided user profile, generate a concise, actionable 3-step retention strategy to prevent this user from churning. Also suggest a personalized email subject and short message to send them. Keep it professional and empathetic. Output in pure JSON format: {\"strategy_steps\": [\"step1\", \"step2\", \"step3\"], \"email_subject\": \"Subject here\", \"email_body\": \"Message here\"}";
        
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
}
