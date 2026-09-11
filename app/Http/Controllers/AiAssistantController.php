<?php

namespace App\Http\Controllers;

use App\Services\LibraroAiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AiAssistantController extends Controller
{
    protected $aiService;

    public function __construct(LibraroAiService $aiService)
    {
        $this->aiService = $aiService;
    }

    /**
     * Handle incoming user prompt from the AI Chat Widget.
     */
    public function sendQuery(Request $request)
    {
        $request->validate([
            'query' => 'required|string|max:1000',
        ]);

        $libraryId = getLibraryId();
        if (!$libraryId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized or missing library session.',
            ], 401);
        }

        $branchId = getCurrentBranch() ?? $libraryId;

        $result = $this->aiService->processQuery($request->input('query'), $libraryId, $branchId);

        return response()->json($result);
    }

    /**
     * Get recent chat history for the logged-in library owner.
     */
    public function getHistory()
    {
        $libraryId = getLibraryId();
        if (!$libraryId) {
            return response()->json(['success' => false, 'history' => []], 401);
        }

        $history = DB::table('ai_chat_histories')
            ->where('library_id', $libraryId)
            ->orderBy('id', 'asc')
            ->take(30)
            ->get(['id', 'sender', 'message', 'created_at']);

        return response()->json([
            'success' => true,
            'history' => $history,
        ]);
    }

    /**
     * Clear conversation history for the logged-in library owner.
     */
    public function clearHistory()
    {
        $libraryId = getLibraryId();
        if (!$libraryId) {
            return response()->json(['success' => false], 401);
        }

        DB::table('ai_chat_histories')
            ->where('library_id', $libraryId)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Chat history cleared.',
        ]);
    }
}
