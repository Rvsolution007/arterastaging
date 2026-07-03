<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Festivals;
use App\Models\ContentPlan;
use App\Models\ProductCategory;
use App\Models\AiPushNotification;
use Carbon\Carbon;

class SeedGrowthOs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'growthos:seed';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seeds the Growth OS Planner and Marketing AI with upcoming festivals and categories data.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting Growth OS Seeding...');

        // Clear existing data (optional, but good for resetting)
        ContentPlan::truncate();
        AiPushNotification::truncate();

        // 1. Seed Upcoming Festivals
        $festivals = Festivals::orderBy('festivals_date', 'asc')
            ->whereDate('festivals_date', '>=', now()->subDays(30))
            ->take(4)
            ->get();

        foreach($festivals as $fest) {
            ContentPlan::create([
                'plan_date' => $fest->festivals_date, 
                'content_type' => 'Festival', 
                'target_id' => $fest->id, 
                'target_name' => $fest->title, 
                'suggested_templates' => rand(8, 15), 
                'status' => 'draft', 
                'opportunity_score' => rand(75, 95)
            ]);
        }

        // 2. Seed Categories
        $categories = ProductCategory::where('status', 1)->limit(3)->get();
        foreach($categories as $cat) {
            ContentPlan::create([
                'plan_date' => now()->addDays(rand(1, 5))->format('Y-m-d'), 
                'content_type' => 'Category', 
                'target_id' => $cat->id, 
                'target_name' => $cat->name, 
                'suggested_templates' => rand(5, 12), 
                'status' => 'pending', 
                'opportunity_score' => rand(65, 88)
            ]);
        }

        // 3. Seed Marketing AI Push Notifications
        $festName = $festivals->count() > 0 ? $festivals[0]->title : 'Festival';
        $festDate = $festivals->count() > 0 ? $festivals[0]->festivals_date : now()->addDays(1)->format('Y-m-d H:i:s');

        AiPushNotification::create([
            'target_type' => 'All Users', 
            'target_id' => 0, 
            'title' => 'Happy ' . $festName . '! 🎉', 
            'body' => 'Create stunning posts for your business today! Tap here to explore 20+ new templates.', 
            'status' => 'draft', 
            'scheduled_for' => $festDate, 
            'predicted_ctr' => rand(8, 15) . '.' . rand(1, 9)
        ]);

        $catName = $categories->count() > 0 ? $categories[0]->name : 'Your Business';
        AiPushNotification::create([
            'target_type' => 'Category Users', 
            'target_id' => ($categories[0]->id ?? 1), 
            'title' => 'New Templates for ' . $catName, 
            'body' => 'We just added fresh designs that fit your brand perfectly. Check them out!', 
            'status' => 'scheduled', 
            'scheduled_for' => now()->addDays(2)->format('Y-m-d H:i:s'), 
            'predicted_ctr' => rand(12, 18) . '.' . rand(1, 9)
        ]);

        AiPushNotification::create([
            'target_type' => 'Inactive Users', 
            'target_id' => 0, 
            'title' => 'We Miss You! 😢', 
            'body' => 'Get 50% off on your next premium download. Offer valid for 24 hours only.', 
            'status' => 'pending', 
            'scheduled_for' => now()->addDays(1)->format('Y-m-d H:i:s'), 
            'predicted_ctr' => rand(18, 25) . '.' . rand(1, 9)
        ]);

        $this->info('Growth OS Smart Planner and Marketing AI successfully seeded with live data!');
        return 0;
    }
}
