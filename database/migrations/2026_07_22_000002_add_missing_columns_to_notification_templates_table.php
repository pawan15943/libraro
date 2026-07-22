<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMissingColumnsToNotificationTemplatesTable extends Migration
{
    public function up()
    {
        Schema::table('notification_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('notification_templates', 'template_code')) {
                $table->string('template_code')->nullable()->after('template_name');
            }
            if (!Schema::hasColumn('notification_templates', 'is_custom')) {
                $table->boolean('is_custom')->default(false)->after('is_active');
            }
            if (!Schema::hasColumn('notification_templates', 'is_paid')) {
                $table->enum('is_paid', ['0', '1'])->default('0')->after('is_custom');
            }
        });
    }

    public function down()
    {
        Schema::table('notification_templates', function (Blueprint $table) {
            foreach (['template_code', 'is_custom', 'is_paid'] as $column) {
                if (Schema::hasColumn('notification_templates', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
