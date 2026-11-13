<?php

namespace App\Http\Controllers;

use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Learner side of the end-to-end encrypted chat. Only public keys and ciphertext pass through here. */
class LearnerChatController extends Controller
{
    public function __construct(private ChatService $chat)
    {
    }

    private function learner()
    {
        return Auth::guard('learner')->user();
    }

    public function page()
    {
        $learner = $this->learner();

        return view('learner.chat', [
            'learnerId' => $learner->id,
            'libraryKey' => $this->chat->keyPayload($this->chat->activeKey('library', (int) $learner->library_id)),
        ]);
    }

    /** Register this browser's public key. Returns my key row and the library's active key (null = chat not on yet). */
    public function registerKey(Request $request)
    {
        $learner = $this->learner();
        $jwk = (array) $request->input('jwk', []);
        abort_unless($this->chat->validJwk($jwk), 422, 'Invalid key.');
        $mine = $this->chat->registerKey('learner', $learner->id, $jwk);

        return response()->json([
            'me' => $this->chat->keyPayload($mine),
            'library' => $this->chat->keyPayload($this->chat->activeKey('library', (int) $learner->library_id)),
            'thread_id' => $this->chat->threadFor($learner)->id,
        ]);
    }

    public function messages(Request $request)
    {
        $thread = $this->chat->threadFor($this->learner());

        return response()->json($this->chat->messages($thread, (int) $request->query('since', 0)) + [
            'library' => $this->chat->keyPayload($this->chat->activeKey('library', (int) $thread->library_id)),
        ]);
    }

    public function send(Request $request)
    {
        $learner = $this->learner();
        $thread = $this->chat->threadFor($learner);
        abort_unless($this->chat->activeKey('library', (int) $thread->library_id), 409, 'Your library has not turned on chat yet.');
        $m = $this->chat->send($thread, 'learner', $this->chat->validateSend($request));

        return response()->json(['id' => $m->id]);
    }

    /** Tiny endpoint for the page-wide notifier (unread count + newest unread id; never any message text). */
    public function unread()
    {
        return response()->json($this->chat->learnerSummary($this->learner()->id))->header('Cache-Control', 'no-store');
    }

    /** Upload an encrypted attachment (raw bytes). */
    public function upload(Request $request)
    {
        $thread = $this->chat->threadFor($this->learner());
        abort_unless($this->chat->activeKey('library', (int) $thread->library_id), 409, 'Your library has not turned on chat yet.');

        return response()->json(['id' => $this->chat->storeFile($thread, 'learner', $request->getContent())->token]);
    }

    public function file(string $token)
    {
        $f = $this->chat->fileOf($this->chat->threadFor($this->learner()), $token);

        return response(\Illuminate\Support\Facades\Storage::disk('local')->get($f->path), 200, ['Content-Type' => 'application/octet-stream', 'Cache-Control' => 'private, max-age=3600', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function read()
    {
        $thread = $this->chat->threadFor($this->learner());

        return response()->json(['marked' => $this->chat->markRead($thread, 'learner')]);
    }
}
