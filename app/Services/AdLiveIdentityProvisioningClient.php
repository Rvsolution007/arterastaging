<?php

namespace App\Services;

use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/** Delivers a strictly non-secret canonical identity/business snapshot to AdLive. */
class AdLiveIdentityProvisioningClient
{
    public function __construct(
        private AdLiveBusinessProfileService $profiles,
        private ArteraInternalRequestSigner $signer,
    ) {
    }

    public function isConfigured(): bool
    {
        return (string) config('adlive.identity_provision_url') !== ''
            && (string) config('adlive.shared_secret') !== '';
    }

    /**
     * Build the current source-of-truth snapshot at delivery time. The outbox
     * and queued job therefore contain IDs only, never email or password data.
     */
    public function sync(User $user, Business $business, string $signupSource): bool
    {
        $url = (string) config('adlive.identity_provision_url');
        $secret = (string) config('adlive.shared_secret');
        if ($url === '' || $secret === '') {
            $this->logFailure($user, $business, 'not_configured');

            return false;
        }

        try {
            $profile = $this->profiles->sharedSnapshot($user, $business);
            $payload = [
                'identity' => array_merge($profile['identity'], [
                    'email_verified' => (bool) $user->email_verified_at,
                    'signup_source' => $signupSource,
                    'consent_version' => (string) config('adlive.identity_consent_version'),
                    'business' => $profile['business'],
                ]),
            ];
            $body = $this->signer->encodePayload($payload);
            $headers = $this->signer->headers('POST', $url, $payload, $secret);
            $response = Http::acceptJson()
                ->timeout(max(1, (int) config('adlive.request_timeout_seconds')))
                ->withHeaders($headers)
                ->withBody($body, 'application/json')
                ->post($url);
        } catch (\Throwable) {
            $this->logFailure($user, $business, 'transport');

            return false;
        }

        if (! $response->successful()) {
            // Do not capture the response body; it could reflect request data.
            $this->logFailure($user, $business, 'http_'.$response->status());

            return false;
        }

        return true;
    }

    private function logFailure(User $user, Business $business, string $reason): void
    {
        Log::warning('AdLive identity provisioning delivery failed.', [
            'artera_user_id' => (string) $user->getKey(),
            'artera_business_id' => (string) $business->getKey(),
            'reason' => $reason,
        ]);
    }
}
