<?php

namespace App\Console\Commands;

use App\Services\PlayStoreReviewSyncService;
use Illuminate\Console\Command;
use Throwable;

class SyncPlayStoreReviews extends Command
{
    protected $signature = 'growth:sync-play-store-reviews';
    protected $description = 'Sync Google Play reviews for the Growth OS dashboard.';

    public function handle(PlayStoreReviewSyncService $syncService): int
    {
        try {
            $count = $syncService->sync();
            $this->info("Synced {$count} Google Play reviews.");
            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());
            report($exception);
            return self::FAILURE;
        }
    }
}
