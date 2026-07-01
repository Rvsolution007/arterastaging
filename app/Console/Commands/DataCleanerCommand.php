<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DataCleanerService;

class DataCleanerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs the Antigravity Data Pipeline to clean, normalize, and validate the business taxonomy.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(DataCleanerService $cleanerService)
    {
        $this->info('Starting Antigravity Data Pipeline...');
        
        $this->info('Step 2: Normalizing Names...');
        $cleanerService->normalizeNames();
        
        $this->info('Step 3 & 8: Detecting & Merging Duplicates...');
        $cleanerService->detectDuplicatesAndMerge();
        
        $this->info('Step 4: Validating Hierarchy...');
        $cleanerService->validateHierarchy();
        
        $this->info('Step 5: Mapping Business Types...');
        $cleanerService->mapBusinessTypes();
        
        $this->info('Step 7: Mapping Brands...');
        $cleanerService->mapBrands();
        
        $this->info('Pipeline completed successfully! All records have been cleaned and merged.');
        
        return Command::SUCCESS;
    }
}
