<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Global Discount Master (docs/GLOBAL_DISCOUNT.md): one library-wide offer that applies by itself
 * to every booking form between its start and end date. learner_transactions remembers which
 * offer a booking got and how much of its discount_amount came from it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('global_discounts')) {
            Schema::create('global_discounts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('library_id')->index();
                $table->string('name', 100);
                $table->enum('discount_type', ['percentage', 'amount']);
                $table->decimal('discount_value', 10, 2);
                $table->date('start_date');
                $table->date('end_date');
                $table->boolean('is_enabled')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['library_id', 'start_date', 'end_date']);
            });
        }

        Schema::table('learner_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('learner_transactions', 'global_discount_id')) {
                $table->unsignedBigInteger('global_discount_id')->nullable()->after('discount_amount');
            }
            if (! Schema::hasColumn('learner_transactions', 'global_discount_amount')) {
                $table->decimal('global_discount_amount', 10, 2)->default(0)->after('global_discount_id');
            }
        });

        // Sidebar: "Global Discount" under Advanced Management (same parent as Exam Names)
        $parent = DB::table('menus')->where('guard', 'library')->whereNull('deleted_at')
            ->where('url', 'exam.index')->whereNotNull('parent_id')->value('parent_id');
        $exists = DB::table('menus')->where('guard', 'library')->where('url', 'global-discount.index')->whereNull('deleted_at')->exists();
        if ($parent && ! $exists) {
            DB::table('menus')->insert([
                'name' => 'Global Discount', 'url' => 'global-discount.index', 'icon' => null, 'parent_id' => $parent, 'order' => 7,
                'guard' => 'library', 'status' => 1, 'has_permissions' => null, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('menus')->where('guard', 'library')->where('url', 'global-discount.index')->delete();

        Schema::table('learner_transactions', function (Blueprint $table) {
            foreach (['global_discount_amount', 'global_discount_id'] as $col) {
                if (Schema::hasColumn('learner_transactions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::dropIfExists('global_discounts');
    }
};
