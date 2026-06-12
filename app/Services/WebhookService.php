<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    /**
     * Dispatch a webhook securely to the WhatsApp CRM VPS
     *
     * @param string $event    The event name (e.g., 'product.published')
     * @param array  $payload  The data to sync
     * @return bool
     */
    public static function dispatchToCRM(string $event, array $payload): bool
    {
        // Add metaflag to prevent infinite sync loops between systems
        $payload['_meta'] = [
            'origin' => 'artera_pixel_primary',
            'event'  => $event,
            'timestamp' => now()->timestamp,
        ];

        // Ensure this secret is configured in the .env file!
        // Format: WH_CRM_SECRET=your_long_secure_random_string
        $secret = env('WH_CRM_SECRET', 'fallback_insecure_secret_deploy_only');
        $targetUrl = env('WH_CRM_URL', 'https://wa-crm.com/api/webhooks/artera_pixel-sync');

        $jsonPayload = json_encode($payload);
        
        // Create HMAC SHA256 signature for security verification by the VPS
        $signature = hash_hmac('sha256', $jsonPayload, $secret);

        try {
            $response = Http::withHeaders([
                'X-ArtEra-Pixel-Signature' => $signature,
                'Content-Type'         => 'application/json',
                'Accept'               => 'application/json',
            ])->timeout(10)->post($targetUrl, $payload);

            if ($response->successful()) {
                Log::info("Webhook [{$event}] successfully dispatched to CRM VPS.");
                return true;
            } else {
                Log::error("Webhook [{$event}] failed to dispatch to CRM VPS. Status: " . $response->status());
                return false;
            }
        } catch (\Exception $e) {
            Log::error("Webhook [{$event}] connection exception: " . $e->getMessage());
            return false;
        }
    }
}
