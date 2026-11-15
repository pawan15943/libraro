<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Every portal page carries the viewer's watermark (<meta name="lp-ref"> and <body data-ref>, see
 * portalWatermark()). Given the code from a copied page, this finds the account that opened it.
 */
class FindPortalWatermarkCommand extends Command
{
    protected $signature = 'portal:watermark {code : The lp-ref / data-ref code from the copied page}';

    protected $description = 'Find the account a portal watermark (lp-ref) belongs to';

    public function handle()
    {
        $code = strtolower(trim($this->argument('code')));
        $guards = [
            'library' => ['libraries', ['library_name', 'email']],
            'library_user' => ['library_users', ['name', 'email', 'library_id']],
            'web' => ['users', ['name', 'email']],
            'learner' => ['learners', ['name', 'library_id']],
        ];

        foreach ($guards as $guard => [$table, $cols]) {
            $found = null;
            DB::table($table)->select(array_merge(['id'], $cols))->orderBy('id')
                ->chunk(2000, function ($rows) use ($guard, $code, &$found) {
                    foreach ($rows as $row) {
                        if (portalWatermark([$guard, (int) $row->id]) === $code) {
                            $found = $row;

                            return false;
                        }
                    }
                });
            if ($found) {
                $this->info("Found: {$guard} #{$found->id}");
                $this->table(array_keys((array) $found), [(array) $found]);

                return self::SUCCESS;
            }
        }

        $this->warn('No account has this watermark (wrong code, or the app key changed since).');

        return self::FAILURE;
    }
}
