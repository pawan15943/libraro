<?php

namespace App\Http\Controllers;

use App\Models\CustomNotificationTemplate;
use App\Models\NotificationTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MessageTemplateController extends Controller
{
    public const SUPPORTED_OPERATIONS = [
        11 => [
            'name' => 'Reminder Message',
            'sub_title' => 'Plan Renewal & Expiry Reminder',
            'icon' => 'fa-solid fa-bell',
            'color' => '#f59e0b',
            'bg' => '#fef3c7',
            'variables' => ['learner_name', 'seat_no', 'plan_end_date', 'library_name'],
            'descriptions' => [
                'learner_name' => 'Learner Full Name',
                'seat_no' => 'Assigned Seat No.',
                'plan_end_date' => 'Plan Expiry Date',
                'library_name' => 'Your Library Name',
            ],
        ],
        18 => [
            'name' => 'Pending Payment Reminder',
            'sub_title' => 'Overdue & Balance Payment Notice',
            'icon' => 'fa-solid fa-clock-rotate-left',
            'color' => '#ef4444',
            'bg' => '#fee2e2',
            'variables' => ['learner_name', 'due_date', 'pending_amount', 'library_name'],
            'descriptions' => [
                'learner_name' => 'Learner Full Name',
                'due_date' => 'Payment Due Date',
                'pending_amount' => 'Pending Amount (₹)',
                'library_name' => 'Your Library Name',
            ],
        ],
        19 => [
            'name' => 'Birthday Wishes',
            'sub_title' => 'Birthday Greetings & Wishes',
            'icon' => 'fa-solid fa-cake-candles',
            'color' => '#ec4899',
            'bg' => '#fce7f3',
            'variables' => ['learner_name', 'library_name'],
            'descriptions' => [
                'learner_name' => 'Learner Full Name',
                'library_name' => 'Your Library Name',
            ],
        ],
    ];

    public function index()
    {
        $libraryId = getLibraryId();

        $supportedOpIds = array_keys(self::SUPPORTED_OPERATIONS);

        $globalTemplates = NotificationTemplate::where('is_paid', '0')
            ->where('is_active', 1)
            ->whereIn('operation_id', $supportedOpIds)
            ->whereIn('type', ['waba', 'text'])
            ->get();

        $customTemplates = CustomNotificationTemplate::where('library_id', $libraryId)
            ->whereIn('operation_id', $supportedOpIds)
            ->get()
            ->keyBy(fn ($item) => $item->operation_id . '_' . $item->type);

        $waba = collect();
        $text = collect();

        foreach (self::SUPPORTED_OPERATIONS as $opId => $config) {
            foreach (['waba', 'text'] as $type) {
                $global = $globalTemplates->first(fn ($g) => (int) $g->operation_id === (int) $opId && $g->type === $type);

                $custom = $customTemplates->get($opId . '_' . $type);
                $effectiveMessage = $custom->template_message ?? ($global->template_message ?? '');

                $row = (object) [
                    'operation_id' => $opId,
                    'type' => $type,
                    'name' => $config['name'],
                    'sub_title' => $config['sub_title'],
                    'icon' => $config['icon'],
                    'color' => $config['color'],
                    'bg' => $config['bg'],
                    'template_name' => $global->template_name ?? ($opId . '-' . $type),
                    'template_message' => $effectiveMessage,
                    'variables' => $config['variables'],
                    'descriptions' => $config['descriptions'],
                ];

                if ($type === 'waba') {
                    $waba->push($row);
                } else {
                    $text->push($row);
                }
            }
        }

        return view('notification.message-templates', [
            'wabaMessageTemplates' => $waba,
            'textMessageTemplates' => $text,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'templates' => 'required|array|min:1',
            'templates.*.operation_id' => 'required|integer|in:11,18,19',
            'templates.*.type' => 'required|in:text,waba',
            'templates.*.template_message' => 'required|string',
        ]);

        $libraryId = getLibraryId();

        // Validate that no invalid placeholders are used
        foreach ($request->templates as $item) {
            $opId = (int) $item['operation_id'];
            $config = self::SUPPORTED_OPERATIONS[$opId] ?? null;
            if (!$config) {
                continue;
            }

            $allowedVars = $config['variables'];
            $usedVars = $this->extractVariables($item['template_message']);
            $invalidVars = array_diff($usedVars, $allowedVars);

            if (!empty($invalidVars)) {
                $formattedInvalid = implode(', ', array_map(fn ($v) => '{{' . $v . '}}', $invalidVars));
                return back()
                    ->withErrors([
                        'templates' => 'Invalid variable(s) found in "' . $config['name'] . '": ' . $formattedInvalid . '. Please use only the provided variables.',
                    ])
                    ->withInput();
            }
        }

        DB::beginTransaction();

        try {
            foreach ($request->templates as $item) {
                CustomNotificationTemplate::updateOrCreate(
                    [
                        'library_id' => $libraryId,
                        'operation_id' => $item['operation_id'],
                        'type' => $item['type'],
                    ],
                    [
                        'template_message' => $item['template_message'],
                        'is_active' => 1,
                        'is_custom' => 1,
                        'is_paid' => '0',
                    ]
                );
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('Message Template Update Error: ' . $e->getMessage());

            return back()->withErrors(['templates' => 'Something went wrong while saving templates.']);
        }

        return back()->with('success', 'Templates updated successfully');
    }

    private function extractVariables(?string $message): array
    {
        preg_match_all('/\{\{\s*([a-zA-Z0-9_]+)\s*\}\}/', $message ?? '', $matches);

        return array_values(array_unique($matches[1]));
    }
}
