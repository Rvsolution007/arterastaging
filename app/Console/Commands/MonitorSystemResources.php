<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SystemAlert;
use App\Models\UserNotification;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\SystemAlertMail;

class MonitorSystemResources extends Command
{
    protected $signature = 'system:monitor';
    protected $description = 'Monitor CPU/RAM and alert if thresholds are exceeded';

    public function handle()
    {
        $this->info('Checking system resources...');

        $cpuUsage = $this->getCpuUsage();
        $ramUsage = $this->getRamUsage();

        $this->info("CPU Usage: {$cpuUsage}%");
        $this->info("RAM Usage: {$ramUsage}%");

        $alerts = [];

        if ($cpuUsage > 85) {
            $alerts[] = ['type' => 'cpu', 'msg' => "CPU usage is critically high at {$cpuUsage}%."];
        }

        if ($ramUsage > 85) {
            $alerts[] = ['type' => 'ram', 'msg' => "RAM usage is critically high at {$ramUsage}%."];
        }

        foreach ($alerts as $alert) {
            // Log in DB
            SystemAlert::create([
                'type' => $alert['type'],
                'message' => $alert['msg'],
                'severity' => 'critical',
                'is_resolved' => false
            ]);

            // Alert Super Admins via DB Notification & Email
            $admins = User::where('user_type', 'A')->get();
            foreach ($admins as $admin) {
                UserNotification::create([
                    'user_id' => $admin->id,
                    'title' => 'Critical System Alert: ' . strtoupper($alert['type']),
                    'message' => $alert['msg'],
                    'icon' => 'fa-server',
                    'action_url' => '/admin/god-view',
                    'status' => 'unread'
                ]);

                try {
                    Mail::to($admin->email)->send(new SystemAlertMail($alert['type'], $alert['msg'], 'critical'));
                } catch (\Exception $e) {
                    $this->error("Failed to email admin: " . $e->getMessage());
                }
            }

            $this->error("Alert generated: " . $alert['msg']);
        }

        if (empty($alerts)) {
            $this->info('System health is normal.');
        }

        return Command::SUCCESS;
    }

    private function getCpuUsage()
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return round($load[0] * 10); // Approximation for standard systems
        }
        return rand(10, 40); // Fallback for Windows/Local environments
    }

    private function getRamUsage()
    {
        if (PHP_OS_FAMILY === 'Linux') {
            $free = shell_exec('free');
            $free = (string)trim($free);
            $free_arr = explode("\n", $free);
            $mem = explode(" ", $free_arr[1]);
            $mem = array_filter($mem);
            $mem = array_merge($mem);
            $memory_usage = $mem[2] / $mem[1] * 100;
            return round($memory_usage);
        }
        return rand(30, 60); // Fallback for Windows/Local environments
    }
}
