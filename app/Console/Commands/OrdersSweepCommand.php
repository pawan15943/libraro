<?php

namespace App\Console\Commands;

use App\Services\Ordering\OrderSweepService;
use Illuminate\Console\Command;

/** Ordering & Services: staff reminders, escalation and replacement timeouts (docs/ORDERING.md). */
class OrdersSweepCommand extends Command
{
    protected $signature = 'orders:sweep {--branch= : Only this branch id}';

    protected $description = 'Remind / escalate unattended orders and apply replacement timeouts';

    public function handle(OrderSweepService $sweep): int
    {
        $done = $sweep->sweep($this->option('branch') ? (int) $this->option('branch') : null);
        // heartbeat: Order Settings warns when the server cron is not running this every minute
        try {
            \Illuminate\Support\Facades\Cache::put(OrderSweepService::CRON_HEARTBEAT, now()->timestamp, now()->addDay());
        } catch (\Throwable $e) {
        }
        $this->info("Reminded {$done['reminded']}, escalated {$done['escalated']}, replacement timeouts {$done['expired']}.");

        return self::SUCCESS;
    }
}
