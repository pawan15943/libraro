<?php

namespace Tests\Feature;

use App\Models\Library;
use App\Support\HowToUseGuide;
use App\Support\HowToUseGuideContent;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/** The "How Libraro works" guide: content, the page and its use by Limo. */
class HowToUseGuideTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->useStoragePath(sys_get_temp_dir().'/libraro-test-storage');
        foreach (['framework/views', 'framework/cache', 'app', 'logs'] as $dir) {
            @mkdir(storage_path($dir), 0777, true);
        }
        config(['view.compiled' => storage_path('framework/views'), 'cache.default' => 'array', 'session.driver' => 'array', 'app.url' => 'http://localhost']);
        URL::forceRootUrl('http://localhost');
        foreach (array_keys(config('logging.channels', [])) as $channel) {
            if (config("logging.channels.$channel.path")) {
                config(["logging.channels.$channel.path" => sys_get_temp_dir()."/libraro-test-$channel.log"]);
            }
        }
        $this->withoutMiddleware([\App\Http\Middleware\LicenseCheck::class, \App\Http\Middleware\LoggerMiddleware::class, \App\Http\Middleware\VerifyCsrfToken::class]);
    }

    public function test_every_guide_has_both_languages_and_a_known_topic(): void
    {
        $topics = array_keys(HowToUseGuide::categories());
        $titles = [];
        foreach (HowToUseGuideContent::entries() as $e) {
            $this->assertContains($e['cat'], $topics, $e['title'].': unknown topic');
            $this->assertNotEmpty($e['en'], $e['title'].': no English steps');
            $this->assertCount(count($e['en']), $e['hi'], $e['title'].': English and Hindi step counts differ');
            $this->assertMatchesRegularExpression('/[\x{0900}-\x{097F}]/u', $e['title_hi'].implode(' ', $e['hi']), $e['title'].': Hindi text missing');
            $this->assertNotContains($e['title'], $titles, 'duplicate guide: '.$e['title']);
            $titles[] = $e['title'];
        }
        $this->assertGreaterThanOrEqual(60, count($titles));
    }

    public function test_the_table_holds_the_current_guide(): void
    {
        if (! DB::getSchemaBuilder()->hasColumn('how-to-use', 'category')) {
            $this->markTestSkipped('run migration 2026_11_07_000002 first');
        }
        $rows = HowToUseGuide::rows();
        $this->assertSame(count(HowToUseGuideContent::entries()), $rows->count());
        $this->assertSame('start', $rows->first()->category, 'guide starts with Getting started');
        $this->assertSame(0, $rows->whereNull('category')->count());
    }

    public function test_the_page_shows_topics_and_both_languages(): void
    {
        $library = Library::whereNotNull('email_verified_at')->where('status', 1)->where('is_paid', 1)
            ->whereNotNull('current_branch')->whereNull('deleted_at')->orderByDesc('id')->first();
        if (! $library) {
            $this->markTestSkipped('no paid library');
        }
        $this->actingAs($library, 'library');

        $this->get(route('library.how-to-use', [], false))->assertOk()
            ->assertSee('How Libraro works')
            ->assertSee('Limo AI assistant')       // topic chip
            ->assertSee('सीट बुक करें')            // Hindi title
            ->assertSee('lhu-steps', false);        // numbered steps
    }

    public function test_limo_still_finds_the_guides(): void
    {
        $branch = DB::table('learner_detail')->whereNull('deleted_at')->groupBy('branch_id')->orderByRaw('COUNT(*) DESC')->value('branch_id');
        $lib = $branch ? DB::table('branches')->where('id', $branch)->value('library_id') : null;
        if (! $lib) {
            $this->markTestSkipped('no branch');
        }
        $ai = app(\App\Services\LibraroAiService::class);
        foreach (['How to book a seat?' => 'Book Seat', 'How to renew a seat?' => 'Re-New Seat', 'How to freeze a plan?' => 'Freeze / Unfreeze', 'How to reactivate a learner?' => 'Reactivate Existing Learner', 'सीट कैसे बदलें?' => 'Swap Seat (change seat number)'] as $q => $title) {
            $this->assertSame($title, $ai->processQuery($q, (int) $lib, (int) $branch, 'en')['reply']['title'], "Limo answer for: $q");
        }
    }
}
