<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Feedback;
use App\Services\LibraryConfigurationService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class FeedbackController extends Controller
{
    public function show()
    {
        $feedback = Feedback::where('library_id', getLibraryId())->first();

        if ($feedback && $feedback->attachment) {
            $feedback->attachment = asset('public/' . $feedback->attachment);
        }

        return response()->json([
            'status' => true,
            'message' => $feedback ? 'Feedback fetched successfully' : 'No feedback submitted yet',
            'data' => $feedback,
        ]);
    }

    public function save(Request $request, LibraryConfigurationService $service)
    {
        $validator = Validator::make($request->all(), [
            'feedback_feature_id' => 'required|integer|exists:feedback_features,id',
            'rating' => 'nullable|integer|min:1|max:5',
            'description' => 'required|string',
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
            'recommend' => 'nullable|boolean',
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

            $feedback = Feedback::where('library_id', getLibraryId())->first();

            $attachmentInput = $request->file('attachment') ?? $request->input('attachment');

            if (!empty($attachmentInput)) {
                if ($feedback && $feedback->attachment) {
                    File::delete(public_path($feedback->attachment));
                }
                $data['attachment'] = $service->moveTempFileToPublic($attachmentInput, 'feedback', 'upload/feedback');
            }

            if ($feedback) {
                $feedback->update($data);
                $message = 'Feedback updated successfully';
            } else {
                $feedback = Feedback::create($data);
                $message = 'Feedback submitted successfully';
            }

            if ($feedback->attachment) {
                $feedback->attachment = asset('public/' . $feedback->attachment);
            }

            return response()->json([
                'status' => true,
                'message' => $message,
                'data' => $feedback,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
