<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Free "Pending Payment Reminder" (operation 18) templates used by the dashboard's
 * Payment due → Remind button. Libraries can edit them under Message templates.
 * Only adds a template when none exists for that channel.
 */
return new class extends Migration
{
    private const OPERATION_ID = 18;

    public function up(): void
    {
        if (! Schema::hasTable('notification_templates')) {
            return;
        }

        $templates = [
            'waba' => [
                'template_name' => 'pending-payment',
                'template_message' => "Hi {{learner_name}},\n\nThis is a gentle reminder that your library fee of ₹{{pending_amount}} (due on {{due_date}}) is still pending.\n\nPlease clear it at the earliest so you can keep studying without any interruption.\n\nThank you,\n{{library_name}} 📚",
            ],
            'text' => [
                'template_name' => 'pending-payment-sms',
                'template_message' => 'Dear {{learner_name}}, your library fee of Rs.{{pending_amount}} due on {{due_date}} is pending. Please pay at the earliest. - {{library_name}}',
            ],
        ];

        foreach ($templates as $type => $tpl) {
            $exists = DB::table('notification_templates')
                ->where('operation_id', self::OPERATION_ID)
                ->where('type', $type)
                ->where('is_paid', '0')
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('notification_templates')->insert([
                'operation_id' => self::OPERATION_ID,
                'type' => $type,
                'template_name' => $tpl['template_name'],
                'template_code' => $tpl['template_name'],
                'template_message' => $tpl['template_message'],
                'is_active' => 1,
                'is_custom' => 0,
                'is_paid' => '0',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('notification_templates')
            ->where('operation_id', self::OPERATION_ID)
            ->where('type', 'text')
            ->where('template_name', 'pending-payment-sms')
            ->delete();
    }
};
