<?php

namespace App\Console\Commands;

use App\Services\LibraryService;
use Illuminate\Console\Command;
use Log;

class LibraryStatusUpdateCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'library:daily-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run daily library subscription status update';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        \Log::info('Library Daily Status Cron Ran at: ' . now());
        $this->info('Library daily status update started.');

        app(LibraryService::class)->runDailyUpdate();

        $this->info('Library daily status update completed.');
    }
}
