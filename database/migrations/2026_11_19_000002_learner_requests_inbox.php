<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Learner requests (renew / change plan / upgrade / swap seat / close) get a library inbox:
 *
 *  - learner_request: the library's reply and who handled it, when. request_status: 0 pending, 1 done, 2 not possible.
 *  - permission "Learner Requests" (same plans as "Learner List") and Learners → Requests in the sidebar.
 *
 * Rows are found by name, never by id. Live DB: db/libraro_live_db_changes_2026-11-19.sql section 4.
 */
return new class extends Migration
{
    private const NAME = 'Learner Requests';

    public function up(): void
    {
        Schema::table('learner_request', function (Blueprint $t) {
            if (! Schema::hasColumn('learner_request', 'reply')) {
                $t->text('reply')->nullable()->after('request_status');
            }
            if (! Schema::hasColumn('learner_request', 'handled_at')) {
                $t->timestamp('handled_at')->nullable()->after('reply');
            }
            if (! Schema::hasColumn('learner_request', 'handled_by_type')) {
                $t->string('handled_by_type', 20)->nullable()->after('handled_at');
            }
            if (! Schema::hasColumn('learner_request', 'handled_by_id')) {
                $t->unsignedBigInteger('handled_by_id')->nullable()->after('handled_by_type');
            }
        });

        $now = now();
        $like = DB::table('permissions')->where('guard_name', 'library')->where('name', 'Learner List')->first();
        $id = DB::table('permissions')->where('guard_name', 'library')->where('name', self::NAME)->value('id')
            ?? DB::table('permissions')->insertGetId([
                'name' => self::NAME, 'slug' => Str::slug(self::NAME), 'guard_name' => 'library',
                'permission_category_id' => $like->permission_category_id ?? null,
                'description' => 'See and answer learner requests: renew, change plan, upgrade, swap seat, close',
                'created_at' => $now, 'updated_at' => $now,
            ]);
        if ($like) {
            $plans = DB::table('subscription_permission')->where('permission_id', $like->id)->pluck('subscription_id');
            foreach ($plans as $plan) {
                if (! DB::table('subscription_permission')->where('subscription_id', $plan)->where('permission_id', $id)->exists()) {
                    DB::table('subscription_permission')->insert(['subscription_id' => $plan, 'permission_id' => $id, 'created_at' => $now]);
                }
            }
        }

        // Learners → Requests (the group the "Learners" list link sits in)
        $parentId = DB::table('menus')->where('guard', 'library')->where('url', 'learners')->whereNull('deleted_at')->value('parent_id');
        if ($parentId && ! DB::table('menus')->where('guard', 'library')->where('url', 'learner.requests.index')->whereNull('deleted_at')->exists()) {
            DB::table('menus')->insert([
                'name' => 'Requests', 'url' => 'learner.requests.index', 'icon' => null, 'parent_id' => $parentId, 'order' => 7,
                'guard' => 'library', 'status' => 1, 'has_permissions' => self::NAME, 'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        try {
            app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        } catch (\Throwable $e) {
        }
    }

    public function down(): void
    {
        DB::table('menus')->where('guard', 'library')->where('url', 'learner.requests.index')->delete();
        $ids = DB::table('permissions')->where('guard_name', 'library')->where('name', self::NAME)->pluck('id');
        DB::table('subscription_permission')->whereIn('permission_id', $ids)->delete();
        DB::table('library_user_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();

        Schema::table('learner_request', function (Blueprint $t) {
            foreach (['handled_by_id', 'handled_by_type', 'handled_at', 'reply'] as $col) {
                if (Schema::hasColumn('learner_request', $col)) {
                    $t->dropColumn($col);
                }
            }
        });
    }
};
