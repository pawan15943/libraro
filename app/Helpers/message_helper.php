<?php

/*
|--------------------------------------------------------------------------
| Free reminder messages (wa.me / sms: links)
|--------------------------------------------------------------------------
| Message text comes from the library's own template (Message templates page,
| custom_notification_templates) and falls back to the global free template
| (notification_templates, is_paid = 0). See MessageTemplateController.
*/

use App\Models\CustomNotificationTemplate;
use App\Models\NotificationTemplate;

if (!function_exists('freeMessageTemplate')) {
    /**
     * @param string $type 'waba' (WhatsApp) or 'text' (SMS)
     */
    function freeMessageTemplate(int $operationId, string $type): ?string
    {
        static $cache = [];
        $libraryId = getLibraryId();
        $key = $libraryId . ':' . $operationId . ':' . $type;

        if (!array_key_exists($key, $cache)) {
            $message = CustomNotificationTemplate::where('library_id', $libraryId)
                ->where('operation_id', $operationId)
                ->where('type', $type)
                ->value('template_message');

            if (trim((string) $message) === '') {
                $message = NotificationTemplate::where('operation_id', $operationId)
                    ->where('type', $type)
                    ->where('is_paid', '0')
                    ->where('is_active', 1)
                    ->value('template_message');
            }

            $cache[$key] = trim((string) $message) !== '' ? $message : null;
        }

        return $cache[$key];
    }
}

if (!function_exists('fillMessageTemplate')) {
    /** Replaces {{variable}} placeholders; unknown ones are left empty. */
    function fillMessageTemplate(string $message, array $vars): string
    {
        return preg_replace_callback('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', function ($m) use ($vars) {
            return (string) ($vars[$m[1]] ?? '');
        }, $message);
    }
}

if (!function_exists('reminderChannels')) {
    /**
     * Channels a learner gets reminders on, from learners.sended_message_type
     * ('whatsapp' / 'text' / 'both', or 1 / 2 / 3 from the learner list API).
     * Nothing chosen → WhatsApp.
     *
     * @return array{whatsapp: bool, sms: bool}
     */
    function reminderChannels($preference): array
    {
        $pref = strtolower(trim((string) $preference));
        $pref = ['1' => 'whatsapp', '2' => 'text', '3' => 'both'][$pref] ?? $pref;

        return match ($pref) {
            'text' => ['whatsapp' => false, 'sms' => true],
            'both' => ['whatsapp' => true, 'sms' => true],
            default => ['whatsapp' => true, 'sms' => false],
        };
    }
}

if (!function_exists('pendingPaymentReminderLinks')) {
    /**
     * WhatsApp / SMS links with the "Pending Payment Reminder" (operation 18) message.
     *
     * @return array{whatsapp: ?string, sms: ?string}
     */
    function pendingPaymentReminderLinks(string $mobile, string $name, $pendingAmount, $dueDate, $preference): array
    {
        $phone = preg_replace('/\D/', '', $mobile);
        if (strlen($phone) > 10 && str_starts_with($phone, '91')) {
            $phone = substr($phone, 2);
        }
        if ($phone === '') {
            return ['whatsapp' => null, 'sms' => null];
        }

        $amount = rtrim(rtrim(number_format((float) $pendingAmount, 2, '.', ','), '0'), '.');
        $due = '';
        if (!empty($dueDate)) {
            try {
                $due = \Carbon\Carbon::parse($dueDate)->format('d M Y');
            } catch (\Throwable $e) {
                $due = (string) $dueDate;
            }
        }
        $vars = [
            'learner_name' => $name,
            'pending_amount' => $amount,
            'due_date' => $due ?: 'today',
            'library_name' => getCurrentBranchName() ?: (getLibrary()->library_name ?? ''),
        ];

        $default = "Dear {{learner_name}},\n\nYour library fee of ₹{{pending_amount}} (due on {{due_date}}) is still pending. Please clear it at the earliest to continue uninterrupted access to your library seat.\n\n– Team {{library_name}}";
        $channels = reminderChannels($preference);

        return [
            'whatsapp' => $channels['whatsapp']
                ? 'https://wa.me/91' . $phone . '?text=' . rawurlencode(fillMessageTemplate(freeMessageTemplate(18, 'waba') ?? $default, $vars))
                : null,
            'sms' => $channels['sms']
                ? 'sms:+91' . $phone . '?body=' . rawurlencode(fillMessageTemplate(freeMessageTemplate(18, 'text') ?? $default, $vars))
                : null,
        ];
    }
}
