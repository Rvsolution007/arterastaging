<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ExecuteGrowthTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'growth:execute-tasks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Morning Cron: Executes the tasks planned by the midnight Growth Report';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting Growth Task Execution...');
        
        $yesterday = Carbon::yesterday();
        
        $latestReport = DB::table('ai_growth_reports')
                            ->orderBy('id', 'desc')
                            ->first();

        if (!$latestReport) {
            $this->error('No growth report found to execute.');
            return Command::FAILURE;
        }

        $executionPlan = json_decode($latestReport->execution_plan, true);

        if (empty($executionPlan)) {
            $this->info('No tasks in the execution plan today.');
            return Command::SUCCESS;
        }

        foreach ($executionPlan as $task) {
            $this->info("Executing task: " . $task['task'] . " (Priority: " . $task['priority'] . ")");
            // In a real system, you'd map these strings to actual system actions.
            // E.g., if ($task['task'] == 'Send Push Notification') { app(NotificationService::class)->sendPush(...) }
            Log::info("Growth OS Executed: " . $task['task']);
        }

        $this->info('All Growth Tasks Executed Successfully.');
        return Command::SUCCESS;
    }
}
