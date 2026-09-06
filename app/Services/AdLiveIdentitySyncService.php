<?php

namespace App\Services;

use App\Jobs\ProvisionAdLiveIdentity;
use App\Models\AdLiveIdentityProvisionOutbox;
use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/** Queues one current canonical identity snapshot delivery per active business. */
class AdLiveIdentitySyncService
{
    public function __construct(private AdLiveIdentityProvisioningClient $client)
    {
    }

    /**
     * @return array<int, int> durable outbox IDs, in callback delivery order
     */
    public function queueForUser(User $user): array
    {
        if (! $this->client->isConfigured() || ! $user->getKey() || (int) $user->status !== 1) {
            return [];
        }

        try {
            $businesses = Business::query()
                ->where('user_id', $user->getKey())
                ->where('status', 1)
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->get(['id', 'user_id', 'is_default']);
            $activeBusiness = $businesses->first();
            if (! $activeBusiness) {
                return [];
            }

            // Existing selection order chooses the first record above. Send it
            // last, after every other active business, so AdLive selects it.
            $orderedBusinesses = $businesses
                ->reject(fn (Business $business) => $business->id === $activeBusiness->id)
                ->sortBy('id')
                ->values()
                ->push($activeBusiness);
            $signupSource = $user->registration_source === 'adlive' ? 'adlive' : 'artera_pixel';
            $syncBatchId = (string) Str::uuid();

            $outboxIds = DB::transaction(function () use ($orderedBusinesses, $user, $signupSource, $syncBatchId): array {
                $ids = [];
                foreach ($orderedBusinesses as $deliveryOrder => $business) {
                    $event = AdLiveIdentityProvisionOutbox::create([
                        'artera_user_id' => $user->getKey(),
                        'artera_business_id' => $business->id,
                        'sync_batch_id' => $syncBatchId,
                        'delivery_order' => $deliveryOrder,
                        'signup_source' => $signupSource,
                    ]);
                    $ids[] = (int) $event->getKey();
                }

                return $ids;
            }, 3);

            foreach ($outboxIds as $outboxId) {
                ProvisionAdLiveIdentity::dispatch($outboxId);
            }

            return $outboxIds;
        } catch (\Throwable) {
            // Login/signup must succeed even if queue infrastructure is down.
            Log::warning('AdLive identity synchronization could not be queued.', [
                'artera_user_id' => (string) $user->getKey(),
            ]);

            return [];
        }
    }
}
