<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportStagingDb extends Command
{
    protected $signature = 'db:import-staging';
    protected $description = 'Imports arterastaging.sql into the current database';

    public function handle()
    {
        $path = base_path('arterastaging.sql');
        
        if (!File::exists($path)) {
            $this->error('arterastaging.sql file not found in root directory!');
            return Command::FAILURE;
        }

        $this->info('Wiping current database...');
        $this->call('db:wipe', ['--force' => true]);

        $this->info('Importing arterastaging.sql... (This might take a few seconds)');
        $sql = File::get($path);
        
        try {
            DB::unprepared($sql);
            $this->info('Database imported successfully!');
            
            $this->info('Running remaining migrations...');
            $this->call('migrate', ['--force' => true]);
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Import failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
