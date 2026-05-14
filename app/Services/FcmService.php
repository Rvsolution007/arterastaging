<?php

namespace App\Services;

use App\Models\NotificationSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

class FcmService
{
    private array $serviceAccount;
    private string $projectId;

    public function __construct()
    {
        $this->serviceAccount = $this->loadServiceAccount();
        $this->projectId = $this->serviceAccount['project_id'] ?? '';
    }

    /**
     * Load Service Account credentials from encrypted DB or legacy file path.
     */
    private function loadServiceAccount(): array
    {
        // Try encrypted format first
        $encrypted = NotificationSetting::getNotificationSetting('firebase_service_account_encrypted');
        if ($encrypted) {
            try {
                $json = Crypt::decryptString($encrypted);
                $sa = json_decode($json, true);
                if ($sa && isset($sa['client_email']) && isset($sa['private_key'])) {
                    return $sa;
                }
            } catch (\Exception $e) {
                Log::error('FCM: Failed to decrypt Service Account credentials: ' . $e->getMessage());
            }
        }

        // Fallback: legacy file-path format (if applicable)
        $saPath = NotificationSetting::getNotificationSetting('firebase_service_account');
        if ($saPath) {
            // key_value stores something like 'storage/app/firebase-service-account.json'
            $fullPath = base_path($saPath);
            if (file_exists($fullPath)) {
                $sa = json_decode(file_get_contents($fullPath), true);
                if ($sa && is_array($sa)) {
                    return $sa;
                }
            }
        }

        return [];
    }

    public function isConfigured(): bool
    {
        return !empty($this->projectId) && !empty($this->serviceAccount);
    }

    public function sendNotification(string $title, string $message, ?string $image = null, array $data = [], string $topic = 'all'): array
    {
        if (!$this->isConfigured()) {
            return ['status' => 'error', 'message' => 'FCM Service not configured.'];
        }

        try {
            $accessToken = $this->getAccessToken();
            $endpoint = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

            $messagePayload = [
                'message' => [
                    'topic' => $topic,
                    'notification' => [
                        'title' => $title,
                        'body' => $message,
                    ],
                ]
            ];

            if (!empty($data)) {
                $messagePayload['message']['data'] = array_map('strval', $data);
            }

            if ($image) {
                // General notification image (FCM v1)
                $messagePayload['message']['notification']['image'] = $image;
                
                // Android-specific configuration
                $messagePayload['message']['android'] = [
                    'priority' => 'high',
                    'notification' => [
                        'image' => $image,
                        'notification_priority' => 'PRIORITY_MAX',
                        'channel_id' => 'high_importance_channel',
                        'default_sound' => true,
                        'default_vibrate_timings' => true,
                    ]
                ];
                
                // Also include in data for manual handling if needed
                $messagePayload['message']['data']['image'] = $image;
            } else {
                // Still set priority even if no image
                $messagePayload['message']['android'] = [
                    'priority' => 'high',
                ];
            }

            // Web/iOS priority hints
            $messagePayload['message']['apns'] = [
                'payload' => [
                    'aps' => [
                        'content-available' => 1,
                    ],
                ],
                'headers' => [
                    'apns-priority' => '10', // 10 is high, 5 is normal
                ],
            ];

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
                'Content-Type' => 'application/json',
            ])->post($endpoint, $messagePayload);

            if ($response->successful()) {
                Log::info('FCM: Successfully sent notification', ['response' => $response->json()]);
                return ['status' => 'success', 'response' => $response->json()];
            } else {
                Log::error('FCM: API error', ['status' => $response->status(), 'body' => $response->body()]);
                return ['status' => 'error', 'message' => $response->body()];
            }

        } catch (\Exception $e) {
            Log::error('FCM: Exception - ' . $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    private function getAccessToken(): string
    {
        $cacheKey = 'fcm_v1_access_token_' . md5($this->projectId);
        return Cache::remember($cacheKey, 3300, function () {
            return $this->generateAccessToken();
        });
    }

    private function generateAccessToken(): string
    {
        $now = time();
        $header = base64url_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));

        $claimSet = [
            'iss' => $this->serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        $payload = base64url_encode(json_encode($claimSet));
        $signingInput = "{$header}.{$payload}";

        $privateKeyString = $this->serviceAccount['private_key'] ?? '';
        $privateKeyString = str_replace(['\\n', '\\r'], ["\n", ""], $privateKeyString);
        $privateKeyString = str_replace("\r", "", $privateKeyString);
        $privateKeyString = trim($privateKeyString);

        $privateKey = openssl_pkey_get_private($privateKeyString);
        if (!$privateKey) {
            throw new \RuntimeException('FCM: Invalid private key in service account');
        }

        openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        $jwt = $signingInput . '.' . base64url_encode($signature);

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('FCM: Failed to get access token - ' . $response->body());
        }

        return $response->json('access_token');
    }
}

if (!function_exists('base64url_encode')) {
    function base64url_encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
