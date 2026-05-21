<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\SystemAlert;
use App\Models\UserNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\SystemAlertMail;
use Illuminate\Support\Str;

class DetectAiAnomalies extends Command
{
    protected $signature = 'ai:detect-anomalies';
    protected $description = 'Detect anomalous AI token usage and secure the API';

    public function handle()
    {
        $this->info('Starting AI Anomaly Detection...');

        // Check ai_monitor_logs for the last hour
        $lastHourStart = now()->subHour();
        
        $usageStats = DB::table('ai_monitor_logs')
            ->select('user_id', DB::raw('count(*) as request_count'))
            ->where('created_at', '>=', $lastHourStart)
            ->groupBy('user_id')
            ->get();

        foreach ($usageStats as $stat) {
            $user = User::find($stat->user_id);
            if (!$user) continue;

            // Simple threshold: If a user makes > 50 requests in an hour
            if ($stat->request_count > 50) {
                $msg = "User {$user->email} (ID: {$user->id}) made {$stat->request_count} AI requests in the last hour. This is extremely high and indicates potential token abuse or a bot attack.";
                
                // Security action: Revoke API token and block account
                $user->api_token = Str::random(60);
                $user->status = 0; // Suspend account
                $user->save();

                $msg .= " SECURITY ACTION TAKEN: The user's API token was revoked and the account has been temporarily suspended.";

                // Log Alert
                SystemAlert::create([
                    'type' => 'security',
                    'message' => $msg,
                    'severity' => 'critical',
                    'is_resolved' => false
                ]);

                // Notify Admins
                $admins = User::where('user_type', 'A')->get();
                foreach ($admins as $admin) {
                    UserNotification::create([
                        'user_id' => $admin->id,
                        'title' => 'Critical Security: AI Token Abuse Detected',
                        'message' => $msg,
                        'icon' => 'fa-shield-alt',
                        'action_url' => '/admin/god-view',
                        'status' => 'unread'
                    ]);

                    try {
                        Mail::to($admin->email)->send(new SystemAlertMail('security', $msg, 'critical'));
                    } catch (\Exception $e) {
                        // ignore
                    }
                }

                $this->error("Anomaly detected for user {$user->id}!");
            }
        }

        $this->info('AI Anomaly Detection completed.');
        return Command::SUCCESS;
    }
}
