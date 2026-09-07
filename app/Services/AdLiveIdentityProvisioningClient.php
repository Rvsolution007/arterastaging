<?php

namespace App\Services;

use App\Models\AdLiveIdentityEvent;
use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/** Delivers a signed, non-secret event envelope to AdLive. */
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

    /** Build the current canonical snapshot only when a worker is delivering. */
    public function deliver(AdLiveIdentityEvent $event): bool
    {
        $url = (string) config('adlive.identity_provision_url');
        $secret = (string) config('adlive.shared_secret');
        if ($url === '' || $secret === '') {
            $this->logFailure($event, 'not_configured');

            return false;
        }

        try {
            $user = User::query()->withTrashed()->whereKey($event->artera_user_id)->first([
                'id', 'name', 'email', 'mobile_no', 'status', 'registration_source',
                'email_verified_at', 'created_at', 'updated_at', 'deleted_at',
            ]);
            if (! $user || ($event->event_type !== 'identity.deleted' && (int) $user->status !== 1)) {
                $this->logFailure($event, 'source_not_available');

                return false;
            }

            $business = null;
            if ($event->artera_business_id !== null) {
                $business = Business::query()
                    ->whereKey($event->artera_business_id)
                    ->where('user_id', $user->id)
                    ->where('status', 1)
                    ->first();
                if (! $business) {
                    $this->logFailure($event, 'source_not_available');

                    return false;
                }
            }

            $identity = $this->profiles->canonicalIdentitySnapshot(
                $user,
                $business,
                $event->event_type !== 'identity.deleted'
                    && str_starts_with($event->event_type, 'identity.'),
            );
            $payload = [
                'event_id' => (string) $event->event_id,
                'event_type' => (string) $event->event_type,
                'occurred_at' => $event->occurred_at->utc()->toIso8601String(),
                'source' => 'artera_pixel',
                'identity' => $identity,
            ];
            $body = $this->signer->encodePayload($payload);
            $headers = $this->signer->headers('POST', $url, $payload, $secret);
            $response = Http::acceptJson()
                ->timeout(max(1, (int) config('adlive.request_timeout_seconds')))
                ->withHeaders($headers)
                ->withBody($body, 'application/json')
                ->post($url);
        } catch (\Throwable) {
            $this->logFailure($event, 'transport');

            return false;
        }

        if (! $response->successful()) {
            // A remote response body could reflect identity input; never log it.
            $this->logFailure($event, 'http_'.$response->status());

            return false;
        }

        return true;
    }

    private function logFailure(AdLiveIdentityEvent $event, string $reason): void
    {
        Log::warning('AdLive identity event delivery failed.', [
            'event_id' => (string) $event->event_id,
            'event_type' => (string) $event->event_type,
            'artera_user_id' => (string) $event->artera_user_id,
            'reason' => $reason,
        ]);
    }
}
