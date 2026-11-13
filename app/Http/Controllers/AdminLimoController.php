<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/**
 * Platform-admin pages for Limo, the library AI assistant:
 *  - questions Limo could not answer and answers marked 👎, to learn what to add;
 *  - the knowledge base (tips / FAQ) Limo answers from — editable without a developer.
 */
class AdminLimoController extends Controller
{
    /** Unanswered questions (grouped) or answers marked not helpful. */
    public function questions(Request $request)
    {
        $tab = $request->query('tab') === 'disliked' ? 'disliked' : 'unanswered';
        $since = now()->subDays(30);

        $base = DB::table('ai_chat_histories')->where('sender', 'assistant');
        $stats = [
            'asked' => (clone $base)->where('created_at', '>=', $since)->count(),
            'unanswered' => (clone $base)->where('created_at', '>=', $since)->where('intent', 'unknown')->count(),
            'liked' => (clone $base)->where('feedback', 1)->count(),
            'disliked' => (clone $base)->where('feedback', -1)->count(),
        ];
        $stats['answeredPct'] = $stats['asked'] ? round(($stats['asked'] - $stats['unanswered']) / $stats['asked'] * 100) : null;

        if ($tab === 'unanswered') {
            $rows = (clone $base)->where('intent', 'unknown')->whereNotNull('question')
                ->selectRaw('LOWER(TRIM(question)) as q, COUNT(*) as times, COUNT(DISTINCT library_id) as libraries, MAX(created_at) as last_asked')
                ->groupBy(DB::raw('LOWER(TRIM(question))'))
                ->orderByDesc('times')->orderByDesc('last_asked')
                ->paginate(30)->withQueryString();
        } else {
            $rows = (clone $base)->where('ai_chat_histories.feedback', -1)
                ->leftJoin('libraries as l', 'l.id', '=', 'ai_chat_histories.library_id')
                ->orderByDesc('ai_chat_histories.updated_at')
                ->select('ai_chat_histories.id', 'ai_chat_histories.question', 'ai_chat_histories.intent', 'ai_chat_histories.message', 'ai_chat_histories.updated_at', 'l.library_name')
                ->paginate(30)->withQueryString();
            $rows->getCollection()->transform(function ($r) {
                $r->title = (json_decode((string) $r->message, true) ?: [])['title'] ?? '—';

                return $r;
            });
        }

        return view('admin.limo.questions', compact('tab', 'rows', 'stats'));
    }

    public function knowledge(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $entries = DB::table('ai_knowledge_entries')
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->where('title_en', 'like', "%{$search}%")->orWhere('title_hi', 'like', "%{$search}%")->orWhere('keys', 'like', "%{$search}%")))
            ->orderByDesc('status')->orderBy('title_en')
            ->paginate(30)->withQueryString();

        return view('admin.limo.knowledge', compact('entries', 'search'));
    }

    public function create(Request $request)
    {
        // "Add answer" from the unanswered list pre-fills the question as a key phrase
        $entry = (object) ['id' => null, 'keys' => (string) $request->query('keys', ''), 'title_en' => '', 'title_hi' => '', 'steps_en' => '', 'steps_hi' => '', 'links' => null, 'status' => 1];

        return view('admin.limo.form', ['entry' => $entry, 'linksText' => '']);
    }

    public function store(Request $request)
    {
        DB::table('ai_knowledge_entries')->insert($this->validated($request) + ['created_at' => now(), 'updated_at' => now()]);

        return redirect()->route('admin.limo.knowledge')->with('success', 'Answer added. Limo will use it right away.');
    }

    public function edit(int $id)
    {
        $entry = DB::table('ai_knowledge_entries')->where('id', $id)->first();
        abort_unless($entry, 404);
        $linksText = collect(json_decode((string) $entry->links, true) ?: [])->map(fn ($l) => implode(' | ', $l))->implode("\n");

        return view('admin.limo.form', compact('entry', 'linksText'));
    }

    public function update(Request $request, int $id)
    {
        abort_unless(DB::table('ai_knowledge_entries')->where('id', $id)->exists(), 404);
        DB::table('ai_knowledge_entries')->where('id', $id)->update($this->validated($request) + ['updated_at' => now()]);

        return redirect()->route('admin.limo.knowledge')->with('success', 'Answer updated.');
    }

    public function toggle(int $id)
    {
        $entry = DB::table('ai_knowledge_entries')->where('id', $id)->first();
        abort_unless($entry, 404);
        DB::table('ai_knowledge_entries')->where('id', $id)->update(['status' => $entry->status ? 0 : 1, 'updated_at' => now()]);

        return back()->with('success', $entry->status ? 'Answer turned off.' : 'Answer turned on.');
    }

    public function destroy(int $id)
    {
        DB::table('ai_knowledge_entries')->where('id', $id)->delete();

        return redirect()->route('admin.limo.knowledge')->with('success', 'Answer deleted.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'keys' => 'required|string|max:5000',
            'title_en' => 'required|string|max:191',
            'title_hi' => 'required|string|max:191',
            'steps_en' => 'required|string|max:10000',
            'steps_hi' => 'required|string|max:10000',
            'links' => 'nullable|string|max:2000',
            'status' => 'nullable|boolean',
        ]);

        $lines = fn ($t) => implode("\n", array_values(array_filter(array_map('trim', preg_split('/\r?\n/', (string) $t)))));

        // Links: one per line — "route.name | English label | Hindi label"
        $links = [];
        foreach (array_filter(array_map('trim', preg_split('/\r?\n/', (string) ($data['links'] ?? '')))) as $i => $line) {
            $parts = array_map('trim', explode('|', $line));
            if (count($parts) !== 3 || ! Route::has($parts[0])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'links' => 'Line '.($i + 1).': use "route.name | English label | Hindi label" with a route that exists.',
                ]);
            }
            $links[] = $parts;
        }

        return [
            'keys' => $lines($data['keys']),
            'title_en' => trim($data['title_en']),
            'title_hi' => trim($data['title_hi']),
            'steps_en' => $lines($data['steps_en']),
            'steps_hi' => $lines($data['steps_hi']),
            'links' => $links ? json_encode($links, JSON_UNESCAPED_UNICODE) : null,
            'status' => $request->boolean('status') ? 1 : 0,
        ];
    }
}
