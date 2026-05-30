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

            // Build the common notification parts
            $notificationBlock = [
                'title' => $title,
                'body' => $message,
            ];

            $dataBlock = !empty($data) ? array_map('strval', $data) : [];

            if ($image) {
                $notificationBlock['image'] = $image;
                $dataBlock['image'] = $image;
            }

            $androidBlock = [
                'priority' => 'high',
                'notification' => [
                    'notification_priority' => 'PRIORITY_MAX',
                    'channel_id' => 'high_importance_channel',
                    'default_sound' => true,
                    'default_vibrate_timings' => true,
                ],
            ];
            if ($image) {
                $androidBlock['notification']['image'] = $image;
            }

            $apnsBlock = [
                'payload' => [
                    'aps' => [
                        'content-available' => 1,
                    ],
                ],
                'headers' => [
                    'apns-priority' => '10',
                ],
            ];

            // --- Strategy: Send to ALL registered device tokens for guaranteed delivery ---
            $allTokens = \App\Models\AndroidLogin::whereNotNull('fcmToken')
                ->where('fcmToken', '!=', '')
                ->pluck('fcmToken')
                ->unique()
                ->values()
                ->all();

            $sentCount = 0;
            $failedCount = 0;
            $unregisteredTokenIds = [];

            foreach ($allTokens as $token) {
                $messagePayload = [
                    'message' => [
                        'token' => $token,
                        'notification' => $notificationBlock,
                        'android' => $androidBlock,
                        'apns' => $apnsBlock,
                    ]
                ];
                if (!empty($dataBlock)) {
                    $messagePayload['message']['data'] = $dataBlock;
                }

                $response = Http::withHeaders([
                    'Authorization' => "Bearer {$accessToken}",
                    'Content-Type' => 'application/json',
                ])->post($endpoint, $messagePayload);

                if ($response->successful()) {
                    $sentCount++;
                } else {
                    $failedCount++;
                    // Clean up unregistered tokens
                    $body = $response->json();
                    $errorCode = $body['error']['details'][0]['errorCode'] ?? ($body['error']['status'] ?? '');
                    if (in_array($errorCode, ['UNREGISTERED', 'NOT_FOUND'])) {
                        $unregisteredTokenIds[] = $token;
                    }
                    Log::warning('FCM: Failed to send to token', ['status' => $response->status(), 'body' => $response->body()]);
                }
            }

            // Clean up invalid tokens from DB
            if (!empty($unregisteredTokenIds)) {
                \App\Models\AndroidLogin::whereIn('fcmToken', $unregisteredTokenIds)->delete();
                Log::info('FCM: Cleaned up ' . count($unregisteredTokenIds) . ' unregistered tokens');
            }

            // Also send via topic as a safety net (catches devices that registered but haven't logged in)
            $topicPayload = [
                'message' => [
                    'topic' => $topic,
                    'notification' => $notificationBlock,
                    'android' => $androidBlock,
                    'apns' => $apnsBlock,
                ]
            ];
            if (!empty($dataBlock)) {
                $topicPayload['message']['data'] = $dataBlock;
            }
            Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
                'Content-Type' => 'application/json',
            ])->post($endpoint, $topicPayload);

            Log::info("FCM: Broadcast complete. Sent to {$sentCount} tokens, {$failedCount} failed, topic '{$topic}' also sent.");
            return ['status' => 'success', 'message' => "Sent to {$sentCount} devices."];

        } catch (\Exception $e) {
            Log::error('FCM: Exception - ' . $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function sendNotificationToToken(string $token, string $title, string $message, ?string $image = null, array $data = []): array
    {
        if (!$this->isConfigured()) {
            return ['status' => 'error', 'message' => 'FCM Service not configured.'];
        }

        try {
            $accessToken = $this->getAccessToken();
            $endpoint = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

            $messagePayload = [
                'message' => [
                    'token' => $token,
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
                $messagePayload['message']['notification']['image'] = $image;
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
                $messagePayload['message']['data']['image'] = $image;
            } else {
                $messagePayload['message']['android'] = [
                    'priority' => 'high',
                    'notification' => [
                        'notification_priority' => 'PRIORITY_MAX',
                        'channel_id' => 'high_importance_channel',
                        'default_sound' => true,
                        'default_vibrate_timings' => true,
                    ]
                ];
            }

            $messagePayload['message']['apns'] = [
                'payload' => [
                    'aps' => [
                        'content-available' => 1,
                    ],
                ],
                'headers' => [
                    'apns-priority' => '10',
                ],
            ];

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
                'Content-Type' => 'application/json',
            ])->post($endpoint, $messagePayload);

            if ($response->successful()) {
                Log::info('FCM: Successfully sent notification to token', ['response' => $response->json()]);
                return ['status' => 'success', 'response' => $response->json()];
            } else {
                Log::error('FCM: API error to token', ['status' => $response->status(), 'body' => $response->body()]);
                return ['status' => 'error', 'message' => $response->body()];
            }

        } catch (\Exception $e) {
            Log::error('FCM: Exception to token - ' . $e->getMessage());
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    public function sendNotificationToUser(int $userId, string $title, string $message, ?string $image = null, array $data = []): array
    {
        $logins = \App\Models\AndroidLogin::where('userId', $userId)->get();
        if ($logins->isEmpty()) {
            return ['status' => 'error', 'message' => 'User not found or missing FCM token.'];
        }
        
        $hasSent = false;
        foreach ($logins as $login) {
            if (!empty($login->fcmToken)) {
                $result = $this->sendNotificationToToken($login->fcmToken, $title, $message, $image, $data);
                if ($result['status'] === 'success') {
                    $hasSent = true;
                } else {
                    // Auto-clean unregistered/invalid tokens
                    if (str_contains($result['message'] ?? '', 'UNREGISTERED') || str_contains($result['message'] ?? '', 'NOT_FOUND')) {
                        $login->delete();
                        Log::info("FCM: Cleaned up unregistered token for user {$userId}");
                    }
                }
            }
        }
        
        if (!$hasSent) {
            return ['status' => 'error', 'message' => 'User has no valid FCM tokens.'];
        }
        return ['status' => 'success', 'message' => 'Sent to user devices.'];
    }

    private function getAccessToken(): string
    {
        $cacheKey = 'fcm_v1_access_token_' . md5($this->projectId);
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Fix for "Invalid JWT Signature" caused by wrong local system time:
        // Fetch the true UTC time from worldtimeapi so the JWT signature matches Google's servers.
        $now = time();
        try {
            $timeResponse = Http::timeout(2)->get('http://worldtimeapi.org/api/timezone/Etc/UTC');
            if ($timeResponse->successful()) {
                $now = $timeResponse->json('unixtime', time());
            }
        } catch (\Exception $e) {
            // fallback to local time if API fails
            $now = time();
        }

        $header = base64url_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));

        $claimSet = [
            'iss' => $this->serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => $this->serviceAccount['token_uri'],
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

if (!function_exists('App\Services\base64url_encode')) {
    function base64url_encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
