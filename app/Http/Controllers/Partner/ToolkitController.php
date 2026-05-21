<?php

namespace App\Http\Controllers\Partner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use App\Models\AiSetting;
use Illuminate\Support\Facades\Crypt;

class ToolkitController extends Controller
{
    public function index()
    {
        return view('partner.toolkit.index');
    }

    public function generate(Request $request)
    {
        $request->validate([
            'platform' => 'required',
            'tone' => 'required',
            'product' => 'nullable',
        ]);

        $user = Auth::user();
        if (!$user->referral_code) {
            $user->referral_code = strtoupper(\Illuminate\Support\Str::random(8));
            $user->save();
        }
        
        $referralLink = url('/register?ref=' . $user->referral_code);

        $prompt = "Generate a short promotional text for an affiliate marketer to post on " . $request->platform . ".
                   The tone should be " . $request->tone . ".
                   The product or feature they are promoting is: " . ($request->product ?? 'our amazing SaaS platform') . ".
                   They must include their affiliate link in the post: " . $referralLink . "
                   Do not output any markdown formatting other than line breaks, just the raw text they can copy-paste.";

        try {
            $provider = AiSetting::getAiSetting('ai_provider') ?: 'vertex';

            if ($provider === 'gemini') {
                $result = $this->callGemini($prompt);
            } elseif ($provider === 'chatgpt') {
                $result = $this->callChatGPT($prompt);
            } else {
                $result = $this->callVertex($prompt);
            }

            if ($result['success']) {
                return response()->json(['success' => true, 'text' => $result['text']]);
            } else {
                return response()->json(['success' => false, 'message' => $result['message']]);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'AI Generation failed: ' . $e->getMessage()]);
        }
    }

    private function callGemini($prompt)
    {
        $apiKey = AiSetting::getAiSetting('gemini_api_key');
        $model = trim(AiSetting::getAiSetting('gemini_model') ?: 'gemini-2.0-flash');

        if (!$apiKey) {
            return ['success' => false, 'message' => 'Gemini API Key is not configured. Please set it in Admin > Settings > AI Configuration.'];
        }

        $cleanModel = preg_replace('/^models\//', '', $model);
        $url = "https://generativelanguage.googleapis.com/v1/models/" . urlencode($cleanModel) . ":generateContent?key={$apiKey}";

        return $this->executeGoogleAI($url, $prompt);
    }

    private function callVertex($prompt)
    {
        $encrypted = AiSetting::getAiSetting('google_application_credentials_encrypted');
        if (!$encrypted) {
            return ['success' => false, 'message' => 'Vertex AI credentials are not configured. Please upload your Service Account JSON in Admin > Settings.'];
        }

        try {
            $json = Crypt::decryptString($encrypted);
            $sa = json_decode($json, true);
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Failed to decrypt Vertex AI credentials.'];
        }

        // Get OAuth2 access token
        $now = time();
        $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
        $claims = json_encode([
            'iss' => $sa['client_email'],
            'scope' => 'https://www.googleapis.com/auth/cloud-platform',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]);

        $b64Header = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
        $b64Claims = rtrim(strtr(base64_encode($claims), '+/', '-_'), '=');
        $signingInput = $b64Header . '.' . $b64Claims;

        openssl_sign($signingInput, $signature, $sa['private_key'], OPENSSL_ALGO_SHA256);
        $b64Signature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
        $jwt = $signingInput . '.' . $b64Signature;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://oauth2.googleapis.com/token',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        $tokenResponse = json_decode(curl_exec($ch), true);
        curl_close($ch);

        $accessToken = $tokenResponse['access_token'] ?? null;
        if (!$accessToken) {
            return ['success' => false, 'message' => 'Failed to get Vertex AI access token.'];
        }

        $projectId = AiSetting::getAiSetting('google_cloud_project_id');
        $location = AiSetting::getAiSetting('vertex_location') ?: 'us-central1';
        $model = AiSetting::getAiSetting('ai_model') ?: 'gemini-2.0-flash';

        $url = "https://{$location}-aiplatform.googleapis.com/v1/projects/{$projectId}/locations/{$location}/publishers/google/models/{$model}:generateContent";
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken,
        ];

        return $this->executeGoogleAI($url, $prompt, $headers);
    }

    private function callChatGPT($prompt)
    {
        $apiKey = AiSetting::getAiSetting('chatgpt_api_key');
        $model = trim(AiSetting::getAiSetting('chatgpt_model') ?: 'gpt-4o-mini');

        if (!$apiKey) {
            return ['success' => false, 'message' => 'ChatGPT API Key is not configured. Please set it in Admin > Settings > AI Configuration.'];
        }

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => 1000,
            'temperature' => 0.7,
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://api.openai.com/v1/chat/completions',
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response, true);

        if ($httpCode === 200 && isset($data['choices'][0]['message']['content'])) {
            return ['success' => true, 'text' => $data['choices'][0]['message']['content']];
        }

        return ['success' => false, 'message' => 'ChatGPT Error: ' . ($data['error']['message'] ?? 'Unknown error')];
    }

    private function executeGoogleAI($url, $prompt, $headers = null)
    {
        $payload = [
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => $prompt]]]
            ],
            'generationConfig' => [
                'maxOutputTokens' => 1000,
                'temperature' => 0.7,
            ]
        ];

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => $headers ?: ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return ['success' => false, 'message' => 'Connection error: ' . $curlError];
        }

        $data = json_decode($response, true);

        if ($httpCode === 200 && isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return ['success' => true, 'text' => $data['candidates'][0]['content']['parts'][0]['text']];
        }

        return ['success' => false, 'message' => 'API Error (HTTP ' . $httpCode . '): ' . ($data['error']['message'] ?? 'Unknown error')];
    }
}
