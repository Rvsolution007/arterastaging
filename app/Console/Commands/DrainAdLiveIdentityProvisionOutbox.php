<?php

namespace App\Console\Commands;

use App\Jobs\ProvisionAdLiveIdentity;
use App\Models\AdLiveIdentityProvisionOutbox;
use Illuminate\Console\Command;

/** Re-dispatches safely claimable identity-delivery outbox records. */
class DrainAdLiveIdentityProvisionOutbox extends Command
{
    protected $signature = 'adlive:drain-identity-outbox {--limit=100 : Maximum records to dispatch}';

    protected $description = 'Dispatch pending Artera-to-AdLive identity provision records';

    public function handle(): int
    {
        $limit = min(500, max(1, (int) $this->option('limit')));
        $maxAttempts = max(1, (int) config('adlive.identity_provision_max_attempts', 5));
        $staleBefore = now()->subMinutes(10);

        $ids = AdLiveIdentityProvisionOutbox::query()
            ->whereNull('sent_at')
            ->where('delivery_attempts', '<', $maxAttempts)
            ->where(function ($query) use ($staleBefore): void {
                $query->whereNull('processing_at')
                    ->orWhere('processing_at', '<', $staleBefore);
            })
            ->orderBy('id')
            ->limit($limit)
            ->pluck('id');

        foreach ($ids as $id) {
            ProvisionAdLiveIdentity::dispatch((int) $id);
        }

        $this->info("Dispatched {$ids->count()} AdLive identity outbox record(s).");

        return self::SUCCESS;
    }
}
