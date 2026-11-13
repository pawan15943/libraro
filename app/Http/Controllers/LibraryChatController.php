<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\Learner;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Library owner side of the end-to-end encrypted chat (owner only: staff do not hold the key). */
class LibraryChatController extends Controller
{
    public function __construct(private ChatService $chat)
    {
    }

    /** Owner = the `library` guard. Staff (library_user) are refused. */
    private function libraryId(): int
    {
        abort_unless(Auth::guard('library')->check(), 403, 'Only the library owner can use chat.');

        return (int) Auth::guard('library')->id();
    }

    private function learnerOf(int $learnerId): Learner
    {
        return Learner::withoutGlobalScopes()->where('library_id', $this->libraryId())->whereNull('deleted_at')->findOrFail($learnerId);
    }

    /** Conversations of this library, newest first, with unread counts. */
    private function threadList(int $lib, string $q = '')
    {
        return ChatThread::where('chat_threads.library_id', $lib)
            ->join('learners', 'learners.id', '=', 'chat_threads.learner_id')
            ->whereNotNull('chat_threads.last_message_at')
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('learners.name', 'like', "%$q%")->orWhere('learners.learner_no', 'like', "%$q%")))
            ->select('chat_threads.*', 'learners.name as learner_name', 'learners.learner_no')
            ->selectSub(
                ChatMessage::selectRaw('count(*)')->whereColumn('chat_messages.thread_id', 'chat_threads.id')->where('sender_type', 'learner')->whereNull('read_at'),
                'unread'
            )
            ->orderByDesc('chat_threads.last_message_at')->limit(200)->get();
    }

    public function index(Request $request)
    {
        $lib = $this->libraryId();
        $q = trim((string) $request->query('q', ''));

        return view('library.chat.index', [
            'threads' => $this->threadList($lib, $q), 'q' => $q, 'active' => null,
            'hasKey' => (bool) $this->chat->activeKey('library', $lib), 'libraryId' => $lib,
        ]);
    }

    /** Start a chat: search any student of this library (name, ID or mobile). */
    public function students(Request $request)
    {
        $lib = $this->libraryId();
        $q = trim((string) $request->query('q', ''));
        $rows = Learner::withoutGlobalScopes()->where('library_id', $lib)->whereNull('deleted_at')
            ->when($q !== '', fn ($w) => $w->where(fn ($x) => $x->where('name', 'like', "%$q%")->orWhere('learner_no', 'like', "%$q%")->orWhere('mobile', 'like', "%$q%")))
            ->orderBy('name')->limit(30)->get(['id', 'name', 'learner_no', 'mobile', 'status']);

        return response()->json($rows->map(fn ($l) => [
            'id' => $l->id, 'name' => $l->name, 'no' => $l->learner_no, 'mobile' => $l->mobile,
            'active' => (int) $l->status === 1, 'url' => route('chat.thread', $l->id),
        ])->values());
    }

    /** Owner's own key, registered as soon as the chat page opens (no conversation needed). */
    public function registerOwnKey(Request $request)
    {
        $lib = $this->libraryId();
        $jwk = (array) $request->input('jwk', []);
        abort_unless($this->chat->validJwk($jwk), 422, 'Invalid key.');

        return response()->json(['me' => $this->chat->keyPayload($this->chat->registerKey('library', $lib, $jwk))]);
    }

    public function thread(int $learner)
    {
        $lib = $this->libraryId();
        $l = $this->learnerOf($learner);

        return view('library.chat.thread', [
            'learner' => $l, 'libraryId' => $lib, 'threads' => $this->threadList($lib), 'q' => '', 'active' => $l->id,
            'hasKey' => (bool) $this->chat->activeKey('library', $lib),
        ]);
    }

    /** Register the owner's public key; returns it with the learner's active key (null = learner has not opened chat). */
    public function registerKey(Request $request, int $learner)
    {
        $lib = $this->libraryId();
        $l = $this->learnerOf($learner);
        $jwk = (array) $request->input('jwk', []);
        abort_unless($this->chat->validJwk($jwk), 422, 'Invalid key.');
        $mine = $this->chat->registerKey('library', $lib, $jwk);
        $thread = $this->chat->threadFor($l);

        return response()->json([
            'me' => $this->chat->keyPayload($mine),
            'learner' => $this->chat->keyPayload($this->chat->activeKey('learner', $l->id)),
            'thread_id' => $thread->id,
        ]);
    }

    public function messages(Request $request, int $learner)
    {
        $l = $this->learnerOf($learner);
        $thread = $this->chat->threadFor($l);

        return response()->json($this->chat->messages($thread, (int) $request->query('since', 0)) + [
            'learner' => $this->chat->keyPayload($this->chat->activeKey('learner', $l->id)),
        ]);
    }

    public function send(Request $request, int $learner)
    {
        $l = $this->learnerOf($learner);
        $thread = $this->chat->threadFor($l);
        abort_unless($this->chat->activeKey('learner', $l->id), 409, 'This student has not opened chat yet.');
        $m = $this->chat->send($thread, 'library', $this->chat->validateSend($request));

        return response()->json(['id' => $m->id]);
    }

    public function unread()
    {
        return response()->json($this->chat->librarySummary($this->libraryId()) + [
            'links' => ['chat' => route('chat.index')],
        ])->header('Cache-Control', 'no-store');
    }

    public function upload(Request $request, int $learner)
    {
        $l = $this->learnerOf($learner);
        $thread = $this->chat->threadFor($l);
        abort_unless($this->chat->activeKey('learner', $l->id), 409, 'This student has not opened chat yet.');

        return response()->json(['id' => $this->chat->storeFile($thread, 'library', $request->getContent())->token]);
    }

    public function file(int $learner, string $token)
    {
        $f = $this->chat->fileOf($this->chat->threadFor($this->learnerOf($learner)), $token);

        return response(\Illuminate\Support\Facades\Storage::disk('local')->get($f->path), 200, ['Content-Type' => 'application/octet-stream', 'Cache-Control' => 'private, max-age=3600', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function read(int $learner)
    {
        $thread = $this->chat->threadFor($this->learnerOf($learner));

        return response()->json(['marked' => $this->chat->markRead($thread, 'library')]);
    }
}
