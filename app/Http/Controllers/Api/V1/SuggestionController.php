<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Suggestion;
use App\Services\LibraryConfigurationService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class SuggestionController extends Controller
{
    public function index()
    {
        $suggestions = Suggestion::where('library_id', getLibraryId())
            ->orderByDesc('id')
            ->get()
            ->map(function ($suggestion) {
                if ($suggestion->attachment) {
                    $suggestion->attachment = asset('public/' . $suggestion->attachment);
                }
                return $suggestion;
            });

        return response()->json([
            'status' => true,
            'message' => 'Suggestions fetched successfully',
            'data' => $suggestions,
        ]);
    }

    public function save(Request $request, LibraryConfigurationService $service)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'suggestion_feature' => 'required|string|max:255',
            'description' => 'nullable|string',
            'attachment' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    if ($value instanceof UploadedFile) {
                        if (!in_array(strtolower($value->getClientOriginalExtension()), ['jpg', 'jpeg', 'png', 'pdf'])) {
                            $fail('The attachment must be a file of type: jpg, jpeg, png, pdf.');
                        }
                        if ($value->getSize() > 2048 * 1024) {
                            $fail('The attachment must not be greater than 2048 kilobytes.');
                        }
                    } elseif (is_string($value) && $value !== '') {
                        $path = parse_url($value, PHP_URL_PATH) ?: $value;
                        if (str_contains($path, '/storage/')) {
                            $path = substr($path, strpos($path, '/storage/') + 9);
                        }
                        if (str_starts_with($path, 'temp/') && !Storage::disk('public')->exists($path)) {
                            $fail('Temp attachment not found.');
                        }
                    }
                },
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        try {
            $data = $validator->validated();
            unset($data['attachment']);
            $data['library_id'] = getLibraryId();

            $attachmentInput = $request->file('attachment') ?? $request->input('attachment');

            if (!empty($attachmentInput)) {
                $data['attachment'] = $service->moveTempFileToPublic($attachmentInput, 'suggestion', 'upload/suggestions');
            }

            $suggestion = Suggestion::create($data);

            if ($suggestion->attachment) {
                $suggestion->attachment = asset('public/' . $suggestion->attachment);
            }

            return response()->json([
                'status' => true,
                'message' => 'Suggestion submitted successfully',
                'data' => $suggestion,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
