<?php

namespace App\Services;

use App\Models\Business;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/** Sends a non-secret identity snapshot to AdLive when an Artera user is created. */
class AdLiveUserProvisioningClient
{
    public function sync(User $user, ?Business $business, string $signupSource = 'artera_pixel'): bool
    {
        $url = (string) config('adlive.user_provision_url');
        $secret = (string) config('adlive.shared_secret');
        if ($url === '' || $secret === '') {
            Log::warning('Skipped AdLive user provisioning because the bridge is not configured.', ['user_id' => $user->id]);
            return false;
        }

        $profiles = app(AdLiveBusinessProfileService::class);
        $identity = array_merge($profiles->identity($user), [
            'signup_source' => $signupSource,
            'consent_version' => 'shared-identity-v1',
            'email_verified' => (bool) $user->email_verified_at,
        ]);
        if ($business) {
            $identity['business'] = $profiles->snapshot($user, $business)['business'];
        }
        $payload = ['identity' => $identity];

        try {
            $signer = app(ArteraInternalRequestSigner::class);
            $body = $signer->encodePayload($payload);
            $headers = $signer->headers('POST', $url, $payload, $secret);
            $response = Http::acceptJson()
                ->timeout(max(1, (int) config('adlive.request_timeout_seconds')))
                ->withHeaders($headers)
                ->withBody($body, 'application/json')
                ->post($url);
        } catch (ConnectionException $exception) {
            report($exception);
            Log::warning('AdLive user provisioning could not reach AdLive.', ['user_id' => $user->id]);
            return false;
        }

        if (! $response->successful()) {
            Log::warning('AdLive user provisioning was rejected.', [
                'user_id' => $user->id,
                'status' => $response->status(),
            ]);
            return false;
        }

        return true;
    }
}
