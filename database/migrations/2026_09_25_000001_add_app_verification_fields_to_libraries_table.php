<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('libraries', function (Blueprint $table) {
            if (!Schema::hasColumn('libraries', 'is_app_verified')) {
                $table->boolean('is_app_verified')->default(false)->after('status');
            }
            if (!Schema::hasColumn('libraries', 'app_verification_code')) {
                $table->string('app_verification_code', 50)->nullable()->after('is_app_verified');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('libraries', function (Blueprint $table) {
            if (Schema::hasColumn('libraries', 'is_app_verified')) {
                $table->dropColumn('is_app_verified');
            }
            if (Schema::hasColumn('libraries', 'app_verification_code')) {
                $table->dropColumn('app_verification_code');
            }
        });
    }
};
