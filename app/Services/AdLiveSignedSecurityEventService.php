<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/** Sends session-revocation notices with the same HMAC scheme as all bridges. */
class AdLiveSignedSecurityEventService
{
    public function __construct(private ArteraInternalRequestSigner $signer)
    {
    }

    public function revokeLinkedSessions(User $user, string $reason): bool
    {
        $url = (string) config('adlive.security_revoke_url');
        $secret = (string) config('adlive.shared_secret');
        if ($url === '' || $secret === '') {
            return true;
        }

        try {
            $payload = [
                'artera_user_id' => (string) $user->id,
                'reason' => $reason,
            ];
            $body = $this->signer->encodePayload($payload);
            $headers = $this->signer->headers('POST', $url, $payload, $secret);
            $response = Http::acceptJson()
                ->timeout(max(1, (int) config('adlive.request_timeout_seconds')))
                ->withHeaders($headers)
                ->withBody($body, 'application/json')
                ->post($url);
        } catch (\Throwable) {
            Log::warning('AdLive linked-session revocation could not reach AdLive.', [
                'artera_user_id' => (string) $user->id,
                'reason' => $reason,
            ]);

            return false;
        }

        if (! $response->successful()) {
            Log::warning('AdLive linked-session revocation was rejected.', [
                'artera_user_id' => (string) $user->id,
                'reason' => $reason,
                'status' => $response->status(),
            ]);

            return false;
        }

        return true;
    }
}
