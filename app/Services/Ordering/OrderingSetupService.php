<?php

namespace App\Services\Ordering;

use App\Models\Ordering\OrderCategory;
use App\Models\Ordering\OrderSetting;
use Illuminate\Support\Facades\DB;

/**
 * Ordering & Services set-up for one branch: its settings row, and the starter categories the
 * first time the branch opens the module. Works without a logged-in user (commands, learner side)
 * because library_id / branch_id are always passed in.
 */
class OrderingSetupService
{
    public function settings(int $libraryId, int $branchId): OrderSetting
    {
        $setting = OrderSetting::withoutGlobalScopes()->where('branch_id', $branchId)->first();
        if ($setting) {
            return $setting;
        }

        return DB::transaction(function () use ($libraryId, $branchId) {
            // lock-free double-create guard: branch_id is unique
            $setting = OrderSetting::withoutGlobalScopes()->firstOrCreate(
                ['branch_id' => $branchId],
                ['library_id' => $libraryId]
            );
            if ($setting->wasRecentlyCreated) {
                $this->seedCategories($libraryId, $branchId);
            }

            return $setting->fresh();
        });
    }

    /** Settings of the branch the library user is working in. */
    public function current(): OrderSetting
    {
        return $this->settings((int) getLibraryId(), (int) getCurrentBranch());
    }

    private function seedCategories(int $libraryId, int $branchId): void
    {
        $exists = OrderCategory::withoutGlobalScopes()->withTrashed()->where('branch_id', $branchId)->exists();
        if ($exists) {
            return;
        }
        $now = now();
        $rows = [];
        foreach (OrderCategory::DEFAULTS as $i => [$name, $icon, $isService, $payLater]) {
            $rows[] = [
                'library_id' => $libraryId, 'branch_id' => $branchId, 'name' => $name, 'icon' => $icon,
                'sort_order' => $i + 1, 'is_enabled' => true, 'is_service' => $isService,
                'allow_online' => true, 'allow_offline' => true, 'allow_pay_later' => $payLater,
                'created_at' => $now, 'updated_at' => $now,
            ];
        }
        OrderCategory::withoutGlobalScopes()->insert($rows);
    }
}
