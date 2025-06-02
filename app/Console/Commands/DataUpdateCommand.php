<?php

namespace App\Console\Commands;

use App\Http\Controllers\LearnerController;
use Illuminate\Console\Command;

class DataUpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:update';

   
    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run the dataUpdateStatus method';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $learnerController = app(LearnerController::class); // Resolves the controller with dependencies
            $learnerController->dataUpdateStatus();
            $this->info('Data update completed!');
        } catch (\Exception $e) {
            $this->error('Error during data update: ' . $e->getMessage());
        }
    }
}
