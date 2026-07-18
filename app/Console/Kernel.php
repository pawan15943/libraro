<?php

namespace App\Console;

use App\Jobs\DailyStatusUpdateJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
     protected $commands = [
        \App\Console\Commands\UpdateLearnerStatus::class, 
    ];
    protected function schedule(Schedule $schedule): void
    {
        //Learner status update
        $schedule->job(new DailyStatusUpdateJob())
             ->dailyAt('09:56')
             ->withoutOverlapping();
      
             //Library status update
         $schedule->command('library:daily-status')
        ->dailyAt('03:00')
        ->withoutOverlapping()
        ->runInBackground();
        

        // $schedule->command('notifications:update-status')->daily();
        // $schedule->command('data:update')->dailyAt('00:00');
        //  $schedule->command('learner:update-status')->dailyAt('06:00'); 
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
