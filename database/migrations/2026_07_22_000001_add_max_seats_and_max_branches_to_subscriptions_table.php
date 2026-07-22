<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            // NULL = unlimited
            $table->unsignedInteger('max_seats')->nullable()->after('name');
            $table->unsignedInteger('max_branches')->nullable()->after('max_seats');
        });

        // Seed the known default plans (Basic / Standard / Premium) with their limits.
        DB::table('subscriptions')->where('id', 1)->update(['max_seats' => 50, 'max_branches' => 1]);
        DB::table('subscriptions')->where('id', 2)->update(['max_seats' => 100, 'max_branches' => 2]);
        DB::table('subscriptions')->where('id', 3)->update(['max_seats' => null, 'max_branches' => 3]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['max_seats', 'max_branches']);
        });
    }
};
