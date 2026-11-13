<?php

namespace App\Console\Commands;

use App\Services\Crm\CrmSyncService;
use Illuminate\Console\Command;

class CrmSyncLibrariesCommand extends Command
{
    protected $signature = 'crm:sync-libraries';

    protected $description = 'Calling CRM: add libraries that registered but did not pay as leads, and close leads whose library paid';

    public function handle(CrmSyncService $sync): int
    {
        $r = $sync->sync();
        $this->info("Added {$r['added']} leads, marked {$r['enrolled']} as enrolled.");

        return self::SUCCESS;
    }
}
