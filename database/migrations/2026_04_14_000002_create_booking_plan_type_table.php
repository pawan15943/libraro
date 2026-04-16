<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Leftover from a failed run (record not inserted into `migrations`).
        Schema::dropIfExists('booking_plan_type');

        $bookingTable = 'bookings';
        if (! Schema::hasTable($bookingTable) && Schema::hasTable('booking')) {
            $bookingTable = 'booking';
        }

        Schema::create('booking_plan_type', function (Blueprint $table) use ($bookingTable) {
            $table->id();
            // Avoid FK to bookings when table name/engine differs (legacy DBs); integrity enforced in app.
            $table->unsignedBigInteger('booking_id');
            $table->index('booking_id');
            $table->foreignId('plan_type_id')->constrained('plan_types')->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['booking_id', 'plan_type_id'], 'booking_pt_unique');
        });

        if (Schema::hasTable($bookingTable) && Schema::hasColumn($bookingTable, 'plan_type_id')) {
            $rows = DB::table($bookingTable)->select('id', 'plan_type_id')->whereNotNull('plan_type_id')->get();
            foreach ($rows as $row) {
                DB::table('booking_plan_type')->insert([
                    'booking_id' => $row->id,
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
        Schema::dropIfExists('booking_plan_type');
    }
};
