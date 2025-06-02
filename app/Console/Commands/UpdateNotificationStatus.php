<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class UpdateNotificationStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-notification-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update status of notifications that have expired';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Update notifications where the expiry date matches the current date
        DB::table('notifications')
            ->whereDate('end_date', now()->toDateString()) // Using `now()->toDateString()` to check only the date part
            ->update(['status' => 0]);

        $this->info('Notification statuses updated successfully.');

        return 0;
    }
}
