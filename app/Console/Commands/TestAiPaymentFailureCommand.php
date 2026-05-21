<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Events\PaymentFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;

class TestAiPaymentFailureCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'artera:test-ai-payment-failure {email} {reason=insufficient_funds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simulate a webhook hitting the server with a specific bank decline code to test AI email generation';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $email = $this->argument('email');
        $reason = $this->argument('reason');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User not found with email: {$email}");
            return 1;
        }

        $this->info("Simulating Stripe Webhook failure for {$user->name}...");
        $this->info("Decline Reason Code: {$reason}");

        $subscription = current($user->active_subscription) ? current($user->active_subscription) : (object)['plan_name' => 'Premium Plan'];

        // Directly fire the event just like the Webhook controller would
        event(new PaymentFailed($user, $subscription, $reason));

        $this->info("Event fired. Check the log and Mailtrap to see the AI-generated email!");
        return 0;
    }
}
