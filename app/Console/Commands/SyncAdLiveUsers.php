<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Models\User;
use App\Services\AdLiveUserProvisioningClient;
use Illuminate\Console\Command;

/** Backfills existing active Pixel users into AdLive's User directory. */
class SyncAdLiveUsers extends Command
{
    protected $signature = 'adlive:sync-artera-users {--user-id= : Sync one Pixel user only} {--dry-run : Report eligible records without sending them}';

    protected $description = 'Synchronize active Artera Pixel users and their default business profiles to AdLive';

    public function handle(AdLiveUserProvisioningClient $provisioning): int
    {
        $query = User::query()->where('status', 1)->orderBy('id');
        if ($userId = $this->option('user-id')) {
            $query->whereKey($userId);
        }

        $counts = ['eligible' => 0, 'synced' => 0, 'skipped' => 0, 'failed' => 0];
        $dryRun = (bool) $this->option('dry-run');

        $query->chunkById(100, function ($users) use ($provisioning, $dryRun, &$counts): void {
            foreach ($users as $user) {
                $business = Business::query()
                    ->where('user_id', $user->id)
                    ->where('status', 1)
                    ->orderByDesc('is_default')
                    ->orderBy('id')
                    ->first();

                $counts['eligible']++;
                if ($dryRun) {
                    continue;
                }

                $source = $user->registration_source === 'adlive' ? 'adlive' : 'artera_pixel';
                if ($provisioning->sync($user, $business, $source)) {
                    $counts['synced']++;
                } else {
                    $counts['failed']++;
                }
            }
        });

        $this->info(sprintf(
            'AdLive user sync: %d eligible, %d synced, %d skipped, %d failed%s.',
            $counts['eligible'],
            $counts['synced'],
            $counts['skipped'],
            $counts['failed'],
            $dryRun ? ' (dry run)' : '',
        ));

        return $counts['failed'] === 0 ? self::SUCCESS : self::FAILURE;
    }
}
