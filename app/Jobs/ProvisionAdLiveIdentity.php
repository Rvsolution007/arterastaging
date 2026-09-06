<?php

namespace App\Jobs;

use App\Models\AdLiveIdentityProvisionOutbox;
use App\Models\Business;
use App\Models\User;
use App\Services\AdLiveIdentityProvisioningClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/**
 * Queue payload holds only an outbox primary key. The canonical profile is
 * rebuilt immediately before the HTTP call, so passwords and PII are not
 * serialized into Redis/database queue payloads.
 */
class ProvisionAdLiveIdentity implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 5;

    /** @var array<int, int> */
    public array $backoff = [30, 120, 600, 1800];

    public function __construct(public int $outboxId)
    {
    }

    public function handle(AdLiveIdentityProvisioningClient $client): void
    {
        $outbox = DB::transaction(function (): ?AdLiveIdentityProvisionOutbox {
            $event = AdLiveIdentityProvisionOutbox::query()
                ->lockForUpdate()
                ->find($this->outboxId);
            if (! $event || $event->sent_at
                || $event->delivery_attempts >= max(1, (int) config('adlive.identity_provision_max_attempts', 5))
                || ($event->processing_at && $event->processing_at->greaterThan(now()->subMinutes(10)))) {
                return null;
            }

            // One login may emit several businesses. Hold the selected/active
            // business until all prior records in this batch have succeeded.
            // This remains correct with multiple queue workers.
            if ($event->delivery_order > 0
                && AdLiveIdentityProvisionOutbox::query()
                    ->where('sync_batch_id', $event->sync_batch_id)
                    ->where('delivery_order', '<', $event->delivery_order)
                    ->whereNull('sent_at')
                    ->exists()) {
                return null;
            }

            $event->increment('delivery_attempts');
            $event->forceFill(['processing_at' => now()])->save();

            return $event->fresh();
        }, 3);
        if (! $outbox) {
            return;
        }

        try {
            // Select only fields needed for the permitted identity profile. In
            // particular, the password hash is not even loaded by this job.
            $user = User::query()
                ->whereKey($outbox->artera_user_id)
                ->where('status', 1)
                ->first(['id', 'name', 'email', 'mobile_no', 'registration_source', 'email_verified_at', 'updated_at']);
            $business = Business::query()
                ->whereKey($outbox->artera_business_id)
                ->where('user_id', $outbox->artera_user_id)
                ->where('status', 1)
                ->first(['id', 'user_id', 'name', 'address', 'business_category_id', 'status', 'is_default', 'profile_version', 'updated_at']);

            if (! $user || ! $business) {
                $outbox->forceFill([
                    'last_failure' => 'source_not_available',
                    'processing_at' => null,
                ])->save();

                return;
            }

            if ($client->sync($user, $business, (string) $outbox->signup_source)) {
                $outbox->forceFill([
                    'sent_at' => now(),
                    'last_failure' => null,
                    'processing_at' => null,
                ])->save();

                return;
            }

            // This exact generic message is the only queue failure text. It
            // has no request body, password, secret, email, or remote body.
            throw new \RuntimeException('AdLive identity delivery attempt failed.');
        } catch (\Throwable $exception) {
            $outbox->forceFill([
                'last_failure' => 'delivery_failed',
                'processing_at' => null,
            ])->save();

            throw $exception;
        }
    }

    public function failed(\Throwable $exception): void
    {
        // Laravel may call this after the final retry. Never report or inspect
        // the exception because queue infrastructure could attach context.
        AdLiveIdentityProvisionOutbox::query()
            ->whereKey($this->outboxId)
            ->whereNull('sent_at')
            ->update([
                'last_failure' => 'delivery_failed',
                'processing_at' => null,
                'updated_at' => now(),
            ]);
    }
}
