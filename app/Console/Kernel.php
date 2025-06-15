<?php

namespace App\Console;

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
        $schedule->command('notifications:update-status')->daily();
        $schedule->command('data:update')->dailyAt('00:00');
         $schedule->command('learner:update-status')->dailyAt('06:00'); // 6 AM daily
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
