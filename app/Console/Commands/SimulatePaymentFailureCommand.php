<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Events\PaymentFailed;

class SimulatePaymentFailureCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'artera:test-payment-failed {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simulate a payment failure event to test the Dunning automation flow';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $email = $this->argument('email');
        
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User not found with email: {$email}");
            return 1;
        }

        $subscription = current($user->active_subscription) ? current($user->active_subscription) : (object)['plan_name' => 'Premium Plan'];

        $this->info("Simulating payment failure for user: {$user->name}...");
        
        // Dispatch the event
        event(new PaymentFailed($user, $subscription));
        
        $this->info("PaymentFailed event dispatched successfully! Check logs, mail, and FCM.");
        
        return 0;
    }
}
