<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('learners', 'no_expiry')) {
            Schema::table('learners', function (Blueprint $table) {
                $table->boolean('no_expiry')->default(0)->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('learners', 'no_expiry')) {
            Schema::table('learners', function (Blueprint $table) {
                $table->dropColumn('no_expiry');
            });
        }
    }
};
