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
        Schema::table('learner_detail', function (Blueprint $table) {
            if (!Schema::hasColumn('learner_detail', 'learner_id')) {
                $table->unsignedBigInteger('learner_id')->after('id');
            }

            // Add foreign key constraint
            $table->foreign('learner_id')
                ->references('id')
                ->on('learners')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('learner_detail', function (Blueprint $table) {
            $table->dropForeign(['learner_id']);
            $table->dropColumn('learner_id');
        });
    }
};
