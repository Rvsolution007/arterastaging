<?php

namespace App\Services;

use App\Jobs\DeliverAdLiveIdentityEvent;
use App\Models\AdLiveIdentityEvent;
use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/** Writes one durable event for a completed, explicit Pixel action. */
class AdLiveIdentitySyncService
{
    /** @return array<int, int> */
    public function queueForUser(User $user, string $eventType = 'identity.updated'): array
    {
        return $this->queue($user, $eventType, null);
    }

    /** @return array<int, int> */
    public function queueBusiness(User $user, Business $business, string $eventType): array
    {
        return $this->queue($user, $eventType, $business);
    }

    /** @return array<int, int> */
    public function queueDeletion(User $user): array
    {
        return $this->queue($user, 'identity.deleted', null);
    }

    /** @return array<int, int> */
    private function queue(User $user, string $eventType, ?Business $business): array
    {
        $allowed = [
            'identity.created',
            'identity.updated',
            'identity.deleted',
            'business.created',
            'business.updated',
        ];
        if (! in_array($eventType, $allowed, true) || ! $user->getKey()) {
            return [];
        }
        if (str_starts_with($eventType, 'business.') && (! $business || (string) $business->user_id !== (string) $user->getKey())) {
            return [];
        }

        try {
            $event = DB::transaction(function () use ($user, $eventType, $business): AdLiveIdentityEvent {
                return AdLiveIdentityEvent::create([
                    'event_id' => (string) Str::uuid(),
                    'event_type' => $eventType,
                    'artera_user_id' => $user->getKey(),
                    'artera_business_id' => $business?->getKey(),
                    'occurred_at' => now()->utc(),
                ]);
            }, 3);

            $dispatch = fn () => DeliverAdLiveIdentityEvent::dispatch((int) $event->getKey());
            // A caller that already owns a save transaction must not publish
            // before its business/user writes commit. The outbox row rolls
            // back with that transaction and dispatch runs only afterwards.
            if (DB::transactionLevel() > 0) {
                DB::afterCommit($dispatch);
            } else {
                $dispatch();
            }

            return [(int) $event->getKey()];
        } catch (\Throwable) {
            // Do not make a completed signup/login/save fail due to temporary
            // queue infrastructure trouble. No request values are logged.
            Log::warning('AdLive identity event could not be queued.', [
                'artera_user_id' => (string) $user->getKey(),
                'event_type' => $eventType,
            ]);

            return [];
        }
    }
}
