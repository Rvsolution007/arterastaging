<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ProcessPartnerPayouts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'artera:process-payouts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process partner payouts by clearing held earnings after the refund hold period and creating withdraw requests.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info("Starting Process Partner Payouts...");

        $holdDays = \App\Models\ReferralSystem::getReferralSystem('partner_refund_hold_days') ?? 7;
        $withdrawalLimit = \App\Models\ReferralSystem::getReferralSystem('withdrawal_limit') ?? 500;

        $targetDate = now()->subDays($holdDays);

        // Find earnings that are past the hold period
        $pendingEarnings = \App\Models\EarningHistory::where('status', 'pending')
                                ->where('created_at', '<=', $targetDate)
                                ->get();

        $usersUpdated = [];

        foreach ($pendingEarnings as $earning) {
            $earning->status = 'available';
            $earning->save();

            $user = \App\Models\User::find($earning->user_id);
            if ($user) {
                // Add to current balance
                $user->current_balance += $earning->amount;
                $user->total_balance += $earning->amount;
                $user->save();
                
                $usersUpdated[$user->id] = $user;
            }
        }

        $this->info("Cleared " . count($pendingEarnings) . " held earnings.");

        // Check if any updated user exceeded the limit, create withdraw request
        $requestsCreated = 0;
        foreach ($usersUpdated as $user) {
            if ($user->current_balance >= $withdrawalLimit) {
                // Check if they already have a pending withdraw request
                $hasPending = \App\Models\WithdrawRequest::where('user_id', $user->id)
                                    ->where('status', 0) // 0 = Pending in this system
                                    ->exists();
                
                if (!$hasPending) {
                    \App\Models\WithdrawRequest::create([
                        'user_id' => $user->id,
                        'withdraw_amount' => $user->current_balance,
                        'status' => 0,
                        // upi_id can be left blank or filled from user profile later
                        'upi_id' => 'Auto-Generated',
                    ]);

                    // Deduct from current balance so it doesn't get processed again
                    $user->current_balance = 0;
                    $user->save();

                    $requestsCreated++;
                }
            }
        }

        $this->info("Created $requestsCreated manual withdraw requests.");
        return 0;
    }
}
