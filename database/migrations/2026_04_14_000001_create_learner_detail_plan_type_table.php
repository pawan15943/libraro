<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('learner_detail_plan_type', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learner_detail_id')->constrained('learner_detail')->cascadeOnDelete();
            $table->foreignId('plan_type_id')->constrained('plan_types')->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['learner_detail_id', 'plan_type_id'], 'ld_pt_unique');
        });

        if (Schema::hasTable('learner_detail')) {
            $rows = DB::table('learner_detail')->select('id', 'plan_type_id')->whereNotNull('plan_type_id')->get();
            foreach ($rows as $row) {
                DB::table('learner_detail_plan_type')->insert([
                    'learner_detail_id' => $row->id,
                    'plan_type_id' => $row->plan_type_id,
                    'sort_order' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('learner_detail_plan_type');
    }
};
