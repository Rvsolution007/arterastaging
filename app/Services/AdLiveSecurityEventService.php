<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdLiveSecurityEventService
{
    /**
     * Revoke every AdLive session for this linked identity. A missing bridge
     * configuration is harmless before AdLive SSO is enabled; a configured
     * bridge must acknowledge the revocation before Artera changes a password.
     */
    public function revokeLinkedSessions(User $user, string $reason): bool
    {
        $url = (string) config('adlive.security_revoke_url');
        $secret = (string) config('adlive.shared_secret');

        if ($url === '' || $secret === '') {
            return true;
        }

        try {
            $response = Http::acceptJson()
                ->asJson()
                ->timeout(max(1, (int) config('adlive.request_timeout_seconds')))
                ->withHeaders(['X-Artera-AdLive-Secret' => $secret])
                ->post($url, [
                    'artera_user_id' => (string) $user->id,
                    'reason' => $reason,
                ]);
        } catch (ConnectionException $exception) {
            report($exception);
            Log::error('AdLive linked-session revocation could not reach AdLive.', [
                'artera_user_id' => $user->id,
                'reason' => $reason,
            ]);

            return false;
        }

        if (! $response->successful()) {
            Log::error('AdLive linked-session revocation was rejected.', [
                'artera_user_id' => $user->id,
                'reason' => $reason,
                'status' => $response->status(),
            ]);

            return false;
        }

        return true;
    }
}
