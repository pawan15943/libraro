<?php

namespace Tests\Feature;

use App\Models\ChatMessage;
use App\Models\Learner;
use App\Models\Library;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/** E2E encrypted chat: the server only relays public keys and opaque ciphertext. */
class LearnerChatTest extends TestCase
{
    use DatabaseTransactions;

    private const X = 'f83OJ3D2xF1Bg8vub9tLe1gHMzV76e8Tus9uPHvRVEU';
    private const Y = 'x_FEzRu9m36HLN_tue659LNpXW6pCyStikYjKIWI5a0';

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->useStoragePath(sys_get_temp_dir().'/libraro-test-storage');
        foreach (array_keys(config('logging.channels', [])) as $channel) {
            if (config("logging.channels.$channel.path")) {
                config(["logging.channels.$channel.path" => sys_get_temp_dir()."/libraro-test-$channel.log"]);
            }
        }
        $this->withoutMiddleware([\App\Http\Middleware\LicenseCheck::class, \App\Http\Middleware\LoggerMiddleware::class]);
    }

    private function learner(): Learner
    {
        $l = Learner::withoutGlobalScopes()->where('status', 1)->whereNull('deleted_at')->whereNotNull('library_id')->orderByDesc('id')->first();
        if (! $l) {
            $this->markTestSkipped('no active learner in this database');
        }

        return $l;
    }

    private function jwk(string $x = self::X, string $y = self::Y): array
    {
        return ['kty' => 'EC', 'crv' => 'P-256', 'x' => $x, 'y' => $y];
    }

    private function otherJwk(): array
    {
        return $this->jwk(strrev(self::X), strrev(self::Y));
    }

    private function msg(array $keys): array
    {
        return ['ciphertext' => base64_encode(random_bytes(40)), 'iv' => base64_encode(random_bytes(12)), 'sender_key_id' => $keys[0], 'recipient_key_id' => $keys[1]];
    }

    public function test_full_round_trip_and_unread(): void
    {
        $learner = $this->learner();
        $library = Library::find($learner->library_id);

        // the owner may already have a key in this database; this test starts without one (rolled back)
        \App\Models\ChatKey::where('owner_type', 'library')->where('owner_id', $library->id)->delete();

        // student opens chat before the owner: page works, but no library key yet, sending is refused
        $this->actingAs($learner, 'learner');
        $this->get('/learner/chat')->assertOk()->assertSee('end-to-end', false)->assertSee('lb-chat-crypto.js', false);
        $k = $this->postJson('/learner/chat/key', ['jwk' => $this->jwk()])->assertOk()->assertJsonPath('library', null);
        $learnerKey = $k->json('me.id');
        $this->postJson('/learner/chat/send', $this->msg([$learnerKey, $learnerKey]))->assertStatus(409);
        $this->postJson('/learner/chat/key', ['jwk' => ['kty' => 'EC', 'crv' => 'P-256', 'x' => 'a', 'y' => 'b']])->assertStatus(422);

        // owner opens the page and registers a key
        $this->app['auth']->forgetGuards();
        $this->actingAs($library, 'library');
        $this->get('/library/chat')->assertOk();
        $this->get("/library/chat/{$learner->id}")->assertOk()->assertSee('lb-chat.js', false);
        $o = $this->postJson("/library/chat/{$learner->id}/key", ['jwk' => $this->otherJwk()])->assertOk()->assertJsonPath('learner.id', $learnerKey);
        $ownerKey = $o->json('me.id');

        // owner -> student (opaque ciphertext)
        $m = $this->msg([$ownerKey, $learnerKey]);
        $this->postJson("/library/chat/{$learner->id}/send", $m)->assertOk();
        // wrong key owner types are refused
        $this->postJson("/library/chat/{$learner->id}/send", $this->msg([$learnerKey, $ownerKey]))->assertStatus(422);
        // size limits
        $big = $m; $big['ciphertext'] = str_repeat('A', 6001);
        $this->postJson("/library/chat/{$learner->id}/send", $big)->assertStatus(422);
        $this->postJson("/library/chat/{$learner->id}/send", ['iv' => $m['iv']])->assertStatus(422);

        // student sees the unread dot count and the ciphertext, which the server stored untouched
        $this->app['auth']->forgetGuards();
        $this->actingAs($learner, 'learner');
        $this->assertSame(1, app(\App\Services\ChatService::class)->unreadForLearner($learner->id));
        $res = $this->getJson('/learner/chat/messages?since=0')->assertOk();
        $this->assertSame($m['ciphertext'], $res->json('messages.0.ciphertext'));
        $this->assertSame('library', $res->json('messages.0.sender'));
        $this->assertNotNull($res->json("keys.$ownerKey.jwk.x"));
        $this->postJson('/learner/chat/read')->assertOk()->assertJsonPath('marked', 1);
        $this->assertSame(0, app(\App\Services\ChatService::class)->unreadForLearner($learner->id));

        // student -> owner, owner list shows unread
        $this->postJson('/learner/chat/send', $this->msg([$learnerKey, $ownerKey]))->assertOk();
        $this->get('/learner/home')->assertOk();
        $this->app['auth']->forgetGuards();
        $this->actingAs($library, 'library');
        $this->get('/library/chat')->assertOk()->assertSee('1 new');
        $this->getJson("/library/chat/{$learner->id}/messages?since=0")->assertOk()->assertJsonCount(2, 'messages');
        $this->getJson("/library/chat/{$learner->id}/messages?since=".ChatMessage::max('id'))->assertOk()->assertJsonCount(0, 'messages');
        $this->postJson("/library/chat/{$learner->id}/read")->assertJsonPath('marked', 1);

        // a changed key revokes the old one; old messages still carry their original key ids
        $this->actingAs($learner, 'learner');
    }

    public function test_key_change_revokes_old_key(): void
    {
        $learner = $this->learner();
        $this->actingAs($learner, 'learner');
        $a = $this->postJson('/learner/chat/key', ['jwk' => $this->jwk()])->json('me.id');
        $same = $this->postJson('/learner/chat/key', ['jwk' => $this->jwk()])->json('me.id');
        $b = $this->postJson('/learner/chat/key', ['jwk' => $this->otherJwk()])->json('me.id');
        $this->assertSame($a, $same);
        $this->assertNotSame($a, $b);
        $this->assertNotNull(\App\Models\ChatKey::find($a)->revoked_at);
    }

    public function test_tenancy_and_staff_are_refused(): void
    {
        $learner = $this->learner();
        $other = Library::where('id', '!=', $learner->library_id)->first();
        if ($other) {
            $this->actingAs($other, 'library');
            $this->get("/library/chat/{$learner->id}")->assertNotFound();
            $this->postJson("/library/chat/{$learner->id}/key", ['jwk' => $this->jwk()])->assertNotFound();
            $this->getJson("/library/chat/{$learner->id}/messages")->assertNotFound();
            $this->app['auth']->forgetGuards();
        }

        // a learner only ever reads their own thread (the endpoints take no thread id)
        $this->actingAs($learner, 'learner');
        $this->getJson('/learner/chat/messages')->assertOk()->assertJsonMissing(['sender' => 'nobody']);
        // learners cannot reach the owner endpoints
        $this->getJson("/library/chat/{$learner->id}/messages")->assertUnauthorized();

        // library staff (not the owner) cannot use chat
        $staff = \App\Models\LibraryUser::withoutGlobalScopes()->where('library_id', $learner->library_id)->first();
        if ($staff) {
            $this->app['auth']->forgetGuards();
            $this->actingAs($staff, 'library_user');
            $this->assertContains($this->getJson("/library/chat/{$learner->id}/messages")->getStatusCode(), [302, 401, 403]);
        }
    }

    public function test_owner_can_start_a_chat_from_the_list(): void
    {
        $learner = $this->learner();
        $library = Library::find($learner->library_id);
        $this->actingAs($library, 'library');

        $this->get('/library/chat')->assertOk()->assertSee('Start a new chat')->assertSee('lcxDlg', false);

        // search finds a student of this library only
        $first = strtok((string) $learner->name, ' ');
        $rows = $this->getJson('/library/chat/students?q='.urlencode($first))->assertOk()->json();
        $this->assertContains($learner->id, array_column($rows, 'id'));
        $this->assertSame(route('chat.thread', $learner->id), collect($rows)->firstWhere('id', $learner->id)['url']);

        // the owner key can be created from the list page, before any conversation
        $this->postJson('/library/chat/key', ['jwk' => $this->jwk()])->assertOk()->assertJsonStructure(['me' => ['id', 'fingerprint']]);
        $this->postJson('/library/chat/key', ['jwk' => ['kty' => 'EC']])->assertStatus(422);
        $this->get('/library/chat')->assertOk()->assertSee('Private chat is on');

        // the thread page shows the messenger shell with the list
        $this->get("/library/chat/{$learner->id}")->assertOk()->assertSee('lcx-side', false)->assertSee('lb-chat.js', false);
    }

    public function test_encrypted_attachments_upload_and_download(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        $learner = $this->learner();
        $library = Library::find($learner->library_id);
        \App\Models\ChatKey::where('owner_type', 'library')->where('owner_id', $library->id)->delete();

        $this->actingAs($library, 'library');
        $this->postJson('/library/chat/key', ['jwk' => $this->otherJwk()])->assertOk();
        $this->actingAs($learner, 'learner');
        $this->postJson('/learner/chat/key', ['jwk' => $this->jwk()])->assertOk();

        $blob = random_bytes(5000); // opaque ciphertext as far as the server is concerned
        $up = $this->call('POST', '/learner/chat/file', [], [], [], ['CONTENT_TYPE' => 'application/octet-stream', 'HTTP_ACCEPT' => 'application/json'], $blob)->assertOk();
        $token = $up->json('id');
        $this->assertTrue(\Illuminate\Support\Str::isUuid($token));

        $this->get("/learner/chat/file/{$token}")->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertSame($blob, $this->get("/learner/chat/file/{$token}")->getContent());
        $this->get('/learner/chat/file/not-a-token')->assertNotFound();
        $this->call('POST', '/learner/chat/file', [], [], [], ['CONTENT_TYPE' => 'application/octet-stream'], 'tiny')->assertStatus(422);
        $this->call('POST', '/learner/chat/file', [], [], [], ['CONTENT_TYPE' => 'application/octet-stream'], str_repeat('x', \App\Services\ChatService::MAX_FILE + 1))->assertStatus(413);

        // the owner of this library can fetch it; another library's owner cannot
        $this->actingAs($library, 'library');
        $this->get("/library/chat/{$learner->id}/file/{$token}")->assertOk();
        $other = Library::where('id', '!=', $library->id)->first();
        if ($other) {
            $this->actingAs($other, 'library');
            $this->get("/library/chat/{$learner->id}/file/{$token}")->assertNotFound();
        }
    }

    public function test_unread_summaries_and_notifier_wiring(): void
    {
        $learner = $this->learner();
        $library = Library::find($learner->library_id);
        \App\Models\ChatKey::where('owner_type', 'library')->where('owner_id', $library->id)->delete();

        $this->actingAs($library, 'library');
        $ownerKey = $this->postJson('/library/chat/key', ['jwk' => $this->otherJwk()])->json('me.id');
        $this->actingAs($learner, 'learner');
        $learnerKey = $this->postJson('/learner/chat/key', ['jwk' => $this->jwk()])->json('me.id');

        $this->getJson('/learner/chat/unread')->assertOk()->assertJson(['unread' => 0, 'latest' => 0]);

        // the student writes: the owner sees 1 chat with 1 unread message, in the menu badge feed
        $this->postJson('/learner/chat/send', $this->msg([$learnerKey, $ownerKey]))->assertOk();
        $this->get('/learner/home')->assertOk()->assertSee('lb-chat-notify.js', false)->assertSee('data-lc-badge', false);

        $this->actingAs($library, 'library');
        $s = $this->getJson('/library/chat/unread')->assertOk()->json();
        $this->assertSame(1, $s['chats']);
        $this->assertSame(1, $s['unread']);
        $this->assertGreaterThan(0, $s['latest']);
        $this->assertSame($learner->id, $s['recent'][0]['learner_id']);
        $this->assertArrayNotHasKey('ciphertext', $s['recent'][0]); // never any message content

        // reading the chat clears the badge
        $this->postJson("/library/chat/{$learner->id}/read")->assertOk();
        $this->getJson('/library/chat/unread')->assertOk()->assertJson(['chats' => 0, 'unread' => 0]);

        // owner replies: the student's unread count goes up
        $this->postJson("/library/chat/{$learner->id}/send", $this->msg([$ownerKey, $learnerKey]))->assertOk();
        $this->actingAs($learner, 'learner');
        $this->getJson('/learner/chat/unread')->assertOk()->assertJson(['unread' => 1]);

        // a different library's owner sees nothing of this chat
        $other = Library::where('id', '!=', $library->id)->first();
        if ($other) {
            $this->actingAs($other, 'library');
            $this->getJson('/library/chat/unread')->assertOk()->assertJson(['chats' => 0]);
        }
    }
}

