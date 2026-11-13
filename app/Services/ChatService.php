<?php

namespace App\Services;

use App\Models\ChatKey;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\Learner;
use App\Models\ChatFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Relay for the end-to-end encrypted learner <-> library owner chat.
 * It only ever sees public keys, ciphertext and metadata (sender, time, read state).
 */
class ChatService
{
    public const MAX_CIPHERTEXT = 6000; // base64 of ~2000 chars of UTF-8 text + GCM tag, with headroom

    public function activeKey(string $type, int $id): ?ChatKey
    {
        return ChatKey::where('owner_type', $type)->where('owner_id', $id)->whereNull('revoked_at')->orderByDesc('id')->first();
    }

    /** Store a public key. A new, different key revokes the old one (the other side is warned in the UI). */
    public function registerKey(string $type, int $id, array $jwk): ChatKey
    {
        $clean = ['kty' => 'EC', 'crv' => 'P-256', 'x' => (string) ($jwk['x'] ?? ''), 'y' => (string) ($jwk['y'] ?? '')];
        $fingerprint = hash('sha256', $clean['x'] . '.' . $clean['y']);

        $current = $this->activeKey($type, $id);
        if ($current && $current->fingerprint === $fingerprint) {
            return $current;
        }
        ChatKey::where('owner_type', $type)->where('owner_id', $id)->whereNull('revoked_at')->update(['revoked_at' => now()]);

        return ChatKey::create(['owner_type' => $type, 'owner_id' => $id, 'public_jwk' => $clean, 'fingerprint' => $fingerprint]);
    }

    public function validJwk(array $jwk): bool
    {
        return ($jwk['kty'] ?? null) === 'EC' && ($jwk['crv'] ?? null) === 'P-256'
            && preg_match('/^[A-Za-z0-9_-]{42,46}$/', (string) ($jwk['x'] ?? '')) === 1
            && preg_match('/^[A-Za-z0-9_-]{42,46}$/', (string) ($jwk['y'] ?? '')) === 1;
    }

    public function threadFor(Learner $learner): ChatThread
    {
        return ChatThread::firstOrCreate(['learner_id' => $learner->id], ['library_id' => $learner->library_id]);
    }

    public function keyPayload(?ChatKey $k): ?array
    {
        return $k ? ['id' => $k->id, 'jwk' => $k->public_jwk, 'fingerprint' => $k->fingerprint] : null;
    }

    /** Messages after $since, with the public keys needed to decrypt them. */
    public function messages(ChatThread $thread, int $since = 0): array
    {
        $rows = ChatMessage::where('thread_id', $thread->id)->where('id', '>', $since)->orderBy('id')->limit(200)->get();
        $keyIds = $rows->pluck('sender_key_id')->merge($rows->pluck('recipient_key_id'))->unique()->all();
        $keys = ChatKey::whereIn('id', $keyIds)->get()->mapWithKeys(fn ($k) => [$k->id => ['jwk' => $k->public_jwk, 'fingerprint' => $k->fingerprint, 'owner_type' => $k->owner_type]]);

        return [
            'messages' => $rows->map(fn ($m) => [
                'id' => $m->id, 'sender' => $m->sender_type, 'ciphertext' => $m->ciphertext, 'iv' => $m->iv,
                'sender_key_id' => $m->sender_key_id, 'recipient_key_id' => $m->recipient_key_id,
                'read' => (bool) $m->read_at, 'at' => $m->created_at?->toIso8601String(),
            ])->values(),
            'keys' => $keys,
        ];
    }

    public function validateSend(Request $request): array
    {
        return $request->validate([
            'ciphertext' => 'required|string|max:' . self::MAX_CIPHERTEXT,
            'iv' => ['required', 'string', 'regex:/^[A-Za-z0-9+\/=_-]{14,24}$/'],
            'sender_key_id' => 'required|integer',
            'recipient_key_id' => 'required|integer',
        ]);
    }

    public function send(ChatThread $thread, string $sender, array $d): ChatMessage
    {
        $mine = $sender;
        $theirs = $sender === 'learner' ? 'library' : 'learner';
        $senderKey = ChatKey::findOrFail($d['sender_key_id']);
        $recipientKey = ChatKey::findOrFail($d['recipient_key_id']);
        $ownerOf = fn (ChatKey $k) => $k->owner_type === 'learner' ? $thread->learner_id : $thread->library_id;
        abort_unless($senderKey->owner_type === $mine && $ownerOf($senderKey) === $thread->{$mine === 'learner' ? 'learner_id' : 'library_id'}
            && $recipientKey->owner_type === $theirs && $ownerOf($recipientKey) === $thread->{$theirs === 'learner' ? 'learner_id' : 'library_id'}, 422, 'Wrong keys for this chat.');

        $m = ChatMessage::create([
            'thread_id' => $thread->id, 'sender_type' => $sender, 'ciphertext' => $d['ciphertext'], 'iv' => $d['iv'],
            'sender_key_id' => $senderKey->id, 'recipient_key_id' => $recipientKey->id, 'created_at' => now(),
        ]);
        $thread->update(['last_message_at' => now()]);

        return $m;
    }

    public const MAX_FILE = 10 * 1024 * 1024 + 64;       // 10 MB of content + GCM tag
    public const MAX_THREAD_FILES = 300 * 1024 * 1024;   // total ciphertext kept per conversation

    /** Store an already-encrypted attachment. The body is opaque bytes. */
    public function storeFile(ChatThread $thread, string $uploader, string $bytes): ChatFile
    {
        $size = strlen($bytes);
        abort_if($size < 17, 422, 'Empty file.');
        abort_if($size > self::MAX_FILE, 413, 'File is too big. The limit is 10 MB.');
        abort_if((int) ChatFile::where('thread_id', $thread->id)->sum('size') + $size > self::MAX_THREAD_FILES, 413, 'This chat has reached its file storage limit.');

        $token = (string) Str::uuid();
        $path = 'chat/' . $thread->id . '/' . $token . '.bin';
        Storage::disk('local')->put($path, $bytes);

        return ChatFile::create(['token' => $token, 'thread_id' => $thread->id, 'uploader_type' => $uploader, 'size' => $size, 'path' => $path, 'created_at' => now()]);
    }

    public function fileOf(ChatThread $thread, string $token): ChatFile
    {
        abort_unless(Str::isUuid($token), 404);

        return ChatFile::where('thread_id', $thread->id)->where('token', $token)->firstOrFail();
    }

    /** Mark everything the other side sent as read. */
    public function markRead(ChatThread $thread, string $reader): int
    {
        $other = $reader === 'learner' ? 'library' : 'learner';

        return ChatMessage::where('thread_id', $thread->id)->where('sender_type', $other)->whereNull('read_at')->update(['read_at' => now()]);
    }

    /** Light summary for the notifier: unread count and the newest unread message id from the library. */
    public function learnerSummary(int $learnerId): array
    {
        $q = ChatMessage::join('chat_threads', 'chat_threads.id', '=', 'chat_messages.thread_id')
            ->where('chat_threads.learner_id', $learnerId)->where('chat_messages.sender_type', 'library')->whereNull('chat_messages.read_at');

        return ['unread' => (int) (clone $q)->count(), 'latest' => (int) (clone $q)->max('chat_messages.id')];
    }

    /** Owner summary: how many chats have unread messages, total unread, newest unread message id, newest senders. */
    public function librarySummary(int $libraryId): array
    {
        $rows = ChatMessage::join('chat_threads', 'chat_threads.id', '=', 'chat_messages.thread_id')
            ->join('learners', 'learners.id', '=', 'chat_threads.learner_id')
            ->where('chat_threads.library_id', $libraryId)->where('chat_messages.sender_type', 'learner')->whereNull('chat_messages.read_at')
            ->groupBy('chat_threads.learner_id', 'learners.name')
            ->selectRaw('chat_threads.learner_id as learner_id, learners.name as name, count(*) as c, max(chat_messages.id) as last_id')
            ->orderByDesc('last_id')->get();

        return [
            'chats' => $rows->count(),
            'unread' => (int) $rows->sum('c'),
            'latest' => (int) ($rows->max('last_id') ?? 0),
            'recent' => $rows->take(3)->map(fn ($r) => ['learner_id' => $r->learner_id, 'name' => $r->name, 'count' => (int) $r->c])->values(),
        ];
    }

    public function unreadForLearner(int $learnerId): int
    {
        return (int) ChatMessage::join('chat_threads', 'chat_threads.id', '=', 'chat_messages.thread_id')
            ->where('chat_threads.learner_id', $learnerId)->where('chat_messages.sender_type', 'library')->whereNull('chat_messages.read_at')->count();
    }
}
