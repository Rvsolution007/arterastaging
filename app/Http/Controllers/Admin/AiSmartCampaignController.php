<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\VertexAIService;
use App\Services\FcmService;
use App\Models\UserNotification;
use Illuminate\Support\Str;
use App\Models\NotificationSetting;
use App\Models\StorageSetting;
use Illuminate\Support\Facades\Storage;

class AiSmartCampaignController extends Controller
{
    public function index()
    {
        return view('admin.ai_campaigns.index');
    }

    public function generateCopy(Request $request)
    {
        $prompt = $request->input('prompt');
        $tone = $request->input('tone', 'persuasive');
        
        $userId = auth()->id() ?? 1; // Fallback to 1 if not logged in just in case
        $aiService = new VertexAIService($userId);
        
        $systemInstruction = "You are an expert marketing copywriter. Write a push notification based on the user's prompt. Tone should be $tone. Keep it concise, engaging, and include emojis. Return ONLY a JSON object in this exact format, with no markdown formatting: {\"title\": \"The generated title (max 50 chars)\", \"message\": \"The generated message (max 150 chars)\"}";
        
        $response = $aiService->generateContent($systemInstruction, [
            ['role' => 'user', 'text' => "User Prompt: " . $prompt]
        ]);
        
        try {
            if (isset($response['text']) && !str_contains($response['text'], 'Sorry, an error occurred')) {
                $jsonStr = trim($response['text']);
                if(str_starts_with($jsonStr, '```json')) {
                    $jsonStr = str_replace(['```json', '```'], '', $jsonStr);
                }
                $data = json_decode(trim($jsonStr), true);
                
                if(isset($data['title']) && isset($data['message'])) {
                    return response()->json([
                        'success' => true,
                        'title' => $data['title'],
                        'message' => $data['message']
                    ]);
                }
            }
        } catch (\Exception $e) {
            // ignore JSON error, fallback below
        }
        
        return response()->json([
            'success' => false,
            'error' => 'Failed to generate correct format. Raw: ' . json_encode($response)
        ]);
    }

    public function sendCampaign(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'message' => 'required|string',
        ]);

        $fileName = null;
        if ($request->hasFile('image')) {
            if (StorageSetting::getStorageSetting("storage") == "DigitalOcean") {
                $image = $request->file('image');
                $fileName = Str::uuid() . '.' . $image->getClientOriginalExtension();
                Storage::disk('spaces')->put('uploads/' . $fileName, file_get_contents($image), 'public');
            } else {
                $destinationPath = public_path('uploads');
                $extension = $request->file('image')->getClientOriginalExtension();
                $fileName = Str::uuid() . '.' . $extension;
                $request->file('image')->move($destinationPath, $fileName);
            }
        }

        UserNotification::create([
            "title" => $request->title,
            "message" => $request->message,
            "image" => $fileName ?? null,
            "type" => "ai_campaign",
            "type_id" => null,
        ]);

        $fcmService = new FcmService();
        if ($fcmService->isConfigured()) {
            $imageFullUrl = null;
            if ($fileName) {
                $imageFullUrl = (StorageSetting::getStorageSetting('storage') == 'DigitalOcean') 
                    ? Storage::disk('spaces')->url('uploads/' . $fileName) 
                    : asset('uploads/' . $fileName);
            }

            $content = ["type" => "ai_campaign"];

            $fcmResult = $fcmService->sendNotification(
                $request->title,
                $request->message,
                $imageFullUrl,
                $content,
                'all'
            );

            if ($fcmResult['status'] === 'error') {
                return back()->with("error", "Campaign Saved, but FCM error: " . $fcmResult['message']);
            }
            return back()->with("message", "Smart AI Campaign Sent Successfully!");
        }

        return back()->with("error", "Campaign Saved, but FCM is not configured.");
    }
}
