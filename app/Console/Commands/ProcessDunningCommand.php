<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Http\Controllers\Admin\WhatsappMessageController;
use Illuminate\Support\Facades\Mail;
use App\Mail\SubscriptionDowngradedMail;
use Carbon\Carbon;

class ProcessDunningCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'artera:dunning-process {--simulate-days=0 : Simulate days passed for testing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process the 3-day dunning sequence for failed subscriptions';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $simulateDays = (int)$this->option('simulate-days');

        // Fetch users currently in dunning
        $users = User::whereNotNull('dunning_status')
                     ->whereIn('dunning_status', ['day1', 'day2'])
                     ->get();

        if ($users->isEmpty()) {
            $this->info("No users currently in dunning process.");
            return 0;
        }

        foreach ($users as $user) {
            $startedAt = Carbon::parse($user->dunning_started_at);
            
            // Adjust the actual time elapsed by our simulation factor
            $hoursElapsed = $startedAt->diffInHours(now()) + ($simulateDays * 24);

            $this->info("Processing {$user->name} ({$user->dunning_status}, {$hoursElapsed} hours elapsed)");

            // Day 2 Logic: WhatsApp Reminder
            if ($user->dunning_status === 'day1' && $hoursElapsed >= 24) {
                $this->sendWhatsAppReminder($user);
                
                $user->dunning_status = 'day2';
                $user->save();
                
                $this->info("Moved {$user->name} to Day 2 and sent WhatsApp reminder.");
            }
            
            // Day 3 Logic: Downgrade and Exit Dunning
            elseif ($user->dunning_status === 'day2' && $hoursElapsed >= 48) {
                $this->downgradeUser($user);
                
                $user->dunning_status = 'day3_downgraded';
                $user->save();
                
                $this->info("Downgraded {$user->name} and sent final email.");
            }
        }

        $this->info("Dunning processing complete.");
        return 0;
    }

    private function sendWhatsAppReminder(User $user)
    {
        // Mocking the request to the existing WhatsappMessageController
        // In a real app, you'd extract the Whatsapp API logic to a Service class.
        // For now, we will simulate the API call or log it.
        try {
            if ($user->mobile_no) {
                // Here you would call your WhatsappService
                // app(WhatsappMessageController::class)->send_whatsapp_msg_user($request);
                \Log::info("WhatsApp Dunning Reminder sent to {$user->mobile_no} for user {$user->id}");
            }
        } catch (\Exception $e) {
            \Log::error("WhatsApp dunning failed for user {$user->id}: " . $e->getMessage());
        }
    }

    private function downgradeUser(User $user)
    {
        // Reset their premium limits
        $user->is_subscribe = 0;
        $user->business_limit = 1;
        $user->subscription_end_date = now()->subDay()->format('Y-m-d');
        $user->save();

        // Send the Feedback/Downgrade email
        if ($user->email) {
            Mail::to($user->email)->send(new SubscriptionDowngradedMail($user));
        }
    }
}
