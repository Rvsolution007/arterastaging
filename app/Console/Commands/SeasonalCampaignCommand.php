<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Carbon\Carbon;

class SeasonalCampaignCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'artera:seasonal-campaign {date?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Trigger automated marketing campaigns based on seasonal events';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info("Checking Seasonal Campaigns...");
        
        $dateStr = $this->argument('date');
        $today = $dateStr ? Carbon::parse($dateStr) : now();
        $monthDay = $today->format('m-d');

        // Simple hardcoded festival calendar (In reality, this might be in a DB table for dynamic dates like Diwali)
        $festivals = [
            '10-28' => 'Diwali',
            '12-25' => 'Christmas',
            '03-14' => 'Holi',
            '01-01' => 'New Year'
        ];

        // Check if today is exactly 3 days before any festival
        $targetMonthDay = $today->copy()->addDays(3)->format('m-d');

        if (array_key_exists($targetMonthDay, $festivals)) {
            $festival = $festivals[$targetMonthDay];
            $this->info("Upcoming festival detected: $festival. Triggering Campaign!");

            // Normally we'd fetch all users and batch send push notifications
            // $users = User::all();
            
            $title = "Get ready for $festival! 🎉";
            $body = "Have you seen our new $festival design templates? Generate your festive posts today!";
            
            $this->info("Campaign Dispatched:");
            $this->info("Push Title: $title");
            $this->info("Push Body: $body");
            
            // FcmService::sendToTopic('all', $title, $body);
        } else {
            $this->info("No upcoming festivals in 3 days. Skipping.");
        }

        return 0;
    }
}
