<?php

namespace App\Jobs;

use App\Models\AdLiveIdentityEvent;
use App\Services\AdLiveIdentityProvisioningClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/** Delivers one explicit-action event using an ID-only queue payload. */
class DeliverAdLiveIdentityEvent implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 600, 1800];

    public function __construct(public int $eventId)
    {
    }

    public function handle(AdLiveIdentityProvisioningClient $client): void
    {
        $event = DB::transaction(function (): ?AdLiveIdentityEvent {
            $event = AdLiveIdentityEvent::query()->lockForUpdate()->find($this->eventId);
            if (! $event || $event->sent_at
                || $event->delivery_attempts >= max(1, (int) config('adlive.identity_provision_max_attempts', 5))
                || ($event->processing_at && $event->processing_at->greaterThan(now()->subMinutes(10)))) {
                return null;
            }

            $event->increment('delivery_attempts');
            $event->forceFill(['processing_at' => now()])->save();

            return $event->fresh();
        }, 3);

        if (! $event) {
            return;
        }

        try {
            if ($client->deliver($event)) {
                $event->forceFill([
                    'sent_at' => now(),
                    'processing_at' => null,
                    'last_failure' => null,
                ])->save();

                return;
            }

            throw new \RuntimeException('AdLive identity event delivery failed.');
        } catch (\Throwable $exception) {
            $event->forceFill([
                'processing_at' => null,
                'last_failure' => 'delivery_failed',
            ])->save();

            throw $exception;
        }
    }

    public function failed(\Throwable $exception): void
    {
        AdLiveIdentityEvent::query()
            ->whereKey($this->eventId)
            ->whereNull('sent_at')
            ->update([
                'last_failure' => 'delivery_failed',
                'processing_at' => null,
                'updated_at' => now(),
            ]);
    }
}
