<?php

namespace App\Console\Commands;

use App\Http\Controllers\LibraryController;
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
            // dataUpdateStatus() lives on LibraryController (it was looked up on LearnerController and failed)
            app(LibraryController::class)->dataUpdateStatus();
            $this->info('Data update completed!');
        } catch (\Exception $e) {
            $this->error('Error during data update: ' . $e->getMessage());
        }
    }
}
