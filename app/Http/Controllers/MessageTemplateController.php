<?php

namespace App\Http\Controllers;

use App\Models\CustomNotificationTemplate;
use App\Models\NotificationTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MessageTemplateController extends Controller
{
    public function index()
    {
        $libraryId = getLibraryId();

        $globalTemplates = NotificationTemplate::where('is_paid', '0')
            ->where('is_active', 1)
            ->whereIn('type', ['waba', 'text'])
            ->orderBy('operation_id')
            ->get();

        $customTemplates = CustomNotificationTemplate::where('library_id', $libraryId)
            ->get()
            ->keyBy(fn ($item) => $item->operation_id . '_' . $item->type);

        $waba = collect();
        $text = collect();

        foreach ($globalTemplates as $template) {
            $key = $template->operation_id . '_' . $template->type;
            $custom = $customTemplates->get($key);
            $effectiveMessage = $custom->template_message ?? $template->template_message;
            $variables = $this->extractVariables($effectiveMessage);

            $row = (object) [
                'operation_id' => $template->operation_id,
                'type' => $template->type,
                'template_name' => $template->template_name,
                'template_message' => $effectiveMessage,
                'variables' => $variables,
                'variables_display' => $this->formatVariablesForDisplay($variables),
            ];

            $template->type === 'waba' ? $waba->push($row) : $text->push($row);
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
            'templates.*.operation_id' => 'required|integer|exists:operations,id',
            'templates.*.type' => 'required|in:text,waba',
            'templates.*.template_message' => 'required|string',
        ]);

        $libraryId = getLibraryId();

        foreach ($request->templates as $item) {
            $global = NotificationTemplate::where('operation_id', $item['operation_id'])
                ->where('type', $item['type'])
                ->where('is_paid', '0')
                ->first();

            if (!$global) {
                continue;
            }

            $custom = CustomNotificationTemplate::where('library_id', $libraryId)
                ->where('operation_id', $item['operation_id'])
                ->where('type', $item['type'])
                ->first();

            $currentMessage = $custom->template_message ?? $global->template_message;
            $requiredVariables = $this->extractVariables($currentMessage);
            $submittedVariables = $this->extractVariables($item['template_message']);
            $missing = array_diff($requiredVariables, $submittedVariables);

            if (!empty($missing)) {
                return back()
                    ->withErrors([
                        'templates' => 'Please keep these variables in the "' . $global->template_name . '" message: ' . implode(', ', $missing),
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

    private function formatVariablesForDisplay(array $variables): string
    {
        if (empty($variables)) {
            return '';
        }

        $wrapped = array_map(fn ($name) => '{{' . $name . '}}', $variables);

        return implode(', ', $wrapped) . '.';
    }
}
