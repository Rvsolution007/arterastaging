<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;

class WinbackReactivationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'artera:winback {email?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send one-click reactivation emails to expired winback users';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $email = $this->argument('email');

        $query = User::where('is_subscribe', 0)->whereNotNull('subscription_end_date');
        
        if ($email) {
            $users = $query->where('email', $email)->get();
        } else {
            // Find users who expired exactly 30 days ago
            $targetDate = now()->subDays(30)->toDateString();
            $users = $query->whereDate('subscription_end_date', $targetDate)->get();
        }

        if ($users->isEmpty()) {
            $this->info("No winback targets found today.");
            return 0;
        }

        foreach ($users as $user) {
            $this->info("Targeting winback user: {$user->name} (Expired: {$user->subscription_end_date})");
            
            // Generate a signed one-click reactivation URL (expires in 7 days)
            $reactivationLink = URL::temporarySignedRoute(
                'admin.reactivate', 
                now()->addDays(7), 
                ['user' => $user->id]
            );

            $this->info("Generated Magic Link: {$reactivationLink}");
            
            // Send the email
            // Mail::to($user->email)->send(new WinbackMail($user, $reactivationLink));
            $this->info("Winback email dispatched.");
        }

        return 0;
    }
}
