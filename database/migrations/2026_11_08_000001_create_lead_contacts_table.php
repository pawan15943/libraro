<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sales leads of the admin portal (LeadContactController, /leads).
 * The table was used by the code but had no migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lead_contacts')) {
            return;
        }

        Schema::create('lead_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('library_name');
            $table->string('name')->nullable();
            $table->string('mobile', 15)->unique();
            $table->string('city')->nullable()->index();
            $table->string('lead_status')->nullable()->index();
            $table->string('status')->nullable()->index();
            $table->json('comments')->nullable();
            $table->boolean('is_contact_saved')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_contacts');
    }
};
