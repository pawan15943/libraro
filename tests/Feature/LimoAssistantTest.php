<?php

namespace Tests\Feature;

use App\Models\Library;
use App\Models\LibraryUser;
use App\Models\User;
use App\Services\LibraroAiService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Limo, the library AI assistant: intent routing (English / Hinglish / Hindi), staff
 * permissions, follow-up questions, feedback, unanswered-question log, the editable
 * knowledge base, PDF export and the admin pages.
 *
 * Runs on the local database inside a rolled-back transaction. Set LIMO_LIBRARY_ID to
 * pick the library; otherwise the branch with the most learners is used.
 */
class LimoAssistantTest extends TestCase
{
    use DatabaseTransactions;

    private Library $library;

    private int $branchId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->useStoragePath(sys_get_temp_dir().'/libraro-test-storage');
        foreach (['framework/views', 'framework/cache', 'app', 'logs'] as $dir) {
            @mkdir(storage_path($dir), 0777, true);
        }
        config(['view.compiled' => storage_path('framework/views')]);
        foreach (array_keys(config('logging.channels', [])) as $channel) {
            if (config("logging.channels.$channel.path")) {
                config(["logging.channels.$channel.path" => sys_get_temp_dir()."/libraro-test-$channel.log"]);
            }
        }
        // No phpunit.xml in this repo, so tests may not run as APP_ENV=testing: switch off CSRF and file cache here
        $this->withoutMiddleware([\App\Http\Middleware\LicenseCheck::class, \App\Http\Middleware\LoggerMiddleware::class, \App\Http\Middleware\VerifyCsrfToken::class]);
        config(['cache.default' => 'array', 'session.driver' => 'array']);
        // APP_URL points at a sub-folder (XAMPP); test requests are served from the root
        config(['app.url' => 'http://localhost']);
        \Illuminate\Support\Facades\URL::forceRootUrl('http://localhost');

        $branch = env('LIMO_LIBRARY_ID')
            ? DB::table('branches')->where('library_id', env('LIMO_LIBRARY_ID'))->orderBy('id')->value('id')
            : DB::table('learner_detail')->whereNull('deleted_at')->groupBy('branch_id')->orderByRaw('COUNT(*) DESC')->value('branch_id');
        $libraryId = $branch ? DB::table('branches')->where('id', $branch)->value('library_id') : null;
        $library = $libraryId ? Library::find($libraryId) : null;
        if (! $library || ! DB::getSchemaBuilder()->hasTable('ai_knowledge_entries')) {
            $this->markTestSkipped('needs a library with learners and the Limo migration');
        }
        $library->current_branch = (int) $branch;
        $this->library = $library;
        $this->branchId = (int) $branch;
    }

    /** Relative URL of a named route (the app runs in a sub-folder). */
    private function u(string $name, $params = []): string
    {
        return route($name, $params, false);
    }

    private function askAs(string $q, string $lang = 'auto'): array
    {
        return app(LibraroAiService::class)->processQuery($q, $this->library->id, $this->branchId, $lang)['reply'];
    }

    private function owner(): void
    {
        Auth::guard('library_user')->logout();
        Auth::guard('library')->setUser($this->library);
    }

    private function staff(array $permissions): LibraryUser
    {
        $id = DB::table('library_users')->insertGetId([
            'library_id' => $this->library->id, 'name' => 'Limo Test Staff', 'mobile' => '9000000000',
            'email' => 'limo.staff.'.uniqid().'@example.com', 'password' => bcrypt('secret'),
            'branch_id' => $this->branchId, 'current_branch' => $this->branchId, 'status' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach (DB::table('permissions')->whereIn('name', $permissions)->pluck('id') as $pid) {
            DB::table('library_user_permissions')->insert(['library_user_id' => $id, 'permission_id' => $pid]);
        }
        $user = LibraryUser::find($id);
        Auth::guard('library')->logout();
        Auth::guard('library_user')->setUser($user);

        return $user;
    }

    /** Question → expected intent, in English, Hinglish and Hindi. */
    public static function questions(): array
    {
        return [
            ["Today's collection", 'collection'], ['aaj ka collection kitna hai', 'collection'], ['आज का कलेक्शन कितना है?', 'collection'],
            ['cash kitna aaya aaj', 'collection'], ['colection today', 'collection'],
            ['Show due students list', 'dues'], ['बकाया छात्रों की सूची दिखाओ', 'dues'], ['Who has to pay', 'dues'], ['pending fees kitni hai', 'dues'],
            ['Whose plan expires in next 7 days?', 'expiring'], ['अगले 30 दिन में किसका प्लान खत्म होगा?', 'expiring'],
            ['Recently expired learners', 'expired'], ['This month expenses', 'expenses'], ['इस महीने का मुनाफा', 'profit'],
            ['Refunds this month', 'refunds'], ['Renewals this month', 'renewals'], ['This month vs last month', 'compare'],
            ['Show vacant seats', 'seats'], ['vaccant seats', 'seats'], ['Who is on seat 1?', 'seat'], ['सीट 1 पर कौन है?', 'seat'],
            ['Show floors', 'floors'], ['Shift timings', 'shifts'], ['Shift wise learners', 'shiftLearners'], ['full day me kitne students', 'shiftLearners'],
            ['Plan fees', 'prices'], ['monthly fees kitni hai', 'prices'], ['Frozen learners', 'frozen'], ['Locker list', 'lockers'],
            ['Enquiries and online bookings', 'enquiries'], ['My branches', 'branches'], ['Staff list', 'staff'], ['Learner feedback', 'feedback'],
            ['Branch details', 'profile'], ['upi id kya hai', 'profile'], ['My Libraro plan', 'subscription'], ['Who came today?', 'attendance'],
            ['आज कौन नहीं आया?', 'attendance'], ['New admissions this month', 'admissions'], ["Today's summary", 'summary'], ['meri library kaisi chal rahi hai', 'summary'],
            ['How to book a seat?', 'guide'], ['सीट कैसे बदलें?', 'guide'], ['ID कार्ड कैसे बनाएं?', 'guide'], ['How to freeze a plan?', 'guide'],
            ['What is the VIP plan?', 'guide'], ['I deleted a student by mistake', 'guide'], ['How to change plan price?', 'guide'],
            ['How to get more admissions?', 'tips'], ['बकाया कैसे वसूलें?', 'tips'], ['Library rules', 'tips'], ['How to switch branch?', 'tips'],
            ['help', 'help'], ['tell me a joke', 'unknown'],
        ];
    }

    public function test_questions_reach_the_right_answer(): void
    {
        $this->owner();
        $wrong = [];
        foreach (self::questions() as [$q, $intent]) {
            $got = $this->askAs($q)['intent'];
            if ($got !== $intent) {
                $wrong[] = "\"$q\" → $got (expected $intent)";
            }
        }
        $this->assertSame([], $wrong, "Limo routed these questions wrongly:\n".implode("\n", $wrong));
    }

    public function test_answers_in_the_language_of_the_question_or_the_one_chosen(): void
    {
        $this->owner();
        $this->assertSame('hi', $this->askAs('आज का कलेक्शन')['lang']);
        $this->assertSame('hi', $this->askAs('aaj ka collection kitna hai')['lang']);
        $this->assertSame('en', $this->askAs("today's collection")['lang']);
        $this->assertSame('hi', $this->askAs("today's collection", 'hi')['lang']);
        $this->assertSame('en', $this->askAs('आज का कलेक्शन', 'en')['lang']);
    }

    public function test_follow_up_questions_reuse_the_previous_answer(): void
    {
        $this->owner();
        $this->askAs("today's collection");
        $this->assertStringContainsString('Yesterday', $this->askAs('aur kal ka?', 'en')['title']);
        $this->assertSame('collection', $this->askAs('and last month?')['intent']);

        $this->askAs('expiring in 7 days');
        $this->assertSame('expiring', $this->askAs('and 30 days?')['intent']);

        $name = DB::table('learners')->where('branch_id', $this->branchId)->whereNull('deleted_at')->orderBy('id')->value('name');
        $card = $this->askAs((string) $name);
        if ($card['intent'] === 'learner') {
            $this->assertSame('learner', $this->askAs('uska bakaya?')['intent']);
        }
    }

    public function test_staff_only_see_what_their_permissions_allow(): void
    {
        $this->staff(['Learner List']);

        foreach (["today's collection", 'due list', 'profit', 'staff list', 'my libraro plan', 'my branches'] as $q) {
            $this->assertSame('denied', $this->askAs($q)['intent'], "staff without permission could see: $q");
        }
        $this->assertSame('shiftLearners', $this->askAs('shift wise learners')['intent']);

        $summary = $this->askAs('summary');
        $labels = collect($summary['blocks'])->where('type', 'stats')->flatMap(fn ($b) => array_column($b['items'], 'label'))->all();
        $this->assertNotContains("Today's collection", $labels);
        $this->assertNotContains('Pending dues', $labels);
    }

    public function test_staff_with_permission_can_see_dues(): void
    {
        $this->staff(['Pending Payment Report']);
        $this->assertSame('dues', $this->askAs('due list')['intent']);
    }

    public function test_chat_endpoints_feedback_and_history_are_per_user(): void
    {
        $this->actingAs($this->library, 'library');

        $res = $this->postJson($this->u('ai.chat.send'), ['query' => "today's summary", 'lang' => 'en'])->assertOk();
        $id = $res->json('reply.id');
        $this->assertNotNull($id);
        $this->assertSame('summary', $res->json('reply.intent'));

        $this->postJson($this->u('ai.chat.feedback'), ['id' => $id, 'value' => -1])->assertOk();
        $this->assertSame(-1, (int) DB::table('ai_chat_histories')->where('id', $id)->value('feedback'));

        $history = $this->getJson($this->u('ai.chat.history'))->assertOk()->json('history');
        $this->assertSame(-1, (int) collect($history)->firstWhere('id', $id)['reply']['feedback']);

        $this->getJson($this->u('ai.chat.alerts'))->assertOk()->assertJsonStructure(['success', 'alerts']);

        // another library's answer cannot be voted on or exported
        $other = DB::table('ai_chat_histories')->where('library_id', '!=', $this->library->id)->where('sender', 'assistant')->value('id');
        if ($other) {
            $this->postJson($this->u('ai.chat.feedback'), ['id' => $other, 'value' => 1])->assertNotFound();
            $this->get($this->u('ai.chat.export', $other))->assertNotFound();
        }

        $this->postJson($this->u('ai.chat.clear'))->assertOk();
        $this->assertSame([], $this->getJson($this->u('ai.chat.history'))->json('history'));
    }

    public function test_answer_exports_as_pdf(): void
    {
        $this->actingAs($this->library, 'library');
        $id = $this->postJson($this->u('ai.chat.send'), ['query' => 'आज का सारांश', 'lang' => 'hi'])->json('reply.id');

        $res = $this->get($this->u('ai.chat.export', $id))->assertOk();
        $this->assertSame('application/pdf', $res->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $res->getContent());
    }

    public function test_unanswered_questions_are_logged_and_knowledge_edits_go_live(): void
    {
        $this->owner();
        $id = $this->askAs('xyzzy blorp question')['id'];
        $this->assertSame('unknown', DB::table('ai_chat_histories')->where('id', $id)->value('intent'));
        $this->assertSame('xyzzy blorp question', DB::table('ai_chat_histories')->where('id', $id)->value('question'));

        DB::table('ai_knowledge_entries')->insert([
            'keys' => "xyzzy blorp\nजादुई सवाल", 'title_en' => 'Magic answer', 'title_hi' => 'जादुई जवाब',
            'steps_en' => "Step one\nStep two", 'steps_hi' => "पहला\nदूसरा", 'status' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $reply = $this->askAs('xyzzy blorp question');
        $this->assertSame('Magic answer', $reply['title']);
        $this->assertSame(['Step one', 'Step two'], $reply['blocks'][0]['en']);
    }

    public function test_admin_pages_manage_the_knowledge_base(): void
    {
        $admin = User::role('superadmin')->first() ?? User::first();
        if (! $admin) {
            $this->markTestSkipped('no admin user');
        }
        $this->actingAs($admin, 'web');

        $this->get($this->u('admin.limo.questions'))->assertOk()->assertSee('Questions to improve');
        $this->get($this->u('admin.limo.questions', ['tab' => 'disliked']))->assertOk();
        $this->get($this->u('admin.limo.knowledge'))->assertOk()->assertSee('Knowledge base');
        $this->get($this->u('admin.limo.knowledge.create', ['keys' => 'new question']))->assertOk()->assertSee('new question');

        $this->post($this->u('admin.limo.knowledge.store'), [
            'keys' => 'limo admin test', 'title_en' => 'Admin test', 'title_hi' => 'एडमिन टेस्ट',
            'steps_en' => 'Do this', 'steps_hi' => 'यह करें', 'links' => 'library.home | Dashboard | डैशबोर्ड', 'status' => 1,
        ])->assertRedirect($this->u('admin.limo.knowledge'));
        $entry = DB::table('ai_knowledge_entries')->where('title_en', 'Admin test')->first();
        $this->assertNotNull($entry);
        $this->assertSame([['library.home', 'Dashboard', 'डैशबोर्ड']], json_decode($entry->links, true));

        $this->post($this->u('admin.limo.knowledge.store'), [
            'keys' => 'x', 'title_en' => 'x', 'title_hi' => 'x', 'steps_en' => 'x', 'steps_hi' => 'x', 'links' => 'no.such.route | a | b',
        ])->assertSessionHasErrors('links');

        $this->get($this->u('admin.limo.knowledge.edit', $entry->id))->assertOk()->assertSee('Admin test');
        $this->put($this->u('admin.limo.knowledge.update', $entry->id), [
            'keys' => 'limo admin test', 'title_en' => 'Admin test 2', 'title_hi' => 'एडमिन टेस्ट', 'steps_en' => 'Do this', 'steps_hi' => 'यह करें',
        ])->assertRedirect();
        $this->assertSame(0, (int) DB::table('ai_knowledge_entries')->where('id', $entry->id)->value('status'), 'unchecked status turns the answer off');

        $this->post($this->u('admin.limo.knowledge.toggle', $entry->id))->assertRedirect();
        $this->assertSame(1, (int) DB::table('ai_knowledge_entries')->where('id', $entry->id)->value('status'));

        $this->delete($this->u('admin.limo.knowledge.destroy', $entry->id))->assertRedirect($this->u('admin.limo.knowledge'));
        $this->assertFalse(DB::table('ai_knowledge_entries')->where('id', $entry->id)->exists());
    }

    public function test_api_requires_login(): void
    {
        $res = $this->getJson('/api/v1/library/ai/history');
        $status = $res->getStatusCode();
        $this->assertArrayNotHasKey('history', $res->json() ?? []);
        $this->assertContains($status, [401, 403], "the AI API answered without a login (status $status)");
    }
    /*
    |--------------------------------------------------------------------------
    | Support mode
    |--------------------------------------------------------------------------
    */

    private function support(string $q, ?string $shown = null, string $lang = 'auto'): array
    {
        return app(LibraroAiService::class)->processQuery($q, $this->library->id, $this->branchId, $lang, 'support', $shown)['reply'];
    }

    /** Problem → expected playbook title (English), in English, Hinglish and Hindi. */
    public static function problems(): array
    {
        return [
            ['Renew button is not showing', 'Renew button is not showing'], ['renew ka button nahi dikh raha', 'Renew button is not showing'], ['रिन्यू का बटन नहीं दिख रहा', 'Renew button is not showing'],
            ['seat book nahi ho rahi', 'Seat is not getting booked'], ['सीट बुक नहीं हो रही', 'Seat is not getting booked'],
            ['galti se student delete ho gaya', 'Deleted a learner by mistake'], ['whatsapp reminder nahi ja raha', 'WhatsApp / SMS reminder is not going'],
            ['staff ko menu nahi dikh raha', 'Staff cannot see a menu or option'], ['id card print nahi ho raha', 'ID card is not printing'],
            ['attendance qr kaam nahi kar raha', 'Attendance QR is not working'], ['bakaya galat dikh raha hai', 'Dues amount looks wrong'],
            ['change plan ka option nahi aa raha', 'Change plan / shift option is not showing'], ['upgrade nahi dikh raha', 'Upgrade plan option is not showing'],
            ['freeze nahi ho raha', 'Freeze / Unfreeze is not working'], ['plan in queue kyu likha hai', 'It says “Plan in Queue”'],
            ['reactivate nahi dikh raha', 'Reactivate option is not showing'], ['seat close nahi ho rahi', 'Cannot close or delete a seat'],
            ['koi seat khali nahi dikh rahi', 'No vacant seat is showing'], ['shift booking me nahi dikh rahi', 'A plan or shift is not showing in booking'],
            ['login nahi ho raha password bhool gaya', 'Cannot log in'], ['online booking nahi dikh rahi', 'Online bookings are not showing'],
            ['csv import nahi ho raha', 'Learner import is failing'], ['software band ho gaya subscription khatam', 'Libraro subscription / panel locked'],
            ['collection cash se match nahi ho raha', 'Collection does not match the cash'], ['gift days nahi jud rahe', 'Gift days are not working'],
            ['receipt download nahi ho rahi', 'Receipt is not downloading'], ['total seats galat dikh rahe', 'Seat count or seat numbers look wrong'],
        ];
    }

    public function test_support_recognises_problems_in_any_language(): void
    {
        $this->owner();
        $wrong = [];
        foreach (self::problems() as [$q, $title]) {
            $r = $this->support($q, null, 'en'); // English titles to compare; language is tested separately
            if ($r['intent'] !== 'support' || $r['title'] !== $title) {
                $wrong[] = "\"$q\" → {$r['intent']} / {$r['title']} (expected $title)";
            }
        }
        $this->assertSame([], $wrong, "Support routed these problems wrongly:\n".implode("\n", $wrong));
    }

    public function test_support_answers_in_the_owners_language_and_keeps_it(): void
    {
        $this->owner();
        $this->assertSame('hi', $this->support('renew ka button nahi dikh raha')['lang']);
        $this->assertSame('hi', $this->support('रिन्यू का बटन नहीं दिख रहा')['lang']);
        $name = DB::table('learners')->where('branch_id', $this->branchId)->whereNull('deleted_at')->orderBy('id')->value('name');
        $this->assertSame('hi', $this->support(explode(' ', (string) $name)[0])['lang'], 'a bare name keeps the conversation language');
        $this->assertSame('en', $this->support('Renew button is not showing')['lang']);
    }

    public function test_support_flow_steps_then_deeper_checks_then_hand_over(): void
    {
        $this->owner();
        $r = $this->support('renew button is not showing');
        $this->assertSame(['#solved', '#more'], array_column($r['choices'], 'query'));
        $this->assertSame('guide', collect($r['blocks'])->firstWhere('type', 'guide')['type']);

        $more = $this->support('#more', 'Still not working');
        $this->assertStringStartsWith('More checks', $more['title']);
        $this->assertContains('#human', array_column($more['choices'], 'query'));

        $human = $this->support('#more', 'Still not solved');
        $this->assertSame('support_human', $human['intent']);
        $wa = collect($human['choices'])->firstWhere('tone', 'wa')['url'];
        $this->assertStringStartsWith('https://wa.me/918114479678?text=', $wa);
        $this->assertStringContainsString(rawurlencode('Renew button is not showing'), $wa);

        $this->assertSame('support_solved', $this->support('#solved', 'Solved')['intent']);
    }

    public function test_a_new_problem_is_not_taken_as_a_reply_to_the_old_one(): void
    {
        $this->owner();
        $this->support('renew button is not showing');
        // contains "ho gaya" and "nahi ho raha" — still new problems, not "solved" / "still not working"
        $this->assertSame('Deleted a learner by mistake', $this->support('galti se student delete ho gaya', null, 'en')['title']);
        $this->assertSame('ID card is not printing', $this->support('id card print nahi ho raha', null, 'en')['title']);
        $this->assertSame('support_solved', $this->support('ho gaya thanks')['intent']);
    }

    public function test_support_checks_the_learners_record(): void
    {
        $this->owner();
        $name = DB::table('learner_detail as ld')->join('learners as l', 'l.id', '=', 'ld.learner_id')
            ->where('ld.branch_id', $this->branchId)->whereNull('l.deleted_at')->whereNull('ld.deleted_at')
            ->orderByDesc('ld.plan_end_date')->value('l.name');
        if (! $name) {
            $this->markTestSkipped('no learner with a plan');
        }
        $r = $this->support($name.' ka renew button nahi dikh raha', null, 'en');
        $checks = collect($r['blocks'])->firstWhere('type', 'checks');
        $this->assertNotNull($checks, 'Limo should report what it found for the learner');
        $this->assertStringContainsString($name, $checks['caption']);
        $this->assertStringStartsWith('Plan:', $checks['items'][0]['text']);
    }

    public function test_support_topics_and_unknown_problems_lead_somewhere(): void
    {
        $this->owner();
        $topic = $this->support('#cat plan', 'Renew & plan change');
        $this->assertSame('support_topic', $topic['intent']);
        $this->assertContains('#pb renew_missing', array_column($topic['choices'], 'query'));

        $unknown = $this->support('my printer is broken');
        $this->assertSame('support_unknown', $unknown['intent']);
        $this->assertContains('#cat booking', array_column($unknown['choices'], 'query'));

        // Ask mode offers Support when it does not understand
        $this->assertContains('#home', array_column($this->askAs('tell me a joke')['choices'] ?? [], 'query'));
    }

    public function test_support_endpoint_saves_the_shown_label(): void
    {
        $this->actingAs($this->library, 'library');
        $res = $this->postJson($this->u('ai.chat.send'), ['query' => '#cat booking', 'shown' => 'Seat booking', 'mode' => 'support'])->assertOk();
        $this->assertSame('support', $res->json('reply.mode'));
        $this->assertSame('support_topic', $res->json('reply.intent'));
        $this->assertSame('Seat booking', DB::table('ai_chat_histories')->where('library_id', $this->library->id)->where('sender', 'user')->orderByDesc('id')->value('message'));
    }
}
