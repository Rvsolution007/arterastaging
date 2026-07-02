<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Phase 8: SaaS Automation - Run Daily Drip Push Generator every morning at 9:00 AM
        $schedule->command('saas:daily-drip')->dailyAt('09:00')->runInBackground();
        
        // Task 9: Churn Prediction - Run Health Score calculation every night at 2:00 AM
        $schedule->command('artera:calculate-health')->dailyAt('02:00')->runInBackground();
        
        // Task 11: Customer Journey Automation - Run every night at 3:00 AM
        $schedule->command('artera:journey-automation')->dailyAt('03:00')->runInBackground();
        
        // Task 12: Dunning Sequence Processor - Run every morning at 9:00 AM
        $schedule->command('artera:dunning-process')->dailyAt('09:00')->runInBackground();
        
        // Task 13: Automated Feature Discovery - Run weekly
        $schedule->command('artera:feature-discovery')->weeklyOn(1, '10:00')->runInBackground();
        
        // Task 16: Dynamic Discounting for High Churn Risk - Run daily
        $schedule->command('artera:dynamic-discount')->dailyAt('11:00')->runInBackground();
        
        // Task 17: Quota Alerts for 90% Usage - Run hourly
        $schedule->command('artera:quota-alert')->hourly()->runInBackground();
        
        // Task 18: Winback Reactivation - Run daily
        $schedule->command('artera:winback')->dailyAt('12:00')->runInBackground();
        
        // Task: Process Payouts
        $schedule->command('artera:process-payouts')->dailyAt('00:00')->runInBackground();
        
        // Phase 5 Tasks
        $schedule->command('artera:ai-blog')->weeklyOn(2, '08:00')->runInBackground(); // Task 21
        $schedule->command('artera:social-post')->dailyAt('14:00')->runInBackground(); // Task 22
        $schedule->command('artera:lead-score')->dailyAt('02:00')->runInBackground(); // Task 23
        $schedule->command('artera:cold-outreach')->dailyAt('10:30')->runInBackground(); // Task 24
        $schedule->command('artera:seasonal-campaign')->dailyAt('09:00')->runInBackground(); // Task 25

        // Phase 7: Engagement & Gamification
        $schedule->command('push:smart-optimize')->dailyAt('01:00')->runInBackground(); // Task 33
        $schedule->command('emails:milestones')->dailyAt('08:00')->runInBackground(); // Task 35

        // Phase 8: Advanced Analytics & Monitoring
        $schedule->command('system:monitor')->everyFiveMinutes()->runInBackground(); // Task 36
        $schedule->command('ai:detect-anomalies')->hourly()->runInBackground(); // Task 37
        $schedule->command('competitor:track')->dailyAt('00:00')->runInBackground(); // Task 39

        // AI Error Analysis - Daily scan of unanalyzed client errors
        $schedule->command('artera:ai-error-scan --limit=30')->dailyAt('04:00')->runInBackground();

        // AI Growth OS - Midnight Analysis & Morning Execution
        $schedule->command('growth:generate-report')->dailyAt('00:00')->runInBackground();
        $schedule->command('growth:execute-tasks')->dailyAt('06:00')->runInBackground();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
