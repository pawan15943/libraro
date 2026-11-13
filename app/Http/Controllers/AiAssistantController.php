<?php

namespace App\Http\Controllers;

use App\Services\LibraroAiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mpdf\Mpdf;

/**
 * Limo, the Libraro AI assistant — used by the library panel widget and the mobile app
 * (same endpoints under api/v1, see docs/API.md). Every conversation belongs to one user
 * (owner or staff) of one branch.
 */
class AiAssistantController extends Controller
{
    public function __construct(protected LibraroAiService $aiService) {}

    /**
     * Answer a question. `lang`: auto (detect from the question) | en | hi.
     * `mode`: ask (default) | support — step-by-step problem solving.
     * `shown`: the text the chat shows for this question (e.g. a button label), saved in history.
     */
    public function sendQuery(Request $request)
    {
        $data = $request->validate([
            'query' => 'required|string|max:500',
            'lang' => 'nullable|in:auto,en,hi',
            'mode' => 'nullable|in:ask,support',
            'shown' => 'nullable|string|max:200',
        ]);

        $libraryId = getLibraryId();
        if (! $libraryId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized or missing library session.'], 401);
        }

        return response()->json($this->aiService->processQuery(
            trim($data['query']),
            (int) $libraryId,
            (int) (getCurrentBranch() ?: 0),
            $data['lang'] ?? 'auto',
            $data['mode'] ?? 'ask',
            $data['shown'] ?? null
        ));
    }

    /**
     * This user's latest 40 messages in the current branch, oldest first.
     * Assistant messages come back as structured replies (`reply`, with `id` and `feedback`).
     */
    public function getHistory()
    {
        $libraryId = getLibraryId();
        if (! $libraryId) {
            return response()->json(['success' => false, 'history' => []], 401);
        }

        $history = $this->historyQuery($libraryId)
            ->orderByDesc('id')
            ->take(40)
            ->get(['id', 'sender', 'message', 'feedback', 'created_at'])
            ->reverse()
            ->values()
            ->map(function ($m) {
                $reply = $m->sender === 'assistant' ? json_decode($m->message, true) : null;
                if (is_array($reply)) {
                    $reply['id'] = $m->id;
                    $reply['feedback'] = $m->feedback;
                }

                return [
                    'id' => $m->id,
                    'sender' => $m->sender,
                    'message' => is_array($reply) ? null : $m->message,
                    'reply' => is_array($reply) ? $reply : null,
                    'created_at' => $m->created_at,
                ];
            });

        return response()->json(['success' => true, 'history' => $history]);
    }

    /**
     * Clear this user's conversation in the current branch.
     */
    public function clearHistory()
    {
        $libraryId = getLibraryId();
        if (! $libraryId) {
            return response()->json(['success' => false], 401);
        }

        $this->historyQuery($libraryId)->delete();

        return response()->json(['success' => true, 'message' => 'Chat history cleared.']);
    }

    /**
     * 👍 / 👎 on one answer. value: 1 (helpful), -1 (not helpful), 0 (remove).
     */
    public function feedback(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|integer',
            'value' => 'required|integer|in:-1,0,1',
        ]);

        $libraryId = getLibraryId();
        if (! $libraryId) {
            return response()->json(['success' => false], 401);
        }

        $ok = $this->historyQuery($libraryId)->where('id', $data['id'])->exists()
            && $this->aiService->feedback((int) $libraryId, (int) $data['id'], (int) $data['value']);

        return response()->json(['success' => $ok], $ok ? 200 : 404);
    }

    /**
     * Things that need attention today (shown when the chat opens).
     */
    public function alerts(Request $request)
    {
        $libraryId = getLibraryId();
        if (! $libraryId) {
            return response()->json(['success' => false, 'alerts' => []], 401);
        }
        $lang = $request->query('lang') === 'hi' ? 'hi' : 'en';

        return response()->json([
            'success' => true,
            'alerts' => $this->aiService->alerts((int) $libraryId, (int) (getCurrentBranch() ?: 0), $lang),
        ]);
    }

    /**
     * One answer as a PDF (Hindi text included).
     */
    public function exportPdf(int $id)
    {
        $libraryId = getLibraryId();
        abort_unless($libraryId && $this->historyQuery($libraryId)->where('id', $id)->exists(), 404);

        $reply = $this->aiService->storedReply((int) $libraryId, $id);
        abort_unless($reply, 404);

        $html = view('partials.limo-export', [
            'reply' => $reply,
            'branch' => DB::table('branches')->where('id', getCurrentBranch())->value(DB::raw('COALESCE(display_name, name)')),
            'generatedAt' => now()->format('j M Y, g:i A'),
        ])->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_top' => 14, 'margin_bottom' => 14, 'margin_left' => 14, 'margin_right' => 14,
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'tempDir' => storage_path('app/mpdf-tmp'),
        ]);
        $mpdf->WriteHTML($html);

        $name = 'limo-'.preg_replace('/[^a-z0-9]+/i', '-', strtolower((string) ($reply['intent'] ?? 'answer'))).'-'.now()->format('Y-m-d').'.pdf';

        return response($mpdf->Output($name, 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$name.'"',
        ]);
    }

    /** This user's messages in the current branch. */
    private function historyQuery($libraryId)
    {
        $branchId = getCurrentBranch();
        $actor = $this->aiService->actorFor((int) $libraryId);

        return DB::table('ai_chat_histories')
            ->where('library_id', $libraryId)
            ->when($branchId, fn ($q) => $q->where(fn ($w) => $w->where('branch_id', $branchId)->orWhereNull('branch_id')))
            ->where(function ($q) use ($actor, $libraryId) {
                $q->where('actor', $actor);
                // Messages saved before users were tracked belong to the owner
                if ($actor === 'library:'.$libraryId) {
                    $q->orWhereNull('actor');
                }
            });
    }
}
